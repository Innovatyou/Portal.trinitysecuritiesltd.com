<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Hits every active tenant's /cron endpoint once. Cron.php itself needs no
 * multi-tenant changes - DB resolution already happens per-request from the
 * Host header - it just needs to be invoked once per tenant domain instead
 * of once for the whole app. Meant to be the actual crontab entry:
 *
 *   * * * * *  php /path/to/spark tenant:cron >> /path/to/writable/logs/tenant_cron.log 2>&1
 *
 * so a newly-provisioned company's cron starts running with no crontab
 * edits required.
 */
class TenantCron extends BaseCommand {

    protected $group = 'Tenants';
    protected $name = 'tenant:cron';
    protected $description = "Runs every active tenant's /cron endpoint once.";

    public function run(array $params) {
        $scheme = CLI::getOption('scheme') ?: 'https';
        $port = CLI::getOption('port') ?: ($scheme === 'https' ? 443 : 80);
        $resolve_ip = CLI::getOption('resolve-ip'); // force every domain to this IP - useful for testing/no-public-DNS setups

        $landlord = db_connect('landlord');

        $domains = $landlord->table('tenant_domains d')
            ->select('d.domain')
            ->join('tenants t', 't.id = d.tenant_id')
            ->where('t.status', 'active')
            ->where('d.is_primary', 1)
            ->get()->getResult();

        if (!$domains) {
            CLI::write('No active tenant domains found.', 'yellow');
            return;
        }

        foreach ($domains as $row) {
            $url = "{$scheme}://{$row->domain}:{$port}/cron";
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $scheme === 'https');
            if ($resolve_ip) {
                curl_setopt($ch, CURLOPT_RESOLVE, ["{$row->domain}:{$port}:{$resolve_ip}"]);
            }
            $body = curl_exec($ch);
            $errno = curl_errno($ch);
            $error = curl_error($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($errno) {
                CLI::error("{$row->domain}: {$error}");
                continue;
            }

            CLI::write("{$row->domain} [{$status}]: " . trim((string) $body));
        }
    }
}
