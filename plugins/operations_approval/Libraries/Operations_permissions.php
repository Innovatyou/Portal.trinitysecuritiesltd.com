<?php

namespace operations_approval\Libraries;

class Operations_permissions
{
    public const KEYS = [
        'operations_create_request', 'operations_view_own_requests', 'operations_view_department_requests',
        'operations_view_all_requests', 'operations_approve', 'operations_reject', 'operations_return',
        'operations_comment', 'operations_manage_workflows', 'operations_manage_forms',
        'operations_manage_settings', 'operations_view_reports', 'operations_export',
        'operations_manage_delegation', 'operations_admin_override'
    ];

    public function allowed(string $permission, $user = null): bool
    {
        $user = $user ?: (new \App\Controllers\Security_Controller(false))->login_user;
        return !empty($user->is_admin) || get_array_value($user->permissions ?? [], $permission) === '1';
    }

    public function addMenu(array $menu): array
    {
        $security = new \App\Controllers\Security_Controller(false);
        $user = $security->login_user;
        $canUse = $this->allowed('operations_create_request', $user)
            || $this->allowed('operations_view_own_requests', $user)
            || $this->allowed('operations_approve', $user)
            || $this->allowed('operations_view_all_requests', $user);
        if (!$canUse) {
            return $menu;
        }

        $sub = [
            ['name' => 'operations_dashboard', 'url' => 'operations', 'class' => 'monitor'],
            ['name' => 'operations_my_requests', 'url' => 'operations/my_requests', 'class' => 'file-text'],
            ['name' => 'operations_pending_my_approval', 'url' => 'operations/pending', 'class' => 'check-square'],
        ];
        if ($this->allowed('operations_create_request', $user)) {
            array_splice($sub, 1, 0, [[ 'name' => 'operations_new_request', 'url' => 'operations/new_request', 'class' => 'plus-circle' ]]);
        }
        if ($this->allowed('operations_view_all_requests', $user)) {
            $sub[] = ['name' => 'operations_requests', 'url' => 'operations/requests', 'class' => 'list'];
        }
        if ($this->allowed('operations_manage_workflows', $user)) {
            $sub[] = ['name' => 'operations_workflow_types', 'url' => 'operations_workflows', 'class' => 'git-branch'];
        }
        if ($this->allowed('operations_view_reports', $user)) {
            $sub[] = ['name' => 'operations_reports', 'url' => 'operations/reports', 'class' => 'bar-chart-2'];
        }
        if ($this->allowed('operations_manage_settings', $user)) {
            $sub[] = ['name' => 'operations_settings', 'url' => 'operations_settings', 'class' => 'settings'];
        }
        $menu['operations'] = ['name' => 'operations', 'class' => 'shuffle', 'submenu' => $sub, 'position' => 7];
        return $menu;
    }

    public function collectRolePermissions(array $permissions): array
    {
        $request = service('request');
        foreach (self::KEYS as $key) {
            $permissions[$key] = $request->getPost($key) ? '1' : '0';
        }
        return $permissions;
    }
}
