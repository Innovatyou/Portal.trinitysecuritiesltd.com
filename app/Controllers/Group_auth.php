<?php

namespace App\Controllers;

class Group_auth extends Group_base {

    function index() {
        if ($this->group_admin) {
            return redirect()->to(site_url('group_portal'));
        }
        return view('group/login');
    }

    function login() {
        $email = trim((string) $this->request->getPost('email'));
        $password = (string) $this->request->getPost('password');

        $admin = $this->landlord->table('group_admins')
            ->where('email', $email)
            ->where('is_active', 1)
            ->get()->getRow();

        if (!$admin || !password_verify($password, $admin->password_hash)) {
            return view('group/login', ['error' => 'Invalid email or password.']);
        }

        service('session')->set('group_admin_id', $admin->id);
        $this->landlord->table('group_admins')->where('id', $admin->id)->update(['last_login_at' => date('Y-m-d H:i:s')]);

        return redirect()->to(site_url('group_portal'));
    }

    function logout() {
        service('session')->remove('group_admin_id');
        return redirect()->to(site_url('group_auth'));
    }
}
