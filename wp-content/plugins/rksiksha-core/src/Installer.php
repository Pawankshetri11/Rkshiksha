<?php
/**
 * Database schema installer — creates all 11 custom tables with proper indexes.
 *
 * Uses dbDelta() so it's safe to call repeatedly (idempotent).
 *
 * @package RKSiksha\Core
 */

declare( strict_types=1 );

namespace RKSiksha\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Installer {

	public static function install(): void {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$p               = $wpdb->prefix;

		$tables = [];

		/* ---------------------------------------------------------------- */
		/* 1. BRANCHES                                                      */
		/* ---------------------------------------------------------------- */
		$tables[] = "CREATE TABLE {$p}rks_branches (
			id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			type            ENUM('main','franchise') NOT NULL DEFAULT 'franchise',
			name            VARCHAR(190) NOT NULL,
			address         TEXT NULL,
			city            VARCHAR(100) NULL,
			state           VARCHAR(100) NULL,
			pincode         VARCHAR(10) NULL,
			phone           VARCHAR(30) NULL,
			email           VARCHAR(190) NULL,
			owner_user_id   BIGINT UNSIGNED NOT NULL DEFAULT 0,
			franchise_id    BIGINT UNSIGNED NULL,
			status          ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
			meta            LONGTEXT NULL,
			created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY type (type),
			KEY status (status),
			KEY owner_user_id (owner_user_id),
			KEY franchise_id (franchise_id)
		) {$charset_collate};";

