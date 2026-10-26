<?php
/**
 * Admin: System-wide Reports & Analytics (super admin only).
 *
 * Pure CSS/HTML charts — no external JS dependency. CSV export for admissions/payments.
 *
 * @package RKSiksha\Core
 */

declare( strict_types=1 );

namespace RKSiksha\Core\Admin;

use RKSiksha\Core\Support\DB;
use RKSiksha\Core\Support\Icons;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ReportsPage {

	public const SLUG = 'rks-reports';

	/** @var array<string,array{from:string,to:string,label:string}> */
	private array $ranges = [];

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'register_menu' ], 40 );
		add_action( 'admin_post_rks_export_csv', [ $this, 'handle_export' ] );
	}

	public function register_menu(): void {
		add_submenu_page(
			Menu::SLUG_OPERATIONS,
			'Reports',
			'Reports',
			'rks_view_global_reports',
			self::SLUG,
			[ $this, 'render' ]
		);
	}

	private function build_ranges(): void {
		$today = date( 'Y-m-d' );
		$this->ranges = [
			'7d'   => [ 'from' => date( 'Y-m-d', strtotime( '-6 days' ) ),  'to' => $today, 'label' => 'Last 7 days' ],
			'30d'  => [ 'from' => date( 'Y-m-d', strtotime( '-29 days' ) ), 'to' => $today, 'label' => 'Last 30 days' ],
			'90d'  => [ 'from' => date( 'Y-m-d', strtotime( '-89 days' ) ), 'to' => $today, 'label' => 'Last 90 days' ],
			'365d' => [ 'from' => date( 'Y-m-d', strtotime( '-364 days' ) ), 'to' => $today, 'label' => 'Last 12 months' ],
			'all'  => [ 'from' => '2000-01-01', 'to' => $today, 'label' => 'All time' ],
		];
	}

	public function render(): void {
		if ( ! current_user_can( 'rks_view_global_reports' ) ) wp_die( 'Forbidden' );

		$this->build_ranges();

		$range = isset( $_GET['range'] ) ? sanitize_key( wp_unslash( (string) $_GET['range'] ) ) : '30d';
		if ( ! isset( $this->ranges[ $range ] ) && $range !== 'custom' ) $range = '30d';

		if ( $range === 'custom' ) {
			$from = isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['from'] ) ) : date( 'Y-m-d', strtotime( '-29 days' ) );
			$to   = isset( $_GET['to'] )   ? sanitize_text_field( wp_unslash( (string) $_GET['to'] ) )   : date( 'Y-m-d' );
		} else {
			$from = $this->ranges[ $range ]['from'];
			$to   = $this->ranges[ $range ]['to'];
		}

		// Validate date format
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $from ) ) $from = date( 'Y-m-d', strtotime( '-29 days' ) );
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $to ) )   $to   = date( 'Y-m-d' );

		global $wpdb;
		$adm = DB::table( 'admissions' );
		$pay = DB::table( 'payments' );
		$enr = DB::table( 'enrollments' );
		$bch = DB::table( 'branches' );
		$frn = DB::table( 'franchises' );
		$cer = DB::table( 'certificates' );

		$settings = (array) get_option( 'rks_settings', [] );
		$currency = (string) ( $settings['currency_symbol'] ?? '₹' );

		$from_dt = $from . ' 00:00:00';
		$to_dt   = $to   . ' 23:59:59';

		// ── KPIs (in window + lifetime) ─────────────────────────────────────
		$kpi = [
			'revenue_window'    => (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(amount),0) FROM $pay WHERE status='approved' AND verified_at BETWEEN %s AND %s", $from_dt, $to_dt ) ),
			'admissions_window' => (int)   $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $adm WHERE created_at BETWEEN %s AND %s", $from_dt, $to_dt ) ),
			'students_window'   => (int)   $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT user_id) FROM $enr WHERE user_id > 0 AND enrolled_at BETWEEN %s AND %s", $from_dt, $to_dt ) ),
			'certs_window'      => (int)   $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $cer WHERE status='valid' AND created_at BETWEEN %s AND %s", $from_dt, $to_dt ) ),
		];

		// Previous-period delta (same length, immediately before)
		$days = max( 1, (int) ( ( strtotime( $to_dt ) - strtotime( $from_dt ) ) / DAY_IN_SECONDS ) + 1 );
		$prev_to   = date( 'Y-m-d 23:59:59', strtotime( $from_dt ) - 1 );
		$prev_from = date( 'Y-m-d 00:00:00', strtotime( $prev_to ) - ( ( $days - 1 ) * DAY_IN_SECONDS ) );

		$prev = [
			'revenue'    => (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(amount),0) FROM $pay WHERE status='approved' AND verified_at BETWEEN %s AND %s", $prev_from, $prev_to ) ),
			'admissions' => (int)   $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $adm WHERE created_at BETWEEN %s AND %s", $prev_from, $prev_to ) ),
			'students'   => (int)   $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT user_id) FROM $enr WHERE user_id > 0 AND enrolled_at BETWEEN %s AND %s", $prev_from, $prev_to ) ),
			'certs'      => (int)   $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $cer WHERE status='valid' AND created_at BETWEEN %s AND %s", $prev_from, $prev_to ) ),
		];

		// Lifetime totals
		$total_branches    = (int)   $wpdb->get_var( "SELECT COUNT(*) FROM $bch" );
		$total_franchises  = (int)   $wpdb->get_var( "SELECT COUNT(*) FROM $bch WHERE type='franchise'" );
		$total_revenue     = (float) $wpdb->get_var( "SELECT COALESCE(SUM(amount),0) FROM $pay WHERE status='approved'" );
		$pending_franchise = (int)   $wpdb->get_var( "SELECT COUNT(*) FROM $frn WHERE status='pending'" );

		// ── Daily revenue + admission trend in window ──────────────────────
		$rev_rows = $wpdb->get_results( $wpdb->prepare( "SELECT DATE(verified_at) AS d, SUM(amount) AS s FROM $pay WHERE status='approved' AND verified_at BETWEEN %s AND %s GROUP BY DATE(verified_at) ORDER BY d ASC", $from_dt, $to_dt ), ARRAY_A );
		$adm_rows = $wpdb->get_results( $wpdb->prepare( "SELECT DATE(created_at) AS d, COUNT(*) AS n FROM $adm WHERE created_at BETWEEN %s AND %s GROUP BY DATE(created_at) ORDER BY d ASC", $from_dt, $to_dt ), ARRAY_A );
		$rev_map = []; foreach ( $rev_rows as $r ) $rev_map[ $r['d'] ] = (float) $r['s'];
		$adm_map = []; foreach ( $adm_rows as $r ) $adm_map[ $r['d'] ] = (int) $r['n'];
		$rev_max = $rev_map ? max( $rev_map ) : 0;
		$adm_max = $adm_map ? max( $adm_map ) : 0;

		// ── Admission status breakdown (in window) ─────────────────────────
		$status_rows = $wpdb->get_results( $wpdb->prepare( "SELECT status, COUNT(*) AS n FROM $adm WHERE created_at BETWEEN %s AND %s GROUP BY status", $from_dt, $to_dt ), ARRAY_A );
		$status_map  = []; foreach ( $status_rows as $r ) $status_map[ $r['status'] ] = (int) $r['n'];
		$status_total = max( 1, array_sum( $status_map ) );

		// ── Payment status & method breakdown ──────────────────────────────
		$pay_status_rows = $wpdb->get_results( $wpdb->prepare( "SELECT status, COUNT(*) AS n, COALESCE(SUM(amount),0) AS s FROM $pay WHERE created_at BETWEEN %s AND %s GROUP BY status", $from_dt, $to_dt ), ARRAY_A );
		$pay_method_rows = $wpdb->get_results( $wpdb->prepare( "SELECT method, COUNT(*) AS n, COALESCE(SUM(amount),0) AS s FROM $pay WHERE status='approved' AND verified_at BETWEEN %s AND %s GROUP BY method", $from_dt, $to_dt ), ARRAY_A );

		// ── Branch performance (in window) ──────────────────────────────────
		$branch_perf = $wpdb->get_results( $wpdb->prepare(
			"SELECT b.id, b.name, b.type,
			        (SELECT COUNT(*)         FROM $enr e WHERE e.branch_id=b.id AND e.enrolled_at BETWEEN %s AND %s) AS enrollments,
			        (SELECT COUNT(*)         FROM $adm a WHERE a.branch_id=b.id AND a.created_at  BETWEEN %s AND %s) AS admissions,
			        (SELECT COALESCE(SUM(amount),0) FROM $pay p WHERE p.branch_id=b.id AND p.status='approved' AND p.verified_at BETWEEN %s AND %s) AS revenue
			   FROM $bch b
			   ORDER BY revenue DESC, enrollments DESC",
			$from_dt, $to_dt, $from_dt, $to_dt, $from_dt, $to_dt
		), ARRAY_A );
		$max_branch_revenue = 0;
		foreach ( $branch_perf as $bp ) $max_branch_revenue = max( $max_branch_revenue, (float) $bp['revenue'] );
		$max_branch_revenue = max( $max_branch_revenue, 1 );
		$total_window_revenue = array_sum( array_column( $branch_perf, 'revenue' ) );
		$total_window_revenue = max( 1, $total_window_revenue );

		// ── Franchise revenue contribution (in window) ─────────────────────
		$franchise_perf = $wpdb->get_results( $wpdb->prepare(
			"SELECT b.id AS branch_id, b.name, b.city, f.id AS franchise_id, f.applicant_name, f.email,
			        (SELECT COUNT(DISTINCT user_id) FROM $enr e WHERE e.branch_id=b.id) AS students_total,
			        (SELECT COUNT(*)         FROM $adm a WHERE a.branch_id=b.id AND a.created_at  BETWEEN %s AND %s) AS admissions,
			        (SELECT COALESCE(SUM(amount),0) FROM $pay p WHERE p.branch_id=b.id AND p.status='approved' AND p.verified_at BETWEEN %s AND %s) AS revenue
			   FROM $bch b
			   LEFT JOIN $frn f ON f.branch_id=b.id
			   WHERE b.type='franchise'
			   ORDER BY revenue DESC",
			$from_dt, $to_dt, $from_dt, $to_dt
		), ARRAY_A );
		$max_franchise_revenue = 0;
		foreach ( $franchise_perf as $fp ) $max_franchise_revenue = max( $max_franchise_revenue, (float) $fp['revenue'] );
		$max_franchise_revenue = max( $max_franchise_revenue, 1 );

		// ── Top courses by revenue (in window) ─────────────────────────────
		$top_courses = $wpdb->get_results( $wpdb->prepare(
			"SELECT a.course_id, a.course_title, COUNT(*) AS enrollments, COALESCE(SUM(p.amount),0) AS revenue
			   FROM $adm a
			   LEFT JOIN $pay p ON p.id = a.payment_id AND p.status='approved'
			  WHERE a.created_at BETWEEN %s AND %s AND a.course_id > 0
			  GROUP BY a.course_id
			  ORDER BY revenue DESC, enrollments DESC
			  LIMIT 10",
			$from_dt, $to_dt
		), ARRAY_A );
		$max_course_rev = 0;
		foreach ( $top_courses as $tc ) $max_course_rev = max( $max_course_rev, (float) $tc['revenue'] );
		$max_course_rev = max( $max_course_rev, 1 );

		$status_colors = [
			'pending'         => '#f6c177',
			'payment_pending' => '#ffd391',
			'payment_review'  => '#f6a05e',
			'approved'        => '#8EC9C4',
			'rejected'        => '#E63946',
			'refunded'        => '#9b9b9b',
		];

		$base_url   = admin_url( 'admin.php?page=' . self::SLUG );
		$range_url  = static fn( $r ) => add_query_arg( [ 'page' => self::SLUG, 'range' => $r ], admin_url( 'admin.php' ) );
		$delta_html = function( float $now, float $prev ): string {
			if ( $prev <= 0 && $now <= 0 ) return '<span class="rks-stat-card__delta rks-stat-card__delta--flat">—</span>';
			if ( $prev <= 0 ) return '<span class="rks-stat-card__delta">' . Icons::svg( 'trending-up', 12 ) . ' New</span>';
			$pct = ( ( $now - $prev ) / $prev ) * 100;
			$cls = $pct >= 0.5 ? '' : ( $pct <= -0.5 ? '--down' : '--flat' );
			$ic  = $pct >= 0.5 ? 'trending-up' : ( $pct <= -0.5 ? 'trending-down' : 'arrow-right' );
			return sprintf(
				'<span class="rks-stat-card__delta rks-stat-card__delta%s">%s %s%.1f%%</span>',
				esc_attr( $cls ),
				Icons::svg( $ic, 12 ),
				$pct >= 0 ? '+' : '',
				$pct
			);
		};
		?>
		<div class="wrap rks-admin-wrap">
			<header class="rks-page-hero">
				<div class="rks-page-hero__main">
					<div class="rks-page-hero__icon"><?php echo Icons::svg( 'chart-line', 24 ); ?></div>
					<div>
						<small>Analytics Engine</small>
						<h1>Reports &amp; Analytics</h1>
						<p>Revenue, admissions, branch &amp; franchise performance — system-wide.</p>
					</div>
				</div>
				<div class="rks-page-hero__actions">
					<a href="<?php echo esc_url( admin_url( 'admin-post.php?action=rks_export_csv&dataset=admissions&from=' . $from . '&to=' . $to . '&_wpnonce=' . wp_create_nonce( 'rks_export_csv' ) ) ); ?>" class="button"><?php echo Icons::svg( 'download', 14 ); ?> Admissions CSV</a>
					<a href="<?php echo esc_url( admin_url( 'admin-post.php?action=rks_export_csv&dataset=payments&from='   . $from . '&to=' . $to . '&_wpnonce=' . wp_create_nonce( 'rks_export_csv' ) ) ); ?>" class="button button-primary"><?php echo Icons::svg( 'download', 14 ); ?> Revenue CSV</a>
				</div>
			</header>

			<!-- ====== Date range filter ====== -->
			<div class="rks-filter-bar">
				<label><?php echo Icons::svg( 'calendar-days', 14 ); ?> Range</label>
				<div class="rks-segmented">
					<?php foreach ( $this->ranges as $key => $r ) : ?>
						<a href="<?php echo esc_url( $range_url( $key ) ); ?>" class="<?php echo $range === $key ? 'is-active' : ''; ?>"><?php echo esc_html( $r['label'] ); ?></a>
					<?php endforeach; ?>
				</div>
				<form method="get" style="display:inline-flex;gap:6px;align-items:center;">
					<input type="hidden" name="page" value="<?php echo esc_attr( self::SLUG ); ?>">
					<input type="hidden" name="range" value="custom">
					<label>Custom</label>
					<input type="date" name="from" value="<?php echo esc_attr( $from ); ?>">
					<span style="color:#6b7a82;">→</span>
					<input type="date" name="to"   value="<?php echo esc_attr( $to ); ?>">
					<button class="button"><?php echo Icons::svg( 'filter', 12 ); ?> Apply</button>
				</form>
				<span style="margin-left:auto;color:#6b7a82;font-size:12px;">
					Showing <strong><?php echo esc_html( date( 'd M Y', strtotime( $from ) ) ); ?></strong>
					→ <strong><?php echo esc_html( date( 'd M Y', strtotime( $to ) ) ); ?></strong>
					(<?php echo (int) $days; ?> days)
				</span>
			</div>

			<!-- ====== KPI cards with deltas ====== -->
			<div class="rks-admin-grid">
				<div class="rks-stat-card">
					<div class="rks-stat-card__head">
						<?php echo Icons::chip( 'wallet', 'red' ); ?>
						<span class="rks-stat-card__label">Revenue</span>
					</div>
					<span class="rks-stat-card__num"><?php echo esc_html( $currency . number_format( $kpi['revenue_window'], 0 ) ); ?></span>
					<?php echo $delta_html( $kpi['revenue_window'], $prev['revenue'] ); ?>
					<small class="rks-stat-card__sub">Prev period: <?php echo esc_html( $currency . number_format( $prev['revenue'], 0 ) ); ?></small>
				</div>
				<div class="rks-stat-card">
					<div class="rks-stat-card__head">
						<?php echo Icons::chip( 'inbox', 'gold' ); ?>
						<span class="rks-stat-card__label">New Admissions</span>
					</div>
					<span class="rks-stat-card__num"><?php echo number_format( $kpi['admissions_window'] ); ?></span>
					<?php echo $delta_html( (float) $kpi['admissions_window'], (float) $prev['admissions'] ); ?>
					<small class="rks-stat-card__sub">Prev: <?php echo (int) $prev['admissions']; ?></small>
				</div>
				<div class="rks-stat-card">
					<div class="rks-stat-card__head">
						<?php echo Icons::chip( 'user-plus', 'mint' ); ?>
						<span class="rks-stat-card__label">New Students</span>
					</div>
					<span class="rks-stat-card__num"><?php echo number_format( $kpi['students_window'] ); ?></span>
					<?php echo $delta_html( (float) $kpi['students_window'], (float) $prev['students'] ); ?>
					<small class="rks-stat-card__sub">Prev: <?php echo (int) $prev['students']; ?></small>
				</div>
				<div class="rks-stat-card">
					<div class="rks-stat-card__head">
						<?php echo Icons::chip( 'badge-check', 'lime' ); ?>
						<span class="rks-stat-card__label">Certificates Issued</span>
					</div>
					<span class="rks-stat-card__num"><?php echo number_format( $kpi['certs_window'] ); ?></span>
					<?php echo $delta_html( (float) $kpi['certs_window'], (float) $prev['certs'] ); ?>
					<small class="rks-stat-card__sub">Prev: <?php echo (int) $prev['certs']; ?></small>
				</div>
				<div class="rks-stat-card">
					<div class="rks-stat-card__head">
						<?php echo Icons::chip( 'building', 'teal' ); ?>
						<span class="rks-stat-card__label">Network</span>
					</div>
					<span class="rks-stat-card__num"><?php echo (int) $total_branches; ?></span>
					<small class="rks-stat-card__sub"><?php echo (int) $total_franchises; ?> franchises · 1 main HQ</small>
				</div>
				<div class="rks-stat-card <?php echo $pending_franchise ? 'rks-admin-card--alert' : ''; ?>">
					<div class="rks-stat-card__head">
						<?php echo Icons::chip( 'briefcase', $pending_franchise ? 'red' : 'slate' ); ?>
						<span class="rks-stat-card__label">Pending Applications</span>
					</div>
					<span class="rks-stat-card__num"><?php echo (int) $pending_franchise; ?></span>
					<?php if ( $pending_franchise ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . FranchisesPage::SLUG ) ); ?>" style="font-size:12px;">Review applications →</a>
					<?php else : ?>
						<small class="rks-stat-card__sub">All caught up</small>
					<?php endif; ?>
				</div>
			</div>

			<!-- ====== Revenue + Admission trends side-by-side ====== -->
			<div class="rks-analytics-grid">
				<div class="rks-admin-card">
					<div class="rks-section-head" style="margin-top:0;">
						<?php echo Icons::chip( 'chart-line', 'red' ); ?>
						<h2>Revenue Trend</h2>
						<small>· verified payments by day</small>
					</div>
					<?php if ( $rev_max <= 0 ) : ?>
						<div class="rks-empty">
							<?php echo Icons::chip( 'wallet', 'slate', 22 ); ?>
							<h3>No verified revenue in this period</h3>
							<small>Verify pending payments to populate this chart.</small>
						</div>
					<?php else : ?>
						<div class="rks-rep-spark">
							<?php
							$start = strtotime( $from );
							$end   = strtotime( $to );
							$days  = (int) ( ( $end - $start ) / DAY_IN_SECONDS ) + 1;
							$step  = $days <= 14 ? 1 : ( $days >= 60 ? 5 : 3 );
							$idx   = 0;
							for ( $t = $start; $t <= $end; $t += DAY_IN_SECONDS ) :
								$d = date( 'Y-m-d', $t );
								$v = (float) ( $rev_map[ $d ] ?? 0 );
								$h = $rev_max > 0 ? (int) round( ( $v / $rev_max ) * 100 ) : 0;
							?>
								<div class="rks-rep-spark__bar" title="<?php echo esc_attr( $d . ': ' . $currency . number_format( $v, 0 ) ); ?>">
									<div style="height: <?php echo (int) $h; ?>%;background:linear-gradient(to top,#c92c39,#E63946);"></div>
									<?php if ( $idx % $step === 0 ) : ?>
										<small><?php echo esc_html( date( 'd', $t ) ); ?></small>
									<?php else : ?>
										<small>&nbsp;</small>
									<?php endif; ?>
								</div>
							<?php $idx++; endfor; ?>
						</div>
						<small>Peak day: <?php echo esc_html( $currency . number_format( $rev_max, 0 ) ); ?></small>
					<?php endif; ?>
				</div>

				<div class="rks-admin-card">
					<div class="rks-section-head" style="margin-top:0;">
						<?php echo Icons::chip( 'chart-bar', 'teal' ); ?>
						<h2>Admissions Trend</h2>
						<small>· new applications per day</small>
					</div>
					<?php if ( $adm_max <= 0 ) : ?>
						<div class="rks-empty">
							<?php echo Icons::chip( 'inbox', 'slate', 22 ); ?>
							<h3>No admissions in this period</h3>
						</div>
					<?php else : ?>
						<div class="rks-rep-spark">
							<?php
							$start = strtotime( $from );
							$end   = strtotime( $to );
							$days  = (int) ( ( $end - $start ) / DAY_IN_SECONDS ) + 1;
							$step  = $days <= 14 ? 1 : ( $days >= 60 ? 5 : 3 );
							$idx   = 0;
							for ( $t = $start; $t <= $end; $t += DAY_IN_SECONDS ) :
								$d = date( 'Y-m-d', $t );
								$n = (int) ( $adm_map[ $d ] ?? 0 );
								$h = $adm_max > 0 ? (int) round( ( $n / $adm_max ) * 100 ) : 0;
							?>
								<div class="rks-rep-spark__bar" title="<?php echo esc_attr( $d . ': ' . $n ); ?>">
									<div style="height: <?php echo (int) $h; ?>%;"></div>
									<?php if ( $idx % $step === 0 ) : ?>
										<small><?php echo esc_html( date( 'd', $t ) ); ?></small>
									<?php else : ?>
										<small>&nbsp;</small>
									<?php endif; ?>
								</div>
							<?php $idx++; endfor; ?>
						</div>
						<small>Peak day: <?php echo (int) $adm_max; ?> admissions</small>
					<?php endif; ?>
				</div>
			</div>

			<!-- ====== Status breakdown (admissions) + Payment breakdowns ====== -->
			<div class="rks-analytics-grid">
				<div class="rks-admin-card">
					<div class="rks-section-head" style="margin-top:0;">
						<?php echo Icons::chip( 'chart-pie', 'gold' ); ?>
						<h2>Admissions by Status</h2>
					</div>
					<?php if ( empty( $status_map ) ) : ?>
						<em>No admissions in this period.</em>
					<?php else : ?>
						<div class="rks-rep-bar rks-rep-bar--segments">
							<?php foreach ( $status_map as $st => $n ) :
								$pct = round( ( $n / $status_total ) * 100, 1 );
								$col = $status_colors[ $st ] ?? '#999';
							?>
								<div style="width:<?php echo esc_attr( (string) $pct ); ?>%; background:<?php echo esc_attr( $col ); ?>;" title="<?php echo esc_attr( $st . ': ' . $n ); ?>"></div>
							<?php endforeach; ?>
						</div>
						<ul class="rks-rep-legend">
							<?php foreach ( $status_map as $st => $n ) :
								$pct = round( ( $n / $status_total ) * 100, 1 );
								$col = $status_colors[ $st ] ?? '#999';
							?>
								<li>
									<span class="rks-rep-swatch" style="background:<?php echo esc_attr( $col ); ?>"></span>
									<strong><?php echo esc_html( str_replace( '_', ' ', $st ) ); ?></strong>
									<small><?php echo (int) $n; ?> (<?php echo esc_html( (string) $pct ); ?>%)</small>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>

				<div class="rks-admin-card">
					<div class="rks-section-head" style="margin-top:0;">
						<?php echo Icons::chip( 'credit-card', 'mint' ); ?>
						<h2>Payments — Status &amp; Method</h2>
					</div>
					<?php if ( empty( $pay_status_rows ) ) : ?>
						<em>No payment activity in this period.</em>
					<?php else : ?>
						<table class="rks-summary-table">
							<thead><tr><th>Status</th><th>Count</th><th>Amount</th></tr></thead>
							<tbody>
								<?php foreach ( $pay_status_rows as $r ) : ?>
								<tr>
									<td><span class="rks-pill rks-pill--<?php echo $r['status'] === 'approved' ? 'success' : ( $r['status'] === 'rejected' ? 'danger' : 'warn' ); ?>"><?php echo esc_html( $r['status'] ); ?></span></td>
									<td><?php echo (int) $r['n']; ?></td>
									<td><strong><?php echo esc_html( $currency . number_format( (float) $r['s'], 0 ) ); ?></strong></td>
								</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
						<?php if ( ! empty( $pay_method_rows ) ) : ?>
						<h4 style="margin:18px 0 8px;color:#2C687B;font-size:13px;text-transform:uppercase;letter-spacing:0.4px;">Verified by Method</h4>
						<table class="rks-summary-table">
							<thead><tr><th>Method</th><th>Count</th><th>Amount</th></tr></thead>
							<tbody>
								<?php foreach ( $pay_method_rows as $r ) : ?>
								<tr>
									<td><span class="rks-tag rks-tag--main"><?php echo esc_html( strtoupper( (string) $r['method'] ) ); ?></span></td>
									<td><?php echo (int) $r['n']; ?></td>
									<td><strong><?php echo esc_html( $currency . number_format( (float) $r['s'], 0 ) ); ?></strong></td>
								</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
						<?php endif; ?>
					<?php endif; ?>
				</div>
			</div>

			<!-- ====== Branch performance ====== -->
			<div class="rks-admin-card">
				<div class="rks-section-head" style="margin-top:0;">
					<?php echo Icons::chip( 'building', 'teal' ); ?>
					<h2>Branch Performance</h2>
					<small>· revenue, admissions and enrollments by branch (in window)</small>
				</div>
				<?php if ( empty( $branch_perf ) ) : ?>
					<em>No branches.</em>
				<?php else : ?>
					<table class="widefat striped">
						<thead>
							<tr>
								<th>Branch</th>
								<th>Type</th>
								<th>Admissions</th>
								<th>Enrollments</th>
								<th>Revenue</th>
								<th>Revenue Share</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $branch_perf as $b ) :
								$pct = round( ( (float) $b['revenue'] / $max_branch_revenue ) * 100, 1 );
								$share = round( ( (float) $b['revenue'] / $total_window_revenue ) * 100, 1 );
							?>
								<tr>
									<td><strong><?php echo esc_html( (string) $b['name'] ); ?></strong></td>
									<td><span class="rks-tag rks-tag--<?php echo esc_attr( (string) $b['type'] ); ?>"><?php echo esc_html( (string) $b['type'] ); ?></span></td>
									<td><?php echo number_format( (int) $b['admissions'] ); ?></td>
									<td><?php echo number_format( (int) $b['enrollments'] ); ?></td>
									<td><strong><?php echo esc_html( $currency . number_format( (float) $b['revenue'], 0 ) ); ?></strong></td>
									<td>
										<div class="rks-mini-chart__bar"><div style="width: <?php echo esc_attr( (string) $pct ); ?>%;"><?php echo esc_html( (string) $share ); ?>%</div></div>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>

			<!-- ====== Franchise revenue contribution ====== -->
			<div class="rks-admin-card">
				<div class="rks-section-head" style="margin-top:0;">
					<?php echo Icons::chip( 'briefcase', 'red' ); ?>
					<h2>Franchise Revenue Contribution</h2>
					<small>· per-franchise breakdown</small>
				</div>
				<?php if ( empty( $franchise_perf ) ) : ?>
					<div class="rks-empty">
						<?php echo Icons::chip( 'briefcase', 'slate', 22 ); ?>
						<h3>No franchise branches yet</h3>
						<small>Approve a franchise application to start tracking revenue contribution.</small>
					</div>
				<?php else : ?>
					<table class="widefat striped">
						<thead>
							<tr>
								<th>Franchise (Branch)</th>
								<th>Owner</th>
								<th>City</th>
								<th>Students</th>
								<th>Admissions</th>
								<th>Revenue</th>
								<th>Share of all branches</th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $franchise_perf as $fp ) :
								$pct   = round( ( (float) $fp['revenue'] / $max_franchise_revenue ) * 100, 1 );
								$share = round( ( (float) $fp['revenue'] / $total_window_revenue ) * 100, 1 );
							?>
								<tr>
									<td><strong><?php echo esc_html( (string) $fp['name'] ); ?></strong></td>
									<td>
										<?php echo esc_html( (string) ( $fp['applicant_name'] ?? '—' ) ); ?>
										<?php if ( ! empty( $fp['email'] ) ) : ?>
											<br><small><?php echo esc_html( (string) $fp['email'] ); ?></small>
										<?php endif; ?>
									</td>
									<td><?php echo esc_html( (string) $fp['city'] ); ?></td>
									<td><?php echo number_format( (int) $fp['students_total'] ); ?></td>
									<td><?php echo number_format( (int) $fp['admissions'] ); ?></td>
									<td><strong><?php echo esc_html( $currency . number_format( (float) $fp['revenue'], 0 ) ); ?></strong></td>
									<td>
										<div class="rks-mini-chart__bar rks-mini-chart__bar--red"><div style="width: <?php echo esc_attr( (string) $pct ); ?>%;"><?php echo esc_html( (string) $share ); ?>%</div></div>
									</td>
									<td>
										<?php if ( ! empty( $fp['franchise_id'] ) ) : ?>
											<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . FranchisesPage::SLUG . '&view=' . (int) $fp['franchise_id'] ) ); ?>" class="button button-small"><?php echo Icons::svg( 'eye', 12 ); ?> View</a>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>

			<!-- ====== Top courses by revenue ====== -->
			<div class="rks-admin-card">
				<div class="rks-section-head" style="margin-top:0;">
					<?php echo Icons::chip( 'cap', 'lilac' ); ?>
					<h2>Top Courses by Revenue</h2>
					<small>· top 10 in window</small>
				</div>
				<?php if ( empty( $top_courses ) ) : ?>
					<em>No course revenue in this period.</em>
				<?php else : ?>
					<table class="widefat striped">
						<thead><tr><th>Course</th><th>Enrollments</th><th>Revenue</th><th>Share</th></tr></thead>
						<tbody>
							<?php foreach ( $top_courses as $tc ) :
								$pct = round( ( (float) $tc['revenue'] / $max_course_rev ) * 100, 1 );
							?>
								<tr>
									<td><strong><?php echo esc_html( (string) ( $tc['course_title'] ?: '—' ) ); ?></strong></td>
									<td><?php echo (int) $tc['enrollments']; ?></td>
									<td><strong><?php echo esc_html( $currency . number_format( (float) $tc['revenue'], 0 ) ); ?></strong></td>
									<td>
										<div class="rks-mini-chart__bar rks-mini-chart__bar--gold"><div style="width: <?php echo esc_attr( (string) $pct ); ?>%;"></div></div>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>

			<!-- ====== CSV exports ====== -->
			<div class="rks-admin-card">
				<div class="rks-section-head" style="margin-top:0;">
					<?php echo Icons::chip( 'download', 'slate' ); ?>
					<h2>Data Export</h2>
					<small>· CSV files for the current date range</small>
				</div>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-flex;gap:10px;flex-wrap:wrap;">
					<?php wp_nonce_field( 'rks_export_csv' ); ?>
					<input type="hidden" name="action" value="rks_export_csv">
					<input type="hidden" name="from" value="<?php echo esc_attr( $from ); ?>">
					<input type="hidden" name="to"   value="<?php echo esc_attr( $to ); ?>">
					<button name="dataset" value="admissions" class="button button-primary"><?php echo Icons::svg( 'inbox', 12 ); ?> Admissions</button>
					<button name="dataset" value="payments"   class="button button-primary"><?php echo Icons::svg( 'wallet', 12 ); ?> Payments</button>
					<button name="dataset" value="students"   class="button button-primary"><?php echo Icons::svg( 'users', 12 ); ?> Students</button>
					<button name="dataset" value="franchises" class="button button-primary"><?php echo Icons::svg( 'briefcase', 12 ); ?> Franchises</button>
				</form>
				<p style="margin-top:12px;"><small>Lifetime revenue across all time: <strong><?php echo esc_html( $currency . number_format( $total_revenue, 0 ) ); ?></strong></small></p>
			</div>
		</div>

		<style>
			.rks-rep-bar { display:block; width:100%; height:12px; background:#f1f1f1; border-radius:6px; overflow:hidden; }
			.rks-rep-bar > div { height:100%; background:#8EC9C4; transition:width .3s ease; }
			.rks-rep-bar--segments { display:flex; height:22px; margin:8px 0; }
			.rks-rep-bar--segments > div { height:100%; }
			.rks-rep-legend { list-style:none; margin:0; padding:0; display:flex; gap:18px; flex-wrap:wrap; font-size:13px; }
			.rks-rep-legend li { display:flex; align-items:center; gap:6px; }
			.rks-rep-swatch { width:14px; height:14px; border-radius:3px; display:inline-block; }
			.rks-rep-spark { display:flex; align-items:flex-end; gap:3px; height:140px; padding:8px 0 4px; }
			.rks-rep-spark__bar { flex:1; display:flex; flex-direction:column; justify-content:flex-end; align-items:center; gap:4px; min-width:0; }
			.rks-rep-spark__bar > div { width:100%; background:linear-gradient(to top,#2C687B,#8EC9C4); min-height:2px; border-radius:2px 2px 0 0; }
			.rks-rep-spark__bar small { font-size:9px; color:#6b7a82; }
		</style>
		<?php
	}

	public function handle_export(): void {
		check_admin_referer( 'rks_export_csv' );
		if ( ! current_user_can( 'rks_view_global_reports' ) ) wp_die( 'Forbidden', 'Forbidden', [ 'response' => 403 ] );

		$dataset = sanitize_key( wp_unslash( (string) ( $_REQUEST['dataset'] ?? '' ) ) );
		$from    = sanitize_text_field( wp_unslash( (string) ( $_REQUEST['from'] ?? '' ) ) );
		$to      = sanitize_text_field( wp_unslash( (string) ( $_REQUEST['to'] ?? '' ) ) );
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $from ) ) $from = '';
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $to ) )   $to   = '';
		$where_dates = '';
		$params      = [];
		if ( $from && $to ) {
			$where_dates = ' WHERE created_at BETWEEN %s AND %s';
			$params      = [ $from . ' 00:00:00', $to . ' 23:59:59' ];
		}

		global $wpdb;
		$rows    = [];
		$headers = [];
		$file    = 'rks-export-' . date( 'Y-m-d' ) . '.csv';

		switch ( $dataset ) {
			case 'admissions':
				$sql     = 'SELECT * FROM ' . DB::table( 'admissions' ) . $where_dates . ' ORDER BY id DESC';
				$rows    = $params ? $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A ) : $wpdb->get_results( $sql, ARRAY_A );
				$headers = $rows ? array_keys( $rows[0] ) : [ 'id' ];
				$file    = 'rks-admissions-' . $from . '_to_' . $to . '.csv';
				break;
			case 'payments':
				$sql     = 'SELECT id, branch_id, admission_id, amount, method, utr, status, verifier_user_id, verified_at, created_at FROM ' . DB::table( 'payments' ) . $where_dates . ' ORDER BY id DESC';
				$rows    = $params ? $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A ) : $wpdb->get_results( $sql, ARRAY_A );
				$headers = $rows ? array_keys( $rows[0] ) : [ 'id' ];
				$file    = 'rks-payments-' . $from . '_to_' . $to . '.csv';
				break;
			case 'students':
				$rows = $wpdb->get_results(
					"SELECT u.ID AS user_id, u.user_login, u.user_email, u.display_name, u.user_registered,
					        (SELECT COUNT(*) FROM " . DB::table( 'enrollments' ) . " e WHERE e.user_id=u.ID) AS enrollments,
					        (SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id=u.ID AND meta_key='rks_branch_id') AS branch_id
					 FROM {$wpdb->users} u
					 INNER JOIN {$wpdb->usermeta} m ON m.user_id=u.ID AND m.meta_key='wp_capabilities'
					 WHERE m.meta_value LIKE '%rks_student%'
					 ORDER BY u.ID DESC",
					ARRAY_A
				);
				$headers = $rows ? array_keys( $rows[0] ) : [ 'user_id' ];
				$file    = 'rks-students-' . date( 'Y-m-d' ) . '.csv';
				break;
			case 'franchises':
				$rows    = $wpdb->get_results( 'SELECT * FROM ' . DB::table( 'franchises' ) . ' ORDER BY id DESC', ARRAY_A );
				$headers = $rows ? array_keys( $rows[0] ) : [ 'id' ];
				$file    = 'rks-franchises-' . date( 'Y-m-d' ) . '.csv';
				break;
			default:
				wp_die( 'Unknown dataset' );
		}

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $file . '"' );

		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, $headers );
		foreach ( $rows as $r ) {
			fputcsv( $out, array_map( static fn( $v ) => is_scalar( $v ) ? $v : (string) wp_json_encode( $v ), array_values( $r ) ) );
		}
		fclose( $out );
		exit;
	}
}
