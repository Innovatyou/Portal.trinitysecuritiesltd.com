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

        $sql = file_get_contents(ROOTPATH . 'install/database.sql');

        $now = date('Y-m-d H:i:s');
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
            return ['success' => false, 'message' => "Could not connect to the new tenant database: {$tenant_mysqli->connect_error}"];
        }

        $tenant_mysqli->multi_query($sql);
        do {
        } while (mysqli_more_results($tenant_mysqli) && mysqli_next_result($tenant_mysqli));

        $system_file_path = "files/tenants/{$slug}/";

        $tenant_mysqli->query(
            "INSERT INTO `{$db_prefix}settings` (`setting_name`, `setting_value`, `type`) VALUES ('system_file_path', '" . $tenant_mysqli->real_escape_string($system_file_path) . "', 'app')
             ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`)"
        );
        $tenant_mysqli->close();

        $upload_dir = FCPATH . $system_file_path;
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $encrypted_password = base64_encode(service('encrypter')->encrypt($db_password));

        $landlord->transStart();

        $landlord->table('tenants')->insert([
            'name' => $name,
            'slug' => $slug,
            'status' => 'active',
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

        $landlord->table('tenant_domains')->insert([
            'tenant_id' => $tenant_id,
            'domain' => $domain,
            'is_primary' => 1,
            'ssl_status' => 'pending',
            'created_at' => $now,
        ]);

        $landlord->transComplete();

        if (!$landlord->transStatus()) {
            return ['success' => false, 'message' => 'Failed to write the tenant record to the landlord database.'];
        }

        return ['success' => true, 'tenant_id' => $tenant_id, 'domain' => $domain];
    }
}
