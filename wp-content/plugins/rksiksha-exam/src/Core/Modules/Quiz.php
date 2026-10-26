<?php
/**
 * Quiz module — quizzes, questions, attempts, auto-grading.
 *
 * @package RKSiksha\Core
 */

declare( strict_types=1 );

namespace RKSiksha\Core\Modules;

use RKSiksha\Core\Support\DB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Quiz {

	public const TYPE_MCQ   = 'mcq';
	public const TYPE_TF    = 'tf';
	public const TYPE_SHORT = 'short';

	public const STATUS_IN_PROGRESS = 'in_progress';
	public const STATUS_SUBMITTED   = 'submitted';
	public const STATUS_PASSED      = 'passed';
	public const STATUS_FAILED      = 'failed';
	public const STATUS_ABANDONED   = 'abandoned';

	public const ASSIGN_ASSIGNED = 'assigned';
	public const ASSIGN_PASSED   = 'passed';

	public function register(): void {
		// Hook into student dashboard form submissions
		add_action( 'template_redirect', [ $this, 'handle_actions' ], 4 );
	}

	/* ================================================================== */
	/* QUIZ CRUD                                                            */
	/* ================================================================== */

	/**
	 * @param array<string, mixed> $data
	 */
	public function create_quiz( array $data ): int {
		global $wpdb;
		$row = [
			'course_id'      => (int) ( $data['course_id'] ?? 0 ),
			'lesson_id'      => isset( $data['lesson_id'] ) ? (int) $data['lesson_id'] : null,
			'title'          => $this->ensure_utf8( sanitize_text_field( (string) ( $data['title'] ?? '' ) ) ),
			'description'    => $this->ensure_utf8( sanitize_textarea_field( (string) ( $data['description'] ?? '' ) ) ),
			'time_limit_min' => (int) ( $data['time_limit_min'] ?? 0 ),
			'passing_score'  => max( 0, min( 100, (int) ( $data['passing_score'] ?? 60 ) ) ),
			'max_attempts'   => (int) ( $data['max_attempts'] ?? 3 ),
			'randomize'      => ! empty( $data['randomize'] ) ? 1 : 0,
			'show_answers'   => ! empty( $data['show_answers'] ) ? 1 : 0,
			'is_final'       => ! empty( $data['is_final'] ) ? 1 : 0,
			'is_released'    => ! empty( $data['is_released'] ) ? 1 : 0,
			'status'         => (string) ( $data['status'] ?? 'published' ),
		];
		$ok = $wpdb->insert( DB::table( 'quizzes' ), $row, [ '%d','%d','%s','%s','%d','%d','%d','%d','%d','%d','%d','%s' ] );
		return $ok ? (int) $wpdb->insert_id : 0;
	}

	public function update_quiz( int $id, array $data ): bool {
		global $wpdb;
		$allowed = [ 'course_id', 'lesson_id', 'title', 'description', 'time_limit_min', 'passing_score', 'max_attempts', 'randomize', 'show_answers', 'is_final', 'is_released', 'status' ];
		$update  = [];
		$format  = [];
		foreach ( $allowed as $f ) {
			if ( ! array_key_exists( $f, $data ) ) continue;
			if ( in_array( $f, [ 'title', 'description', 'status' ], true ) ) {
				$val = (string) $data[ $f ];
				if ( in_array( $f, [ 'title', 'description' ], true ) ) {
					$val = $this->ensure_utf8( $val );
				}
				$update[ $f ] = $val;
				$format[]     = '%s';
			} else {
				$update[ $f ] = (int) $data[ $f ];
				$format[]     = '%d';
			}
		}
		if ( empty( $update ) ) return false;
		return false !== $wpdb->update( DB::table( 'quizzes' ), $update, [ 'id' => $id ], $format, [ '%d' ] );
	}

	/**
	 * Defensive UTF-8 normalization for text columns.
	 *
	 * If the value is already valid UTF-8, return it unchanged. Otherwise attempt
	 * to coerce from the auto-detected encoding into UTF-8 so we never write raw
	 * non-UTF-8 bytes to a utf8mb4 column (which is what causes mojibake later).
	 */
	private function ensure_utf8( string $value ): string {
		if ( $value === '' ) return $value;
		if ( ! function_exists( 'mb_check_encoding' ) ) return $value;
		if ( mb_check_encoding( $value, 'UTF-8' ) ) return $value;
		if ( ! function_exists( 'mb_convert_encoding' ) ) return $value;
		$converted = @mb_convert_encoding( $value, 'UTF-8', 'auto' );
		return is_string( $converted ) && $converted !== '' ? $converted : $value;
	}

	public function delete_quiz( int $id ): bool {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . DB::table( 'quiz_answers' ) . ' WHERE attempt_id IN (SELECT id FROM ' . DB::table( 'quiz_attempts' ) . ' WHERE quiz_id = %d)', $id ) );
		$wpdb->delete( DB::table( 'quiz_attempts' ),  [ 'quiz_id' => $id ], [ '%d' ] );
		$wpdb->delete( DB::table( 'quiz_questions' ), [ 'quiz_id' => $id ], [ '%d' ] );
		$wpdb->delete( DB::table( 'quiz_assignments' ), [ 'quiz_id' => $id ], [ '%d' ] );
		return false !== $wpdb->delete( DB::table( 'quizzes' ), [ 'id' => $id ], [ '%d' ] );
	}

	public function get_quiz( int $id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . DB::table( 'quizzes' ) . ' WHERE id = %d', $id ), ARRAY_A );
		return $row ?: null;
	}

	public function list_quizzes( array $args = [] ): array {
		global $wpdb;
		$where  = [ '1=1' ];
		$params = [];
		if ( ! empty( $args['course_id'] ) )         { $where[] = 'course_id = %d';   $params[] = (int) $args['course_id']; }
		if ( ! empty( $args['lesson_id'] ) )         { $where[] = 'lesson_id = %d';   $params[] = (int) $args['lesson_id']; }
		if ( ! empty( $args['status'] ) )            { $where[] = 'status = %s';      $params[] = (string) $args['status']; }
		if ( isset( $args['is_released'] ) )         { $where[] = 'is_released = %d'; $params[] = (int) (bool) $args['is_released']; }
		$limit  = max( 1, min( 200, (int) ( $args['limit']  ?? 100 ) ) );
		$offset = max( 0, (int) ( $args['offset'] ?? 0 ) );
		$sql    = 'SELECT * FROM ' . DB::table( 'quizzes' ) . ' WHERE ' . implode( ' AND ', $where ) . ' ORDER BY id DESC LIMIT %d OFFSET %d';
		$params[] = $limit;
		$params[] = $offset;
		return $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A ) ?: [];
	}

	/**
	 * Toggle a quiz's visibility to students.
	 * Returns the new is_released state, or null on failure.
	 */
	public function set_release( int $quiz_id, bool $released ): ?bool {
		global $wpdb;
		$ok = $wpdb->update(
			DB::table( 'quizzes' ),
			[ 'is_released' => $released ? 1 : 0 ],
			[ 'id' => $quiz_id ],
			[ '%d' ],
			[ '%d' ]
		);
		return false === $ok ? null : $released;
	}

	/* ================================================================== */
	/* QUESTION CRUD                                                        */
	/* ================================================================== */

	public function create_question( array $data ): int {
		global $wpdb;
		$row = [
			'quiz_id'        => (int) ( $data['quiz_id'] ?? 0 ),
			'question_text'  => wp_kses_post( (string) ( $data['question_text'] ?? '' ) ),
			'type'           => (string) ( $data['type'] ?? self::TYPE_MCQ ),
			'options'        => DB::encode_meta( (array) ( $data['options'] ?? [] ) ),
			'correct_answer' => DB::encode_meta( (array) ( $data['correct_answer'] ?? [] ) ),
			'points'         => max( 1, (int) ( $data['points'] ?? 1 ) ),
			'display_order'  => (int) ( $data['display_order'] ?? 0 ),
			'explanation'    => sanitize_textarea_field( (string) ( $data['explanation'] ?? '' ) ),
		];
		$ok = $wpdb->insert( DB::table( 'quiz_questions' ), $row, [ '%d','%s','%s','%s','%s','%d','%d','%s' ] );
		return $ok ? (int) $wpdb->insert_id : 0;
	}

	public function update_question( int $id, array $data ): bool {
		global $wpdb;
		$update = [];
		$format = [];
		if ( isset( $data['question_text'] ) ) { $update['question_text'] = wp_kses_post( (string) $data['question_text'] );             $format[] = '%s'; }
		if ( isset( $data['type'] ) )          { $update['type']          = (string) $data['type'];                                       $format[] = '%s'; }
		if ( isset( $data['options'] ) )       { $update['options']       = DB::encode_meta( (array) $data['options'] );                 $format[] = '%s'; }
		if ( isset( $data['correct_answer'] ) ){ $update['correct_answer']= DB::encode_meta( (array) $data['correct_answer'] );          $format[] = '%s'; }
		if ( isset( $data['points'] ) )        { $update['points']        = max( 1, (int) $data['points'] );                              $format[] = '%d'; }
		if ( isset( $data['display_order'] ) ) { $update['display_order'] = (int) $data['display_order'];                                 $format[] = '%d'; }
		if ( isset( $data['explanation'] ) )   { $update['explanation']   = sanitize_textarea_field( (string) $data['explanation'] );    $format[] = '%s'; }
		if ( empty( $update ) ) return false;
		return false !== $wpdb->update( DB::table( 'quiz_questions' ), $update, [ 'id' => $id ], $format, [ '%d' ] );
	}

	public function delete_question( int $id ): bool {
		global $wpdb;
		return false !== $wpdb->delete( DB::table( 'quiz_questions' ), [ 'id' => $id ], [ '%d' ] );
	}

	public function get_question( int $id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . DB::table( 'quiz_questions' ) . ' WHERE id = %d', $id ), ARRAY_A );
		if ( ! $row ) return null;
		$row['options']        = DB::decode_meta( $row['options'] );
		$row['correct_answer'] = DB::decode_meta( $row['correct_answer'] );
		return $row;
	}

	public function get_questions( int $quiz_id ): array {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare(
			'SELECT * FROM ' . DB::table( 'quiz_questions' ) . ' WHERE quiz_id = %d ORDER BY display_order ASC, id ASC',
			$quiz_id
		), ARRAY_A );
		foreach ( $rows as &$r ) {
			$r['options']        = DB::decode_meta( $r['options'] );
			$r['correct_answer'] = DB::decode_meta( $r['correct_answer'] );
		}
		return $rows ?: [];
	}

	public function count_questions( int $quiz_id ): int {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare(
			'SELECT COUNT(*) FROM ' . DB::table( 'quiz_questions' ) . ' WHERE quiz_id = %d',
			$quiz_id
		) );
	}

	public function total_points( int $quiz_id ): int {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare(
			'SELECT COALESCE(SUM(points),0) FROM ' . DB::table( 'quiz_questions' ) . ' WHERE quiz_id = %d',
			$quiz_id
		) );
	}

	/* ================================================================== */
	/* ASSIGNMENTS                                                          */
	/* ================================================================== */

	/**
	 * @return array<int, int>
	 */
	public function assigned_user_ids( int $quiz_id ): array {
		global $wpdb;
		$ids = $wpdb->get_col( $wpdb->prepare(
			'SELECT user_id FROM ' . DB::table( 'quiz_assignments' ) . ' WHERE quiz_id = %d ORDER BY user_id ASC',
			$quiz_id
		) );
		return array_map( 'intval', $ids ?: [] );
	}

	/**
	 * Replace the assigned student list for an exam.
	 *
	 * Existing attempt allowances are preserved for students who remain assigned.
	 *
	 * @param array<int, int|string> $user_ids
	 */
	public function sync_assigned_students( int $quiz_id, array $user_ids, int $assigned_by = 0 ): void {
		global $wpdb;
		$user_ids = array_values( array_unique( array_filter( array_map( 'intval', $user_ids ) ) ) );
		$table    = DB::table( 'quiz_assignments' );

		if ( empty( $user_ids ) ) {
			$wpdb->delete( $table, [ 'quiz_id' => $quiz_id ], [ '%d' ] );
			return;
		}

		$placeholders = implode( ',', array_fill( 0, count( $user_ids ), '%d' ) );
		$params       = array_merge( [ $quiz_id ], $user_ids );
		$wpdb->query( $wpdb->prepare(
			"DELETE FROM {$table} WHERE quiz_id = %d AND user_id NOT IN ({$placeholders})",
			$params
		) );

		foreach ( $user_ids as $uid ) {
			$this->assign_student( $quiz_id, $uid, 1, $assigned_by );
		}
	}

	public function assign_student( int $quiz_id, int $user_id, int $attempts_allowed = 1, int $assigned_by = 0 ): bool {
		global $wpdb;
		if ( $quiz_id <= 0 || $user_id <= 0 ) {
			return false;
		}

		$table = DB::table( 'quiz_assignments' );
		$sql   = $wpdb->prepare(
			"INSERT INTO {$table}
				(quiz_id, user_id, attempts_allowed, status, assigned_by, assigned_at, updated_at)
			 VALUES (%d, %d, %d, %s, %d, %s, %s)
			 ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at)",
			$quiz_id,
			$user_id,
			max( 1, $attempts_allowed ),
			self::ASSIGN_ASSIGNED,
			$assigned_by,
			current_time( 'mysql' ),
			current_time( 'mysql' )
		);
		return false !== $wpdb->query( $sql );
	}

	public function get_assignment( int $user_id, int $quiz_id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare(
			'SELECT * FROM ' . DB::table( 'quiz_assignments' ) . ' WHERE user_id = %d AND quiz_id = %d LIMIT 1',
			$user_id,
			$quiz_id
		), ARRAY_A );
		return $row ?: null;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function assignment_rows( int $quiz_id ): array {
		global $wpdb;
		$table = DB::table( 'quiz_assignments' );
		$rows  = $wpdb->get_results( $wpdb->prepare(
			"SELECT a.*, u.display_name, u.user_login, u.user_email
			   FROM {$table} a
			   LEFT JOIN {$wpdb->users} u ON u.ID = a.user_id
			  WHERE a.quiz_id = %d
			  ORDER BY u.display_name ASC, u.user_login ASC",
			$quiz_id
		), ARRAY_A ) ?: [];

		foreach ( $rows as &$row ) {
			$attempts = $this->user_attempts( (int) $row['user_id'], $quiz_id );
			$used     = 0;
			$best     = 0.0;
			$passed   = false;
			foreach ( $attempts as $attempt ) {
				if ( (string) $attempt['status'] !== self::STATUS_IN_PROGRESS ) {
					$used++;
				}
				$best = max( $best, (float) $attempt['percentage'] );
				if ( (string) $attempt['status'] === self::STATUS_PASSED ) {
					$passed = true;
				}
			}
			$row['attempts_used']      = $used;
			$row['best_percentage']    = $best;
			$row['has_passed']         = $passed;
			$row['attempts_remaining'] = max( 0, (int) $row['attempts_allowed'] - $used );
		}
		unset( $row );

		return $rows;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function assigned_quizzes_for_user( int $user_id ): array {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare(
			'SELECT q.*, a.attempts_allowed, a.status AS assignment_status, a.last_attempt_id, a.assigned_at, a.updated_at
			   FROM ' . DB::table( 'quiz_assignments' ) . ' a
			   INNER JOIN ' . DB::table( 'quizzes' ) . " q ON q.id = a.quiz_id
			  WHERE a.user_id = %d
			    AND q.status = 'published'
			    AND q.is_released = 1
			  ORDER BY a.updated_at DESC, q.id DESC",
			$user_id
		), ARRAY_A ) ?: [];

		foreach ( $rows as &$row ) {
			$used = $this->attempts_used( $user_id, (int) $row['id'] );
			$row['attempts_used']      = $used;
			$row['attempts_remaining'] = max( 0, (int) $row['attempts_allowed'] - $used );
			$row['has_passed']         = $this->user_passed( $user_id, (int) $row['id'] );
		}
		unset( $row );

		return $rows;
	}

	public function attempts_used( int $user_id, int $quiz_id ): int {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare(
			'SELECT COUNT(*) FROM ' . DB::table( 'quiz_attempts' ) . " WHERE user_id = %d AND quiz_id = %d AND status != 'in_progress'",
			$user_id,
			$quiz_id
		) );
	}

	public function allow_next_attempt( int $quiz_id, int $user_id, int $admin_id = 0 ): bool {
		if ( $this->user_passed( $user_id, $quiz_id ) ) {
			return false;
		}

		global $wpdb;
		$assignment = $this->get_assignment( $user_id, $quiz_id );
		if ( ! $assignment ) {
			return $this->assign_student( $quiz_id, $user_id, 1, $admin_id );
		}

		$used    = $this->attempts_used( $user_id, $quiz_id );
		$current = max( 1, (int) $assignment['attempts_allowed'] );
		$next    = max( $current + 1, $used + 1 );

		return false !== $wpdb->update(
			DB::table( 'quiz_assignments' ),
			[
				'attempts_allowed' => $next,
				'status'           => self::ASSIGN_ASSIGNED,
				'assigned_by'      => $admin_id,
			],
			[ 'id' => (int) $assignment['id'] ],
			[ '%d', '%s', '%d' ],
			[ '%d' ]
		);
	}

	private function update_assignment_after_attempt( int $attempt_id, int $user_id, int $quiz_id, bool $passed ): void {
		global $wpdb;
		$assignment = $this->get_assignment( $user_id, $quiz_id );
		if ( ! $assignment ) {
			return;
		}

		$wpdb->update(
			DB::table( 'quiz_assignments' ),
			[
				'status'          => $passed ? self::ASSIGN_PASSED : self::ASSIGN_ASSIGNED,
				'last_attempt_id' => $attempt_id,
			],
			[ 'id' => (int) $assignment['id'] ],
			[ '%s', '%d' ],
			[ '%d' ]
		);
	}

	private function default_branch_id_for_user( int $user_id ): int {
		global $wpdb;
		$branch_id = (int) get_user_meta( $user_id, 'rks_branch_id', true );
		if ( $branch_id <= 0 ) {
			$branch_id = (int) $wpdb->get_var( "SELECT id FROM {$wpdb->prefix}rks_branches WHERE type='main' ORDER BY id ASC LIMIT 1" );
		}
		return max( 0, $branch_id );
	}

	/* ================================================================== */
	/* ATTEMPTS                                                             */
	/* ================================================================== */

	public function start_attempt( int $user_id, int $quiz_id ): int {
		global $wpdb;
		$quiz = $this->get_quiz( $quiz_id );
		if ( ! $quiz ) return 0;
		if ( (string) $quiz['status'] !== 'published' || empty( $quiz['is_released'] ) ) return 0;
		if ( $this->user_passed( $user_id, $quiz_id ) ) return 0;

		$assignment = $this->get_assignment( $user_id, $quiz_id );
		if ( ! $assignment ) return 0;

		// Resume in-progress attempt if exists
		$existing = (int) $wpdb->get_var( $wpdb->prepare(
			'SELECT id FROM ' . DB::table( 'quiz_attempts' ) . " WHERE user_id = %d AND quiz_id = %d AND status = 'in_progress' LIMIT 1",
			$user_id, $quiz_id
		) );
		if ( $existing > 0 ) return $existing;

		$attempts_used = $this->attempts_used( $user_id, $quiz_id );
		if ( $attempts_used >= (int) $assignment['attempts_allowed'] ) return 0;

		$enrollment = ( new Lms() )->get_enrollment( $user_id, (int) $quiz['course_id'] );
		$branch_id  = $enrollment ? (int) ( $enrollment['branch_id'] ?? 0 ) : $this->default_branch_id_for_user( $user_id );

		$wpdb->insert( DB::table( 'quiz_attempts' ), [
			'user_id'   => $user_id,
			'quiz_id'   => $quiz_id,
			'course_id' => (int) $quiz['course_id'],
			'branch_id' => $branch_id,
			'max_score' => $this->total_points( $quiz_id ),
			'status'    => self::STATUS_IN_PROGRESS,
		], [ '%d','%d','%d','%d','%d','%s' ] );
		return (int) $wpdb->insert_id;
	}

	public function get_attempt( int $id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare(
			'SELECT * FROM ' . DB::table( 'quiz_attempts' ) . ' WHERE id = %d',
			$id
		), ARRAY_A );
		return $row ?: null;
	}

	public function user_attempts( int $user_id, int $quiz_id ): array {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare(
			'SELECT * FROM ' . DB::table( 'quiz_attempts' ) . ' WHERE user_id = %d AND quiz_id = %d ORDER BY id DESC',
			$user_id, $quiz_id
		), ARRAY_A ) ?: [];
	}

	public function user_passed( int $user_id, int $quiz_id ): bool {
		global $wpdb;
		return (bool) $wpdb->get_var( $wpdb->prepare(
			'SELECT id FROM ' . DB::table( 'quiz_attempts' ) . " WHERE user_id = %d AND quiz_id = %d AND status = 'passed' LIMIT 1",
			$user_id, $quiz_id
		) );
	}

	/**
	 * Submit an attempt with answers, auto-grade, and update status.
	 *
	 * @param array<int, mixed> $answers question_id => given_answer
	 */
	public function submit_attempt( int $attempt_id, array $answers ): array {
		global $wpdb;
		$attempt = $this->get_attempt( $attempt_id );
		if ( ! $attempt ) return [ 'ok' => false, 'error' => 'Attempt not found' ];
		if ( $attempt['status'] !== self::STATUS_IN_PROGRESS ) return [ 'ok' => false, 'error' => 'Attempt already submitted' ];

		$quiz      = $this->get_quiz( (int) $attempt['quiz_id'] );
		$questions = $this->get_questions( (int) $attempt['quiz_id'] );

		$score        = 0;
		$max_score    = 0;
		$user_id      = (int) $attempt['user_id'];

		foreach ( $questions as $q ) {
			$max_score   += (int) $q['points'];
			$given        = $answers[ (int) $q['id'] ] ?? '';
			$is_correct   = $this->grade_question( $q, $given );
			$points       = $is_correct ? (int) $q['points'] : 0;
			$score       += $points;

			$wpdb->insert( DB::table( 'quiz_answers' ), [
				'attempt_id'     => $attempt_id,
				'question_id'    => (int) $q['id'],
				'user_id'        => $user_id,
				'given_answer'   => is_array( $given ) ? wp_json_encode( $given ) : (string) $given,
				'is_correct'     => $is_correct ? 1 : 0,
				'points_awarded' => $points,
			], [ '%d','%d','%d','%s','%d','%d' ] );
		}

		$pct       = $max_score > 0 ? round( ( $score / $max_score ) * 100, 2 ) : 0;
		$passed    = $pct >= (float) $quiz['passing_score'];
		$new_status = $passed ? self::STATUS_PASSED : self::STATUS_FAILED;
		$started   = strtotime( (string) $attempt['started_at'] );
		$elapsed   = $started ? time() - $started : 0;

		$wpdb->update( DB::table( 'quiz_attempts' ), [
			'score'          => $score,
			'max_score'      => $max_score,
			'percentage'     => $pct,
			'status'         => $new_status,
			'submitted_at'   => current_time( 'mysql' ),
			'time_taken_sec' => max( 0, (int) $elapsed ),
		], [ 'id' => $attempt_id ], [ '%d', '%d', '%f', '%s', '%s', '%d' ], [ '%d' ] );

		$this->update_assignment_after_attempt( $attempt_id, $user_id, (int) $quiz['id'], $passed );

		do_action( 'rks_quiz_attempt_submitted', $attempt_id, $user_id, (int) $quiz['id'], $passed );

		return [
			'ok'         => true,
			'score'      => $score,
			'max_score'  => $max_score,
			'percentage' => $pct,
			'passed'     => $passed,
			'status'     => $new_status,
		];
	}

	/**
	 * @param array<string, mixed> $question
	 * @param mixed $given
	 */
	private function grade_question( array $question, $given ): bool {
		$correct = $question['correct_answer'] ?? [];
		$type    = $question['type'] ?? self::TYPE_MCQ;

		if ( self::TYPE_SHORT === $type ) {
			$g = is_string( $given ) ? trim( strtolower( $given ) ) : '';
			foreach ( (array) $correct as $c ) {
				if ( trim( strtolower( (string) $c ) ) === $g && $g !== '' ) return true;
			}
			return false;
		}

		// MCQ / TF — compare normalized arrays (multi-select supported)
		$g = is_array( $given ) ? array_map( 'strval', $given ) : [ (string) $given ];
		$c = is_array( $correct ) ? array_map( 'strval', $correct ) : [ (string) $correct ];
		sort( $g );
		sort( $c );
		return $g === $c && ! empty( $c );
	}

	/* ================================================================== */
	/* HANDLER (frontend submissions)                                       */
	/* ================================================================== */

	public function handle_actions(): void {
		if ( ! is_user_logged_in() ) return;
		if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) return;
		if ( empty( $_POST['rks_action'] ) ) return;

		$action = sanitize_key( wp_unslash( (string) $_POST['rks_action'] ) );
		switch ( $action ) {
			case 'quiz_start':  $this->action_start();  break;
			case 'quiz_submit': $this->action_submit(); break;
		}
	}

	private function action_start(): void {
		check_admin_referer( 'rks_quiz_start' );
		$quiz_id = (int) ( $_POST['quiz_id'] ?? 0 );
		$user    = wp_get_current_user();
		$attempt_id = $this->start_attempt( $user->ID, $quiz_id );
		if ( $attempt_id <= 0 ) {
			wp_safe_redirect( add_query_arg( [ 'view' => 'quizzes', 'rks_msg' => 'attempts_exceeded' ], home_url( '/student-dashboard/' ) ) );
			exit;
		}
		wp_safe_redirect( add_query_arg( [ 'view' => 'quiz-take', 'attempt_id' => $attempt_id ], home_url( '/student-dashboard/' ) ) );
		exit;
	}

	private function action_submit(): void {
		check_admin_referer( 'rks_quiz_submit' );
		$attempt_id = (int) ( $_POST['attempt_id'] ?? 0 );
		$user       = wp_get_current_user();

		$attempt = $this->get_attempt( $attempt_id );
		if ( ! $attempt || (int) $attempt['user_id'] !== (int) $user->ID ) {
			wp_safe_redirect( home_url( '/student-dashboard/?view=quizzes' ) );
			exit;
		}

		$answers_raw = (array) ( $_POST['answers'] ?? [] );
		$answers     = [];
		foreach ( $answers_raw as $qid => $val ) {
			$answers[ (int) $qid ] = is_array( $val )
				? array_map( fn( $v ) => sanitize_text_field( (string) $v ), $val )
				: sanitize_text_field( (string) $val );
		}

		$this->submit_attempt( $attempt_id, $answers );

		wp_safe_redirect( add_query_arg( [ 'view' => 'quiz-result', 'attempt_id' => $attempt_id ], home_url( '/student-dashboard/' ) ) );
		exit;
	}
}
