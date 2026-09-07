<?php

namespace operations_approval\Libraries;

class Request_number_service
{
    // Callers (Workflow_engine::submit()) always run this inside their own
    // already-open transaction. This used to wrap itself in a *second*
    // transBegin()/transCommit() - harmless in the common case (CI4 just
    // tracks nesting depth), but if this INSERT/SELECT FOR UPDATE/UPDATE
    // sequence ever threw partway through, its own transRollback() could
    // roll back the outer transaction's earlier work too (the request's
    // status update happens in the same outer transaction, right after this
    // returns) while the caller's catch block still believes only its own
    // work needs rolling back - a real, if rare, way to end up with a
    // request that has genuinely moved status but never got a request_no.
    // Just participate in whatever transaction is already open.
    public function next(int $workflowId, string $prefix): string
    {
        $db = db_connect('default');
        $table = $db->getPrefix() . 'oa_sequences';
        $year = (int) date('Y');
        $db->query("INSERT INTO `{$table}` (`workflow_id`,`sequence_year`,`last_number`) VALUES (?,?,0) ON DUPLICATE KEY UPDATE `last_number`=`last_number`", [$workflowId, $year]);
        $row = $db->query("SELECT `last_number` FROM `{$table}` WHERE `workflow_id`=? AND `sequence_year`=? FOR UPDATE", [$workflowId, $year])->getRow();
        $next = ((int) $row->last_number) + 1;
        $db->table($table)->where(['workflow_id' => $workflowId, 'sequence_year' => $year])->update(['last_number' => $next]);
        return strtoupper(preg_replace('/[^A-Z0-9_-]/i', '', $prefix ?: 'REQ')) . '-' . $year . '-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}

