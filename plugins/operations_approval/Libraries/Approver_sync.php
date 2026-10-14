<?php

namespace operations_approval\Libraries;

// Requests snapshot their stages at submission time, so publishing a new
// workflow version (e.g. swapping a stage's approver) used to leave every
// in-flight request still waiting on the OLD approver forever. This moves
// each request's still-open stage instances onto the matching stage of the
// workflow's current version - same name, else same position - and, for a
// stage that is already waiting on a decision, re-resolves its approvers:
// newly listed people get a pending assignment, people no longer listed
// lose theirs (marked 'reassigned', kept for the audit trail). Decisions
// already recorded are never touched.
class Approver_sync
{
    private $db;
    private $p;
    private const OPEN_REQUEST_STATUSES = ['submitted', 'pending_approval', 'information_requested', 'resubmitted', 'returned', 'configuration_error'];
    private const OPEN_STAGE_STATUSES = ['pending', 'active', 'overdue', 'configuration_error'];

    public function __construct()
    {
        $this->db = db_connect('default');
        $this->p = $this->db->getPrefix();
    }

    // Cheap when nothing is stale (one indexed SELECT), so it is safe to
    // call from inbox/list pages - that also catches workflows whose
    // approver was changed before this sync existed.
    public function syncStale(?int $workflowId = null, ?int $requestId = null, ?object $actor = null): void
    {
        try {
            $builder = $this->db->table($this->p . 'oa_stage_instances i')->distinct()->select('i.request_id')
                ->join($this->p . 'oa_stages s', 's.id=i.stage_id')
                ->join($this->p . 'oa_requests r', 'r.id=i.request_id')
                ->join($this->p . 'oa_workflows w', 'w.id=r.workflow_id')
                ->where('r.deleted', 0)->whereIn('r.status', self::OPEN_REQUEST_STATUSES)->whereIn('i.status', self::OPEN_STAGE_STATUSES)
                ->where('w.current_version_id IS NOT NULL', null, false)->where('s.version_id <> w.current_version_id', null, false);
            if ($workflowId) $builder->where('r.workflow_id', $workflowId);
            if ($requestId) $builder->where('r.id', $requestId);
            foreach ($builder->get()->getResult() as $row) {
                $this->syncRequest((int) $row->request_id, $actor);
            }
        } catch (\Throwable $e) {
            log_message('error', 'operations_approval approver sync failed: ' . $e->getMessage());
        }
    }

    // The stage of the workflow's current version that corresponds to
    // $stage (a stage of an older version), or $stage itself if the
    // workflow has no newer version or nothing matches.
    public function currentStageFor(object $stage, int $workflowId): object
    {
        $currentVersionId = (int) ($this->db->table($this->p . 'oa_workflows')->select('current_version_id')->where('id', $workflowId)->get()->getRow()->current_version_id ?? 0);
        if (!$currentVersionId || $currentVersionId === (int) $stage->version_id) return $stage;
        $stages = $this->db->table($this->p . 'oa_stages')->where('version_id', $currentVersionId)->get()->getResult();
        return $this->match($stage, $stages) ?: $stage;
    }

    private function match(object $stage, array $candidates): ?object
    {
        $name = mb_strtolower(trim((string) $stage->name));
        foreach ($candidates as $candidate) {
            if (mb_strtolower(trim((string) $candidate->name)) === $name) return $candidate;
        }
        foreach ($candidates as $candidate) {
            if ((int) $candidate->position === (int) $stage->position) return $candidate;
        }
        return null;
    }

