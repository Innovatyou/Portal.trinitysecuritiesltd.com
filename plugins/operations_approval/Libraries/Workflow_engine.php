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
            $number = $request->request_no ?: (new Request_number_service())->next($workflow->prefix);
            $now = get_current_utc_time();
            // DBDebug is off in production, so a failed UPDATE (e.g. a
            // request_no collision that somehow still occurs) returns
            // false instead of throwing - check explicitly rather than
            // silently letting the request advance to "submitted" with no
            // request_no, which is exactly how that bug used to surface.
            $updated = $this->db->table($this->p . 'oa_requests')->where('id', $requestId)->update([
                'request_no' => $number, 'version_id' => $workflow->current_version_id, 'status' => 'submitted',
                'submitted_at' => $now, 'updated_at' => $now, 'lock_version' => ((int) $request->lock_version) + 1
            ]);
            if (!$updated) throw new \RuntimeException('Could not assign a request number. Please try again.');
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

    public function decide(int $requestId, int $stageInstanceId, int $expectedLockVersion, string $decision, string $comment, object $actor, bool $isOverride = false): void
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
                if (!$isOverride) {
                    throw new \DomainException('You are not an active approver for this stage.');
                }
                // operations_admin_override: hand this stage a pending
                // assignment for the actor on the fly - the exact same
                // mechanism Delegation_service uses to hand an assignment
                // to someone else - so the decision below runs through the
                // normal recording/threshold logic instead of a parallel
                // code path. The unique key on (stage_instance_id,user_id)
                // means this can only fail if the actor already holds some
                // OTHER-status row for this stage (e.g. already decided,
                // or delegated away) - surface that plainly rather than
                // letting DBDebug=false swallow it as a silent no-op.
                $inserted = $this->db->table($this->p . 'oa_assignments')->insert(['stage_instance_id' => $stageInstanceId, 'user_id' => $actor->id, 'source_snapshot' => 'admin_override', 'status' => 'pending', 'assigned_at' => get_current_utc_time()]);
                if (!$inserted) {
                    throw new \DomainException('You have already acted on this stage.');
                }
                $assignment = $this->db->table($this->p . 'oa_assignments')->where(['stage_instance_id' => $stageInstanceId, 'user_id' => $actor->id, 'status' => 'pending'])->get()->getRow();
                $this->audit->record('admin_override_assigned', $requestId, $stageInstanceId, $actor);
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

    // The approval $userId gave on this request that they can still take
    // back, or null. Only their most recent approval qualifies, and only
    // while nobody has acted on the request since: a later decision on
    // another stage, an open information request, a return/rejection or
    // cancellation all mean the mistake has already been built on, so
    // unwinding it would silently erase someone else's work.
    public function revocableApproval(int $requestId, int $userId): ?object
    {
        $request = $this->db->table($this->p . 'oa_requests')->where(['id' => $requestId, 'deleted' => 0])->get()->getRow();
        if (!$request || !in_array($request->status, ['pending_approval', 'completed', 'configuration_error'], true)) return null;
        $decision = $this->db->table($this->p . 'oa_decisions d')->select('d.*, a.source_snapshot, i.status stage_status, i.position, i.lock_version stage_lock_version')
            ->join($this->p . 'oa_assignments a', 'a.id=d.assignment_id')->join($this->p . 'oa_stage_instances i', 'i.id=d.stage_instance_id')
            ->where(['d.request_id' => $requestId, 'd.actor_id' => $userId, 'd.decision' => 'approve', 'a.status' => 'approve'])
            ->orderBy('d.id', 'DESC')->get(1)->getRow();
        if (!$decision || !in_array($decision->stage_status, ['active', 'overdue', 'approved'], true)) return null;
        $laterDecisions = $this->db->table($this->p . 'oa_decisions')->where(['request_id' => $requestId, 'stage_instance_id !=' => $decision->stage_instance_id, 'id >' => $decision->id])->countAllResults();
        if ($laterDecisions) return null;
        // Still-open stage: the request must still be sitting on it. Closed
        // (approved) stage: the request has either moved to a later stage
        // nobody has touched yet, or completed because this was the last one.
        if ($decision->stage_status !== 'approved' && (int) $request->current_stage_instance_id !== (int) $decision->stage_instance_id) return null;
        return $decision;
    }

    public function revokeApproval(int $requestId, object $actor, string $reason): void
    {
        if (trim($reason) === '') throw new \InvalidArgumentException('A reason is required.');
        $this->db->transBegin();
        try {
            $request = $this->lockRequest($requestId);
            $decision = $this->revocableApproval($requestId, (int) $actor->id);
            if (!$decision) throw new \DomainException('This approval can no longer be revoked - the request has already moved on.');
            $stageInstanceId = (int) $decision->stage_instance_id;
            $now = get_current_utc_time();
            $reset = [];
            if ($decision->stage_status === 'approved') {
                // Rewind every stage this approval activated or skipped on
                // its way forward. revocableApproval() already guaranteed
                // none of them carries a decision, so their assignments hold
                // nothing worth keeping - delete rather than cancel in place,
                // or the (stage_instance_id,user_id) unique key would block
                // re-assigning the same approver when the stage reopens.
                $later = $this->db->table($this->p . 'oa_stage_instances')->where(['request_id' => $requestId, 'position >' => (int) $decision->position])
                    ->groupStart()->whereIn('status', ['active', 'overdue', 'configuration_error'])->orGroupStart()->where('status', 'skipped')->where('completed_at >=', $decision->created_at)->groupEnd()->groupEnd()
                    ->get()->getResult();
                foreach ($later as $instance) {
                    $this->db->table($this->p . 'oa_assignments')->where('stage_instance_id', $instance->id)->delete();
                    $this->db->table($this->p . 'oa_stage_instances')->where('id', $instance->id)->update(['status' => 'pending', 'activated_at' => null, 'due_at' => null, 'completed_at' => null, 'condition_result_json' => null, 'lock_version' => ((int) $instance->lock_version) + 1]);
                    $reset[] = (int) $instance->id;
                }
                // Co-approvers whose turn was cut short when the threshold
                // was met get their pending assignment back.
                $this->db->table($this->p . 'oa_assignments')->where(['stage_instance_id' => $stageInstanceId, 'status' => 'not_required'])->update(['status' => 'pending', 'acted_at' => null]);
            }
            $this->db->table($this->p . 'oa_stage_instances')->where('id', $stageInstanceId)->update(['status' => 'active', 'completed_at' => null, 'lock_version' => ((int) $decision->stage_lock_version) + 1]);
            // One decision per assignment (unique key), so the original row
            // has to go for the approver to be able to decide again. The
            // audit entry below keeps everything it held.
            $this->db->table($this->p . 'oa_decisions')->where('id', $decision->id)->delete();
            if ($decision->source_snapshot === 'admin_override') {
                // They were never on this stage's approver list - drop the
                // on-the-fly assignment instead of leaving it in their inbox.
                $this->db->table($this->p . 'oa_assignments')->where('id', $decision->assignment_id)->delete();
            } else {
                $this->db->table($this->p . 'oa_assignments')->where('id', $decision->assignment_id)->update(['status' => 'pending', 'acted_at' => null]);
            }
            $this->db->table($this->p . 'oa_requests')->where('id', $requestId)->update(['status' => 'pending_approval', 'current_stage_instance_id' => $stageInstanceId, 'completed_at' => null, 'updated_at' => $now, 'lock_version' => ((int) $request->lock_version) + 1]);
            $this->db->table($this->p . 'oa_comments')->insert(['request_id' => $requestId, 'stage_instance_id' => $stageInstanceId, 'user_id' => $actor->id, 'user_name_snapshot' => trim(($actor->first_name ?? '') . ' ' . ($actor->last_name ?? '')), 'comment' => 'Approval revoked: ' . clean_data($reason), 'comment_type' => 'approval_revoked', 'visibility' => 'workflow', 'created_at' => $now]);
            $this->audit->record('approval_revoked', $requestId, $stageInstanceId, $actor, ['decision_id' => (int) $decision->id, 'decision' => 'approve', 'comment' => $decision->comment, 'decided_at' => $decision->created_at, 'request_status' => $request->status], ['reset_stage_instance_ids' => $reset], ['reason' => $reason]);
            $this->db->transCommit();
            $recipients = array_merge((new Notification_service())->requester($requestId), $this->stageApprovers($stageInstanceId, (int) $actor->id));
            (new Notification_service())->send('approval_revoked', $requestId, $recipients, $actor, ['comment' => $reason, 'dedupe' => 'revoke-' . $decision->id]);
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    private function stageApprovers(int $stageInstanceId, int $exceptUserId): array
    {
        $rows = $this->db->table($this->p . 'oa_assignments')->select('user_id')->where(['stage_instance_id' => $stageInstanceId, 'status' => 'pending', 'user_id !=' => $exceptUserId])->get()->getResult();
        return array_map(fn($row) => (int) $row->user_id, $rows);
    }

    // A stage that resolves to zero eligible approvers (no manager/department
    // head assigned, an empty group, self-approval filtered the only
    // candidate out, etc.) deliberately parks the request in
    // configuration_error rather than silently skipping or auto-approving
    // it (see ADMIN_GUIDE.md). Once whoever manages workflows fixes the
    // underlying setup (assigns a department head, adds a group member...),
    // this re-opens that same stuck stage and tries resolution again -
    // otherwise the only way out was a direct database edit.
    // Re-resolving from the stage's own (immutable, version-locked)
    // approver_config_json only helps when the surrounding DATA has
    // changed since - a manager got assigned, a group gained a member, a
    // department head got set. It can never help when the config itself
    // is simply wrong, most commonly a "specific people" stage whose
    // frozen user list turns out to BE the requester and gets filtered
    // out by allow_self_approval - re-running the exact same resolution
    // just fails the exact same way forever. $manualApproverIds lets an
    // admin hand this one stuck stage directly to specific people
    // instead, without needing to touch the (possibly shared, published)
    // stage definition at all.
    public function retryConfiguration(int $requestId, object $actor, array $manualApproverIds = []): void
    {
        $this->db->transBegin();
        try {
            $request = $this->lockRequest($requestId);
            if ($request->status !== 'configuration_error' || !$request->current_stage_instance_id) {
                throw new \DomainException('This request is not stuck on a configuration error.');
            }
            $stage = $this->db->table($this->p . 'oa_stage_instances')->where('id', $request->current_stage_instance_id)->get()->getRow();
            if (!$stage) throw new \DomainException('The stuck stage could not be found.');
            $this->db->table($this->p . 'oa_requests')->where('id', $requestId)->update(['status' => 'pending_approval']);
            $request->status = 'pending_approval';
            $manualApproverIds = array_values(array_unique(array_filter(array_map('intval', $manualApproverIds))));
            if ($manualApproverIds) {
                $now = get_current_utc_time();
                $this->db->table($this->p . 'oa_stage_instances')->where('id', $stage->id)->update(['status' => 'active', 'activated_at' => $now, 'condition_result_json' => null]);
                foreach ($manualApproverIds as $userId) {
                    // (stage_instance_id,user_id) is unique - a retry that
                    // re-picks someone already handed this stage (e.g. a
                    // second manual retry with an overlapping selection)
                    // would otherwise hit that and (DBDebug is off) fail
                    // silently; skip rather than re-insert.
                    $already = $this->db->table($this->p . 'oa_assignments')->where(['stage_instance_id' => $stage->id, 'user_id' => $userId])->countAllResults();
                    if (!$already) {
                        $this->db->table($this->p . 'oa_assignments')->insert(['stage_instance_id' => $stage->id, 'user_id' => $userId, 'source_snapshot' => 'manual_override', 'status' => 'pending', 'assigned_at' => $now]);
                    }
                }
                $this->db->table($this->p . 'oa_requests')->where('id', $requestId)->update(['current_stage_instance_id' => $stage->id, 'updated_at' => $now]);
                $this->audit->record('configuration_retry_manual', $requestId, (int) $stage->id, $actor, [], ['approver_ids' => $manualApproverIds]);
            } else {
                $this->db->table($this->p . 'oa_stage_instances')->where('id', $stage->id)->update(['status' => 'pending', 'condition_result_json' => null]);
                $this->db->table($this->p . 'oa_requests')->where('id', $requestId)->update(['current_stage_instance_id' => null]);
                $this->activateNext($request, $actor, ((int) $stage->position) - 1);
                $this->audit->record('configuration_retry', $requestId, (int) $stage->id, $actor);
            }
            $this->db->transCommit();
            $this->notifyActiveApprovers($requestId, $actor);
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

    // Superadmin-only (operations_admin_override): reshuffle a request's
    // not-yet-decided stages relative to each other, e.g. moving Control
    // Review ahead of Finance Review. Only stage_instances still
    // pending/active/overdue are touched - approved/rejected/skipped ones
    // keep their recorded position forever. Positions are reassigned by
    // permuting the SAME set of position values the open stages already
    // occupy, so a completed/skipped stage sitting between them never
    // collides with the new numbering.
    public function reorderStages(int $requestId, object $actor, array $orderedStageInstanceIds): void
    {
        $this->db->transBegin();
        try {
            $request = $this->lockRequest($requestId);
            $open = $this->db->query("SELECT * FROM `{$this->p}oa_stage_instances` WHERE `request_id`=? AND `status` IN ('pending','active','overdue') FOR UPDATE", [$requestId])->getResult();
            if (count($open) < 2) throw new \DomainException('There is nothing left to reorder on this request.');
            $byId = [];
            foreach ($open as $instance) $byId[(int) $instance->id] = $instance;
            $requestedIds = array_values(array_unique(array_map('intval', $orderedStageInstanceIds)));
            $openIds = array_map('intval', array_keys($byId));
            sort($openIds);
            $sortedRequested = $requestedIds;
            sort($sortedRequested);
            if ($sortedRequested !== $openIds) {
                throw new \DomainException('The new order must include exactly the stages still open on this request.');
            }
            $activeInstance = null;
            foreach ($byId as $instance) {
                if (in_array($instance->status, ['active', 'overdue'], true)) { $activeInstance = $instance; break; }
            }
            // Only demote the active stage if it's being pushed out of first
            // place - a pure reshuffle among the untouched pending stages
            // behind it never needs to touch it at all.
            $demoteActive = $activeInstance && (int) $activeInstance->id !== $requestedIds[0];
            if ($demoteActive) {
                // Reordering ahead of an active stage effectively rewinds it
                // to "hasn't happened yet" - safe only when nobody has
                // actually acted on it yet. Otherwise a real approval or
                // rejection would silently vanish from the audit trail.
                $decided = $this->db->table($this->p . 'oa_decisions')->where('stage_instance_id', $activeInstance->id)->countAllResults();
                if ($decided > 0) {
                    throw new \DomainException('"' . $activeInstance->name_snapshot . '" already has a recorded decision and cannot be reordered ahead of. Approve, reject, or return it first.');
                }
            }
            $positions = array_map(static fn($instance) => (int) $instance->position, $open);
            sort($positions);
            $oldOrder = [];
            foreach ($byId as $instance) $oldOrder[] = ['id' => (int) $instance->id, 'name' => $instance->name_snapshot, 'position' => (int) $instance->position];
            $newOrder = [];
            foreach ($requestedIds as $index => $stageInstanceId) {
                $instance = $byId[$stageInstanceId];
                $newPosition = $positions[$index];
                $newOrder[] = ['id' => $stageInstanceId, 'name' => $instance->name_snapshot, 'position' => $newPosition];
                $update = ['position' => $newPosition, 'lock_version' => ((int) $instance->lock_version) + 1];
                if ($demoteActive && $stageInstanceId === (int) $activeInstance->id) {
                    $update += ['status' => 'pending', 'activated_at' => null, 'due_at' => null];
                }
                $this->db->table($this->p . 'oa_stage_instances')->where('id', $stageInstanceId)->update($update);
            }
            if ($demoteActive) {
                // Fully clear the demoted stage's assignments (not just the
                // pending one) rather than cancel-in-place: the unique key
                // on (stage_instance_id,user_id) means re-resolving the same
                // approver when this stage reactivates later would otherwise
                // collide with a leftover row here and silently fail to
                // insert (DBDebug is off in production). decided===0 above
                // already guarantees none of these rows represent a real
                // recorded decision, so nothing worth keeping is lost.
                $this->db->table($this->p . 'oa_assignments')->where('stage_instance_id', $activeInstance->id)->delete();
                $this->db->table($this->p . 'oa_requests')->where('id', $requestId)->update(['current_stage_instance_id' => null]);
                $request->current_stage_instance_id = null;
            }
            $this->audit->record('stages_reordered', $requestId, null, $actor, ['order' => $oldOrder], ['order' => $newOrder]);
            if ($demoteActive) $this->activateNext($request, $actor, $positions[0] - 1);
            $this->db->transCommit();
            if ($demoteActive) $this->notifyActiveApprovers($requestId, $actor);
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
