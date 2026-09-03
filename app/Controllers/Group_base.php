<?php

namespace App\Controllers;

use CodeIgniter\Controller;

/**
 * Base for the Group MD area - a business-side login (separate from
 * platform_admins, the SaaS operator's own login) that can be granted admin
 * access into several companies and switch between them. See
 * app/Libraries/Tenant_provisioning.php for how that access is granted, and
 * Group_sso.php for how a switch actually establishes a session on the
 * target tenant's own domain.
 *
 * Deliberately does not extend App_Controller/Security_Controller, for the
 * same reason Platform_base doesn't: group_admins accounts live only in the
 * landlord database, entirely outside any tenant's users table.
 *
 * Reachable only on the reserved platform host, same as Platform_base.
 */
class Group_base extends Controller {

    protected $landlord;
    protected $group_admin;

    public function __construct() {
        helper(['url', 'form']);

        if (!service('tenant')->is_platform_host) {
            show_404();
        }

        $this->landlord = db_connect('landlord');

        $group_admin_id = service('session')->get('group_admin_id');
        if ($group_admin_id) {
            $this->group_admin = $this->landlord->table('group_admins')
                ->where('id', $group_admin_id)
                ->where('is_active', 1)
                ->get()->getRow();
        }
    }

    protected function require_login() {
        if (!$this->group_admin) {
            return redirect()->to(site_url('group_auth'));
        }
        return null;
    }
}
