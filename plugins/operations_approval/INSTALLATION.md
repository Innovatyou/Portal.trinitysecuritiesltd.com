# Installation

1. Back up the database and files.
2. Keep the folder name exactly `operations_approval` under `plugins/`.
3. In RISE, open Settings → Plugins, install the plugin, then activate it.
4. Open each applicable role and grant Operations permissions.
5. Create a workflow draft, validate its JSON, save, then publish it.
6. Assign RISE cron normally. The plugin uses the existing after-cron hook; it creates no scheduler.

Installation creates only prefixed `oa_*` tables. Uninstall/deletion intentionally retains the database tables and history. Remove them only after a separately verified retention/export decision.

