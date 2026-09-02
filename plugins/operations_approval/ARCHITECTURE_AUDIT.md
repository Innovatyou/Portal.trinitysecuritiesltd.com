# Installed RISE architecture audit

| Area | Installed convention used |
|---|---|
| RISE | 3.9.5 (`app/Config/Rise.php`) |
| Framework | CodeIgniter 4.6.3 (`system/CodeIgniter.php`) |
| Plugin discovery | `plugins/<slug>/index.php`; active namespaces loaded by `app/Config/Autoload.php` |
| Lifecycle | `register_installation_hook`, `register_update_hook`, activation/deactivation/uninstallation hooks |
| Routing | Plugin bootstrap registers explicit `service('routes')` routes |
| Authentication | Controllers extend `App\Controllers\Security_Controller` and require staff access |
| Staff / roles | Prefixed `users` and `roles`; serialized role permissions loaded onto `login_user` |
| Permissions | Role UI action and save-data filter; admin remains implicit superuser |
| Menus | `app_filter_staff_left_menu`; native menu arrays and Feather icons |
| Database | `db_connect('default')`, runtime `getPrefix()`, query builder, InnoDB transactions |
| Security | RISE `form_open`/`appForm`, explicit CSRF route filter, server validation, escaped output |
| Files | RISE temp uploader and `move_temp_file`; authorized controller downloads with hashes |
| Notifications | `log_notification`, plugin notification configuration and recipient filter |
| Email | Native notification processor and plugin event templates |
| Audit | Core `Activity_logs_model`; plugin uses stricter append-only domain audit table |
| AJAX | JSON `{success,message,...}` and native `appForm` |
| Tables | Native Bootstrap tables/DataTables; server-side appTable convention for large lists |
| Modal | `modal_anchor`, `template->view`, general-form/appForm |
| Cron | `app_hook_after_cron_run` fired by core `Cron` controller |
| Localization | CI locator merges namespace `Language/<locale>/default_lang.php`; `app_lang` keys |
| UI | Bootstrap 5 conventions, cards, badges, Select2, Feather icons |
| Dates/time | `get_current_utc_time`, `format_to_datetime`, RISE timezone configuration |

The repository contained no installed example plugin. Core plugin loader, hooks, controllers, helpers, views, and bundled conventions were therefore treated as authoritative.
