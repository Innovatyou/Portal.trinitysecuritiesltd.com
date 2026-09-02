<?php

namespace App\Libraries;

/**
 * Resolves which company (tenant) the current request belongs to, from the
 * incoming Host header, and points the app's 'default' DB connection group
 * at that tenant's own database - before any model in the app touches the
 * database. This is the entire multi-tenant boundary: everything downstream
 * (all existing controllers/models) is unaware multi-tenancy exists.
 *
 * Must run as the first thing in the pre_system event, before routing and
 * before App_Controller loads any model.
 */
class Tenant_resolver {

    public static function resolve(): void {
        $host = self::current_host();

        $platform_host = config('Platform')->platform_host;
        if ($host === $platform_host) {
            // Platform admin area talks only to the landlord DB - it never
            // touches a tenant's 'default' group. Controllers under
            // app/Controllers/Platform/* must connect via db_connect('landlord')
            // explicitly instead of using models that assume 'default'.
            // Session storage is repointed at 'landlord' too, so a platform
            // operator's session can never end up inside a tenant's own
            // database (whatever 'default' happens to be left pointing at
            // here, since it's not resolved for this host at all).
            config('Session')->DBGroup = 'landlord';
            service('tenant')->mark_platform_host();
            return;
        }

        $tenant_row = self::find_tenant_by_domain($host);

        if (!$tenant_row) {
            self::halt(404, "This domain isn't connected to any company yet.");
        }

        if ($tenant_row->status !== 'active') {
            self::halt(503, "This company's account is currently unavailable.");
        }

        self::point_default_connection_at($tenant_row);

        service('tenant')->set_from_row($tenant_row);
    }

    private static function current_host(): string {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        // strip a port if present (e.g. "localhost:8080")
        $host = explode(':', $host)[0];
        return strtolower(trim($host, '.'));
    }

    private static function find_tenant_by_domain(string $host) {
        $db = db_connect('landlord');

        $builder = $db->table('tenant_domains');
        $builder->select('tenants.*');
        $builder->join('tenants', 'tenants.id = tenant_domains.tenant_id');
        $builder->where('tenant_domains.domain', $host);

        return $builder->get()->getRow();
    }

    private static function point_default_connection_at($tenant_row): void {
        $encrypted_password = base64_decode($tenant_row->db_password_encrypted);
        $password = service('encrypter')->decrypt($encrypted_password);

        $db_config = config('Database');
        $db_config->default = [
            'DSN'      => '',
            'hostname' => $tenant_row->db_hostname,
            'username' => $tenant_row->db_username,
            'password' => $password,
            'database' => $tenant_row->db_database,
            'DBDriver' => 'MySQLi',
            'DBPrefix' => $tenant_row->db_prefix,
            'pConnect' => false,
            'DBDebug'  => (ENVIRONMENT !== 'production'),
            'charset'  => 'utf8mb4',
            'DBCollat' => 'utf8mb4_unicode_ci',
            'swapPre'  => '',
            'encrypt'  => false,
            'compress' => false,
            'strictOn' => false,
            'failover' => [],
            'port'     => (int) $tenant_row->db_port,
        ];
    }

    private static function halt(int $status, string $message): void {
        http_response_code($status);
        echo $message;
        exit();
    }
}
