CREATE TABLE IF NOT EXISTS `{DB_PREFIX}oa_prefix_sequences` (
 `prefix` VARCHAR(20) NOT NULL, `sequence_year` SMALLINT UNSIGNED NOT NULL, `last_number` BIGINT UNSIGNED NOT NULL DEFAULT 0,
 PRIMARY KEY (`prefix`,`sequence_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `{DB_PREFIX}oa_prefix_sequences` (`prefix`,`sequence_year`,`last_number`)
SELECT UPPER(w.`prefix`), s.`sequence_year`, MAX(s.`last_number`) FROM `{DB_PREFIX}oa_sequences` s
JOIN `{DB_PREFIX}oa_workflows` w ON w.`id` = s.`workflow_id`
GROUP BY UPPER(w.`prefix`), s.`sequence_year`;
