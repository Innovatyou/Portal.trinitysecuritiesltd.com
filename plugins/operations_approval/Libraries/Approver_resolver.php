<?php

namespace operations_approval\Libraries;

class Approver_resolver
{
    public function resolve(object $stage, object $request, array $values): array
    {
        $db = db_connect('default');
        $config = json_decode($stage->approver_config_json ?: '{}', true) ?: [];
        $ids = [];
        if ($stage->approver_type === 'users') {
            $ids = array_map('intval', $config['user_ids'] ?? []);
        } elseif ($stage->approver_type === 'role') {
            $roleId = (int) ($config['role_id'] ?? 0);
            $rows = $db->table($db->getPrefix() . 'users')->select('id')->where(['role_id' => $roleId, 'status' => 'active', 'deleted' => 0, 'user_type' => 'staff'])->get()->getResult();
            $ids = array_map(fn($row) => (int) $row->id, $rows);
        } elseif ($stage->approver_type === 'dynamic_field') {
            $value = $values[$config['field_key'] ?? ''] ?? 0;
            $ids = [(int) $value];
        } elseif ($stage->approver_type === 'group') {
            $rows = $db->table($db->getPrefix() . 'oa_approver_group_members')->select('user_id')->where('group_id', (int) ($config['group_id'] ?? 0))->get()->getResult();
            $ids = array_map(fn($row) => (int) $row->user_id, $rows);
        } elseif ($stage->approver_type === 'requester_manager') {
            $row = $db->table($db->getPrefix() . 'oa_user_departments')->select('manager_user_id')->where('user_id', $request->requester_id)->get()->getRow();
            $ids = $row ? [(int) $row->manager_user_id] : [];
        } elseif ($stage->approver_type === 'department_head') {
            $departmentId = (int) ($config['department_id'] ?? $request->department_id);
            $row = $db->table($db->getPrefix() . 'oa_departments')->select('head_user_id')->where(['id' => $departmentId, 'deleted' => 0, 'status' => 'active'])->get()->getRow();
            $ids = $row ? [(int) $row->head_user_id] : [];
        } elseif ($stage->approver_type === 'requester_department_head') {
            $row = $db->table($db->getPrefix() . 'oa_user_departments ud')->select('d.head_user_id')->join($db->getPrefix() . 'oa_departments d', 'd.id=ud.department_id')->where('ud.user_id', $request->requester_id)->get()->getRow();
            $ids = $row ? [(int) $row->head_user_id] : [];
        }
        $ids = array_values(array_unique(array_filter($ids)));
        $settings = json_decode($stage->settings_json ?: '{}', true) ?: [];
        if (empty($settings['allow_self_approval'])) {
            $ids = array_values(array_diff($ids, [(int) $request->requester_id]));
        }
        $resolved = [];
        $delegations = new Delegation_service();
        foreach ($ids as $id) {
            $resolved[] = $delegations->activeDelegate($id, (int) $request->workflow_id) ?: $id;
        }
        return array_values(array_unique($resolved));
    }
}
