# Administrator guide

Grant the smallest relevant permissions. `operations_manage_workflows` controls definitions and publishing; `operations_admin_override` is reserved for future controlled corrections and grants no hidden behavior in 1.0.0.

Published workflow versions are immutable. Editing saves a new draft version. Publishing materializes its fields and stages and makes it current for future submissions. Existing requests retain their `version_id` and request-time stage/approver snapshots.

If a stage resolves no eligible approver—especially when self-approval is disabled—the request enters `configuration_error`; it is never silently skipped or approved.

Normal uninstall preserves history. Submitted requests have no delete endpoint.

