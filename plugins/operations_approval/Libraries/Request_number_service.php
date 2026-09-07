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
    //
    // The table is created here on first use, and a never-before-seen
    // prefix/year is seeded from the request numbers already issued in
    // oa_requests (not the old per-workflow oa_sequences counters, which
    // could already be out of sync with reality). Every tenant runs
    // install/sql on its own schedule via the plugin's "Updates" action,
    // so this can't assume that migration has already applied - it has to
    // work correctly the moment the code deploys, on its own.
    public function next(string $prefix): string
    {
        $normalizedPrefix = strtoupper(preg_replace('/[^A-Z0-9_-]/i', '', $prefix ?: 'REQ'));
        $db = db_connect('default');
        $dbPrefix = $db->getPrefix();
        $table = $dbPrefix . 'oa_prefix_sequences';
        $year = (int) date('Y');

        $db->query("CREATE TABLE IF NOT EXISTS `{$table}` (`prefix` VARCHAR(20) NOT NULL, `sequence_year` SMALLINT UNSIGNED NOT NULL, `last_number` BIGINT UNSIGNED NOT NULL DEFAULT 0, PRIMARY KEY (`prefix`,`sequence_year`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $existing = $db->query("SELECT `last_number` FROM `{$table}` WHERE `prefix`=? AND `sequence_year`=?", [$normalizedPrefix, $year])->getRow();
        if (!$existing) {
            $requestsTable = $dbPrefix . 'oa_requests';
            $seed = $db->query("SELECT MAX(CAST(SUBSTRING_INDEX(`request_no`,'-',-1) AS UNSIGNED)) seed FROM `{$requestsTable}` WHERE `request_no` LIKE ?", [$normalizedPrefix . '-' . $year . '-%'])->getRow();
            $db->query("INSERT INTO `{$table}` (`prefix`,`sequence_year`,`last_number`) VALUES (?,?,?) ON DUPLICATE KEY UPDATE `last_number`=`last_number`", [$normalizedPrefix, $year, (int) ($seed->seed ?? 0)]);
        }

        $row = $db->query("SELECT `last_number` FROM `{$table}` WHERE `prefix`=? AND `sequence_year`=? FOR UPDATE", [$normalizedPrefix, $year])->getRow();
        $next = ((int) $row->last_number) + 1;
        $db->table($table)->where(['prefix' => $normalizedPrefix, 'sequence_year' => $year])->update(['last_number' => $next]);
        return $normalizedPrefix . '-' . $year . '-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
