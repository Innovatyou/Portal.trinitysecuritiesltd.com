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
                $this->run_idempotent(str_replace('{DB_PREFIX}', $this->prefix, $statement));
            }
        }
        $this->saveSetting('operations_approval_version', OPERATIONS_APPROVAL_VERSION);
        $this->saveSetting('operations_approval_active', '1');
    }

    /**
     * install()/upgrade() re-run every statement in install/sql unconditionally
     * every time (that's how upgrades apply new ALTER TABLEs to an
     * already-installed tenant), relying on CREATE TABLE IF NOT EXISTS and
     * INSERT IGNORE for idempotency. Plain ADD COLUMN has no portable
     * "if not exists" form on real MySQL (MariaDB's ADD COLUMN IF NOT
     * EXISTS isn't valid syntax here - see install/sql/002_complete_workflow.sql
     * history), so tolerate the specific "already exists" error codes a
     * second run of those statements is expected to hit, and only those.
     */
    private function run_idempotent(string $statement): void
    {
        try {
            $this->db->query($statement);
        } catch (\Throwable $e) {
            $code = (int) ($this->db->error()['code'] ?? 0);
            $already_exists_codes = [1060, 1061, 1050, 1826]; // duplicate column / key / table / FK constraint
            if (!in_array($code, $already_exists_codes, true)) {
                throw $e;
            }
        }
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
