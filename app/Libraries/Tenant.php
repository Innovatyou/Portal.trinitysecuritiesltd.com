<?php

namespace App\Libraries;

/**
 * Holds the tenant resolved for the current request by Tenant_resolver.
 * Populated once in the pre_system event, read-only afterwards.
 */
class Tenant {

    public ?int $id = null;
    public ?string $name = null;
    public ?string $slug = null;
    public ?string $system_file_path = null;
    public bool $is_platform_host = false;

    public function set_from_row($row) {
        $this->id = (int) $row->id;
        $this->name = $row->name;
        $this->slug = $row->slug;
        $this->system_file_path = $row->system_file_path;
    }

    public function mark_platform_host() {
        $this->is_platform_host = true;
    }

    public function is_resolved(): bool {
        return $this->id !== null || $this->is_platform_host;
    }
}
