<?php
/**
 * DevTools — demo user seeder + login autofill panel.
 *
 * Active only when RKS_DEV_MODE is true. Disable in production by setting
 * `define( 'RKS_DEV_MODE', false );` in rksiksha-core.php.
 *
 * @package RKSiksha\Core
 */

declare( strict_types=1 );

namespace RKSiksha\Core\Admin;

use RKSiksha\Core\Modules\Branches;
use RKSiksha\Core\Modules\Courses;
use RKSiksha\Core\Roles;
use RKSiksha\Core\Support\DB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DevTools {

	public const DEMO_PASSWORD = 'Demo@1234';

	public function register(): void {
		if ( ! RKS_DEV_MODE ) return;

		add_action( 'admin_post_rks_seed_demo',  [ $this, 'handle_seed' ] );
		add_action( 'admin_post_rks_clear_demo', [ $this, 'handle_clear' ] );
		add_action( 'admin_post_rks_devtools_seed_monthly_course', [ $this, 'handle_seed_monthly_course' ] );
	}

	/* ================================================================== */
	/* DEMO USERS                                                          */
	/* ================================================================== */

	/**
	 * @return array<int, array{login: string, label: string, role: string}>
	 */
	public function demo_users(): array {
		return [
			[ 'login' => 'super.demo',     'label' => 'Super Admin',           'role' => Roles::SUPER_ADMIN  ],
			[ 'login' => 'mainadmin',      'label' => 'Main Branch Admin',     'role' => Roles::BRANCH_ADMIN ],
			[ 'login' => 'franchise.demo', 'label' => 'Franchise Branch Admin','role' => Roles::BRANCH_ADMIN ],
			[ 'login' => 'student.demo',   'label' => 'Student',               'role' => Roles::STUDENT      ],
		];
	}

	public function seed_status(): array {
		$result = [];
		foreach ( $this->demo_users() as $u ) {
			$user = get_user_by( 'login', $u['login'] );
			$result[] = array_merge( $u, [
				'exists'      => (bool) $user,
				'user_id'     => $user ? (int) $user->ID : 0,
				'is_demo'     => $user ? (bool) get_user_meta( $user->ID, 'rks_is_demo', true ) : false,
			] );
		}
		return $result;
	}

	public function handle_seed(): void {
		check_admin_referer( 'rks_seed_demo' );
		if ( ! current_user_can( 'rks_manage_settings' ) ) wp_die( 'Forbidden', 'Forbidden', [ 'response' => 403 ] );

		$created = $this->seed();
		wp_safe_redirect( add_query_arg( [ 'page' => Menu::SLUG, 'rks_msg' => 'seeded', 'rks_count' => $created ], admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_clear(): void {
		check_admin_referer( 'rks_clear_demo' );
		if ( ! current_user_can( 'rks_manage_settings' ) ) wp_die( 'Forbidden', 'Forbidden', [ 'response' => 403 ] );

		$cleared = $this->clear();
		wp_safe_redirect( add_query_arg( [ 'page' => Menu::SLUG, 'rks_msg' => 'cleared', 'rks_count' => $cleared ], admin_url( 'admin.php' ) ) );
		exit;
	}

	public function seed(): int {
		$created = 0;

		$main_branch_id = ( new Branches() )->get_main_branch_id();

		// 1. Super Admin
		if ( ! get_user_by( 'login', 'super.demo' ) ) {
			$uid = wp_create_user( 'super.demo', self::DEMO_PASSWORD, 'super.demo@rksiksha.test' );
			if ( ! is_wp_error( $uid ) ) {
				$user = get_user_by( 'id', $uid );
				$user->set_role( 'administrator' );
				$user->add_role( Roles::SUPER_ADMIN );
				wp_update_user( [ 'ID' => $uid, 'first_name' => 'Demo', 'last_name' => 'Super', 'display_name' => 'Demo Super Admin' ] );
				update_user_meta( $uid, 'rks_is_demo', 1 );
				$created++;
			}
		}

		// 2. Main Branch Admin (mainadmin already exists from activation; reset to demo password)
		$main_admin = get_user_by( 'login', 'mainadmin' );
		if ( $main_admin ) {
			wp_set_password( self::DEMO_PASSWORD, $main_admin->ID );
			update_user_meta( $main_admin->ID, 'rks_is_demo', 1 );
		}

		// 3. (Haldwani demo franchise removed by user request)

		// 4. Student
		global $wpdb;
		if ( ! get_user_by( 'login', 'student.demo' ) ) {
			$uid = wp_create_user( 'student.demo', self::DEMO_PASSWORD, 'student.demo@rksiksha.test' );
			if ( ! is_wp_error( $uid ) ) {
				$user = get_user_by( 'id', $uid );
				$user->set_role( Roles::STUDENT );
				wp_update_user( [ 'ID' => $uid, 'first_name' => 'Demo', 'last_name' => 'Student', 'display_name' => 'Demo Student' ] );
				update_user_meta( $uid, 'rks_branch_id', $main_branch_id );
				update_user_meta( $uid, 'rks_phone', '+91 9999912345' );
				update_user_meta( $uid, 'rks_is_demo', 1 );

				// Enroll in first available course with partial progress
				$courses = get_posts( [ 'post_type' => 'rks_course', 'posts_per_page' => 1, 'orderby' => 'ID', 'order' => 'ASC' ] );
				if ( $courses ) {
					$course_id = (int) $courses[0]->ID;

					// Insert enrollment
					$wpdb->insert(
						DB::table( 'enrollments' ),
						[
							'user_id'     => $uid,
							'course_id'   => $course_id,
							'branch_id'   => $main_branch_id,
							'status'      => 'active',
						],
						[ '%d', '%d', '%d', '%s' ]
					);
					$enrollment_id = (int) $wpdb->insert_id;

					// Mark first 2 lessons complete to show progress
					$lessons = get_posts( [
						'post_type'      => 'rks_lesson',
						'posts_per_page' => 2,
						'meta_key'       => '_rks_lesson_order',
						'orderby'        => 'meta_value_num',
						'order'          => 'ASC',
						'meta_query'     => [ [ 'key' => '_rks_lesson_course_id', 'value' => $course_id, 'type' => 'NUMERIC' ] ],
					] );
					foreach ( $lessons as $l ) {
						$wpdb->insert(
							DB::table( 'course_progress' ),
							[
								'enrollment_id' => $enrollment_id,
								'user_id'       => $uid,
								'course_id'     => $course_id,
								'lesson_id'     => (int) $l->ID,
								'completed'     => 1,
								'completed_at'  => current_time( 'mysql' ),
							],
							[ '%d','%d','%d','%d','%d','%s' ]
						);
					}

					// Recompute progress
					$total = (int) get_post_meta( $course_id, '_rks_course_lesson_count', true );
					$done  = count( $lessons );
					$pct   = $total > 0 ? (int) round( ( $done / $total ) * 100 ) : 0;
					$wpdb->update(
						DB::table( 'enrollments' ),
						[ 'lessons_total' => $total, 'lessons_done' => $done, 'progress' => $pct ],
						[ 'id' => $enrollment_id ],
						[ '%d','%d','%d' ], [ '%d' ]
					);
				}
				$created++;
			}
		}

		return $created;
	}

	public function clear(): int {
		$cleared = 0;
		foreach ( $this->demo_users() as $u ) {
			if ( $u['login'] === 'mainadmin' ) continue; // never delete mainadmin (system user)
			$user = get_user_by( 'login', $u['login'] );
			if ( $user && get_user_meta( $user->ID, 'rks_is_demo', true ) ) {
				require_once ABSPATH . 'wp-admin/includes/user.php';
				wp_delete_user( $user->ID );
				$cleared++;
			}
		}

		// Remove demo franchise branch
		global $wpdb;
		$wpdb->query( "DELETE FROM " . DB::table( 'branches' ) . " WHERE name LIKE '%Demo Franchise%' AND type='franchise'" );

		return $cleared;
	}

	/* ================================================================== */
	/* DEMO MONTHLY COURSE                                                 */
	/* ================================================================== */

	public const DEMO_MONTHLY_COURSE_TITLE = 'Demo Monthly Course (Test)';

	public function handle_seed_monthly_course(): void {
		check_admin_referer( 'rks_devtools_seed_monthly_course' );
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Forbidden', 'Forbidden', [ 'response' => 403 ] );

		$existing = get_posts( [
			'post_type'        => Courses::CPT_COURSE,
			'post_status'      => [ 'publish', 'draft', 'pending', 'private' ],
			'title'            => self::DEMO_MONTHLY_COURSE_TITLE,
			'posts_per_page'   => 1,
			'fields'           => 'ids',
			'suppress_filters' => false,
		] );

		if ( ! empty( $existing ) ) {
			$existing_id = (int) $existing[0];
			wp_safe_redirect( add_query_arg( [
				'page'        => Menu::SLUG,
				'rks_msg'     => 'monthly_course_exists',
				'rks_course'  => $existing_id,
			], admin_url( 'admin.php' ) ) );
			exit;
		}

		$course_id = $this->seed_monthly_course();

		wp_safe_redirect( add_query_arg( [
			'page'       => Menu::SLUG,
			'rks_msg'    => $course_id ? 'monthly_course_seeded' : 'monthly_course_failed',
			'rks_course' => (int) $course_id,
		], admin_url( 'admin.php' ) ) );
		exit;
	}

	public function seed_monthly_course(): int {
		$course_id = wp_insert_post( [
			'post_type'    => Courses::CPT_COURSE,
			'post_status'  => 'publish',
			'post_title'   => self::DEMO_MONTHLY_COURSE_TITLE,
			'post_content' => 'This is a demo course for testing monthly payment flow. Rs. 500/month x 3 months = Rs. 1500 total.',
			'post_excerpt' => 'Demo offline course for monthly payment testing (Rs. 500 x 3 months).',
		], true );

		if ( is_wp_error( $course_id ) || ! $course_id ) {
			return 0;
		}
		$course_id = (int) $course_id;

		$course_meta = [
			Courses::M_IS_PAID        => 1,
			Courses::M_PAYMENT_MODE   => 'monthly',
			Courses::M_MONTHLY_FEE    => 500.0,
			Courses::M_TOTAL_MONTHS   => 3,
			Courses::M_PRICE          => 1500.0,
			Courses::M_DURATION_VALUE => 3,
			Courses::M_DURATION_UNIT  => 'months',
			Courses::M_LEVEL          => 'beginner',
			Courses::M_INSTRUCTOR     => 'Demo Instructor',
			Courses::M_SHORT_DESC     => 'Test course for monthly payment flow. Rs. 500/month x 3 = Rs. 1500 total.',
			Courses::M_MODE           => 'offline',
			Courses::M_COMPLETION     => 'lessons',
			Courses::M_SEAT_LIMIT     => 5,
			Courses::M_ENROLLED_COUNT => 0,
		];
		foreach ( $course_meta as $key => $value ) {
			update_post_meta( $course_id, $key, $value );
		}

		$lessons = [
			[ 'title' => 'Lesson 1 — Intro',     'order' => 1, 'preview' => 1 ],
			[ 'title' => 'Lesson 2 — Concepts',  'order' => 2, 'preview' => 0 ],
			[ 'title' => 'Lesson 3 — Wrap-up',   'order' => 3, 'preview' => 0 ],
		];
		$lesson_count = 0;
		foreach ( $lessons as $l ) {
			$lesson_id = wp_insert_post( [
				'post_type'   => Courses::CPT_LESSON,
				'post_status' => 'publish',
				'post_title'  => $l['title'],
			], true );
			if ( is_wp_error( $lesson_id ) || ! $lesson_id ) continue;
			$lesson_id = (int) $lesson_id;
			update_post_meta( $lesson_id, Courses::ML_COURSE_ID,    $course_id );
			update_post_meta( $lesson_id, Courses::ML_ORDER,        (int) $l['order'] );
			update_post_meta( $lesson_id, Courses::ML_DURATION_MIN, 15 );
			update_post_meta( $lesson_id, Courses::ML_FREE_PREVIEW, (int) $l['preview'] );
			$lesson_count++;
		}

		update_post_meta( $course_id, Courses::M_LESSON_COUNT, $lesson_count );

		return $course_id;
	}
}
