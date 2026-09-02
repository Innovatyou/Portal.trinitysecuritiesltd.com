<?php

namespace operations_approval\Libraries;

class Operations_installer
{
    private $db;
    private $prefix;

    public function __construct()
    {
        $this->db = db_connect('default');
        $this->prefix = $this->db->getPrefix();
    }

    public function install(): void
    {
        $files = glob(__DIR__ . '/../install/sql/*.sql');
        sort($files, SORT_NATURAL);
        foreach ($files as $file) {
            $sql = file_get_contents($file);
            foreach (array_filter(array_map('trim', preg_split('/;\s*(?:\r?\n|$)/', $sql))) as $statement) {
                $this->db->query(str_replace('{DB_PREFIX}', $this->prefix, $statement));
            }
        }
        $this->saveSetting('operations_approval_version', OPERATIONS_APPROVAL_VERSION);
        $this->saveSetting('operations_approval_active', '1');
    }

    public function upgrade(): void
    {
        $this->install();
    }

    public function setActive(bool $active): void
    {
        $this->saveSetting('operations_approval_active', $active ? '1' : '0');
    }

    public function markUninstalled(): void
    {
        $this->saveSetting('operations_approval_active', '0');
    }

    private function saveSetting(string $key, string $value): void
    {
        $table = $this->prefix . 'settings';
        $row = $this->db->table($table)->where('setting_name', $key)->get()->getRow();
        if ($row) {
            $this->db->table($table)->where('setting_name', $key)->update(['setting_value' => $value]);
        } else {
            $this->db->table($table)->insert(['setting_name' => $key, 'setting_value' => $value, 'type' => 'app']);
        }
    }
}
