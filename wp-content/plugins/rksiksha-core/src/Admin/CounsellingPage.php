<?php
/**
 * Admin page: Counselling leads queue + single-lead detail.
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

final class CounsellingPage {

	public const SLUG = 'rks-counselling';

	private const CAP = 'rks_view_global_reports';

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'register_menu' ], 33 );
		add_action( 'admin_post_rks_counselling_status', [ $this, 'handle_status_update' ] );
		add_action( 'admin_post_rks_counselling_note', [ $this, 'handle_add_note' ] );
		add_action( 'admin_post_rks_counselling_delete', [ $this, 'handle_delete' ] );
	}

	public function register_menu(): void {
		add_submenu_page(
			Menu::SLUG_SUPPORT,
			'University Admissions',
			'University Admissions',
			self::CAP,
			self::SLUG,
			[ $this, 'render' ]
		);
	}

	public function render(): void {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( 'Forbidden' );
		}

		$view_id = isset( $_GET['view'] ) ? (int) $_GET['view'] : 0;
		if ( $view_id > 0 ) {
			$this->render_detail( $view_id );
			return;
		}

		$this->render_list();
	}

	/* ================================================================== */
	/* LIST VIEW                                                            */
	/* ================================================================== */

	private function render_list(): void {
		global $wpdb;
		$table = DB::table( 'counselling_leads' );

		$status_filter = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( (string) $_GET['status'] ) ) : 'all';
		$search        = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['s'] ) ) : '';

		$where  = ' WHERE 1=1 ';
		$params = [];

		if ( in_array( $status_filter, [ 'new', 'contacted', 'converted', 'closed' ], true ) ) {
			$where   .= ' AND status = %s ';
			$params[] = $status_filter;
		}

		if ( '' !== $search ) {
			$where   .= ' AND ( full_name LIKE %s OR email LIKE %s OR phone LIKE %s OR city LIKE %s ) ';
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		$sql = "SELECT id, full_name, email, phone, city, stream, status, created_at FROM $table $where ORDER BY id DESC LIMIT 200";
		$rows = $params
			? $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A )
			: $wpdb->get_results( $sql, ARRAY_A );

		// Counts for tabs
		$counts = [
			'all'       => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table" ),
			'new'       => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE status='new'" ),
			'contacted' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE status='contacted'" ),
			'converted' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE status='converted'" ),
			'closed'    => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE status='closed'" ),
		];

		$msg = isset( $_GET['rks_msg'] ) ? sanitize_key( wp_unslash( (string) $_GET['rks_msg'] ) ) : '';
		?>
		<div class="wrap rks-admin-wrap">
			<header class="rks-page-hero">
				<div class="rks-page-hero__main">
					<div class="rks-page-hero__icon"><?php echo Icons::svg( 'help', 24 ); ?></div>
					<div>
						<small>Admissions</small>
						<h1>University</h1>
						<p>University admission enquiries. Reach out, track conversations, and convert prospects into admissions.</p>
					</div>
				</div>
				<div class="rks-page-hero__actions">
					<span class="rks-pill rks-pill--warn"><?php echo (int) $counts['new']; ?> new</span>
					<span class="rks-pill rks-pill--info"><?php echo (int) $counts['contacted']; ?> in progress</span>
				</div>
			</header>

			<?php if ( $msg ) : ?>
				<div class="notice notice-<?php echo $msg === 'error' ? 'error' : 'success'; ?> is-dismissible">
					<p><?php echo esc_html( ucfirst( str_replace( '_', ' ', $msg ) ) ); ?></p>
				</div>
			<?php endif; ?>

			<ul class="rks-page-filters">
				<?php foreach ( [ 'all' => 'All', 'new' => 'New', 'contacted' => 'Contacted', 'converted' => 'Converted', 'closed' => 'Closed' ] as $s => $label ) : ?>
					<li>
						<a href="<?php echo esc_url( add_query_arg( [ 'page' => self::SLUG, 'status' => $s ], admin_url( 'admin.php' ) ) ); ?>" class="<?php echo $status_filter === $s ? 'is-active' : ''; ?>">
							<?php echo esc_html( $label ); ?> <small>(<?php echo (int) ( $counts[ $s ] ?? 0 ); ?>)</small>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>

			<form method="get" style="margin:16px 0;">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::SLUG ); ?>">
				<input type="hidden" name="status" value="<?php echo esc_attr( $status_filter ); ?>">
				<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Search by name, email, phone, city..." style="width:340px;">
				<button class="button"><?php echo Icons::svg( 'search', 12 ); ?> Search</button>
				<?php if ( $search ) : ?>
					<a href="<?php echo esc_url( add_query_arg( [ 'page' => self::SLUG, 'status' => $status_filter ], admin_url( 'admin.php' ) ) ); ?>" class="button">Clear</a>
				<?php endif; ?>
			</form>

			<table class="widefat striped" style="margin-top:8px;">
				<thead>
					<tr>
						<th>ID</th>
						<th>Name</th>
						<th>Email</th>
						<th>Phone</th>
						<th>City</th>
						<th>Stream</th>
						<th>Status</th>
						<th>Created</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $rows ) ) : ?>
						<tr><td colspan="9"><div class="rks-empty"><?php echo Icons::chip( 'help', 'slate', 22 ); ?><h3>No leads found</h3><small>No counselling enquiries match this filter.</small></div></td></tr>
					<?php else : foreach ( $rows as $r ) :
						$pill_tone = $this->status_pill_tone( (string) $r['status'] );
					?>
						<tr>
							<td>#<?php echo (int) $r['id']; ?></td>
							<td><strong><?php echo esc_html( (string) $r['full_name'] ); ?></strong></td>
							<td><small><?php echo esc_html( (string) $r['email'] ); ?></small></td>
							<td><small><?php echo esc_html( (string) $r['phone'] ); ?></small></td>
							<td><?php echo esc_html( (string) ( $r['city'] ?: '—' ) ); ?></td>
							<td><?php echo esc_html( (string) ( $r['stream'] ?: '—' ) ); ?></td>
							<td><span class="rks-pill rks-pill--<?php echo esc_attr( $pill_tone ); ?>"><?php echo esc_html( (string) $r['status'] ); ?></span></td>
							<td><small><?php echo esc_html( (string) $r['created_at'] ); ?></small></td>
							<td>
								<a href="<?php echo esc_url( add_query_arg( [ 'page' => self::SLUG, 'view' => (int) $r['id'] ], admin_url( 'admin.php' ) ) ); ?>" class="button button-primary button-small"><?php echo Icons::svg( 'eye', 12 ); ?> View</a>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-left:4px;">
									<?php wp_nonce_field( 'rks_counselling_delete' ); ?>
									<input type="hidden" name="action" value="rks_counselling_delete">
									<input type="hidden" name="lead_id" value="<?php echo (int) $r['id']; ?>">
									<button type="submit" class="button button-small button-link-delete" onclick="return confirm('Permanently delete this counselling lead? This cannot be undone.');"><?php echo Icons::svg( 'trash', 12 ); ?> Delete</button>
								</form>
							</td>
						</tr>
					<?php endforeach; endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/* ================================================================== */
	/* DETAIL VIEW                                                          */
	/* ================================================================== */

	private function render_detail( int $id ): void {
		global $wpdb;
		$table = DB::table( 'counselling_leads' );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ), ARRAY_A );

		if ( ! $row ) {
			echo '<div class="wrap rks-admin-wrap"><div class="notice notice-error"><p>Counselling lead not found.</p></div></div>';
			return;
		}

		$msg = isset( $_GET['rks_msg'] ) ? sanitize_key( wp_unslash( (string) $_GET['rks_msg'] ) ) : '';

		$status    = (string) $row['status'];
		$pill_tone = $this->status_pill_tone( $status );

		$initials = strtoupper( substr( trim( (string) $row['full_name'] ), 0, 1 ) ?: 'C' );

		$created_ts = strtotime( (string) $row['created_at'] );
		$days_since = $created_ts ? max( 0, (int) floor( ( current_time( 'timestamp' ) - $created_ts ) / DAY_IN_SECONDS ) ) : 0;

		$assigned_user = ! empty( $row['assigned_to'] ) ? get_userdata( (int) $row['assigned_to'] ) : null;

		// Parse notes log — newline separated entries
		$notes_raw    = (string) ( $row['notes'] ?? '' );
		$notes_list   = array_values( array_filter( array_map( 'trim', preg_split( "/\r?\n/", $notes_raw ) ?: [] ) ) );

		?>
		<div class="wrap rks-admin-wrap">

			<p style="margin:14px 0;"><a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::SLUG ) ); ?>" class="button"><?php echo Icons::svg( 'arrow-left', 12 ); ?> All University Admissions</a></p>

			<div class="rks-detail-hero">
				<div class="rks-detail-hero__avatar"><?php echo esc_html( $initials ); ?></div>
				<div class="rks-detail-hero__main">
					<small>Counselling Lead · #<?php echo (int) $row['id']; ?></small>
					<h1><?php echo esc_html( (string) $row['full_name'] ); ?></h1>
					<p>
						<?php if ( ! empty( $row['city'] ) ) : ?>
							<?php echo Icons::svg( 'pin', 12 ); ?> <?php echo esc_html( (string) $row['city'] ); ?>
							&nbsp;·&nbsp;
						<?php endif; ?>
						<?php echo Icons::svg( 'mail', 12 ); ?> <?php echo esc_html( (string) $row['email'] ); ?>
						&nbsp;·&nbsp;
						<?php echo Icons::svg( 'phone', 12 ); ?> <?php echo esc_html( (string) $row['phone'] ); ?>
					</p>
					<p style="margin-top:8px;">
						<span class="rks-pill rks-pill--<?php echo esc_attr( $pill_tone ); ?>"><?php echo esc_html( $status ); ?></span>
						&nbsp;
						<small>Submitted <?php echo esc_html( human_time_diff( (int) $created_ts, current_time( 'timestamp' ) ) ); ?> ago</small>
					</p>
				</div>
				<div class="rks-detail-hero__actions">
					<a href="mailto:<?php echo esc_attr( (string) $row['email'] ); ?>" class="button"><?php echo Icons::svg( 'mail', 12 ); ?> Email</a>
					<a href="tel:<?php echo esc_attr( (string) $row['phone'] ); ?>" class="button"><?php echo Icons::svg( 'phone', 12 ); ?> Call</a>
					<a href="https://wa.me/<?php echo esc_attr( preg_replace( '/\D+/', '', (string) $row['phone'] ) ); ?>" target="_blank" rel="noopener" class="button button-primary"><?php echo Icons::svg( 'message', 12 ); ?> WhatsApp</a>
				</div>
			</div>

			<?php if ( $msg ) : ?>
				<div class="notice notice-<?php echo $msg === 'error' ? 'error' : 'success'; ?> is-dismissible"><p><?php echo esc_html( ucfirst( str_replace( '_', ' ', $msg ) ) ); ?></p></div>
			<?php endif; ?>

			<?php if ( in_array( $status, [ 'new', 'contacted' ], true ) ) : ?>
				<div class="rks-action-box">
					<h3><?php echo Icons::svg( 'alert', 14 ); ?> Action Required</h3>
					<p>Update the lead status as the conversation progresses. Aap apna note bhi yahan add kar sakte hain — har note timestamp ke saath save hota hai.</p>

					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-bottom:12px;">
						<?php wp_nonce_field( 'rks_counselling_action' ); ?>
						<input type="hidden" name="action" value="rks_counselling_status">
						<input type="hidden" name="lead_id" value="<?php echo (int) $row['id']; ?>">
						<textarea name="note" rows="3" style="width:100%;" placeholder="Add a note for this status update (optional but recommended)"></textarea>
						<div style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap;">
							<?php if ( 'new' === $status ) : ?>
								<button class="button button-primary" name="do" value="contacted"><?php echo Icons::svg( 'phone', 12 ); ?> Mark Contacted</button>
							<?php endif; ?>
							<button class="button button-primary" name="do" value="converted" onclick="return confirm('Mark this lead as Converted?');"><?php echo Icons::svg( 'check', 12 ); ?> Mark Converted</button>
							<button class="button button-link-delete" name="do" value="closed" onclick="return confirm('Close this lead without conversion?');"><?php echo Icons::svg( 'x-circle', 12 ); ?> Close Lead</button>
						</div>
					</form>

					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'rks_counselling_action' ); ?>
						<input type="hidden" name="action" value="rks_counselling_note">
						<input type="hidden" name="lead_id" value="<?php echo (int) $row['id']; ?>">
						<textarea name="note" rows="2" style="width:100%;" placeholder="Add a follow-up note (without changing status)" required></textarea>
						<button class="button" style="margin-top:8px;"><?php echo Icons::svg( 'plus', 12 ); ?> Add Note Only</button>
					</form>
				</div>
			<?php endif; ?>

			<div class="rks-detail-grid">

				<!-- LEFT: main content -->
				<div>

					<div class="rks-admin-card">
						<div class="rks-section-head" style="margin-top:0;">
							<?php echo Icons::chip( 'user', 'teal' ); ?>
							<h2>Lead Details</h2>
						</div>
						<dl class="rks-meta-list">
							<li><dt>Full Name</dt><dd><strong><?php echo esc_html( (string) $row['full_name'] ); ?></strong></dd></li>
							<li><dt>Email</dt><dd><a href="mailto:<?php echo esc_attr( (string) $row['email'] ); ?>"><?php echo esc_html( (string) $row['email'] ); ?></a></dd></li>
							<li><dt>Phone</dt><dd><a href="tel:<?php echo esc_attr( (string) $row['phone'] ); ?>"><?php echo esc_html( (string) $row['phone'] ); ?></a></dd></li>
							<li><dt>City</dt><dd><?php echo esc_html( (string) ( $row['city'] ?: '—' ) ); ?></dd></li>
							<li><dt>Qualification</dt><dd><?php echo esc_html( (string) ( $row['qualification'] ?: '—' ) ); ?></dd></li>
							<li><dt>Stream</dt><dd><?php echo esc_html( (string) ( $row['stream'] ?: '—' ) ); ?></dd></li>
							<li><dt>Preferred Colleges</dt><dd><?php echo nl2br( esc_html( (string) ( $row['preferred_colleges'] ?: '—' ) ) ); ?></dd></li>
						</dl>
					</div>

					<?php if ( ! empty( $row['message'] ) ) : ?>
					<div class="rks-admin-card">
						<div class="rks-section-head" style="margin-top:0;">
							<?php echo Icons::chip( 'message', 'gold' ); ?>
							<h2>Lead's Message</h2>
						</div>
						<p style="white-space:pre-line;color:#4a5763;line-height:1.6;"><?php echo esc_html( (string) $row['message'] ); ?></p>
					</div>
					<?php endif; ?>

					<div class="rks-admin-card">
						<div class="rks-section-head" style="margin-top:0;">
							<?php echo Icons::chip( 'history', 'lilac' ); ?>
							<h2>Notes &amp; Activity Log</h2>
							<small>· <?php echo count( $notes_list ); ?> entries</small>
						</div>
						<?php if ( empty( $notes_list ) ) : ?>
							<div class="rks-empty"><?php echo Icons::chip( 'message', 'slate', 22 ); ?><h3>No notes yet</h3><small>Add a note above when you contact this lead.</small></div>
						<?php else : ?>
							<ul class="rks-timeline">
								<?php foreach ( array_reverse( $notes_list ) as $note ) : ?>
									<li class="is-done">
										<span class="rks-timeline__dot"><?php echo Icons::svg( 'message', 12 ); ?></span>
										<strong style="white-space:pre-line;"><?php echo esc_html( $note ); ?></strong>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>

				</div>

				<!-- RIGHT: sidebar -->
				<div>

					<div class="rks-admin-card">
						<div class="rks-section-head" style="margin-top:0;">
							<?php echo Icons::chip( 'history', 'slate' ); ?>
							<h2>Status Timeline</h2>
						</div>
						<ul class="rks-timeline">
							<li class="is-done">
								<span class="rks-timeline__dot"><?php echo Icons::svg( 'check', 12 ); ?></span>
								<strong>Submitted</strong>
								<small><?php echo esc_html( (string) $row['created_at'] ); ?></small>
							</li>
							<li class="<?php echo in_array( $status, [ 'contacted', 'converted', 'closed' ], true ) ? 'is-done' : 'is-pending'; ?>">
								<span class="rks-timeline__dot"><?php echo Icons::svg( in_array( $status, [ 'contacted', 'converted', 'closed' ], true ) ? 'check' : 'clock', 12 ); ?></span>
								<strong>Contacted</strong>
								<small><?php echo in_array( $status, [ 'contacted', 'converted', 'closed' ], true ) ? 'Reached out' : 'Pending outreach'; ?></small>
							</li>
							<li class="<?php echo $status === 'converted' ? 'is-done' : ( $status === 'closed' ? 'is-error' : 'is-pending' ); ?>">
								<span class="rks-timeline__dot"><?php echo Icons::svg( $status === 'converted' ? 'check' : ( $status === 'closed' ? 'x' : 'clock' ), 12 ); ?></span>
								<strong><?php echo $status === 'closed' ? 'Closed' : 'Converted'; ?></strong>
								<small>
									<?php
									if ( $status === 'converted' ) {
										echo 'Lead converted to admission';
									} elseif ( $status === 'closed' ) {
										echo 'Lead closed';
									} else {
										echo 'Pending decision';
									}
									?>
								</small>
							</li>
						</ul>
					</div>

					<div class="rks-admin-card">
						<div class="rks-section-head" style="margin-top:0;">
							<?php echo Icons::chip( 'chart-line', 'red' ); ?>
							<h2>Quick Stats</h2>
						</div>
						<ul class="rks-quick-stats">
							<li><small>Days since lead</small><strong><?php echo (int) $days_since; ?></strong></li>
							<li><small>Status</small><strong><?php echo esc_html( $status ); ?></strong></li>
						</ul>
						<dl class="rks-meta-list">
							<li><dt>Assigned To</dt><dd>
								<?php if ( $assigned_user ) : ?>
									<strong><?php echo esc_html( $assigned_user->display_name ?: $assigned_user->user_login ); ?></strong>
									<small style="display:block;color:#6b7a82;"><?php echo esc_html( $assigned_user->user_email ); ?></small>
								<?php else : ?>
									<em>Unassigned</em>
								<?php endif; ?>
							</dd></li>
							<li><dt>Last Updated</dt><dd><?php echo esc_html( (string) $row['updated_at'] ); ?></dd></li>
						</dl>
					</div>

					<div class="rks-admin-card">
						<div class="rks-section-head" style="margin-top:0;">
							<?php echo Icons::chip( 'info', 'teal' ); ?>
							<h2>Lead Meta</h2>
						</div>
						<dl class="rks-meta-list">
							<li><dt>Lead ID</dt><dd>#<?php echo (int) $row['id']; ?></dd></li>
							<li><dt>Created</dt><dd><?php echo esc_html( (string) $row['created_at'] ); ?></dd></li>
							<li><dt>Updated</dt><dd><?php echo esc_html( (string) $row['updated_at'] ); ?></dd></li>
						</dl>
					</div>

				</div>

			</div><!-- /detail-grid -->

			<div class="rks-admin-card rks-admin-card--alert" style="margin-top:24px;">
				<div class="rks-section-head" style="margin-top:0;">
					<?php echo Icons::chip( 'trash', 'red' ); ?>
					<h2>Danger Zone</h2>
				</div>
				<p>Permanently delete this counselling lead and its internal notes.</p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'rks_counselling_delete' ); ?>
					<input type="hidden" name="action" value="rks_counselling_delete">
					<input type="hidden" name="lead_id" value="<?php echo (int) $row['id']; ?>">
					<button type="submit" class="button button-link-delete" onclick="return confirm('Permanently delete this counselling lead? This cannot be undone.');">
						<?php echo Icons::svg( 'trash', 12 ); ?> Delete Counselling Lead
					</button>
				</form>
			</div>

		</div>
		<?php
	}

	/* ================================================================== */
	/* HANDLERS                                                             */
	/* ================================================================== */

	public function handle_status_update(): void {
		check_admin_referer( 'rks_counselling_action' );
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( 'Forbidden', 'Forbidden', [ 'response' => 403 ] );
		}

		$id     = (int) ( $_POST['lead_id'] ?? 0 );
		$action = sanitize_key( wp_unslash( (string) ( $_POST['do'] ?? '' ) ) );
		$note   = sanitize_textarea_field( wp_unslash( (string) ( $_POST['note'] ?? '' ) ) );

		$allowed = [ 'contacted', 'converted', 'closed' ];
		if ( $id <= 0 || ! in_array( $action, $allowed, true ) ) {
			wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG, 'rks_msg' => 'error' ], admin_url( 'admin.php' ) ) );
			exit;
		}

		global $wpdb;
		$table = DB::table( 'counselling_leads' );

		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT id, notes FROM $table WHERE id = %d", $id ), ARRAY_A );
		if ( ! $existing ) {
			wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG, 'rks_msg' => 'error' ], admin_url( 'admin.php' ) ) );
			exit;
		}

		$user      = wp_get_current_user();
		$user_name = $user && $user->ID ? ( $user->display_name ?: $user->user_login ) : 'system';
		$stamp     = current_time( 'mysql' );

		$entry  = sprintf( '[%s] %s → status: %s', $stamp, $user_name, $action );
		if ( '' !== $note ) {
			$entry .= ' — ' . $note;
		}

		$new_notes = trim( (string) ( $existing['notes'] ?? '' ) );
		$new_notes = $new_notes === '' ? $entry : ( $new_notes . "\n" . $entry );

		$updated = $wpdb->query( $wpdb->prepare(
			"UPDATE $table SET status = %s, notes = %s, updated_at = %s WHERE id = %d",
			$action,
			$new_notes,
			$stamp,
			$id
		) );

		$msg = ( false === $updated ) ? 'error' : 'status_updated';

		wp_safe_redirect( add_query_arg(
			[ 'page' => self::SLUG, 'view' => $id, 'rks_msg' => $msg ],
			admin_url( 'admin.php' )
		) );
		exit;
	}

	public function handle_add_note(): void {
		check_admin_referer( 'rks_counselling_action' );
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( 'Forbidden', 'Forbidden', [ 'response' => 403 ] );
		}

		$id   = (int) ( $_POST['lead_id'] ?? 0 );
		$note = sanitize_textarea_field( wp_unslash( (string) ( $_POST['note'] ?? '' ) ) );

		if ( $id <= 0 || '' === $note ) {
			wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG, 'view' => $id, 'rks_msg' => 'error' ], admin_url( 'admin.php' ) ) );
			exit;
		}

		global $wpdb;
		$table = DB::table( 'counselling_leads' );

		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT id, notes FROM $table WHERE id = %d", $id ), ARRAY_A );
		if ( ! $existing ) {
			wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG, 'rks_msg' => 'error' ], admin_url( 'admin.php' ) ) );
			exit;
		}

		$user      = wp_get_current_user();
		$user_name = $user && $user->ID ? ( $user->display_name ?: $user->user_login ) : 'system';
		$stamp     = current_time( 'mysql' );

		$entry     = sprintf( '[%s] %s — %s', $stamp, $user_name, $note );
		$new_notes = trim( (string) ( $existing['notes'] ?? '' ) );
		$new_notes = $new_notes === '' ? $entry : ( $new_notes . "\n" . $entry );

		$wpdb->query( $wpdb->prepare(
			"UPDATE $table SET notes = %s, updated_at = %s WHERE id = %d",
			$new_notes,
			$stamp,
			$id
		) );

		wp_safe_redirect( add_query_arg(
			[ 'page' => self::SLUG, 'view' => $id, 'rks_msg' => 'note_added' ],
			admin_url( 'admin.php' )
		) );
		exit;
	}

	public function handle_delete(): void {
		check_admin_referer( 'rks_counselling_delete' );
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( 'Forbidden', 'Forbidden', [ 'response' => 403 ] );
		}

		$id = (int) ( $_POST['lead_id'] ?? 0 );
		if ( $id <= 0 ) {
			wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG, 'rks_msg' => 'error' ], admin_url( 'admin.php' ) ) );
			exit;
		}

		global $wpdb;
		$deleted = $wpdb->delete( DB::table( 'counselling_leads' ), [ 'id' => $id ], [ '%d' ] );

		wp_safe_redirect( add_query_arg(
			[ 'page' => self::SLUG, 'rks_msg' => false === $deleted ? 'error' : 'deleted' ],
			admin_url( 'admin.php' )
		) );
		exit;
	}

	/* ================================================================== */
	/* HELPERS                                                              */
	/* ================================================================== */

	private function status_pill_tone( string $status ): string {
		switch ( $status ) {
			case 'new':       return 'warn';
			case 'contacted': return 'info';
			case 'converted': return 'success';
			case 'closed':    return 'slate';
			default:          return 'info';
		}
	}
}
