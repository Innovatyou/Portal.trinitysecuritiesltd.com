<?php

namespace App\Libraries;

/**
 * Registers/removes each tenant's custom domain with cPanel itself (an
 * Addon Domain for the customer's root domain, a Subdomain for the
 * "portal.<domain>" hostname the app actually serves) - without this,
 * cPanel has no idea the domain exists at all, and everything that depends
 * on cPanel's own domain registry - AutoSSL chief among them - silently
 * never runs for it. Apache still serves the app fine over plain HTTP
 * regardless, via its own catch-all vhost, which is exactly what let this
 * go unnoticed until AutoSSL (see Ssl_issuer) was wired up.
 *
 * Confirmed directly against this account's existing tenants
 * (portal.trinityfinancialng.com, platform.trinitysecuritiesltd.com) via
 * DomainInfo::domains_data: both are plain cPanel subdomains, and multiple
 * subdomains happily share the exact same document root - so new tenants
 * get their "portal.<domain>" subdomain pointed at the same shared docroot
 * everything else uses, rather than a dedicated folder per tenant.
 *
 * Registration here is deliberately best-effort from Tenant_provisioning's
 * point of view (see provision()/deprovision()): a company is still a
 * working tenant without it (plain HTTP keeps working via the catch-all
 * vhost either way), it just means SSL/AutoSSL won't work for that domain
 * until whatever failed here is fixed and retried.
 */
class Cpanel_domains {

    private string $shared_docroot;

    public function __construct() {
        // Relative to the cPanel account's home directory - matches how
        // platform.trinitysecuritiesltd.com's own subdomain entry is
        // configured (confirmed via DomainInfo::domains_data).
        $this->shared_docroot = 'public_html/portal.trinitysecuritiesltd.com';
    }

    /**
     * Splits "portal.example.com" into ['portal', 'example.com']. Assumes
     * the convention every existing tenant domain already follows: a
     * single-label subdomain in front of the customer's own root domain.
     * Not a general public-suffix-aware split - a bare root domain with no
     * subdomain label, or a domain under a multi-label TLD, isn't handled.
     *
     * @return array{0: string, 1: string}|null null if $domain doesn't
     *         look like "label.root.domain" (fewer than 2 dots).
     */
    public function split(string $domain): ?array {
        if (substr_count($domain, '.') < 2) {
            return null;
        }

        return explode('.', $domain, 2);
    }

    public function is_root_domain_registered(string $root_domain): bool {
        $result = Cpanel_api::call('DomainInfo', 'list_domains', []);
        if (!$result['success']) {
            return false;
        }

        $data = $result['data'] ?? [];
        if (($data['main_domain'] ?? null) === $root_domain) {
            return true;
        }

        return in_array($root_domain, $data['addon_domains'] ?? [], true);
    }

    /** @return array{success: bool, message?: string} */
    public function add_root_domain(string $root_domain): array {
        // cPanel ties every addon domain to an auto-created subdomain of
        // the account's own primary domain - this label just needs to be
        // unique on the account, so the root domain's own first label
        // (matching how trinityfinancialng.com was already set up) works,
        // as long as two tenants never share a root domain, which
        // Tenant_provisioning::provision() already enforces separately.
        $primary_subdomain_label = explode('.', $root_domain, 2)[0];

        return Cpanel_api::call('AddonDomain', 'addaddondomain', [
            'newdomain' => $root_domain,
            'subdomain' => $primary_subdomain_label,
            'dir' => $this->shared_docroot,
        ]);
    }

    /** @return array{success: bool, message?: string} */
    public function add_subdomain(string $label, string $root_domain): array {
        return Cpanel_api::call('SubDomain', 'addsubdomain', [
            'domain' => $label,
            'rootdomain' => $root_domain,
            'dir' => $this->shared_docroot,
        ]);
    }

    /**
     * Best-effort - mirrors the rest of Tenant_provisioning::deprovision(),
     * which doesn't fail the whole operation over cleanup steps that don't
     * strictly need to succeed for the tenant to be gone from the app's own
     * perspective.
     */
    public function remove_subdomain(string $label, string $root_domain): void {
        Cpanel_api::call('SubDomain', 'delsubdomain', ['domain' => "{$label}.{$root_domain}"]);
    }

    /** Best-effort, see remove_subdomain(). */
    public function remove_root_domain(string $root_domain): void {
        Cpanel_api::call('AddonDomain', 'deladdondomain', ['domain' => $root_domain]);
    }
}
