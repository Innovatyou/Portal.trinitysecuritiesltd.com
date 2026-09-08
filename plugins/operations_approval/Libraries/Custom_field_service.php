<?php

namespace operations_approval\Libraries;

class Custom_field_service
{
    private $db;
    private $p;

    public function __construct()
    {
        $this->db = db_connect('default');
        $this->p = $this->db->getPrefix();
        $this->ensureTable();
    }

    // Self-creating on first use rather than relying only on
    // install/sql, same reasoning as Request_number_service::next() - a
    // tenant's plugin "Updates" action is a manual admin step a code
    // deploy doesn't trigger on its own, so this has to work immediately
    // without it.
    private function ensureTable(): void
    {
        $table = $this->p . 'oa_request_custom_fields';
        $this->db->query("CREATE TABLE IF NOT EXISTS `{$table}` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `request_id` BIGINT UNSIGNED NOT NULL, `label` VARCHAR(180) NOT NULL, `value` LONGTEXT NULL, `created_by` BIGINT UNSIGNED NOT NULL, `created_at` DATETIME NOT NULL, PRIMARY KEY (`id`), KEY `oa_request_custom_field_request` (`request_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    // A requester's own ad-hoc extras, separate from the workflow's own
    // defined fields (oa_fields/oa_request_values) - unstructured by
    // design, so they don't participate in resubmission/revision
    // tracking the way defined fields do; they're written once at
    // creation and just displayed afterwards.
    public function storeMany(int $requestId, array $labels, array $values, int $userId): void
    {
        $now = get_current_utc_time();
        $count = min(count($labels), count($values), 20);
        for ($i = 0; $i < $count; $i++) {
            $label = trim(clean_data((string) $labels[$i]));
            $value = trim(clean_data((string) $values[$i]));
            if ($label === '' && $value === '') continue;
            $this->db->table($this->p . 'oa_request_custom_fields')->insert([
                'request_id' => $requestId,
                'label' => $label !== '' ? $label : app_lang('operations_custom_field'),
                'value' => $value,
                'created_by' => $userId,
                'created_at' => $now,
            ]);
        }
    }

    public function list(int $requestId): array
    {
        return $this->db->table($this->p . 'oa_request_custom_fields')->where('request_id', $requestId)->orderBy('id')->get()->getResult();
    }
}
