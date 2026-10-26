<?php
/**
 * StudentDashboard — frontend `/student-dashboard/` template handler.
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

final class StudentDashboard {

	public const VIEWS = [ 'overview', 'courses', 'lesson', 'quizzes', 'quiz-take', 'quiz-result', 'certificates', 'idcard', 'payments', 'profile', 'pay-monthly' ];

	public function register(): void {
		add_action( 'template_redirect', [ $this, 'gate_access' ], 1 );
		add_action( 'template_redirect', [ $this, 'handle_actions' ], 5 );
	}

	public function gate_access(): void {
		if ( ! is_page_template( 'page-student-dashboard.php' ) ) return;
		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( add_query_arg( 'redirect_to', urlencode( home_url( '/student-dashboard/' ) ), home_url( '/login/' ) ) );
			exit;
		}
		$user = wp_get_current_user();
		// Allow students; allow admins for testing
		$roles      = (array) $user->roles;
		$has_access = in_array( Roles::STUDENT, $roles, true )
			|| in_array( Roles::BRANCH_ADMIN, $roles, true )
			|| in_array( 'administrator', $roles, true )
			|| in_array( Roles::SUPER_ADMIN, $roles, true );
		if ( ! $has_access ) {
			wp_die( 'Student account required to access this page.', 'Forbidden', [ 'response' => 403 ] );
		}
	}

	public function handle_actions(): void {
		if ( ! is_page_template( 'page-student-dashboard.php' ) ) return;
		if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) return;
		if ( empty( $_POST['rks_action'] ) ) return;
		if ( ! is_user_logged_in() ) return;

		$action = sanitize_key( wp_unslash( (string) $_POST['rks_action'] ) );

		switch ( $action ) {
			case 'mark_complete':   $this->action_mark_complete();   break;
			case 'profile_save':    $this->action_profile_save();    break;
			case 'password_change': $this->action_password_change(); break;
			case 'photo_upload':    $this->action_photo_upload();    break;
			case 'photo_remove':    $this->action_photo_remove();    break;
		}
	}

	private function action_photo_upload(): void {
		check_admin_referer( 'rks_photo_upload' );
		$user = wp_get_current_user();
		if ( empty( $_FILES['photo']['tmp_name'] ) ) $this->redirect_profile( 'no_file' );

		// Validate MIME + size
		$allowed = [ 'image/jpeg' => 'jpg', 'image/jpg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp' ];
		$mime    = function_exists( 'mime_content_type' ) ? (string) mime_content_type( $_FILES['photo']['tmp_name'] ) : (string) ( $_FILES['photo']['type'] ?? '' );
		if ( ! isset( $allowed[ $mime ] ) ) $this->redirect_profile( 'bad_type' );
		if ( (int) ( $_FILES['photo']['size'] ?? 0 ) > 3 * 1024 * 1024 ) $this->redirect_profile( 'too_large' );

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$attachment_id = media_handle_upload( 'photo', 0 );
		if ( is_wp_error( $attachment_id ) ) $this->redirect_profile( 'upload_failed' );

		// Remove any previous photo
		$old = (int) get_user_meta( $user->ID, 'rks_photo_id', true );
		if ( $old && $old !== (int) $attachment_id ) wp_delete_attachment( $old, true );

		update_user_meta( $user->ID, 'rks_photo_id', (int) $attachment_id );

		$this->redirect_profile( 'photo_saved' );
	}

	private function action_photo_remove(): void {
		check_admin_referer( 'rks_photo_remove' );
		$user = wp_get_current_user();
		$old = (int) get_user_meta( $user->ID, 'rks_photo_id', true );
		if ( $old ) wp_delete_attachment( $old, true );
		delete_user_meta( $user->ID, 'rks_photo_id' );
		$this->redirect_profile( 'photo_removed' );
	}

	private function action_mark_complete(): void {
		check_admin_referer( 'rks_lesson_complete' );
		$lesson_id = (int) ( $_POST['lesson_id'] ?? 0 );
		$course_id = (int) ( $_POST['course_id'] ?? 0 );
		$user      = wp_get_current_user();

		( new Lms() )->mark_lesson_complete( $user->ID, $lesson_id );

		// Find next lesson in the course
		$lessons = Courses::get_lessons( $course_id );
		$next_id = 0;
		$found_current = false;
		foreach ( $lessons as $l ) {
			if ( $found_current ) { $next_id = (int) $l->ID; break; }
			if ( (int) $l->ID === $lesson_id ) $found_current = true;
		}

		$args = [ 'view' => 'lesson', 'course_id' => $course_id, 'rks_msg' => 'completed' ];
		if ( $next_id > 0 ) $args['lesson_id'] = $next_id;
		else                $args['view']      = 'overview';

		wp_safe_redirect( add_query_arg( $args, home_url( '/student-dashboard/' ) ) );
		exit;
	}

	private function action_profile_save(): void {
		check_admin_referer( 'rks_profile_save' );
		$user = wp_get_current_user();
		$first = sanitize_text_field( wp_unslash( (string) ( $_POST['first_name'] ?? '' ) ) );
		$last  = sanitize_text_field( wp_unslash( (string) ( $_POST['last_name']  ?? '' ) ) );
		$phone = sanitize_text_field( wp_unslash( (string) ( $_POST['phone']      ?? '' ) ) );

		wp_update_user( [
			'ID'           => $user->ID,
			'first_name'   => $first,
			'last_name'    => $last,
			'display_name' => trim( $first . ' ' . $last ) ?: $user->user_login,
		] );
		update_user_meta( $user->ID, 'rks_phone', $phone );

		wp_safe_redirect( add_query_arg( [ 'view' => 'profile', 'rks_msg' => 'saved' ], home_url( '/student-dashboard/' ) ) );
		exit;
	}

	private function action_password_change(): void {
		check_admin_referer( 'rks_password_change' );
		$user = wp_get_current_user();

		$current = (string) ( $_POST['current_password'] ?? '' );
		$new     = (string) ( $_POST['new_password']     ?? '' );
		$new2    = (string) ( $_POST['new_password2']    ?? '' );

		if ( ! wp_check_password( $current, $user->user_pass, $user->ID ) ) {
			$this->redirect_profile( 'wrong_current' );
		}
		if ( strlen( $new ) < 8 )                $this->redirect_profile( 'weak_password' );
		if ( $new !== $new2 )                    $this->redirect_profile( 'mismatch' );

		wp_set_password( $new, $user->ID );
		wp_clear_auth_cookie();
		wp_set_current_user( $user->ID );
		wp_set_auth_cookie( $user->ID );

		$this->redirect_profile( 'password_changed' );
	}

	private function redirect_profile( string $msg ): void {
		wp_safe_redirect( add_query_arg( [ 'view' => 'profile', 'rks_msg' => $msg ], home_url( '/student-dashboard/' ) ) );
		exit;
	}

	/* ================================================================== */
	/* QUERY HELPERS                                                       */
	/* ================================================================== */

	public function stats( int $user_id ): array {
		global $wpdb;
		$enr  = DB::table( 'enrollments' );
		$cert = DB::table( 'certificates' );

		return [
			'total_enrolled'     => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $enr WHERE user_id = %d", $user_id ) ),
			'in_progress'        => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $enr WHERE user_id = %d AND status = 'active' AND progress > 0", $user_id ) ),
			'completed'          => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $enr WHERE user_id = %d AND status = 'completed'", $user_id ) ),
			'certificates'       => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $cert WHERE user_id = %d AND status = 'valid'", $user_id ) ),
		];
	}
}
