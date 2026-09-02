<?php

namespace App\Commands;

use App\Libraries\Tenant_provisioning;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Provisions a new company (tenant) from the command line. See
 * App\Libraries\Tenant_provisioning for what this actually does - this
 * command is a thin CLI wrapper around it, shared with the Platform "add
 * company" web form.
 *
 * Usage:
 *   php spark tenant:create --name "Prodigy Bank" --slug prodigybank \
 *     --domain prodigybank.test --admin-first Jane --admin-last Doe \
 *     --admin-email admin@prodigybank.test --admin-password "ChangeMe123!"
 *
 * Note: options take their value from the NEXT space-separated argument,
 * not --key=value syntax (CI4's CLI parser doesn't support the latter).
 */
class TenantCreate extends BaseCommand {

    protected $group = 'Tenants';
    protected $name = 'tenant:create';
    protected $description = 'Provisions a new company: its own database, schema, admin user, and domain mapping.';

    public function run(array $params) {
        $data = [
            'name' => CLI::getOption('name'),
            'slug' => CLI::getOption('slug'),
            'domain' => CLI::getOption('domain'),
            'admin_first' => CLI::getOption('admin-first'),
            'admin_last' => CLI::getOption('admin-last'),
            'admin_email' => CLI::getOption('admin-email'),
            'admin_password' => CLI::getOption('admin-password'),
        ];

        $missing = array_filter(['name', 'slug', 'domain', 'admin_first', 'admin_last', 'admin_email', 'admin_password'], fn($key) => !$data[$key]);
        if ($missing) {
            CLI::error('Missing required option(s): ' . implode(', ', array_map(fn($k) => str_replace('_', '-', $k), $missing)));
            return;
        }

        $result = (new Tenant_provisioning())->provision($data);

        if (!$result['success']) {
            CLI::error($result['message']);
            return;
        }

        CLI::newLine();
        CLI::write("Tenant #{$result['tenant_id']} ({$data['name']}) provisioned.", 'green');
        CLI::write("Domain:      http://{$result['domain']}/");
        CLI::write("Admin login: {$data['admin_email']}");
        CLI::write('(Admin password is the one you passed via --admin-password.)');
    }
}
