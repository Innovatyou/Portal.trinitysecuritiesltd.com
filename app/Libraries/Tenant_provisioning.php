<?php

namespace App\Libraries;

/**
 * Provisions a new company (tenant): its own database, schema/seed (from
 * install/database.sql - the same source the original single-tenant
 * installer uses), a tenant-unique system_file_path so uploads never
 * collide with another company's files, the physical upload directory, and
 * the landlord registration (tenant + domain rows).
 *
 * Shared by app/Commands/TenantCreate.php (CLI) and the Platform "add
 * company" controller (web), so there is exactly one place that knows how
 * to create a tenant.
 */
class Tenant_provisioning {

    /**
     * Re-runs active-plugin schema installation for an EXISTING tenant.
     * Needed whenever a plugin is activated (or its install SQL changes)
     * after some tenants already exist - activated_plugins.json is global,
     * so every tenant's app code expects that plugin's hooks to work, but
     * each tenant's schema has to be brought up to date individually.
     *
     * @return array{success: bool, message?: string, plugin_warnings?: string[]}
     */
    public function retrofit_plugins(string $slug): array {
        $landlord = db_connect('landlord');
        $tenant = $landlord->table('tenants')->where('slug', $slug)->get()->getRow();

        if (!$tenant) {
            return ['success' => false, 'message' => "No tenant with slug '{$slug}'."];
        }

        $admin_config = config('Database')->landlord;
        $db_password = service('encrypter')->decrypt(base64_decode($tenant->db_password_encrypted));

        $warnings = $this->install_active_plugins($admin_config, $tenant->db_database, $tenant->db_username, $db_password);

        return ['success' => true, 'plugin_warnings' => $warnings];
    }

    /**
     * Permanently destroys a tenant: drops its database and MySQL user,
     * deletes its uploaded files, and removes its landlord records. There
     * is no undo - callers are responsible for confirming intent (see
     * Platform_companies::destroy_confirm/destroy, which requires typing
     * the slug back before this ever runs).
     *
     * @return array{success: bool, message?: string}
     */
    public function deprovision(string $slug): array {
        $landlord = db_connect('landlord');
        $tenant = $landlord->table('tenants')->where('slug', $slug)->get()->getRow();

        if (!$tenant) {
            return ['success' => false, 'message' => "No tenant with slug '{$slug}'."];
        }

        $admin_config = config('Database')->landlord;
        $mysqli = new \mysqli($admin_config['hostname'], $admin_config['username'], $admin_config['password'], '', (int) $admin_config['port']);
        if ($mysqli->connect_errno) {
            return ['success' => false, 'message' => "Could not connect to MySQL as an admin user: {$mysqli->connect_error}"];
        }

        // Backtick-quoted identifiers can't be parameterized; db_database
        // and db_username are values this app generated itself at
        // provisioning time (tenant_<slug> / tenant_<slug>_<hex>), never
        // user-supplied at this point, so this mirrors how provision()
        // already builds the equivalent CREATE DATABASE/USER statements.
        $mysqli->query("DROP DATABASE IF EXISTS `{$tenant->db_database}`");
        $mysqli->query("DROP USER IF EXISTS '{$tenant->db_username}'@'%'");
        $mysqli->close();

        $upload_dir = FCPATH . $tenant->system_file_path;
        if (is_dir($upload_dir)) {
            helper('filesystem');
            delete_files($upload_dir, true);
        }

        $landlord->transStart();
        $landlord->table('tenant_domains')->where('tenant_id', $tenant->id)->delete();
        $landlord->table('tenants')->where('id', $tenant->id)->delete();
        $landlord->transComplete();

        if (!$landlord->transStatus()) {
            return ['success' => false, 'message' => 'The database and files were removed, but the landlord record could not be deleted - check tenants/tenant_domains manually.'];
        }

        return ['success' => true];
    }

