<?php
/**
 * Main plugin singleton — orchestrates module loading.
 *
 * @package RKSiksha\Core
 */

declare( strict_types=1 );

namespace RKSiksha\Core;

use RKSiksha\Core\Modules\Admissions;
use RKSiksha\Core\Modules\Auth;
use RKSiksha\Core\Modules\Branches;
use RKSiksha\Core\Modules\Courses;
use RKSiksha\Core\Modules\Dashboard;
use RKSiksha\Core\Modules\Emailer;
use RKSiksha\Core\Modules\Franchises;
use RKSiksha\Core\Modules\Leads;
use RKSiksha\Core\Modules\Notices;
use RKSiksha\Core\Admin\FranchisesPage;
use RKSiksha\Core\Admin\ReportsPage;
use RKSiksha\Core\Modules\Certificate;
use RKSiksha\Core\Modules\IdCard;
use RKSiksha\Core\Modules\Lms;
use RKSiksha\Core\Modules\Payments;
use RKSiksha\Core\Modules\StudentDashboard;
use RKSiksha\Core\Modules\Subscriptions;
use RKSiksha\Core\Admin\AdmissionsPage;
use RKSiksha\Core\Admin\CourseBuilderPage;
use RKSiksha\Core\Admin\DevTools;
use RKSiksha\Core\Admin\Menu;
use RKSiksha\Core\Admin\Settings;
use RKSiksha\Core\Admin\HeaderFooterPage;
use RKSiksha\Core\Admin\CertificateDesignerPage;
use RKSiksha\Core\Admin\StudentsPage;
use RKSiksha\Core\Admin\ContactPage;
use RKSiksha\Core\Admin\CounsellingPage;
use RKSiksha\Core\Admin\LegalPage;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Plugin {

	private static ?Plugin $instance = null;

	private bool $booted = false;

	/** @var array<string, object> */
	private array $modules = [];

	private function __construct() {}

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function boot(): void {
		if ( $this->booted ) {
			return;
		}
		$this->booted = true;

		// Always-on modules (lightweight)
		$this->modules['branches'] = new Branches();
		$this->modules['branches']->register();

		$this->modules['courses'] = new Courses();
		$this->modules['courses']->register();

		$this->modules['auth'] = new Auth();
		$this->modules['auth']->register();

		$this->modules['emailer'] = new Emailer();
		$this->modules['emailer']->register();

		$this->modules['payments'] = new Payments();
		$this->modules['payments']->register();

		$this->modules['subscriptions'] = new Subscriptions();
		$this->modules['subscriptions']->register();

		$this->modules['admissions'] = new Admissions();
		$this->modules['admissions']->register();

		$this->modules['dashboard'] = new Dashboard();
		$this->modules['dashboard']->register();

		$this->modules['lms'] = new Lms();
		$this->modules['lms']->register();

		$this->modules['certificate'] = new Certificate();
		$this->modules['certificate']->register();

		$this->modules['idcard'] = new IdCard();
		$this->modules['idcard']->register();

		$this->modules['franchises'] = new Franchises();
		$this->modules['franchises']->register();

		$this->modules['student_dashboard'] = new StudentDashboard();
		$this->modules['student_dashboard']->register();

		$this->modules['leads'] = new Leads();
		$this->modules['leads']->register();

		$this->modules['notices'] = new Notices();
		$this->modules['notices']->register();

		add_action( 'admin_post_nopriv_rks_legal_document', [ LegalPage::class, 'serve_public_document' ] );
		add_action( 'admin_post_rks_legal_document', [ LegalPage::class, 'serve_public_document' ] );

		// Admin-only modules — only load in admin context for performance
		if ( is_admin() ) {
			$this->modules['admin_menu']     = new Menu();
			$this->modules['admin_settings'] = new Settings();
			$this->modules['admin_admissions'] = new AdmissionsPage();
			$this->modules['admin_builder'] = new CourseBuilderPage();
			$this->modules['admin_builder']->register();
			$this->modules['admin_menu']->register();
			$this->modules['admin_settings']->register();

			$this->modules['admin_header_footer'] = new HeaderFooterPage();
			$this->modules['admin_header_footer']->register();

			$this->modules['admin_cert_designer'] = new CertificateDesignerPage();
			$this->modules['admin_cert_designer']->register();

			$this->modules['admin_legal'] = new LegalPage();
			$this->modules['admin_legal']->register();

			$this->modules['admin_admissions']->register();

			$this->modules['admin_franchises'] = new FranchisesPage();
			$this->modules['admin_franchises']->register();

			$this->modules['admin_reports'] = new ReportsPage();
			$this->modules['admin_reports']->register();

			$this->modules['admin_students'] = new StudentsPage();
			$this->modules['admin_students']->register();

			$this->modules['admin_counselling'] = new CounsellingPage();
			$this->modules['admin_counselling']->register();

			$this->modules['admin_contact'] = new ContactPage();
			$this->modules['admin_contact']->register();

			$this->modules['devtools'] = new DevTools();
			$this->modules['devtools']->register();
		}

		// DB version check (lightweight; runs every request but cached via option)
		$this->maybe_upgrade();

		do_action( 'rks_plugin_booted', $this );
	}

	public function module( string $key ): ?object {
		return $this->modules[ $key ] ?? null;
	}

	private function maybe_upgrade(): void {
		$installed = (string) get_option( 'rks_db_version', '0' );
		if ( version_compare( $installed, RKS_DB_VERSION, '<' ) ) {
			Installer::install();
			Roles::install(); // re-register roles + caps on upgrade

			// v5 — reset course catalogue & ensure Gallery page exists
			if ( version_compare( $installed, '5', '<' ) ) {
				$this->reset_course_catalogue();
				$this->ensure_gallery_page();
			}

			// v6 — Legal: create page, import 3 seed PDFs, seed option
			if ( version_compare( $installed, '6', '<' ) ) {
				$this->ensure_legal_page();
				$this->seed_legal_documents();
			}

			// v9 — Replace old support phone number 7017448683 → 8979775664 in all saved options
			if ( version_compare( $installed, '9', '<' ) ) {
				$this->migrate_phone_number();
			}

			// v10 — Production cleanup: delete test transactions, quizzes, students, franchise branches.
			// KEEPS: Courses + lessons + main HQ branch + current super admin.
			if ( version_compare( $installed, '10', '<' ) ) {
				$this->production_cleanup();
			}

			update_option( 'rks_db_version', RKS_DB_VERSION, true );
		}
	}

	/** v5: wipe all rks_course posts + rks_course_cat terms, then reseed. */
	private function reset_course_catalogue(): void {
		// Delete all course posts (and any attached lessons)
		$course_ids = get_posts( [
			'post_type'      => 'rks_course',
			'post_status'    => 'any',
			'numberposts'    => -1,
			'fields'         => 'ids',
		] );
		foreach ( $course_ids as $cid ) {
			wp_delete_post( (int) $cid, true );
		}
		$lesson_ids = get_posts( [
			'post_type'      => 'rks_lesson',
			'post_status'    => 'any',
			'numberposts'    => -1,
			'fields'         => 'ids',
		] );
		foreach ( $lesson_ids as $lid ) {
			wp_delete_post( (int) $lid, true );
		}

		// Delete all category terms
		$terms = get_terms( [
			'taxonomy'   => 'rks_course_cat',
			'hide_empty' => false,
			'fields'     => 'ids',
		] );
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $tid ) {
				wp_delete_term( (int) $tid, 'rks_course_cat' );
			}
		}

		// Reset seed flag + reseed
		delete_option( 'rks_courses_seeded' );
		if ( class_exists( '\\RKSiksha\\Core\\Modules\\Courses' ) ) {
			\RKSiksha\Core\Modules\Courses::seed_samples();
		}
	}

	/** v5: auto-create Gallery WP page if missing. */
	private function ensure_gallery_page(): void {
		$existing = get_page_by_path( 'gallery' );
		if ( $existing && $existing->post_status !== 'trash' ) {
			// Make sure template is set
			if ( get_page_template_slug( $existing->ID ) !== 'page-gallery.php' ) {
				update_post_meta( $existing->ID, '_wp_page_template', 'page-gallery.php' );
			}
			return;
		}
		$page_id = wp_insert_post( [
			'post_title'   => 'Gallery',
			'post_name'    => 'gallery',
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => '',
			'post_author'  => 1,
		] );
		if ( $page_id && ! is_wp_error( $page_id ) ) {
			update_post_meta( $page_id, '_wp_page_template', 'page-gallery.php' );
		}
	}

	/**
	 * v10: Production cleanup. One-shot, idempotent (DB version gate prevents re-run).
	 *
	 * Deletes ALL test data so the site can launch fresh.
	 * KEEPS: courses, lessons, course categories, main HQ branch (type=main), and
	 * the super admin currently performing the upgrade.
	 *
	 * Wiped:
	 *   - All transactions (admissions, payments, enrollments, subscriptions, course_progress)
	 *   - All quizzes (quiz rows, questions, attempts, answers)
	 *   - All certificates, id cards
	 *   - All counselling leads, support tickets, contact entries
	 *   - All franchise applications + franchise branches
	 *   - All non-superadmin users (students, branch admins) including the 4 demo users
	 */
	private function production_cleanup(): void {
	    if ( ! defined( 'EMPTY_TRASH_DAYS' ) ) {
	define( 'EMPTY_TRASH_DAYS', 30 );
}
		global $wpdb;
		$p = $wpdb->prefix;

		// --- 1. Transaction-related tables ---
		$wpdb->query( "DELETE FROM {$p}rks_quiz_answers" );
		$wpdb->query( "DELETE FROM {$p}rks_quiz_attempts" );
		$wpdb->query( "DELETE FROM {$p}rks_quiz_assignments" );
		$wpdb->query( "DELETE FROM {$p}rks_quiz_questions" );
		$wpdb->query( "DELETE FROM {$p}rks_quizzes" );
		$wpdb->query( "DELETE FROM {$p}rks_course_progress" );
		$wpdb->query( "DELETE FROM {$p}rks_course_subscriptions" );
		$wpdb->query( "DELETE FROM {$p}rks_enrollments" );
		$wpdb->query( "DELETE FROM {$p}rks_payments" );
		$wpdb->query( "DELETE FROM {$p}rks_admissions" );
		$wpdb->query( "DELETE FROM {$p}rks_certificates" );
		$wpdb->query( "DELETE FROM {$p}rks_idcards" );
		$wpdb->query( "DELETE FROM {$p}rks_counselling_leads" );
		$wpdb->query( "DELETE FROM {$p}rks_support_tickets" );

		// --- 2. Franchise data ---
		$wpdb->query( "DELETE FROM {$p}rks_franchises" );
		// Delete all franchise branches (keep main HQ branch where type='main')
		$wpdb->query( "DELETE FROM {$p}rks_branches WHERE type != 'main'" );

		// --- 3. Users — keep only super admin(s) and the WordPress 'administrator' role ---
		$users_to_delete = (array) $wpdb->get_col(
			"SELECT u.ID FROM {$wpdb->users} u
			 INNER JOIN {$wpdb->usermeta} m ON m.user_id = u.ID
			 WHERE m.meta_key = '{$wpdb->prefix}capabilities'
			   AND m.meta_value NOT LIKE '%administrator%'
			   AND m.meta_value NOT LIKE '%rks_super_admin%'"
		);
		if ( ! empty( $users_to_delete ) ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
			foreach ( $users_to_delete as $uid ) {
				$uid = (int) $uid;
				if ( $uid <= 0 ) continue;
				if ( $uid === get_current_user_id() ) continue; // never delete self
				wp_delete_user( $uid );
			}
		}

		// --- 4. Clear transient seed flags so future seeds (if dev mode is re-enabled) don't double up ---
		delete_option( 'rks_demo_seeded' );
		delete_option( 'rks_courses_seeded' );
	}

	/**
	 * v9: Replace old support phone number 7017448683 → 8979775664 in saved options.
	 * Handles both `rks_settings` (support_phone) and `rks_hf_settings`
	 * (topbar_phone, footer_phone, social_whatsapp).
	 */
	private function migrate_phone_number(): void {
		$old_digits = '7017448683';
		$new_digits = '8979775664';

		// 1. Main settings
		$rks_settings = (array) get_option( 'rks_settings', [] );
		if ( ! empty( $rks_settings['support_phone'] ) ) {
			$rks_settings['support_phone'] = str_replace( $old_digits, $new_digits, (string) $rks_settings['support_phone'] );
			update_option( 'rks_settings', $rks_settings, true );
		}

		// 2. Header / footer settings
		$rks_hf = (array) get_option( 'rks_hf_settings', [] );
		$changed = false;
		foreach ( [ 'topbar_phone', 'footer_phone', 'social_whatsapp' ] as $key ) {
			if ( ! empty( $rks_hf[ $key ] ) && strpos( (string) $rks_hf[ $key ], $old_digits ) !== false ) {
				$rks_hf[ $key ] = str_replace( $old_digits, $new_digits, (string) $rks_hf[ $key ] );
				$changed = true;
			}
		}
		if ( $changed ) {
			update_option( 'rks_hf_settings', $rks_hf, true );
		}
	}

	/** v6: auto-create Legal WP page if missing. */
	private function ensure_legal_page(): void {
		$existing = get_page_by_path( 'legal' );
		if ( $existing && $existing->post_status !== 'trash' ) {
			if ( get_page_template_slug( $existing->ID ) !== 'page-legal.php' ) {
				update_post_meta( $existing->ID, '_wp_page_template', 'page-legal.php' );
			}
			return;
		}
		$page_id = wp_insert_post( [
			'post_title'   => 'Legal',
			'post_name'    => 'legal',
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => '',
			'post_author'  => 1,
		] );
		if ( $page_id && ! is_wp_error( $page_id ) ) {
			update_post_meta( $page_id, '_wp_page_template', 'page-legal.php' );
		}
	}

	/**
	 * v6: Import the 3 seed PDFs (already placed in uploads/legal/) into the
	 * Media Library and register them as initial documents under the
	 * rks_legal_docs option. Idempotent — skips if option already populated.
	 */
	private function seed_legal_documents(): void {
		// Skip if admin already configured documents.
		$existing = (array) get_option( LegalPage::OPTION_KEY, [] );
		if ( ! empty( $existing ) ) {
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$uploads = wp_get_upload_dir();
		$legal_dir = trailingslashit( $uploads['basedir'] ) . 'legal';

		$seeds = [
			[
				'file'        => 'darpan-id-registration.pdf',
				'title'       => 'DARPAN ID Registration Profile',
				'description' => 'Government of India NGO Darpan portal registration profile — confirms our verified institutional identity.',
			],
			[
				'file'        => 'gallery-portfolio.pdf',
				'title'       => 'PDF Gallery Portfolio',
				'description' => 'Official portfolio document showcasing campus facilities, batches and certified programmes.',
			],
			[
				'file'        => 'udyam-certificate.pdf',
				'title'       => 'UDYAM Certificate with Annexure',
				'description' => 'Ministry of MSME UDYAM registration certificate with full annexure — registered enterprise.',
			],
		];

		$docs = [];
		$order = 1;
		foreach ( $seeds as $seed ) {
			$path = trailingslashit( $legal_dir ) . $seed['file'];
			if ( ! file_exists( $path ) ) {
				continue;
			}

			// Check if already attached (avoid duplicate imports)
			$existing_att = $this->find_attachment_by_filename( $seed['file'] );
			if ( $existing_att > 0 ) {
				$att_id = $existing_att;
			} else {
				$filetype = wp_check_filetype( $seed['file'], null );
				$att_id = wp_insert_attachment( [
					'guid'           => trailingslashit( $uploads['baseurl'] ) . 'legal/' . $seed['file'],
					'post_mime_type' => $filetype['type'] ?: 'application/pdf',
					'post_title'     => $seed['title'],
					'post_content'   => '',
					'post_status'    => 'inherit',
				], $path );
				if ( ! is_wp_error( $att_id ) && $att_id > 0 ) {
					$meta = wp_generate_attachment_metadata( $att_id, $path );
					wp_update_attachment_metadata( $att_id, $meta );
				} else {
					continue;
				}
			}

			$docs[] = [
				'id'          => wp_generate_uuid4(),
				'title'       => $seed['title'],
				'description' => $seed['description'],
				'file_id'     => (int) $att_id,
				'order'       => $order++,
				'enabled'     => true,
			];
		}

		if ( ! empty( $docs ) ) {
			update_option( LegalPage::OPTION_KEY, $docs, true );
		}
	}

	/** Find an attachment by its filename (basename match on _wp_attached_file). */
	private function find_attachment_by_filename( string $filename ): int {
		global $wpdb;
		$like = '%' . $wpdb->esc_like( $filename );
		$row = $wpdb->get_var( $wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s LIMIT 1",
			$like
		) );
		return $row ? (int) $row : 0;
	}

	private function __clone() {}
	public function __wakeup() { throw new \RuntimeException( 'Cannot unserialize singleton' ); }
}
