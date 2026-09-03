<?php

namespace App\Controllers;

class Group_portal extends Group_base {

    function __construct() {
        parent::__construct();
    }

    function index() {
        if ($login_redirect = $this->require_login()) {
            return $login_redirect;
        }

        $companies = $this->landlord->table('group_admin_access ga')
            ->select('t.id, t.name, t.slug, td.domain')
            ->join('tenants t', 't.id = ga.tenant_id')
            ->join('tenant_domains td', 'td.tenant_id = t.id AND td.is_primary = 1')
            ->where('ga.group_admin_id', $this->group_admin->id)
            ->where('t.status', 'active')
            ->orderBy('t.name', 'ASC')
            ->get()->getResult();

        return view('group/portal', ['group_admin' => $this->group_admin, 'companies' => $companies]);
    }

    function switch_to($tenant_id) {
        if ($login_redirect = $this->require_login()) {
            return $login_redirect;
        }

        $access = $this->landlord->table('group_admin_access')
            ->where('group_admin_id', $this->group_admin->id)
            ->where('tenant_id', (int) $tenant_id)
            ->get()->getRow();

        if (!$access) {
            return view('group/portal', [
                'group_admin' => $this->group_admin,
                'companies' => [],
                'error' => 'You do not have access to that company.',
            ]);
        }

        $domain = $this->landlord->table('tenant_domains')
            ->where('tenant_id', (int) $tenant_id)
            ->where('is_primary', 1)
            ->get()->getRow();

        if (!$domain) {
            return view('group/portal', [
                'group_admin' => $this->group_admin,
                'companies' => [],
                'error' => 'That company has no primary domain configured.',
            ]);
        }

        $token = bin2hex(random_bytes(32));
        // expires_at is computed by MySQL itself (NOW() + INTERVAL), not
        // PHP's date() - this app's MySQL server and PHP run in different
        // timezones (confirmed: an hour apart), and Group_sso::consume()'s
        // expiry check compares against MySQL's own NOW(), so a
        // PHP-computed timestamp would already read as expired the instant
        // it's written.
        $builder = $this->landlord->table('group_admin_sso_tokens');
        $builder->set('token', $token);
        $builder->set('group_admin_id', (int) $this->group_admin->id);
        $builder->set('tenant_id', (int) $tenant_id);
        $builder->set('tenant_user_id', (int) $access->tenant_user_id);
        $builder->set('expires_at', 'NOW() + INTERVAL 120 SECOND', false);
        $builder->set('created_at', 'NOW()', false);
        $builder->insert();

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return redirect()->to($scheme . '://' . $domain->domain . '/group_sso/consume/' . $token);
    }

    function logout() {
        service('session')->remove('group_admin_id');
        return redirect()->to(site_url('group_auth'));
    }
}
