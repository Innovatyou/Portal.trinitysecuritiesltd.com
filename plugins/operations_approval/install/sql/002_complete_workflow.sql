CREATE TABLE IF NOT EXISTS `{DB_PREFIX}oa_settings` (
 `setting_key` VARCHAR(100) NOT NULL, `setting_value` LONGTEXT NULL, `updated_by` BIGINT UNSIGNED NULL,
 `updated_at` DATETIME NULL, PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{DB_PREFIX}oa_departments` (
 `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `name` VARCHAR(150) NOT NULL, `code` VARCHAR(50) NOT NULL,
 `head_user_id` BIGINT UNSIGNED NULL, `status` VARCHAR(20) NOT NULL DEFAULT 'active', `created_by` BIGINT UNSIGNED NOT NULL,
 `created_at` DATETIME NOT NULL, `updated_at` DATETIME NULL, `deleted` TINYINT(1) NOT NULL DEFAULT 0,
 PRIMARY KEY (`id`), UNIQUE KEY `oa_department_code` (`code`), KEY `oa_department_head` (`head_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{DB_PREFIX}oa_user_departments` (
 `user_id` BIGINT UNSIGNED NOT NULL, `department_id` BIGINT UNSIGNED NOT NULL, `manager_user_id` BIGINT UNSIGNED NULL,
 `is_head` TINYINT(1) NOT NULL DEFAULT 0, `updated_at` DATETIME NOT NULL, PRIMARY KEY (`user_id`),
 KEY `oa_department_members` (`department_id`,`is_head`), KEY `oa_user_manager` (`manager_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{DB_PREFIX}oa_approver_groups` (
 `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `name` VARCHAR(150) NOT NULL, `description` TEXT NULL,
 `status` VARCHAR(20) NOT NULL DEFAULT 'active', `created_by` BIGINT UNSIGNED NOT NULL, `created_at` DATETIME NOT NULL,
 `updated_at` DATETIME NULL, `deleted` TINYINT(1) NOT NULL DEFAULT 0, PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{DB_PREFIX}oa_approver_group_members` (
 `group_id` BIGINT UNSIGNED NOT NULL, `user_id` BIGINT UNSIGNED NOT NULL, PRIMARY KEY (`group_id`,`user_id`),
 KEY `oa_group_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{DB_PREFIX}oa_request_revisions` (
 `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `request_id` BIGINT UNSIGNED NOT NULL, `revision_no` INT UNSIGNED NOT NULL,
 `changed_by` BIGINT UNSIGNED NOT NULL, `reason` TEXT NULL, `changes_json` LONGTEXT NOT NULL, `created_at` DATETIME NOT NULL,
 PRIMARY KEY (`id`), UNIQUE KEY `oa_revision_unique` (`request_id`,`revision_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{DB_PREFIX}oa_conversations` (
 `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `request_id` BIGINT UNSIGNED NOT NULL, `stage_instance_id` BIGINT UNSIGNED NULL,
 `opened_by` BIGINT UNSIGNED NOT NULL, `assigned_to` BIGINT UNSIGNED NOT NULL, `status` VARCHAR(20) NOT NULL DEFAULT 'open',
 `question` TEXT NOT NULL, `response` TEXT NULL, `opened_at` DATETIME NOT NULL, `responded_at` DATETIME NULL,
 PRIMARY KEY (`id`), KEY `oa_conversation_request` (`request_id`,`status`), KEY `oa_conversation_assignee` (`assigned_to`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{DB_PREFIX}oa_request_relations` (
 `request_id` BIGINT UNSIGNED NOT NULL, `related_request_id` BIGINT UNSIGNED NOT NULL, `relation_type` VARCHAR(30) NOT NULL DEFAULT 'related',
 `created_by` BIGINT UNSIGNED NOT NULL, `created_at` DATETIME NOT NULL, PRIMARY KEY (`request_id`,`related_request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{DB_PREFIX}oa_reminders` (
 `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `request_id` BIGINT UNSIGNED NOT NULL, `stage_instance_id` BIGINT UNSIGNED NOT NULL,
 `assignment_id` BIGINT UNSIGNED NULL, `reminder_type` VARCHAR(30) NOT NULL, `recipient_id` BIGINT UNSIGNED NOT NULL,
 `scheduled_for` DATETIME NOT NULL, `sent_at` DATETIME NULL, `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
 `attempts` INT UNSIGNED NOT NULL DEFAULT 0, `last_error` TEXT NULL, `dedupe_key` VARCHAR(190) NOT NULL,
 PRIMARY KEY (`id`), UNIQUE KEY `oa_reminder_dedupe` (`dedupe_key`), KEY `oa_reminder_due` (`status`,`scheduled_for`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{DB_PREFIX}oa_email_templates` (
 `event` VARCHAR(60) NOT NULL, `enabled` TINYINT(1) NOT NULL DEFAULT 1, `subject` VARCHAR(255) NOT NULL,
 `body` LONGTEXT NOT NULL, `updated_by` BIGINT UNSIGNED NULL, `updated_at` DATETIME NULL, PRIMARY KEY (`event`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `{DB_PREFIX}oa_requests` ADD COLUMN `return_stage_instance_id` BIGINT UNSIGNED NULL AFTER `current_stage_instance_id`;
ALTER TABLE `{DB_PREFIX}oa_requests` ADD COLUMN `return_strategy` VARCHAR(30) NULL AFTER `return_stage_instance_id`;
ALTER TABLE `{DB_PREFIX}oa_requests` ADD COLUMN `test_mode` TINYINT(1) NOT NULL DEFAULT 0 AFTER `priority`;
ALTER TABLE `{DB_PREFIX}oa_requests` ADD COLUMN `cancelled_at` DATETIME NULL AFTER `completed_at`;
ALTER TABLE `{DB_PREFIX}oa_attachments` ADD COLUMN `storage_path` VARCHAR(500) NOT NULL DEFAULT '' AFTER `storage_name`;
ALTER TABLE `{DB_PREFIX}oa_attachments` ADD COLUMN `context` VARCHAR(30) NOT NULL DEFAULT 'request' AFTER `comment_id`;
ALTER TABLE `{DB_PREFIX}oa_attachments` ADD COLUMN `replaced_attachment_id` BIGINT UNSIGNED NULL AFTER `version_no`;
ALTER TABLE `{DB_PREFIX}oa_stage_instances` ADD COLUMN `reminder_sent_at` DATETIME NULL AFTER `due_at`;
ALTER TABLE `{DB_PREFIX}oa_stage_instances` ADD COLUMN `escalated_at` DATETIME NULL AFTER `reminder_sent_at`;
ALTER TABLE `{DB_PREFIX}oa_stage_instances` ADD COLUMN `cycle_no` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `status`;

INSERT IGNORE INTO `{DB_PREFIX}oa_settings` (`setting_key`,`setting_value`) VALUES
 ('allowed_extensions','pdf,doc,docx,xls,xlsx,png,jpg,jpeg,txt,csv'),('max_file_size_mb','10'),('default_page_size','25'),
 ('mobile_app_enabled','1'),('mobile_module_operations','1'),('mobile_module_projects','1'),('mobile_module_contracts','1'),('mobile_module_proposals','1'),('mobile_module_estimates','1'),('mobile_module_invoices','1'),('mobile_module_payments','1'),('mobile_module_tickets','1'),('email_enabled','1'),('in_app_enabled','1'),('reminder_hours','24'),('escalation_hours','72'),('retention_days','0');

INSERT IGNORE INTO `{DB_PREFIX}oa_email_templates` (`event`,`enabled`,`subject`,`body`) VALUES
 ('request_submitted',1,'Request {REQUEST_NUMBER} submitted','{REQUESTER_NAME} submitted {REQUEST_TITLE}. Open: {ACTION_URL}'),
 ('approval_assigned',1,'Approval required: {REQUEST_NUMBER}','{CURRENT_STAGE} requires your action. Open: {ACTION_URL}'),
 ('request_approved',1,'Request {REQUEST_NUMBER} approved','The request has been approved at {CURRENT_STAGE}. Open: {ACTION_URL}'),
 ('request_rejected',1,'Request {REQUEST_NUMBER} rejected','Reason: {COMMENT}. Open: {ACTION_URL}'),
 ('request_returned',1,'Request {REQUEST_NUMBER} returned','Correction is required: {COMMENT}. Open: {ACTION_URL}'),
 ('information_requested',1,'Information requested for {REQUEST_NUMBER}','Question: {COMMENT}. Open: {ACTION_URL}'),
 ('request_resubmitted',1,'Request {REQUEST_NUMBER} resubmitted','The corrected request has been resubmitted. Open: {ACTION_URL}'),
 ('request_completed',1,'Request {REQUEST_NUMBER} completed','The approval workflow is complete. Open: {ACTION_URL}'),
 ('approval_reminder',1,'Reminder: {REQUEST_NUMBER} awaits approval','{CURRENT_STAGE} is waiting for your action. Open: {ACTION_URL}'),
 ('sla_breached',1,'SLA breached: {REQUEST_NUMBER}','{CURRENT_STAGE} is overdue. Open: {ACTION_URL}');

INSERT INTO `{DB_PREFIX}notification_settings` (`event`,`category`,`enable_email`,`enable_web`,`enable_slack`,`notify_to_team`,`notify_to_team_members`,`notify_to_terms`,`sort`,`deleted`)
SELECT `event_name`,'operations',1,1,0,'','','operations_recipients',200,0 FROM (
 SELECT 'operations_request_submitted' event_name UNION SELECT 'operations_approval_assigned' UNION SELECT 'operations_request_approved'
 UNION SELECT 'operations_request_rejected' UNION SELECT 'operations_request_returned' UNION SELECT 'operations_information_requested'
 UNION SELECT 'operations_request_resubmitted' UNION SELECT 'operations_request_completed' UNION SELECT 'operations_approval_reminder'
 UNION SELECT 'operations_sla_breached' UNION SELECT 'operations_approval_delegated'
) events WHERE NOT EXISTS (SELECT 1 FROM `{DB_PREFIX}notification_settings` ns WHERE ns.event=events.event_name);
