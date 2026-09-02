<?php

namespace App\Controllers;

use App\Libraries\Ssl_issuer;
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

        $domains = $this->landlord->table('tenant_domains')->get()->getResult();

        return view('platform/companies_index', ['companies' => $companies, 'domains' => $domains]);
    }

    // Manually triggered once a domain's DNS is confirmed pointed at this
    // server - see Phase 3 in the SaaS plan for why this isn't auto-polled.
    function issue_ssl() {
        if ($redirect = $this->require_login()) {
            return $redirect;
        }

        $domain_id = (int) $this->request->getPost('domain_id');
        $domain_row = $this->landlord->table('tenant_domains')->where('id', $domain_id)->get()->getRow();

        if (!$domain_row) {
            session()->setFlashdata('error', 'Unknown domain.');
            return redirect()->to(site_url('platform_companies'));
        }

        $result = (new Ssl_issuer())->issue($domain_row->domain, FCPATH);

        $this->landlord->table('tenant_domains')->where('id', $domain_id)->update([
            'ssl_status' => $result['success'] ? 'issued' : 'failed',
            'verified_at' => $result['success'] ? date('Y-m-d H:i:s') : null,
        ]);

        session()->setFlashdata($result['success'] ? 'success' : 'error', $result['message']);
        return redirect()->to(site_url('platform_companies'));
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

        $message = "\"{$data['name']}\" is live at http://{$result['domain']}/";
        if (!empty($result['plugin_warnings'])) {
            $message .= ' (plugin setup issues: ' . implode('; ', $result['plugin_warnings']) . ')';
        }
        session()->setFlashdata('success', $message);
        return redirect()->to(site_url('platform_companies'));
    }
}
