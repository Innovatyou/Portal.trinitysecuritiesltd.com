<?php

namespace App\Controllers;

use App\Libraries\Tenant_provisioning;

class Platform_companies extends Platform_base {

    function __construct() {
        parent::__construct();
    }

    function index() {
        if ($redirect = $this->require_login()) {
            return $redirect;
        }

        $companies = $this->landlord->table('tenants t')
            ->select('t.*, GROUP_CONCAT(d.domain SEPARATOR ", ") as domains')
            ->join('tenant_domains d', 'd.tenant_id = t.id', 'left')
            ->groupBy('t.id')
            ->orderBy('t.created_at', 'DESC')
            ->get()->getResult();

        return view('platform/companies_index', ['companies' => $companies]);
    }

    function create() {
        if ($redirect = $this->require_login()) {
            return $redirect;
        }
        return view('platform/companies_create');
    }

    function store() {
        if ($redirect = $this->require_login()) {
            return $redirect;
        }

        $data = [
            'name' => $this->request->getPost('name'),
            'slug' => $this->request->getPost('slug'),
            'domain' => $this->request->getPost('domain'),
            'admin_first' => $this->request->getPost('admin_first'),
            'admin_last' => $this->request->getPost('admin_last'),
            'admin_email' => $this->request->getPost('admin_email'),
            'admin_password' => $this->request->getPost('admin_password'),
        ];

        $result = (new Tenant_provisioning())->provision($data);

        if (!$result['success']) {
            return view('platform/companies_create', ['error' => $result['message'], 'old' => $data]);
        }

        session()->setFlashdata('success', "\"{$data['name']}\" is live at http://{$result['domain']}/");
        return redirect()->to(site_url('platform_companies'));
    }
}