    /**
     * @param array $data name, slug, domain, admin_first, admin_last, admin_email, admin_password
     * @return array{success: bool, message?: string, tenant_id?: int, domain?: string}
     */
    public function provision(array $data): array {
        $name = trim((string) ($data['name'] ?? ''));
        $slug = trim((string) ($data['slug'] ?? ''));
        $domain = strtolower(trim((string) ($data['domain'] ?? '')));
        $admin_first = trim((string) ($data['admin_first'] ?? ''));
        $admin_last = trim((string) ($data['admin_last'] ?? ''));
        $admin_email = trim((string) ($data['admin_email'] ?? ''));
        $admin_password = (string) ($data['admin_password'] ?? '');

        if (!$name || !$slug || !$domain || !$admin_first || !$admin_last || !$admin_email || !$admin_password) {
            return ['success' => false, 'message' => 'All fields are required.'];
        }

        if (!preg_match('/^[a-z][a-z0-9_]{2,40}$/', $slug)) {
            return ['success' => false, 'message' => 'Slug must be lowercase letters/digits/underscores, starting with a letter (3-41 chars).'];
        }

        if (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Admin email is not valid.'];
        }

        $landlord = db_connect('landlord');

        if ($landlord->table('tenants')->where('slug', $slug)->countAllResults() > 0) {
            return ['success' => false, 'message' => "A company with slug '{$slug}' already exists."];
        }

        if ($landlord->table('tenant_domains')->where('domain', $domain)->countAllResults() > 0) {
            return ['success' => false, 'message' => "Domain '{$domain}' is already registered to a company."];
        }

        $db_database = 'tenant_' . $slug;
        $db_username = 'tenant_' . substr($slug, 0, 10) . '_' . bin2hex(random_bytes(3));
        $db_password = bin2hex(random_bytes(24));
        $db_prefix = 'tsl_';

        $admin_config = config('Database')->landlord;

        $mysqli = new \mysqli($admin_config['hostname'], $admin_config['username'], $admin_config['password'], '', (int) $admin_config['port']);
        if ($mysqli->connect_errno) {
            return ['success' => false, 'message' => "Could not connect to MySQL as an admin user: {$mysqli->connect_error}"];
        }

        $mysqli->query("CREATE DATABASE IF NOT EXISTS `{$db_database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $mysqli->query("CREATE USER IF NOT EXISTS '{$db_username}'@'%' IDENTIFIED BY '{$db_password}'");
        $mysqli->query("GRANT ALL PRIVILEGES ON `{$db_database}`.* TO '{$db_username}'@'%'");
        $mysqli->query("FLUSH PRIVILEGES");
        $mysqli->close();

        $now = date('Y-m-d H:i:s');
        $system_file_path = "files/tenants/{$slug}/";
        $encrypted_password = base64_encode(service('encrypter')->encrypt($db_password));

        // Record the tenant as 'provisioning' before doing anything else
        // that could fail (schema import, plugin install) - a database and
        // MySQL user now exist; if something below throws or exit()s (a
        // plugin installer, say), this row is how that gets found and
        // cleaned up or retried instead of silently orphaning both.
        $landlord->table('tenants')->insert([
            'name' => $name,
            'slug' => $slug,
            'status' => 'provisioning',
            'db_hostname' => $admin_config['hostname'],
            'db_port' => (int) $admin_config['port'],
            'db_database' => $db_database,
            'db_username' => $db_username,
            'db_password_encrypted' => $encrypted_password,
            'db_prefix' => $db_prefix,
            'system_file_path' => $system_file_path,
            'created_at' => $now,
        ]);
        $tenant_id = $landlord->insertID();

        $sql = file_get_contents(ROOTPATH . 'install/database.sql');

        $sql = str_replace('admin_first_name', $admin_first, $sql);
        $sql = str_replace('admin_last_name', $admin_last, $sql);
        $sql = str_replace('admin_email', $admin_email, $sql);
        $sql = str_replace('admin_password', password_hash($admin_password, PASSWORD_DEFAULT), $sql);
        $sql = str_replace('admin_created_at', $now, $sql);
        $sql = str_replace('ITEM-PURCHASE-CODE', 'provisioned-via-platform-admin', $sql);
        $sql = str_replace('CREATE TABLE IF NOT EXISTS `', 'CREATE TABLE IF NOT EXISTS `' . $db_prefix, $sql);
        $sql = str_replace('INSERT INTO `', 'INSERT INTO `' . $db_prefix, $sql);

        $tenant_mysqli = new \mysqli($admin_config['hostname'], $db_username, $db_password, $db_database, (int) $admin_config['port']);
        if ($tenant_mysqli->connect_errno) {
            return ['success' => false, 'message' => "Could not connect to the new tenant database: {$tenant_mysqli->connect_error}", 'tenant_id' => $tenant_id];
        }

        $tenant_mysqli->multi_query($sql);
        do {
        } while (mysqli_more_results($tenant_mysqli) && mysqli_next_result($tenant_mysqli));

        $tenant_mysqli->query(
            "INSERT INTO `{$db_prefix}settings` (`setting_name`, `setting_value`, `type`) VALUES ('system_file_path', '" . $tenant_mysqli->real_escape_string($system_file_path) . "', 'app')
             ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`)"
        );

        // The CustomersApi and operations_approval mobile APIs both read this
        // one shared JWT secret (RestApiController::login, Operations_api's
        // constructor) - normally set once by hand on the CustomersApi
        // settings page. Nobody does that for a brand-new tenant, so without
        // this every mobile login for a new company returns 503 "Mobile API
        // is not configured." - discovered exactly that way against Prodigy
        // Bank and Trinity Financial Services before this existed.
        $mobile_jwt_secret = bin2hex(random_bytes(32));
        $tenant_mysqli->query(
            "INSERT INTO `{$db_prefix}settings` (`setting_name`, `setting_value`, `type`) VALUES ('customersapi_secret_key', '" . $tenant_mysqli->real_escape_string($mobile_jwt_secret) . "', 'app')
             ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`)"
        );

        // install/database.sql seeds zero roles - only the admin user
        // created above can do anything until someone builds a role by
        // hand. Confirmed directly: a plain staff account with no role has
        // every operations_* permission key missing, so
        // Operations_permissions::allowed() denies everything - the mobile
        // app would work for the one provisioning admin and nobody else
        // the company adds. Seed a baseline "Staff" role covering the
        // minimum every employee needs (submit requests, see their own,
        // comment) - deliberately NOT approval/admin permissions, which
        // should stay a deliberate assignment per the company's own
        // approval structure, not a blanket default.
        $staff_permissions = serialize([
            'operations_create_request' => '1',
            'operations_view_own_requests' => '1',
            'operations_comment' => '1',
        ]);
        $tenant_mysqli->query(
            "INSERT INTO `{$db_prefix}roles` (`title`, `permissions`, `deleted`) VALUES ('Staff', '" . $tenant_mysqli->real_escape_string($staff_permissions) . "', 0)"
        );

        $tenant_mysqli->close();

        $plugin_warnings = $this->install_active_plugins($admin_config, $db_database, $db_username, $db_password);

        $upload_dir = FCPATH . $system_file_path;
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $landlord->transStart();

        $landlord->table('tenants')->where('id', $tenant_id)->update(['status' => 'active']);

        $landlord->table('tenant_domains')->insert([
            'tenant_id' => $tenant_id,
            'domain' => $domain,
            'is_primary' => 1,
            'ssl_status' => 'pending',
            'created_at' => $now,
        ]);

        $landlord->transComplete();

        if (!$landlord->transStatus()) {
            return ['success' => false, 'message' => 'Schema and admin user were created, but finalizing the tenant record failed.', 'tenant_id' => $tenant_id];
        }

        return ['success' => true, 'tenant_id' => $tenant_id, 'domain' => $domain, 'plugin_warnings' => $plugin_warnings];
    }

