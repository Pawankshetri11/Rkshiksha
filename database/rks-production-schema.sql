-- Rishikul Shiksha production DB schema/migration
-- Generated from local rkshiksha DB on 2026-06-12.
-- WordPress table prefix used here: wp_
-- If production uses a different $table_prefix, replace `wp_` before running.
-- This is schema-only. It does not delete or truncate data.

SET NAMES utf8mb4;
SET foreign_key_checks = 0;
CREATE TABLE IF NOT EXISTS `wp_rks_admissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `full_name` varchar(190) NOT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `dob` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `address` text DEFAULT NULL,
  `qualification` varchar(190) DEFAULT NULL,
  `course_id` bigint(20) unsigned DEFAULT NULL,
  `course_title` varchar(190) DEFAULT NULL,
  `fees_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_id` bigint(20) unsigned DEFAULT NULL,
  `status` enum('pending','payment_pending','payment_review','approved','rejected') NOT NULL DEFAULT 'pending',
  `rejection_note` text DEFAULT NULL,
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `meta` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `father_name` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `branch_id` (`branch_id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  KEY `email` (`email`),
  KEY `course_id` (`course_id`),
  KEY `payment_id` (`payment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
CREATE TABLE IF NOT EXISTS `wp_rks_attendance` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `course_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `date_marked` date NOT NULL,
  `status` enum('present','absent','late','excused') NOT NULL DEFAULT 'present',
  `marked_by` bigint(20) unsigned DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_course_date` (`user_id`,`course_id`,`date_marked`),
  KEY `branch_id` (`branch_id`),
  KEY `date_marked` (`date_marked`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
CREATE TABLE IF NOT EXISTS `wp_rks_branches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('main','franchise') NOT NULL DEFAULT 'franchise',
  `name` varchar(190) NOT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(190) DEFAULT NULL,
  `owner_user_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `franchise_id` bigint(20) unsigned DEFAULT NULL,
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `meta` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `status` (`status`),
  KEY `owner_user_id` (`owner_user_id`),
  KEY `franchise_id` (`franchise_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
CREATE TABLE IF NOT EXISTS `wp_rks_certificates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `unique_id` varchar(50) NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `course_id` bigint(20) unsigned NOT NULL,
  `enrollment_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `holder_name` varchar(190) NOT NULL,
  `course_title` varchar(190) NOT NULL,
  `issue_date` date NOT NULL,
  `pdf_path` varchar(255) DEFAULT NULL,
  `qr_path` varchar(255) DEFAULT NULL,
  `status` enum('valid','revoked') NOT NULL DEFAULT 'valid',
  `meta` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_id` (`unique_id`),
  KEY `user_id` (`user_id`),
  KEY `course_id` (`course_id`),
  KEY `branch_id` (`branch_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
CREATE TABLE IF NOT EXISTS `wp_rks_contact_leads` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `full_name` varchar(190) NOT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `subject` varchar(50) NOT NULL DEFAULT 'general',
  `message` text NOT NULL,
  `ip_address` varchar(64) DEFAULT NULL,
  `status` enum('new','contacted','closed') NOT NULL DEFAULT 'new',
  `assigned_to` bigint(20) unsigned DEFAULT NULL,
  `notes` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `subject` (`subject`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
CREATE TABLE IF NOT EXISTS `wp_rks_counselling_leads` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `full_name` varchar(190) NOT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `qualification` varchar(190) DEFAULT NULL,
  `stream` varchar(100) DEFAULT NULL,
  `preferred_colleges` text DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('new','contacted','converted','closed') NOT NULL DEFAULT 'new',
  `assigned_to` bigint(20) unsigned DEFAULT NULL,
  `notes` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `email` (`email`),
  KEY `assigned_to` (`assigned_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
CREATE TABLE IF NOT EXISTS `wp_rks_course_progress` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `enrollment_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `course_id` bigint(20) unsigned NOT NULL,
  `lesson_id` bigint(20) unsigned NOT NULL,
  `completed` tinyint(1) NOT NULL DEFAULT 0,
  `completed_at` datetime DEFAULT NULL,
  `seconds_watched` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_lesson` (`user_id`,`lesson_id`),
  KEY `enrollment_id` (`enrollment_id`),
  KEY `course_id` (`course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
CREATE TABLE IF NOT EXISTS `wp_rks_course_subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `course_id` bigint(20) unsigned NOT NULL,
  `enrollment_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `monthly_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_months` smallint(5) unsigned NOT NULL DEFAULT 0,
  `months_paid` smallint(5) unsigned NOT NULL DEFAULT 0,
  `next_due_date` date DEFAULT NULL,
  `status` enum('active','overdue','completed','cancelled') NOT NULL DEFAULT 'active',
  `started_at` datetime NOT NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_course` (`user_id`,`course_id`),
  KEY `status` (`status`),
  KEY `next_due` (`next_due_date`),
  KEY `branch_id` (`branch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
CREATE TABLE IF NOT EXISTS `wp_rks_enrollments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `course_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `progress` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `lessons_total` smallint(5) unsigned NOT NULL DEFAULT 0,
  `lessons_done` smallint(5) unsigned NOT NULL DEFAULT 0,
  `status` enum('active','completed','suspended','cancelled') NOT NULL DEFAULT 'active',
  `enrolled_at` datetime NOT NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  `certificate_id` bigint(20) unsigned DEFAULT NULL,
  `meta` longtext DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_course` (`user_id`,`course_id`),
  KEY `branch_id` (`branch_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
CREATE TABLE IF NOT EXISTS `wp_rks_franchises` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `applicant_name` varchar(190) NOT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `city` varchar(100) NOT NULL,
  `budget` varchar(50) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','onboarded') NOT NULL DEFAULT 'pending',
  `rejection_note` text DEFAULT NULL,
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `agreement_date` date DEFAULT NULL,
  `territory` varchar(190) DEFAULT NULL,
  `franchise_fee` decimal(12,2) DEFAULT NULL,
  `meta` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `email` (`email`),
  KEY `branch_id` (`branch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
CREATE TABLE IF NOT EXISTS `wp_rks_idcards` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `unique_id` varchar(50) NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `holder_type` enum('student','branch_admin','franchise_admin') NOT NULL DEFAULT 'student',
  `holder_name` varchar(190) NOT NULL,
  `holder_role` varchar(100) DEFAULT NULL,
  `photo_attach_id` bigint(20) unsigned DEFAULT NULL,
  `issue_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `pdf_path` varchar(255) DEFAULT NULL,
  `qr_path` varchar(255) DEFAULT NULL,
  `status` enum('active','expired','revoked') NOT NULL DEFAULT 'active',
  `meta` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_id` (`unique_id`),
  KEY `user_id` (`user_id`),
  KEY `branch_id` (`branch_id`),
  KEY `status` (`status`),
  KEY `holder_type` (`holder_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
CREATE TABLE IF NOT EXISTS `wp_rks_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `admission_id` bigint(20) unsigned DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `method` varchar(20) NOT NULL DEFAULT 'upi',
  `utr` varchar(50) DEFAULT NULL,
  `screenshot_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected','refunded') NOT NULL DEFAULT 'pending',
  `verifier_user_id` bigint(20) unsigned DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `rejection_note` text DEFAULT NULL,
  `meta` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `branch_id` (`branch_id`),
  KEY `user_id` (`user_id`),
  KEY `admission_id` (`admission_id`),
  KEY `status` (`status`),
  KEY `utr` (`utr`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
CREATE TABLE IF NOT EXISTS `wp_rks_quiz_answers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `attempt_id` bigint(20) unsigned NOT NULL,
  `question_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `given_answer` longtext DEFAULT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT 0,
  `points_awarded` smallint(5) unsigned NOT NULL DEFAULT 0,
  `answered_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `attempt_id` (`attempt_id`),
  KEY `question_id` (`question_id`),
  KEY `attempt_question` (`attempt_id`,`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
CREATE TABLE IF NOT EXISTS `wp_rks_quiz_assignments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `quiz_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `attempts_allowed` smallint(5) unsigned NOT NULL DEFAULT 1,
  `status` enum('assigned','passed') NOT NULL DEFAULT 'assigned',
  `last_attempt_id` bigint(20) unsigned DEFAULT NULL,
  `assigned_by` bigint(20) unsigned DEFAULT NULL,
  `assigned_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `meta` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_quiz` (`user_id`,`quiz_id`),
  KEY `quiz_id` (`quiz_id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  KEY `last_attempt_id` (`last_attempt_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
CREATE TABLE IF NOT EXISTS `wp_rks_quiz_attempts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `quiz_id` bigint(20) unsigned NOT NULL,
  `course_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `score` int(10) unsigned NOT NULL DEFAULT 0,
  `max_score` int(10) unsigned NOT NULL DEFAULT 0,
  `percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `status` enum('in_progress','submitted','passed','failed','abandoned') NOT NULL DEFAULT 'in_progress',
  `started_at` datetime NOT NULL DEFAULT current_timestamp(),
  `submitted_at` datetime DEFAULT NULL,
  `time_taken_sec` int(10) unsigned NOT NULL DEFAULT 0,
  `meta` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `quiz_id` (`quiz_id`),
  KEY `course_id` (`course_id`),
  KEY `status` (`status`),
  KEY `user_quiz` (`user_id`,`quiz_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
CREATE TABLE IF NOT EXISTS `wp_rks_quiz_questions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `quiz_id` bigint(20) unsigned NOT NULL,
  `question_text` text NOT NULL,
  `type` enum('mcq','tf','short') NOT NULL DEFAULT 'mcq',
  `options` longtext DEFAULT NULL,
  `correct_answer` longtext DEFAULT NULL,
  `points` smallint(5) unsigned NOT NULL DEFAULT 1,
  `display_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `explanation` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `quiz_id` (`quiz_id`),
  KEY `display_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
CREATE TABLE IF NOT EXISTS `wp_rks_quizzes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` bigint(20) unsigned NOT NULL,
  `lesson_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(190) NOT NULL,
  `description` text DEFAULT NULL,
  `time_limit_min` smallint(5) unsigned NOT NULL DEFAULT 0,
  `passing_score` tinyint(3) unsigned NOT NULL DEFAULT 60,
  `max_attempts` smallint(5) unsigned NOT NULL DEFAULT 3,
  `randomize` tinyint(1) NOT NULL DEFAULT 0,
  `show_answers` tinyint(1) NOT NULL DEFAULT 1,
  `is_final` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('draft','published') NOT NULL DEFAULT 'published',
  `meta` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_released` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `course_id` (`course_id`),
  KEY `lesson_id` (`lesson_id`),
  KEY `status` (`status`),
  KEY `is_final` (`is_final`),
  KEY `is_released` (`is_released`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
CREATE TABLE IF NOT EXISTS `wp_rks_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` longtext DEFAULT NULL,
  `autoload` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
CREATE TABLE IF NOT EXISTS `wp_rks_support_tickets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `subject` varchar(190) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `message` longtext NOT NULL,
  `priority` enum('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `status` enum('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
  `assignee_id` bigint(20) unsigned DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `meta` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `branch_id` (`branch_id`),
  KEY `status` (`status`),
  KEY `assignee_id` (`assignee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- Safe column/index upgrades for sites that already have older wp_rks_* tables.
-- These helpers check INFORMATION_SCHEMA before altering, so reruns are safe.
DROP PROCEDURE IF EXISTS rks_add_column_if_missing;
DROP PROCEDURE IF EXISTS rks_add_index_if_missing;
DROP PROCEDURE IF EXISTS rks_modify_column_if_exists;
DELIMITER $$
CREATE PROCEDURE rks_add_column_if_missing(IN p_table VARCHAR(128), IN p_column VARCHAR(128), IN p_definition TEXT)
BEGIN
	IF EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table)
	   AND NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND COLUMN_NAME = p_column) THEN
		SET @rks_sql = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN ', p_definition);
		PREPARE rks_stmt FROM @rks_sql;
		EXECUTE rks_stmt;
		DEALLOCATE PREPARE rks_stmt;
	END IF;
END$$
CREATE PROCEDURE rks_add_index_if_missing(IN p_table VARCHAR(128), IN p_index VARCHAR(128), IN p_definition TEXT)
BEGIN
	IF EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table)
	   AND NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND INDEX_NAME = p_index) THEN
		SET @rks_sql = CONCAT('ALTER TABLE `', p_table, '` ADD ', p_definition);
		PREPARE rks_stmt FROM @rks_sql;
		EXECUTE rks_stmt;
		DEALLOCATE PREPARE rks_stmt;
	END IF;
END$$
CREATE PROCEDURE rks_modify_column_if_exists(IN p_table VARCHAR(128), IN p_column VARCHAR(128), IN p_definition TEXT)
BEGIN
	IF EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND COLUMN_NAME = p_column) THEN
		SET @rks_sql = CONCAT('ALTER TABLE `', p_table, '` MODIFY COLUMN ', p_definition);
		PREPARE rks_stmt FROM @rks_sql;
		EXECUTE rks_stmt;
		DEALLOCATE PREPARE rks_stmt;
	END IF;
END$$
DELIMITER ;

CALL rks_add_column_if_missing('wp_rks_branches', 'owner_user_id', '`owner_user_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER `email`');
CALL rks_add_column_if_missing('wp_rks_branches', 'franchise_id', '`franchise_id` BIGINT UNSIGNED NULL AFTER `owner_user_id`');
CALL rks_add_column_if_missing('wp_rks_branches', 'meta', '`meta` LONGTEXT NULL AFTER `status`');
CALL rks_add_index_if_missing('wp_rks_branches', 'owner_user_id', 'KEY `owner_user_id` (`owner_user_id`)');
CALL rks_add_index_if_missing('wp_rks_branches', 'franchise_id', 'KEY `franchise_id` (`franchise_id`)');

CALL rks_add_column_if_missing('wp_rks_franchises', 'branch_id', '`branch_id` BIGINT UNSIGNED NULL AFTER `id`');
CALL rks_add_column_if_missing('wp_rks_franchises', 'budget', '`budget` VARCHAR(50) NULL AFTER `city`');
CALL rks_add_column_if_missing('wp_rks_franchises', 'message', '`message` TEXT NULL AFTER `budget`');
CALL rks_add_column_if_missing('wp_rks_franchises', 'rejection_note', '`rejection_note` TEXT NULL AFTER `status`');
CALL rks_add_column_if_missing('wp_rks_franchises', 'reviewed_by', '`reviewed_by` BIGINT UNSIGNED NULL AFTER `rejection_note`');
CALL rks_add_column_if_missing('wp_rks_franchises', 'reviewed_at', '`reviewed_at` DATETIME NULL AFTER `reviewed_by`');
CALL rks_add_column_if_missing('wp_rks_franchises', 'agreement_date', '`agreement_date` DATE NULL AFTER `reviewed_at`');
CALL rks_add_column_if_missing('wp_rks_franchises', 'territory', '`territory` VARCHAR(190) NULL AFTER `agreement_date`');
CALL rks_add_column_if_missing('wp_rks_franchises', 'franchise_fee', '`franchise_fee` DECIMAL(12,2) NULL AFTER `territory`');
CALL rks_add_column_if_missing('wp_rks_franchises', 'meta', '`meta` LONGTEXT NULL AFTER `franchise_fee`');
CALL rks_add_column_if_missing('wp_rks_franchises', 'updated_at', '`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`');
CALL rks_modify_column_if_exists('wp_rks_franchises', 'status', '`status` ENUM(''pending'',''approved'',''rejected'',''onboarded'') NOT NULL DEFAULT ''pending''');
CALL rks_add_index_if_missing('wp_rks_franchises', 'branch_id', 'KEY `branch_id` (`branch_id`)');

CALL rks_add_column_if_missing('wp_rks_admissions', 'father_name', '`father_name` VARCHAR(150) NULL AFTER `full_name`');
CALL rks_add_column_if_missing('wp_rks_admissions', 'payment_id', '`payment_id` BIGINT UNSIGNED NULL AFTER `fees_amount`');
CALL rks_add_column_if_missing('wp_rks_admissions', 'reviewed_by', '`reviewed_by` BIGINT UNSIGNED NULL AFTER `rejection_note`');
CALL rks_add_column_if_missing('wp_rks_admissions', 'reviewed_at', '`reviewed_at` DATETIME NULL AFTER `reviewed_by`');
CALL rks_add_column_if_missing('wp_rks_admissions', 'meta', '`meta` LONGTEXT NULL AFTER `reviewed_at`');
CALL rks_add_column_if_missing('wp_rks_admissions', 'updated_at', '`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`');
CALL rks_add_index_if_missing('wp_rks_admissions', 'payment_id', 'KEY `payment_id` (`payment_id`)');

CALL rks_add_column_if_missing('wp_rks_payments', 'admission_id', '`admission_id` BIGINT UNSIGNED NULL AFTER `user_id`');
CALL rks_add_column_if_missing('wp_rks_payments', 'screenshot_path', '`screenshot_path` VARCHAR(255) NULL AFTER `utr`');
CALL rks_add_column_if_missing('wp_rks_payments', 'verifier_user_id', '`verifier_user_id` BIGINT UNSIGNED NULL AFTER `status`');
CALL rks_add_column_if_missing('wp_rks_payments', 'verified_at', '`verified_at` DATETIME NULL AFTER `verifier_user_id`');
CALL rks_add_column_if_missing('wp_rks_payments', 'rejection_note', '`rejection_note` TEXT NULL AFTER `verified_at`');
CALL rks_add_column_if_missing('wp_rks_payments', 'meta', '`meta` LONGTEXT NULL AFTER `rejection_note`');
CALL rks_add_column_if_missing('wp_rks_payments', 'updated_at', '`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`');
CALL rks_add_index_if_missing('wp_rks_payments', 'admission_id', 'KEY `admission_id` (`admission_id`)');
CALL rks_add_index_if_missing('wp_rks_payments', 'utr', 'KEY `utr` (`utr`)');

CALL rks_add_column_if_missing('wp_rks_enrollments', 'lessons_total', '`lessons_total` SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER `progress`');
CALL rks_add_column_if_missing('wp_rks_enrollments', 'lessons_done', '`lessons_done` SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER `lessons_total`');
CALL rks_add_column_if_missing('wp_rks_enrollments', 'certificate_id', '`certificate_id` BIGINT UNSIGNED NULL AFTER `completed_at`');
CALL rks_add_column_if_missing('wp_rks_enrollments', 'meta', '`meta` LONGTEXT NULL AFTER `certificate_id`');
CALL rks_add_column_if_missing('wp_rks_enrollments', 'updated_at', '`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `meta`');

CALL rks_add_column_if_missing('wp_rks_certificates', 'enrollment_id', '`enrollment_id` BIGINT UNSIGNED NULL AFTER `course_id`');
CALL rks_add_column_if_missing('wp_rks_certificates', 'branch_id', '`branch_id` BIGINT UNSIGNED NOT NULL DEFAULT 1 AFTER `enrollment_id`');
CALL rks_add_column_if_missing('wp_rks_certificates', 'pdf_path', '`pdf_path` VARCHAR(255) NULL AFTER `issue_date`');
CALL rks_add_column_if_missing('wp_rks_certificates', 'qr_path', '`qr_path` VARCHAR(255) NULL AFTER `pdf_path`');
CALL rks_add_column_if_missing('wp_rks_certificates', 'status', '`status` ENUM(''valid'',''revoked'') NOT NULL DEFAULT ''valid'' AFTER `qr_path`');
CALL rks_add_column_if_missing('wp_rks_certificates', 'meta', '`meta` LONGTEXT NULL AFTER `status`');
CALL rks_add_column_if_missing('wp_rks_certificates', 'updated_at', '`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`');
CALL rks_add_index_if_missing('wp_rks_certificates', 'branch_id', 'KEY `branch_id` (`branch_id`)');
CALL rks_add_index_if_missing('wp_rks_certificates', 'status', 'KEY `status` (`status`)');

CALL rks_add_column_if_missing('wp_rks_idcards', 'holder_type', '`holder_type` ENUM(''student'',''branch_admin'',''franchise_admin'') NOT NULL DEFAULT ''student'' AFTER `branch_id`');
CALL rks_add_column_if_missing('wp_rks_idcards', 'holder_role', '`holder_role` VARCHAR(100) NULL AFTER `holder_name`');
CALL rks_add_column_if_missing('wp_rks_idcards', 'photo_attach_id', '`photo_attach_id` BIGINT UNSIGNED NULL AFTER `holder_role`');
CALL rks_add_column_if_missing('wp_rks_idcards', 'pdf_path', '`pdf_path` VARCHAR(255) NULL AFTER `expiry_date`');
CALL rks_add_column_if_missing('wp_rks_idcards', 'qr_path', '`qr_path` VARCHAR(255) NULL AFTER `pdf_path`');
CALL rks_add_column_if_missing('wp_rks_idcards', 'meta', '`meta` LONGTEXT NULL AFTER `status`');
CALL rks_add_column_if_missing('wp_rks_idcards', 'updated_at', '`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`');
CALL rks_add_index_if_missing('wp_rks_idcards', 'holder_type', 'KEY `holder_type` (`holder_type`)');

CALL rks_add_column_if_missing('wp_rks_quizzes', 'lesson_id', '`lesson_id` BIGINT UNSIGNED NULL AFTER `course_id`');
CALL rks_add_column_if_missing('wp_rks_quizzes', 'is_final', '`is_final` TINYINT(1) NOT NULL DEFAULT 0 AFTER `show_answers`');
CALL rks_add_column_if_missing('wp_rks_quizzes', 'is_released', '`is_released` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_final`');
CALL rks_add_column_if_missing('wp_rks_quizzes', 'meta', '`meta` LONGTEXT NULL AFTER `status`');
CALL rks_add_column_if_missing('wp_rks_quizzes', 'updated_at', '`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`');
CALL rks_add_index_if_missing('wp_rks_quizzes', 'lesson_id', 'KEY `lesson_id` (`lesson_id`)');
CALL rks_add_index_if_missing('wp_rks_quizzes', 'is_final', 'KEY `is_final` (`is_final`)');
CALL rks_add_index_if_missing('wp_rks_quizzes', 'is_released', 'KEY `is_released` (`is_released`)');

CALL rks_add_column_if_missing('wp_rks_quiz_questions', 'type', '`type` ENUM(''mcq'',''tf'',''short'') NOT NULL DEFAULT ''mcq'' AFTER `question_text`');
CALL rks_add_column_if_missing('wp_rks_quiz_questions', 'options', '`options` LONGTEXT NULL AFTER `type`');
CALL rks_add_column_if_missing('wp_rks_quiz_questions', 'correct_answer', '`correct_answer` LONGTEXT NULL AFTER `options`');
CALL rks_add_column_if_missing('wp_rks_quiz_questions', 'points', '`points` SMALLINT UNSIGNED NOT NULL DEFAULT 1 AFTER `correct_answer`');
CALL rks_add_column_if_missing('wp_rks_quiz_questions', 'display_order', '`display_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER `points`');
CALL rks_add_column_if_missing('wp_rks_quiz_questions', 'explanation', '`explanation` TEXT NULL AFTER `display_order`');
CALL rks_add_column_if_missing('wp_rks_quiz_questions', 'updated_at', '`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`');
CALL rks_add_index_if_missing('wp_rks_quiz_questions', 'display_order', 'KEY `display_order` (`display_order`)');

CALL rks_add_column_if_missing('wp_rks_quiz_attempts', 'branch_id', '`branch_id` BIGINT UNSIGNED NULL AFTER `course_id`');
CALL rks_add_column_if_missing('wp_rks_quiz_attempts', 'max_score', '`max_score` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `score`');
CALL rks_add_column_if_missing('wp_rks_quiz_attempts', 'percentage', '`percentage` DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER `max_score`');
CALL rks_add_column_if_missing('wp_rks_quiz_attempts', 'time_taken_sec', '`time_taken_sec` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `submitted_at`');
CALL rks_add_column_if_missing('wp_rks_quiz_attempts', 'meta', '`meta` LONGTEXT NULL AFTER `time_taken_sec`');
CALL rks_add_index_if_missing('wp_rks_quiz_attempts', 'user_quiz', 'KEY `user_quiz` (`user_id`,`quiz_id`)');

CALL rks_add_column_if_missing('wp_rks_quiz_answers', 'user_id', '`user_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER `question_id`');
CALL rks_add_column_if_missing('wp_rks_quiz_answers', 'points_awarded', '`points_awarded` SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER `is_correct`');

DROP PROCEDURE IF EXISTS rks_add_column_if_missing;
DROP PROCEDURE IF EXISTS rks_add_index_if_missing;
DROP PROCEDURE IF EXISTS rks_modify_column_if_exists;

SET foreign_key_checks = 1;

-- Prevent old plugin upgrade cleanup from running on production after files are uploaded.
-- WordPress wp_options has UNIQUE KEY option_name, so this is safe to rerun.
INSERT INTO `wp_options` (`option_name`, `option_value`, `autoload`) VALUES
('rks_db_version', '10', 'yes'),
('rks_exam_db_version', '2', 'yes')
ON DUPLICATE KEY UPDATE `option_value` = VALUES(`option_value`), `autoload` = VALUES(`autoload`);

-- End of Rishikul Shiksha production schema.
