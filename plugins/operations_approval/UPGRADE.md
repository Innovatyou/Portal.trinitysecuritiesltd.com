# Upgrade

Back up the database and plugin folder before upgrading. Keep migrations additive. Never rewrite published `oa_workflow_versions`, decisions, comments, or audit rows. The update hook reapplies idempotent `CREATE TABLE IF NOT EXISTS` statements and records the plugin version.

For future schema changes, add ordered SQL files and a version gate rather than editing historical definitions. Test upgrades against a database copy containing active and completed requests.

