<?php

namespace operations_approval\Libraries;

class Audit_service
{
    private $db;
    private $table;

    public function __construct()
    {
        $this->db = db_connect('default');
        $this->table = $this->db->getPrefix() . 'oa_audit';
    }

    public function record(string $action, ?int $requestId, ?int $stageId, ?object $actor, array $old = [], array $new = [], array $metadata = []): int
    {
        $previous = $this->db->table($this->table)->select('integrity_hash')->where('request_id', $requestId)->orderBy('id', 'DESC')->get(1)->getRow();
        $timestamp = date('Y-m-d H:i:s.u');
        $payload = [
            'request_id' => $requestId, 'stage_instance_id' => $stageId, 'actor_id' => $actor->id ?? null,
            'actor_name_snapshot' => $actor ? trim(($actor->first_name ?? '') . ' ' . ($actor->last_name ?? '')) : null,
            'action' => $action, 'old_value_json' => $old ? json_encode($old) : null,
            'new_value_json' => $new ? json_encode($new) : null, 'metadata_json' => $metadata ? json_encode($metadata) : null,
            'ip_address' => service('request')->getIPAddress(), 'created_at' => $timestamp
        ];
        $payload['integrity_hash'] = hash('sha256', ($previous->integrity_hash ?? '') . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $this->db->table($this->table)->insert($payload);
        return (int) $this->db->insertID();
    }
}

