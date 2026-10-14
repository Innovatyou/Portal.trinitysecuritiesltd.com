<?php

namespace operations_approval\Libraries;

// Requests snapshot their stages at submission time, so changing who
// approves a stage used to leave every in-flight request waiting on the
// OLD approver forever. This keeps open requests in step with the current
// setup, in two ways:
//  - a newly published workflow version: each still-open stage instance
//    moves onto the matching stage of the current version (see match())
//    and takes its name, rule and approvers;
//  - a changed approver behind the SAME definition (role/group members,
//    department head, delegation, a deactivated user): a stage waiting on
//    a decision has its approvers re-resolved.
// Newly resolved people get a pending assignment, people no longer listed
// lose theirs (marked 'reassigned', kept for the audit trail). Recorded
// decisions are never touched, and hand-picked approvers (manual/admin
// override) are left alone.
class Approver_sync
{
    private $db;
    private $p;
    private const OPEN_REQUEST_STATUSES = ['submitted', 'pending_approval', 'information_requested', 'resubmitted', 'returned', 'configuration_error'];
    private const OPEN_STAGE_STATUSES = ['pending', 'active', 'overdue', 'configuration_error'];
    private const REFRESH_EVERY_SECONDS = 300;

    public function __construct()
    {
        $this->db = db_connect('default');
        $this->p = $this->db->getPrefix();
    }

