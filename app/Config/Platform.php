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
}
