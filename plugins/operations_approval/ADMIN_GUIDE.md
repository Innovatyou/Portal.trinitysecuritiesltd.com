# Administrator guide

Grant the smallest relevant permissions. `operations_manage_workflows` controls definitions and publishing. `operations_admin_override` is the module's "superadmin" right - it lets a user view every request regardless of `operations_view_all_requests`/`operations_view_department_requests`, and approve/reject/return any request's current stage even when they're not its assigned approver (Workflow_engine hands them a pending assignment for that stage on the fly, recorded distinctly in the audit log as `admin_override_assigned` so it's traceable). Their "Pending my approval" inbox also becomes every request org-wide awaiting a decision, not just their own assignments. Grant it sparingly.

Published workflow versions are immutable. Editing saves a new draft version. Publishing materializes its fields and stages and makes it current for future submissions. Existing requests retain their `version_id` and request-time stage/approver snapshots.

If a stage resolves no eligible approver—especially when self-approval is disabled—the request enters `configuration_error`; it is never silently skipped or approved.

Normal uninstall preserves history. Submitted requests have no delete endpoint.

