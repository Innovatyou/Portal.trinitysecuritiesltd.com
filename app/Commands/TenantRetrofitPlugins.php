<?php

namespace App\Commands;

use App\Libraries\Tenant_provisioning;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Re-runs active-plugin schema installation for an existing tenant (or
 * every tenant with --all). Needed after a plugin is activated, or its
 * install SQL changes, once tenants already exist.
 *
 * Usage:
 *   php spark tenant:retrofit-plugins --slug prodigybank
 *   php spark tenant:retrofit-plugins --all
 */
class TenantRetrofitPlugins extends BaseCommand {

    protected $group = 'Tenants';
    protected $name = 'tenant:retrofit-plugins';
    protected $description = "Re-runs active-plugin schema installation for an existing tenant, or every tenant with --all.";

    public function run(array $params) {
        $slug = CLI::getOption('slug');
        $all = CLI::getOption('all');

        if (!$slug && !$all) {
            CLI::error('Pass --slug <slug> for one tenant, or --all for every tenant.');
            return;
        }

        $slugs = [$slug];
        if ($all) {
            $slugs = array_map(fn($row) => $row->slug, db_connect('landlord')->table('tenants')->select('slug')->get()->getResult());
        }

        $provisioning = new Tenant_provisioning();

        foreach ($slugs as $s) {
            $result = $provisioning->retrofit_plugins($s);

            if (!$result['success']) {
                CLI::error("{$s}: {$result['message']}");
                continue;
            }

            if ($result['plugin_warnings']) {
                CLI::write("{$s}: done, with warnings:", 'yellow');
                foreach ($result['plugin_warnings'] as $warning) {
                    CLI::write("  - {$warning}");
                }
            } else {
                CLI::write("{$s}: OK", 'green');
            }
        }
    }
}
