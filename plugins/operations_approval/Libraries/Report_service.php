<?php

namespace operations_approval\Libraries;

class Report_service
{
    private $db; private $p;
    public function __construct() { $this->db = db_connect('default'); $this->p = $this->db->getPrefix(); }

    public function summary(array $filters = []): array
    {
        $apply = function ($builder) use ($filters) {
            if (!empty($filters['date_from'])) $builder->where('r.created_at >=', $filters['date_from'] . ' 00:00:00');
            if (!empty($filters['date_to'])) $builder->where('r.created_at <=', $filters['date_to'] . ' 23:59:59');
            if (!empty($filters['workflow_id'])) $builder->where('r.workflow_id', (int) $filters['workflow_id']);
            if (!empty($filters['status'])) $builder->where('r.status', $filters['status']);
            if (!empty($filters['department_id'])) $builder->where('r.department_id', (int) $filters['department_id']);
            return $builder;
        };
        $status = $apply($this->db->table($this->p . 'oa_requests r')->select('r.status label, COUNT(*) total')->where('r.deleted', 0))->groupBy('r.status')->get()->getResultArray();
        $workflow = $apply($this->db->table($this->p . 'oa_requests r')->select('w.name label, COUNT(*) total')->join($this->p . 'oa_workflows w', 'w.id=r.workflow_id')->where('r.deleted', 0))->groupBy('w.id')->orderBy('total', 'DESC')->get()->getResultArray();
        $monthly = $apply($this->db->table($this->p . 'oa_requests r')->select("DATE_FORMAT(r.created_at,'%Y-%m') label, COUNT(*) total")->where('r.deleted', 0))->groupBy("DATE_FORMAT(r.created_at,'%Y-%m')")->orderBy('label')->get()->getResultArray();
        $turnaround = $this->db->table($this->p . 'oa_stage_instances')->select('name_snapshot label, ROUND(AVG(TIMESTAMPDIFF(MINUTE,activated_at,completed_at))/60,2) total')->where('activated_at IS NOT NULL', null, false)->where('completed_at IS NOT NULL', null, false)->groupBy('name_snapshot')->orderBy('total', 'DESC')->get()->getResultArray();
        return compact('status', 'workflow', 'monthly', 'turnaround');
    }
}

