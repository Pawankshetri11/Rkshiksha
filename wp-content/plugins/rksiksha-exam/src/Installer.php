<?php
/**
 * Exam table installer.
 *
 * @package RKSiksha\Exam
 */

declare( strict_types=1 );

namespace RKSiksha\Exam;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Installer {

	public static function maybe_install(): void {
		$installed = (string) get_option( 'rks_exam_db_version', '0' );
		if ( version_compare( $installed, RKS_EXAM_DB_VERSION, '<' ) ) {
			self::install();
		}
	}

	public static function install(): void {
		if ( ! defined( 'ABSPATH' ) ) {
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$p               = $wpdb->prefix;

		$tables = [];

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

		$tables[] = "CREATE TABLE {$p}rks_quiz_assignments (
			id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			quiz_id             BIGINT UNSIGNED NOT NULL,
			user_id             BIGINT UNSIGNED NOT NULL,
			attempts_allowed    SMALLINT UNSIGNED NOT NULL DEFAULT 1,
			status              ENUM('assigned','passed') NOT NULL DEFAULT 'assigned',
			last_attempt_id     BIGINT UNSIGNED NULL,
			assigned_by         BIGINT UNSIGNED NULL,
			assigned_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			meta                LONGTEXT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY user_quiz (user_id, quiz_id),
			KEY quiz_id (quiz_id),
			KEY user_id (user_id),
			KEY status (status),
			KEY last_attempt_id (last_attempt_id)
		) {$charset_collate};";

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

		foreach ( $tables as $sql ) {
			dbDelta( $sql );
		}

		update_option( 'rks_exam_db_version', RKS_EXAM_DB_VERSION, true );
		Roles::install();
	}
}
