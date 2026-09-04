<?php

namespace App\Libraries;

/**
 * Creates/destroys tenant MySQL databases and users via cPanel's UAPI,
 * over HTTPS with an API token - NOT shell_exec(), which this account's
 * PHP-FPM pool hard-disables via php_admin_value[disable_functions]
 * (confirmed directly: set at the FPM pool config level, so it can't be
 * overridden per-request or per-account, and it's applied identically
 * across every domain on this server - clearly a deliberate hardening
 * policy, not something worth weakening just for this feature).
 *
 * Why cPanel's API at all, rather than raw SQL: the landlord MySQL
 * account (admintsl_landlorduser) is a normal cPanel-issued account, not
 * a MySQL superuser. Confirmed directly against production that even a
 * wildcard-scoped `GRANT ALL PRIVILEGES ON \`tenant_%\`.* ... WITH GRANT
 * OPTION` isn't enough for MySQL to allow re-granting to a brand-new
 * tenant user - MySQL 8's re-grant check requires an exact, non-wildcard
 * match, which can't exist yet for a tenant that hasn't been created.
 * cPanel's own root-level internals create/grant instead, so the
 * app-facing landlord DB user never needs elevated MySQL privileges.
 *
 * Every name passed to cPanel MUST already carry the "{cpanel_username}_"
 * prefix - cPanel rejects a bare name outright rather than adding the
 * prefix itself (confirmed directly against this server; several UAPI
 * writeups online describe the opposite, which is NOT what happens here).
 * Use prefixed() to build names before calling any other method here.
 *
 * Response shape over this HTTPS /execute/ endpoint is flatter than the
 * `uapi --output=json` CLI form - status/errors/data are top-level keys
 * here, not nested under a "result" object (confirmed directly).
 */
class Cpanel_mysql {

    private string $cpanel_username;
    private string $token;
    private string $hostname;

    public function __construct() {
        $platform = config('Platform');
        $this->cpanel_username = $platform->cpanel_username;
        $this->token = $platform->cpanel_api_token;
        $this->hostname = $platform->cpanel_hostname;

        if (!$this->cpanel_username || !$this->token) {
            throw new \RuntimeException('platform.cpanel_username / platform.cpanel_api_token are not set.');
        }
    }

    public function prefixed(string $short_name): string {
        return $this->cpanel_username . '_' . $short_name;
    }

    /** @return array{success: bool, message?: string} */
    public function create_database(string $full_name): array {
        return $this->call('create_database', ['name' => $full_name]);
    }

    /** @return array{success: bool, message?: string} */
    public function create_user(string $full_name, string $password): array {
        return $this->call('create_user', ['name' => $full_name, 'password' => $password]);
    }

    /** @return array{success: bool, message?: string} */
    public function grant_all(string $full_user, string $full_database): array {
        return $this->call('set_privileges_on_database', [
            'user' => $full_user,
            'database' => $full_database,
            'privileges' => 'ALL',
        ]);
    }

    /** @return array{success: bool, message?: string} */
    public function delete_database(string $full_name): array {
        return $this->call('delete_database', ['name' => $full_name]);
    }

    /** @return array{success: bool, message?: string} */
    public function delete_user(string $full_name): array {
        return $this->call('delete_user', ['name' => $full_name]);
    }

    /** @return array{success: bool, message?: string} */
    private function call(string $function, array $params): array {
        if (!function_exists('curl_init')) {
            return ['success' => false, 'message' => 'PHP curl extension is not available.'];
        }

        $url = "https://{$this->hostname}:2083/execute/Mysql/{$function}";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($params),
            CURLOPT_HTTPHEADER => ["Authorization: cpanel {$this->cpanel_username}:{$this->token}"],
            CURLOPT_RETURNTRANSFER => true,
            // Loopback call to cPanel's own local service on this same
            // server, not a network hop - see class docblock.
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT => 30,
        ]);
        $output = curl_exec($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($output === false) {
            $message = "uapi Mysql::{$function} request failed: {$curl_error}";
            log_message('error', 'Cpanel_mysql::{function} failed: {message}', ['function' => $function, 'message' => $message]);

            return ['success' => false, 'message' => $message];
        }

        $decoded = json_decode((string) $output, true);
        $status = $decoded['status'] ?? null;

        if ($status !== 1) {
            $errors = $decoded['errors'] ?? null;
            $message = is_array($errors) ? implode('; ', $errors) : ('uapi Mysql::' . $function . ' failed: ' . trim((string) $output));
            log_message('error', 'Cpanel_mysql::{function} failed: {message}', ['function' => $function, 'message' => $message]);

            return ['success' => false, 'message' => $message];
        }

        return ['success' => true];
    }
}
