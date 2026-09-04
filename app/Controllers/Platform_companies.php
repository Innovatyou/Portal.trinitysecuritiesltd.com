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

        // AutoSSL runs asynchronously across every domain on the account -
        // 'checking' means it hadn't finished within Ssl_issuer's short
        // poll window, not a real answer yet, so leave ssl_status as
        // 'pending' rather than marking it 'failed'. Re-clicking "Issue
        // certificate" shortly after gets a definitive result.
        if ($result['status'] !== 'checking') {
            $this->landlord->table('tenant_domains')->where('id', $domain_id)->update([
                'ssl_status' => $result['success'] ? 'issued' : 'failed',
                'verified_at' => $result['success'] ? date('Y-m-d H:i:s') : null,
            ]);
        }

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
        if (!empty($result['warnings'])) {
            $message .= ' (setup issues: ' . implode('; ', $result['warnings']) . ')';
        }
        session()->setFlashdata('success', $message);
        return redirect()->to(site_url('platform_companies'));
    }

    // Shows the confirm page - never deletes anything itself. destroy()
    // is the only action that actually removes a tenant, and only once
    // the typed slug matches.
    function destroy_confirm($id) {
        if ($redirect = $this->require_login()) {
            return $redirect;
        }

        $company = $this->landlord->table('tenants')->where('id', (int) $id)->get()->getRow();
        if (!$company) {
            session()->setFlashdata('error', 'Unknown company.');
            return redirect()->to(site_url('platform_companies'));
        }

        return view('platform/companies_destroy_confirm', ['company' => $company]);
    }

    function destroy() {
        if ($redirect = $this->require_login()) {
            return $redirect;
        }

        $id = (int) $this->request->getPost('id');
        $company = $this->landlord->table('tenants')->where('id', $id)->get()->getRow();

        if (!$company) {
            session()->setFlashdata('error', 'Unknown company.');
            return redirect()->to(site_url('platform_companies'));
        }

        if ((string) $this->request->getPost('confirm_slug') !== $company->slug) {
            session()->setFlashdata('error', "That didn't match \"{$company->slug}\" - nothing was deleted.");
            return redirect()->to(site_url('platform_companies/destroy_confirm/' . $id));
        }

        $result = (new Tenant_provisioning())->deprovision($company->slug);

        if (!$result['success']) {
            session()->setFlashdata('error', $result['message']);
            return redirect()->to(site_url('platform_companies'));
        }

        session()->setFlashdata('success', "\"{$company->name}\" and its database have been permanently deleted.");
        return redirect()->to(site_url('platform_companies'));
    }
}
