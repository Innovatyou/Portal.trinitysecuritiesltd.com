<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Multi-tenant control-plane settings.
 *
 * platform_host is the one reserved hostname that is NOT looked up as a
 * tenant - it only ever talks to the landlord database (see
 * app/Controllers/Platform/*, added in a later phase). Every other
 * incoming Host header is resolved against tenant_domains.
 */
class Platform extends BaseConfig {

    public string $platform_host = 'platform.trinitysecuritiesltd.com';

    /**
     * Whether Tenant_provisioning creates each tenant's database/MySQL user
     * via cPanel's UAPI (shelling out to /usr/local/cpanel/bin/uapi as the
     * account's own OS user - see App\Libraries\Cpanel_mysql) instead of
     * raw CREATE DATABASE/CREATE USER/GRANT SQL.
     *
     * A real cPanel MySQL account is a normal, non-superuser account:
     * confirmed directly against production that even a wildcard-scoped
     * `GRANT ALL PRIVILEGES ON \`tenant_%\`.* ... WITH GRANT OPTION` isn't
     * enough for MySQL to allow re-granting to a brand-new tenant user -
     * its re-grant check requires an exact, non-wildcard match, which can't
     * exist yet for a tenant that hasn't been created. Raw-SQL provisioning
     * only works against an unrestricted MySQL user like local dev's
     * (Laragon's root) - cPanel's own API creates the database/user/grant
     * through its own root-level internals instead, so the app-facing
     * landlord DB user never needs elevated MySQL privileges.
     *
     * Set via .env: platform.use_cpanel_provisioning = true
     */
    public bool $use_cpanel_provisioning = false;

    /**
     * The cPanel account username that will own every tenant database/user.
     * cPanel requires this exact "{cpanel_username}_" prefix on every name
     * passed to Mysql::create_database/create_user - confirmed directly
     * that a bare name is rejected outright, not auto-prefixed.
     *
     * Set via .env: platform.cpanel_username = admintsl
     */
    public string $cpanel_username = '';
}