		/* ---------------------------------------------------------------- */
		/* 2. FRANCHISES (applications + agreements)                        */
		/* ---------------------------------------------------------------- */
		$tables[] = "CREATE TABLE {$p}rks_franchises (
			id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			branch_id       BIGINT UNSIGNED NULL,
			applicant_name  VARCHAR(190) NOT NULL,
			email           VARCHAR(190) NOT NULL,
			phone           VARCHAR(30) NOT NULL,
			city            VARCHAR(100) NOT NULL,
			budget          VARCHAR(50) NULL,
			message         TEXT NULL,
			status          ENUM('pending','approved','rejected','onboarded') NOT NULL DEFAULT 'pending',
			rejection_note  TEXT NULL,
			reviewed_by     BIGINT UNSIGNED NULL,
			reviewed_at     DATETIME NULL,
			agreement_date  DATE NULL,
			territory       VARCHAR(190) NULL,
			franchise_fee   DECIMAL(12,2) NULL,
			meta            LONGTEXT NULL,
			created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY status (status),
			KEY email (email),
			KEY branch_id (branch_id)
		) {$charset_collate};";

		/* ---------------------------------------------------------------- */
		/* 3. ADMISSIONS                                                    */
		/* ---------------------------------------------------------------- */
		$tables[] = "CREATE TABLE {$p}rks_admissions (
			id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			branch_id       BIGINT UNSIGNED NOT NULL,
			user_id         BIGINT UNSIGNED NULL,
			full_name       VARCHAR(190) NOT NULL,
			father_name     VARCHAR(150) NULL,
			email           VARCHAR(190) NOT NULL,
			phone           VARCHAR(30) NOT NULL,
			dob             DATE NULL,
			gender          ENUM('male','female','other') NULL,
			address         TEXT NULL,
			qualification   VARCHAR(190) NULL,
			course_id       BIGINT UNSIGNED NULL,
			course_title    VARCHAR(190) NULL,
			fees_amount     DECIMAL(12,2) NOT NULL DEFAULT 0,
			payment_id      BIGINT UNSIGNED NULL,
			status          ENUM('pending','payment_pending','payment_review','approved','rejected') NOT NULL DEFAULT 'pending',
			rejection_note  TEXT NULL,
			reviewed_by     BIGINT UNSIGNED NULL,
			reviewed_at     DATETIME NULL,
			meta            LONGTEXT NULL,
			created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY branch_id (branch_id),
			KEY user_id (user_id),
			KEY status (status),
			KEY email (email),
			KEY course_id (course_id),
			KEY payment_id (payment_id)
		) {$charset_collate};";

		/* ---------------------------------------------------------------- */
		/* 4. PAYMENTS                                                      */
		/* ---------------------------------------------------------------- */
		$tables[] = "CREATE TABLE {$p}rks_payments (
			id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			branch_id           BIGINT UNSIGNED NOT NULL,
			user_id             BIGINT UNSIGNED NULL,
			admission_id        BIGINT UNSIGNED NULL,
			amount              DECIMAL(12,2) NOT NULL,
			method              VARCHAR(20) NOT NULL DEFAULT 'upi',
			utr                 VARCHAR(50) NULL,
			screenshot_path     VARCHAR(255) NULL,
			status              ENUM('pending','approved','rejected','refunded') NOT NULL DEFAULT 'pending',
			verifier_user_id    BIGINT UNSIGNED NULL,
			verified_at         DATETIME NULL,
			rejection_note      TEXT NULL,
			meta                LONGTEXT NULL,
			created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY branch_id (branch_id),
			KEY user_id (user_id),
			KEY admission_id (admission_id),
			KEY status (status),
			KEY utr (utr)
		) {$charset_collate};";

		/* ---------------------------------------------------------------- */
		/* 5. ENROLLMENTS                                                   */
		/* ---------------------------------------------------------------- */
		$tables[] = "CREATE TABLE {$p}rks_enrollments (
			id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id         BIGINT UNSIGNED NOT NULL,
			course_id       BIGINT UNSIGNED NOT NULL,
			branch_id       BIGINT UNSIGNED NOT NULL,
			progress        TINYINT UNSIGNED NOT NULL DEFAULT 0,
			lessons_total   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
			lessons_done    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
			status          ENUM('active','completed','suspended','cancelled') NOT NULL DEFAULT 'active',
			enrolled_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			completed_at    DATETIME NULL,
			certificate_id  BIGINT UNSIGNED NULL,
			meta            LONGTEXT NULL,
			updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY user_course (user_id, course_id),
			KEY branch_id (branch_id),
			KEY status (status)
		) {$charset_collate};";

		/* ---------------------------------------------------------------- */
		/* 5b. COURSE SUBSCRIPTIONS (monthly payment plans)                 */
		/* ---------------------------------------------------------------- */
		$tables[] = "CREATE TABLE {$p}rks_course_subscriptions (
			id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id         BIGINT UNSIGNED NOT NULL,
			course_id       BIGINT UNSIGNED NOT NULL,
			enrollment_id   BIGINT UNSIGNED NOT NULL,
			branch_id       BIGINT UNSIGNED NOT NULL,
			monthly_fee     DECIMAL(10,2) NOT NULL DEFAULT 0,
			total_months    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
			months_paid     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
			next_due_date   DATE NULL,
			status          ENUM('active','overdue','completed','cancelled') NOT NULL DEFAULT 'active',
			started_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			completed_at    DATETIME NULL,
			created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY user_course (user_id, course_id),
			KEY status (status),
			KEY next_due (next_due_date),
			KEY branch_id (branch_id)
		) {$charset_collate};";

		/* ---------------------------------------------------------------- */
		/* 6. COURSE PROGRESS (lesson-level)                                */
		/* ---------------------------------------------------------------- */
		$tables[] = "CREATE TABLE {$p}rks_course_progress (
			id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			enrollment_id   BIGINT UNSIGNED NOT NULL,
			user_id         BIGINT UNSIGNED NOT NULL,
			course_id       BIGINT UNSIGNED NOT NULL,
			lesson_id       BIGINT UNSIGNED NOT NULL,
			completed       TINYINT(1) NOT NULL DEFAULT 0,
			completed_at    DATETIME NULL,
			seconds_watched INT UNSIGNED NOT NULL DEFAULT 0,
			created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY user_lesson (user_id, lesson_id),
			KEY enrollment_id (enrollment_id),
			KEY course_id (course_id)
		) {$charset_collate};";

		/* ---------------------------------------------------------------- */
		/* 7. CERTIFICATES                                                  */
		/* ---------------------------------------------------------------- */
		$tables[] = "CREATE TABLE {$p}rks_certificates (
			id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			unique_id       VARCHAR(50) NOT NULL,
			user_id         BIGINT UNSIGNED NOT NULL,
			course_id       BIGINT UNSIGNED NOT NULL,
			enrollment_id   BIGINT UNSIGNED NULL,
			branch_id       BIGINT UNSIGNED NOT NULL,
			holder_name     VARCHAR(190) NOT NULL,
			course_title    VARCHAR(190) NOT NULL,
			issue_date      DATE NOT NULL,
			pdf_path        VARCHAR(255) NULL,
			qr_path         VARCHAR(255) NULL,
			status          ENUM('valid','revoked') NOT NULL DEFAULT 'valid',
			meta            LONGTEXT NULL,
			created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY unique_id (unique_id),
			KEY user_id (user_id),
			KEY course_id (course_id),
			KEY branch_id (branch_id),
			KEY status (status)
		) {$charset_collate};";

		/* ---------------------------------------------------------------- */
		/* 8. ID CARDS                                                      */
		/* ---------------------------------------------------------------- */
		$tables[] = "CREATE TABLE {$p}rks_idcards (
			id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			unique_id       VARCHAR(50) NOT NULL,
			user_id         BIGINT UNSIGNED NOT NULL,
			branch_id       BIGINT UNSIGNED NOT NULL,
			holder_type     ENUM('student','branch_admin','franchise_admin') NOT NULL DEFAULT 'student',
			holder_name     VARCHAR(190) NOT NULL,
			holder_role     VARCHAR(100) NULL,
			photo_attach_id BIGINT UNSIGNED NULL,
			issue_date      DATE NOT NULL,
			expiry_date     DATE NULL,
			pdf_path        VARCHAR(255) NULL,
			qr_path         VARCHAR(255) NULL,
			status          ENUM('active','expired','revoked') NOT NULL DEFAULT 'active',
			meta            LONGTEXT NULL,
			created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY unique_id (unique_id),
			KEY user_id (user_id),
			KEY branch_id (branch_id),
			KEY status (status),
			KEY holder_type (holder_type)
		) {$charset_collate};";

		/* ---------------------------------------------------------------- */
		/* 9. SUPPORT TICKETS                                               */
		/* ---------------------------------------------------------------- */
		$tables[] = "CREATE TABLE {$p}rks_support_tickets (
			id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id         BIGINT UNSIGNED NOT NULL,
			branch_id       BIGINT UNSIGNED NOT NULL,
			subject         VARCHAR(190) NOT NULL,
			category        VARCHAR(50) NULL,
			message         LONGTEXT NOT NULL,
			priority        ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
			status          ENUM('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
			assignee_id     BIGINT UNSIGNED NULL,
			resolved_at     DATETIME NULL,
			meta            LONGTEXT NULL,
			created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY branch_id (branch_id),
			KEY status (status),
			KEY assignee_id (assignee_id)
		) {$charset_collate};";

		/* ---------------------------------------------------------------- */
		/* 10. COUNSELLING LEADS                                            */
		/* ---------------------------------------------------------------- */
		$tables[] = "CREATE TABLE {$p}rks_counselling_leads (
			id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			full_name           VARCHAR(190) NOT NULL,
			email               VARCHAR(190) NOT NULL,
			phone               VARCHAR(30) NOT NULL,
			city                VARCHAR(100) NULL,
			qualification       VARCHAR(190) NULL,
			stream              VARCHAR(100) NULL,
			preferred_colleges  TEXT NULL,
			message             TEXT NULL,
			status              ENUM('new','contacted','converted','closed') NOT NULL DEFAULT 'new',
			assigned_to         BIGINT UNSIGNED NULL,
			notes               LONGTEXT NULL,
			created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY status (status),
			KEY email (email),
			KEY assigned_to (assigned_to)
		) {$charset_collate};";

		/* ---------------------------------------------------------------- */
		/* 11. CONTACT LEADS                                                */
		/* ---------------------------------------------------------------- */
		$tables[] = "CREATE TABLE {$p}rks_contact_leads (
			id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			full_name    VARCHAR(190) NOT NULL,
			email        VARCHAR(190) NOT NULL,
			phone        VARCHAR(30) NULL,
			subject      VARCHAR(50) NOT NULL DEFAULT 'general',
			message      TEXT NOT NULL,
			ip_address   VARCHAR(64) NULL,
			status       ENUM('new','contacted','closed') NOT NULL DEFAULT 'new',
			assigned_to  BIGINT UNSIGNED NULL,
			notes        LONGTEXT NULL,
			created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY status (status),
			KEY subject (subject),
			KEY email (email)
		) {$charset_collate};";

		/* ---------------------------------------------------------------- */
		/* 12. QUIZZES                                                      */
		/* ---------------------------------------------------------------- */
		$tables[] = "CREATE TABLE {$p}rks_quizzes (
			id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			course_id       BIGINT UNSIGNED NOT NULL,
			lesson_id       BIGINT UNSIGNED NULL,
			title           VARCHAR(190) NOT NULL,
			description     TEXT NULL,
			time_limit_min  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
			passing_score   TINYINT UNSIGNED NOT NULL DEFAULT 60,
			max_attempts    SMALLINT UNSIGNED NOT NULL DEFAULT 3,
			randomize       TINYINT(1) NOT NULL DEFAULT 0,
			show_answers    TINYINT(1) NOT NULL DEFAULT 1,
			is_final        TINYINT(1) NOT NULL DEFAULT 0,
			is_released     TINYINT(1) NOT NULL DEFAULT 0,
			status          ENUM('draft','published') NOT NULL DEFAULT 'published',
			meta            LONGTEXT NULL,
			created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY course_id (course_id),
			KEY lesson_id (lesson_id),
			KEY status (status),
			KEY is_final (is_final),
			KEY is_released (is_released)
		) {$charset_collate};";

		/* ---------------------------------------------------------------- */
		/* 13. QUIZ QUESTIONS                                               */
		/* ---------------------------------------------------------------- */
		$tables[] = "CREATE TABLE {$p}rks_quiz_questions (
			id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			quiz_id         BIGINT UNSIGNED NOT NULL,
			question_text   TEXT NOT NULL,
			type            ENUM('mcq','tf','short') NOT NULL DEFAULT 'mcq',
			options         LONGTEXT NULL,
			correct_answer  LONGTEXT NULL,
			points          SMALLINT UNSIGNED NOT NULL DEFAULT 1,
			display_order   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
			explanation     TEXT NULL,
			created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY quiz_id (quiz_id),
			KEY display_order (display_order)
		) {$charset_collate};";

		/* ---------------------------------------------------------------- */
		/* 14. QUIZ ATTEMPTS                                                */
		/* ---------------------------------------------------------------- */
		$tables[] = "CREATE TABLE {$p}rks_quiz_attempts (
			id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id         BIGINT UNSIGNED NOT NULL,
			quiz_id         BIGINT UNSIGNED NOT NULL,
			course_id       BIGINT UNSIGNED NOT NULL,
			branch_id       BIGINT UNSIGNED NULL,
			score           INT UNSIGNED NOT NULL DEFAULT 0,
			max_score       INT UNSIGNED NOT NULL DEFAULT 0,
			percentage      DECIMAL(5,2) NOT NULL DEFAULT 0,
			status          ENUM('in_progress','submitted','passed','failed','abandoned') NOT NULL DEFAULT 'in_progress',
			started_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			submitted_at    DATETIME NULL,
			time_taken_sec  INT UNSIGNED NOT NULL DEFAULT 0,
			meta            LONGTEXT NULL,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY quiz_id (quiz_id),
			KEY course_id (course_id),
			KEY status (status),
			KEY user_quiz (user_id, quiz_id)
		) {$charset_collate};";

		/* ---------------------------------------------------------------- */
		/* 15. QUIZ ANSWERS                                                 */
		/* ---------------------------------------------------------------- */
		$tables[] = "CREATE TABLE {$p}rks_quiz_answers (
			id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			attempt_id      BIGINT UNSIGNED NOT NULL,
			question_id     BIGINT UNSIGNED NOT NULL,
			user_id         BIGINT UNSIGNED NOT NULL,
			given_answer    LONGTEXT NULL,
			is_correct      TINYINT(1) NOT NULL DEFAULT 0,
			points_awarded  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
			answered_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY attempt_id (attempt_id),
			KEY question_id (question_id),
			KEY attempt_question (attempt_id, question_id)
		) {$charset_collate};";

		/* ---------------------------------------------------------------- */
		/* 11. SETTINGS (key-value fallback)                                */
		/* ---------------------------------------------------------------- */
		$tables[] = "CREATE TABLE {$p}rks_settings (
			id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			setting_key     VARCHAR(100) NOT NULL,
			setting_value   LONGTEXT NULL,
			autoload        TINYINT(1) NOT NULL DEFAULT 1,
			updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY setting_key (setting_key)
		) {$charset_collate};";

		foreach ( $tables as $sql ) {
			dbDelta( $sql );
		}
	}
}
