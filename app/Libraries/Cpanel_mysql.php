<?php

namespace App\Libraries;

/**
 * Creates/destroys tenant MySQL databases and users via cPanel's UAPI,
 * shelling out to /usr/local/cpanel/bin/uapi rather than raw SQL.
 *
 * Why: the landlord MySQL account (admintsl_landlorduser) is a normal
 * cPanel-issued account, not a MySQL superuser. Confirmed directly against
 * production that even `GRANT ALL PRIVILEGES ON \`tenant_%\`.* ... WITH
 * GRANT OPTION` isn't enough for it to then GRANT to a brand-new tenant
 * user - MySQL 8's re-grant check requires an exact (non-wildcard) match,
 * which can't exist yet for a tenant that hasn't been created. The only
 * ways around that are giving the landlord user full `*.* WITH GRANT
 * OPTION` (effectively MySQL root - rejected, too large a blast radius for
 * a credential this app holds in .env) or letting cPanel's own root-level
 * internals do the create/grant instead. This is the second option.
 *
 * No API token needed: uapi auto-authenticates as whichever OS user runs
 * it, and PHP-FPM runs this app as the cPanel account's own user -
 * confirmed directly (`su - <account> -c uapi ...` behaves identically to
 * what PHP's shell_exec() sees here).
 *
 * Every name passed to cPanel MUST already carry the "{cpanel_username}_"
 * prefix - cPanel rejects a bare name outright rather than adding the
 * prefix itself (confirmed directly against this server; several UAPI
 * writeups online describe the opposite, auto-prefixing, which is NOT
 * what happens here). Use prefixed() to build names before calling any
 * other method here.
 */
class Cpanel_mysql {

    private string $cpanel_username;
    private string $binary = '/usr/local/cpanel/bin/uapi';

    public function __construct() {
        $this->cpanel_username = config('Platform')->cpanel_username;
        if (!$this->cpanel_username) {
            throw new \RuntimeException('platform.cpanel_username is not set.');
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
        $args = [escapeshellarg($this->binary), '--output=json', 'Mysql', $function];
        foreach ($params as $key => $value) {
            $args[] = escapeshellarg("{$key}={$value}");
        }

        $output = shell_exec(implode(' ', $args) . ' 2>&1');
        $decoded = json_decode((string) $output, true);

        $status = $decoded['result']['status'] ?? null;
        if ($status !== 1) {
            $errors = $decoded['result']['errors'] ?? null;
            $message = is_array($errors) ? implode('; ', $errors) : ('uapi Mysql::' . $function . ' failed: ' . trim((string) $output));
            log_message('error', 'Cpanel_mysql::{function} failed: {message}', ['function' => $function, 'message' => $message]);

            return ['success' => false, 'message' => $message];
        }

        return ['success' => true];
    }
}
