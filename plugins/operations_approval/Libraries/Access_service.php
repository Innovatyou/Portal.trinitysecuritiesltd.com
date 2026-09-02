<?php

namespace operations_approval\Libraries;

class Access_service
{
    private $db;
    private $p;
    private $permissions;

    public function __construct()
    {
        $this->db = db_connect('default');
        $this->p = $this->db->getPrefix();
        $this->permissions = new Operations_permissions();
    }

    public function canView(object $request, object $user): bool
    {
        if ($this->permissions->allowed('operations_view_all_requests', $user) || (int) $request->requester_id === (int) $user->id) return true;
        if ($this->permissions->allowed('operations_view_department_requests', $user)) {
            $membership = $this->db->table($this->p . 'oa_user_departments')->where(['user_id' => $user->id, 'department_id' => $request->department_id])->countAllResults();
            if ($membership) return true;
        }
        $assigned = $this->db->table($this->p . 'oa_stage_instances i')->join($this->p . 'oa_assignments a', 'a.stage_instance_id=i.id')->where(['i.request_id' => $request->id, 'a.user_id' => $user->id])->countAllResults();
        return $assigned > 0;
    }

    public function canEdit(object $request, object $user): bool
    {
        return (int) $request->requester_id === (int) $user->id && in_array($request->status, ['draft', 'returned'], true);
    }
}

