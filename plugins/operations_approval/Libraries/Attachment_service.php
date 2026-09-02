<?php

namespace operations_approval\Libraries;

class Attachment_service
{
    private $db;
    private $p;

    public function __construct()
    {
        $this->db = db_connect('default');
        $this->p = $this->db->getPrefix();
    }

    public function store(int $requestId, int $userId, string $tempName, ?int $stageId = null, string $context = 'request'): int
    {
        $original = basename($tempName);
        if ($original !== $tempName || preg_match('/\.(php\d*|phtml|phar|cgi|pl|exe|sh|bat|cmd|com)(\.|$)/i', $original)) {
            throw new \DomainException('Unsafe attachment name.');
        }
        $settings = $this->settings();
        $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        if (!in_array($extension, $settings['extensions'], true)) throw new \DomainException('This attachment type is not allowed.');
        $tempPath = rtrim(get_setting('temp_file_path'), '/\\') . '/' . $original;
        if (!is_file($tempPath)) throw new \DomainException('Temporary attachment was not found.');
        $size = filesize($tempPath);
        if ($size <= 0 || $size > $settings['max_bytes']) throw new \DomainException('The attachment exceeds the configured size limit.');
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tempPath) ?: 'application/octet-stream';
        if ($this->isExecutableMime($mime)) throw new \DomainException('Executable attachments are not allowed.');
        $storagePath = rtrim(get_setting('timeline_file_path'), '/\\') . '/operations_approval/' . $requestId . '/';
        $stored = move_temp_file($original, $storagePath, 'operations_approval', null, bin2hex(random_bytes(16)) . '.' . $extension);
        $storedName = is_array($stored) ? get_array_value($stored, 'file_name') : ($stored->file_name ?? '');
        if (!$storedName) throw new \RuntimeException('Attachment storage failed.');
        $fullPath = rtrim($storagePath, '/\\') . '/' . $storedName;
        $this->db->table($this->p . 'oa_attachments')->insert([
            'request_id' => $requestId, 'stage_instance_id' => $stageId, 'context' => $context, 'uploaded_by' => $userId,
            'storage_name' => $storedName, 'storage_path' => $storagePath, 'original_name' => $original,
            'mime_type' => $mime, 'size_bytes' => $size, 'sha256' => hash_file('sha256', $fullPath), 'created_at' => get_current_utc_time()
        ]);
        return (int) $this->db->insertID();
    }

    public function get(int $id): ?object
    {
        return $this->db->table($this->p . 'oa_attachments')->where(['id' => $id, 'deleted_at' => null])->get()->getRow();
    }

    private function settings(): array
    {
        $rows = $this->db->table($this->p . 'oa_settings')->whereIn('setting_key', ['allowed_extensions', 'max_file_size_mb'])->get()->getResult();
        $values = [];
        foreach ($rows as $row) $values[$row->setting_key] = $row->setting_value;
        return ['extensions' => array_filter(array_map('trim', explode(',', strtolower($values['allowed_extensions'] ?? 'pdf,doc,docx,xls,xlsx,png,jpg,jpeg,txt,csv')))), 'max_bytes' => max(1, (int) ($values['max_file_size_mb'] ?? 10)) * 1024 * 1024];
    }

    private function isExecutableMime(string $mime): bool
    {
        return preg_match('#(x-httpd-php|x-php|x-executable|x-dosexec|x-sh|javascript|html)#i', $mime) === 1;
    }
}
