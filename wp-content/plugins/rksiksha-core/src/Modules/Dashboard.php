<?php
/**
 * Dashboard module — frontend Branch Admin dashboard.
 *
 * Provides query helpers, branch profile saving, and action handlers used by
 * the `page-branch-dashboard.php` theme template.
 *
 * @package RKSiksha\Core
 */

declare( strict_types=1 );

namespace RKSiksha\Core\Modules;

use RKSiksha\Core\Roles;
use RKSiksha\Core\Support\DB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Dashboard {

	public const VIEWS = [ 'overview', 'admissions', 'payments', 'students', 'branch' ];

	public function register(): void {
		add_action( 'template_redirect', [ $this, 'gate_access' ], 1 );
		add_action( 'template_redirect', [ $this, 'handle_actions' ], 5 );
	}

	/* ================================================================== */
	/* ACCESS CONTROL                                                      */
	/* ================================================================== */

	public function gate_access(): void {
		if ( ! is_page_template( 'page-branch-dashboard.php' ) ) return;

		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( add_query_arg( 'redirect_to', urlencode( home_url( '/branch-dashboard/' ) ), home_url( '/login/' ) ) );
			exit;
		}
		if ( ! current_user_can( 'rks_view_branch_dashboard' ) ) {
			wp_die( 'You do not have permission to access this dashboard.', 'Forbidden', [ 'response' => 403 ] );
		}
	}

	/* ================================================================== */
	/* ACTION HANDLERS                                                     */
	/* ================================================================== */

	public function handle_actions(): void {
		if ( ! is_page_template( 'page-branch-dashboard.php' ) ) return;
		if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) return;
		if ( empty( $_POST['rks_action'] ) ) return;
		if ( ! is_user_logged_in() ) return;

		$action = sanitize_key( wp_unslash( (string) $_POST['rks_action'] ) );

		switch ( $action ) {
			case 'admission_approve': $this->action_admission_approve(); break;
			case 'admission_reject':  $this->action_admission_reject();  break;
			case 'branch_save':       $this->action_branch_save();       break;
		}
	}

	private function action_admission_approve(): void {
		check_admin_referer( 'rks_dash_admission' );
		if ( ! current_user_can( 'rks_approve_admission' ) ) wp_die( 'Forbidden' );

		$id   = (int) ( $_POST['admission_id'] ?? 0 );
		$user = wp_get_current_user();
		$adm  = ( new Admissions() )->get( $id );

		if ( ! $adm ) { $this->redirect( 'admissions', 'not_found' ); }
		if ( ! $this->user_can_act_on_branch( $user, (int) $adm['branch_id'] ) ) $this->redirect( 'admissions', 'wrong_branch' );

		$res = ( new Admissions() )->approve( $id, $user->ID );
		$this->redirect( 'admissions', $res['ok'] ? 'approved' : 'error' );
	}

	private function action_admission_reject(): void {
		check_admin_referer( 'rks_dash_admission' );
		if ( ! current_user_can( 'rks_approve_admission' ) ) wp_die( 'Forbidden' );

		$id   = (int) ( $_POST['admission_id'] ?? 0 );
		$note = sanitize_textarea_field( wp_unslash( (string) ( $_POST['note'] ?? '' ) ) );
		$user = wp_get_current_user();
		$adm  = ( new Admissions() )->get( $id );

		if ( ! $adm ) $this->redirect( 'admissions', 'not_found' );
		if ( ! $this->user_can_act_on_branch( $user, (int) $adm['branch_id'] ) ) $this->redirect( 'admissions', 'wrong_branch' );

		$res = ( new Admissions() )->reject( $id, $user->ID, $note );
		$this->redirect( 'admissions', $res['ok'] ? 'rejected' : 'error' );
	}

	private function action_branch_save(): void {
		check_admin_referer( 'rks_dash_branch' );
		$user      = wp_get_current_user();
		$branch_id = $this->user_branch_id( $user );

		if ( $branch_id <= 0 || ! $this->user_can_act_on_branch( $user, $branch_id ) ) {
			$this->redirect( 'branch', 'forbidden' );
		}

		$data = [
			'name'    => sanitize_text_field( wp_unslash( (string) ( $_POST['name']    ?? '' ) ) ),
			'address' => sanitize_textarea_field( wp_unslash( (string) ( $_POST['address'] ?? '' ) ) ),
			'city'    => sanitize_text_field( wp_unslash( (string) ( $_POST['city']    ?? '' ) ) ),
			'state'   => sanitize_text_field( wp_unslash( (string) ( $_POST['state']   ?? '' ) ) ),
			'pincode' => sanitize_text_field( wp_unslash( (string) ( $_POST['pincode'] ?? '' ) ) ),
			'phone'   => sanitize_text_field( wp_unslash( (string) ( $_POST['phone']   ?? '' ) ) ),
			'email'   => sanitize_email(      wp_unslash( (string) ( $_POST['email']   ?? '' ) ) ),
		];

		( new Branches() )->update( $branch_id, $data );
		$this->redirect( 'branch', 'saved' );
	}

	/* ================================================================== */
	/* QUERY HELPERS — used by theme template                              */
	/* ================================================================== */

	public function user_branch_id( \WP_User $user ): int {
		$id = (int) get_user_meta( $user->ID, 'rks_branch_id', true );
		if ( $id > 0 ) return $id;
		// Super admins without a branch default to Main Branch
		if ( $this->is_super( $user ) ) {
			return ( new Branches() )->get_main_branch_id();
		}
		return 0;
	}

	public function user_can_act_on_branch( \WP_User $user, int $branch_id ): bool {
		if ( $this->is_super( $user ) ) return true;
		return (int) get_user_meta( $user->ID, 'rks_branch_id', true ) === $branch_id && $branch_id > 0;
	}

	public function is_super( \WP_User $user ): bool {
		$r = (array) $user->roles;
		return in_array( 'administrator', $r, true ) || in_array( Roles::SUPER_ADMIN, $r, true );
	}

	/**
	 * Aggregate stats for the overview tab.
	 *
	 * @return array<string, int|float>
	 */
	public function stats( int $branch_id ): array {
		global $wpdb;
		$adm = DB::table( 'admissions' );
		$pay = DB::table( 'payments' );
		$enr = DB::table( 'enrollments' );

		$where = $wpdb->prepare( 'branch_id = %d', $branch_id );

		$total_students      = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT user_id) FROM $enr WHERE $where AND user_id > 0" );
		$pending_admissions  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $adm WHERE $where AND status IN ('pending','payment_review')" );
		$pending_payments    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $pay WHERE $where AND status = 'pending'" );
		$approved_this_month = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $adm WHERE $where AND status = 'approved' AND YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())" );
		$revenue_this_month  = (float) $wpdb->get_var( "SELECT COALESCE(SUM(amount),0) FROM $pay WHERE $where AND status = 'approved' AND YEAR(verified_at) = YEAR(CURDATE()) AND MONTH(verified_at) = MONTH(CURDATE())" );
		$total_revenue       = (float) $wpdb->get_var( "SELECT COALESCE(SUM(amount),0) FROM $pay WHERE $where AND status = 'approved'" );

		return [
			'total_students'      => $total_students,
			'pending_admissions'  => $pending_admissions,
			'pending_payments'    => $pending_payments,
			'approved_this_month' => $approved_this_month,
			'revenue_this_month'  => $revenue_this_month,
			'total_revenue'       => $total_revenue,
		];
	}

	/**
	 * Paginated student list for a branch (joined with enrollment + course).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function students( int $branch_id, int $limit = 50, int $offset = 0 ): array {
		global $wpdb;
		$enr   = DB::table( 'enrollments' );
		$users = $wpdb->users;
		$posts = $wpdb->posts;

		$sql = $wpdb->prepare(
			"SELECT u.ID AS user_id, u.user_login, u.user_email, u.display_name,
			        COUNT(e.id) AS enrollment_count,
			        MAX(e.enrolled_at) AS last_enrolled,
			        SUM(CASE WHEN e.status='completed' THEN 1 ELSE 0 END) AS completed_count
			 FROM $enr e
			 INNER JOIN $users u ON u.ID = e.user_id
			 WHERE e.branch_id = %d AND e.user_id > 0
			 GROUP BY u.ID
			 ORDER BY last_enrolled DESC
			 LIMIT %d OFFSET %d",
			$branch_id, $limit, $offset
		);
		return $wpdb->get_results( $sql, ARRAY_A ) ?: [];
	}

	/* ================================================================== */
	/* INTERNAL                                                            */
	/* ================================================================== */

	private function redirect( string $view, string $msg ): void {
		$url = add_query_arg( [ 'view' => $view, 'rks_msg' => $msg ], home_url( '/branch-dashboard/' ) );
		wp_safe_redirect( $url );
		exit;
	}
}
