# Administrator guide

Grant the smallest relevant permissions. `operations_manage_workflows` controls definitions and publishing. `operations_admin_override` is the module's "superadmin" right - it lets a user view every request regardless of `operations_view_all_requests`/`operations_view_department_requests`, and approve/reject/return any request's current stage even when they're not its assigned approver (Workflow_engine hands them a pending assignment for that stage on the fly, recorded distinctly in the audit log as `admin_override_assigned` so it's traceable). Their "Pending my approval" inbox also becomes every request org-wide awaiting a decision, not just their own assignments. Grant it sparingly.

The same right also lets them reorder a specific request's remaining stages from its approval timeline (e.g. move Control Review ahead of Finance Review), using up/down arrows shown only to them next to any stage still pending or active. This only reshuffles that one request's stage instances - the published workflow template is untouched, so future requests still follow the original order. A stage that's currently Active can be pushed behind another one (it's reset to Pending and its in-progress assignments are cleared so it can re-activate later), but only while it has zero recorded decisions; once someone has actually approved/rejected/returned it, it can no longer be reordered ahead of - resolve it normally first. Every reorder is logged to the audit trail as `stages_reordered` with the before/after order.

Published workflow versions are immutable. Editing saves a new draft version. Publishing materializes its fields and stages and makes it current for future submissions. Existing requests retain their `version_id` and request-time stage/approver snapshots.

If a stage resolves no eligible approver—especially when self-approval is disabled—the request enters `configuration_error`; it is never silently skipped or approved.

Normal uninstall preserves history. Submitted requests have no delete endpoint.

