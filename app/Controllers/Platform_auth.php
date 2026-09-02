<?php

namespace App\Controllers;

class Platform_auth extends Platform_base {

    function index() {
        if ($this->admin) {
            return redirect()->to(site_url('platform_companies'));
        }
        return view('platform/login');
    }

    function login() {
        $email = trim((string) $this->request->getPost('email'));
        $password = (string) $this->request->getPost('password');

        $admin = $this->landlord->table('platform_admins')
            ->where('email', $email)
            ->where('is_active', 1)
            ->get()->getRow();

        if (!$admin || !password_verify($password, $admin->password_hash)) {
            return view('platform/login', ['error' => 'Invalid email or password.']);
        }

        service('session')->set('platform_admin_id', $admin->id);
        $this->landlord->table('platform_admins')->where('id', $admin->id)->update(['last_login_at' => date('Y-m-d H:i:s')]);

        return redirect()->to(site_url('platform_companies'));
    }

    function logout() {
        service('session')->remove('platform_admin_id');
        return redirect()->to(site_url('platform_auth'));
    }
}
