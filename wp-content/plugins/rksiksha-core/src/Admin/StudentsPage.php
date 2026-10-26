<?php
/**
 * Admin: Students directory + single student profile.
 *
 * Lists all users with the rks_student role + drill-down profile showing
 * enrollments, payments, certificates, exam attempts.
 *
 * @package RKSiksha\Core
 */

declare( strict_types=1 );

namespace RKSiksha\Core\Admin;

use RKSiksha\Core\Modules\Branches;
use RKSiksha\Core\Support\DB;
use RKSiksha\Core\Support\Icons;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class StudentsPage {

	public const SLUG = 'rks-students';

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'register_menu' ], 32 );
	}

	public function register_menu(): void {
		add_submenu_page(
			Menu::SLUG_OPERATIONS,
			'Students',
			'Students',
			'rks_view_all_admissions',
			self::SLUG,
			[ $this, 'render' ]
		);
	}

	public function render(): void {
		if ( ! current_user_can( 'rks_view_all_admissions' ) ) wp_die( 'Forbidden' );

		$user_id = isset( $_GET['user_id'] ) ? (int) $_GET['user_id'] : 0;
		if ( $user_id > 0 ) { $this->render_profile( $user_id ); return; }

		$this->render_list();
	}

	private function render_list(): void {
		global $wpdb;
		$search    = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['s'] ) ) : '';
		$branch_id = isset( $_GET['branch'] ) ? (int) $_GET['branch'] : 0;
		$enr       = DB::table( 'enrollments' );

		$where = [ "m.meta_key='wp_capabilities'", "m.meta_value LIKE '%rks_student%'" ];
		$params = [];
		if ( $search !== '' ) {
			$where[]  = '(u.user_login LIKE %s OR u.user_email LIKE %s OR u.display_name LIKE %s)';
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$params[] = $like; $params[] = $like; $params[] = $like;
		}
		if ( $branch_id > 0 ) {
			$where[]  = 'EXISTS (SELECT 1 FROM ' . $wpdb->usermeta . " bm WHERE bm.user_id=u.ID AND bm.meta_key='rks_branch_id' AND bm.meta_value=%d)";
			$params[] = $branch_id;
		}

		$sql = "SELECT u.ID, u.user_login, u.user_email, u.display_name, u.user_registered,
		               (SELECT COUNT(*) FROM $enr e WHERE e.user_id=u.ID) AS enrollments,
		               (SELECT COUNT(*) FROM $enr e WHERE e.user_id=u.ID AND e.status='completed') AS completed,
		               (SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id=u.ID AND meta_key='rks_branch_id') AS branch_id
		          FROM {$wpdb->users} u
		          INNER JOIN {$wpdb->usermeta} m ON m.user_id=u.ID
		         WHERE " . implode( ' AND ', $where ) . '
		         ORDER BY u.ID DESC LIMIT 200';

		$rows = $params ? $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A ) : $wpdb->get_results( $sql, ARRAY_A );

		$branches = ( new Branches() )->list( [ 'limit' => 100 ] );
		?>
		<div class="wrap rks-admin-wrap">
			<header class="rks-page-hero">
				<div class="rks-page-hero__main">
					<div class="rks-page-hero__icon"><?php echo Icons::svg( 'users', 24 ); ?></div>
					<div>
						<small>Students Directory</small>
						<h1>All Students</h1>
						<p>Every student in the system across all branches. Click "View Profile" for full details.</p>
					</div>
				</div>
				<div class="rks-page-hero__actions">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . AdmissionsPage::SLUG_LIST ) ); ?>" class="button"><?php echo Icons::svg( 'inbox', 14 ); ?> Admissions</a>
				</div>
			</header>

			<form method="get" class="rks-filter-bar">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::SLUG ); ?>">
				<label><?php echo Icons::svg( 'search', 14 ); ?> Search</label>
				<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Name, email, login...">
				<label>Branch</label>
				<select name="branch">
					<option value="0">All branches</option>
					<?php foreach ( $branches as $b ) : ?>
						<option value="<?php echo (int) $b['id']; ?>" <?php selected( $branch_id, (int) $b['id'] ); ?>><?php echo esc_html( (string) $b['name'] ); ?></option>
					<?php endforeach; ?>
				</select>
				<button class="button button-primary"><?php echo Icons::svg( 'filter', 12 ); ?> Filter</button>
				<?php if ( $search || $branch_id ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::SLUG ) ); ?>" class="button">Reset</a>
				<?php endif; ?>
				<span style="margin-left:auto;color:#6b7a82;font-size:12px;"><?php echo (int) count( $rows ); ?> result(s)</span>
			</form>

			<table class="widefat striped">
				<thead><tr><th>ID</th><th>Student</th><th>Email</th><th>Branch</th><th>Enrollments</th><th>Completed</th><th>Registered</th><th></th></tr></thead>
				<tbody>
					<?php if ( empty( $rows ) ) : ?>
						<tr><td colspan="8"><div class="rks-empty"><?php echo Icons::chip( 'users', 'slate', 22 ); ?><h3>No students found</h3><small>Try adjusting filters or wait for first approved admission.</small></div></td></tr>
					<?php else : foreach ( $rows as $r ) :
						$branch = (int) $r['branch_id'] ? ( new Branches() )->get( (int) $r['branch_id'] ) : null;
					?>
						<tr>
							<td>#<?php echo (int) $r['ID']; ?></td>
							<td><strong><?php echo esc_html( (string) ( $r['display_name'] ?: $r['user_login'] ) ); ?></strong><br><small><code><?php echo esc_html( (string) $r['user_login'] ); ?></code></small></td>
							<td><small><?php echo esc_html( (string) $r['user_email'] ); ?></small></td>
							<td><?php echo $branch ? esc_html( (string) $branch['name'] ) : '<em>—</em>'; ?></td>
							<td><?php echo (int) $r['enrollments']; ?></td>
							<td><?php echo (int) $r['completed']; ?></td>
							<td><small><?php echo esc_html( date( 'd M Y', strtotime( (string) $r['user_registered'] ) ) ); ?></small></td>
							<td><a href="<?php echo esc_url( add_query_arg( [ 'page' => self::SLUG, 'user_id' => (int) $r['ID'] ], admin_url( 'admin.php' ) ) ); ?>" class="button button-primary button-small"><?php echo Icons::svg( 'eye', 12 ); ?> Profile</a></td>
						</tr>
					<?php endforeach; endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	private function render_profile( int $user_id ): void {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			echo '<div class="wrap rks-admin-wrap"><div class="notice notice-error"><p>Student not found.</p></div></div>';
			return;
		}
		if ( ! in_array( 'rks_student', (array) $user->roles, true ) ) {
			echo '<div class="wrap rks-admin-wrap"><div class="notice notice-warning"><p>This user is not a student.</p></div></div>';
			return;
		}

		global $wpdb;
		$adm = DB::table( 'admissions' );
		$pay = DB::table( 'payments' );
		$enr = DB::table( 'enrollments' );
		$cer = DB::table( 'certificates' );
		$qa  = DB::table( 'quiz_attempts' );

		$settings = (array) get_option( 'rks_settings', [] );
		$currency = (string) ( $settings['currency_symbol'] ?? '₹' );

		$branch_id = (int) get_user_meta( $user_id, 'rks_branch_id', true );
		$branch    = $branch_id ? ( new Branches() )->get( $branch_id ) : null;

		// Stats
		$stats = [
			'enrollments'   => (int)   $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $enr WHERE user_id = %d", $user_id ) ),
			'completed'     => (int)   $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $enr WHERE user_id = %d AND status='completed'", $user_id ) ),
			'certs'         => (int)   $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $cer WHERE user_id = %d AND status='valid'", $user_id ) ),
			'paid'          => (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(amount),0) FROM $pay WHERE user_id = %d AND status='approved'", $user_id ) ),
			'admissions'    => (int)   $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $adm WHERE user_id = %d", $user_id ) ),
		];

		// Enrollments
		$enrollments = $wpdb->get_results( $wpdb->prepare(
			"SELECT id, course_id, branch_id, progress, lessons_total, lessons_done, status, enrolled_at, completed_at FROM $enr WHERE user_id = %d ORDER BY enrolled_at DESC LIMIT 50",
			$user_id
		), ARRAY_A );

		// Payments
		$payments = $wpdb->get_results( $wpdb->prepare(
			"SELECT id, amount, method, utr, status, created_at, verified_at FROM $pay WHERE user_id = %d ORDER BY id DESC LIMIT 20",
			$user_id
		), ARRAY_A );

		// Certificates
		$certificates = $wpdb->get_results( $wpdb->prepare(
			"SELECT id, unique_id, course_title, issue_date, status FROM $cer WHERE user_id = %d ORDER BY id DESC LIMIT 20",
			$user_id
		), ARRAY_A );

		// Exam attempts (recent)
		$quiz_attempts = $wpdb->get_results( $wpdb->prepare(
			"SELECT id, quiz_id, score, max_score, percentage, status, started_at, submitted_at FROM $qa WHERE user_id = %d ORDER BY id DESC LIMIT 20",
			$user_id
		), ARRAY_A );

		$initials = strtoupper( substr( trim( (string) ( $user->display_name ?: $user->user_login ) ), 0, 1 ) ?: 'U' );
		?>
		<div class="wrap rks-admin-wrap">
			<p style="margin:14px 0;"><a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::SLUG ) ); ?>" class="button"><?php echo Icons::svg( 'arrow-left', 12 ); ?> All Students</a></p>

			<div class="rks-detail-hero">
				<div class="rks-detail-hero__avatar"><?php echo esc_html( $initials ); ?></div>
				<div class="rks-detail-hero__main">
					<small>Student Profile · #<?php echo (int) $user->ID; ?></small>
					<h1><?php echo esc_html( $user->display_name ?: $user->user_login ); ?></h1>
					<p>
						<?php echo Icons::svg( 'mail', 12 ); ?> <?php echo esc_html( $user->user_email ); ?>
						<?php if ( $branch ) : ?>
							&nbsp;·&nbsp; <?php echo Icons::svg( 'building', 12 ); ?> <?php echo esc_html( (string) $branch['name'] ); ?>
						<?php endif; ?>
					</p>
					<p style="margin-top:8px;">
						<span class="rks-pill rks-pill--info">Student</span>
						&nbsp;
						<small>Joined <?php echo esc_html( date( 'd M Y', strtotime( $user->user_registered ) ) ); ?></small>
					</p>
				</div>
				<div class="rks-detail-hero__actions">
					<a href="mailto:<?php echo esc_attr( $user->user_email ); ?>" class="button"><?php echo Icons::svg( 'mail', 12 ); ?> Email</a>
					<a href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . (int) $user->ID ) ); ?>" class="button button-primary"><?php echo Icons::svg( 'edit', 12 ); ?> Edit User</a>
				</div>
			</div>

			<div class="rks-detail-grid">

				<!-- LEFT main -->
				<div>

					<div class="rks-admin-card">
						<div class="rks-section-head" style="margin-top:0;">
							<?php echo Icons::chip( 'cap', 'lilac' ); ?>
							<h2>Enrollments</h2>
							<small>· <?php echo (int) $stats['enrollments']; ?> total · <?php echo (int) $stats['completed']; ?> completed</small>
						</div>
						<?php if ( empty( $enrollments ) ) : ?>
							<em>No enrollments yet.</em>
						<?php else : ?>
							<table class="rks-summary-table">
								<thead><tr><th>Course</th><th>Progress</th><th>Lessons</th><th>Status</th><th>Enrolled</th></tr></thead>
								<tbody>
								<?php foreach ( $enrollments as $e ) :
									$course = get_post( (int) $e['course_id'] );
								?>
									<tr>
										<td><strong><?php echo $course ? esc_html( $course->post_title ) : 'Course #' . (int) $e['course_id']; ?></strong></td>
										<td>
											<div class="rks-mini-chart__bar" style="margin:0;"><div style="width: <?php echo (int) $e['progress']; ?>%;"><?php echo (int) $e['progress']; ?>%</div></div>
										</td>
										<td><?php echo (int) $e['lessons_done']; ?> / <?php echo (int) $e['lessons_total']; ?></td>
										<td><span class="rks-pill rks-pill--<?php echo $e['status'] === 'completed' ? 'success' : ( $e['status'] === 'cancelled' ? 'danger' : 'info' ); ?>"><?php echo esc_html( (string) $e['status'] ); ?></span></td>
										<td><small><?php echo esc_html( date( 'd M Y', strtotime( (string) $e['enrolled_at'] ) ) ); ?></small></td>
									</tr>
								<?php endforeach; ?>
								</tbody>
							</table>
						<?php endif; ?>
					</div>

					<div class="rks-admin-card">
						<div class="rks-section-head" style="margin-top:0;">
							<?php echo Icons::chip( 'wallet', 'red' ); ?>
							<h2>Payment History</h2>
							<small>· <?php echo esc_html( $currency . number_format( $stats['paid'], 0 ) ); ?> paid in total</small>
						</div>
						<?php if ( empty( $payments ) ) : ?>
							<em>No payments recorded.</em>
						<?php else : ?>
							<table class="rks-summary-table">
								<thead><tr><th>ID</th><th>Amount</th><th>Method</th><th>UTR</th><th>Status</th><th>Date</th><th></th></tr></thead>
								<tbody>
								<?php foreach ( $payments as $p ) : ?>
									<tr>
										<td>#<?php echo (int) $p['id']; ?></td>
										<td><strong><?php echo esc_html( $currency . number_format( (float) $p['amount'], 0 ) ); ?></strong></td>
										<td><span class="rks-tag rks-tag--main"><?php echo esc_html( strtoupper( (string) $p['method'] ) ); ?></span></td>
										<td><code><?php echo esc_html( (string) ( $p['utr'] ?: '—' ) ); ?></code></td>
										<td><span class="rks-pill rks-pill--<?php echo $p['status'] === 'approved' ? 'success' : ( $p['status'] === 'rejected' ? 'danger' : 'warn' ); ?>"><?php echo esc_html( (string) $p['status'] ); ?></span></td>
										<td><small><?php echo esc_html( (string) ( $p['verified_at'] ?: $p['created_at'] ) ); ?></small></td>
										<td><a href="<?php echo esc_url( add_query_arg( [ 'page' => AdmissionsPage::SLUG_PAYMENTS, 'view' => (int) $p['id'] ], admin_url( 'admin.php' ) ) ); ?>" class="button button-small"><?php echo Icons::svg( 'arrow-right', 12 ); ?></a></td>
									</tr>
								<?php endforeach; ?>
								</tbody>
							</table>
						<?php endif; ?>
					</div>

					<div class="rks-admin-card">
						<div class="rks-section-head" style="margin-top:0;">
							<?php echo Icons::chip( 'badge-check', 'lime' ); ?>
							<h2>Certificates</h2>
							<small>· <?php echo (int) $stats['certs']; ?> issued</small>
						</div>
						<?php if ( empty( $certificates ) ) : ?>
							<em>No certificates issued yet.</em>
						<?php else : ?>
							<table class="rks-summary-table">
								<thead><tr><th>Unique ID</th><th>Course</th><th>Issue Date</th><th>Status</th><th></th></tr></thead>
								<tbody>
								<?php foreach ( $certificates as $c ) : ?>
									<tr>
										<td><code><?php echo esc_html( (string) $c['unique_id'] ); ?></code></td>
										<td><strong><?php echo esc_html( (string) $c['course_title'] ); ?></strong></td>
										<td><?php echo esc_html( (string) $c['issue_date'] ); ?></td>
										<td><span class="rks-pill rks-pill--<?php echo $c['status'] === 'valid' ? 'success' : 'danger'; ?>"><?php echo esc_html( (string) $c['status'] ); ?></span></td>
										<td><a href="<?php echo esc_url( home_url( '/verify-certificate/?id=' . urlencode( (string) $c['unique_id'] ) ) ); ?>" target="_blank" class="button button-small"><?php echo Icons::svg( 'eye', 12 ); ?> View</a></td>
									</tr>
								<?php endforeach; ?>
								</tbody>
							</table>
						<?php endif; ?>
					</div>

					<div class="rks-admin-card">
						<div class="rks-section-head" style="margin-top:0;">
							<?php echo Icons::chip( 'clipboard-check', 'gold' ); ?>
							<h2>Recent Exam Attempts</h2>
						</div>
						<?php if ( empty( $quiz_attempts ) ) : ?>
							<em>No exam attempts yet.</em>
						<?php else : ?>
							<table class="rks-summary-table">
								<thead><tr><th>Exam</th><th>Score</th><th>%</th><th>Result</th><th>Date</th><th></th></tr></thead>
								<tbody>
								<?php foreach ( $quiz_attempts as $qatt ) :
									$qz_id = (int) $qatt['quiz_id'];
									$qz_title = (string) ( $wpdb->get_var( $wpdb->prepare( 'SELECT title FROM ' . DB::table( 'quizzes' ) . ' WHERE id = %d', $qz_id ) ) ?: 'Exam #' . $qz_id );
									$passed = (string) $qatt['status'] === 'passed';
									$failed = (string) $qatt['status'] === 'failed';
								?>
									<tr>
										<td><strong><?php echo esc_html( $qz_title ); ?></strong></td>
										<td><?php echo (int) $qatt['score']; ?> / <?php echo (int) $qatt['max_score']; ?></td>
										<td><strong><?php echo number_format( (float) $qatt['percentage'], 1 ); ?>%</strong></td>
										<td><span class="rks-pill rks-pill--<?php echo $passed ? 'success' : ( $failed ? 'danger' : 'warn' ); ?>"><?php echo esc_html( ucfirst( str_replace( '_', ' ', (string) $qatt['status'] ) ) ); ?></span></td>
										<td><small><?php echo esc_html( (string) ( $qatt['submitted_at'] ?: $qatt['started_at'] ) ); ?></small></td>
										<td><a href="<?php echo esc_url( add_query_arg( [ 'page' => 'rks-exams', 'mode' => 'attempt', 'attempt_id' => (int) $qatt['id'] ], admin_url( 'admin.php' ) ) ); ?>" class="button button-small"><?php echo Icons::svg( 'eye', 12 ); ?> Review</a></td>
									</tr>
								<?php endforeach; ?>
								</tbody>
							</table>
						<?php endif; ?>
					</div>

				</div>

				<!-- RIGHT sidebar -->
				<div>

					<div class="rks-admin-card">
						<div class="rks-section-head" style="margin-top:0;">
							<?php echo Icons::chip( 'chart-line', 'red' ); ?>
							<h2>At a Glance</h2>
						</div>
						<ul class="rks-quick-stats">
							<li><small>Enrolled</small><strong><?php echo (int) $stats['enrollments']; ?></strong></li>
							<li><small>Completed</small><strong><?php echo (int) $stats['completed']; ?></strong></li>
							<li><small>Certificates</small><strong><?php echo (int) $stats['certs']; ?></strong></li>
							<li><small>Admissions</small><strong><?php echo (int) $stats['admissions']; ?></strong></li>
						</ul>
						<dl class="rks-meta-list">
							<li><dt>Total Paid</dt><dd><strong style="color:#c92c39;font-size:18px;"><?php echo esc_html( $currency . number_format( $stats['paid'], 0 ) ); ?></strong></dd></li>
						</dl>
					</div>

					<div class="rks-admin-card">
						<div class="rks-section-head" style="margin-top:0;">
							<?php echo Icons::chip( 'user-circle', 'teal' ); ?>
							<h2>Account</h2>
						</div>
						<dl class="rks-meta-list">
							<li><dt>User ID</dt><dd>#<?php echo (int) $user->ID; ?></dd></li>
							<li><dt>Login</dt><dd><code><?php echo esc_html( $user->user_login ); ?></code></dd></li>
							<li><dt>Email</dt><dd><?php echo esc_html( $user->user_email ); ?></dd></li>
							<li><dt>Display Name</dt><dd><?php echo esc_html( $user->display_name ); ?></dd></li>
							<li><dt>Registered</dt><dd><?php echo esc_html( date( 'd M Y H:i', strtotime( $user->user_registered ) ) ); ?></dd></li>
							<?php if ( $branch ) : ?>
								<li><dt>Branch</dt><dd><strong><?php echo esc_html( (string) $branch['name'] ); ?></strong><br><small><?php echo esc_html( (string) $branch['city'] ); ?></small></dd></li>
							<?php endif; ?>
						</dl>
					</div>

				</div>

			</div>
		</div>
		<?php
	}
}
