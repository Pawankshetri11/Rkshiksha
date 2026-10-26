<?php
/**
 * Issues certificates when a student passes a final exam.
 *
 * @package RKSiksha\Exam
 */

declare( strict_types=1 );

namespace RKSiksha\Exam\Modules;

use RKSiksha\Core\Modules\Certificate;
use RKSiksha\Core\Modules\Lms;
use RKSiksha\Core\Support\DB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CertificateIssuer {

	public function register(): void {
		add_action( 'rks_quiz_attempt_submitted', [ $this, 'maybe_issue_certificate' ], 20, 4 );
		add_action( 'init', [ $this, 'repair_passed_attempts' ], 30 );
	}

	/**
	 * Link/issue certificates for passed attempts created before the issuer fix.
	 */
	public function repair_passed_attempts(): void {
		if ( get_option( 'rks_exam_certificate_links_repaired_v1', false ) ) {
			return;
		}

		global $wpdb;
		$attempts = $wpdb->get_results(
			'SELECT id, user_id, quiz_id FROM ' . DB::table( 'quiz_attempts' ) . " WHERE status = 'passed' ORDER BY id ASC",
			ARRAY_A
		) ?: [];

		foreach ( $attempts as $attempt ) {
			$this->maybe_issue_certificate(
				(int) $attempt['id'],
				(int) $attempt['user_id'],
				(int) $attempt['quiz_id'],
				true
			);
		}

		update_option( 'rks_exam_certificate_links_repaired_v1', 1, false );
	}

	public function maybe_issue_certificate( int $attempt_id, int $user_id, int $quiz_id, bool $passed ): void {
		if ( ! $passed || ! class_exists( Certificate::class ) ) {
			return;
		}

		global $wpdb;

		$quiz = $wpdb->get_row( $wpdb->prepare(
			'SELECT * FROM ' . DB::table( 'quizzes' ) . " WHERE id = %d AND status = 'published' LIMIT 1",
			$quiz_id
		), ARRAY_A );
		if ( ! $quiz || ! $this->is_certificate_exam( $quiz ) ) {
			return;
		}

		$course_id = (int) $quiz['course_id'];
		$lms = new Lms();
		$enrollment = $lms->get_enrollment( $user_id, $course_id );
		if ( ! $enrollment ) {
			$branch_id = $this->default_branch_id_for_user( $user_id );
			$enrollment_id = $lms->enroll_user( $user_id, $course_id, $branch_id, 'active' );
			$enrollment = $enrollment_id > 0 ? $lms->get_enrollment( $user_id, $course_id ) : null;
		}
		if ( ! $enrollment ) {
			return;
		}

		$certificate_id = (int) $wpdb->get_var( $wpdb->prepare(
			'SELECT id FROM ' . DB::table( 'certificates' ) . " WHERE user_id = %d AND course_id = %d AND status = 'valid' LIMIT 1",
			$user_id,
			$course_id
		) );

		if ( $certificate_id <= 0 ) {
			$enrollment_id = (int) $enrollment['id'];

			if ( (string) $enrollment['status'] !== 'completed' ) {
				$wpdb->update(
					DB::table( 'enrollments' ),
					[
						'status'       => 'completed',
						'progress'     => 100,
						'completed_at' => current_time( 'mysql' ),
					],
					[ 'id' => $enrollment_id ],
					[ '%s', '%d', '%s' ],
					[ '%d' ]
				);
			}

			( new Certificate() )->on_course_completed( $user_id, $course_id, $enrollment_id );

			$certificate_id = (int) $wpdb->get_var( $wpdb->prepare(
				'SELECT id FROM ' . DB::table( 'certificates' ) . " WHERE user_id = %d AND course_id = %d AND status = 'valid' LIMIT 1",
				$user_id,
				$course_id
			) );
		}

		if ( $certificate_id > 0 ) {
			$this->link_attempt_to_certificate( $attempt_id, $certificate_id );
		}
	}

	/**
	 * Explicit final exams always qualify. For older courses where the admin did
	 * not tick "final", the course-level (non-lesson) exam qualifies only when
	 * that course has no other explicit final exam.
	 */
	private function is_certificate_exam( array $quiz ): bool {
		if ( ! empty( $quiz['is_final'] ) ) {
			return true;
		}
		if ( ! empty( $quiz['lesson_id'] ) ) {
			return false;
		}

		global $wpdb;
		$explicit_final = (int) $wpdb->get_var( $wpdb->prepare(
			'SELECT id FROM ' . DB::table( 'quizzes' ) . " WHERE course_id = %d AND is_final = 1 AND status = 'published' LIMIT 1",
			(int) $quiz['course_id']
		) );

		return $explicit_final <= 0;
	}

	private function link_attempt_to_certificate( int $attempt_id, int $certificate_id ): void {
		global $wpdb;

		$attempt = $wpdb->get_row( $wpdb->prepare(
			'SELECT meta FROM ' . DB::table( 'quiz_attempts' ) . ' WHERE id = %d',
			$attempt_id
		), ARRAY_A );
		if ( ! $attempt ) {
			return;
		}

		$meta = DB::decode_meta( (string) ( $attempt['meta'] ?? '' ) );
		if ( ! is_array( $meta ) ) {
			$meta = [];
		}
		$meta['certificate_id'] = $certificate_id;

		$wpdb->update(
			DB::table( 'quiz_attempts' ),
			[ 'meta' => DB::encode_meta( $meta ) ],
			[ 'id' => $attempt_id ],
			[ '%s' ],
			[ '%d' ]
		);
	}

	private function default_branch_id_for_user( int $user_id ): int {
		global $wpdb;
		$branch_id = (int) get_user_meta( $user_id, 'rks_branch_id', true );
		if ( $branch_id <= 0 ) {
			$branch_id = (int) $wpdb->get_var( "SELECT id FROM {$wpdb->prefix}rks_branches WHERE type='main' ORDER BY id ASC LIMIT 1" );
		}
		return $branch_id > 0 ? $branch_id : 1;
	}
}
