<?php

namespace operations_approval\Libraries;

class Request_number_service
{
    // request_no is globally unique across every workflow (oa_requests has
    // a single UNIQUE KEY on it), but the counter used to be scoped per
    // workflow_id (oa_sequences). Two different workflows sharing the same
    // prefix - e.g. both left on the "REQ" default - would each hand out
    // "REQ-2026-000001" independently, and the second one to actually
    // reach oa_requests would hit that unique-key collision. Since
    // DBDebug is off in production, that failed UPDATE just returned
    // false silently (see Workflow_engine::submit()) instead of throwing,
    // so the request's status still advanced while request_no stayed
    // empty. Keying the counter by the prefix itself instead - everyone
    // who shares "REQ" shares one counter - makes a collision structurally
    // impossible rather than merely unlikely.
    public function next(string $prefix): string
    {
        $normalizedPrefix = strtoupper(preg_replace('/[^A-Z0-9_-]/i', '', $prefix ?: 'REQ'));
        $db = db_connect('default');
        $table = $db->getPrefix() . 'oa_prefix_sequences';
        $year = (int) date('Y');
        $db->query("INSERT INTO `{$table}` (`prefix`,`sequence_year`,`last_number`) VALUES (?,?,0) ON DUPLICATE KEY UPDATE `last_number`=`last_number`", [$normalizedPrefix, $year]);
        $row = $db->query("SELECT `last_number` FROM `{$table}` WHERE `prefix`=? AND `sequence_year`=? FOR UPDATE", [$normalizedPrefix, $year])->getRow();
        $next = ((int) $row->last_number) + 1;
        $db->table($table)->where(['prefix' => $normalizedPrefix, 'sequence_year' => $year])->update(['last_number' => $next]);
        return $normalizedPrefix . '-' . $year . '-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
