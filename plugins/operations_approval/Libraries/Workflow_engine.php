<?php

namespace operations_approval\Libraries;

class Workflow_engine
{
    private $db;
    private $p;
    private $audit;

    public function __construct()
    {
        $this->db = db_connect('default');
        $this->p = $this->db->getPrefix();
        $this->audit = new Audit_service();
    }

    public function submit(int $requestId, object $actor): void
    {
        $this->db->transBegin();
        try {
            $request = $this->lockRequest($requestId);
            if ($request->status !== 'draft' && $request->status !== 'returned') {
                throw new \DomainException('Only draft or returned requests can be submitted.');
            }
            $workflow = $this->db->table($this->p . 'oa_workflows')->where('id', $request->workflow_id)->get()->getRow();
            if (!$workflow || $workflow->status !== 'active' || !$workflow->current_version_id) {
                throw new \DomainException('The selected workflow is not published and active.');
            }
            $number = $request->request_no ?: (new Request_number_service())->next((int) $workflow->id, $workflow->prefix);
            $now = get_current_utc_time();
            $this->db->table($this->p . 'oa_requests')->where('id', $requestId)->update([
                'request_no' => $number, 'version_id' => $workflow->current_version_id, 'status' => 'submitted',
                'submitted_at' => $now, 'updated_at' => $now, 'lock_version' => ((int) $request->lock_version) + 1
            ]);
            $request->version_id = $workflow->current_version_id;
            $request->request_no = $number;
            $this->snapshotStages($request);
            $this->activateNext($request, $actor);
            $this->audit->record('request_submitted', $requestId, null, $actor, [], ['request_no' => $number, 'version_id' => $workflow->current_version_id]);
            $this->db->transCommit();
            (new Notification_service())->send('request_submitted', $requestId, [$actor->id], $actor, ['dedupe' => 'submit-' . $requestId]);
            $this->notifyActiveApprovers($requestId, $actor);
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    public function decide(int $requestId, int $stageInstanceId, int $expectedLockVersion, string $decision, string $comment, object $actor): void
    {
        if (!in_array($decision, ['approve', 'reject', 'return'], true)) {
            throw new \InvalidArgumentException('Unsupported decision.');
        }
        if (in_array($decision, ['reject', 'return'], true) && trim($comment) === '') {
            throw new \InvalidArgumentException('A reason is required.');
        }
        $this->db->transBegin();
        try {
            $request = $this->lockRequest($requestId);
            $stage = $this->db->query("SELECT * FROM `{$this->p}oa_stage_instances` WHERE `id`=? FOR UPDATE", [$stageInstanceId])->getRow();
            if (!$stage || (int) $stage->request_id !== $requestId || !in_array($stage->status, ['active', 'overdue'], true) || (int) $stage->lock_version !== $expectedLockVersion || (int) $request->current_stage_instance_id !== $stageInstanceId) {
                throw new \DomainException('This approval is stale or no longer active. Refresh the request.');
            }
            $assignment = $this->db->table($this->p . 'oa_assignments')->where(['stage_instance_id' => $stageInstanceId, 'user_id' => $actor->id, 'status' => 'pending'])->get()->getRow();
            if (!$assignment) {
                throw new \DomainException('You are not an active approver for this stage.');
            }
            $now = get_current_utc_time();
            $this->db->table($this->p . 'oa_decisions')->insert([
                'request_id' => $requestId, 'stage_instance_id' => $stageInstanceId, 'assignment_id' => $assignment->id,
                'actor_id' => $actor->id, 'decision' => $decision, 'comment' => $comment,
                'actor_name_snapshot' => trim(($actor->first_name ?? '') . ' ' . ($actor->last_name ?? '')),
                'created_at' => $now, 'ip_address' => service('request')->getIPAddress()
            ]);
            $this->db->table($this->p . 'oa_assignments')->where('id', $assignment->id)->update(['status' => $decision, 'acted_at' => $now]);
            if ($decision === 'reject' || $decision === 'return') {
                $requestStatus = $decision === 'reject' ? 'rejected' : 'returned';
                $this->db->table($this->p . 'oa_stage_instances')->where('id', $stageInstanceId)->update(['status' => $requestStatus, 'completed_at' => $now, 'lock_version' => ((int) $stage->lock_version) + 1]);
                $requestUpdate = ['status' => $requestStatus, 'current_stage_instance_id' => null, 'updated_at' => $now, 'lock_version' => ((int) $request->lock_version) + 1];
                if ($decision === 'return') {
                    $settings = json_decode($this->db->table($this->p . 'oa_stages')->select('settings_json')->where('id', $stage->stage_id)->get()->getRow()->settings_json ?: '{}', true);
                    $requestUpdate['return_stage_instance_id'] = $stageInstanceId;
                    $requestUpdate['return_strategy'] = $settings['return_strategy'] ?? 'same_stage';
                }
                $this->db->table($this->p . 'oa_requests')->where('id', $requestId)->update($requestUpdate);
            } elseif ($this->approvalThresholdMet($stageInstanceId, $stage)) {
                $this->db->table($this->p . 'oa_stage_instances')->where('id', $stageInstanceId)->update(['status' => 'approved', 'completed_at' => $now, 'lock_version' => ((int) $stage->lock_version) + 1]);
                $this->db->table($this->p . 'oa_assignments')->where(['stage_instance_id' => $stageInstanceId, 'status' => 'pending'])->update(['status' => 'not_required']);
                $this->activateNext($request, $actor, (int) $stage->position);
            }
            $this->audit->record($decision === 'approve' ? 'stage_approved' : 'request_' . $decision . 'ed', $requestId, $stageInstanceId, $actor, [], ['comment' => $comment]);
            $this->db->transCommit();
            $event = $decision === 'approve' ? 'request_approved' : ($decision === 'reject' ? 'request_rejected' : 'request_returned');
            (new Notification_service())->send($event, $requestId, (new Notification_service())->requester($requestId), $actor, ['comment' => $comment, 'dedupe' => 'decision-' . $stageInstanceId . '-' . $assignment->id]);
            if ($decision === 'approve') $this->notifyActiveApprovers($requestId, $actor);
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    public function resubmit(int $requestId, object $actor): void
    {
        $this->db->transBegin();
        try {
            $request = $this->lockRequest($requestId);
            if ($request->status !== 'returned' || (int) $request->requester_id !== (int) $actor->id || !$request->return_stage_instance_id) throw new \DomainException('This request cannot be resubmitted.');
            $returned = $this->db->table($this->p . 'oa_stage_instances')->where('id', $request->return_stage_instance_id)->get()->getRow();
            if (!$returned) throw new \DomainException('Returned stage history is missing.');
            $position = $request->return_strategy === 'restart' ? 1 : (int) $returned->position;
            $stage = $this->db->table($this->p . 'oa_stages')->where(['version_id' => $request->version_id, 'position' => $position])->get()->getRow();
            if (!$stage) throw new \DomainException('Workflow stage cannot be resumed.');
            $maxCycle = $this->db->table($this->p . 'oa_stage_instances')->selectMax('cycle_no', 'cycle')->where(['request_id' => $requestId, 'stage_id' => $stage->id])->get()->getRow();
            $this->db->table($this->p . 'oa_stage_instances')->insert(['request_id' => $requestId, 'stage_id' => $stage->id, 'position' => $stage->position, 'name_snapshot' => $stage->name, 'type_snapshot' => $stage->stage_type, 'status' => 'pending', 'cycle_no' => ((int) ($maxCycle->cycle ?? 0)) + 1, 'rule_snapshot' => $stage->approval_rule, 'required_count' => $stage->required_count]);
            $now = get_current_utc_time();
            $this->db->table($this->p . 'oa_requests')->where('id', $requestId)->update(['status' => 'resubmitted', 'return_stage_instance_id' => null, 'updated_at' => $now, 'lock_version' => ((int) $request->lock_version) + 1]);
            $request->status = 'resubmitted';
            $this->activateNext($request, $actor, $position - 1);
            $this->audit->record('request_resubmitted', $requestId, null, $actor, [], ['revision_no' => $request->revision_no, 'strategy' => $request->return_strategy]);
            $this->db->transCommit();
            (new Notification_service())->send('request_resubmitted', $requestId, $this->priorApprovers($requestId), $actor, ['dedupe' => 'resubmit-' . $request->revision_no]);
            $this->notifyActiveApprovers($requestId, $actor);
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    private function snapshotStages(object $request): void
    {
        $stages = $this->db->table($this->p . 'oa_stages')->where('version_id', $request->version_id)->orderBy('position')->get()->getResult();
        foreach ($stages as $stage) {
            $this->db->table($this->p . 'oa_stage_instances')->insert([
                'request_id' => $request->id, 'stage_id' => $stage->id, 'position' => $stage->position,
                'name_snapshot' => $stage->name, 'type_snapshot' => $stage->stage_type, 'status' => 'pending',
                'rule_snapshot' => $stage->approval_rule, 'required_count' => $stage->required_count
            ]);
        }
    }

    private function activateNext(object $request, object $actor, int $afterPosition = 0): void
    {
        $values = $this->requestValues((int) $request->id);
        $instances = $this->db->query("SELECT i.*, s.condition_json, s.approver_type, s.approver_config_json, s.settings_json, s.sla_minutes FROM `{$this->p}oa_stage_instances` i JOIN `{$this->p}oa_stages` s ON s.id=i.stage_id WHERE i.request_id=? AND i.position>? AND i.status='pending' ORDER BY i.position", [$request->id, $afterPosition])->getResult();
        foreach ($instances as $instance) {
            $result = (new Condition_evaluator())->evaluate(json_decode($instance->condition_json ?: 'null', true), $values);
            if (!$result['matched']) {
                $this->db->table($this->p . 'oa_stage_instances')->where('id', $instance->id)->update(['status' => 'skipped', 'condition_result_json' => json_encode($result), 'completed_at' => get_current_utc_time()]);
                $this->audit->record('stage_skipped', (int) $request->id, (int) $instance->id, $actor, [], $result);
                continue;
            }
            $approvers = (new Approver_resolver())->resolve($instance, $request, $values);
            if (!$approvers || ($instance->rule_snapshot === 'minimum' && count($approvers) < (int) $instance->required_count)) {
                $this->db->table($this->p . 'oa_requests')->where('id', $request->id)->update(['status' => 'configuration_error', 'current_stage_instance_id' => $instance->id]);
                $this->db->table($this->p . 'oa_stage_instances')->where('id', $instance->id)->update(['status' => 'configuration_error', 'condition_result_json' => json_encode($result)]);
                $this->audit->record('approver_resolution_failed', (int) $request->id, (int) $instance->id, $actor);
                return;
            }
            $now = get_current_utc_time();
            $due = $instance->sla_minutes ? date('Y-m-d H:i:s', strtotime($now . ' +' . (int) $instance->sla_minutes . ' minutes')) : null;
            $this->db->table($this->p . 'oa_stage_instances')->where('id', $instance->id)->update(['status' => 'active', 'activated_at' => $now, 'due_at' => $due, 'condition_result_json' => json_encode($result)]);
            foreach ($approvers as $userId) {
                $this->db->table($this->p . 'oa_assignments')->insert(['stage_instance_id' => $instance->id, 'user_id' => $userId, 'source_snapshot' => $instance->approver_type, 'status' => 'pending', 'assigned_at' => $now]);
            }
            $this->db->table($this->p . 'oa_requests')->where('id', $request->id)->update(['status' => 'pending_approval', 'current_stage_instance_id' => $instance->id, 'updated_at' => $now]);
            $this->audit->record('stage_activated', (int) $request->id, (int) $instance->id, $actor, [], ['approvers' => $approvers, 'condition' => $result]);
            return;
        }
        $now = get_current_utc_time();
        $this->db->table($this->p . 'oa_requests')->where('id', $request->id)->update(['status' => 'completed', 'current_stage_instance_id' => null, 'completed_at' => $now, 'updated_at' => $now]);
        $this->audit->record('request_completed', (int) $request->id, null, $actor);
    }

    private function approvalThresholdMet(int $stageId, object $stage): bool
    {
        $total = $this->db->table($this->p . 'oa_assignments')->where('stage_instance_id', $stageId)->countAllResults();
        $approved = $this->db->table($this->p . 'oa_assignments')->where(['stage_instance_id' => $stageId, 'status' => 'approve'])->countAllResults();
        if ($stage->rule_snapshot === 'all') return $approved >= $total;
        if ($stage->rule_snapshot === 'minimum') return $approved >= (int) $stage->required_count;
        if ($stage->rule_snapshot === 'majority') return $approved > ($total / 2);
        return $approved >= 1;
    }

    private function requestValues(int $requestId): array
    {
        $request = $this->db->table($this->p . 'oa_requests')->where('id', $requestId)->get()->getRow();
        $rows = $this->db->table($this->p . 'oa_request_values')->where(['request_id' => $requestId, 'revision_no' => $request->revision_no])->get()->getResult();
        $values = [];
        foreach ($rows as $row) $values[$row->field_key] = $row->value_json ? json_decode($row->value_json, true) : $row->value_text;
        return $values;
    }

    private function lockRequest(int $requestId): object
    {
        $request = $this->db->query("SELECT * FROM `{$this->p}oa_requests` WHERE `id`=? AND `deleted`=0 FOR UPDATE", [$requestId])->getRow();
        if (!$request) throw new \DomainException('Request not found.');
        return $request;
    }

    private function notifyActiveApprovers(int $requestId, object $actor): void
    {
        $rows = $this->db->table($this->p . 'oa_assignments a')->select('a.user_id, a.stage_instance_id')->join($this->p . 'oa_stage_instances i', 'i.id=a.stage_instance_id')->where(['i.request_id' => $requestId, 'i.status' => 'active', 'a.status' => 'pending'])->get()->getResult();
        if ($rows) (new Notification_service())->send('approval_assigned', $requestId, array_map(fn($r) => (int) $r->user_id, $rows), $actor, ['dedupe' => 'stage-' . $rows[0]->stage_instance_id]);
    }

    private function priorApprovers(int $requestId): array
    {
        $rows = $this->db->table($this->p . 'oa_decisions')->select('actor_id')->where('request_id', $requestId)->get()->getResult();
        return array_map(fn($row) => (int) $row->actor_id, $rows);
    }
}
