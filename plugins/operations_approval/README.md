# Operations & Approval Workflow

Independent RISE CRM plugin for version 3.9.5 / CodeIgniter 4.6.3. Version 1.0.0 establishes a versioned workflow engine with sequential and conditional stages, specific-user/role/dynamic-field approvers, any/all/minimum/majority approval thresholds, drafts, approval, rejection, return, comments, timeline, chained audit hashes, SLA breach processing, permissions, dashboards, and reports foundation.

No RISE core file is modified and no core table is altered.

## Architecture

- `index.php`: metadata, lifecycle hooks, routes, permissions, menu, cron hook.
- `Libraries/Workflow_engine.php`: submission, immutable stage snapshots, assignment, decisions, conditional skip, advancement, completion.
- `Libraries/Condition_evaluator.php`: deterministic nested AND/OR conditions.
- `Libraries/Approver_resolver.php`: request-time assignment snapshots.
- `Libraries/Audit_service.php`: append-only audit entries chained by SHA-256.
- `Controllers`: thin authenticated endpoints.
- `Views`: native RISE cards, forms, tables, badges, Select2 and appForm.
- `install/sql`: prefix-safe InnoDB schema.

See [ARCHITECTURE_AUDIT.md](ARCHITECTURE_AUDIT.md), [INSTALLATION.md](INSTALLATION.md), [ADMIN_GUIDE.md](ADMIN_GUIDE.md), [WORKFLOW_GUIDE.md](WORKFLOW_GUIDE.md), and [UPGRADE.md](UPGRADE.md).

## Release scope

Version 1.0 includes the complete reliable workflow path: drafts/submission, versioned custom forms, conditional sequential stages, multi-approver thresholds, protected attachments, comments, return/revision/resubmission, information requests, rejection, cancellation, delegation, native notifications/email, SLA reminders/escalation, audit history, reports, CSV export, departments, settings, and responsive approval pages. The builder uses structured JSON for advanced definitions to keep publishing deterministic; the guide includes a working example.