    // $requestId: sync just that request, always re-resolving its active
    // stage (used by the request page). Otherwise sync every request on a
    // superseded version or mis-matched by an earlier sync (one cheap
    // SELECT when there is none), plus - at most every few minutes - a
    // re-resolve of every active stage so inboxes pick up approver changes
    // that never touched the workflow definition.
    public function syncStale(?int $workflowId = null, ?int $requestId = null, ?object $actor = null): void
    {
        try {
            $ids = $this->staleRequestIds($workflowId, $requestId);
            if ($requestId) {
                $ids[] = $requestId;
            } elseif ($this->refreshDue()) {
                $builder = $this->db->table($this->p . 'oa_stage_instances i')->distinct()->select('i.request_id')->join($this->p . 'oa_requests r', 'r.id=i.request_id')
                    ->where('r.deleted', 0)->whereIn('r.status', self::OPEN_REQUEST_STATUSES)->whereIn('i.status', ['active', 'overdue']);
                if ($workflowId) $builder->where('r.workflow_id', $workflowId);
                foreach ($builder->get()->getResult() as $row) $ids[] = (int) $row->request_id;
            }
            foreach (array_unique($ids) as $id) {
                // A second pass settles stages that swapped places (one
                // couldn't move until the other had vacated its slot).
                if ($this->syncRequest((int) $id, $actor)) $this->syncRequest((int) $id, $actor);
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
        $currentVersionId = $this->currentVersionId($workflowId);
        if (!$currentVersionId || $currentVersionId === (int) $stage->version_id) return $stage;
        return $this->match($stage, $this->stagesOf($currentVersionId), $this->stageCount((int) $stage->version_id)) ?: $stage;
    }

    // Same name first, then the same name ignoring a trailing " - person"
    // ("Control - Stella" -> "Control - Kazeem"), and only as a last resort
    // the same position - and only when both versions have the same number
    // of stages. Matching on position alone after a stage was added or
    // removed is what put requests on an entirely different stage (and
    // approver) than the one they were waiting at.
    private function match(object $stage, array $candidates, int $oldStageCount): ?object
    {
        $name = $this->normalise((string) $stage->name);
        foreach ($candidates as $candidate) {
            if ($this->normalise((string) $candidate->name) === $name) return $candidate;
        }
        $base = $this->baseName((string) $stage->name);
        $byBase = array_values(array_filter($candidates, fn($candidate) => $base !== '' && $this->baseName((string) $candidate->name) === $base));
        if (count($byBase) === 1) return $byBase[0];
        if ($oldStageCount === count($candidates)) {
            foreach ($candidates as $candidate) {
                if ((int) $candidate->position === (int) $stage->position) return $candidate;
            }
        }
        return null;
    }

    private function normalise(string $name): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', $name)));
    }

    private function baseName(string $name): string
    {
        return $this->normalise(preg_split('/\s+[-–—(]\s*/u', $name)[0] ?? $name);
    }

    private function staleRequestIds(?int $workflowId, ?int $requestId): array
    {
        $ids = [];
        // On an older version than the workflow's current one...
        $builder = $this->openInstances($workflowId, $requestId)->where('s.version_id <> w.current_version_id', null, false);
        foreach ($builder->get()->getResult() as $row) $ids[] = (int) $row->request_id;
        // ...or moved by an earlier sync onto a stage that isn't the one it
        // was waiting at (its name snapshot no longer matches the stage).
        $builder = $this->openInstances($workflowId, $requestId)->where('s.version_id = w.current_version_id', null, false)->where('i.name_snapshot <> s.name', null, false)
            ->where("EXISTS (SELECT 1 FROM {$this->p}oa_audit au WHERE au.stage_instance_id=i.id AND au.action='approvers_synced')", null, false);
        foreach ($builder->get()->getResult() as $row) $ids[] = (int) $row->request_id;
        return $ids;
    }

    private function openInstances(?int $workflowId, ?int $requestId)
    {
        $builder = $this->db->table($this->p . 'oa_stage_instances i')->distinct()->select('i.request_id')
            ->join($this->p . 'oa_stages s', 's.id=i.stage_id')
            ->join($this->p . 'oa_requests r', 'r.id=i.request_id')
            ->join($this->p . 'oa_workflows w', 'w.id=r.workflow_id')
            ->where('r.deleted', 0)->whereIn('r.status', self::OPEN_REQUEST_STATUSES)->whereIn('i.status', self::OPEN_STAGE_STATUSES)
            ->where('w.current_version_id IS NOT NULL', null, false);
        if ($workflowId) $builder->where('r.workflow_id', $workflowId);
        if ($requestId) $builder->where('r.id', $requestId);
        return $builder;
    }

    private function refreshDue(): bool
    {
        try {
            $cache = cache();
            if ($cache->get('oa_approver_refresh')) return false;
            $cache->save('oa_approver_refresh', 1, self::REFRESH_EVERY_SECONDS);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    // Returns true when a stage couldn't move yet because its target slot
    // was still occupied by another of this request's stages.
    private function syncRequest(int $requestId, ?object $actor): bool
    {
        $notify = false;
        $blocked = false;
        $this->db->transBegin();
        try {
            $request = $this->db->query("SELECT * FROM `{$this->p}oa_requests` WHERE `id`=? AND `deleted`=0 FOR UPDATE", [$requestId])->getRow();
            $currentVersionId = $request ? $this->currentVersionId((int) $request->workflow_id) : 0;
            if (!$request || !$currentVersionId || !in_array($request->status, self::OPEN_REQUEST_STATUSES, true)) {
                $this->db->transCommit();
                return false;
            }
            $newStages = $this->stagesOf($currentVersionId);
            $instances = $this->db->query("SELECT i.*, s.name stage_name, s.position stage_position, s.version_id stage_version_id FROM `{$this->p}oa_stage_instances` i JOIN `{$this->p}oa_stages` s ON s.id=i.stage_id WHERE i.request_id=? AND i.status IN ('pending','active','overdue','configuration_error') ORDER BY i.position FOR UPDATE", [$requestId])->getResult();
            $values = null;
            foreach ($instances as $instance) {
                $change = [];
                $stageChanged = false;
                $target = $this->targetStage($instance, $newStages, $currentVersionId);
                if ($target && ((int) $target->id !== (int) $instance->stage_id || $instance->name_snapshot !== $target->name)) {
                    $taken = (int) $target->id !== (int) $instance->stage_id && $this->db->table($this->p . 'oa_stage_instances')->where(['request_id' => $requestId, 'stage_id' => $target->id, 'cycle_no' => $instance->cycle_no])->countAllResults();
                    if ($taken) $blocked = true;
                    if (!$taken) {
                        $this->db->table($this->p . 'oa_stage_instances')->where('id', $instance->id)->update(['stage_id' => $target->id, 'name_snapshot' => $target->name, 'rule_snapshot' => $target->approval_rule, 'required_count' => $target->required_count, 'lock_version' => ((int) $instance->lock_version) + 1]);
                        $change = ['from_stage_id' => (int) $instance->stage_id, 'to_stage_id' => (int) $target->id, 'from_name' => $instance->name_snapshot, 'to_name' => $target->name];
                        $instance->stage_id = $target->id;
                        $stageChanged = true;
                    }
                }
                if (in_array($instance->status, ['active', 'overdue'], true)) {
                    if ($values === null) $values = $this->requestValues($request);
                    $stage = $this->db->table($this->p . 'oa_stages')->where('id', $instance->stage_id)->get()->getRow();
                    $assignmentChange = $stage ? $this->reassign($instance, $stage, $request, $values, $stageChanged) : [];
                    if ($assignmentChange) $change += $assignmentChange;
                    if (!empty($assignmentChange['added'])) $notify = true;
                }
                if ($change) (new Audit_service())->record('approvers_synced', $requestId, (int) $instance->id, $actor, [], $change);
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
        return $blocked;
    }

    // Which current-version stage $instance belongs on, or null to leave it.
    private function targetStage(object $instance, array $newStages, int $currentVersionId): ?object
    {
        $original = (object) ['name' => $instance->stage_name, 'position' => $instance->stage_position, 'version_id' => $instance->stage_version_id];
        if ((int) $instance->stage_version_id === $currentVersionId) {
            if ($instance->name_snapshot === $instance->stage_name) return null;
            // Already on the current version but under a different name: an
            // earlier sync moved it. Re-match from the stage it was ORIGINALLY
            // waiting at; if that no longer matches anything, put it back
            // there (its own approver config still applies).
            $first = $this->db->table($this->p . 'oa_audit')->select('new_value_json')->where(['stage_instance_id' => $instance->id, 'action' => 'approvers_synced'])->orderBy('id')->get(1)->getRow();
            $fromId = (int) (json_decode($first->new_value_json ?? '{}', true)['from_stage_id'] ?? 0);
            $from = $fromId ? $this->db->table($this->p . 'oa_stages')->where('id', $fromId)->get()->getRow() : null;
            if (!$from) return null;
            return $this->match($from, $newStages, $this->stageCount((int) $from->version_id)) ?: $from;
        }
        return $this->match($original, $newStages, $this->stageCount((int) $instance->stage_version_id));
    }

    private function reassign(object $instance, object $stage, object $request, array $values, bool $stageChanged): array
    {
        $rows = $this->db->table($this->p . 'oa_assignments')->where('stage_instance_id', $instance->id)->get()->getResult();
        foreach ($rows as $row) {
            // Someone picked these approvers by hand - don't second-guess them.
            if ($row->status === 'pending' && in_array($row->source_snapshot, ['manual_override', 'admin_override'], true)) return [];
        }
        $approvers = (new Approver_resolver())->resolve($stage, $request, $values);
        $now = get_current_utc_time();
        if (!$approvers || ($stage->approval_rule === 'minimum' && count($approvers) < (int) $stage->required_count)) {
            // Nobody eligible under the stage's definition. Keep whoever
            // already holds it if they can still act; but if they can't
            // (deactivated) or the stage itself just changed, park the
            // request in configuration_error - the same state activateNext()
            // uses - so an admin gets "Assign approver directly" instead of
            // the stage silently waiting on the wrong person.
            $pendingIds = array_map(fn($row) => (int) $row->user_id, array_filter($rows, fn($row) => $row->status === 'pending'));
            $activePending = $pendingIds ? $this->db->table($this->p . 'users')->whereIn('id', $pendingIds)->where(['status' => 'active', 'deleted' => 0])->countAllResults() : 0;
            if (!$stageChanged && $activePending) return [];
            $this->db->table($this->p . 'oa_assignments')->where(['stage_instance_id' => $instance->id, 'status' => 'pending'])->update(['status' => 'reassigned', 'acted_at' => $now]);
            $this->db->table($this->p . 'oa_stage_instances')->where('id', $instance->id)->update(['status' => 'configuration_error']);
            $this->db->table($this->p . 'oa_requests')->where('id', $request->id)->update(['status' => 'configuration_error', 'current_stage_instance_id' => $instance->id, 'updated_at' => $now]);
            return ['removed' => $pendingIds, 'configuration_error' => true];
        }
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
                $this->db->table($this->p . 'oa_assignments')->where('id', $existing->id)->update(['status' => 'pending', 'acted_at' => null, 'source_snapshot' => $stage->approver_type, 'assigned_at' => $now]);
            } else {
                $this->db->table($this->p . 'oa_assignments')->insert(['stage_instance_id' => $instance->id, 'user_id' => $userId, 'source_snapshot' => $stage->approver_type, 'status' => 'pending', 'assigned_at' => $now]);
            }
            $added[] = $userId;
        }
        return ($added || $removed) ? ['added' => $added, 'removed' => $removed] : [];
    }

    private function currentVersionId(int $workflowId): int
    {
        return (int) ($this->db->table($this->p . 'oa_workflows')->select('current_version_id')->where('id', $workflowId)->get()->getRow()->current_version_id ?? 0);
    }

    private function stagesOf(int $versionId): array
    {
        return $this->db->table($this->p . 'oa_stages')->where('version_id', $versionId)->orderBy('position')->get()->getResult();
    }

    private function stageCount(int $versionId): int
    {
        return $this->db->table($this->p . 'oa_stages')->where('version_id', $versionId)->countAllResults();
    }

    private function requestValues(object $request): array
    {
        $rows = $this->db->table($this->p . 'oa_request_values')->where(['request_id' => $request->id, 'revision_no' => $request->revision_no])->get()->getResult();
        $values = [];
        foreach ($rows as $row) $values[$row->field_key] = $row->value_json ? json_decode($row->value_json, true) : $row->value_text;
        return $values;
    }
}
