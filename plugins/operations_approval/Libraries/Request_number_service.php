<?php

namespace operations_approval\Libraries;

class Request_number_service
{
    public function next(int $workflowId, string $prefix): string
    {
        $db = db_connect('default');
        $table = $db->getPrefix() . 'oa_sequences';
        $year = (int) date('Y');
        $db->transBegin();
        try {
            $db->query("INSERT INTO `{$table}` (`workflow_id`,`sequence_year`,`last_number`) VALUES (?,?,0) ON DUPLICATE KEY UPDATE `last_number`=`last_number`", [$workflowId, $year]);
            $row = $db->query("SELECT `last_number` FROM `{$table}` WHERE `workflow_id`=? AND `sequence_year`=? FOR UPDATE", [$workflowId, $year])->getRow();
            $next = ((int) $row->last_number) + 1;
            $db->table($table)->where(['workflow_id' => $workflowId, 'sequence_year' => $year])->update(['last_number' => $next]);
            $db->transCommit();
            return strtoupper(preg_replace('/[^A-Z0-9_-]/i', '', $prefix ?: 'REQ')) . '-' . $year . '-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }
}

