<?php

namespace operations_approval\Libraries;

class Delegation_service
{
    private $db;
    private $p;

    public function __construct()
    {
        $this->db = db_connect('default');
        $this->p = $this->db->getPrefix();
    }

    public function activeDelegate(int $userId, int $workflowId): ?int
    {
        $now = get_current_utc_time();
        $row = $this->db->table($this->p . 'oa_delegations')->where('user_id', $userId)->where('starts_at <=', $now)->where('ends_at >=', $now)->where('revoked_at', null)->groupStart()->where('workflow_id', null)->orWhere('workflow_id', $workflowId)->groupEnd()->orderBy('workflow_id', 'DESC')->get(1)->getRow();
        return $row ? (int) $row->delegate_id : null;
    }

    public function delegateAssignment(int $assignmentId, int $delegateId, object $actor, string $reason): void
    {
        if (!$delegateId || trim($reason) === '') throw new \InvalidArgumentException('Delegate and reason are required.');
        $assignment = $this->db->table($this->p . 'oa_assignments a')->select('a.*, i.request_id')->join($this->p . 'oa_stage_instances i', 'i.id=a.stage_instance_id')->where(['a.id' => $assignmentId, 'a.status' => 'pending'])->get()->getRow();
        if (!$assignment || ((int) $assignment->user_id !== (int) $actor->id && empty($actor->is_admin))) throw new \DomainException('The assignment cannot be delegated.');
        $this->db->transStart();
        $this->db->table($this->p . 'oa_assignments')->where('id', $assignmentId)->update(['status' => 'delegated']);
        $this->db->table($this->p . 'oa_assignments')->insert(['stage_instance_id' => $assignment->stage_instance_id, 'user_id' => $delegateId, 'original_user_id' => $assignment->user_id, 'source_snapshot' => 'delegation', 'status' => 'pending', 'assigned_at' => get_current_utc_time()]);
        (new Audit_service())->record('approval_delegated', (int) $assignment->request_id, (int) $assignment->stage_instance_id, $actor, ['user_id' => $assignment->user_id], ['delegate_id' => $delegateId], ['reason' => $reason]);
        $this->db->transComplete();
        (new Notification_service())->send('approval_delegated', (int) $assignment->request_id, [$delegateId], $actor, ['comment' => $reason, 'dedupe' => $assignmentId]);
    }
}
