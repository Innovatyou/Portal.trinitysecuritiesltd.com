<?php

namespace App\Libraries;

/**
 * Issues an SSL certificate for one tenant domain via cPanel's own AutoSSL
 * (UAPI SSL module), not a standalone certbot binary - certbot needed
 * exec()/shell_exec() to run, which this server's PHP-FPM pool hard-disables
 * via php_admin_value (confirmed directly - same restriction Cpanel_mysql
 * works around). Using cPanel's native AutoSSL is also simply the correct
 * approach on a cPanel server: certificates it issues install directly into
 * cPanel's own Apache TLS config, with no separate "reload the web server"
 * step needed - a standalone certbot cert, by contrast, cPanel wouldn't
 * know about at all.
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
        $platform = config('Platform');
        if (!$platform->cpanel_username || !$platform->cpanel_api_token) {
            return ['success' => false, 'status' => 'failed', 'message' => 'cPanel API is not configured (platform.cpanel_username / platform.cpanel_api_token).'];
        }

        $start = $this->call('start_autossl_check', []);
        if (!$start['success']) {
            return ['success' => false, 'status' => 'failed', 'message' => "Could not start AutoSSL check: {$start['message']}"];
        }

        $still_running = true;
        for ($i = 0; $i < self::MAX_POLLS; $i++) {
            sleep(self::POLL_INTERVAL_SECONDS);

            $progress = $this->call('is_autossl_check_in_progress', []);
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

        $installed = $this->call('installed_host', ['domain' => $domain]);
        if ($installed['success'] && !empty($installed['data']['certificate'])) {
            return ['success' => true, 'status' => 'issued', 'message' => 'Certificate issued.'];
        }

        $problems = $this->call('get_autossl_problems', []);
        $reason = null;
        if ($problems['success'] && is_array($problems['data'])) {
            foreach ($problems['data'] as $problem) {
                if (($problem['domain'] ?? null) === $domain) {
                    $reason = $problem['reason'] ?? null;
                    break;
                }
            }
        }

        return [
            'success' => false,
            'status' => 'failed',
            'message' => $reason
                ? "AutoSSL could not issue a certificate for {$domain}: {$reason}"
                : "AutoSSL ran but {$domain} still has no certificate - check that its DNS A record points at this server.",
        ];
    }

    /** @return array{success: bool, message?: string, data?: mixed} */
    private function call(string $function, array $params): array {
        $platform = config('Platform');

        if (!function_exists('curl_init')) {
            return ['success' => false, 'message' => 'PHP curl extension is not available.'];
        }

        $url = "https://{$platform->cpanel_hostname}:2083/execute/SSL/{$function}";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($params),
            CURLOPT_HTTPHEADER => ["Authorization: cpanel {$platform->cpanel_username}:{$platform->cpanel_api_token}"],
            CURLOPT_RETURNTRANSFER => true,
            // Loopback call to cPanel's own local service on this same
            // server, not a network hop - see Cpanel_mysql's docblock for
            // the same reasoning.
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT => 30,
        ]);
        $output = curl_exec($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($output === false) {
            return ['success' => false, 'message' => "uapi SSL::{$function} request failed: {$curl_error}"];
        }

        $decoded = json_decode((string) $output, true);
        $status = $decoded['status'] ?? null;

        if ($status !== 1) {
            $errors = $decoded['errors'] ?? null;
            $message = is_array($errors) ? implode('; ', $errors) : ('uapi SSL::' . $function . ' failed: ' . trim((string) $output));

            return ['success' => false, 'message' => $message];
        }

        return ['success' => true, 'data' => $decoded['data'] ?? null];
    }
}