    private function syncRequest(int $requestId, ?object $actor): void
    {
        $notify = false;
        $this->db->transBegin();
        try {
            $request = $this->db->query("SELECT * FROM `{$this->p}oa_requests` WHERE `id`=? AND `deleted`=0 FOR UPDATE", [$requestId])->getRow();
            $currentVersionId = $request ? (int) ($this->db->table($this->p . 'oa_workflows')->select('current_version_id')->where('id', $request->workflow_id)->get()->getRow()->current_version_id ?? 0) : 0;
            if (!$request || !$currentVersionId || !in_array($request->status, self::OPEN_REQUEST_STATUSES, true)) {
                $this->db->transCommit();
                return;
            }
            $newStages = $this->db->table($this->p . 'oa_stages')->where('version_id', $currentVersionId)->get()->getResult();
            $instances = $this->db->query("SELECT i.*, s.name stage_name, s.position stage_position, s.version_id stage_version_id FROM `{$this->p}oa_stage_instances` i JOIN `{$this->p}oa_stages` s ON s.id=i.stage_id WHERE i.request_id=? AND i.status IN ('pending','active','overdue','configuration_error') FOR UPDATE", [$requestId])->getResult();
            $values = null;
            foreach ($instances as $instance) {
                if ((int) $instance->stage_version_id === $currentVersionId) continue;
                $newStage = $this->match((object) ['name' => $instance->stage_name, 'position' => $instance->stage_position], $newStages);
                if (!$newStage) continue;
                $taken = $this->db->table($this->p . 'oa_stage_instances')->where(['request_id' => $requestId, 'stage_id' => $newStage->id, 'cycle_no' => $instance->cycle_no])->countAllResults();
                if ($taken) continue;
                $this->db->table($this->p . 'oa_stage_instances')->where('id', $instance->id)->update(['stage_id' => $newStage->id, 'rule_snapshot' => $newStage->approval_rule, 'required_count' => $newStage->required_count, 'lock_version' => ((int) $instance->lock_version) + 1]);
                $change = ['from_stage_id' => (int) $instance->stage_id, 'to_stage_id' => (int) $newStage->id];
                if (in_array($instance->status, ['active', 'overdue'], true)) {
                    if ($values === null) $values = $this->requestValues($request);
                    $change += $this->reassign($instance, $newStage, $request, $values);
                    if (!empty($change['added'])) $notify = true;
                }
                (new Audit_service())->record('approvers_synced', $requestId, (int) $instance->id, $actor, [], $change);
            }
            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
        if ($notify) {
            try {
                $rows = $this->db->table($this->p . 'oa_assignments a')->select('a.user_id, a.stage_instance_id')->join($this->p . 'oa_stage_instances i', 'i.id=a.stage_instance_id')->where(['i.request_id' => $requestId, 'a.status' => 'pending'])->whereIn('i.status', ['active', 'overdue'])->get()->getResult();
                if ($rows) (new Notification_service())->send('approval_assigned', $requestId, array_map(fn($r) => (int) $r->user_id, $rows), $actor, ['dedupe' => 'stage-' . $rows[0]->stage_instance_id]);
            } catch (\Throwable $e) {
                log_message('error', 'operations_approval notification failed: ' . $e->getMessage());
            }
        }
    }

    private function reassign(object $instance, object $newStage, object $request, array $values): array
    {
        $approvers = (new Approver_resolver())->resolve($newStage, $request, $values);
        // A new definition that resolves to nobody (or too few) would leave
        // the stage with no-one able to act - keep the existing approvers
        // rather than strand the request.
        if (!$approvers || ($newStage->approval_rule === 'minimum' && count($approvers) < (int) $newStage->required_count)) return ['skipped' => 'no_eligible_approvers'];
        $now = get_current_utc_time();
        $rows = $this->db->table($this->p . 'oa_assignments')->where('stage_instance_id', $instance->id)->get()->getResult();
        $removed = [];
        $covered = [];
        foreach ($rows as $row) {
            $userId = (int) $row->user_id;
            $originalId = (int) ($row->original_user_id ?? 0);
            $stillListed = in_array($userId, $approvers, true) || ($originalId && in_array($originalId, $approvers, true));
            if ($row->status === 'pending' && !$stillListed) {
                $this->db->table($this->p . 'oa_assignments')->where('id', $row->id)->update(['status' => 'reassigned', 'acted_at' => $now]);
                $removed[] = $userId;
                continue;
            }
            if ($row->status === 'reassigned') continue;
            $covered[] = $userId;
            if ($originalId) $covered[] = $originalId;
        }
        $added = [];
        foreach ($approvers as $userId) {
            if (in_array($userId, $covered, true)) continue;
            $existing = null;
            foreach ($rows as $row) { if ((int) $row->user_id === $userId) { $existing = $row; break; } }
            if ($existing) {
                $this->db->table($this->p . 'oa_assignments')->where('id', $existing->id)->update(['status' => 'pending', 'acted_at' => null, 'source_snapshot' => $newStage->approver_type, 'assigned_at' => $now]);
            } else {
                $this->db->table($this->p . 'oa_assignments')->insert(['stage_instance_id' => $instance->id, 'user_id' => $userId, 'source_snapshot' => $newStage->approver_type, 'status' => 'pending', 'assigned_at' => $now]);
            }
            $added[] = $userId;
        }
        return ['added' => $added, 'removed' => $removed];
    }

    private function requestValues(object $request): array
    {
        $rows = $this->db->table($this->p . 'oa_request_values')->where(['request_id' => $request->id, 'revision_no' => $request->revision_no])->get()->getResult();
        $values = [];
        foreach ($rows as $row) $values[$row->field_key] = $row->value_json ? json_decode($row->value_json, true) : $row->value_text;
        return $values;
    }
}
