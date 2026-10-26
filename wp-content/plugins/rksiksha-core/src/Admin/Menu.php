<?php
/**
 * Top-level "Rishikul Shiksha" admin menu + dashboard landing.
 *
 * @package RKSiksha\Core
 */

declare( strict_types=1 );

namespace RKSiksha\Core\Admin;

use RKSiksha\Core\Modules\Branches;
use RKSiksha\Core\Modules\Notices;
use RKSiksha\Core\Roles;
use RKSiksha\Core\Support\DB;
use RKSiksha\Core\Support\Icons;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Menu {

	public const SLUG = 'rks-dashboard';
	public const SLUG_OPERATIONS = 'rks-operations';
	public const SLUG_SUPPORT    = 'rks-support';
	public const SLUG_NETWORK    = 'rks-network';
	public const SLUG_WEBSITE    = 'rks-website';

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
		add_action( 'admin_menu', [ $this, 'reorder_native_content_menus' ], 1000 );
		add_action( 'admin_menu_editor-menu_replaced', [ $this, 'reorder_native_content_menus' ], 1000 );
		add_filter( 'submenu_file', [ $this, 'reorder_native_content_menus_filter' ], 1002, 2 );
		add_filter( 'parent_file', [ $this, 'reorder_native_content_menus_filter' ], 1002, 2 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'wp_dashboard_setup', [ $this, 'register_dashboard_widget' ] );
		add_action( 'admin_post_rks_branch_delete', [ $this, 'handle_branch_delete' ] );
	}

	public function register_menu(): void {
		add_menu_page( 'RK Dashboard', 'RK Dashboard', 'rks_view_branch_dashboard', self::SLUG, [ $this, 'render_dashboard' ], 'dashicons-dashboard', 3 );
		add_submenu_page( self::SLUG, 'Dashboard', 'Dashboard', 'rks_view_branch_dashboard', self::SLUG, [ $this, 'render_dashboard' ] );

		add_menu_page( 'Operations', 'Operations', 'rks_view_branch_dashboard', self::SLUG_OPERATIONS, [ $this, 'render_operations' ], 'dashicons-chart-bar', 7 );
		add_submenu_page( self::SLUG_OPERATIONS, 'Operations Overview', 'Overview', 'rks_view_branch_dashboard', self::SLUG_OPERATIONS, [ $this, 'render_operations' ] );

		add_menu_page( 'Support', 'Support', 'rks_view_global_reports', self::SLUG_SUPPORT, [ $this, 'render_support' ], 'dashicons-sos', 8 );
		add_submenu_page( self::SLUG_SUPPORT, 'Support Overview', 'Overview', 'rks_view_global_reports', self::SLUG_SUPPORT, [ $this, 'render_support' ] );

		add_menu_page( 'Network', 'Network', 'rks_view_all_branches', self::SLUG_NETWORK, [ $this, 'render_network' ], 'dashicons-networking', 9 );
		add_submenu_page( self::SLUG_NETWORK, 'Network Overview', 'Overview', 'rks_view_all_branches', self::SLUG_NETWORK, [ $this, 'render_network' ] );
		add_submenu_page( self::SLUG_NETWORK, 'Branches', 'Branches', 'rks_view_all_branches', self::SLUG . '-branches', [ $this, 'render_branches' ] );

		add_menu_page( 'Website', 'Website', 'manage_options', self::SLUG_WEBSITE, [ $this, 'render_website' ], 'dashicons-admin-site-alt3', 28 );
		add_submenu_page( self::SLUG_WEBSITE, 'Website Overview', 'Overview', 'manage_options', self::SLUG_WEBSITE, [ $this, 'render_website' ] );
		add_submenu_page( self::SLUG_WEBSITE, 'Notices', 'Notices', 'manage_options', 'edit.php?post_type=' . Notices::CPT );
	}

	public function reorder_native_content_menus(): void {
		global $menu;

		if ( empty( $menu ) || ! is_array( $menu ) ) {
			return;
		}

		$settings_position = $this->menu_item_position( 'rks-settings' );
		if ( null === $settings_position ) {
			return;
		}

		$base_slot = (float) $settings_position;
		$content_slugs = [ 'edit.php', 'edit.php?post_type=page', 'upload.php' ];

		$offset = 1;
		$last_content_slot = null;
		$this->remove_menu_item_by_slug( 'rks-wp-menu-separator' );
		$this->remove_menu_item_by_slug( 'rks-wp-menu-label' );

		foreach ( $content_slugs as $slug ) {
			$item = $this->remove_menu_item_by_slug( $slug );
			if ( null === $item ) {
				continue;
			}

			do {
				$slot = number_format( $base_slot + ( $offset * 0.01 ), 2, '.', '' );
				$offset++;
			} while ( isset( $menu[ $slot ] ) );

			$menu[ $slot ] = $item;
			$last_content_slot = (float) $slot;
		}

		if ( null !== $last_content_slot ) {
			$separator_offset = 1;
			do {
				$slot = number_format( $last_content_slot + ( $separator_offset * 0.01 ), 2, '.', '' );
				$separator_offset++;
			} while ( isset( $menu[ $slot ] ) );

			$menu[ $slot ] = [
				'',
				'read',
				'rks-wp-menu-separator',
				'',
				'wp-menu-separator rks-wp-menu-separator',
				'rks-wp-menu-separator',
				'',
			];

			$this->remove_generic_separators_between( (float) $slot, $this->menu_item_position( 'themes.php' ) );
		}

		ksort( $menu, SORT_NUMERIC );
	}

	public function reorder_native_content_menus_filter( $file, $parent_file = '' ) {
		$this->reorder_native_content_menus();
		return $file;
	}

	private function menu_item_position( string $slug ) {
		global $menu;

		foreach ( (array) $menu as $position => $item ) {
			if ( isset( $item[2] ) && $item[2] === $slug ) {
				return $position;
			}
		}

		return null;
	}

	/**
	 * @return array<int, mixed>|null
	 */
	private function remove_menu_item_by_slug( string $slug ): ?array {
		global $menu;

		foreach ( (array) $menu as $position => $item ) {
			if ( isset( $item[2] ) && $item[2] === $slug ) {
				unset( $menu[ $position ] );
				return $item;
			}
		}

		return null;
	}

	private function remove_generic_separators_between( float $after, $before ): void {
		global $menu;

		$before = null === $before ? null : (float) $before;
		foreach ( (array) $menu as $position => $item ) {
			$numeric_position = (float) $position;
			$slug            = isset( $item[2] ) ? (string) $item[2] : '';
			$class           = isset( $item[4] ) ? (string) $item[4] : '';

			if ( $numeric_position <= $after || ( null !== $before && $numeric_position >= $before ) ) {
				continue;
			}

			if ( ! in_array( $slug, [ 'rks-wp-menu-separator', 'rks-wp-menu-label' ], true ) && str_contains( $class, 'wp-menu-separator' ) ) {
				unset( $menu[ $position ] );
			}
		}
	}

	public function enqueue_assets( string $hook ): void {
		if ( current_user_can( 'rks_view_branch_dashboard' ) || current_user_can( 'manage_options' ) ) {
			$skin_path = RKS_PLUGIN_DIR . 'assets/css/admin-skin.css';
			$skin_ver  = file_exists( $skin_path ) ? (string) filemtime( $skin_path ) : RKS_VERSION;
			wp_enqueue_style( 'rks-admin-skin', RKS_PLUGIN_URL . 'assets/css/admin-skin.css', [], $skin_ver );
		}

		if ( strpos( $hook, self::SLUG ) === false && strpos( $hook, 'rks-' ) === false ) return;
		$admin_path = RKS_PLUGIN_DIR . 'assets/css/admin.css';
		$admin_ver  = file_exists( $admin_path ) ? (string) filemtime( $admin_path ) : RKS_VERSION;
		wp_enqueue_style( 'rks-admin', RKS_PLUGIN_URL . 'assets/css/admin.css', [ 'rks-admin-skin' ], $admin_ver );
	}

	public function register_dashboard_widget(): void {
		if ( ! current_user_can( 'rks_view_branch_dashboard' ) ) {
			return;
		}

		wp_add_dashboard_widget(
			'rks_quick_dashboard',
			'Rishikul Shiksha Quick Desk',
			[ $this, 'render_dashboard_widget' ]
		);
	}

	public function render_dashboard_widget(): void {
		$links = [
			[ 'Dashboard', 'Branch, revenue, students', admin_url( 'admin.php?page=' . self::SLUG ) ],
			[ 'Courses', 'Catalogue and builder', admin_url( 'edit.php?post_type=rks_course' ) ],
			[ 'Exams', 'Questions and attempts', admin_url( 'admin.php?page=rks-exams' ) ],
			[ 'Certificates', 'Issued, offline, prefixes, templates', admin_url( 'admin.php?page=rks-certificates' ) ],
			[ 'Support', 'Counselling and queries', admin_url( 'admin.php?page=' . self::SLUG_SUPPORT ) ],
			[ 'Operations', 'Admissions and payments', admin_url( 'admin.php?page=' . self::SLUG_OPERATIONS ) ],
		];

		echo '<div class="rks-wp-dashboard-widget">';
		foreach ( $links as $link ) {
			printf(
				'<a href="%s"><span>%s</span><small>%s</small></a>',
				esc_url( $link[2] ),
				esc_html( $link[0] ),
				esc_html( $link[1] )
			);
		}
		echo '</div>';
	}

	public function render_operations(): void {
		if ( ! current_user_can( 'rks_view_branch_dashboard' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'rksiksha' ) );
		}

		$this->render_section_page(
			'Operations',
			'Admissions, payments, students, and reports in one place.',
			[
				[ 'Admissions', 'Review applications and approve enrollments.', 'rks_approve_admission', admin_url( 'admin.php?page=rks-admissions' ) ],
				[ 'Payments', 'Verify UTRs, screenshots, and payment status.', 'rks_verify_payment', admin_url( 'admin.php?page=rks-payments' ) ],
				[ 'Students', 'Open student profiles, courses, certificates, and attempts.', 'rks_view_all_admissions', admin_url( 'admin.php?page=rks-students' ) ],
				[ 'Reports', 'System-wide analytics and CSV exports.', 'rks_view_global_reports', admin_url( 'admin.php?page=rks-reports' ) ],
			]
		);
	}

	public function render_support(): void {
		if ( ! current_user_can( 'rks_view_global_reports' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'rksiksha' ) );
		}

		$this->render_section_page(
			'Support',
			'Student enquiries, counselling leads, and contact queries.',
			[
				[ 'University Admissions', 'University and admission form submissions.', 'rks_view_global_reports', admin_url( 'admin.php?page=rks-counselling' ) ],
				[ 'Contact Queries', 'General contact form messages and follow-ups.', 'rks_manage_settings', admin_url( 'admin.php?page=rks-contact-leads' ) ],
			]
		);
	}

	public function render_network(): void {
		if ( ! current_user_can( 'rks_view_all_branches' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'rksiksha' ) );
		}

		$this->render_section_page(
			'Network',
			'Branches and franchise applications.',
			[
				[ 'Branches', 'Main branch and approved franchise locations.', 'rks_view_all_branches', admin_url( 'admin.php?page=' . self::SLUG . '-branches' ) ],
				[ 'Franchises', 'Review franchise applications and onboarding.', 'rks_approve_franchise', admin_url( 'admin.php?page=' . FranchisesPage::SLUG ) ],
			]
		);
	}

	public function render_website(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'rksiksha' ) );
		}

		$this->render_section_page(
			'Website',
			'Header, footer, gallery, legal documents, and public site controls.',
			[
				[ 'Header', 'Logo, top bar, and header content.', 'manage_options', admin_url( 'admin.php?page=rks-hf' ) ],
				[ 'Footer', 'Footer branding and contact blocks.', 'manage_options', admin_url( 'admin.php?page=rks-hf-footer' ) ],
				[ 'Gallery', 'Manage public website photos.', 'manage_options', admin_url( 'admin.php?page=rks-gallery' ) ],
				[ 'Notices', 'Publish homepage notices with links and attachments.', 'manage_options', admin_url( 'edit.php?post_type=' . Notices::CPT ) ],
				[ 'Legal', 'Upload and organize public legal documents.', 'manage_options', admin_url( 'admin.php?page=rks-legal' ) ],
				[ 'Visit Site', 'Open the public website.', 'read', home_url( '/' ) ],
			]
		);
	}

	/**
	 * @param array<int, array{0:string,1:string,2:string,3:string}> $links
	 */
	private function render_section_page( string $title, string $description, array $links ): void {
		?>
		<div class="wrap rks-admin-wrap">
			<header class="rks-page-hero">
				<div class="rks-page-hero__main">
					<div class="rks-page-hero__icon"><?php echo Icons::svg( 'grid', 24 ); ?></div>
					<div>
						<small>Organized Workspace</small>
						<h1><?php echo esc_html( $title ); ?></h1>
						<p><?php echo esc_html( $description ); ?></p>
					</div>
				</div>
			</header>

			<div class="rks-admin-card">
				<h2><?php echo esc_html( $title ); ?> Shortcuts</h2>
				<div class="rks-super-quick">
					<?php
					$shown = 0;
					foreach ( $links as $link ) :
						if ( ! current_user_can( $link[2] ) ) {
							continue;
						}
						$shown++;
						?>
						<a href="<?php echo esc_url( $link[3] ); ?>">
							<strong><?php echo esc_html( $link[0] ); ?></strong>
							<small><?php echo esc_html( $link[1] ); ?></small>
						</a>
					<?php endforeach; ?>
					<?php if ( 0 === $shown ) : ?>
						<p>You do not have access to any pages in this section.</p>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	public function render_dashboard(): void {
		if ( ! current_user_can( 'rks_view_branch_dashboard' ) ) wp_die( esc_html__( 'Insufficient permissions.', 'rksiksha' ) );

		$user      = wp_get_current_user();
		$is_super  = current_user_can( 'rks_view_global_reports' );
		$branches  = new Branches();

		// Handle dismiss for initial password
		if ( isset( $_POST['rks_dismiss_initial_pw'] ) && check_admin_referer( 'rks_dismiss_initial_pw' ) ) {
			delete_option( 'rks_main_admin_initial_password' );
			echo '<script>location.replace(location.pathname + location.search);</script>';
			return;
		}

		if ( $is_super ) {
			$this->render_super_dashboard( $user, $branches );
		} else {
			$this->render_branch_dashboard( $user, $branches );
		}
	}

	/* ================================================================== */
	/* SUPER ADMIN DASHBOARD                                                */
	/* ================================================================== */

	private function render_super_dashboard( \WP_User $user, Branches $branches ): void {
		global $wpdb;
		$adm = DB::table( 'admissions' );
		$pay = DB::table( 'payments' );
		$enr = DB::table( 'enrollments' );
		$bch = DB::table( 'branches' );
		$frn = DB::table( 'franchises' );
		$cer = DB::table( 'certificates' );

		$settings = (array) get_option( 'rks_settings', [] );
		$currency = (string) ( $settings['currency_symbol'] ?? '₹' );

		// Top-level stats
		$total_branches    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $bch" );
		$total_franchises  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $bch WHERE type='franchise'" );
		$total_students    = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT user_id) FROM $enr WHERE user_id > 0" );
		$total_admissions  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $adm" );
		$total_revenue     = (float) $wpdb->get_var( "SELECT COALESCE(SUM(amount),0) FROM $pay WHERE status='approved'" );
		$revenue_30d       = (float) $wpdb->get_var( "SELECT COALESCE(SUM(amount),0) FROM $pay WHERE status='approved' AND verified_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)" );
		$total_certs       = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $cer WHERE status='valid'" );

		// Pending action items
		$pending_admissions = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $adm WHERE status IN ('pending','payment_review')" );
		$pending_payments   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $pay WHERE status='pending'" );
		$pending_franchise  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $frn WHERE status='pending'" );

		// Recent activity (latest 5 of each)
		$recent_adm = $wpdb->get_results( "SELECT id, full_name, course_title, status, branch_id, created_at FROM $adm ORDER BY id DESC LIMIT 5", ARRAY_A );
		$recent_frn = $wpdb->get_results( "SELECT id, applicant_name, city, status, created_at FROM $frn ORDER BY id DESC LIMIT 5", ARRAY_A );

		// Last 14-day admission spark
		$daily_rows = $wpdb->get_results( "SELECT DATE(created_at) AS d, COUNT(*) AS n FROM $adm WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY) GROUP BY DATE(created_at)", ARRAY_A );
		$daily_map = [];
		foreach ( $daily_rows as $r ) $daily_map[ $r['d'] ] = (int) $r['n'];
		$daily_max = $daily_map ? max( $daily_map ) : 0;

		$msg       = isset( $_GET['rks_msg'] )   ? sanitize_key( wp_unslash( (string) $_GET['rks_msg'] ) ) : '';
		$msg_count = isset( $_GET['rks_count'] ) ? (int) $_GET['rks_count'] : 0;
		?>
		<div class="wrap rks-admin-wrap">

			<header class="rks-super-hero">
				<div>
					<small>Super Admin Dashboard</small>
					<h1>Hello, <?php echo esc_html( $user->display_name ?: $user->user_login ); ?></h1>
					<p>Here's the system-wide picture across <strong><?php echo (int) $total_branches; ?></strong> branches and <strong><?php echo number_format( $total_students ); ?></strong> students.</p>
				</div>
				<div class="rks-super-hero__actions">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . ReportsPage::SLUG ) ); ?>" class="button button-primary"><?php echo Icons::svg( 'sparkle', 14 ); ?> Open Reports</a>
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" class="button">View Site</a>
				</div>
			</header>

			<?php
			$msg_course = isset( $_GET['rks_course'] ) ? (int) $_GET['rks_course'] : 0;
			?>
			<?php if ( 'seeded' === $msg ) : ?>
				<div class="notice notice-success is-dismissible"><p>✓ Demo users ready. <strong><?php echo (int) $msg_count; ?></strong> account(s) created or updated. Go to <a href="<?php echo esc_url( home_url( '/login/' ) ); ?>" target="_blank">/login/</a> and use the Quick Login panel.</p></div>
			<?php elseif ( 'cleared' === $msg ) : ?>
				<div class="notice notice-warning is-dismissible"><p>Demo users cleared (<?php echo (int) $msg_count; ?> removed). The Demo Franchise branch was also removed.</p></div>
			<?php elseif ( 'monthly_course_seeded' === $msg && $msg_course ) : ?>
				<div class="notice notice-success is-dismissible"><p>Demo monthly course created (course #<?php echo (int) $msg_course; ?>). <a href="<?php echo esc_url( get_permalink( $msg_course ) ); ?>" target="_blank">View course</a> &middot; <a href="<?php echo esc_url( get_edit_post_link( $msg_course ) ); ?>">Edit</a></p></div>
			<?php elseif ( 'monthly_course_exists' === $msg && $msg_course ) : ?>
				<div class="notice notice-info is-dismissible"><p>Demo monthly course already exists (course #<?php echo (int) $msg_course; ?>). <a href="<?php echo esc_url( get_permalink( $msg_course ) ); ?>" target="_blank">View</a> &middot; <a href="<?php echo esc_url( get_edit_post_link( $msg_course ) ); ?>">Edit</a></p></div>
			<?php elseif ( 'monthly_course_failed' === $msg ) : ?>
				<div class="notice notice-error is-dismissible"><p>Could not create the demo monthly course. Check error logs.</p></div>
			<?php endif; ?>

			<!-- Action required alerts -->
			<?php if ( $pending_admissions || $pending_payments || $pending_franchise ) : ?>
				<div class="rks-super-alerts">
					<?php if ( $pending_admissions ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=rks-admissions&status=pending' ) ); ?>" class="rks-super-alert">
							<span class="rks-super-alert__num"><?php echo (int) $pending_admissions; ?></span>
							<span>Pending Admissions <small>Awaiting your review →</small></span>
						</a>
					<?php endif; ?>
					<?php if ( $pending_payments ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=rks-payments&status=pending' ) ); ?>" class="rks-super-alert">
							<span class="rks-super-alert__num"><?php echo (int) $pending_payments; ?></span>
							<span>Pending Payments <small>UTR + screenshot to verify →</small></span>
						</a>
					<?php endif; ?>
					<?php if ( $pending_franchise ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . FranchisesPage::SLUG . '&status=pending' ) ); ?>" class="rks-super-alert">
							<span class="rks-super-alert__num"><?php echo (int) $pending_franchise; ?></span>
							<span>Franchise Applications <small>Approve to onboard new partners →</small></span>
						</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<!-- Stat tiles -->
			<div class="rks-super-stats">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::SLUG . '-branches' ) ); ?>" class="rks-super-tile">
					<span class="rks-super-tile__icon"><?php echo Icons::svg( 'building', 22 ); ?></span>
					<div>
						<small>Branches</small>
						<strong><?php echo number_format( $total_branches ); ?></strong>
						<em><?php echo (int) $total_franchises; ?> franchise · <?php echo $total_branches - $total_franchises; ?> main</em>
					</div>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . ReportsPage::SLUG ) ); ?>" class="rks-super-tile">
					<span class="rks-super-tile__icon"><?php echo Icons::svg( 'users', 22 ); ?></span>
					<div>
						<small>Total Students</small>
						<strong><?php echo number_format( $total_students ); ?></strong>
						<em><?php echo number_format( $total_admissions ); ?> admissions</em>
					</div>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=rks-payments' ) ); ?>" class="rks-super-tile rks-super-tile--accent">
					<span class="rks-super-tile__icon"><?php echo Icons::svg( 'wallet', 22 ); ?></span>
					<div>
						<small>Total Revenue</small>
						<strong><?php echo esc_html( $currency . number_format( $total_revenue, 0 ) ); ?></strong>
						<em>+<?php echo esc_html( $currency . number_format( $revenue_30d, 0 ) ); ?> last 30d</em>
					</div>
				</a>
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=rks_course' ) ); ?>" class="rks-super-tile">
					<span class="rks-super-tile__icon"><?php echo Icons::svg( 'cap', 22 ); ?></span>
					<div>
						<small>Courses</small>
						<strong><?php echo number_format( (int) ( wp_count_posts( 'rks_course' )->publish ?? 0 ) ); ?></strong>
						<em><?php echo number_format( $total_certs ); ?> certificates</em>
					</div>
				</a>
			</div>

			<!-- 2-column: trend + activity -->
			<div class="rks-super-grid">
				<div class="rks-admin-card">
					<h2>Admissions — Last 14 Days</h2>
					<?php if ( $daily_max <= 0 ) : ?>
						<em>No admissions in the last 14 days.</em>
					<?php else : ?>
						<div class="rks-rep-spark" style="height:120px;">
							<?php
							$today = strtotime( 'today' );
							$start = strtotime( '-13 days' );
							for ( $t = $start; $t <= $today; $t += DAY_IN_SECONDS ) :
								$date = date( 'Y-m-d', $t );
								$n    = (int) ( $daily_map[ $date ] ?? 0 );
								$h    = $daily_max ? (int) round( ( $n / $daily_max ) * 100 ) : 0;
							?>
								<div class="rks-rep-spark__bar" title="<?php echo esc_attr( $date . ': ' . $n ); ?>">
									<div style="height: <?php echo (int) $h; ?>%;"></div>
									<small><?php echo esc_html( date( 'd', $t ) ); ?></small>
								</div>
							<?php endfor; ?>
						</div>
					<?php endif; ?>
				</div>

				<div class="rks-admin-card">
					<h2>Recent Admissions</h2>
					<?php if ( empty( $recent_adm ) ) : ?>
						<em>No admissions yet.</em>
					<?php else : ?>
						<ul class="rks-super-feed">
							<?php foreach ( $recent_adm as $r ) : ?>
								<li>
									<strong><?php echo esc_html( $r['full_name'] ); ?></strong>
									<small><?php echo esc_html( $r['course_title'] ); ?></small>
									<span class="rks-status rks-status--<?php echo esc_attr( $r['status'] ); ?>"><?php echo esc_html( str_replace( '_', ' ', $r['status'] ) ); ?></span>
									<time><?php echo esc_html( human_time_diff( strtotime( (string) $r['created_at'] ), current_time( 'timestamp' ) ) ); ?> ago</time>
								</li>
							<?php endforeach; ?>
						</ul>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=rks-admissions' ) ); ?>">View all admissions →</a>
					<?php endif; ?>
				</div>
			</div>

			<!-- Recent franchise apps -->
			<?php if ( ! empty( $recent_frn ) ) : ?>
			<div class="rks-admin-card">
				<h2>Recent Franchise Applications</h2>
				<ul class="rks-super-feed">
					<?php foreach ( $recent_frn as $f ) : ?>
						<li>
							<strong><?php echo esc_html( $f['applicant_name'] ); ?></strong>
							<small>City: <?php echo esc_html( $f['city'] ); ?></small>
							<span class="rks-status rks-status--<?php echo esc_attr( $f['status'] ); ?>"><?php echo esc_html( $f['status'] ); ?></span>
							<time><?php echo esc_html( human_time_diff( strtotime( (string) $f['created_at'] ), current_time( 'timestamp' ) ) ); ?> ago</time>
						</li>
					<?php endforeach; ?>
				</ul>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . FranchisesPage::SLUG ) ); ?>">All applications →</a>
			</div>
			<?php endif; ?>

			<!-- Quick action grid -->
			<div class="rks-admin-card">
				<h2>Quick Actions</h2>
				<div class="rks-super-quick">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=rks-admissions' ) ); ?>"><strong>Review Admissions</strong><small>Approve / reject student applications</small></a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . FranchisesPage::SLUG ) ); ?>"><strong>Franchise Applications</strong><small>Onboard new franchise partners</small></a>
					<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=rks_course' ) ); ?>"><strong>Add New Course</strong><small>Create a course for the catalog</small></a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=rks-settings' ) ); ?>"><strong>Settings</strong><small>UPI QR, branding, prefixes</small></a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . ReportsPage::SLUG ) ); ?>"><strong>Reports & Exports</strong><small>Analytics + CSV downloads</small></a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::SLUG . '-branches' ) ); ?>"><strong>All Branches</strong><small>Main + franchise branches</small></a>
				</div>
			</div>

			<?php if ( RKS_DEV_MODE ) $this->render_dev_panel(); ?>

			<?php $this->render_initial_password_card(); ?>
		</div>
		<?php
	}

	/* ================================================================== */
	/* BRANCH ADMIN (limited) DASHBOARD                                     */
	/* ================================================================== */

	private function render_branch_dashboard( \WP_User $user, Branches $branches ): void {
		$user_branch_id = (int) get_user_meta( $user->ID, 'rks_branch_id', true );
		$current_branch = $user_branch_id ? $branches->get( $user_branch_id ) : null;
		?>
		<div class="wrap rks-admin-wrap">
			<header class="rks-super-hero">
				<div>
					<small>Branch Admin</small>
					<h1>Hello, <?php echo esc_html( $user->display_name ?: $user->user_login ); ?></h1>
					<p>Manage your branch operations below or use the dedicated frontend dashboard.</p>
				</div>
				<div class="rks-super-hero__actions">
					<a href="<?php echo esc_url( home_url( '/branch-dashboard/' ) ); ?>" target="_blank" class="button button-primary"><?php echo Icons::svg( 'arrow-right', 12 ); ?> Open Branch Dashboard</a>
				</div>
			</header>

			<?php if ( $current_branch ) : ?>
			<div class="rks-detail-grid">
				<div>
					<div class="rks-admin-card">
						<div class="rks-section-head" style="margin-top:0;">
							<?php echo Icons::chip( 'building', 'mint' ); ?>
							<h2>Your Branch</h2>
						</div>
						<dl class="rks-meta-list">
							<li><dt>Name</dt><dd><strong><?php echo esc_html( (string) $current_branch['name'] ); ?></strong></dd></li>
							<li><dt>City</dt><dd><?php echo esc_html( (string) $current_branch['city'] ); ?></dd></li>
							<li><dt>Email</dt><dd><?php echo esc_html( (string) $current_branch['email'] ); ?></dd></li>
							<li><dt>Phone</dt><dd><?php echo esc_html( (string) $current_branch['phone'] ); ?></dd></li>
							<li><dt>Type</dt><dd><span class="rks-tag rks-tag--<?php echo esc_attr( (string) $current_branch['type'] ); ?>"><?php echo esc_html( (string) $current_branch['type'] ); ?></span></dd></li>
						</dl>
					</div>
				</div>
				<div>
					<div class="rks-admin-card">
						<div class="rks-section-head" style="margin-top:0;">
							<?php echo Icons::chip( 'arrow-right', 'red' ); ?>
							<h2>Quick Actions</h2>
						</div>
						<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=rks-admissions' ) ); ?>" class="button button-primary" style="width:100%;margin-bottom:6px;"><?php echo Icons::svg( 'inbox', 12 ); ?> Admissions Queue</a></p>
						<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=rks-payments' ) ); ?>" class="button" style="width:100%;margin-bottom:6px;"><?php echo Icons::svg( 'wallet', 12 ); ?> Payments Audit</a></p>
						<p><a href="<?php echo esc_url( home_url( '/branch-dashboard/' ) ); ?>" target="_blank" class="button" style="width:100%;"><?php echo Icons::svg( 'home', 12 ); ?> Frontend Dashboard</a></p>
					</div>
				</div>
			</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/* ================================================================== */
	/* PARTIALS                                                             */
	/* ================================================================== */

	private function render_dev_panel(): void {
		$dev = new DevTools();
		$status = $dev->seed_status();
		?>
		<div class="rks-admin-card rks-dev-panel">
			<h2><?php echo Icons::svg( 'tools', 18 ); ?> Dev Tools <small>— Demo Users</small></h2>
			<p>Quick-seed dummy users for each role so you can switch between perspectives during development. The login page (<code>/login/</code>) will show one-click autofill buttons for these accounts.</p>

			<table class="widefat" style="margin:10px 0 14px;">
				<thead><tr><th>Role</th><th>Username</th><th>Status</th></tr></thead>
				<tbody>
					<?php foreach ( $status as $u ) : ?>
						<tr>
							<td><?php echo esc_html( $u['label'] ); ?></td>
							<td><code><?php echo esc_html( $u['login'] ); ?></code></td>
							<td>
								<?php if ( $u['exists'] ) : ?>
									<span class="rks-tag rks-tag--main">Ready · #<?php echo (int) $u['user_id']; ?></span>
								<?php else : ?>
									<span class="rks-tag">Not seeded</span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<p>Default password for all demo users: <code><?php echo esc_html( DevTools::DEMO_PASSWORD ); ?></code></p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-right:8px;">
				<?php wp_nonce_field( 'rks_seed_demo' ); ?>
				<input type="hidden" name="action" value="rks_seed_demo">
				<button class="button button-primary">Seed / Refresh Demo Users</button>
			</form>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;">
				<?php wp_nonce_field( 'rks_clear_demo' ); ?>
				<input type="hidden" name="action" value="rks_clear_demo">
				<button class="button button-link-delete" onclick="return confirm('Remove all demo users (except mainadmin)?');">Clear Demo Users</button>
			</form>

			<hr style="margin:18px 0;">

			<h3 style="margin:0 0 6px;"><?php echo Icons::svg( 'book-open', 14 ); ?> Demo Monthly Course</h3>
			<p style="margin:0 0 10px;"><small>Creates one offline course at Rs. 500/month x 3 months with 5-seat cap and 3 demo lessons. Use this to test the monthly payment flow.</small></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
				<?php wp_nonce_field( 'rks_devtools_seed_monthly_course' ); ?>
				<input type="hidden" name="action" value="rks_devtools_seed_monthly_course">
				<button class="button button-primary"><?php echo Icons::svg( 'plus', 12 ); ?> Seed Demo Monthly Course</button>
			</form>

			<p><small>Disable this panel by setting <code>define( 'RKS_DEV_MODE', false );</code> in <code>rksiksha-core.php</code>.</small></p>
		</div>
		<?php
	}

	private function render_initial_password_card(): void {
		$initial_pw = get_option( 'rks_main_admin_initial_password' );
		if ( ! $initial_pw ) return;
		$main_admin_id = (int) get_option( 'rks_main_admin_user_id' );
		$main_admin    = $main_admin_id ? get_userdata( $main_admin_id ) : null;
		?>
		<div class="rks-admin-card rks-admin-card--alert">
			<h2><?php echo Icons::svg( 'alert', 18 ); ?> Initial Main Branch Admin Credentials</h2>
			<p>Save these credentials securely, then dismiss this notice.</p>
			<table class="widefat">
				<tbody>
					<tr><th>Username</th><td><code><?php echo esc_html( $main_admin ? $main_admin->user_login : 'mainadmin' ); ?></code></td></tr>
					<tr><th>Email</th><td><code><?php echo esc_html( $main_admin ? $main_admin->user_email : 'mainadmin@rishikulshiksha.com' ); ?></code></td></tr>
					<tr><th>Password</th><td><code><?php echo esc_html( (string) $initial_pw ); ?></code></td></tr>
				</tbody>
			</table>
			<form method="post" style="margin-top:12px;">
				<?php wp_nonce_field( 'rks_dismiss_initial_pw' ); ?>
				<button class="button button-primary" name="rks_dismiss_initial_pw" value="1">Dismiss</button>
			</form>
		</div>
		<?php
	}

	public function render_branches(): void {
		if ( ! current_user_can( 'rks_view_all_branches' ) ) wp_die( esc_html__( 'Insufficient permissions.', 'rksiksha' ) );
		$branches = new Branches();
		$rows     = $branches->list( [ 'limit' => 100 ] );
		$msg      = isset( $_GET['rks_msg'] ) ? sanitize_key( wp_unslash( (string) $_GET['rks_msg'] ) ) : '';
		?>
		<div class="wrap rks-admin-wrap">
			<header class="rks-page-hero">
				<div class="rks-page-hero__main">
					<div class="rks-page-hero__icon"><?php echo Icons::svg( 'building', 24 ); ?></div>
					<div>
						<small>Network</small>
						<h1>All Branches</h1>
						<p>Main HQ branch and all approved franchise branches across cities.</p>
					</div>
				</div>
				<div class="rks-page-hero__actions">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . FranchisesPage::SLUG ) ); ?>" class="button button-primary"><?php echo Icons::svg( 'plus', 14 ); ?> Franchise Applications</a>
				</div>
			</header>

			<?php if ( $msg ) : ?>
				<div class="notice notice-<?php echo $msg === 'error' || $msg === 'protected' ? 'error' : 'success'; ?> is-dismissible">
					<p><?php echo esc_html( 'protected' === $msg ? 'Main branch cannot be deleted.' : ucfirst( str_replace( '_', ' ', $msg ) ) ); ?></p>
				</div>
			<?php endif; ?>

			<table class="widefat striped">
				<thead><tr><th>ID</th><th>Type</th><th>Name</th><th>City</th><th>Owner User</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
				<tbody>
					<?php if ( empty( $rows ) ) : ?>
						<tr><td colspan="8"><em>No branches yet.</em></td></tr>
					<?php else : foreach ( $rows as $r ) : ?>
						<tr>
							<td><?php echo (int) $r['id']; ?></td>
							<td><span class="rks-tag rks-tag--<?php echo esc_attr( (string) $r['type'] ); ?>"><?php echo esc_html( (string) $r['type'] ); ?></span></td>
							<td><strong><?php echo esc_html( (string) $r['name'] ); ?></strong></td>
							<td><?php echo esc_html( (string) $r['city'] ); ?></td>
							<td>
								<?php
								$uid  = (int) $r['owner_user_id'];
								$ownr = $uid ? get_userdata( $uid ) : null;
								echo $ownr ? esc_html( $ownr->user_login . ' (#' . $uid . ')' ) : '—';
								?>
							</td>
							<td><?php echo esc_html( (string) $r['status'] ); ?></td>
							<td><?php echo esc_html( (string) $r['created_at'] ); ?></td>
							<td>
								<?php if ( current_user_can( 'rks_manage_branches' ) && (string) $r['type'] !== Branches::TYPE_MAIN ) : ?>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;">
										<?php wp_nonce_field( 'rks_branch_delete' ); ?>
										<input type="hidden" name="action" value="rks_branch_delete">
										<input type="hidden" name="branch_id" value="<?php echo (int) $r['id']; ?>">
										<button type="submit" class="button button-small button-link-delete" onclick="return confirm('Permanently delete this branch record? Linked admissions, payments, students, and franchise application records will not be removed.');"><?php echo Icons::svg( 'trash', 12 ); ?> Delete</button>
									</form>
								<?php else : ?>
									<em>Protected</em>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public function handle_branch_delete(): void {
		check_admin_referer( 'rks_branch_delete' );
		if ( ! current_user_can( 'rks_manage_branches' ) ) {
			wp_die( 'Forbidden', 'Forbidden', [ 'response' => 403 ] );
		}

		$id = (int) ( $_POST['branch_id'] ?? 0 );
		if ( $id <= 0 ) {
			wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG . '-branches', 'rks_msg' => 'error' ], admin_url( 'admin.php' ) ) );
			exit;
		}

		$branches = new Branches();
		$row      = $branches->get( $id );
		if ( ! $row ) {
			wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG . '-branches', 'rks_msg' => 'not_found' ], admin_url( 'admin.php' ) ) );
			exit;
		}

		if ( (string) ( $row['type'] ?? '' ) === Branches::TYPE_MAIN ) {
			wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG . '-branches', 'rks_msg' => 'protected' ], admin_url( 'admin.php' ) ) );
			exit;
		}

		$deleted = $branches->delete( $id );
		wp_safe_redirect( add_query_arg(
			[ 'page' => self::SLUG . '-branches', 'rks_msg' => $deleted ? 'deleted' : 'error' ],
			admin_url( 'admin.php' )
		) );
		exit;
	}
}
