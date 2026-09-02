<?php

namespace App\Libraries;

/**
 * Issues a Let's Encrypt certificate for one tenant domain via certbot's
 * HTTP-01 webroot challenge, and updates tenant_domains.ssl_status.
 *
 * This assumes:
 *  - certbot is installed on the VPS and reachable on PATH.
 *  - the webroot this app serves from is also what certbot's HTTP-01
 *    challenge is served from (standard `certbot certonly --webroot`).
 *  - the domain's DNS A record already points at this server - certbot
 *    will fail cleanly (and this returns success=false) if it doesn't yet.
 *  - the actual web server (Apache/nginx) is reloaded separately after
 *    issuance to pick up the new certificate; that step is server-specific
 *    and deliberately left to the ops runbook rather than guessed at here.
 *
 * Deliberately synchronous and manually triggered (the "Issue certificate"
 * button in the platform admin company list) rather than auto-polling -
 * see Phase 3 in the SaaS plan for why. Not exercised against a real
 * certbot/DNS setup in this environment (Windows dev box); the "certbot
 * not found" path is what actually gets tested here.
 */
class Ssl_issuer {

    public function issue(string $domain, string $webroot): array {
        if (!$this->certbot_available()) {
            return ['success' => false, 'message' => 'certbot is not installed on this server. Install it, then retry.'];
        }

        $admin_email = get_setting('smtp_email') ?: get_setting('email') ?: 'admin@' . $domain;

        $cmd = sprintf(
            'certbot certonly --non-interactive --agree-tos -m %s --webroot -w %s -d %s 2>&1',
            escapeshellarg($admin_email),
            escapeshellarg(rtrim($webroot, '/')),
            escapeshellarg($domain)
        );

        exec($cmd, $output, $exit_code);
        $output_text = implode("\n", $output);

        return [
            'success' => $exit_code === 0,
            'message' => $exit_code === 0 ? 'Certificate issued.' : "certbot exited with code {$exit_code}: {$output_text}",
        ];
    }

    private function certbot_available(): bool {
        exec('certbot --version 2>&1', $output, $exit_code);
        return $exit_code === 0;
    }
}
