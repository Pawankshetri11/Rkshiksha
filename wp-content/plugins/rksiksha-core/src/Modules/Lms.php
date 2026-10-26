<?php
/**
 * LMS module — enrollment + lesson progress tracking + completion trigger.
 *
 * @package RKSiksha\Core
 */

declare( strict_types=1 );

namespace RKSiksha\Core\Modules;

use RKSiksha\Core\Support\DB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Lms {

	public function register(): void {
		// Recount progress when lesson completion changes
		add_action( 'rks_lesson_completed', [ $this, 'recompute_enrollment_progress' ], 10, 2 );

		// On quiz pass, re-evaluate completion (in case rule is quiz-only or both)
		add_action( 'rks_quiz_attempt_submitted', [ $this, 'on_quiz_submitted' ], 10, 4 );

		// Free-course self-enroll endpoint (logged-in students only)
		add_action( 'admin_post_rks_free_enroll', [ $this, 'handle_free_enroll' ] );
	}

	/**
	 * Logged-in student self-enrolls into a free course.
	 * Validates: nonce, course is_paid=0, not already enrolled.
	 */
	public function handle_free_enroll(): void {
		check_admin_referer( 'rks_free_enroll' );
		if ( ! is_user_logged_in() ) wp_safe_redirect( wp_login_url() );

		$course_id = (int) ( $_POST['course_id'] ?? 0 );
		$course    = $course_id ? get_post( $course_id ) : null;
		if ( ! $course || $course->post_type !== Courses::CPT_COURSE ) wp_die( 'Course not found', 'Not found', [ 'response' => 404 ] );

		$is_paid = (bool) get_post_meta( $course_id, Courses::M_IS_PAID, true );
		if ( $is_paid ) wp_die( 'Paid course — please apply via /admission/.', 'Forbidden', [ 'response' => 403 ] );

		$user_id   = get_current_user_id();
		$branch_id = (int) get_user_meta( $user_id, 'rks_branch_id', true );
		if ( $branch_id <= 0 ) {
			// Fallback to main branch
			global $wpdb;
			$branch_id = (int) $wpdb->get_var( "SELECT id FROM {$wpdb->prefix}rks_branches WHERE type='main' ORDER BY id ASC LIMIT 1" );
		}
		if ( $branch_id <= 0 ) $branch_id = 1;

		$this->enroll_user( $user_id, $course_id, $branch_id );

		wp_safe_redirect( add_query_arg( [ 'view' => 'courses', 'rks_msg' => 'enrolled' ], home_url( '/student-dashboard/' ) ) );
		exit;
	}

	public function on_quiz_submitted( int $attempt_id, int $user_id, int $quiz_id, bool $passed ): void {
		if ( ! $passed ) return;
		global $wpdb;
		$course_id = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT course_id FROM {$wpdb->prefix}rks_quizzes WHERE id = %d AND is_final = 1",
			$quiz_id
		) );
		if ( $course_id <= 0 ) return;

		$enrollment = $this->get_enrollment( $user_id, $course_id );
		if ( ! $enrollment ) {
			// Issue certificate directly since they passed the final exam without being enrolled!
			$cert_module = new Certificate();
			$branch_id = (int) get_user_meta( $user_id, 'rks_branch_id', true );
			if ( $branch_id <= 0 ) {
				$branch_id = (int) $wpdb->get_var( "SELECT id FROM {$wpdb->prefix}rks_branches WHERE type='main' ORDER BY id ASC LIMIT 1" );
			}
			if ( $branch_id <= 0 ) $branch_id = 1;

			$user = get_userdata( $user_id );
			$course = get_post( $course_id );
			if ( $user && $course ) {
				$existing = (int) $wpdb->get_var( $wpdb->prepare(
					"SELECT id FROM " . DB::table( 'certificates' ) . " WHERE user_id = %d AND course_id = %d AND status = 'valid' LIMIT 1",
					$user_id, $course_id
				) );
				if ( $existing <= 0 ) {
					$cert_module->generate( $user_id, $course_id, 0, $branch_id, $user->display_name ?: $user->user_login, $course->post_title );
				}
			}
			return;
		}

		$this->maybe_complete_course( $user_id, $course_id, (int) $enrollment['id'] );
	}

	/**
	 * Decide whether the course should be marked completed based on its rule:
	 * lessons / quiz / both. Triggers `rks_course_completed` exactly once.
	 */
	public function maybe_complete_course( int $user_id, int $course_id, int $enrollment_id ): void {
		global $wpdb;
		$rule = (string) get_post_meta( $course_id, Courses::M_COMPLETION, true ) ?: 'lessons';

		// Block course completion (and thus certificate) until monthly subscription is fully paid.
		$subs = new Subscriptions();
		$sub  = $subs->get_for_user( $user_id, $course_id );
		if ( $sub && $sub['status'] !== Subscriptions::STATUS_COMPLETED ) {
			return;
		}

		$enrollment = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}rks_enrollments WHERE id = %d",
			$enrollment_id
		), ARRAY_A );
		if ( ! $enrollment ) return;

		if ( $enrollment['status'] === 'completed' ) {
			// Older progress code could mark an enrollment completed before firing
			// the completion hook. Re-fire safely; Certificate prevents duplicates.
			do_action( 'rks_course_completed', $user_id, $course_id, $enrollment_id );
			return;
		}

		$lessons_done_pct = (int) $enrollment['progress'];

		// Final quiz pass check
		$final_quiz_id = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}rks_quizzes WHERE course_id = %d AND is_final = 1 AND status = 'published' LIMIT 1",
			$course_id
		) );
		$final_passed = $final_quiz_id > 0 ? (bool) $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}rks_quiz_attempts WHERE user_id = %d AND quiz_id = %d AND status = 'passed' LIMIT 1",
			$user_id, $final_quiz_id
		) ) : true; // no final quiz defined → treat as passed

		$completed = false;
		switch ( $rule ) {
			case 'quiz':    $completed = $final_passed; break;
			case 'both':    $completed = $lessons_done_pct >= 100 && $final_passed; break;
			case 'lessons':
			default:        $completed = $lessons_done_pct >= 100; break;
		}

		if ( $completed ) {
			$wpdb->update( $wpdb->prefix . 'rks_enrollments',
				[ 'status' => 'completed', 'completed_at' => current_time( 'mysql' ), 'progress' => max( 100, $lessons_done_pct ) ],
				[ 'id' => $enrollment_id ],
				[ '%s', '%s', '%d' ], [ '%d' ]
			);
			do_action( 'rks_course_completed', $user_id, $course_id, $enrollment_id );
		}
	}

	/* ================================================================== */
	/* ENROLLMENT                                                          */
	/* ================================================================== */

	public function get_enrollment( int $user_id, int $course_id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare(
			'SELECT * FROM ' . DB::table( 'enrollments' ) . ' WHERE user_id = %d AND course_id = %d LIMIT 1',
			$user_id, $course_id
		), ARRAY_A );
		return $row ?: null;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function user_enrollments( int $user_id, ?string $status = null ): array {
		global $wpdb;
		$where  = [ 'user_id = %d' ];
		$params = [ $user_id ];
		if ( $status ) {
			$where[] = 'status = %s';
			$params[] = $status;
		}
		$sql = 'SELECT * FROM ' . DB::table( 'enrollments' ) . ' WHERE ' . implode( ' AND ', $where ) . ' ORDER BY enrolled_at DESC';
		return $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A ) ?: [];
	}

	public function is_enrolled( int $user_id, int $course_id ): bool {
		return null !== $this->get_enrollment( $user_id, $course_id );
	}

	/* ================================================================== */
	/* LESSON PROGRESS                                                     */
	/* ================================================================== */

	public function mark_lesson_complete( int $user_id, int $lesson_id ): bool {
		$lesson = get_post( $lesson_id );
		if ( ! $lesson || $lesson->post_type !== Courses::CPT_LESSON ) return false;

		$course_id = (int) get_post_meta( $lesson_id, Courses::ML_COURSE_ID, true );
		if ( $course_id <= 0 ) return false;

		$enrollment = $this->get_enrollment( $user_id, $course_id );
		if ( ! $enrollment ) return false;

		global $wpdb;
		$tbl = DB::table( 'course_progress' );

		$existing = $wpdb->get_row( $wpdb->prepare(
			"SELECT id FROM $tbl WHERE user_id = %d AND lesson_id = %d LIMIT 1",
			$user_id, $lesson_id
		), ARRAY_A );

		if ( $existing ) {
			$wpdb->update( $tbl,
				[ 'completed' => 1, 'completed_at' => current_time( 'mysql' ) ],
				[ 'id' => (int) $existing['id'] ],
				[ '%d', '%s' ], [ '%d' ]
			);
		} else {
			$wpdb->insert( $tbl, [
				'enrollment_id' => (int) $enrollment['id'],
				'user_id'       => $user_id,
				'course_id'     => $course_id,
				'lesson_id'     => $lesson_id,
				'completed'     => 1,
				'completed_at'  => current_time( 'mysql' ),
			], [ '%d','%d','%d','%d','%d','%s' ] );
		}

		do_action( 'rks_lesson_completed', $user_id, $lesson_id );
		return true;
	}

	public function is_lesson_completed( int $user_id, int $lesson_id ): bool {
		global $wpdb;
		return (bool) $wpdb->get_var( $wpdb->prepare(
			'SELECT id FROM ' . DB::table( 'course_progress' ) . ' WHERE user_id = %d AND lesson_id = %d AND completed = 1 LIMIT 1',
			$user_id, $lesson_id
		) );
	}

	/**
	 * @return int[] Array of completed lesson IDs in the course
	 */
	public function completed_lesson_ids( int $user_id, int $course_id ): array {
		global $wpdb;
		$rows = $wpdb->get_col( $wpdb->prepare(
			'SELECT lesson_id FROM ' . DB::table( 'course_progress' ) . ' WHERE user_id = %d AND course_id = %d AND completed = 1',
			$user_id, $course_id
		) );
		return array_map( 'intval', $rows );
	}

	public function recompute_enrollment_progress( int $user_id, int $lesson_id ): void {
		$course_id = (int) get_post_meta( $lesson_id, Courses::ML_COURSE_ID, true );
		if ( $course_id <= 0 ) return;

		$enrollment = $this->get_enrollment( $user_id, $course_id );
		if ( ! $enrollment ) return;

		$total_lessons = (int) get_post_meta( $course_id, Courses::M_LESSON_COUNT, true );
		if ( $total_lessons <= 0 ) {
			$total_lessons = count( Courses::get_lessons( $course_id ) );
		}
		$done = count( $this->completed_lesson_ids( $user_id, $course_id ) );

		$progress = $total_lessons > 0 ? min( 100, (int) round( ( $done / $total_lessons ) * 100 ) ) : 0;
		global $wpdb;
		$wpdb->update(
			DB::table( 'enrollments' ),
			[
				'lessons_total' => $total_lessons,
				'lessons_done'  => $done,
				'progress'      => $progress,
			],
			[ 'id' => (int) $enrollment['id'] ],
			[ '%d', '%d', '%d' ],
			[ '%d' ]
		);

		// Re-evaluate completion based on rule
		$this->maybe_complete_course( $user_id, $course_id, (int) $enrollment['id'] );
	}

	/** Manually enroll a user in a course (used by branch admin / super admin). */
	public function enroll_user( int $user_id, int $course_id, int $branch_id, string $status = 'active', ?string $enrolled_at = null ): int {
		global $wpdb;
		if ( $this->is_enrolled( $user_id, $course_id ) ) {
			$existing = $this->get_enrollment( $user_id, $course_id );
			return (int) ( $existing['id'] ?? 0 );
		}
		$total = (int) get_post_meta( $course_id, Courses::M_LESSON_COUNT, true );
		$row = [
			'user_id'       => $user_id,
			'course_id'     => $course_id,
			'branch_id'     => $branch_id,
			'lessons_total' => $total,
			'status'        => $status,
		];
		$formats = [ '%d', '%d', '%d', '%d', '%s' ];
		if ( $enrolled_at ) {
			$row['enrolled_at'] = $enrolled_at;
			$formats[] = '%s';
		}
		$wpdb->insert( $wpdb->prefix . 'rks_enrollments', $row, $formats );

		// Increment denormalized enrollment count (used for seat-limit check on the form).
		$current = (int) get_post_meta( $course_id, Courses::M_ENROLLED_COUNT, true );
		update_post_meta( $course_id, Courses::M_ENROLLED_COUNT, $current + 1 );

		return (int) $wpdb->insert_id;
	}

	/**
	 * Activate a previously pending enrollment (called when admin approves admission).
	 */
	public function activate_enrollment( int $user_id, int $course_id ): bool {
		global $wpdb;
		$row = $this->get_enrollment( $user_id, $course_id );
		if ( ! $row ) return false;
		if ( $row['status'] === 'active' || $row['status'] === 'completed' ) return true;
		$wpdb->update(
			$wpdb->prefix . 'rks_enrollments',
			[ 'status' => 'active' ],
			[ 'id' => (int) $row['id'] ],
			[ '%s' ],
			[ '%d' ]
		);
		return true;
	}
}
