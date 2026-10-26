<?php
/**
 * Admin page: Admissions queue + single admission/payment detail + payments audit.
 *
 * @package RKSiksha\Core
 */

declare( strict_types=1 );

namespace RKSiksha\Core\Admin;

use RKSiksha\Core\Modules\Admissions;
use RKSiksha\Core\Modules\Branches;
use RKSiksha\Core\Modules\Payments;
use RKSiksha\Core\Support\DB;
use RKSiksha\Core\Support\Icons;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AdmissionsPage {

	public const SLUG_LIST     = 'rks-admissions';
	public const SLUG_PAYMENTS = 'rks-payments';

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'register_menu' ], 30 );
		add_action( 'admin_post_rks_admission_action', [ $this, 'handle_action' ] );
		add_action( 'admin_post_rks_admission_bulk',   [ $this, 'handle_bulk' ] );
		add_action( 'admin_post_rks_admission_delete', [ $this, 'handle_admission_delete' ] );
		add_action( 'admin_post_rks_payment_refund',   [ $this, 'handle_refund' ] );
		add_action( 'admin_post_rks_payment_action',   [ $this, 'handle_payment_action' ] );
		add_action( 'admin_post_rks_payment_delete',   [ $this, 'handle_payment_delete' ] );
		add_action( 'admin_post_rks_admission_add',    [ $this, 'handle_add_admission' ] );
	}

	public function register_menu(): void {
		add_submenu_page( Menu::SLUG_OPERATIONS, 'Admissions', 'Admissions', 'rks_approve_admission', self::SLUG_LIST,    [ $this, 'render_admissions' ] );
		add_submenu_page( Menu::SLUG_OPERATIONS, 'Payments',   'Payments',   'rks_verify_payment',    self::SLUG_PAYMENTS, [ $this, 'render_payments' ] );
	}

	public function handle_action(): void {
		check_admin_referer( 'rks_admission_action' );
		if ( ! current_user_can( 'rks_approve_admission' ) ) wp_die( 'Forbidden', 'Forbidden', [ 'response' => 403 ] );

		$id     = isset( $_POST['admission_id'] ) ? (int) $_POST['admission_id'] : 0;
		$action = sanitize_key( wp_unslash( (string) ( $_POST['do'] ?? '' ) ) );
		$note   = sanitize_textarea_field( wp_unslash( (string) ( $_POST['note'] ?? '' ) ) );

		if ( $id <= 0 ) wp_die( 'Invalid', 'Invalid', [ 'response' => 400 ] );

		$user = wp_get_current_user();
		$adm_row     = ( new Admissions() )->get( $id );
		$adm_branch  = (int) ( $adm_row['branch_id'] ?? 0 );
		$user_branch = (int) get_user_meta( $user->ID, 'rks_branch_id', true );

		if ( ! current_user_can( 'rks_view_all_admissions' ) ) {
			if ( $adm_branch !== $user_branch ) wp_die( 'Forbidden — wrong branch', 'Forbidden', [ 'response' => 403 ] );
		} else {
			// Super admin: block approving admissions/payments belonging to franchise branches.
			// Franchise owners must verify their own transactions.
			$branch = $adm_branch > 0 ? ( new Branches() )->get( $adm_branch ) : null;
			if ( $branch && (string) $branch['type'] === Branches::TYPE_FRANCHISE && $adm_branch !== $user_branch ) {
				wp_die( 'Forbidden — franchise transactions must be verified by the franchise owner, not the super admin.', 'Forbidden', [ 'response' => 403 ] );
			}
		}

		$admissions = new Admissions();
		if ( 'approve' === $action ) {
			$result = $admissions->approve( $id, $user->ID );
			$msg    = $result['ok'] ? 'approved' : 'error';
		} elseif ( 'reject' === $action ) {
			$result = $admissions->reject( $id, $user->ID, $note );
			$msg    = $result['ok'] ? 'rejected' : 'error';
		} else {
			$msg = 'unknown';
		}

		$redir = isset( $_POST['return'] ) && (string) $_POST['return'] === 'view'
			? add_query_arg( [ 'page' => self::SLUG_LIST, 'view' => $id, 'rks_msg' => $msg ], admin_url( 'admin.php' ) )
			: add_query_arg( [ 'page' => self::SLUG_LIST, 'rks_msg' => $msg ], admin_url( 'admin.php' ) );

		wp_safe_redirect( $redir );
		exit;
	}

	public function handle_bulk(): void {
		check_admin_referer( 'rks_admission_bulk' );
		if ( ! current_user_can( 'rks_approve_admission' ) ) wp_die( 'Forbidden', 'Forbidden', [ 'response' => 403 ] );

		$action = sanitize_key( wp_unslash( (string) ( $_POST['bulk_action'] ?? '' ) ) );
		$ids    = array_map( 'intval', (array) ( $_POST['ids'] ?? [] ) );
		$ids    = array_filter( $ids, static fn( $i ) => $i > 0 );
		if ( empty( $ids ) || ! in_array( $action, [ 'approve', 'reject' ], true ) ) {
			wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG_LIST, 'rks_msg' => 'nothing' ], admin_url( 'admin.php' ) ) );
			exit;
		}

		$user        = wp_get_current_user();
		$is_super    = current_user_can( 'rks_view_all_admissions' );
		$user_branch = (int) get_user_meta( $user->ID, 'rks_branch_id', true );
		$admissions  = new Admissions();
		$branches    = new Branches();
		$ok = 0;
		foreach ( $ids as $id ) {
			$row = $admissions->get( $id );
			if ( ! $row ) continue;
			$adm_branch = (int) $row['branch_id'];
			if ( ! $is_super && $adm_branch !== $user_branch ) continue;
			// Super admin cannot bulk-approve franchise branch transactions.
			if ( $is_super && $adm_branch !== $user_branch ) {
				$b = $branches->get( $adm_branch );
				if ( $b && (string) $b['type'] === Branches::TYPE_FRANCHISE ) continue;
			}
			if ( ! in_array( (string) $row['status'], [ 'pending', 'payment_review' ], true ) ) continue;
			$res = $action === 'approve'
				? $admissions->approve( $id, $user->ID )
				: $admissions->reject( $id, $user->ID, 'Bulk rejection' );
			if ( ! empty( $res['ok'] ) ) $ok++;
		}

		wp_safe_redirect( add_query_arg( [
			'page'     => self::SLUG_LIST,
			'rks_msg'  => 'bulk_' . $action,
			'rks_cnt'  => $ok,
		], admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_refund(): void {
		check_admin_referer( 'rks_payment_refund' );
		if ( ! current_user_can( 'rks_verify_payment' ) ) wp_die( 'Forbidden', 'Forbidden', [ 'response' => 403 ] );

		$pid  = (int) ( $_POST['payment_id'] ?? 0 );
		$note = sanitize_textarea_field( wp_unslash( (string) ( $_POST['note'] ?? '' ) ) );
		if ( $pid <= 0 ) wp_die( 'Invalid', 'Invalid', [ 'response' => 400 ] );

		$payment = ( new Payments() )->get( $pid );
		if ( ! $payment ) wp_die( 'Payment not found', 'Not found', [ 'response' => 404 ] );

		$is_super    = current_user_can( 'rks_view_all_payments' );
		$user_branch = (int) get_user_meta( get_current_user_id(), 'rks_branch_id', true );
		$pay_branch  = (int) $payment['branch_id'];
		if ( ! $is_super && $pay_branch !== $user_branch ) wp_die( 'Forbidden — wrong branch', 'Forbidden', [ 'response' => 403 ] );

		// Super admin cannot touch franchise branch transactions.
		if ( $is_super && $pay_branch !== $user_branch ) {
			$b = ( new Branches() )->get( $pay_branch );
			if ( $b && (string) $b['type'] === Branches::TYPE_FRANCHISE ) {
				wp_die( 'Forbidden — franchise transactions must be handled by the franchise owner.', 'Forbidden', [ 'response' => 403 ] );
			}
		}

		( new Payments() )->refund( $pid, get_current_user_id(), $note );

		wp_safe_redirect( add_query_arg( [
			'page'    => self::SLUG_PAYMENTS,
			'view'    => $pid,
			'rks_msg' => 'refunded',
		], admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_payment_action(): void {
		check_admin_referer( 'rks_payment_action' );
		if ( ! current_user_can( 'rks_verify_payment' ) ) wp_die( 'Forbidden', 'Forbidden', [ 'response' => 403 ] );

		$id     = isset( $_POST['payment_id'] ) ? (int) $_POST['payment_id'] : 0;
		$action = sanitize_key( wp_unslash( (string) ( $_POST['do'] ?? '' ) ) );
		$note   = sanitize_textarea_field( wp_unslash( (string) ( $_POST['note'] ?? '' ) ) );

		if ( $id <= 0 ) wp_die( 'Invalid', 'Invalid', [ 'response' => 400 ] );

		$user = wp_get_current_user();
		$payments = new Payments();
		$pay_row     = $payments->get( $id );
		if ( ! $pay_row ) wp_die( 'Payment not found', 'Not found', [ 'response' => 404 ] );

		$pay_branch  = (int) ( $pay_row['branch_id'] ?? 0 );
		$user_branch = (int) get_user_meta( $user->ID, 'rks_branch_id', true );

		if ( ! current_user_can( 'rks_view_all_payments' ) ) {
			if ( $pay_branch !== $user_branch ) wp_die( 'Forbidden — wrong branch', 'Forbidden', [ 'response' => 403 ] );
		} else {
			// Super admin: block approving admissions/payments belonging to franchise branches.
			$branch = $pay_branch > 0 ? ( new Branches() )->get( $pay_branch ) : null;
			if ( $branch && (string) $branch['type'] === Branches::TYPE_FRANCHISE && $pay_branch !== $user_branch ) {
				wp_die( 'Forbidden — franchise transactions must be verified by the franchise owner, not the super admin.', 'Forbidden', [ 'response' => 403 ] );
			}
		}

		if ( 'approve' === $action ) {
			$result = $payments->approve( $id, $user->ID );
			$msg    = $result ? 'approved' : 'error';
		} elseif ( 'reject' === $action ) {
			$result = $payments->reject( $id, $user->ID, $note );
			$msg    = $result ? 'rejected' : 'error';
		} else {
			$msg = 'unknown';
		}

		$redir = add_query_arg( [ 'page' => self::SLUG_PAYMENTS, 'view' => $id, 'rks_msg' => $msg ], admin_url( 'admin.php' ) );
		wp_safe_redirect( $redir );
		exit;
	}

	public function handle_admission_delete(): void {
		check_admin_referer( 'rks_admission_delete' );
		if ( ! current_user_can( 'rks_approve_admission' ) ) wp_die( 'Forbidden', 'Forbidden', [ 'response' => 403 ] );

		$id = (int) ( $_POST['admission_id'] ?? 0 );
		if ( $id <= 0 ) wp_die( 'Invalid admission ID', 'Invalid', [ 'response' => 400 ] );

		$adm = ( new Admissions() )->get( $id );
		if ( ! $adm ) {
			wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG_LIST, 'rks_msg' => 'not_found' ], admin_url( 'admin.php' ) ) );
			exit;
		}

		if ( ! $this->can_access_admission_record( $adm ) ) {
			wp_die( 'Forbidden - wrong branch', 'Forbidden', [ 'response' => 403 ] );
		}

		global $wpdb;
		$wpdb->query( $wpdb->prepare( 'UPDATE ' . DB::table( 'payments' ) . ' SET admission_id = NULL WHERE admission_id = %d', $id ) );
		$deleted = $wpdb->delete( DB::table( 'admissions' ), [ 'id' => $id ], [ '%d' ] );

		wp_safe_redirect( add_query_arg(
			[ 'page' => self::SLUG_LIST, 'rks_msg' => false === $deleted ? 'error' : 'deleted' ],
			admin_url( 'admin.php' )
		) );
		exit;
	}

	public function handle_payment_delete(): void {
		check_admin_referer( 'rks_payment_delete' );
		if ( ! current_user_can( 'rks_verify_payment' ) ) wp_die( 'Forbidden', 'Forbidden', [ 'response' => 403 ] );

		$pid = (int) ( $_POST['payment_id'] ?? 0 );
		if ( $pid <= 0 ) wp_die( 'Invalid payment ID', 'Invalid', [ 'response' => 400 ] );

		$payment = ( new Payments() )->get( $pid );
		if ( ! $payment ) {
			wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG_PAYMENTS, 'rks_msg' => 'not_found' ], admin_url( 'admin.php' ) ) );
			exit;
		}

		if ( ! $this->can_access_payment_record( $payment ) ) {
			wp_die( 'Forbidden - wrong branch', 'Forbidden', [ 'response' => 403 ] );
		}

		global $wpdb;
		$wpdb->query( $wpdb->prepare( 'UPDATE ' . DB::table( 'admissions' ) . ' SET payment_id = NULL WHERE payment_id = %d', $pid ) );
		$deleted = $wpdb->delete( DB::table( 'payments' ), [ 'id' => $pid ], [ '%d' ] );

		wp_safe_redirect( add_query_arg(
			[ 'page' => self::SLUG_PAYMENTS, 'rks_msg' => false === $deleted ? 'error' : 'deleted' ],
			admin_url( 'admin.php' )
		) );
		exit;
	}

	public function render_admissions(): void {
		if ( ! current_user_can( 'rks_approve_admission' ) ) wp_die( 'Forbidden' );

		$action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : '';
		if ( 'add' === $action ) { $this->render_add_admission_form(); return; }

		$view_id = isset( $_GET['view'] ) ? (int) $_GET['view'] : 0;
		if ( $view_id > 0 ) { $this->render_admission_detail( $view_id ); return; }

		$this->render_admissions_list();
	}

	private function render_admissions_list(): void {
		$user        = wp_get_current_user();
		$is_super    = current_user_can( 'rks_view_all_admissions' );
		$user_branch = (int) get_user_meta( $user->ID, 'rks_branch_id', true );

		$status_filter = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( (string) $_GET['status'] ) ) : 'all';

		$args = [ 'limit' => 100 ];
		if ( ! $is_super && $user_branch > 0 ) $args['branch_id'] = $user_branch;
		if ( 'all' !== $status_filter )        $args['status']    = $status_filter;

		$rows = ( new Admissions() )->list( $args );
		$msg  = isset( $_GET['rks_msg'] ) ? sanitize_key( wp_unslash( (string) $_GET['rks_msg'] ) ) : '';
		?>
		<div class="wrap rks-admin-wrap">
			<header class="rks-page-hero">
				<div class="rks-page-hero__main">
					<div class="rks-page-hero__icon"><?php echo Icons::svg( 'inbox', 24 ); ?></div>
					<div>
						<small><?php echo $is_super ? 'All Branches' : 'Your Branch Only'; ?></small>
						<h1>Admissions Queue</h1>
						<p>Review applications, verify payments, approve or reject student admissions.</p>
					</div>
				</div>
				<div class="rks-page-hero__actions">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::SLUG_PAYMENTS ) ); ?>" class="button"><?php echo Icons::svg( 'wallet', 14 ); ?> Payments</a>
					<a href="<?php echo esc_url( add_query_arg( [ 'page' => self::SLUG_LIST, 'action' => 'add' ], admin_url( 'admin.php' ) ) ); ?>" class="button button-primary"><?php echo Icons::svg( 'plus', 14 ); ?> Add New Admission</a>
					<a href="<?php echo esc_url( home_url( '/admission/' ) ); ?>" target="_blank" class="button"><?php echo Icons::svg( 'external-link', 14 ); ?> Frontend Form</a>
				</div>
			</header>

			<?php
			$cnt = isset( $_GET['rks_cnt'] ) ? (int) $_GET['rks_cnt'] : 0;
			if ( $msg ) :
				$msg_label = $msg;
				$msg_kind  = 'success';
				if ( $msg === 'bulk_approve' ) $msg_label = $cnt . ' admission(s) approved in bulk';
				elseif ( $msg === 'bulk_reject' ) { $msg_label = $cnt . ' admission(s) rejected in bulk'; $msg_kind = 'warning'; }
				elseif ( $msg === 'nothing' )    { $msg_label = 'No selection or invalid action.'; $msg_kind = 'warning'; }
				elseif ( $msg === 'error' )       $msg_kind = 'error';
			?>
				<div class="notice notice-<?php echo esc_attr( $msg_kind ); ?> is-dismissible"><p><?php echo esc_html( ucfirst( str_replace( '_', ' ', (string) $msg_label ) ) ); ?></p></div>
			<?php endif; ?>

			<ul class="rks-page-filters">
				<?php foreach ( [ 'all' => 'All', 'pending' => 'Pending', 'payment_review' => 'Payment Review', 'approved' => 'Approved', 'rejected' => 'Rejected' ] as $s => $label ) : ?>
					<li><a href="<?php echo esc_url( add_query_arg( [ 'page' => self::SLUG_LIST, 'status' => $s ], admin_url( 'admin.php' ) ) ); ?>" class="<?php echo $status_filter === $s ? 'is-active' : ''; ?>"><?php echo esc_html( $label ); ?></a></li>
				<?php endforeach; ?>
			</ul>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'rks_admission_bulk' ); ?>
			<input type="hidden" name="action" value="rks_admission_bulk">
			<div style="display:flex;gap:8px;align-items:center;margin:18px 0 6px;">
				<select name="bulk_action" style="min-width:180px;">
					<option value="">— Bulk Action —</option>
					<option value="approve">Approve Selected</option>
					<option value="reject">Reject Selected</option>
				</select>
				<button type="submit" class="button button-primary" onclick="return confirm('Apply bulk action to selected admissions?');"><?php echo Icons::svg( 'check', 12 ); ?> Apply</button>
				<small style="color:#6b7a82;">Tip: only pending / payment_review rows will be processed.</small>
			</div>
			<div class="rks-table-scroll rks-admissions-table-wrap">
			<table class="widefat striped rks-admissions-table">
				<thead><tr><th style="width:28px;"><input type="checkbox" onclick="document.querySelectorAll('input[name=&quot;ids[]&quot;]').forEach(c=>c.checked=this.checked);"></th><th>Ref</th><th>Student</th><th>Course</th><th>Branch</th><th>Fee</th><th>Payment</th><th>Status</th><th>Submitted</th><th>Actions</th></tr></thead>
				<tbody>
					<?php if ( empty( $rows ) ) : ?>
						<tr><td colspan="10"><div class="rks-empty"><?php echo Icons::chip( 'inbox', 'slate', 22 ); ?><h3>No admissions found</h3><small>No admissions match this filter.</small></div></td></tr>
					<?php else : foreach ( $rows as $r ) :
						$ref = 'ADM-' . substr( (string) $r['created_at'], 0, 4 ) . '-' . str_pad( (string) $r['id'], 5, '0', STR_PAD_LEFT );
						$ref_short = '#' . str_pad( (string) $r['id'], 5, '0', STR_PAD_LEFT );
						$pay = $r['payment_id'] ? ( new Payments() )->get( (int) $r['payment_id'] ) : null;
						$selectable = in_array( (string) $r['status'], [ 'pending', 'payment_review' ], true );
						$submitted_ts = strtotime( (string) $r['created_at'] );
						$submitted_display = $submitted_ts ? date_i18n( 'd M y H:i', $submitted_ts ) : (string) $r['created_at'];
					?>
						<tr>
							<td><?php if ( $selectable ) : ?><input type="checkbox" name="ids[]" value="<?php echo (int) $r['id']; ?>"><?php endif; ?></td>
							<td><code title="<?php echo esc_attr( $ref ); ?>"><?php echo esc_html( $ref_short ); ?></code></td>
							<td>
								<strong><?php echo esc_html( (string) $r['full_name'] ); ?></strong><br>
								<small><?php echo esc_html( (string) $r['email'] ); ?></small>
							</td>
							<td><?php echo esc_html( (string) $r['course_title'] ); ?></td>
							<td>#<?php echo (int) $r['branch_id']; ?></td>
							<td>₹<?php echo number_format( (float) $r['fees_amount'], 0 ); ?></td>
							<td>
								<?php if ( $pay ) : ?>
									<?php
									$utr = (string) ( $pay['utr'] ?? '' );
									$utr_short = strlen( $utr ) > 6 ? substr( $utr, -6 ) : $utr;
									?>
									<strong>₹<?php echo number_format( (float) $pay['amount'], 0 ); ?></strong><br>
									<small title="<?php echo esc_attr( 'UTR: ' . $utr ); ?>">UTR: <code><?php echo esc_html( $utr_short ?: '—' ); ?></code></small>
								<?php else : ?><em>—</em><?php endif; ?>
							</td>
							<td><span class="rks-tag rks-tag--<?php echo esc_attr( (string) $r['status'] ); ?>"><?php echo esc_html( str_replace( '_', ' ', (string) $r['status'] ) ); ?></span></td>
							<td><small><?php echo esc_html( $submitted_display ); ?></small></td>
							<td class="rks-actions-cell">
								<div class="rks-row-actions">
									<a href="<?php echo esc_url( add_query_arg( [ 'page' => self::SLUG_LIST, 'view' => (int) $r['id'] ], admin_url( 'admin.php' ) ) ); ?>" class="button button-primary button-small" title="View admission" aria-label="View admission"><?php echo Icons::svg( 'eye', 12 ); ?></a>
									<a href="<?php echo esc_url( add_query_arg( 'rks_print', $ref, home_url( '/admission/' ) ) ); ?>" target="_blank" class="button button-small" title="Download admission form" aria-label="Download admission form"><?php echo Icons::svg( 'file-text', 12 ); ?></a>
									<button type="submit" form="rks-admission-delete-<?php echo (int) $r['id']; ?>" class="button button-small button-link-delete" title="Delete admission" aria-label="Delete admission" onclick="return confirm('Permanently delete this admission record? Linked student account, enrollments, and payment records will not be removed.');"><?php echo Icons::svg( 'trash', 12 ); ?></button>
								</div>
							</td>
						</tr>
					<?php endforeach; endif; ?>
				</tbody>
			</table>
			</div>
			</form>
			<?php if ( ! empty( $rows ) ) : foreach ( $rows as $r ) : ?>
				<form id="rks-admission-delete-<?php echo (int) $r['id']; ?>" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:none;">
					<?php wp_nonce_field( 'rks_admission_delete' ); ?>
					<input type="hidden" name="action" value="rks_admission_delete">
					<input type="hidden" name="admission_id" value="<?php echo (int) $r['id']; ?>">
				</form>
			<?php endforeach; endif; ?>
		</div>
		<?php
	}

	private function render_admission_detail( int $id ): void {
		$admissions = new Admissions();
		$adm        = $admissions->get( $id );
		if ( ! $adm ) {
			echo '<div class="wrap rks-admin-wrap"><div class="notice notice-error"><p>Admission not found.</p></div></div>';
			return;
		}

		$is_super    = current_user_can( 'rks_view_all_admissions' );
		$user_branch = (int) get_user_meta( get_current_user_id(), 'rks_branch_id', true );
		if ( ! $is_super && (int) $adm['branch_id'] !== $user_branch ) {
			wp_die( 'Forbidden — wrong branch', 'Forbidden', [ 'response' => 403 ] );
		}

		$msg = isset( $_GET['rks_msg'] ) ? sanitize_key( wp_unslash( (string) $_GET['rks_msg'] ) ) : '';

		$settings = (array) get_option( 'rks_settings', [] );
		$currency = (string) ( $settings['currency_symbol'] ?? '₹' );

		$branch  = ( new Branches() )->get( (int) $adm['branch_id'] );
		$payment = ! empty( $adm['payment_id'] ) ? ( new Payments() )->get( (int) $adm['payment_id'] ) : null;
		$student_user = ! empty( $adm['user_id'] ) ? get_userdata( (int) $adm['user_id'] ) : null;
		$reviewer     = ! empty( $adm['reviewed_by'] ) ? get_userdata( (int) $adm['reviewed_by'] ) : null;
		$course       = ! empty( $adm['course_id'] ) ? get_post( (int) $adm['course_id'] ) : null;

		$ref      = 'ADM-' . substr( (string) $adm['created_at'], 0, 4 ) . '-' . str_pad( (string) $adm['id'], 5, '0', STR_PAD_LEFT );
		$initials = strtoupper( substr( trim( (string) $adm['full_name'] ), 0, 1 ) ?: 'S' );

		// Meta JSON if present
		$meta = [];
		if ( ! empty( $adm['meta'] ) ) {
			$decoded = json_decode( (string) $adm['meta'], true );
			if ( is_array( $decoded ) ) $meta = $decoded;
		}
		$documents = isset( $meta['documents'] ) && is_array( $meta['documents'] ) ? $meta['documents'] : [];
		$student_details = isset( $meta['student_details'] ) && is_array( $meta['student_details'] ) ? $meta['student_details'] : [];
		$course_category_label = ! empty( $meta['course_category_label'] ) ? (string) $meta['course_category_label'] : '';
		$fee_meta = isset( $meta['fees'] ) && is_array( $meta['fees'] ) ? $meta['fees'] : [];
		$has_fee_meta = ! empty( $fee_meta );
		$admission_fee = $has_fee_meta ? (float) ( $fee_meta['admission_fee'] ?? 500 ) : 0.0;
		$course_fee = $has_fee_meta ? (float) ( $fee_meta['course_fee'] ?? 0 ) : (float) $adm['fees_amount'];
		$payable_now = (float) ( $fee_meta['payable_now'] ?? (float) $adm['fees_amount'] );
		$course_total = (float) ( $fee_meta['course_total'] ?? $course_fee );
		$print_url = add_query_arg( 'rks_print', $ref, home_url( '/admission/' ) );
		$display_meta = $meta;
		unset( $display_meta['documents'], $display_meta['course_category'], $display_meta['course_category_label'], $display_meta['fees'], $display_meta['student_details'] );
		?>
		<div class="wrap rks-admin-wrap">

			<p style="margin:14px 0;"><a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::SLUG_LIST ) ); ?>" class="button"><?php echo Icons::svg( 'arrow-left', 12 ); ?> All Admissions</a></p>

			<div class="rks-detail-hero">
				<div class="rks-detail-hero__avatar"><?php echo esc_html( $initials ); ?></div>
				<div class="rks-detail-hero__main">
					<small>Admission · <?php echo esc_html( $ref ); ?></small>
					<h1><?php echo esc_html( (string) $adm['full_name'] ); ?></h1>
					<p>
						<?php echo Icons::svg( 'cap', 12 ); ?> <?php echo esc_html( (string) $adm['course_title'] ); ?>
						&nbsp;·&nbsp;
						<?php echo Icons::svg( 'building', 12 ); ?> <?php echo esc_html( $branch ? (string) $branch['name'] : 'Branch #' . (int) $adm['branch_id'] ); ?>
					</p>
					<p style="margin-top:8px;">
						<span class="rks-pill rks-pill--<?php echo $adm['status'] === 'approved' ? 'success' : ( $adm['status'] === 'rejected' ? 'danger' : 'warn' ); ?>"><?php echo esc_html( str_replace( '_', ' ', (string) $adm['status'] ) ); ?></span>
						&nbsp;
						<small>Submitted <?php echo esc_html( human_time_diff( strtotime( (string) $adm['created_at'] ), current_time( 'timestamp' ) ) ); ?> ago</small>
					</p>
				</div>
				<div class="rks-detail-hero__actions">
					<a href="mailto:<?php echo esc_attr( (string) $adm['email'] ); ?>" class="button"><?php echo Icons::svg( 'mail', 12 ); ?> Email</a>
					<a href="tel:<?php echo esc_attr( (string) $adm['phone'] ); ?>" class="button"><?php echo Icons::svg( 'phone', 12 ); ?> Call</a>
					<a href="<?php echo esc_url( $print_url ); ?>" target="_blank" class="button button-primary"><?php echo Icons::svg( 'file-text', 12 ); ?> Download Admission Form</a>
					<?php if ( $student_user ) : ?>
						<a href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . $student_user->ID ) ); ?>" class="button button-primary"><?php echo Icons::svg( 'user-circle', 12 ); ?> User #<?php echo (int) $student_user->ID; ?></a>
					<?php endif; ?>
				</div>
			</div>

			<?php if ( $msg ) : ?>
				<div class="notice notice-<?php echo $msg === 'error' ? 'error' : 'success'; ?> is-dismissible"><p><?php echo esc_html( ucfirst( $msg ) ); ?></p></div>
			<?php endif; ?>

			<?php
			$current_user_branch = (int) get_user_meta( get_current_user_id(), 'rks_branch_id', true );
			$is_franchise_branch = $branch && (string) $branch['type'] === Branches::TYPE_FRANCHISE;
			$is_super_viewing    = current_user_can( 'rks_view_all_admissions' );
			$blocked_for_super   = $is_super_viewing && $is_franchise_branch && (int) $adm['branch_id'] !== $current_user_branch;
			?>
			<?php if ( in_array( $adm['status'], [ 'pending', 'payment_review' ], true ) ) : ?>
				<?php if ( $blocked_for_super ) : ?>
					<div class="rks-action-box rks-action-box--info">
						<h3><?php echo Icons::svg( 'info', 14 ); ?> Franchise Transaction</h3>
						<p>This admission belongs to a <strong>franchise branch</strong>. Approval/rejection must be done by the franchise owner from their own dashboard. Super Admin cannot verify franchise payments.</p>
					</div>
				<?php else : ?>
				<div class="rks-action-box">
					<h3><?php echo Icons::svg( 'alert', 14 ); ?> Action Required</h3>
					<p>Review the application + payment proof below. Approving auto-creates a student account, enrolls them, and sends a welcome email + ID card.</p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;">
						<?php wp_nonce_field( 'rks_admission_action' ); ?>
						<input type="hidden" name="action" value="rks_admission_action">
						<input type="hidden" name="do" value="approve">
						<input type="hidden" name="admission_id" value="<?php echo (int) $adm['id']; ?>">
						<input type="hidden" name="return" value="view">
						<button class="button button-primary" onclick="return confirm('Approve this admission?');"><?php echo Icons::svg( 'check', 12 ); ?> Approve &amp; Onboard</button>
					</form>
					<button type="button" class="button button-link-delete" onclick="document.getElementById('rks-arej').style.display='block';">Reject</button>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="rks-arej" style="display:none;margin-top:12px;">
						<?php wp_nonce_field( 'rks_admission_action' ); ?>
						<input type="hidden" name="action" value="rks_admission_action">
						<input type="hidden" name="do" value="reject">
						<input type="hidden" name="admission_id" value="<?php echo (int) $adm['id']; ?>">
						<input type="hidden" name="return" value="view">
						<textarea name="note" rows="3" style="width:100%;" placeholder="Reason (sent to student)"></textarea>
						<button class="button button-link-delete"><?php echo Icons::svg( 'x-circle', 12 ); ?> Confirm Reject</button>
					</form>
				</div>
				<?php endif; ?>
			<?php endif; ?>

			<div class="rks-detail-grid">

				<!-- LEFT main -->
				<div>

					<div class="rks-admin-card">
						<div class="rks-section-head" style="margin-top:0;">
							<?php echo Icons::chip( 'user', 'teal' ); ?>
							<h2>Student Information</h2>
						</div>
						<dl class="rks-meta-list">
							<li><dt>Full Name</dt><dd><?php echo esc_html( (string) $adm['full_name'] ); ?></dd></li>
							<?php if ( ! empty( $adm['father_name'] ) ) : ?><li><dt>Father's Name</dt><dd><?php echo esc_html( (string) $adm['father_name'] ); ?></dd></li><?php endif; ?>
							<?php if ( ! empty( $student_details['father_occupation'] ) ) : ?><li><dt>Father's Occupation</dt><dd><?php echo esc_html( (string) $student_details['father_occupation'] ); ?></dd></li><?php endif; ?>
							<li><dt>Email</dt><dd><a href="mailto:<?php echo esc_attr( (string) $adm['email'] ); ?>"><?php echo esc_html( (string) $adm['email'] ); ?></a></dd></li>
							<li><dt>Student Phone</dt><dd><a href="tel:<?php echo esc_attr( (string) $adm['phone'] ); ?>"><?php echo esc_html( (string) $adm['phone'] ); ?></a></dd></li>
							<?php if ( ! empty( $student_details['parent_mobile'] ) ) : ?><li><dt>Parent Phone</dt><dd><a href="tel:<?php echo esc_attr( (string) $student_details['parent_mobile'] ); ?>"><?php echo esc_html( (string) $student_details['parent_mobile'] ); ?></a></dd></li><?php endif; ?>
							<?php if ( ! empty( $adm['dob'] ) ) : ?><li><dt>Date of Birth</dt><dd><?php echo esc_html( (string) $adm['dob'] ); ?></dd></li><?php endif; ?>
							<?php if ( ! empty( $adm['gender'] ) ) : ?><li><dt>Gender</dt><dd><?php echo esc_html( ucfirst( (string) $adm['gender'] ) ); ?></dd></li><?php endif; ?>
							<?php if ( ! empty( $student_details['nationality'] ) ) : ?><li><dt>Nationality</dt><dd><?php echo esc_html( (string) $student_details['nationality'] ); ?></dd></li><?php endif; ?>
							<?php if ( ! empty( $student_details['religion'] ) ) : ?><li><dt>Religion</dt><dd><?php echo esc_html( (string) $student_details['religion'] ); ?></dd></li><?php endif; ?>
							<?php if ( ! empty( $adm['address'] ) ) : ?><li><dt>Permanent Address</dt><dd><?php echo esc_html( (string) $adm['address'] ); ?></dd></li><?php endif; ?>
							<?php if ( ! empty( $student_details['permanent_district'] ) || ! empty( $student_details['permanent_state'] ) || ! empty( $student_details['permanent_nation'] ) ) : ?><li><dt>Permanent Area</dt><dd><?php echo esc_html( trim( implode( ', ', array_filter( [ (string) ( $student_details['permanent_district'] ?? '' ), (string) ( $student_details['permanent_state'] ?? '' ), (string) ( $student_details['permanent_nation'] ?? '' ) ] ) ) ) ); ?></dd></li><?php endif; ?>
							<?php if ( ! empty( $student_details['present_address'] ) ) : ?><li><dt>Present Address</dt><dd><?php echo esc_html( (string) $student_details['present_address'] ); ?></dd></li><?php endif; ?>
							<?php if ( ! empty( $student_details['present_district'] ) || ! empty( $student_details['present_state'] ) || ! empty( $student_details['present_nation'] ) ) : ?><li><dt>Present Area</dt><dd><?php echo esc_html( trim( implode( ', ', array_filter( [ (string) ( $student_details['present_district'] ?? '' ), (string) ( $student_details['present_state'] ?? '' ), (string) ( $student_details['present_nation'] ?? '' ) ] ) ) ) ); ?></dd></li><?php endif; ?>
							<?php if ( ! empty( $adm['qualification'] ) ) : ?><li><dt>Qualification</dt><dd><?php echo esc_html( (string) $adm['qualification'] ); ?></dd></li><?php endif; ?>
						</dl>
					</div>

					<div class="rks-admin-card">
						<div class="rks-section-head" style="margin-top:0;">
							<?php echo Icons::chip( 'cap', 'lilac' ); ?>
							<h2>Course &amp; Branch</h2>
						</div>
						<dl class="rks-meta-list">
							<li><dt>Course</dt><dd>
								<strong><?php echo esc_html( (string) $adm['course_title'] ); ?></strong>
								<?php if ( $course ) : ?>
									<small style="display:block;"><a href="<?php echo esc_url( admin_url( 'admin.php?page=rks-course-builder&course_id=' . (int) $course->ID ) ); ?>">Open in Course Builder →</a></small>
								<?php endif; ?>
							</dd></li>
							<?php if ( $course_category_label ) : ?>
								<li><dt>Category</dt><dd><span class="rks-tag rks-tag--main"><?php echo esc_html( $course_category_label ); ?></span></dd></li>
							<?php endif; ?>
							<li><dt>Branch</dt><dd>
								<?php if ( $branch ) : ?>
									<strong><?php echo esc_html( (string) $branch['name'] ); ?></strong>
									<small style="display:block;color:#6b7a82;"><?php echo esc_html( (string) $branch['city'] ); ?> · <span class="rks-tag rks-tag--<?php echo esc_attr( (string) $branch['type'] ); ?>"><?php echo esc_html( (string) $branch['type'] ); ?></span></small>
								<?php else : ?>
									Branch #<?php echo (int) $adm['branch_id']; ?>
								<?php endif; ?>
							</dd></li>
							<li><dt>Admission Fee</dt><dd><?php echo esc_html( $currency . number_format( $admission_fee, 0 ) ); ?></dd></li>
							<li><dt>Course Fee Now</dt><dd><?php echo esc_html( $currency . number_format( $course_fee, 0 ) ); ?></dd></li>
							<li><dt>Course Total</dt><dd><?php echo esc_html( $currency . number_format( $course_total, 0 ) ); ?></dd></li>
							<li><dt>Total Payable</dt><dd><strong style="font-size:16px;"><?php echo esc_html( $currency . number_format( $payable_now, 0 ) ); ?></strong></dd></li>
						</dl>
					</div>

					<?php if ( ! empty( $documents ) ) : ?>
					<div class="rks-admin-card">
						<div class="rks-section-head" style="margin-top:0;">
							<?php echo Icons::chip( 'file-text', 'gold' ); ?>
							<h2>Application Documents</h2>
						</div>
						<dl class="rks-meta-list">
							<?php foreach ( $documents as $doc_key => $doc ) : ?>
								<?php
								if ( ! is_array( $doc ) || empty( $doc['path'] ) ) {
									continue;
								}
								$doc_label = (string) ( $doc['label'] ?? ucwords( str_replace( '_', ' ', (string) $doc_key ) ) );
								?>
								<li>
									<dt><?php echo esc_html( $doc_label ); ?></dt>
									<dd>
										<a href="<?php echo esc_url( Admissions::document_view_url( (int) $adm['id'], (string) $doc_key ) ); ?>" target="_blank" class="button button-small"><?php echo Icons::svg( 'file-text', 12 ); ?> Open Document</a>
										<?php if ( ! empty( $doc['original_name'] ) ) : ?>
											<small style="display:block;color:#6b7a82;margin-top:6px;"><?php echo esc_html( (string) $doc['original_name'] ); ?></small>
										<?php endif; ?>
									</dd>
								</li>
							<?php endforeach; ?>
						</dl>
					</div>
					<?php endif; ?>

					<div class="rks-admin-card">
						<div class="rks-section-head" style="margin-top:0;">
							<?php echo Icons::chip( 'wallet', $payment ? 'red' : 'slate' ); ?>
							<h2>Payment Proof</h2>
						</div>
						<?php if ( ! $payment ) : ?>
							<div class="rks-empty">
								<?php echo Icons::chip( 'wallet', 'slate', 22 ); ?>
								<h3>No payment recorded</h3>
								<small>This admission was submitted without a payment.</small>
							</div>
						<?php else : ?>
							<dl class="rks-meta-list">
								<li><dt>Payment ID</dt><dd>
									<a href="<?php echo esc_url( add_query_arg( [ 'page' => self::SLUG_PAYMENTS, 'view' => (int) $payment['id'] ], admin_url( 'admin.php' ) ) ); ?>"><strong>#<?php echo (int) $payment['id']; ?></strong> →</a>
								</dd></li>
								<li><dt>Amount</dt><dd><strong style="color:#c92c39;font-size:16px;"><?php echo esc_html( $currency . number_format( (float) $payment['amount'], 0 ) ); ?></strong></dd></li>
								<li><dt>Method</dt><dd><span class="rks-tag rks-tag--main"><?php echo esc_html( strtoupper( (string) $payment['method'] ) ); ?></span></dd></li>
								<li><dt>UTR</dt><dd><code><?php echo esc_html( (string) $payment['utr'] ); ?></code></dd></li>
								<li><dt>Status</dt><dd><span class="rks-pill rks-pill--<?php echo $payment['status'] === 'approved' ? 'success' : ( $payment['status'] === 'rejected' ? 'danger' : 'warn' ); ?>"><?php echo esc_html( (string) $payment['status'] ); ?></span></dd></li>
							</dl>
							<?php if ( ! empty( $payment['screenshot_path'] ) ) : ?>
								<div class="rks-payment-shot">
									<?php $ext = strtolower( pathinfo( (string) $payment['screenshot_path'], PATHINFO_EXTENSION ) ); ?>
									<?php if ( $ext === 'pdf' ) : ?>
										<a href="<?php echo esc_url( Payments::view_url( (int) $payment['id'] ) ); ?>" target="_blank" class="button"><?php echo Icons::svg( 'file-text', 12 ); ?> Open PDF receipt</a>
									<?php else : ?>
										<a href="<?php echo esc_url( Payments::view_url( (int) $payment['id'] ) ); ?>" target="_blank">
											<img src="<?php echo esc_url( Payments::view_url( (int) $payment['id'] ) ); ?>" alt="Payment screenshot">
										</a>
										<p><small><?php echo Icons::svg( 'info', 12 ); ?> Click image to open full size in new tab</small></p>
									<?php endif; ?>
								</div>
							<?php endif; ?>
						<?php endif; ?>
					</div>

					<?php if ( ! empty( $display_meta ) ) : ?>
					<div class="rks-admin-card">
						<div class="rks-section-head" style="margin-top:0;">
							<?php echo Icons::chip( 'file-text', 'gold' ); ?>
							<h2>Additional Form Responses</h2>
						</div>
						<dl class="rks-meta-list">
							<?php foreach ( $display_meta as $k => $v ) : ?>
								<li><dt><?php echo esc_html( ucwords( str_replace( '_', ' ', (string) $k ) ) ); ?></dt><dd><?php echo esc_html( is_scalar( $v ) ? (string) $v : wp_json_encode( $v ) ); ?></dd></li>
							<?php endforeach; ?>
						</dl>
					</div>
					<?php endif; ?>

					<?php if ( $adm['status'] === 'rejected' && ! empty( $adm['rejection_note'] ) ) : ?>
						<div class="rks-admin-card rks-admin-card--alert">
							<div class="rks-section-head" style="margin-top:0;">
								<?php echo Icons::chip( 'x-circle', 'red' ); ?>
								<h2>Rejection Note</h2>
							</div>
							<p style="color:#c92c39;font-style:italic;"><?php echo esc_html( (string) $adm['rejection_note'] ); ?></p>
						</div>
					<?php endif; ?>

				</div>

				<!-- RIGHT sidebar -->
				<div>

					<div class="rks-admin-card">
						<div class="rks-section-head" style="margin-top:0;">
							<?php echo Icons::chip( 'history', 'slate' ); ?>
							<h2>Status Timeline</h2>
						</div>
						<ul class="rks-timeline">
							<li class="is-done">
								<span class="rks-timeline__dot"><?php echo Icons::svg( 'plus', 12 ); ?></span>
								<strong>Application Submitted</strong>
								<small><?php echo esc_html( (string) $adm['created_at'] ); ?></small>
							</li>
							<?php if ( $payment ) : ?>
							<li class="<?php echo $payment['status'] === 'approved' ? 'is-done' : ( $payment['status'] === 'rejected' ? 'is-error' : 'is-pending' ); ?>">
								<span class="rks-timeline__dot"><?php echo Icons::svg( 'wallet', 12 ); ?></span>
								<strong>Payment <?php echo esc_html( ucfirst( (string) $payment['status'] ) ); ?></strong>
								<small><?php echo esc_html( (string) ( $payment['verified_at'] ?: $payment['created_at'] ) ); ?></small>
							</li>
							<?php endif; ?>
							<?php if ( ! empty( $adm['reviewed_at'] ) ) : ?>
							<li class="<?php echo $adm['status'] === 'rejected' ? 'is-error' : 'is-done'; ?>">
								<span class="rks-timeline__dot"><?php echo Icons::svg( $adm['status'] === 'rejected' ? 'x' : 'check', 12 ); ?></span>
								<strong><?php echo $adm['status'] === 'rejected' ? 'Rejected' : 'Approved'; ?></strong>
								<small><?php echo esc_html( (string) $adm['reviewed_at'] ); ?> · by <?php echo esc_html( $reviewer ? ( $reviewer->display_name ?: $reviewer->user_login ) : 'system' ); ?></small>
							</li>
							<?php elseif ( in_array( $adm['status'], [ 'pending', 'payment_review' ], true ) ) : ?>
							<li class="is-pending">
								<span class="rks-timeline__dot"><?php echo Icons::svg( 'clock', 12 ); ?></span>
								<strong>Awaiting Review</strong>
								<small>Action needed</small>
							</li>
							<?php endif; ?>
							<?php if ( $adm['status'] === 'approved' && $student_user ) : ?>
							<li class="is-done">
								<span class="rks-timeline__dot"><?php echo Icons::svg( 'user-check', 12 ); ?></span>
								<strong>Account Created</strong>
								<small>User #<?php echo (int) $student_user->ID; ?> · <?php echo esc_html( $student_user->user_email ); ?></small>
							</li>
							<?php endif; ?>
						</ul>
					</div>

					<div class="rks-admin-card">
						<div class="rks-section-head" style="margin-top:0;">
							<?php echo Icons::chip( 'info', 'teal' ); ?>
							<h2>Application Meta</h2>
						</div>
						<dl class="rks-meta-list">
							<li><dt>Reference</dt><dd><code><?php echo esc_html( $ref ); ?></code></dd></li>
							<li><dt>App ID</dt><dd>#<?php echo (int) $adm['id']; ?></dd></li>
							<li><dt>Created</dt><dd><?php echo esc_html( (string) $adm['created_at'] ); ?></dd></li>
							<li><dt>Updated</dt><dd><?php echo esc_html( (string) $adm['updated_at'] ); ?></dd></li>
						</dl>
					</div>

				</div>

			</div>

			<div class="rks-admin-card rks-admin-card--alert" style="margin-top:24px;">
				<div class="rks-section-head" style="margin-top:0;">
					<?php echo Icons::chip( 'trash', 'red' ); ?>
					<h2>Danger Zone</h2>
				</div>
				<p>Permanently delete this admission record. Linked student account, enrollments, certificates, and payment records will not be removed.</p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'rks_admission_delete' ); ?>
					<input type="hidden" name="action" value="rks_admission_delete">
					<input type="hidden" name="admission_id" value="<?php echo (int) $adm['id']; ?>">
					<button type="submit" class="button button-link-delete" onclick="return confirm('Permanently delete this admission record? This cannot be undone.');">
						<?php echo Icons::svg( 'trash', 12 ); ?> Delete Admission
					</button>
				</form>
			</div>
		</div>
		<?php
	}

	public function render_payments(): void {
		if ( ! current_user_can( 'rks_verify_payment' ) ) wp_die( 'Forbidden' );

		$view_id = isset( $_GET['view'] ) ? (int) $_GET['view'] : 0;
		if ( $view_id > 0 ) { $this->render_payment_detail( $view_id ); return; }

		$user        = wp_get_current_user();
		$is_super    = current_user_can( 'rks_view_all_payments' );
		$user_branch = (int) get_user_meta( $user->ID, 'rks_branch_id', true );

		$args = [ 'limit' => 100 ];
		if ( ! $is_super && $user_branch > 0 ) $args['branch_id'] = $user_branch;

		$status_filter = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( (string) $_GET['status'] ) ) : 'all';
		if ( 'all' !== $status_filter ) $args['status'] = $status_filter;

		$rows = ( new Payments() )->list( $args );
		$msg  = isset( $_GET['rks_msg'] ) ? sanitize_key( wp_unslash( (string) $_GET['rks_msg'] ) ) : '';
		?>
		<div class="wrap rks-admin-wrap">
			<header class="rks-page-hero">
				<div class="rks-page-hero__main">
					<div class="rks-page-hero__icon"><?php echo Icons::svg( 'wallet', 24 ); ?></div>
					<div>
						<small><?php echo $is_super ? 'All Branches' : 'Your Branch Only'; ?></small>
						<h1>Payments Audit</h1>
						<p>UPI payment records — UTR + screenshot for every admission. Verify or reject from the Admissions page.</p>
					</div>
				</div>
				<div class="rks-page-hero__actions">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::SLUG_LIST ) ); ?>" class="button button-primary"><?php echo Icons::svg( 'inbox', 14 ); ?> Open Admissions</a>
				</div>
			</header>

			<?php if ( $msg ) : ?>
				<div class="notice notice-<?php echo $msg === 'error' ? 'error' : 'success'; ?> is-dismissible">
					<p><?php echo esc_html( ucfirst( str_replace( '_', ' ', $msg ) ) ); ?></p>
				</div>
			<?php endif; ?>

			<ul class="rks-page-filters">
				<?php foreach ( [ 'all' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected' ] as $s => $label ) : ?>
					<li><a href="<?php echo esc_url( add_query_arg( [ 'page' => self::SLUG_PAYMENTS, 'status' => $s ], admin_url( 'admin.php' ) ) ); ?>" class="<?php echo $status_filter === $s ? 'is-active' : ''; ?>"><?php echo esc_html( $label ); ?></a></li>
				<?php endforeach; ?>
			</ul>

			<div class="rks-table-scroll rks-payments-table-wrap" style="margin-top:24px;">
			<table class="widefat striped rks-payments-table">
				<thead><tr><th>ID</th><th>Branch</th><th>Student</th><th>Admission / Type</th><th>Amount</th><th>UTR</th><th>Method</th><th>Status</th><th>Created</th><th></th></tr></thead>
				<tbody>
					<?php if ( empty( $rows ) ) : ?>
						<tr><td colspan="10"><div class="rks-empty"><?php echo Icons::chip( 'wallet', 'slate', 22 ); ?><h3>No payments found</h3></div></td></tr>
					<?php else : foreach ( $rows as $p ) : 
						$payer = ! empty( $p['user_id'] ) ? get_userdata( (int) $p['user_id'] ) : null;
						$meta  = \RKSiksha\Core\Support\DB::decode_meta( $p['meta'] ?? null );
						$is_sub = ( ( $meta['kind'] ?? '' ) === \RKSiksha\Core\Modules\Subscriptions::PAYMENT_KIND );
					?>
						<tr>
							<td>#<?php echo (int) $p['id']; ?></td>
							<td>#<?php echo (int) $p['branch_id']; ?></td>
							<td>
								<?php if ( $payer ) : ?>
									<strong><?php echo esc_html( $payer->display_name ?: $payer->user_login ); ?></strong>
									<br><small style="color:#6b7280;"><?php echo esc_html( $payer->user_email ); ?></small>
								<?php else : ?>
									<span style="color:#9ca3af;">—</span>
								<?php endif; ?>
							</td>
							<td>
								<?php 
								if ( $p['admission_id'] ) {
									echo '<span class="rks-pill rks-pill--success" style="font-weight:600;font-size:11px;padding:3px 8px;border-radius:4px;background:#ecfdf5;color:#15803d;display:inline-block;margin-bottom:4px;white-space:nowrap;">Admission Payment</span>';
									echo '<br><small style="color:#6b7280;">Admission #' . (int) $p['admission_id'] . '</small>';
								} elseif ( $is_sub ) {
									echo '<span class="rks-pill rks-pill--warn" style="font-weight:600;font-size:11px;padding:3px 8px;border-radius:4px;background:#fef3c7;color:#b45309;display:inline-block;white-space:nowrap;">Subscription Payment</span>';
								} else {
									echo '—';
								}
								?>
							</td>
							<td><strong>₹<?php echo number_format( (float) $p['amount'], 0 ); ?></strong></td>
							<td><code><?php echo esc_html( (string) $p['utr'] ); ?></code></td>
							<td><span class="rks-tag rks-tag--main"><?php echo esc_html( strtoupper( (string) $p['method'] ) ); ?></span></td>
							<td><span class="rks-pill rks-pill--<?php echo $p['status'] === 'approved' ? 'success' : ( $p['status'] === 'rejected' ? 'danger' : 'warn' ); ?>"><?php echo esc_html( (string) $p['status'] ); ?></span></td>
							<td><small><?php echo esc_html( (string) $p['created_at'] ); ?></small></td>
							<td class="rks-actions-cell">
								<div class="rks-row-actions">
									<a href="<?php echo esc_url( add_query_arg( [ 'page' => self::SLUG_PAYMENTS, 'view' => (int) $p['id'] ], admin_url( 'admin.php' ) ) ); ?>" class="button button-primary button-small" title="View payment" aria-label="View payment"><?php echo Icons::svg( 'eye', 12 ); ?></a>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
										<?php wp_nonce_field( 'rks_payment_delete' ); ?>
										<input type="hidden" name="action" value="rks_payment_delete">
										<input type="hidden" name="payment_id" value="<?php echo (int) $p['id']; ?>">
										<button type="submit" class="button button-small button-link-delete" title="Delete payment" aria-label="Delete payment" onclick="return confirm('Permanently delete this payment record? Linked admission will be unlinked, not deleted.');"><?php echo Icons::svg( 'trash', 12 ); ?></button>
									</form>
								</div>
							</td>
						</tr>
					<?php endforeach; endif; ?>
				</tbody>
			</table>
			</div>
		</div>
		<?php
	}

	private function render_payment_detail( int $id ): void {
		$payment = ( new Payments() )->get( $id );
		if ( ! $payment ) {
			echo '<div class="wrap rks-admin-wrap"><div class="notice notice-error"><p>Payment not found.</p></div></div>';
			return;
		}

		$is_super    = current_user_can( 'rks_view_all_payments' );
		$user_branch = (int) get_user_meta( get_current_user_id(), 'rks_branch_id', true );
		if ( ! $is_super && (int) $payment['branch_id'] !== $user_branch ) {
			wp_die( 'Forbidden — wrong branch', 'Forbidden', [ 'response' => 403 ] );
		}

		$settings = (array) get_option( 'rks_settings', [] );
		$currency = (string) ( $settings['currency_symbol'] ?? '₹' );

		$msg       = isset( $_GET['rks_msg'] ) ? sanitize_key( wp_unslash( (string) $_GET['rks_msg'] ) ) : '';
		$branch    = ( new Branches() )->get( (int) $payment['branch_id'] );
		$admission = ! empty( $payment['admission_id'] ) ? ( new Admissions() )->get( (int) $payment['admission_id'] ) : null;
		$payer     = ! empty( $payment['user_id'] ) ? get_userdata( (int) $payment['user_id'] ) : null;
		$verifier  = ! empty( $payment['verifier_user_id'] ) ? get_userdata( (int) $payment['verifier_user_id'] ) : null;
		?>
		<div class="wrap rks-admin-wrap">
			<p style="margin:14px 0;"><a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::SLUG_PAYMENTS ) ); ?>" class="button"><?php echo Icons::svg( 'arrow-left', 12 ); ?> All Payments</a></p>

			<div class="rks-detail-hero">
				<div class="rks-detail-hero__avatar rks-detail-hero__avatar--accent"><?php echo Icons::svg( 'wallet', 28 ); ?></div>
				<div class="rks-detail-hero__main">
					<small>Payment Record · #<?php echo (int) $payment['id']; ?></small>
					<h1><?php echo esc_html( $currency . number_format( (float) $payment['amount'], 0 ) ); ?></h1>
					<p>
						<?php echo Icons::svg( 'building', 12 ); ?> <?php echo esc_html( $branch ? (string) $branch['name'] : 'Branch #' . (int) $payment['branch_id'] ); ?>
						<?php if ( $admission ) : ?>
							&nbsp;·&nbsp; <?php echo Icons::svg( 'inbox', 12 ); ?> Admission #<?php echo (int) $admission['id']; ?> (<?php echo esc_html( (string) $admission['full_name'] ); ?>)
						<?php else : 
							$meta = \RKSiksha\Core\Support\DB::decode_meta( $payment['meta'] ?? null );
							if ( ( $meta['kind'] ?? '' ) === \RKSiksha\Core\Modules\Subscriptions::PAYMENT_KIND ) : ?>
								&nbsp;·&nbsp; <?php echo Icons::svg( 'refresh', 12 ); ?> Subscription Payment (Month #<?php echo (int) ( $meta['month_number'] ?? 0 ); ?>)
							<?php endif;
						endif; ?>
					</p>
					<p style="margin-top:8px;">
						<span class="rks-pill rks-pill--<?php echo $payment['status'] === 'approved' ? 'success' : ( $payment['status'] === 'rejected' ? 'danger' : 'warn' ); ?>"><?php echo esc_html( (string) $payment['status'] ); ?></span>
						&nbsp; <span class="rks-tag rks-tag--main"><?php echo esc_html( strtoupper( (string) $payment['method'] ) ); ?></span>
					</p>
				</div>
				<div class="rks-detail-hero__actions">
					<?php if ( $admission ) : ?>
						<a href="<?php echo esc_url( add_query_arg( [ 'page' => self::SLUG_LIST, 'view' => (int) $admission['id'] ], admin_url( 'admin.php' ) ) ); ?>" class="button button-primary"><?php echo Icons::svg( 'inbox', 12 ); ?> Open Admission</a>
					<?php endif; ?>
					<?php if ( ! empty( $payment['screenshot_path'] ) ) : ?>
						<a href="<?php echo esc_url( Payments::view_url( (int) $payment['id'] ) ); ?>" target="_blank" class="button"><?php echo Icons::svg( 'eye', 12 ); ?> Open Screenshot</a>
					<?php endif; ?>
				</div>
			</div>

			<?php if ( $msg ) : ?>
				<div class="notice notice-<?php echo $msg === 'error' ? 'error' : 'success'; ?> is-dismissible"><p><?php echo esc_html( ucfirst( str_replace( '_', ' ', $msg ) ) ); ?></p></div>
			<?php endif; ?>

			<?php
			$current_user_branch = (int) get_user_meta( get_current_user_id(), 'rks_branch_id', true );
			$is_franchise_branch = $branch && (string) $branch['type'] === Branches::TYPE_FRANCHISE;
			$is_super_viewing    = current_user_can( 'rks_view_all_payments' );
			$blocked_for_super   = $is_super_viewing && $is_franchise_branch && (int) $payment['branch_id'] !== $current_user_branch;
			?>

			<?php if ( $payment['status'] === 'pending' ) : ?>
				<?php if ( $blocked_for_super ) : ?>
					<div class="rks-action-box rks-action-box--info">
						<h3><?php echo Icons::svg( 'info', 14 ); ?> Franchise Transaction</h3>
						<p>This payment belongs to a <strong>franchise branch</strong>. Approval/rejection must be done by the franchise owner from their own dashboard. Super Admin cannot verify franchise payments.</p>
					</div>
				<?php elseif ( $admission ) : ?>
					<div class="rks-action-box rks-action-box--info">
						<h3><?php echo Icons::svg( 'info', 14 ); ?> Linked to Admission</h3>
						<p>This payment is linked to <strong>Admission #<?php echo (int) $admission['id']; ?> (<?php echo esc_html( (string) $admission['full_name'] ); ?>)</strong>. Please review and approve this transaction from the Admission details page to complete student onboarding.</p>
						<a href="<?php echo esc_url( add_query_arg( [ 'page' => self::SLUG_LIST, 'view' => (int) $admission['id'] ], admin_url( 'admin.php' ) ) ); ?>" class="button button-primary"><?php echo Icons::svg( 'inbox', 12 ); ?> Go to Admission Detail</a>
					</div>
				<?php else : ?>
					<div class="rks-action-box">
						<h3><?php echo Icons::svg( 'alert', 14 ); ?> Action Required</h3>
						<p>Review the payment details and reference/screenshot. Approving this payment will update the student's monthly course subscription status.</p>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;">
							<?php wp_nonce_field( 'rks_payment_action' ); ?>
							<input type="hidden" name="action" value="rks_payment_action">
							<input type="hidden" name="do" value="approve">
							<input type="hidden" name="payment_id" value="<?php echo (int) $payment['id']; ?>">
							<button class="button button-primary" onclick="return confirm('Approve this monthly subscription payment?');"><?php echo Icons::svg( 'check', 12 ); ?> Approve Payment</button>
						</form>
						<button type="button" class="button button-link-delete" onclick="document.getElementById('rks-prej').style.display='block';">Reject</button>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="rks-prej" style="display:none;margin-top:12px;">
							<?php wp_nonce_field( 'rks_payment_action' ); ?>
							<input type="hidden" name="action" value="rks_payment_action">
							<input type="hidden" name="do" value="reject">
							<input type="hidden" name="payment_id" value="<?php echo (int) $payment['id']; ?>">
							<textarea name="note" rows="3" style="width:100%;" placeholder="Reason for rejection (will be shown to student on dashboard)"></textarea>
							<button class="button button-link-delete"><?php echo Icons::svg( 'x-circle', 12 ); ?> Confirm Reject</button>
						</form>
					</div>
				<?php endif; ?>
			<?php endif; ?>

			<?php if ( $payment['status'] === 'approved' ) : ?>
				<div class="rks-action-box">
					<h3><?php echo Icons::svg( 'refresh', 14 ); ?> Internal Payment Correction</h3>
					<p>Public policy non-refundable hai. Is option ko sirf duplicate, incorrect, ya gateway correction case me internal record update ke liye use karein.</p>
					<button type="button" class="button button-link-delete" onclick="document.getElementById('rks-refund-form').style.display='block';"><?php echo Icons::svg( 'refresh', 12 ); ?> Record Correction</button>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="rks-refund-form" style="display:none;margin-top:12px;">
						<?php wp_nonce_field( 'rks_payment_refund' ); ?>
						<input type="hidden" name="action" value="rks_payment_refund">
						<input type="hidden" name="payment_id" value="<?php echo (int) $payment['id']; ?>">
						<textarea name="note" rows="3" style="width:100%;" placeholder="Correction reason / reference (internal note)"></textarea>
						<button class="button button-link-delete" onclick="return confirm('Record this internal payment correction?');"><?php echo Icons::svg( 'check', 12 ); ?> Confirm Correction</button>
					</form>
				</div>
			<?php endif; ?>

			<div class="rks-detail-grid">

				<!-- LEFT main -->
				<div>

					<div class="rks-admin-card">
						<div class="rks-section-head" style="margin-top:0;">
							<?php echo Icons::chip( 'receipt', 'red' ); ?>
							<h2>Payment Details</h2>
						</div>
						<dl class="rks-meta-list">
							<?php
							$meta = \RKSiksha\Core\Support\DB::decode_meta( $payment['meta'] ?? null );
							?>
							<li><dt>Payment ID</dt><dd>#<?php echo (int) $payment['id']; ?></dd></li>
							<li><dt>Amount</dt><dd><strong style="color:#c92c39;font-size:18px;"><?php echo esc_html( $currency . number_format( (float) $payment['amount'], 2 ) ); ?></strong></dd></li>
							<?php if ( isset( $meta['late_fee'] ) && (float) $meta['late_fee'] > 0 ) : ?>
								<li><dt>Late Fee Penalty</dt><dd><strong style="color:#c92c39;font-size:14px;"><?php echo esc_html( $currency . number_format( (float) $meta['late_fee'], 2 ) ); ?></strong></dd></li>
							<?php endif; ?>
							<li><dt>Method</dt><dd><span class="rks-tag rks-tag--main"><?php echo esc_html( strtoupper( (string) $payment['method'] ) ); ?></span></dd></li>
							<li><dt>UTR / Reference</dt><dd><code><?php echo esc_html( (string) ( $payment['utr'] ?: '—' ) ); ?></code></dd></li>
							<li><dt>Status</dt><dd><span class="rks-pill rks-pill--<?php echo $payment['status'] === 'approved' ? 'success' : ( $payment['status'] === 'rejected' ? 'danger' : 'warn' ); ?>"><?php echo esc_html( (string) $payment['status'] ); ?></span></dd></li>
							<li><dt>Created</dt><dd><?php echo esc_html( (string) $payment['created_at'] ); ?></dd></li>
							<?php if ( ! empty( $payment['verified_at'] ) ) : ?>
								<li><dt>Verified At</dt><dd><?php echo esc_html( (string) $payment['verified_at'] ); ?></dd></li>
							<?php endif; ?>
							<?php if ( $verifier ) : ?>
								<li><dt>Verified By</dt><dd><?php echo esc_html( $verifier->display_name ?: $verifier->user_login ); ?></dd></li>
							<?php endif; ?>
						</dl>
					</div>

					<?php if ( ! empty( $payment['screenshot_path'] ) ) : ?>
					<div class="rks-admin-card">
						<div class="rks-section-head" style="margin-top:0;">
							<?php echo Icons::chip( 'eye', 'gold' ); ?>
							<h2>Payment Proof</h2>
							<small>· uploaded screenshot / receipt</small>
						</div>
						<div class="rks-payment-shot">
							<?php $ext = strtolower( pathinfo( (string) $payment['screenshot_path'], PATHINFO_EXTENSION ) ); ?>
							<?php if ( $ext === 'pdf' ) : ?>
								<a href="<?php echo esc_url( Payments::view_url( (int) $payment['id'] ) ); ?>" target="_blank" class="button"><?php echo Icons::svg( 'file-text', 12 ); ?> Open PDF receipt</a>
							<?php else : ?>
								<a href="<?php echo esc_url( Payments::view_url( (int) $payment['id'] ) ); ?>" target="_blank">
									<img src="<?php echo esc_url( Payments::view_url( (int) $payment['id'] ) ); ?>" alt="Payment screenshot">
								</a>
							<?php endif; ?>
						</div>
					</div>
					<?php endif; ?>

					<?php if ( ! empty( $payment['rejection_note'] ) ) : ?>
					<div class="rks-admin-card rks-admin-card--alert">
						<div class="rks-section-head" style="margin-top:0;">
							<?php echo Icons::chip( 'x-circle', 'red' ); ?>
							<h2>Rejection Note</h2>
						</div>
						<p style="color:#c92c39;font-style:italic;"><?php echo esc_html( (string) $payment['rejection_note'] ); ?></p>
					</div>
					<?php endif; ?>

				</div>

				<!-- RIGHT sidebar -->
				<div>

					<?php if ( $admission ) : ?>
					<div class="rks-admin-card">
						<div class="rks-section-head" style="margin-top:0;">
							<?php echo Icons::chip( 'inbox', 'gold' ); ?>
							<h2>Linked Admission</h2>
						</div>
						<dl class="rks-meta-list">
							<li><dt>Student</dt><dd><strong><?php echo esc_html( (string) $admission['full_name'] ); ?></strong></dd></li>
							<li><dt>Course</dt><dd><?php echo esc_html( (string) $admission['course_title'] ); ?></dd></li>
							<li><dt>Total Payable</dt><dd><?php echo esc_html( $currency . number_format( (float) $admission['fees_amount'], 0 ) ); ?></dd></li>
							<li><dt>Status</dt><dd><span class="rks-pill rks-pill--<?php echo $admission['status'] === 'approved' ? 'success' : ( $admission['status'] === 'rejected' ? 'danger' : 'warn' ); ?>"><?php echo esc_html( str_replace( '_', ' ', (string) $admission['status'] ) ); ?></span></dd></li>
						</dl>
						<a href="<?php echo esc_url( add_query_arg( [ 'page' => self::SLUG_LIST, 'view' => (int) $admission['id'] ], admin_url( 'admin.php' ) ) ); ?>" class="button button-small"><?php echo Icons::svg( 'arrow-right', 12 ); ?> Open Admission</a>
					</div>
					<?php else : 
						$meta = \RKSiksha\Core\Support\DB::decode_meta( $payment['meta'] ?? null );
						if ( ( $meta['kind'] ?? '' ) === \RKSiksha\Core\Modules\Subscriptions::PAYMENT_KIND ) : 
							$course_post = ! empty( $meta['course_id'] ) ? get_post( (int) $meta['course_id'] ) : null;
							$sub_id = ! empty( $meta['subscription_id'] ) ? (int) $meta['subscription_id'] : 0;
							$sub = $sub_id > 0 ? ( new \RKSiksha\Core\Modules\Subscriptions() )->get( $sub_id ) : null;
					?>
					<div class="rks-admin-card">
						<div class="rks-section-head" style="margin-top:0;">
							<?php echo Icons::chip( 'refresh', 'gold' ); ?>
							<h2>Course Subscription</h2>
						</div>
						<dl class="rks-meta-list">
							<li><dt>Type</dt><dd><span class="rks-pill rks-pill--warn" style="background:#e5e7eb;color:#374151;font-weight:600;">Subscription Payment</span></dd></li>
							<?php if ( $course_post ) : ?>
								<li><dt>Course</dt><dd><strong><?php echo esc_html( $course_post->post_title ); ?></strong></dd></li>
							<?php endif; ?>
							<?php if ( $sub ) : ?>
								<li><dt>Installment</dt><dd>Month <?php echo (int) ( $meta['month_number'] ?? 0 ); ?> of <?php echo (int) $sub['total_months']; ?></dd></li>
								<li><dt>Months Paid</dt><dd><?php echo (int) $sub['months_paid']; ?> paid</dd></li>
								<li><dt>Status</dt><dd><span class="rks-pill rks-pill--<?php echo $sub['status'] === 'active' ? 'success' : 'warn'; ?>"><?php echo esc_html( (string) $sub['status'] ); ?></span></dd></li>
							<?php endif; ?>
						</dl>
					</div>
					<?php endif; ?>
					<?php endif; ?>

					<?php if ( $branch ) : ?>
					<div class="rks-admin-card">
						<div class="rks-section-head" style="margin-top:0;">
							<?php echo Icons::chip( 'building', 'mint' ); ?>
							<h2>Branch</h2>
						</div>
						<dl class="rks-meta-list">
							<li><dt>Name</dt><dd><strong><?php echo esc_html( (string) $branch['name'] ); ?></strong></dd></li>
							<li><dt>City</dt><dd><?php echo esc_html( (string) $branch['city'] ); ?></dd></li>
							<li><dt>Type</dt><dd><span class="rks-tag rks-tag--<?php echo esc_attr( (string) $branch['type'] ); ?>"><?php echo esc_html( (string) $branch['type'] ); ?></span></dd></li>
						</dl>
					</div>
					<?php endif; ?>

					<?php if ( $payer ) : ?>
					<div class="rks-admin-card">
						<div class="rks-section-head" style="margin-top:0;">
							<?php echo Icons::chip( 'user-circle', 'teal' ); ?>
							<h2>Payer Account</h2>
						</div>
						<dl class="rks-meta-list">
							<li><dt>User</dt><dd><strong><?php echo esc_html( $payer->display_name ?: $payer->user_login ); ?></strong></dd></li>
							<li><dt>Email</dt><dd><?php echo esc_html( $payer->user_email ); ?></dd></li>
							<li><dt>Login</dt><dd><code><?php echo esc_html( $payer->user_login ); ?></code></dd></li>
						</dl>
					</div>
					<?php endif; ?>

				</div>

			</div>

			<div class="rks-admin-card rks-admin-card--alert" style="margin-top:24px;">
				<div class="rks-section-head" style="margin-top:0;">
					<?php echo Icons::chip( 'trash', 'red' ); ?>
					<h2>Danger Zone</h2>
				</div>
				<p>Permanently delete this payment record. Linked admission will be unlinked, but the admission record and student account will not be removed.</p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'rks_payment_delete' ); ?>
					<input type="hidden" name="action" value="rks_payment_delete">
					<input type="hidden" name="payment_id" value="<?php echo (int) $payment['id']; ?>">
					<button type="submit" class="button button-link-delete" onclick="return confirm('Permanently delete this payment record? This cannot be undone.');">
						<?php echo Icons::svg( 'trash', 12 ); ?> Delete Payment
					</button>
				</form>
			</div>
		</div>
		<?php
	}

	private function can_access_admission_record( array $adm ): bool {
		if ( current_user_can( 'rks_view_all_admissions' ) ) {
			return true;
		}

		$user_branch = (int) get_user_meta( get_current_user_id(), 'rks_branch_id', true );
		return (int) ( $adm['branch_id'] ?? 0 ) === $user_branch;
	}

	private function can_access_payment_record( array $payment ): bool {
		if ( current_user_can( 'rks_view_all_payments' ) ) {
			return true;
		}

		$user_branch = (int) get_user_meta( get_current_user_id(), 'rks_branch_id', true );
		return (int) ( $payment['branch_id'] ?? 0 ) === $user_branch;
	}

	private function render_add_admission_form(): void {
		global $wpdb;
		$courses = get_posts( [
			'post_type'      => \RKSiksha\Core\Modules\Courses::CPT_COURSE,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		] );

		$branches = $wpdb->get_results( "SELECT id, name, type, city FROM " . DB::table( 'branches' ) . " WHERE status = 'active' ORDER BY type ASC, name ASC", ARRAY_A );
		?>
		<div class="wrap rks-admin-wrap">
			<header class="rks-page-hero">
				<div class="rks-page-hero__main">
					<div class="rks-page-hero__icon"><?php echo Icons::svg( 'plus', 24 ); ?></div>
					<div>
						<small>Backdated Admission</small>
						<h1>Add New Student Admission</h1>
						<p>Manually onboard a student with a custom/backdated admission date, approved course enrollment, and payment proof.</p>
					</div>
				</div>
				<div class="rks-page-hero__actions">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::SLUG_LIST ) ); ?>" class="button"><?php echo Icons::svg( 'arrow-left', 12 ); ?> Back to queue</a>
				</div>
			</header>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="rks-manual-admission-form" enctype="multipart/form-data" style="max-width:850px; margin-top:20px; background:#fff; padding:30px; border-radius:8px; box-shadow:0 1px 3px rgba(0,0,0,0.05); border:1px solid #e0e3e6;">
				<?php wp_nonce_field( 'rks_admission_add' ); ?>
				<input type="hidden" name="action" value="rks_admission_add">

				<h2>1. Personal Details</h2>
				<table class="form-table" role="presentation">
					<tr>
						<th><label for="full_name">Full Name <span class="required">*</span></label></th>
						<td><input type="text" id="full_name" name="full_name" class="regular-text" required></td>
					</tr>
					<tr>
						<th><label for="email">Email Address <span class="required">*</span></label></th>
						<td><input type="email" id="email" name="email" class="regular-text" required></td>
					</tr>
					<tr>
						<th><label for="phone">Student Mobile No. <span class="required">*</span></label></th>
						<td><input type="tel" id="phone" name="phone" class="regular-text" placeholder="+91 9999999999" required></td>
					</tr>
					<tr>
						<th><label for="father_name">Father's Name <span class="required">*</span></label></th>
						<td><input type="text" id="father_name" name="father_name" class="regular-text" required></td>
					</tr>
					<tr>
						<th><label for="parent_mobile">Parent's Mobile No. <span class="required">*</span></label></th>
						<td><input type="tel" id="parent_mobile" name="parent_mobile" class="regular-text" placeholder="+91 9999999999" required></td>
					</tr>
					<tr>
						<th><label for="dob">Date of Birth <span class="required">*</span></label></th>
						<td><input type="date" id="dob" name="dob" required></td>
					</tr>
					<tr>
						<th><label for="gender">Gender <span class="required">*</span></label></th>
						<td>
							<select id="gender" name="gender" required>
								<option value="">— Select —</option>
								<option value="male">Male</option>
								<option value="female">Female</option>
								<option value="other">Other</option>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="nationality">Nationality <span class="required">*</span></label></th>
						<td><input type="text" id="nationality" name="nationality" class="regular-text" value="Indian" required></td>
					</tr>
					<tr>
						<th><label for="religion">Religion <span class="required">*</span></label></th>
						<td>
							<select id="religion" name="religion" required>
								<option value="">— Select —</option>
								<option value="Hindu">Hindu</option>
								<option value="Muslim">Muslim</option>
								<option value="Sikh">Sikh</option>
								<option value="Christian">Christian</option>
								<option value="Buddhist">Buddhist</option>
								<option value="Jain">Jain</option>
								<option value="Other">Other</option>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="qualification">Qualification <span class="required">*</span></label></th>
						<td><input type="text" id="qualification" name="qualification" class="regular-text" placeholder="e.g. 12th, B.Sc..." required></td>
					</tr>
					<tr>
						<th><label for="address">Permanent Address <span class="required">*</span></label></th>
						<td><textarea id="address" name="address" rows="3" class="large-text" required></textarea></td>
					</tr>
					<tr>
						<th><label for="permanent_district">Permanent District <span class="required">*</span></label></th>
						<td><input type="text" id="permanent_district" name="permanent_district" class="regular-text" required></td>
					</tr>
					<tr>
						<th><label for="permanent_state">Permanent State <span class="required">*</span></label></th>
						<td><input type="text" id="permanent_state" name="permanent_state" class="regular-text" required></td>
					</tr>
					<tr>
						<th><label for="permanent_nation">Permanent Nation <span class="required">*</span></label></th>
						<td><input type="text" id="permanent_nation" name="permanent_nation" class="regular-text" value="India" required></td>
					</tr>
					<tr>
						<th><label for="present_address">Present Address <span class="required">*</span></label></th>
						<td>
							<textarea id="present_address" name="present_address" rows="3" class="large-text" required></textarea>
							<p class="description" style="margin-top:6px;"><label><input type="checkbox" id="same_as_permanent"> Same as Permanent Address</label></p>
						</td>
					</tr>
					<tr>
						<th><label for="present_district">Present District <span class="required">*</span></label></th>
						<td><input type="text" id="present_district" name="present_district" class="regular-text" required></td>
					</tr>
					<tr>
						<th><label for="present_state">Present State <span class="required">*</span></label></th>
						<td><input type="text" id="present_state" name="present_state" class="regular-text" required></td>
					</tr>
					<tr>
						<th><label for="present_nation">Present Nation <span class="required">*</span></label></th>
						<td><input type="text" id="present_nation" name="present_nation" class="regular-text" value="India" required></td>
					</tr>
					<tr>
						<th><label for="student_photo">Student Photo <span class="required">*</span></label></th>
						<td>
							<input type="file" id="student_photo" name="student_photo" accept="image/*" required>
							<p class="description">Passport-size photo. JPG, PNG or WEBP. Max 5MB.</p>
						</td>
					</tr>
					<tr>
						<th><label for="aadhar_front">Aadhaar Card Front <span class="required">*</span></label></th>
						<td>
							<input type="file" id="aadhar_front" name="aadhar_front" accept="image/*,application/pdf" required>
							<p class="description">Upload Aadhaar card front image or PDF. Max 5MB.</p>
						</td>
					</tr>
					<tr>
						<th><label for="aadhar_back">Aadhaar Card Back <span class="required">*</span></label></th>
						<td>
							<input type="file" id="aadhar_back" name="aadhar_back" accept="image/*,application/pdf" required>
							<p class="description">Upload Aadhaar card back image or PDF. Max 5MB.</p>
						</td>
					</tr>
					<tr id="marksheet_row" style="display:none;">
						<th><label for="marksheet">10th / 12th Marksheet <span class="required">*</span></label></th>
						<td>
							<input type="file" id="marksheet" name="marksheet" accept="image/*,application/pdf">
							<p class="description">Required for diploma courses only. Upload marksheet image/PDF.</p>
						</td>
					</tr>
				</table>

				<h2 style="margin-top:30px;">2. Course & Branch</h2>
				<table class="form-table" role="presentation">
					<tr>
						<th><label for="branch_id">Branch / Study Centre <span class="required">*</span></label></th>
						<td>
							<select id="branch_id" name="branch_id" required>
								<option value="">— Select Branch —</option>
								<?php foreach ( $branches as $b ) : ?>
									<option value="<?php echo (int) $b['id']; ?>"><?php echo esc_html( $b['name'] . ( $b['city'] ? ' — ' . $b['city'] : '' ) ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="course_category">Course Category <span class="required">*</span></label></th>
						<td>
							<select id="course_category" name="course_category" required>
								<option value="">— Select Category —</option>
								<option value="<?php echo esc_attr( \RKSiksha\Core\Modules\Courses::CAT_CERTIFICATE ); ?>">Certificate Courses</option>
								<option value="<?php echo esc_attr( \RKSiksha\Core\Modules\Courses::CAT_DIPLOMA ); ?>">Diploma Courses</option>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="course_id">Course <span class="required">*</span></label></th>
						<td>
							<select id="course_id" name="course_id" required>
								<option value="">— Select Course —</option>
								<?php foreach ( $courses as $c ) : 
									$is_paid = (bool) get_post_meta( $c->ID, \RKSiksha\Core\Modules\Courses::M_IS_PAID, true );
									$pay_mode = (string) get_post_meta( $c->ID, \RKSiksha\Core\Modules\Courses::M_PAYMENT_MODE, true ) ?: 'one_time';
									$monthly_fee = (float) get_post_meta( $c->ID, \RKSiksha\Core\Modules\Courses::M_MONTHLY_FEE, true );
									$total_price = (float) get_post_meta( $c->ID, \RKSiksha\Core\Modules\Courses::M_PRICE, true );

									$course_fee_due = 0.0;
									if ( $is_paid ) {
										if ( $pay_mode === 'monthly' && $monthly_fee > 0 ) {
											$course_fee_due = $monthly_fee;
										} else {
											$course_fee_due = $total_price;
										}
									}
									$initial_fees = $course_fee_due + 500.0; // admission fee is 500

									$cat = \RKSiksha\Core\Modules\Courses::classify_admission_category( (int) $c->ID );
								?>
									<option value="<?php echo (int) $c->ID; ?>" data-category="<?php echo esc_attr( $cat ); ?>" data-price="<?php echo esc_attr( $initial_fees ); ?>">
										<?php echo esc_html( $c->post_title . ( $is_paid ? ' (Paid)' : ' (Free)' ) ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
				</table>

				<h2 style="margin-top:30px;">3. Date & Payment Details (Backdating)</h2>
				<table class="form-table" role="presentation">
					<tr>
						<th><label for="admission_date">Admission Date <span class="required">*</span></label></th>
						<td>
							<input type="date" id="admission_date" name="admission_date" value="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>" required>
							<p class="description">This date will be applied to the admission record, WordPress student account registration, course enrollment, and payment transaction.</p>
						</td>
					</tr>
					<tr>
						<th><label for="fees_amount">Fees Paid <span class="required">*</span></label></th>
						<td><input type="number" id="fees_amount" name="fees_amount" value="500" min="0" step="1" required></td>
					</tr>
					<tr>
						<th><label for="payment_method">Payment Method <span class="required">*</span></label></th>
						<td>
							<select id="payment_method" name="payment_method" required>
								<option value="offline">Offline Cash / Direct Deposit</option>
								<option value="upi">UPI / Online Transfer</option>
								<option value="razorpay">Razorpay</option>
								<option value="cashfree">Cashfree</option>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="utr">Transaction ID / UTR / Reference</label></th>
						<td>
							<input type="text" id="utr" name="utr" class="regular-text" placeholder="e.g. 123456789012">
							<p class="description">Optional. Leave empty for offline payments to autogenerate reference code.</p>
						</td>
					</tr>
				</table>

				<div class="submit-wrapper" style="margin-top:30px; border-top:1px solid #e0e3e6; padding-top:20px;">
					<button type="submit" class="button button-primary button-large"><?php echo Icons::svg( 'check', 14 ); ?> Onboard Student</button>
				</div>
			</form>
		</div>

		<script>
		jQuery(document).ready(function($) {
			var courseCategory = $('#course_category');
			var courseSelect = $('#course_id');
			var allOptions = courseSelect.find('option');

			courseCategory.on('change', function() {
				var cat = $(this).val();
				courseSelect.val('');
				allOptions.each(function() {
					var opt = $(this);
					if (opt.val() === '') {
						opt.show();
						return;
					}
					if (opt.data('category') === cat) {
						opt.show();
					} else {
						opt.hide();
					}
				});

				if (cat === '<?php echo esc_js( \RKSiksha\Core\Modules\Courses::CAT_DIPLOMA ); ?>') {
					$('#marksheet_row').show();
					$('#marksheet').prop('required', true);
				} else {
					$('#marksheet_row').hide();
					$('#marksheet').prop('required', false).val('');
				}
			});

			courseSelect.on('change', function() {
				var selectedOption = $(this).find('option:selected');
				var price = selectedOption.data('price');
				if (price !== undefined) {
					$('#fees_amount').val(price);
				}
			});

			$('#same_as_permanent').on('change', function() {
				if (this.checked) {
					$('#present_address').val($('#address').val());
					$('#present_district').val($('#permanent_district').val());
					$('#present_state').val($('#permanent_state').val());
					$('#present_nation').val($('#permanent_nation').val());
				}
			});

			$('#address, #permanent_district, #permanent_state, #permanent_nation').on('input', function() {
				if ($('#same_as_permanent').is(':checked')) {
					$('#present_address').val($('#address').val());
					$('#present_district').val($('#permanent_district').val());
					$('#present_state').val($('#permanent_state').val());
					$('#present_nation').val($('#permanent_nation').val());
				}
			});
		});
		</script>
		<?php
	}

	public function handle_add_admission(): void {
		check_admin_referer( 'rks_admission_add' );
		if ( ! current_user_can( 'rks_approve_admission' ) ) wp_die( 'Forbidden', 'Forbidden', [ 'response' => 403 ] );

		// Sanitize inputs
		$full_name      = sanitize_text_field( wp_unslash( (string) ( $_POST['full_name']     ?? '' ) ) );
		$email          = sanitize_email(      wp_unslash( (string) ( $_POST['email']         ?? '' ) ) );
		$phone          = sanitize_text_field( wp_unslash( (string) ( $_POST['phone']         ?? '' ) ) );
		$father_name    = sanitize_text_field( wp_unslash( (string) ( $_POST['father_name']   ?? '' ) ) );
		$father_occupation = '';
		$parent_mobile  = sanitize_text_field( wp_unslash( (string) ( $_POST['parent_mobile'] ?? '' ) ) );
		$dob            = sanitize_text_field( wp_unslash( (string) ( $_POST['dob']           ?? '' ) ) );
		$gender         = sanitize_text_field( wp_unslash( (string) ( $_POST['gender']        ?? '' ) ) );
		$nationality    = sanitize_text_field( wp_unslash( (string) ( $_POST['nationality']    ?? 'Indian' ) ) );
		$religion       = sanitize_text_field( wp_unslash( (string) ( $_POST['religion']       ?? '' ) ) );
		$qualification  = sanitize_text_field( wp_unslash( (string) ( $_POST['qualification'] ?? '' ) ) );
		
		$address            = sanitize_textarea_field( wp_unslash( (string) ( $_POST['address']            ?? '' ) ) );
		$permanent_district = sanitize_text_field( wp_unslash( (string) ( $_POST['permanent_district']     ?? '' ) ) );
		$permanent_state    = sanitize_text_field( wp_unslash( (string) ( $_POST['permanent_state']        ?? '' ) ) );
		$permanent_nation   = sanitize_text_field( wp_unslash( (string) ( $_POST['permanent_nation']       ?? 'India' ) ) );
		
		$present_address    = sanitize_textarea_field( wp_unslash( (string) ( $_POST['present_address']    ?? '' ) ) );
		$present_district   = sanitize_text_field( wp_unslash( (string) ( $_POST['present_district']       ?? '' ) ) );
		$present_state      = sanitize_text_field( wp_unslash( (string) ( $_POST['present_state']          ?? '' ) ) );
		$present_nation     = sanitize_text_field( wp_unslash( (string) ( $_POST['present_nation']         ?? 'India' ) ) );

		$branch_id       = (int) ( $_POST['branch_id'] ?? 0 );
		$course_id       = (int) ( $_POST['course_id'] ?? 0 );
		$course_category = sanitize_key( wp_unslash( (string) ( $_POST['course_category'] ?? '' ) ) );
		$admission_date  = sanitize_text_field( wp_unslash( (string) ( $_POST['admission_date'] ?? '' ) ) );
		$fees_amount     = (float) ( $_POST['fees_amount'] ?? 0 );
		$payment_method  = sanitize_key( wp_unslash( (string) ( $_POST['payment_method'] ?? 'offline' ) ) );
		$utr             = sanitize_text_field( wp_unslash( (string) ( $_POST['utr'] ?? '' ) ) );

		if ( ! $full_name || ! $email || ! $phone || ! $father_name || ! $parent_mobile || ! $dob || ! $gender || ! $nationality || ! $religion || ! $qualification || ! $address || ! $permanent_district || ! $permanent_state || ! $permanent_nation || ! $present_address || ! $present_district || ! $present_state || ! $present_nation || ! $branch_id || ! $course_id || ! $admission_date ) {
			wp_die( 'Please fill all required fields.', 'Validation Error', [ 'response' => 400 ] );
		}

		$course = get_post( $course_id );
		if ( ! $course || $course->post_type !== \RKSiksha\Core\Modules\Courses::CPT_COURSE ) {
			wp_die( 'Selected course is not valid.', 'Validation Error', [ 'response' => 400 ] );
		}

		// Handle file uploads
		$admissions = new Admissions();
		$documents = [];

		$photo_store = $admissions->store_document( (array) ( $_FILES['student_photo'] ?? [] ), 'student-photo', [ 'image/jpeg', 'image/jpg', 'image/png', 'image/webp' ] );
		if ( empty( $photo_store['ok'] ) ) {
			wp_die( 'Student photo upload failed: ' . ( $photo_store['error'] ?? 'Unknown error' ), 'Upload Error', [ 'response' => 400 ] );
		}
		$documents['student_photo'] = [
			'label'         => 'Student Photo',
			'path'          => (string) $photo_store['path'],
			'original_name' => (string) ( $photo_store['original_name'] ?? '' ),
			'mime'          => (string) ( $photo_store['mime'] ?? '' ),
		];

		$aadhar_front_store = $admissions->store_document( (array) ( $_FILES['aadhar_front'] ?? [] ), 'aadhar-front' );
		if ( empty( $aadhar_front_store['ok'] ) ) {
			wp_die( 'Aadhaar card front upload failed: ' . ( $aadhar_front_store['error'] ?? 'Unknown error' ), 'Upload Error', [ 'response' => 400 ] );
		}
		$documents['aadhar_front'] = [
			'label'         => 'Aadhaar Card Front',
			'path'          => (string) $aadhar_front_store['path'],
			'original_name' => (string) ( $aadhar_front_store['original_name'] ?? '' ),
			'mime'          => (string) ( $aadhar_front_store['mime'] ?? '' ),
		];

		$aadhar_back_store = $admissions->store_document( (array) ( $_FILES['aadhar_back'] ?? [] ), 'aadhar-back' );
		if ( empty( $aadhar_back_store['ok'] ) ) {
			wp_die( 'Aadhaar card back upload failed: ' . ( $aadhar_back_store['error'] ?? 'Unknown error' ), 'Upload Error', [ 'response' => 400 ] );
		}
		$documents['aadhar_back'] = [
			'label'         => 'Aadhaar Card Back',
			'path'          => (string) $aadhar_back_store['path'],
			'original_name' => (string) ( $aadhar_back_store['original_name'] ?? '' ),
			'mime'          => (string) ( $aadhar_back_store['mime'] ?? '' ),
		];

		$requires_marksheet = $course_category === \RKSiksha\Core\Modules\Courses::CAT_DIPLOMA;
		if ( $requires_marksheet ) {
			$marksheet_store = $admissions->store_document( (array) ( $_FILES['marksheet'] ?? [] ), 'marksheet' );
			if ( empty( $marksheet_store['ok'] ) ) {
				wp_die( 'Marksheet upload failed: ' . ( $marksheet_store['error'] ?? 'Unknown error' ), 'Upload Error', [ 'response' => 400 ] );
			}
			$documents['marksheet'] = [
				'label'         => '10th / 12th Marksheet',
				'path'          => (string) $marksheet_store['path'],
				'original_name' => (string) ( $marksheet_store['original_name'] ?? '' ),
				'mime'          => (string) ( $marksheet_store['mime'] ?? '' ),
			];
		}

		// Parse custom admission datetime
		$adm_datetime = date( 'Y-m-d H:i:s', strtotime( $admission_date . ' ' . date( 'H:i:s' ) ) );

		// Check if user already exists
		$existing = get_user_by( 'email', $email );
		$user_id = 0;
		$username = '';
		$password = '';

		if ( $existing ) {
			$user_id = (int) $existing->ID;
			$username = $existing->user_login;
		} else {
			$base = sanitize_user( strtolower( str_replace( ' ', '', explode( '@', $email )[0] ) ), true ) ?: 'student';
			// Unique login lookup
			$try = $base;
			$i = 1;
			while ( username_exists( $try ) ) {
				$try = $base . $i;
				$i++;
			}
			$username = $try;
			$password = Admissions::password_from_dob( $dob );
			if ( '' === $password ) {
				wp_die( 'Date of birth is not valid.', 'Validation Error', [ 'response' => 400 ] );
			}
			$user_id = wp_create_user( $username, $password, $email );

			if ( is_wp_error( $user_id ) ) {
				wp_die( 'User creation failed: ' . $user_id->get_error_message(), 'Error' );
			}

			// Force backdate user_registered date in database!
			global $wpdb;
			$wpdb->update( $wpdb->users, [ 'user_registered' => $adm_datetime ], [ 'ID' => $user_id ] );

			wp_update_user( [
				'ID'           => $user_id,
				'display_name' => $full_name,
				'first_name'   => explode( ' ', $full_name )[0],
			] );

			$student_user = get_user_by( 'id', $user_id );
			$student_user->set_role( \RKSiksha\Core\Roles::STUDENT );
			update_user_meta( $user_id, 'rks_branch_id', $branch_id );
			update_user_meta( $user_id, 'rks_phone', $phone );
		}

		// Set student profile/ID card photo from onboarding documents
		if ( ! empty( $documents['student_photo']['path'] ) ) {
			$admissions->import_photo_to_media(
				(string) $documents['student_photo']['path'],
				(string) ( $documents['student_photo']['original_name'] ?? 'student-photo.jpg' ),
				(string) ( $documents['student_photo']['mime'] ?? 'image/jpeg' ),
				$user_id
			);
		}

		// Create Payment record (approved immediately)
		$payments = new Payments();
		$final_utr = $utr ?: ( strtoupper( $payment_method ) . '-' . strtoupper( wp_generate_password( 8, false, false ) ) );
		$payment_id = $payments->create( [
			'branch_id'    => $branch_id,
			'user_id'      => $user_id,
			'amount'       => $fees_amount,
			'method'       => $payment_method,
			'utr'          => $final_utr,
			'status'       => Payments::STATUS_APPROVED,
			'created_at'   => $adm_datetime,
			'verified_at'  => $adm_datetime,
			'verifier_user_id' => get_current_user_id(),
		] );

		// Create Enrollment
		$lms = new \RKSiksha\Core\Modules\Lms();
		$enrollment_id = $lms->enroll_user( $user_id, $course_id, $branch_id, 'active', $adm_datetime );

		// Create Admission record
		global $wpdb;
		$admission_meta = [
			'course_category'       => $course_category,
			'course_category_label' => $course_category === \RKSiksha\Core\Modules\Courses::CAT_DIPLOMA ? 'Diploma Courses' : 'Certificate Courses',
			'student_details'       => [
				'father_occupation' => $father_occupation,
				'parent_mobile'      => $parent_mobile,
				'nationality'        => $nationality,
				'religion'           => $religion,
				'permanent_address'  => $address,
				'permanent_district' => $permanent_district,
				'permanent_state'    => $permanent_state,
				'permanent_nation'   => $permanent_nation,
				'present_address'    => $present_address,
				'present_district'   => $present_district,
				'present_state'      => $present_state,
				'present_nation'     => $present_nation,
			],
			'fees'                  => [
				'admission_fee' => 500.0,
				'course_fee'    => $fees_amount - 500.0 > 0 ? $fees_amount - 500.0 : 0.0,
				'payable_now'   => $fees_amount,
				'course_total'   => $fees_amount,
			],
			'documents'             => $documents,
		];

		$wpdb->insert(
			DB::table( 'admissions' ),
			[
				'branch_id'     => $branch_id,
				'user_id'       => $user_id,
				'full_name'     => $full_name,
				'father_name'   => $father_name ?: null,
				'email'         => $email,
				'phone'         => $phone,
				'dob'           => $dob ?: null,
				'gender'        => $gender ?: null,
				'address'       => $address ?: null,
				'qualification' => $qualification ?: null,
				'course_id'     => $course_id,
				'course_title'  => $course->post_title,
				'fees_amount'   => $fees_amount,
				'payment_id'    => $payment_id ?: null,
				'status'        => 'approved',
				'reviewed_by'   => get_current_user_id(),
				'reviewed_at'   => $adm_datetime,
				'created_at'    => $adm_datetime,
				'meta'          => DB::encode_meta( $admission_meta ),
			]
		);
		$adm_id = $wpdb->insert_id;

		if ( $adm_id > 0 && $payment_id > 0 ) {
			$wpdb->update( DB::table( 'payments' ), [ 'admission_id' => $adm_id ], [ 'id' => $payment_id ] );
		}

		if ( $adm_id > 0 ) {
			do_action( 'rks_admission_approved', $adm_id, $user_id, $branch_id );
		}

		wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG_LIST, 'view' => $adm_id, 'rks_msg' => 'approved' ], admin_url( 'admin.php' ) ) );
		exit;
	}
}
