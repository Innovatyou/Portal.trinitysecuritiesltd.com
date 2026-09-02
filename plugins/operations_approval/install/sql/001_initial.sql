CREATE TABLE IF NOT EXISTS `{DB_PREFIX}oa_workflows` (
 `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `name` VARCHAR(150) NOT NULL, `code` VARCHAR(50) NOT NULL,
 `description` TEXT NULL, `prefix` VARCHAR(20) NOT NULL DEFAULT 'REQ', `department_id` BIGINT UNSIGNED NULL,
 `settings_json` LONGTEXT NULL, `status` ENUM('draft','active','inactive','archived') NOT NULL DEFAULT 'draft',
 `current_version_id` BIGINT UNSIGNED NULL, `created_by` BIGINT UNSIGNED NOT NULL, `created_at` DATETIME NOT NULL,
 `updated_at` DATETIME NULL, `deleted` TINYINT(1) NOT NULL DEFAULT 0, PRIMARY KEY (`id`),
 UNIQUE KEY `oa_workflow_code` (`code`), KEY `oa_workflow_status` (`status`,`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{DB_PREFIX}oa_workflow_versions` (
 `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `workflow_id` BIGINT UNSIGNED NOT NULL, `version_no` INT UNSIGNED NOT NULL,
 `definition_json` LONGTEXT NOT NULL, `definition_hash` CHAR(64) NOT NULL, `status` ENUM('draft','published','retired') NOT NULL DEFAULT 'draft',
 `published_by` BIGINT UNSIGNED NULL, `published_at` DATETIME NULL, `created_by` BIGINT UNSIGNED NOT NULL, `created_at` DATETIME NOT NULL,
 PRIMARY KEY (`id`), UNIQUE KEY `oa_version_unique` (`workflow_id`,`version_no`), KEY `oa_version_status` (`workflow_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{DB_PREFIX}oa_fields` (
 `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `version_id` BIGINT UNSIGNED NOT NULL, `field_key` VARCHAR(80) NOT NULL,
 `label` VARCHAR(180) NOT NULL, `field_type` VARCHAR(30) NOT NULL, `position` INT NOT NULL DEFAULT 0,
 `config_json` LONGTEXT NULL, `is_required` TINYINT(1) NOT NULL DEFAULT 0, PRIMARY KEY (`id`),
 UNIQUE KEY `oa_field_key` (`version_id`,`field_key`), KEY `oa_field_order` (`version_id`,`position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{DB_PREFIX}oa_stages` (
 `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `version_id` BIGINT UNSIGNED NOT NULL, `name` VARCHAR(180) NOT NULL,
 `stage_type` VARCHAR(30) NOT NULL DEFAULT 'approval', `position` INT NOT NULL, `approver_type` VARCHAR(30) NOT NULL,
 `approver_config_json` LONGTEXT NOT NULL, `approval_rule` VARCHAR(30) NOT NULL DEFAULT 'any', `required_count` INT NOT NULL DEFAULT 1,
 `condition_json` LONGTEXT NULL, `settings_json` LONGTEXT NULL, `sla_minutes` INT UNSIGNED NULL, PRIMARY KEY (`id`),
 UNIQUE KEY `oa_stage_position` (`version_id`,`position`), KEY `oa_stage_version` (`version_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{DB_PREFIX}oa_sequences` (
 `workflow_id` BIGINT UNSIGNED NOT NULL, `sequence_year` SMALLINT UNSIGNED NOT NULL, `last_number` BIGINT UNSIGNED NOT NULL DEFAULT 0,
 PRIMARY KEY (`workflow_id`,`sequence_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{DB_PREFIX}oa_requests` (
 `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `request_no` VARCHAR(60) NULL, `workflow_id` BIGINT UNSIGNED NOT NULL,
 `version_id` BIGINT UNSIGNED NULL, `requester_id` BIGINT UNSIGNED NOT NULL, `department_id` BIGINT UNSIGNED NULL,
 `title` VARCHAR(255) NOT NULL, `priority` VARCHAR(20) NOT NULL DEFAULT 'normal', `status` VARCHAR(30) NOT NULL DEFAULT 'draft',
 `current_stage_instance_id` BIGINT UNSIGNED NULL, `revision_no` INT UNSIGNED NOT NULL DEFAULT 1, `lock_version` INT UNSIGNED NOT NULL DEFAULT 0,
 `submitted_at` DATETIME NULL, `completed_at` DATETIME NULL, `created_at` DATETIME NOT NULL, `updated_at` DATETIME NULL,
 `archived_at` DATETIME NULL, `deleted` TINYINT(1) NOT NULL DEFAULT 0, PRIMARY KEY (`id`),
 UNIQUE KEY `oa_request_no` (`request_no`), KEY `oa_request_list` (`status`,`workflow_id`,`created_at`),
 KEY `oa_request_owner` (`requester_id`,`status`), KEY `oa_request_stage` (`current_stage_instance_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{DB_PREFIX}oa_request_values` (
 `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `request_id` BIGINT UNSIGNED NOT NULL, `field_id` BIGINT UNSIGNED NOT NULL,
 `field_key` VARCHAR(80) NOT NULL, `value_text` LONGTEXT NULL, `value_json` LONGTEXT NULL, `revision_no` INT UNSIGNED NOT NULL DEFAULT 1,
 `created_at` DATETIME NOT NULL, PRIMARY KEY (`id`), UNIQUE KEY `oa_request_field_revision` (`request_id`,`field_key`,`revision_no`),
 KEY `oa_request_values` (`request_id`,`revision_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{DB_PREFIX}oa_stage_instances` (
 `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `request_id` BIGINT UNSIGNED NOT NULL, `stage_id` BIGINT UNSIGNED NOT NULL,
 `position` INT NOT NULL, `name_snapshot` VARCHAR(180) NOT NULL, `type_snapshot` VARCHAR(30) NOT NULL, `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
 `cycle_no` INT UNSIGNED NOT NULL DEFAULT 1,
 `rule_snapshot` VARCHAR(30) NOT NULL, `required_count` INT NOT NULL DEFAULT 1, `condition_result_json` LONGTEXT NULL,
 `activated_at` DATETIME NULL, `due_at` DATETIME NULL, `completed_at` DATETIME NULL, `lock_version` INT UNSIGNED NOT NULL DEFAULT 0,
 PRIMARY KEY (`id`), UNIQUE KEY `oa_instance_stage` (`request_id`,`stage_id`,`cycle_no`), KEY `oa_instance_active` (`status`,`due_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{DB_PREFIX}oa_assignments` (
 `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `stage_instance_id` BIGINT UNSIGNED NOT NULL, `user_id` BIGINT UNSIGNED NOT NULL,
 `original_user_id` BIGINT UNSIGNED NULL, `source_snapshot` VARCHAR(100) NOT NULL, `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
 `assigned_at` DATETIME NOT NULL, `acted_at` DATETIME NULL, PRIMARY KEY (`id`),
 UNIQUE KEY `oa_assignment_user` (`stage_instance_id`,`user_id`), KEY `oa_assignment_inbox` (`user_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{DB_PREFIX}oa_decisions` (
 `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `request_id` BIGINT UNSIGNED NOT NULL, `stage_instance_id` BIGINT UNSIGNED NOT NULL,
 `assignment_id` BIGINT UNSIGNED NOT NULL, `actor_id` BIGINT UNSIGNED NOT NULL, `on_behalf_of_id` BIGINT UNSIGNED NULL,
 `decision` VARCHAR(30) NOT NULL, `comment` TEXT NULL, `actor_name_snapshot` VARCHAR(180) NOT NULL, `created_at` DATETIME NOT NULL,
 `ip_address` VARCHAR(45) NULL, PRIMARY KEY (`id`), UNIQUE KEY `oa_one_decision` (`assignment_id`),
 KEY `oa_decision_timeline` (`request_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{DB_PREFIX}oa_comments` (
 `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `request_id` BIGINT UNSIGNED NOT NULL, `stage_instance_id` BIGINT UNSIGNED NULL,
 `user_id` BIGINT UNSIGNED NOT NULL, `user_name_snapshot` VARCHAR(180) NOT NULL, `comment` TEXT NOT NULL,
 `comment_type` VARCHAR(30) NOT NULL DEFAULT 'general', `visibility` VARCHAR(30) NOT NULL DEFAULT 'workflow', `created_at` DATETIME NOT NULL,
 PRIMARY KEY (`id`), KEY `oa_comment_timeline` (`request_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{DB_PREFIX}oa_attachments` (
 `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `request_id` BIGINT UNSIGNED NOT NULL, `stage_instance_id` BIGINT UNSIGNED NULL,
 `comment_id` BIGINT UNSIGNED NULL, `uploaded_by` BIGINT UNSIGNED NOT NULL, `storage_name` VARCHAR(255) NOT NULL,
 `original_name` VARCHAR(255) NOT NULL, `mime_type` VARCHAR(120) NULL, `size_bytes` BIGINT UNSIGNED NOT NULL,
 `sha256` CHAR(64) NOT NULL, `version_no` INT UNSIGNED NOT NULL DEFAULT 1, `created_at` DATETIME NOT NULL, `deleted_at` DATETIME NULL,
 PRIMARY KEY (`id`), KEY `oa_attachment_request` (`request_id`,`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{DB_PREFIX}oa_audit` (
 `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `request_id` BIGINT UNSIGNED NULL, `stage_instance_id` BIGINT UNSIGNED NULL,
 `actor_id` BIGINT UNSIGNED NULL, `actor_name_snapshot` VARCHAR(180) NULL, `action` VARCHAR(60) NOT NULL,
 `old_value_json` LONGTEXT NULL, `new_value_json` LONGTEXT NULL, `metadata_json` LONGTEXT NULL,
 `ip_address` VARCHAR(45) NULL, `created_at` DATETIME(6) NOT NULL, `integrity_hash` CHAR(64) NOT NULL,
 PRIMARY KEY (`id`), KEY `oa_audit_request` (`request_id`,`id`), KEY `oa_audit_action` (`action`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{DB_PREFIX}oa_delegations` (
 `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `user_id` BIGINT UNSIGNED NOT NULL, `delegate_id` BIGINT UNSIGNED NOT NULL,
 `workflow_id` BIGINT UNSIGNED NULL, `starts_at` DATETIME NOT NULL, `ends_at` DATETIME NOT NULL, `reason` TEXT NOT NULL,
 `created_by` BIGINT UNSIGNED NOT NULL, `created_at` DATETIME NOT NULL, `revoked_at` DATETIME NULL, PRIMARY KEY (`id`),
 KEY `oa_delegation_lookup` (`user_id`,`starts_at`,`ends_at`,`revoked_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{DB_PREFIX}oa_notification_log` (
 `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `request_id` BIGINT UNSIGNED NOT NULL, `event` VARCHAR(60) NOT NULL,
 `recipient_id` BIGINT UNSIGNED NOT NULL, `dedupe_key` VARCHAR(190) NOT NULL, `channel` VARCHAR(20) NOT NULL,
 `status` VARCHAR(20) NOT NULL, `created_at` DATETIME NOT NULL, PRIMARY KEY (`id`), UNIQUE KEY `oa_notification_dedupe` (`dedupe_key`),
 KEY `oa_notification_request` (`request_id`,`event`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
