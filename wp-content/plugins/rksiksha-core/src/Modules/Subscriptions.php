<?php
/**
 * Subscriptions module — monthly payment plans for courses.
 *
 * Lifecycle:
 *   1. On admission approval (admin), if course payment_mode == 'monthly',
 *      a subscription row is created with months_paid=1 (first month was
 *      charged at submission) and next_due_date = today + 30 days.
 *   2. Student pays each subsequent month from the dashboard (manual UPI
 *      flow). pay_next_month() creates a pending payment row tagged with
 *      meta {subscription_id, kind='monthly_subscription'} so the admin
 *      approval handler knows to bump the subscription.
 *   3. When admin approves the payment (Payments::approve()), our listener
 *      on the `rks_payment_approved` action calls approve_payment() which
 *      bumps months_paid + advances next_due_date.
 *   4. When months_paid >= total_months → status becomes 'completed' and
 *      lesson-locks/cert-blocks are lifted.
 *
 * @package RKSiksha\Core
 */

declare( strict_types=1 );

namespace RKSiksha\Core\Modules;

use RKSiksha\Core\Support\DB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Subscriptions {

	public const NONCE = 'rks_pay_monthly';

	public const STATUS_ACTIVE    = 'active';
	public const STATUS_OVERDUE   = 'overdue';
	public const STATUS_COMPLETED = 'completed';
	public const STATUS_CANCELLED = 'cancelled';

	/** Meta key written into the payments.meta JSON to tag a monthly payment. */
	public const PAYMENT_META_KIND   = 'kind';
	public const PAYMENT_META_SUB_ID = 'subscription_id';
	public const PAYMENT_KIND        = 'monthly_subscription';

	public function register(): void {
		// Student-facing form submit.
		add_action( 'admin_post_rks_pay_monthly', [ $this, 'handle_pay_monthly' ] );

		// Auto-create subscription on admission approval.
		add_action( 'rks_admission_approved', [ $this, 'on_admission_approved' ], 10, 3 );

		// Bump subscription when admin approves a monthly payment.
		add_action( 'rks_payment_approved', [ $this, 'on_payment_approved' ], 10, 2 );
	}

	/* ================================================================== */
	/* LIFECYCLE                                                           */
	/* ================================================================== */

	/**
	 * Listener for `rks_admission_approved` — auto-creates a subscription row
	 * if the course is monthly. Safely no-ops for one-time courses.
	 */
	public function on_admission_approved( int $admission_id, int $user_id, int $branch_id ): void {
		global $wpdb;
		$adm = $wpdb->get_row( $wpdb->prepare(
			'SELECT course_id FROM ' . DB::table( 'admissions' ) . ' WHERE id = %d',
			$admission_id
		), ARRAY_A );
		if ( ! $adm || empty( $adm['course_id'] ) ) {
			return;
		}
		$this->create_from_admission( $admission_id, $user_id, (int) $adm['course_id'], $branch_id );
	}

	/**
	 * Create a subscription row for the given admission. Returns the new
	 * subscription id, or 0 if the course is not monthly / already exists /
	 * any other reason to skip.
	 */
	public function create_from_admission( int $admission_id, int $user_id, int $course_id, int $branch_id ): int {
		// Course must be paid + monthly.
		$is_paid      = (bool) get_post_meta( $course_id, Courses::M_IS_PAID, true );
		$pay_mode     = (string) get_post_meta( $course_id, Courses::M_PAYMENT_MODE, true );
		$monthly_fee  = (float) get_post_meta( $course_id, Courses::M_MONTHLY_FEE, true );
		$total_months = (int)   get_post_meta( $course_id, Courses::M_TOTAL_MONTHS, true );

		if ( ! $is_paid || $pay_mode !== 'monthly' || $monthly_fee <= 0 || $total_months <= 0 ) {
			return 0;
		}

		// Idempotent — return existing subscription if present.
		$existing = $this->get_for_user( $user_id, $course_id );
		if ( $existing ) {
			return (int) $existing['id'];
		}

		// Need an enrollment id (admission approval creates/activates it just before this).
		$enrollment_id = 0;
		$lms_enrollment = ( new Lms() )->get_enrollment( $user_id, $course_id );
		if ( $lms_enrollment ) {
			$enrollment_id = (int) $lms_enrollment['id'];
		}

		// First month was already paid at admission submission.
		$months_paid    = 1;
		$is_complete    = ( $months_paid >= $total_months );
		$next_due_date  = $is_complete ? null : gmdate( 'Y-m-d', strtotime( '+30 days' ) );
		$status         = $is_complete ? self::STATUS_COMPLETED : self::STATUS_ACTIVE;
		$completed_at   = $is_complete ? current_time( 'mysql' ) : null;

		global $wpdb;
		$wpdb->insert(
			DB::table( 'course_subscriptions' ),
			[
				'user_id'       => $user_id,
				'course_id'     => $course_id,
				'enrollment_id' => $enrollment_id,
				'branch_id'     => $branch_id,
				'monthly_fee'   => $monthly_fee,
				'total_months'  => $total_months,
				'months_paid'   => $months_paid,
				'next_due_date' => $next_due_date,
				'status'        => $status,
				'completed_at'  => $completed_at,
			],
			[ '%d', '%d', '%d', '%d', '%f', '%d', '%d', '%s', '%s', '%s' ]
		);
		$sub_id = (int) $wpdb->insert_id;

		do_action( 'rks_subscription_created', $sub_id, $user_id, $course_id );
		return $sub_id;
	}

	/* ================================================================== */
	/* QUERIES                                                             */
	/* ================================================================== */

	public function get( int $id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare(
			'SELECT * FROM ' . DB::table( 'course_subscriptions' ) . ' WHERE id = %d LIMIT 1',
			$id
		), ARRAY_A );
		return $row ?: null;
	}

	public function get_for_user( int $user_id, int $course_id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare(
			'SELECT * FROM ' . DB::table( 'course_subscriptions' ) . ' WHERE user_id = %d AND course_id = %d LIMIT 1',
			$user_id, $course_id
		), ARRAY_A );
		return $row ?: null;
	}

	/** Payment is "due" when an installment is owed and the due date has arrived/passed. */
	public function is_payment_due( array $sub ): bool {
		if ( empty( $sub ) ) return false;
		if ( in_array( $sub['status'], [ self::STATUS_COMPLETED, self::STATUS_CANCELLED ], true ) ) return false;
		if ( (int) $sub['months_paid'] >= (int) $sub['total_months'] ) return false;
		if ( empty( $sub['next_due_date'] ) ) return false;
		$today = current_time( 'Y-m-d' );
		return $sub['next_due_date'] <= $today;
	}

	/**
	 * Calculate the late fee penalty for a given subscription.
	 */
	public function calculate_late_fee( array $sub ): float {
		if ( empty( $sub ) ) {
			return 0.0;
		}
		// Only check if active or overdue
		if ( ! in_array( $sub['status'], [ self::STATUS_ACTIVE, self::STATUS_OVERDUE ], true ) ) {
			return 0.0;
		}
		if ( (int) $sub['months_paid'] >= (int) $sub['total_months'] ) {
			return 0.0;
		}
		if ( empty( $sub['next_due_date'] ) ) {
			return 0.0;
		}

		$today = current_time( 'Y-m-d' );
		if ( $sub['next_due_date'] >= $today ) {
			return 0.0;
		}

		// Calculate days overdue
		$due_time = strtotime( $sub['next_due_date'] );
		$today_time = strtotime( $today );
		$diff = $today_time - $due_time;
		$days_overdue = (int) round( $diff / 86400 );
		if ( $days_overdue <= 0 ) {
			return 0.0;
		}

		$settings = (array) get_option( 'rks_settings', [] );
		$grace_days = isset( $settings['late_fee_grace_days'] ) ? absint( $settings['late_fee_grace_days'] ) : 0;
		$fee_type = isset( $settings['late_fee_type'] ) ? $settings['late_fee_type'] : 'fixed';
		$fee_amount = isset( $settings['late_fee_amount'] ) ? (float) $settings['late_fee_amount'] : 0.0;

		if ( $days_overdue <= $grace_days ) {
			return 0.0;
		}

		if ( 'per_day' === $fee_type ) {
			return (float) ( $days_overdue * $fee_amount );
		}

		return (float) $fee_amount;
	}

	/**
	 * Can the student pay right now? True if subscription has installments left
	 * AND status is active/overdue — regardless of due date (allows early payment).
	 */
	public function can_pay_now( array $sub ): bool {
		if ( empty( $sub ) ) return false;
		if ( in_array( $sub['status'], [ self::STATUS_COMPLETED, self::STATUS_CANCELLED ], true ) ) return false;
		if ( (int) $sub['months_paid'] >= (int) $sub['total_months'] ) return false;
		return true;
	}

	/** Is there a pending payment for the next installment? */
	public function has_pending_payment( int $subscription_id ): bool {
		global $wpdb;
		$like = '%' . $wpdb->esc_like( '"' . self::PAYMENT_META_SUB_ID . '":' . $subscription_id ) . '%';
		$id = (int) $wpdb->get_var( $wpdb->prepare(
			'SELECT id FROM ' . DB::table( 'payments' ) . " WHERE status = 'pending' AND meta LIKE %s LIMIT 1",
			$like
		) );
		return $id > 0;
	}

	/**
	 * List subscriptions for a branch admin's dashboard.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function list_for_branch( int $branch_id, array $args = [] ): array {
		global $wpdb;
		$where  = [ 'branch_id = %d' ];
		$params = [ $branch_id ];

		if ( ! empty( $args['status'] ) ) {
			$where[]  = 'status = %s';
			$params[] = (string) $args['status'];
		}
		if ( ! empty( $args['due_only'] ) ) {
			$where[]  = 'next_due_date <= %s';
			$params[] = current_time( 'Y-m-d' );
			$where[]  = 'months_paid < total_months';
		}

		$limit  = max( 1, min( 200, (int) ( $args['limit']  ?? 50 ) ) );
		$offset = max( 0,           (int) ( $args['offset'] ?? 0 ) );
		$sql    = 'SELECT * FROM ' . DB::table( 'course_subscriptions' )
				. ' WHERE ' . implode( ' AND ', $where )
				. ' ORDER BY next_due_date ASC, id DESC LIMIT %d OFFSET %d';
		$params[] = $limit;
		$params[] = $offset;

		return $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A ) ?: [];
	}

	/* ================================================================== */
	/* STUDENT — PAY NEXT MONTH                                            */
	/* ================================================================== */

	/**
	 * Create a pending payment row tagged to this subscription. The
	 * subscription's months_paid is NOT bumped here — that happens only when
	 * admin approves the payment via approve_payment().
	 *
	 * @param array{utr:string, amount:float, screenshot:array} $payment_data
	 * @return array{ok:bool, payment_id?:int, error?:string}
	 */
	public function pay_next_month( int $user_id, int $course_id, array $payment_data ): array {
		$sub = $this->get_for_user( $user_id, $course_id );
		if ( ! $sub ) {
			return [ 'ok' => false, 'error' => 'No active subscription for this course.' ];
		}
		if ( ! $this->can_pay_now( $sub ) ) {
			return [ 'ok' => false, 'error' => 'No installments remaining for this subscription.' ];
		}
		if ( $this->has_pending_payment( (int) $sub['id'] ) ) {
			return [ 'ok' => false, 'error' => 'A payment for this month is already pending review.' ];
		}

		$utr          = sanitize_text_field( (string) ( $payment_data['utr'] ?? '' ) );
		$amount       = (float) ( $payment_data['amount'] ?? 0 );
		$file         = (array) ( $payment_data['screenshot'] ?? [] );
		$payment_type = sanitize_key( (string) ( $payment_data['payment_type'] ?? 'installment' ) );
		$method       = sanitize_key( (string) ( $payment_data['method'] ?? 'upi' ) );

		if ( ! in_array( $method, [ 'upi', 'offline' ], true ) ) {
			return [ 'ok' => false, 'error' => 'Invalid payment method.' ];
		}

		if ( $amount <= 0 ) {
			return [ 'ok' => false, 'error' => 'Please enter a valid amount.' ];
		}

		$remaining_months = (int) $sub['total_months'] - (int) $sub['months_paid'];
		$months_to_pay    = ( $payment_type === 'full' ) ? $remaining_months : 1;
		$late_fee         = $this->calculate_late_fee( $sub );
		$expected_amount  = ( (float) $sub['monthly_fee'] * $months_to_pay ) + $late_fee;

		if ( abs( $amount - $expected_amount ) > 0.01 ) {
			return [ 'ok' => false, 'error' => 'Incorrect payment amount.' ];
		}

		$screenshot_path = '';
		if ( $method === 'offline' ) {
			$utr = 'OFFLINE-' . strtoupper( wp_generate_password( 8, false, false ) );
		} else {
			if ( $utr === '' || empty( $file['tmp_name'] ) ) {
				return [ 'ok' => false, 'error' => 'Please provide UTR / Transaction ID and a screenshot.' ];
			}
			$payments = new Payments();
			$store    = $payments->store_screenshot( $file );
			if ( empty( $store['ok'] ) ) {
				return [ 'ok' => false, 'error' => $store['error'] ?? 'Could not save screenshot.' ];
			}
			$screenshot_path = $store['path'];
		}

		$payments = new Payments();
		$payment_id = $payments->create( [
			'branch_id'       => (int) $sub['branch_id'],
			'user_id'         => $user_id,
			'admission_id'    => null, // monthly installment — not tied to admission row
			'amount'          => $amount,
			'method'          => $method,
			'utr'             => $utr,
			'screenshot_path' => $screenshot_path,
			'status'          => Payments::STATUS_PENDING,
			'meta'            => [
				self::PAYMENT_META_KIND   => self::PAYMENT_KIND,
				self::PAYMENT_META_SUB_ID => (int) $sub['id'],
				'course_id'               => $course_id,
				'month_number'            => (int) $sub['months_paid'] + 1,
				'months_to_pay'           => $months_to_pay,
				'payment_type'            => $payment_type,
				'late_fee'                => $late_fee,
			],
		] );

		if ( $payment_id <= 0 ) {
			return [ 'ok' => false, 'error' => 'Could not record payment.' ];
		}

		return [ 'ok' => true, 'payment_id' => $payment_id ];
	}

	/**
	 * Listener for `rks_payment_approved` — bumps subscription if the
	 * approved payment was tagged as a monthly installment.
	 */
	public function on_payment_approved( int $payment_id, int $reviewer_id ): void {
		$payments = new Payments();
		$pay      = $payments->get( $payment_id );
		if ( ! $pay ) return;

		$meta = DB::decode_meta( $pay['meta'] ?? null );
		if ( ( $meta[ self::PAYMENT_META_KIND ] ?? '' ) !== self::PAYMENT_KIND ) {
			return;
		}
		$sub_id = (int) ( $meta[ self::PAYMENT_META_SUB_ID ] ?? 0 );
		if ( $sub_id <= 0 ) return;

		$this->approve_payment( $payment_id, $reviewer_id );
	}

	/**
	 * Bump the subscription: months_paid++, push next_due_date by 30 days,
	 * mark completed if all months paid.
	 */
	public function approve_payment( int $payment_id, int $reviewer_id ): bool {
		$payments = new Payments();
		$pay      = $payments->get( $payment_id );
		if ( ! $pay ) return false;

		$meta   = DB::decode_meta( $pay['meta'] ?? null );
		$sub_id = (int) ( $meta[ self::PAYMENT_META_SUB_ID ] ?? 0 );
		if ( $sub_id <= 0 ) return false;

		$sub = $this->get( $sub_id );
		if ( ! $sub ) return false;

		$months_to_pay = isset( $meta['months_to_pay'] ) ? (int) $meta['months_to_pay'] : 1;
		if ( $months_to_pay <= 0 ) {
			$months_to_pay = 1;
		}

		$new_months_paid = (int) $sub['months_paid'] + $months_to_pay;
		$total           = (int) $sub['total_months'];
		if ( $new_months_paid > $total ) {
			$new_months_paid = $total;
		}
		$is_complete     = ( $new_months_paid >= $total );

		$days_to_add = 30 * $months_to_pay;

		$today = current_time( 'Y-m-d' );
		if ( ! empty( $sub['next_due_date'] ) && $sub['next_due_date'] > $today ) {
			$next_due_date = date( 'Y-m-d', strtotime( $sub['next_due_date'] . ' +' . $days_to_add . ' days' ) );
		} else {
			$next_due_date = date( 'Y-m-d', strtotime( $today . ' +' . $days_to_add . ' days' ) );
		}

		$update = [
			'months_paid'   => $new_months_paid,
			'next_due_date' => $is_complete ? null : $next_due_date,
			'status'        => $is_complete ? self::STATUS_COMPLETED : self::STATUS_ACTIVE,
			'completed_at'  => $is_complete ? current_time( 'mysql' ) : null,
		];
		$formats = [ '%d', '%s', '%s', '%s' ];

		global $wpdb;
		$ok = false !== $wpdb->update(
			DB::table( 'course_subscriptions' ),
			$update,
			[ 'id' => $sub_id ],
			$formats,
			[ '%d' ]
		);

		if ( $ok ) {
			do_action( 'rks_subscription_payment_approved', $sub_id, (int) $sub['user_id'], (int) $sub['course_id'], $new_months_paid );
			if ( $is_complete ) {
				do_action( 'rks_subscription_completed', $sub_id, (int) $sub['user_id'], (int) $sub['course_id'] );
				// Re-evaluate course completion now that the financial gate has lifted.
				$enrollment_id = (int) $sub['enrollment_id'];
				if ( $enrollment_id > 0 ) {
					( new Lms() )->maybe_complete_course( (int) $sub['user_id'], (int) $sub['course_id'], $enrollment_id );
				}
			}
		}

		return $ok;
	}

	/* ================================================================== */
	/* FORM HANDLER                                                        */
	/* ================================================================== */

	public function handle_pay_monthly(): void {
		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( wp_login_url() );
			exit;
		}
		check_admin_referer( self::NONCE );

		$user_id      = get_current_user_id();
		$course_id    = (int) ( $_POST['course_id'] ?? 0 );
		$utr          = sanitize_text_field( wp_unslash( (string) ( $_POST['utr']    ?? '' ) ) );
		$raw_amount   = $_POST['amount'] ?? '';
		$amount       = is_numeric( $raw_amount ) ? (float) $raw_amount : 0.0;
		$payment_type = isset( $_POST['payment_type'] ) ? sanitize_key( wp_unslash( (string) $_POST['payment_type'] ) ) : 'installment';
		$method       = isset( $_POST['method'] ) ? sanitize_key( wp_unslash( (string) $_POST['method'] ) ) : 'upi';

		// DEBUG — remove after confirming.
		error_log( sprintf(
			'[rks_pay_monthly] user=%d course=%d method=%s payment_type=%s amount=%s raw_amount=%s utr=%s',
			$user_id, $course_id, $method, $payment_type, $amount, (string) $raw_amount, $utr
		) );

		if ( $course_id <= 0 ) {
			error_log( '[rks_pay_monthly] FAIL: invalid course_id' );
			$this->redirect_back( 'invalid_course', $course_id );
		}

		$result = $this->pay_next_month( $user_id, $course_id, [
			'utr'          => $utr,
			'amount'       => $amount,
			'screenshot'   => (array) ( $_FILES['screenshot'] ?? [] ),
			'payment_type' => $payment_type,
			'method'       => $method,
		] );

		error_log( '[rks_pay_monthly] result=' . wp_json_encode( $result ) );

		if ( empty( $result['ok'] ) ) {
			$this->redirect_back( (string) ( $result['error'] ?? 'generic' ), $course_id );
		}

		wp_safe_redirect( add_query_arg(
			[ 'view' => 'courses', 'rks_msg' => 'monthly_submitted' ],
			home_url( '/student-dashboard/' )
		) );
		exit;
	}

	private function redirect_back( string $code, int $course_id = 0 ): void {
		$args = [ 'view' => 'pay-monthly', 'rks_err' => rawurlencode( $code ) ];
		if ( $course_id > 0 ) {
			$args['course_id'] = $course_id;
		} else {
			$args['view'] = 'courses';
		}
		wp_safe_redirect( add_query_arg( $args, home_url( '/student-dashboard/' ) ) );
		exit;
	}
}
