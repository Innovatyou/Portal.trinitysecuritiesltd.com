<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Registers an EXISTING, already-populated, already-working application
 * database as a tenant - the cutover step for a site that was running
 * single-tenant before Tenant_resolver/the landlord database existed (e.g.
 * this app's own production Trinity Securities install). Deliberately does
 * NOT create a database or import install/database.sql - unlike
 * tenant:create (App\Commands\TenantCreate, backed by
 * Tenant_provisioning::provision()), which is for brand-new companies with
 * no existing data.
 *
 * Usage:
 *   php spark tenant:adopt --name "Trinity Securities" --slug trinity-securities \
 *     --domain portal.trinitysecuritiesltd.com --db-hostname localhost \
 *     --db-database admintsl_xxx --db-username admintsl_xxx --db-password '...' \
 *     --db-prefix tsl_ --system-file-path files/system/
 *
 * Note: options take their value from the NEXT space-separated argument,
 * not --key=value syntax (CI4's CLI parser doesn't support the latter).
 */
class TenantAdopt extends BaseCommand {

    protected $group = 'Tenants';
    protected $name = 'tenant:adopt';
    protected $description = 'Registers an existing, already-populated application database as a tenant (does not create a database or import a schema).';

    public function run(array $params) {
        $name = (string) CLI::getOption('name');
        $slug = (string) CLI::getOption('slug');
        $domain = strtolower(trim((string) CLI::getOption('domain')));
        $db_hostname = (string) CLI::getOption('db-hostname');
        $db_port = (int) (CLI::getOption('db-port') ?: 3306);
        $db_database = (string) CLI::getOption('db-database');
        $db_username = (string) CLI::getOption('db-username');
        $db_password_given = CLI::getOption('db-password') !== null;
        $db_password = (string) CLI::getOption('db-password');
        $db_prefix = (string) (CLI::getOption('db-prefix') ?: 'tsl_');
        $system_file_path = (string) (CLI::getOption('system-file-path') ?: 'files/system/');

        // db_password checked separately from the rest - a genuinely blank
        // password (a real, if insecure, local/dev MySQL setup) must not be
        // rejected as "missing" the way an empty name/slug/domain should be.
        $required = compact('name', 'slug', 'domain', 'db_hostname', 'db_database', 'db_username');
        $missing = array_filter(array_keys($required), fn($key) => !$required[$key]);
        if (!$db_password_given) {
            $missing[] = 'db_password';
        }
        if ($missing) {
            CLI::error('Missing required option(s): ' . implode(', ', array_map(fn($k) => str_replace('_', '-', $k), $missing)));
            return;
        }

        if (!preg_match('/^[a-z][a-z0-9_]{2,40}$/', $slug)) {
            CLI::error('Slug must be lowercase letters/digits/underscores, starting with a letter (3-41 chars).');
            return;
        }

        $landlord = db_connect('landlord');

        if ($landlord->table('tenants')->where('slug', $slug)->countAllResults() > 0) {
            CLI::error("A tenant with slug '{$slug}' is already registered.");
            return;
        }

        if ($landlord->table('tenant_domains')->where('domain', $domain)->countAllResults() > 0) {
            CLI::error("Domain '{$domain}' is already registered to a tenant.");
            return;
        }

        // Fail before writing anything if these credentials don't actually
        // work - a bad tenants row would otherwise take the domain down
        // with a confusing error instead of never being created at all.
        $mysqli = @new \mysqli($db_hostname, $db_username, $db_password, $db_database, $db_port);
        if ($mysqli->connect_errno) {
            CLI::error("Could not connect with the given credentials: {$mysqli->connect_error}");
            return;
        }
        $users_table_check = $mysqli->query("SHOW TABLES LIKE '{$db_prefix}users'");
        if (!$users_table_check || $users_table_check->num_rows === 0) {
            CLI::error("Connected, but found no `{$db_prefix}users` table in that database - check --db-prefix.");
            $mysqli->close();
            return;
        }
        $mysqli->close();

        $encrypted_password = base64_encode(service('encrypter')->encrypt($db_password));
        $now = date('Y-m-d H:i:s');

        $landlord->transStart();

        $landlord->table('tenants')->insert([
            'name' => $name,
            'slug' => $slug,
            'status' => 'active',
            'db_hostname' => $db_hostname,
            'db_port' => $db_port,
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
            'ssl_status' => 'issued',
            'verified_at' => $now,
            'created_at' => $now,
        ]);

        $landlord->transComplete();

        if (!$landlord->transStatus()) {
            CLI::error('Connection worked, but writing the landlord records failed - nothing was left half-registered (transaction rolled back).');
            return;
        }

        CLI::newLine();
        CLI::write("Tenant #{$tenant_id} ({$name}) registered against its existing database.", 'green');
        CLI::write("Domain:  http://{$domain}/  (and https, once DNS/SSL for it are confirmed)");
        CLI::write("This did NOT touch the database itself or its data - only the landlord's routing record.");
    }
}
