# Security review

- All controllers require an authenticated staff session.
- Write routes are POST-only and use the CodeIgniter CSRF filter.
- Workflow management, broad visibility, creation, comments, and reports are permission checked server-side.
- Decisions require an active assignment for the authenticated user.
- Request ID, current stage, stage status, assignment status, and lock version are checked under transaction/row locks.
- A unique assignment decision constraint prevents duplicate decisions.
- Query builder/bind parameters are used for user-controlled values.
- Displayed workflow, request, and comment values are escaped.
- Published versions and stage/approver snapshots prevent later configuration changes rewriting history.
- Audit records are append-only in application behavior and hash-chained per request.
- Submitted requests expose no hard-delete endpoint.

Production deployment should still include environment-specific penetration testing, mail-delivery verification, backup/restore rehearsal, and concurrent HTTP load testing against the target database server.
