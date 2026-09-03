<?php

namespace App\Controllers;

use App\Libraries\Tenant_provisioning;

class Platform_group_admins extends Platform_base {

    function __construct() {
        parent::__construct();
    }

    function index() {
        if ($redirect = $this->require_login()) {
            return $redirect;
        }

        $group_admins = $this->landlord->table('group_admins ga')
            ->select('ga.*, COUNT(gaa.id) as company_count')
            ->join('group_admin_access gaa', 'gaa.group_admin_id = ga.id', 'left')
            ->groupBy('ga.id')
            ->orderBy('ga.created_at', 'DESC')
            ->get()->getResult();

        return view('platform/group_admins_index', ['group_admins' => $group_admins]);
    }

    function create() {
        if ($redirect = $this->require_login()) {
            return $redirect;
        }
        return view('platform/group_admins_create');
    }

    function store() {
        if ($redirect = $this->require_login()) {
            return $redirect;
        }

        $data = [
            'first_name' => trim((string) $this->request->getPost('first_name')),
            'last_name' => trim((string) $this->request->getPost('last_name')),
            'email' => trim((string) $this->request->getPost('email')),
            'password' => (string) $this->request->getPost('password'),
        ];

        if (!$data['first_name'] || !$data['last_name'] || !$data['password'] || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return view('platform/group_admins_create', ['error' => 'All fields are required and email must be valid.', 'old' => $data]);
        }

        if ($this->landlord->table('group_admins')->where('email', $data['email'])->countAllResults() > 0) {
            return view('platform/group_admins_create', ['error' => 'A group admin with that email already exists.', 'old' => $data]);
        }

        $this->landlord->table('group_admins')->insert([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        session()->setFlashdata('success', "\"{$data['first_name']} {$data['last_name']}\" can now sign in and be granted company access.");
        return redirect()->to(site_url('platform_group_admins'));
    }

    function show($id) {
        if ($redirect = $this->require_login()) {
            return $redirect;
        }

        $group_admin = $this->landlord->table('group_admins')->where('id', (int) $id)->get()->getRow();
        if (!$group_admin) {
            session()->setFlashdata('error', 'Unknown group admin.');
            return redirect()->to(site_url('platform_group_admins'));
        }

        $access = $this->landlord->table('group_admin_access gaa')
            ->select('gaa.*, t.name, t.slug')
            ->join('tenants t', 't.id = gaa.tenant_id')
            ->where('gaa.group_admin_id', $group_admin->id)
            ->orderBy('t.name', 'ASC')
            ->get()->getResult();

        $granted_ids = array_column($access, 'tenant_id');
        $available = $this->landlord->table('tenants')
            ->where('status', 'active')
            ->where(count($granted_ids) ? "id NOT IN (" . implode(',', array_map('intval', $granted_ids)) . ")" : '1=1')
            ->orderBy('name', 'ASC')
            ->get()->getResult();

        return view('platform/group_admins_show', [
            'group_admin' => $group_admin,
            'access' => $access,
            'available' => $available,
        ]);
    }

    function grant() {
        if ($redirect = $this->require_login()) {
            return $redirect;
        }

        $group_admin_id = (int) $this->request->getPost('group_admin_id');
        $tenant_id = (int) $this->request->getPost('tenant_id');

        $group_admin = $this->landlord->table('group_admins')->where('id', $group_admin_id)->get()->getRow();
        $tenant = $this->landlord->table('tenants')->where('id', $tenant_id)->get()->getRow();

        if (!$group_admin || !$tenant) {
            session()->setFlashdata('error', 'Unknown group admin or company.');
            return redirect()->to(site_url('platform_group_admins'));
        }

        try {
            $result = (new Tenant_provisioning())->link_or_create_group_admin_user($tenant, $group_admin);
        } catch (\Throwable $e) {
            session()->setFlashdata('error', "Could not reach {$tenant->name}'s database: {$e->getMessage()}");
            return redirect()->to(site_url('platform_group_admins/show/' . $group_admin_id));
        }

        $this->landlord->table('group_admin_access')->insert([
            'group_admin_id' => $group_admin_id,
            'tenant_id' => $tenant_id,
            'tenant_user_id' => $result['tenant_user_id'],
            'created_by_grant' => $result['created'] ? 1 : 0,
            'granted_by' => $this->admin->id,
            'granted_at' => date('Y-m-d H:i:s'),
        ]);

        $message = $result['created']
            ? "Granted access to {$tenant->name} - a new admin account was created there."
            : "Granted access to {$tenant->name} - linked to the existing staff account with this email.";
        session()->setFlashdata('success', $message);
        return redirect()->to(site_url('platform_group_admins/show/' . $group_admin_id));
    }

    function revoke() {
        if ($redirect = $this->require_login()) {
            return $redirect;
        }

        $access_id = (int) $this->request->getPost('access_id');
        $group_admin_id = (int) $this->request->getPost('group_admin_id');

        $access = $this->landlord->table('group_admin_access')->where('id', $access_id)->get()->getRow();
        if (!$access) {
            session()->setFlashdata('error', 'Unknown access grant.');
            return redirect()->to(site_url('platform_group_admins/show/' . $group_admin_id));
        }

        $tenant = $this->landlord->table('tenants')->where('id', $access->tenant_id)->get()->getRow();

        if ($tenant && $access->created_by_grant) {
            try {
                (new Tenant_provisioning())->deactivate_tenant_user($tenant, (int) $access->tenant_user_id);
            } catch (\Throwable $e) {
                session()->setFlashdata('error', "Access record removed, but could not deactivate the account in {$tenant->name}: {$e->getMessage()}");
                $this->landlord->table('group_admin_access')->where('id', $access_id)->delete();
                return redirect()->to(site_url('platform_group_admins/show/' . $group_admin_id));
            }
        }

        $this->landlord->table('group_admin_access')->where('id', $access_id)->delete();

        session()->setFlashdata('success', 'Access revoked.');
        return redirect()->to(site_url('platform_group_admins/show/' . $group_admin_id));
    }
}
