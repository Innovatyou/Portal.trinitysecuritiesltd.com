<?php

namespace operations_approval\Libraries;

class Notification_service
{
    private $db;
    private $p;

    public function __construct()
    {
        $this->db = db_connect('default');
        $this->p = $this->db->getPrefix();
    }

    public function send(string $event, int $requestId, array $recipientIds, ?object $actor = null, array $context = []): void
    {
        $recipientIds = array_values(array_unique(array_filter(array_map('intval', $recipientIds))));
        if (!$recipientIds) return;
        $request = $this->db->table($this->p . 'oa_requests r')->select('r.*, w.name workflow_name')->join($this->p . 'oa_workflows w', 'w.id=r.workflow_id')->where('r.id', $requestId)->get()->getRow();
        if (!$request || $request->test_mode) return;
        $recipients = implode(',', $recipientIds);
        $dedupeSuffix = (string) ($context['dedupe'] ?? get_current_utc_time());
        foreach ($recipientIds as $recipientId) {
            $dedupe = hash('sha256', $event . '|' . $requestId . '|' . $recipientId . '|' . $dedupeSuffix);
            try {
                $this->db->table($this->p . 'oa_notification_log')->insert(['request_id' => $requestId, 'event' => $event, 'recipient_id' => $recipientId, 'dedupe_key' => $dedupe, 'channel' => 'rise', 'status' => 'queued', 'created_at' => get_current_utc_time()]);
            } catch (\Throwable $e) {
                continue;
            }
        }
        log_notification('operations_' . $event, [
            'plugin_request_id' => $requestId,
            'plugin_recipients' => $recipients,
            'plugin_comment' => (string) ($context['comment'] ?? ''),
            'notify_to' => $recipients
        ], (int) ($actor->id ?? 0));
    }

    public function requester(int $requestId): array
    {
        $row = $this->db->table($this->p . 'oa_requests')->select('requester_id')->where('id', $requestId)->get()->getRow();
        return $row ? [(int) $row->requester_id] : [];
    }
}
