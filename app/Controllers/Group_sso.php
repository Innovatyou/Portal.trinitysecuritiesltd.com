<?php

namespace App\Controllers;

use CodeIgniter\Controller;

/**
 * Consumes a single-use SSO token minted by Group_portal::switch_to() and
 * establishes an ordinary session on THIS tenant's own domain - the other
 * half of the "one login, switch companies" flow. Runs on a normal tenant
 * host (never the platform host - there's no tenant DB resolved there), so
 * this deliberately does not extend App_Controller/Security_Controller:
 * there is no logged-in user yet, that's exactly what this establishes.
 */
class Group_sso extends Controller {

    function consume($token = null) {
        helper(['url']);

        if (service('tenant')->is_platform_host || !$token) {
            show_404();
        }

        $landlord = db_connect('landlord');

        // Atomic claim: a plain SELECT-then-UPDATE would let two concurrent
        // requests for the same token both pass the "not used yet" check
        // before either writes used_at. This conditional UPDATE can only
        // ever affect a row for the first request to reach it.
        $landlord->query(
            "UPDATE group_admin_sso_tokens SET used_at = NOW() WHERE token = ? AND used_at IS NULL AND expires_at > NOW()",
            [$token]
        );

        if ($landlord->affectedRows() !== 1) {
            return $this->reject('This link has expired or was already used. Go back to the company portal and switch again.');
        }

        $token_row = $landlord->table('group_admin_sso_tokens')->where('token', $token)->get()->getRow();

        if (!$token_row || (int) $token_row->tenant_id !== (int) service('tenant')->id) {
            return $this->reject('This link is not valid for this company.');
        }

        $tenant_user = db_connect()->table('users')
            ->where('id', (int) $token_row->tenant_user_id)
            ->where('status', 'active')
            ->where('deleted', 0)
            ->get()->getRow();

        if (!$tenant_user) {
            return $this->reject('Your access to this company was removed.');
        }

        service('session')->set('user_id', $tenant_user->id);
        service('session')->set('group_admin_switch_url', $this->group_portal_url());

        try {
            app_hooks()->do_action('app_hook_after_signin');
        } catch (\Exception $ex) {
            log_message('error', '[ERROR] {exception}', ['exception' => $ex]);
        }

        return redirect()->to(site_url('dashboard/view'));
    }

    private function reject(string $message) {
        return view('errors/html/error_general', [
            'heading' => 'Sign-in link invalid',
            'message' => $message,
        ]);
    }

    private function group_portal_url(): string {
        $platform_host = config('Platform')->platform_host;
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return $scheme . '://' . $platform_host . '/group_portal';
    }
}