    /**
     * Every activated plugin (app/Config/activated_plugins.json) is a
     * single, codebase-wide list - every tenant gets the same plugins'
     * hooks wired in via Events.php's pre_system - but each plugin's own
     * DB tables have to be created separately, per tenant. Skipping this
     * isn't optional: e.g. Mailbox hooks into app_filter_staff_left_menu,
     * which runs on every authenticated page, so a tenant missing its
     * tables would have a broken dashboard from the first login, not just
     * a broken cron run.
     *
     * CustomersApi and Mailbox are deliberately excluded: both installers
     * require a real purchase code and make a live external API call to
     * validate it (releases.classiccompiler.com / api.envato.com), calling
     * exit() on failure - not something provisioning a new company should
     * ever depend on, and not catchable from here (exit() can't be caught).
     * They stay unavailable for new tenants until someone provisions them
     * manually with a real purchase code. Mailbox's app_filter_staff_left_menu
     * hook (which runs on every page) is hardened separately
     * (get_allowed_mailboxes_ids() in mailbox_general_helper.php) to
     * degrade gracefully rather than crash a tenant that doesn't have it.
     *
     * @return string[] one message per plugin that failed to install (empty if all succeeded)
     */
    private function install_active_plugins(array $admin_config, string $db_database, string $db_username, string $db_password): array {
        $skip = ['CustomersApi', 'Mailbox'];
        $warnings = [];

        $plugins_json = @file_get_contents(ROOTPATH . 'app/Config/activated_plugins.json');
        $plugins = $plugins_json ? json_decode($plugins_json) : null;
        if (!(is_array($plugins) && count($plugins))) {
            return $warnings;
        }

        if (!defined('PLUGINPATH')) {
            define('PLUGINPATH', ROOTPATH . 'plugins/');
        }
        if (!defined('PLUGIN_URL_PATH')) {
            define('PLUGIN_URL_PATH', 'plugins/');
        }
        // app_hooks() reads the global $hooks set up by this file - normally
        // pulled in by Events.php's pre_system closure for real requests,
        // which neither of this method's callers (CLI, the platform admin
        // request) goes through.
        require_once ROOTPATH . 'app/ThirdParty/PHP-Hooks/php-hooks.php';
        // general/language/url/file/form: plugin index/install files call
        // app_lang(), get_setting(), get_uri() etc., normally autoloaded by
        // App_Controller for real requests - neither of this method's
        // callers (CLI, the platform admin request) goes through that
        // controller. Same list App_Controller itself loads.
        helper(['plugin', 'general', 'language', 'url', 'file', 'form', 'date_time', 'app_files', 'widget', 'activity_logs', 'currency', 'reports']);

        // Point 'default' at the new tenant's DB for the duration of the
        // install hooks - the same mechanism Tenant_resolver uses for real
        // requests - then restore whatever it was before.
        $db_config = config('Database');
        $previous_default = $db_config->default;
        $db_config->default = [
            'DSN' => '', 'hostname' => $admin_config['hostname'], 'username' => $db_username,
            'password' => $db_password, 'database' => $db_database, 'DBDriver' => 'MySQLi',
            'DBPrefix' => 'tsl_', 'pConnect' => false, 'DBDebug' => (ENVIRONMENT !== 'production'),
            'charset' => 'utf8mb4', 'DBCollat' => 'utf8mb4_unicode_ci', 'swapPre' => '',
            'encrypt' => false, 'compress' => false, 'strictOn' => false, 'failover' => [], 'port' => (int) $admin_config['port'],
        ];

        foreach ($plugins as $slug) {
            if (in_array($slug, $skip, true)) {
                continue;
            }

            $index_file = PLUGINPATH . $slug . '/index.php';
            if (!is_file($index_file)) {
                continue;
            }

            try {
                require_once $index_file;
                app_hooks()->do_action("app_hook_install_plugin_{$slug}", 'provisioned-via-tenant-provisioning');
            } catch (\Throwable $e) {
                $warnings[] = "{$slug}: {$e->getMessage()}";
            }
        }

        $db_config->default = $previous_default;

        // Belt-and-braces: restoring config alone isn't enough, because
        // Database\Config::connect() serves group 'default' from its own
        // static cache once opened, ignoring config on every later call in
        // this process. Neither current caller (the CLI command, the
        // platform "add company" controller) touches 'default' again after
        // this returns, so this is defensive rather than load-bearing
        // today - but a future caller easily could, so drop the cached
        // connection rather than leave it silently stale. No public CI4
        // API does this for a single group, so fall back to reflection;
        // if that ever breaks (e.g. a CI4 upgrade renames the property),
        // don't let a cosmetic cache-eviction failure take down tenant
        // provisioning over it.
        try {
            $instances_property = (new \ReflectionClass(\CodeIgniter\Database\Config::class))->getProperty('instances');
            $instances_property->setAccessible(true);
            $current = $instances_property->getValue();
            unset($current['default']);
            $instances_property->setValue(null, $current);
        } catch (\Throwable $e) {
            log_message('warning', 'Tenant_provisioning: could not evict the stale default DB connection after plugin install: {msg}', ['msg' => $e->getMessage()]);
        }

        return $warnings;
    }
}
