-- Landlord (control-plane) database schema.
-- Separate from install/database.sql, which seeds ONE tenant's application
-- database. This schema lives in its own dedicated database and only ever
-- maps domains to tenants and stores platform-operator accounts.

CREATE TABLE IF NOT EXISTS `tenants` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `slug` VARCHAR(100) NOT NULL,
  `status` ENUM('provisioning','active','suspended') NOT NULL DEFAULT 'provisioning',
  `db_hostname` VARCHAR(191) NOT NULL,
  `db_port` INT UNSIGNED NOT NULL DEFAULT 3306,
  `db_database` VARCHAR(191) NOT NULL,
  `db_username` VARCHAR(191) NOT NULL,
  `db_password_encrypted` TEXT NOT NULL,
  `db_prefix` VARCHAR(21) NOT NULL DEFAULT 'tsl_',
  `system_file_path` VARCHAR(255) NOT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tenant_domains` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` INT UNSIGNED NOT NULL,
  `domain` VARCHAR(191) NOT NULL,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  `ssl_status` ENUM('pending','dns_pending','issued','failed') NOT NULL DEFAULT 'pending',
  `verified_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `domain` (`domain`),
  KEY `tenant_id` (`tenant_id`),
  CONSTRAINT `tenant_domains_tenant_id_fk` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `platform_admins` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(191) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `first_name` VARCHAR(100) NULL,
  `last_name` VARCHAR(100) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_login_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Session storage for the platform admin area. Deliberately separate from
-- every tenant's own ci_sessions table (Tenant_resolver points Session's
-- DBGroup at 'landlord' on the reserved platform host) so a platform
-- operator's session can never end up living inside a tenant's database.
CREATE TABLE IF NOT EXISTS `ci_sessions` (
  `id` VARCHAR(128) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data` BLOB NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ci_sessions_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tenant_provisioning_jobs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` INT UNSIGNED NOT NULL,
  `step` VARCHAR(50) NOT NULL,
  `status` ENUM('pending','running','done','failed') NOT NULL DEFAULT 'pending',
  `error_message` TEXT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `tenant_id` (`tenant_id`),
  CONSTRAINT `tenant_provisioning_jobs_tenant_id_fk` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A "Group MD" - one login that can be granted admin access into several
-- companies (each its own separate database) and switch between them. Kept
-- entirely separate from platform_admins: a platform admin manages the list
-- of companies on this SaaS, a group admin does real business/admin work
-- inside specific companies they've been granted. See app/Controllers/
-- Group_auth.php, Group_portal.php, Group_sso.php, Platform_group_admins.php.
CREATE TABLE IF NOT EXISTS `group_admins` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(191) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_login_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Which companies a group admin can switch into, and which tenant-side
-- users.id they land as there. created_by_grant distinguishes an account
-- this grant created (safe to deactivate on revoke) from one that already
-- existed under a matching email (revoke must only remove the switching
-- privilege, never touch someone else's real account).
CREATE TABLE IF NOT EXISTS `group_admin_access` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `group_admin_id` INT UNSIGNED NOT NULL,
  `tenant_id` INT UNSIGNED NOT NULL,
  `tenant_user_id` INT UNSIGNED NOT NULL,
  `created_by_grant` TINYINT(1) NOT NULL DEFAULT 1,
  `granted_by` INT UNSIGNED NULL,
  `granted_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `group_admin_tenant` (`group_admin_id`,`tenant_id`),
  CONSTRAINT `gaa_group_admin_fk` FOREIGN KEY (`group_admin_id`) REFERENCES `group_admins` (`id`) ON DELETE CASCADE,
  CONSTRAINT `gaa_tenant_fk` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Short-lived, single-use, tenant-bound handoff tokens: Group_portal mints
-- one when a group admin picks a company, Group_sso consumes it on that
-- company's own domain to establish a normal session there. Never reused
-- across companies or requests - see Group_sso::consume().
CREATE TABLE IF NOT EXISTS `group_admin_sso_tokens` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `token` VARCHAR(191) NOT NULL,
  `group_admin_id` INT UNSIGNED NOT NULL,
  `tenant_id` INT UNSIGNED NOT NULL,
  `tenant_user_id` INT UNSIGNED NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
