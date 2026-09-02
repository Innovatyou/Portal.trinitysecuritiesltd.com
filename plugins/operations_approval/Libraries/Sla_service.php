<?php

namespace operations_approval\Libraries;

class Sla_service
{
    private $db;
    private $p;

    public function __construct()
    {
        $this->db = db_connect('default');
        $this->p = $this->db->getPrefix();
    }

    public function process(): void
    {
        $now = get_current_utc_time();
        $settings = $this->settings();
        $stages = $this->db->table($this->p . 'oa_stage_instances i')->select('i.*,s.settings_json')->join($this->p . 'oa_stages s', 's.id=i.stage_id')->whereIn('i.status', ['active', 'overdue'])->get()->getResult();
        foreach ($stages as $stage) {
            $assignments = $this->db->table($this->p . 'oa_assignments')->where(['stage_instance_id' => $stage->id, 'status' => 'pending'])->get()->getResult();
            if (!$assignments) continue;
            $elapsed = (time() - strtotime($stage->activated_at)) / 3600;
            if ($elapsed >= $settings['reminder_hours']) {
                foreach ($assignments as $assignment) $this->queueAndSend('approval_reminder', $stage, $assignment, (int) $assignment->user_id, 'reminder-' . $stage->id . '-' . $assignment->id);
                if (!$stage->reminder_sent_at) $this->db->table($this->p . 'oa_stage_instances')->where('id', $stage->id)->update(['reminder_sent_at' => $now]);
            }
            if ($stage->due_at && strtotime($stage->due_at) <= time() && $stage->status === 'active') {
                $this->db->table($this->p . 'oa_stage_instances')->where(['id' => $stage->id, 'status' => 'active'])->update(['status' => 'overdue']);
                (new Audit_service())->record('sla_breached', (int) $stage->request_id, (int) $stage->id, null, [], ['due_at' => $stage->due_at]);
                foreach ($assignments as $assignment) $this->queueAndSend('sla_breached', $stage, $assignment, (int) $assignment->user_id, 'breach-' . $stage->id . '-' . $assignment->id);
            }
            if ($elapsed >= $settings['escalation_hours'] && !$stage->escalated_at) {
                $stageSettings = json_decode($stage->settings_json ?: '{}', true) ?: [];
                $recipients = array_map('intval', $stageSettings['escalation_user_ids'] ?? []);
                if (!$recipients) {
                    $admins = $this->db->table($this->p . 'users')->select('id')->where(['is_admin' => 1, 'status' => 'active', 'deleted' => 0])->get()->getResult();
                    $recipients = array_map(fn($user) => (int) $user->id, $admins);
                }
                foreach ($recipients as $recipient) $this->queueAndSend('sla_breached', $stage, null, $recipient, 'escalation-' . $stage->id . '-' . $recipient);
                $this->db->table($this->p . 'oa_stage_instances')->where('id', $stage->id)->update(['escalated_at' => $now]);
                (new Audit_service())->record('request_escalated', (int) $stage->request_id, (int) $stage->id, null, [], ['recipients' => $recipients]);
            }
        }
    }

    private function queueAndSend(string $event, object $stage, ?object $assignment, int $recipient, string $dedupe): void
    {
        try {
            $this->db->table($this->p . 'oa_reminders')->insert(['request_id' => $stage->request_id, 'stage_instance_id' => $stage->id, 'assignment_id' => $assignment->id ?? null, 'reminder_type' => $event, 'recipient_id' => $recipient, 'scheduled_for' => get_current_utc_time(), 'sent_at' => get_current_utc_time(), 'status' => 'sent', 'attempts' => 1, 'dedupe_key' => $dedupe]);
        } catch (\Throwable $e) {
            return;
        }
        (new Notification_service())->send($event, (int) $stage->request_id, [$recipient], null, ['dedupe' => $dedupe]);
    }

    private function settings(): array
    {
        $rows = $this->db->table($this->p . 'oa_settings')->whereIn('setting_key', ['reminder_hours', 'escalation_hours'])->get()->getResult();
        $values = [];
        foreach ($rows as $row) $values[$row->setting_key] = $row->setting_value;
        return ['reminder_hours' => max(1, (int) ($values['reminder_hours'] ?? 24)), 'escalation_hours' => max(1, (int) ($values['escalation_hours'] ?? 72))];
    }
}
