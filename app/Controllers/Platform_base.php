<?php

namespace App\Controllers;

use CodeIgniter\Controller;

/**
 * Base for the platform (landlord) admin area - managing the list of
 * companies on this SaaS. Deliberately does NOT extend App_Controller or
 * Security_Controller: those assume a resolved tenant database with
 * users/clients tables, which is exactly the boundary a platform operator
 * must stay outside of. Platform admin accounts live only in the landlord
 * database's platform_admins table.
 *
 * Reachable only on the reserved platform host (app/Config/Platform.php) -
 * Tenant_resolver marks that in service('tenant'); every other host gets a
 * 404 here even though the route itself is registered globally.
 */
class Platform_base extends Controller {

    protected $landlord;
    protected $admin;

    public function __construct() {
        helper(['url', 'form']);

        if (!service('tenant')->is_platform_host) {
            show_404();
        }

        $this->landlord = db_connect('landlord');

        $admin_id = service('session')->get('platform_admin_id');
        if ($admin_id) {
            $this->admin = $this->landlord->table('platform_admins')
                ->where('id', $admin_id)
                ->where('is_active', 1)
                ->get()->getRow();
        }
    }

    protected function require_login() {
        if (!$this->admin) {
            return redirect()->to(site_url('platform_auth'));
        }
        return null;
    }
}
