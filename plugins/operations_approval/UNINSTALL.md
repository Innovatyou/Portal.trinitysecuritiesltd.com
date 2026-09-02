# Uninstall and retention

RISE removes the plugin directory during plugin deletion. The uninstallation hook only marks the module inactive. All `oa_*` tables remain by design because approval decisions and audit history may have legal or operational retention value.

Database destruction is not automated. An administrator must explicitly approve and execute a separately reviewed retention/export process.

