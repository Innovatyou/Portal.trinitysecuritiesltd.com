<?php

namespace App\Libraries;

/**
 * Issues an SSL certificate for one tenant domain via cPanel's own AutoSSL
 * (UAPI SSL module, through Cpanel_api), not a standalone certbot binary -
 * certbot needed exec(), which this server's PHP-FPM pool hard-disables
 * (confirmed directly - same restriction Cpanel_mysql works around). Using
 * cPanel's native AutoSSL is also simply the correct approach on a cPanel
 * server: certificates it issues install directly into cPanel's own Apache
 * TLS config, with no separate "reload the web server" step needed.
 *
 * AutoSSL only ever considers domains cPanel itself knows about (see
 * Cpanel_domains - the domain must already be a registered Addon Domain or
 * Subdomain, not just a row in this app's own tenant_domains table).
 *
 * AutoSSL is account-wide and asynchronous: start_autossl_check() kicks off
 * a domain-control-validation + issuance pass over EVERY domain on the
 * cPanel account (not just the one requested) and returns immediately -
 * the pass itself can take anywhere from seconds to a couple of minutes.
 * Rather than block the request for that whole time (real risk of hitting
 * PHP/Apache's own execution timeout), this does one short bounded poll of
 * is_autossl_check_in_progress() and, if AutoSSL is still running when that
 * runs out, reports back a "still checking" status instead of guessing at
 * success or failure - Platform_companies::issue_ssl() leaves ssl_status as
 * 'pending' in that case rather than overwriting it, so a re-click shortly
 * after (once AutoSSL has actually finished) gets a real answer.
 *
 * Assumes the domain's DNS A record already points at this server - if it
 * doesn't, AutoSSL's domain-control-validation fails for that domain and
 * this returns a 'failed' status with whatever reason get_autossl_problems()
 * recorded for it.
 */
class Ssl_issuer {

    private const POLL_INTERVAL_SECONDS = 4;
    private const MAX_POLLS = 3;

    /** @return array{success: bool, status: 'issued'|'failed'|'checking', message: string} */
    public function issue(string $domain, string $webroot): array {
        $start = Cpanel_api::call('SSL', 'start_autossl_check', []);
        if (!$start['success']) {
            return ['success' => false, 'status' => 'failed', 'message' => "Could not start AutoSSL check: {$start['message']}"];
        }

        $still_running = true;
        for ($i = 0; $i < self::MAX_POLLS; $i++) {
            sleep(self::POLL_INTERVAL_SECONDS);

            $progress = Cpanel_api::call('SSL', 'is_autossl_check_in_progress', []);
            if (!$progress['success']) {
                return ['success' => false, 'status' => 'failed', 'message' => "Could not check AutoSSL status: {$progress['message']}"];
            }
            if (!$progress['data']) {
                $still_running = false;
                break;
            }
        }

        if ($still_running) {
            return [
                'success' => false,
                'status' => 'checking',
                'message' => 'AutoSSL check started - it can take a minute or two across all domains. Click Issue certificate again shortly to check the result.',
            ];
        }

        $installed = Cpanel_api::call('SSL', 'installed_host', ['domain' => $domain]);
        if ($installed['success'] && !empty($installed['data']['certificate'])) {
            return ['success' => true, 'status' => 'issued', 'message' => 'Certificate issued.'];
        }

        $problems = Cpanel_api::call('SSL', 'get_autossl_problems', []);
        $reason = null;
        if ($problems['success'] && is_array($problems['data'])) {
            foreach ($problems['data'] as $problem) {
                if (($problem['domain'] ?? null) === $domain) {
                    $reason = $problem['problem'] ?? null;
                    break;
                }
            }
        }

        if (!$reason && !$installed['success']) {
            $reason = $installed['message'] ?? null;
        }

        return [
            'success' => false,
            'status' => 'failed',
            'message' => $reason
                ? "AutoSSL could not issue a certificate for {$domain}: {$reason}"
                : "AutoSSL ran but {$domain} still has no certificate - check that its DNS A record points at this server.",
        ];
    }
}
