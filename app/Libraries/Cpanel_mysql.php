<?php

namespace App\Libraries;

/**
 * Creates/destroys tenant MySQL databases and users via cPanel's UAPI
 * (Cpanel_api - HTTPS with an API token, not shell_exec()).
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
 */
class Cpanel_mysql {

    private string $cpanel_username;

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
        return Cpanel_api::call('Mysql', 'create_database', ['name' => $full_name]);
    }

    /** @return array{success: bool, message?: string} */
    public function create_user(string $full_name, string $password): array {
        return Cpanel_api::call('Mysql', 'create_user', ['name' => $full_name, 'password' => $password]);
    }

    /** @return array{success: bool, message?: string} */
    public function grant_all(string $full_user, string $full_database): array {
        return Cpanel_api::call('Mysql', 'set_privileges_on_database', [
            'user' => $full_user,
            'database' => $full_database,
            'privileges' => 'ALL',
        ]);
    }

    /** @return array{success: bool, message?: string} */
    public function delete_database(string $full_name): array {
        return Cpanel_api::call('Mysql', 'delete_database', ['name' => $full_name]);
    }

    /** @return array{success: bool, message?: string} */
    public function delete_user(string $full_name): array {
        return Cpanel_api::call('Mysql', 'delete_user', ['name' => $full_name]);
    }
}
