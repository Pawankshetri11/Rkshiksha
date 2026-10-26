<?php
/**
 * Admin page: Franchise applications queue + single-franchise detail (super admin only).
 *
 * @package RKSiksha\Core
 */

declare( strict_types=1 );

namespace RKSiksha\Core\Admin;

use RKSiksha\Core\Modules\Branches;
use RKSiksha\Core\Modules\Franchises;
use RKSiksha\Core\Support\DB;
use RKSiksha\Core\Support\Icons;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class FranchisesPage {

	public const SLUG            = 'rks-franchises';
	public const SLUG_INQUIRIES  = 'rks-franchise-inquiries';
	public const SLUG_ONBOARDING = 'rks-franchise-onboarding';

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'register_menu' ], 35 );
		add_action( 'admin_post_rks_franchise_action', [ $this, 'handle_action' ] );
		add_action( 'admin_post_rks_franchise_reset_password', [ $this, 'handle_reset_password' ] );
		add_action( 'admin_post_rks_franchise_edit',   [ $this, 'handle_edit' ] );
		add_action( 'admin_post_rks_franchise_delete', [ $this, 'handle_delete' ] );
		add_action( 'admin_post_rks_franchise_document', [ $this, 'handle_document_download' ] );
	}

	public function register_menu(): void {
		add_submenu_page(
			Menu::SLUG_NETWORK,
			'All Franchises',
			'All Franchises',
			'rks_approve_franchise',
			self::SLUG,
			[ $this, 'render' ]
		);
		add_submenu_page(
			Menu::SLUG_NETWORK,
			'Franchise Inquiries',
			'Franchise Inquiries',
			'rks_approve_franchise',
			self::SLUG_INQUIRIES,
			[ $this, 'render' ]
		);
		add_submenu_page(
			Menu::SLUG_NETWORK,
			'Franchise Onboarding',
			'Franchise Onboarding',
			'rks_approve_franchise',
			self::SLUG_ONBOARDING,
			[ $this, 'render' ]
		);
	}

	public function handle_action(): void {
		check_admin_referer( 'rks_franchise_action' );
		if ( ! current_user_can( 'rks_approve_franchise' ) ) wp_die( 'Forbidden', 'Forbidden', [ 'response' => 403 ] );

		$id     = (int) ( $_POST['franchise_id'] ?? 0 );
		$action = sanitize_key( wp_unslash( (string) ( $_POST['do'] ?? '' ) ) );
		$note   = sanitize_textarea_field( wp_unslash( (string) ( $_POST['note'] ?? '' ) ) );

		$user = wp_get_current_user();
		$f    = new Franchises();
		$msg  = 'unknown';

		if ( 'approve' === $action ) {
			$res = $f->approve( $id, $user->ID );
			$msg = $res['ok'] ? 'approved' : 'error';
		} elseif ( 'reject' === $action ) {
			$res = $f->reject( $id, $user->ID, $note );
			$msg = $res['ok'] ? 'rejected' : 'error';
		}

		$return_page = $this->sanitize_list_page( (string) ( $_POST['return_page'] ?? self::SLUG ) );
		$redir = isset( $_POST['return'] ) && (string) $_POST['return'] === 'view'
			? add_query_arg( [ 'page' => $return_page, 'view' => $id, 'rks_msg' => $msg ], admin_url( 'admin.php' ) )
			: add_query_arg( [ 'page' => $return_page, 'rks_msg' => $msg ], admin_url( 'admin.php' ) );

		wp_safe_redirect( $redir );
		exit;
	}

	public function handle_reset_password(): void {
		check_admin_referer( 'rks_franchise_reset_password' );
		if ( ! current_user_can( 'rks_view_global_reports' ) ) {
			wp_die( 'Forbidden', 'Forbidden', [ 'response' => 403 ] );
		}

		$id = (int) ( $_POST['franchise_id'] ?? 0 );
		if ( $id <= 0 ) {
			wp_die( 'Invalid franchise ID', 'Bad Request', [ 'response' => 400 ] );
		}

		$f   = new Franchises();
		$row = $f->get( $id );
		if ( ! $row ) {
			wp_die( 'Franchise not found', 'Not Found', [ 'response' => 404 ] );
		}

		// Resolve the owner user via the linked branch (branches.franchise_id -> owner_user_id).
		global $wpdb;
		$branches_tbl = DB::table( 'branches' );
		$branch       = $wpdb->get_row( $wpdb->prepare(
			"SELECT id, name, city, owner_user_id FROM $branches_tbl WHERE franchise_id = %d ORDER BY id ASC LIMIT 1",
			$id
		), ARRAY_A );

		$user_id = $branch ? (int) $branch['owner_user_id'] : 0;
		if ( ! $user_id ) {
			$redir = add_query_arg( [ 'page' => self::SLUG, 'view' => $id, 'rks_msg' => 'no_user' ], admin_url( 'admin.php' ) );
			wp_safe_redirect( $redir );
			exit;
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			$redir = add_query_arg( [ 'page' => self::SLUG, 'view' => $id, 'rks_msg' => 'no_user' ], admin_url( 'admin.php' ) );
			wp_safe_redirect( $redir );
			exit;
		}

		// Generate and apply the new password.
		$pass = wp_generate_password( 14, true, false );
		wp_set_password( $pass, $user_id );

		// Reuse the same transient shape so the existing card UI renders it automatically.
		set_transient( 'rks_franchise_card_' . $id, [
			'applicant_name' => (string) $row['applicant_name'],
			'branch_name'    => (string) ( $branch['name'] ?? 'Rishikul Shiksha — ' . $row['city'] ),
			'city'           => (string) $row['city'],
			'email'          => (string) $row['email'],
			'username'       => (string) $user->user_login,
			'password'       => $pass,
			'login_url'      => home_url( '/login/' ),
			'created_at'     => current_time( 'mysql' ),
		], DAY_IN_SECONDS );

		$redir = add_query_arg(
			[ 'page' => self::SLUG, 'view' => $id, 'rks_msg' => 'password_reset' ],
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $redir );
		exit;
	}

	public function handle_edit(): void {
		check_admin_referer( 'rks_franchise_edit' );
		if ( ! current_user_can( 'rks_approve_franchise' ) ) {
			wp_die( 'Forbidden', 'Forbidden', [ 'response' => 403 ] );
		}
		$id = (int) ( $_POST['franchise_id'] ?? 0 );
		if ( $id <= 0 ) {
			wp_die( 'Invalid', 'Bad Request', [ 'response' => 400 ] );
		}

		( new Franchises() )->update( $id, [
			'applicant_name' => sanitize_text_field( wp_unslash( (string) ( $_POST['applicant_name'] ?? '' ) ) ),
			'phone'          => sanitize_text_field( wp_unslash( (string) ( $_POST['phone']          ?? '' ) ) ),
			'email'          => sanitize_email(      wp_unslash( (string) ( $_POST['email']          ?? '' ) ) ),
			'city'           => sanitize_text_field( wp_unslash( (string) ( $_POST['city']           ?? '' ) ) ),
		] );

		// Also rename the linked franchise branch if the user provided a new branch name.
		$branch_name = sanitize_text_field( wp_unslash( (string) ( $_POST['branch_name'] ?? '' ) ) );
		if ( $branch_name !== '' ) {
			global $wpdb;
			$branch_id = (int) $wpdb->get_var( $wpdb->prepare(
				'SELECT id FROM ' . \RKSiksha\Core\Support\DB::table( 'branches' ) . ' WHERE franchise_id = %d LIMIT 1',
				$id
			) );
			if ( $branch_id > 0 ) {
				( new \RKSiksha\Core\Modules\Branches() )->update( $branch_id, [ 'name' => $branch_name ] );
			}
		}

		wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG, 'view' => $id, 'rks_msg' => 'updated' ], admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_delete(): void {
		check_admin_referer( 'rks_franchise_delete' );
		if ( ! current_user_can( 'rks_approve_franchise' ) ) {
			wp_die( 'Forbidden', 'Forbidden', [ 'response' => 403 ] );
		}
		$id = (int) ( $_POST['franchise_id'] ?? 0 );
		if ( $id <= 0 ) {
			wp_die( 'Invalid', 'Bad Request', [ 'response' => 400 ] );
		}

		( new Franchises() )->delete( $id );

		$return_page = $this->sanitize_list_page( (string) ( $_POST['return_page'] ?? self::SLUG ) );
		wp_safe_redirect( add_query_arg( [ 'page' => $return_page, 'rks_msg' => 'deleted' ], admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_document_download(): void {
		$id  = (int) ( $_GET['franchise_id'] ?? 0 );
		$doc = sanitize_key( wp_unslash( (string) ( $_GET['doc'] ?? '' ) ) );
		check_admin_referer( 'rks_franchise_document_' . $id . '_' . $doc );

		if ( ! current_user_can( 'rks_approve_franchise' ) ) {
			wp_die( 'Forbidden', 'Forbidden', [ 'response' => 403 ] );
		}
		if ( $id <= 0 || '' === $doc ) {
			wp_die( 'Invalid document request', 'Bad Request', [ 'response' => 400 ] );
		}

		$row = ( new Franchises() )->get( $id );
		if ( ! $row ) {
			wp_die( 'Franchise not found', 'Not Found', [ 'response' => 404 ] );
		}

		$meta      = Franchises::decode_meta( $row['meta'] ?? '' );
		$documents = is_array( $meta['documents'] ?? null ) ? $meta['documents'] : [];
		$file      = is_array( $documents[ $doc ] ?? null ) ? $documents[ $doc ] : [];
		$path      = (string) ( $file['path'] ?? '' );

		if ( '' === $path || ! is_readable( $path ) ) {
			wp_die( 'Document file is missing.', 'Not Found', [ 'response' => 404 ] );
		}

		$uploads     = wp_upload_dir();
		$private_dir = realpath( trailingslashit( (string) $uploads['basedir'] ) . 'rksiksha-private' );
		$real_path   = realpath( $path );
		if ( ! $private_dir || ! $real_path ) {
			wp_die( 'Document path is invalid.', 'Bad Request', [ 'response' => 400 ] );
		}

		$private_norm = trailingslashit( wp_normalize_path( $private_dir ) );
		$path_norm    = wp_normalize_path( $real_path );
		if ( 0 !== strpos( $path_norm, $private_norm ) ) {
			wp_die( 'Document path is invalid.', 'Bad Request', [ 'response' => 400 ] );
		}

		$filename = sanitize_file_name( (string) ( $file['original_name'] ?? basename( $real_path ) ) );
		$mime     = (string) ( $file['mime'] ?? 'application/octet-stream' );

		nocache_headers();
		header( 'Content-Type: ' . $mime );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . filesize( $real_path ) );
		readfile( $real_path );
		exit;
	}

	public function render(): void {
		if ( ! current_user_can( 'rks_approve_franchise' ) ) wp_die( 'Forbidden' );

		$view_id = isset( $_GET['view'] ) ? (int) $_GET['view'] : 0;
		if ( $view_id > 0 ) { $this->render_detail( $view_id ); return; }

		$this->render_list();
	}

	private function current_list_page(): string {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( (string) $_GET['page'] ) ) : self::SLUG;
		return $this->sanitize_list_page( $page );
	}

	private function sanitize_list_page( string $page ): string {
		return in_array( $page, [ self::SLUG, self::SLUG_INQUIRIES, self::SLUG_ONBOARDING ], true ) ? $page : self::SLUG;
	}

	private function form_type_for_page( string $page ): string {
		if ( self::SLUG_INQUIRIES === $page ) {
			return 'inquiry';
		}
		if ( self::SLUG_ONBOARDING === $page ) {
			return 'onboarding';
		}
		return '';
	}

	/**
	 * @return array{eyebrow:string,title:string,description:string,empty:string}
	 */
	private function list_copy( string $form_type ): array {
		if ( 'inquiry' === $form_type ) {
			return [
				'eyebrow'     => 'Franchise Leads',
				'title'       => 'Franchise Inquiries',
				'description' => 'Quick enquiries from the Franchise page. These are leads only; share the register form before approval.',
				'empty'       => 'No franchise enquiries match this filter.',
			];
		}

		if ( 'onboarding' === $form_type ) {
			return [
				'eyebrow'     => 'Partner Onboarding',
				'title'       => 'Franchise Onboarding',
				'description' => 'Final register form submissions with Aadhaar, location, agreement and printable PDF details.',
				'empty'       => 'No franchise onboarding submissions match this filter.',
			];
		}

		return [
			'eyebrow'     => 'Partner Network',
			'title'       => 'All Franchise Entries',
			'description' => 'Combined view of franchise inquiries and final onboarding submissions.',
			'empty'       => 'No franchise entries match this filter.',
		];
	}

	private function render_list(): void {
		$page_slug   = $this->current_list_page();
		$form_filter = $this->form_type_for_page( $page_slug );
		$copy        = $this->list_copy( $form_filter );
		$status_filter = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( (string) $_GET['status'] ) ) : 'all';
		$args = [ 'limit' => 100 ];
		if ( 'all' !== $status_filter ) $args['status'] = $status_filter;
		if ( '' !== $form_filter ) $args['form_type'] = $form_filter;

		$rows = ( new Franchises() )->list( $args );
		$msg  = isset( $_GET['rks_msg'] ) ? sanitize_key( wp_unslash( (string) $_GET['rks_msg'] ) ) : '';
		?>
		<div class="wrap rks-admin-wrap">
			<header class="rks-page-hero">
				<div class="rks-page-hero__main">
					<div class="rks-page-hero__icon"><?php echo Icons::svg( 'briefcase', 24 ); ?></div>
					<div>
						<small><?php echo esc_html( $copy['eyebrow'] ); ?></small>
						<h1><?php echo esc_html( $copy['title'] ); ?></h1>
						<p><?php echo esc_html( $copy['description'] ); ?></p>
					</div>
				</div>
				<div class="rks-page-hero__actions">
					<a href="<?php echo esc_url( home_url( '/franchise-register/' ) ); ?>" target="_blank" rel="noopener" class="button"><?php echo Icons::svg( 'link', 14 ); ?> Register Form</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . Menu::SLUG . '-branches' ) ); ?>" class="button"><?php echo Icons::svg( 'building', 14 ); ?> All Branches</a>
				</div>
			</header>

			<?php if ( $msg ) : ?>
				<div class="notice notice-<?php echo $msg === 'error' ? 'error' : 'success'; ?> is-dismissible">
					<p><?php echo esc_html( ucfirst( $msg ) ); ?></p>
				</div>
			<?php endif; ?>

			<div class="rks-admin-card rks-list-card">
				<div class="rks-list-card__head">
					<span class="rks-list-card__label">Queue:</span>
					<ul class="rks-page-filters rks-filter-tabs">
						<?php foreach ( [ self::SLUG => 'All', self::SLUG_INQUIRIES => 'Inquiries', self::SLUG_ONBOARDING => 'Onboarding' ] as $slug => $label ) : ?>
							<li>
								<a href="<?php echo esc_url( add_query_arg( [ 'page' => $slug ], admin_url( 'admin.php' ) ) ); ?>" class="<?php echo $page_slug === $slug ? 'is-active' : ''; ?>"><?php echo esc_html( $label ); ?></a>
							</li>
						<?php endforeach; ?>
					</ul>
					<span class="rks-list-card__label">Filter:</span>
					<ul class="rks-page-filters rks-filter-tabs">
						<?php foreach ( [ 'all' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'onboarded' => 'Onboarded', 'rejected' => 'Rejected' ] as $s => $label ) : ?>
							<li>
								<a href="<?php echo esc_url( add_query_arg( [ 'page' => $page_slug, 'status' => $s ], admin_url( 'admin.php' ) ) ); ?>" class="<?php echo $status_filter === $s ? 'is-active' : ''; ?>"><?php echo esc_html( $label ); ?></a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>

				<table class="widefat striped rks-list-card__table">
					<thead>
						<tr>
							<th>ID</th>
							<th>Applicant</th>
							<th>Contact</th>
							<th>City</th>
							<th>Type</th>
							<th>Status</th>
							<th>Submitted</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $rows ) ) : ?>
							<tr><td colspan="8"><div class="rks-empty"><?php echo Icons::chip( 'briefcase', 'slate', 22 ); ?><h3>No entries</h3><small><?php echo esc_html( $copy['empty'] ); ?></small><small style="margin-top:6px;display:block;color:#6b7a82;">Leads arrive from the franchise enquiry form and final onboarding arrives from <a href="<?php echo esc_url( home_url( '/franchise-register/' ) ); ?>" target="_blank" rel="noopener">Franchise Register</a>.</small></div></td></tr>
						<?php else : foreach ( $rows as $r ) : ?>
							<?php
							$row_meta = Franchises::decode_meta( $r['meta'] ?? '' );
							$form_type = sanitize_key( (string) ( $row_meta['form_type'] ?? 'application' ) );
							$type_label = 'inquiry' === $form_type ? 'Inquiry' : 'Onboarding';
							?>
							<tr>
								<td>#<?php echo (int) $r['id']; ?></td>
								<td><strong><?php echo esc_html( (string) $r['applicant_name'] ); ?></strong></td>
								<td>
									<small><?php echo esc_html( (string) $r['email'] ); ?></small><br>
									<small><?php echo esc_html( (string) $r['phone'] ); ?></small>
								</td>
								<td><?php echo esc_html( (string) $r['city'] ); ?></td>
								<td><span class="rks-tag rks-tag--main"><?php echo esc_html( $type_label ); ?></span></td>
								<td><span class="rks-tag rks-tag--<?php echo esc_attr( (string) $r['status'] ); ?>"><?php echo esc_html( (string) $r['status'] ); ?></span></td>
								<td><?php echo esc_html( (string) $r['created_at'] ); ?></td>
								<td>
									<a href="<?php echo esc_url( add_query_arg( [ 'page' => $page_slug, 'view' => (int) $r['id'] ], admin_url( 'admin.php' ) ) ); ?>" class="button button-primary button-small"><?php echo Icons::svg( 'eye', 12 ); ?> View Details</a>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block; margin-left:4px;">
										<?php wp_nonce_field( 'rks_franchise_delete' ); ?>
										<input type="hidden" name="action" value="rks_franchise_delete">
										<input type="hidden" name="franchise_id" value="<?php echo (int) $r['id']; ?>">
										<input type="hidden" name="return_page" value="<?php echo esc_attr( $page_slug ); ?>">
										<button type="submit" class="button button-small button-link-delete" onclick="return confirm('Permanently delete this franchise application? This cannot be undone.');"><?php echo Icons::svg( 'trash', 12 ); ?> Delete</button>
									</form>
								</td>
							</tr>
						<?php endforeach; endif; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}

	private function render_detail( int $id ): void {
		global $wpdb;
		$f   = new Franchises();
		$row = $f->get( $id );
		if ( ! $row ) {
			echo '<div class="wrap rks-admin-wrap"><div class="notice notice-error"><p>Franchise application not found.</p></div></div>';
			return;
		}

		$msg = isset( $_GET['rks_msg'] ) ? sanitize_key( wp_unslash( (string) $_GET['rks_msg'] ) ) : '';

		$settings = (array) get_option( 'rks_settings', [] );
		$currency = (string) ( $settings['currency_symbol'] ?? '₹' );
		$meta     = Franchises::decode_meta( $row['meta'] ?? '' );
		$form_type = sanitize_key( (string) ( $meta['form_type'] ?? 'application' ) );
		$form_label = 'inquiry' === $form_type ? 'Franchise Inquiry' : 'Franchise Onboarding';
		$list_page  = $this->current_list_page();
		$list_copy  = $this->list_copy( $this->form_type_for_page( $list_page ) );
		$location = is_array( $meta['location'] ?? null ) ? $meta['location'] : [];
		$premises = is_array( $meta['premises'] ?? null ) ? $meta['premises'] : [];
		$agreement = is_array( $meta['agreement'] ?? null ) ? $meta['agreement'] : [];
		$documents = is_array( $meta['documents'] ?? null ) ? $meta['documents'] : [];

		// Linked branch
		$branch_id = (int) ( $row['branch_id'] ?? 0 );
		$branch    = $branch_id ? ( new Branches() )->get( $branch_id ) : null;

		// Linked branch admin user
		$admin_user = null;
		if ( $branch && ! empty( $branch['owner_user_id'] ) ) {
			$admin_user = get_userdata( (int) $branch['owner_user_id'] );
		}

		// Reviewer
		$reviewer = ! empty( $row['reviewed_by'] ) ? get_userdata( (int) $row['reviewed_by'] ) : null;

		// Stats — only if onboarded with branch
		$stats = [
			'students'      => 0,
			'admissions'    => 0,
			'approved_adm'  => 0,
			'revenue_total' => 0.0,
			'revenue_30d'   => 0.0,
			'certs'         => 0,
		];
		if ( $branch_id ) {
			$adm = DB::table( 'admissions' );
			$pay = DB::table( 'payments' );
			$enr = DB::table( 'enrollments' );
			$cer = DB::table( 'certificates' );
			$stats['students']      = (int)   $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT user_id) FROM $enr WHERE branch_id = %d AND user_id > 0", $branch_id ) );
			$stats['admissions']    = (int)   $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $adm WHERE branch_id = %d", $branch_id ) );
			$stats['approved_adm']  = (int)   $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $adm WHERE branch_id = %d AND status='approved'", $branch_id ) );
			$stats['revenue_total'] = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(amount),0) FROM $pay WHERE branch_id = %d AND status='approved'", $branch_id ) );
			$stats['revenue_30d']   = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(amount),0) FROM $pay WHERE branch_id = %d AND status='approved' AND verified_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)", $branch_id ) );
			$stats['certs']         = (int)   $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $cer WHERE branch_id = %d AND status='valid'", $branch_id ) );

			// Recent payments
			$recent_payments = $wpdb->get_results( $wpdb->prepare(
				"SELECT id, amount, method, status, utr, created_at, verified_at FROM $pay WHERE branch_id = %d ORDER BY id DESC LIMIT 10", $branch_id
			), ARRAY_A );

			// Recent admissions
			$recent_admissions = $wpdb->get_results( $wpdb->prepare(
				"SELECT id, full_name, course_title, status, fees_amount, created_at FROM $adm WHERE branch_id = %d ORDER BY id DESC LIMIT 10", $branch_id
			), ARRAY_A );
		} else {
			$recent_payments   = [];
			$recent_admissions = [];
		}

		$initials = strtoupper( substr( trim( (string) $row['applicant_name'] ), 0, 1 ) ?: 'F' );
		?>
		<div class="wrap rks-admin-wrap">

			<p style="margin:14px 0;"><a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $list_page ) ); ?>" class="button"><?php echo Icons::svg( 'arrow-left', 12 ); ?> Back to <?php echo esc_html( $list_copy['title'] ); ?></a></p>

			<div class="rks-detail-hero">
				<div class="rks-detail-hero__avatar"><?php echo esc_html( $initials ); ?></div>
				<div class="rks-detail-hero__main">
					<small><?php echo esc_html( $form_label ); ?> · #<?php echo (int) $row['id']; ?></small>
					<h1><?php echo esc_html( (string) $row['applicant_name'] ); ?></h1>
					<p>
						<?php echo Icons::svg( 'pin', 12 ); ?> <?php echo esc_html( (string) $row['city'] ); ?>
						&nbsp;·&nbsp;
						<?php echo Icons::svg( 'mail', 12 ); ?> <?php echo esc_html( (string) $row['email'] ); ?>
						&nbsp;·&nbsp;
						<?php echo Icons::svg( 'phone', 12 ); ?> <?php echo esc_html( (string) $row['phone'] ); ?>
					</p>
					<p style="margin-top:8px;">
						<span class="rks-pill rks-pill--<?php echo $row['status'] === 'pending' ? 'warn' : ( $row['status'] === 'rejected' ? 'danger' : 'success' ); ?>"><?php echo esc_html( (string) $row['status'] ); ?></span>
						&nbsp;
						<small>Submitted <?php echo esc_html( human_time_diff( strtotime( (string) $row['created_at'] ), current_time( 'timestamp' ) ) ); ?> ago</small>
					</p>
				</div>
				<div class="rks-detail-hero__actions">
					<a href="mailto:<?php echo esc_attr( (string) $row['email'] ); ?>" class="button"><?php echo Icons::svg( 'mail', 12 ); ?> Email</a>
					<a href="tel:<?php echo esc_attr( (string) $row['phone'] ); ?>" class="button"><?php echo Icons::svg( 'phone', 12 ); ?> Call</a>
					<?php if ( $branch_id ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . Menu::SLUG . '-branches' ) ); ?>" class="button button-primary"><?php echo Icons::svg( 'building', 12 ); ?> Branch #<?php echo (int) $branch_id; ?></a>
					<?php endif; ?>
				</div>
			</div>

			<?php if ( $msg ) : ?>
				<div class="notice notice-<?php echo $msg === 'error' ? 'error' : 'success'; ?> is-dismissible"><p><?php echo esc_html( ucfirst( $msg ) ); ?></p></div>
			<?php endif; ?>

			<?php
			// Lookup linked franchise branch (if any) so admin can rename it too
			global $wpdb;
			$linked_branch_id = (int) $wpdb->get_var( $wpdb->prepare(
				'SELECT id FROM ' . \RKSiksha\Core\Support\DB::table( 'branches' ) . ' WHERE franchise_id = %d LIMIT 1',
				(int) $row['id']
			) );
			$linked_branch_name = '';
			if ( $linked_branch_id > 0 ) {
				$lb = ( new \RKSiksha\Core\Modules\Branches() )->get( $linked_branch_id );
				$linked_branch_name = (string) ( $lb['name'] ?? '' );
			}
			?>
			<details class="rks-admin-card" style="margin-bottom:14px;" <?php echo $msg === 'updated' ? 'open' : ''; ?>>
				<summary style="cursor:pointer; font-weight:600; color:var(--rks-teal); padding:8px 0;">
					<?php echo Icons::svg( 'edit', 14 ); ?> Edit Franchise Details
				</summary>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:14px;">
					<?php wp_nonce_field( 'rks_franchise_edit' ); ?>
					<input type="hidden" name="action" value="rks_franchise_edit">
					<input type="hidden" name="franchise_id" value="<?php echo (int) $row['id']; ?>">
					<table class="form-table">
						<?php if ( $linked_branch_id > 0 ) : ?>
						<tr><th><label>Branch Name</label></th><td>
							<input type="text" name="branch_name" value="<?php echo esc_attr( $linked_branch_name ); ?>" class="regular-text" placeholder="e.g. Rishikul Shiksha — Lucknow">
							<p class="description">Public-facing branch name. Updates the linked Branch record.</p>
						</td></tr>
						<?php endif; ?>
						<tr><th><label>Applicant / Owner Name</label></th><td><input type="text" name="applicant_name" value="<?php echo esc_attr( (string) $row['applicant_name'] ); ?>" class="regular-text" required></td></tr>
						<tr><th><label>Phone</label></th><td><input type="text" name="phone" value="<?php echo esc_attr( (string) $row['phone'] ); ?>" class="regular-text"></td></tr>
						<tr><th><label>Email</label></th><td><input type="email" name="email" value="<?php echo esc_attr( (string) $row['email'] ); ?>" class="regular-text"></td></tr>
						<tr><th><label>City</label></th><td><input type="text" name="city" value="<?php echo esc_attr( (string) $row['city'] ); ?>" class="regular-text"></td></tr>
					</table>
					<p><button class="button button-primary"><?php echo Icons::svg( 'check', 12 ); ?> Save Changes</button></p>
				</form>
			</details>

			<?php if ( $row['status'] === 'pending' && 'inquiry' !== $form_type ) : ?>
				<div class="rks-action-box">
					<h3><?php echo Icons::svg( 'alert', 14 ); ?> Action Required</h3>
					<p>Approve this application to create a branch + branch admin user automatically. Credentials will be emailed to <strong><?php echo esc_html( (string) $row['email'] ); ?></strong>.</p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;">
						<?php wp_nonce_field( 'rks_franchise_action' ); ?>
						<input type="hidden" name="action" value="rks_franchise_action">
						<input type="hidden" name="do" value="approve">
						<input type="hidden" name="franchise_id" value="<?php echo (int) $row['id']; ?>">
						<input type="hidden" name="return" value="view">
						<input type="hidden" name="return_page" value="<?php echo esc_attr( $list_page ); ?>">
						<button class="button button-primary" onclick="return confirm('Approve and onboard? This creates a branch + branch admin user, and emails credentials.');"><?php echo Icons::svg( 'check', 12 ); ?> Approve &amp; Onboard</button>
					</form>
					<button type="button" class="button button-link-delete" onclick="document.getElementById('rks-frej').style.display='block';">Reject</button>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="rks-frej" style="display:none;margin-top:12px;">
						<?php wp_nonce_field( 'rks_franchise_action' ); ?>
						<input type="hidden" name="action" value="rks_franchise_action">
						<input type="hidden" name="do" value="reject">
						<input type="hidden" name="franchise_id" value="<?php echo (int) $row['id']; ?>">
						<input type="hidden" name="return" value="view">
						<input type="hidden" name="return_page" value="<?php echo esc_attr( $list_page ); ?>">
						<textarea name="note" rows="3" style="width:100%;" placeholder="Reason (sent to applicant)"></textarea>
						<button class="button button-link-delete"><?php echo Icons::svg( 'x-circle', 12 ); ?> Confirm Reject</button>
					</form>
				</div>
			<?php elseif ( $row['status'] === 'pending' && 'inquiry' === $form_type ) : ?>
				<div class="rks-action-box" style="border-left:3px solid #2C687B;">
					<h3><?php echo Icons::svg( 'info', 14 ); ?> Inquiry Lead</h3>
					<p>This is a quick franchise enquiry, not the final onboarding form. Share the Franchise Register link with the applicant before approval.</p>
					<a class="button button-primary" href="<?php echo esc_url( home_url( '/franchise-register/' ) ); ?>" target="_blank" rel="noopener"><?php echo Icons::svg( 'link', 12 ); ?> Open Franchise Register</a>
				</div>
			<?php endif; ?>

			<?php if ( in_array( $row['status'], [ 'approved', 'onboarded' ], true ) && $admin_user ) : ?>
			<div class="rks-action-box" style="border-left:3px solid #2C687B;">
				<h3><?php echo Icons::svg( 'refresh', 14 ); ?> Owner Password</h3>
				<p>Regenerate the franchise owner's login password for <strong><?php echo esc_html( $admin_user->user_login ); ?></strong>. The new password will be shown below in a downloadable card. The old password stops working immediately.</p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block; margin: 8px 0;">
					<?php wp_nonce_field( 'rks_franchise_reset_password' ); ?>
					<input type="hidden" name="action" value="rks_franchise_reset_password">
					<input type="hidden" name="franchise_id" value="<?php echo (int) $row['id']; ?>">
					<button class="button" onclick="return confirm('Generate a new password for this franchise owner? The old password will stop working immediately.');">
						<?php echo Icons::svg( 'refresh', 12 ); ?> Reset &amp; Show Password
					</button>
				</form>
			</div>
			<?php endif; ?>

			<?php
			$card_data = get_transient( 'rks_franchise_card_' . (int) $row['id'] );
			if ( $card_data && is_array( $card_data ) ) :
			?>
			<div class="rks-admin-card rks-fcard-wrap">
				<h2 class="rks-card-title">
					<?php echo Icons::svg( 'idcard', 16 ); ?>
					Onboarding Credentials
				</h2>
				<p class="description">Below are the login credentials emailed to the franchise. Use the "Download" button to also send them via WhatsApp or hand-deliver. Credentials expire from this view 24 hours after approval.</p>

				<div class="rks-fcard" id="rks-fcard" data-brand="<?php echo esc_attr( (string) ( get_option( 'rks_settings' )['site_brand'] ?? 'Rishikul Shiksha' ) ); ?>">
					<div class="rks-fcard__head">
						<strong><?php echo esc_html( (string) ( get_option( 'rks_settings' )['site_brand'] ?? 'Rishikul Shiksha' ) ); ?></strong>
						<span>FRANCHISE ONBOARDING</span>
					</div>
					<h3>Welcome aboard, <?php echo esc_html( (string) $card_data['applicant_name'] ); ?>!</h3>
					<dl class="rks-fcard__meta">
						<dt>Branch</dt><dd><?php echo esc_html( (string) $card_data['branch_name'] ); ?></dd>
						<dt>City</dt><dd><?php echo esc_html( (string) $card_data['city'] ); ?></dd>
						<dt>Email</dt><dd><?php echo esc_html( (string) $card_data['email'] ); ?></dd>
						<dt>Username</dt><dd><code><?php echo esc_html( (string) $card_data['username'] ); ?></code></dd>
						<dt>Password</dt><dd><code><?php echo esc_html( (string) $card_data['password'] ); ?></code></dd>
						<dt>Login URL</dt><dd><?php echo esc_html( (string) $card_data['login_url'] ); ?></dd>
					</dl>
					<p class="rks-fcard__note">Please change your password after first login. Keep these credentials safe.</p>
				</div>

				<div class="rks-fcard__actions">
					<button type="button" class="button button-primary" id="rks-fcard-download">
						<?php echo Icons::svg( 'download', 12 ); ?> Download as Image
					</button>
					<button type="button" class="button" id="rks-fcard-copy">
						<?php echo Icons::svg( 'clipboard', 12 ); ?> Copy to Clipboard
					</button>
				</div>
			</div>

			<script>
			(function () {
				var btn  = document.getElementById('rks-fcard-download');
				var card = document.getElementById('rks-fcard');
				var copyBtn = document.getElementById('rks-fcard-copy');
				if (!btn || !card) return;

				btn.addEventListener('click', function () {
					var W = 480, scale = 2;
					var pad = 18, labelCol = 95, rowGap = 7, rowH = 18;
					var headerH = 52;
					var noteFontSize = 11, noteLineH = 14, notePadX = 12, notePadY = 10;

					var rows = [];
					card.querySelectorAll('.rks-fcard__meta dt').forEach(function (dt) {
						var dd = dt.nextElementSibling;
						if (dd) rows.push({ label: dt.textContent.trim(), value: dd.textContent.trim() });
					});

					var noteEl = card.querySelector('.rks-fcard__note');
					var note   = noteEl ? noteEl.textContent.trim() : '';
					var titleEl = card.querySelector('h3');
					var title  = titleEl ? titleEl.textContent.trim() : '';

					// Measurement pass
					var measureCv = document.createElement('canvas');
					var mctx = measureCv.getContext('2d');
					mctx.font = noteFontSize + 'px Poppins, Arial, sans-serif';
					var noteMaxW = W - pad * 2 - notePadX * 2;
					function wrapLines(text, maxW) {
						var words = text.split(' '), line = '', out = [];
						for (var i = 0; i < words.length; i++) {
							var test = line + words[i] + ' ';
							if (mctx.measureText(test).width > maxW && line.length) {
								out.push(line.trim()); line = words[i] + ' ';
							} else { line = test; }
						}
						if (line.trim()) out.push(line.trim());
						return out;
					}
					var noteLines = note ? wrapLines(note, noteMaxW) : [];
					var noteH     = note ? (noteLines.length * noteLineH + notePadY * 2) : 0;

					mctx.font = 'bold 13px Poppins, Arial, sans-serif';
					var valMaxW = W - pad - labelCol - pad;
					rows.forEach(function (r) { r.lines = wrapLines(r.value, valMaxW); });
					var rowsH = 0;
					rows.forEach(function (r) {
						var lineCount = Math.max(1, r.lines.length);
						r.height = lineCount * rowH + rowGap;
						rowsH += r.height;
					});

					var titleH = 30;
					var noteGap = note ? 14 : 0;
					var bottomPad = 20;
					var topAfterHdr = 18;
					var H = headerH + topAfterHdr + titleH + rowsH + noteGap + noteH + bottomPad;

					var cv = document.createElement('canvas');
					cv.width = W * scale; cv.height = H * scale;
					var ctx = cv.getContext('2d');
					ctx.scale(scale, scale);

					// Cream bg
					ctx.fillStyle = '#FBF3E9';
					ctx.fillRect(0, 0, W, H);
					ctx.fillStyle = '#ffffff';
					ctx.fillRect(0, headerH, W, H - headerH);

					// Teal header
					ctx.fillStyle = '#2C687B';
					ctx.fillRect(0, 0, W, headerH);
					ctx.fillStyle = '#8EC9C4';
					ctx.fillRect(0, headerH, W, 3);

					ctx.fillStyle = '#ffffff';
					ctx.textBaseline = 'middle';
					ctx.font = 'bold 16px Poppins, Arial, sans-serif';
					ctx.fillText(card.dataset.brand || 'Rishikul Shiksha', pad, headerH / 2 - 7);
					ctx.font = '10px Poppins, Arial, sans-serif';
					ctx.globalAlpha = 0.85;
					ctx.fillText('FRANCHISE ONBOARDING', pad, headerH / 2 + 10);
					ctx.globalAlpha = 1;
					ctx.textBaseline = 'alphabetic';

					// Title
					var titleY = headerH + topAfterHdr + 4;
					ctx.fillStyle = '#E63946';
					ctx.font = 'bold 14px Poppins, Arial, sans-serif';
					ctx.fillText(title, pad, titleY);
					ctx.strokeStyle = 'rgba(230, 57, 70, 0.3)';
					ctx.lineWidth = 1;
					ctx.setLineDash([3, 3]);
					ctx.beginPath();
					ctx.moveTo(pad, titleY + 8);
					ctx.lineTo(W - pad, titleY + 8);
					ctx.stroke();
					ctx.setLineDash([]);

					// Meta rows
					var y = headerH + topAfterHdr + titleH + 4;
					rows.forEach(function (r) {
						ctx.fillStyle = '#6b7280';
						ctx.font = '600 9.5px Poppins, Arial, sans-serif';
						ctx.fillText(r.label.toUpperCase(), pad, y);
						ctx.fillStyle = '#111827';
						ctx.font = 'bold 13px Poppins, Arial, sans-serif';
						var ly = y;
						r.lines.forEach(function (ln) {
							ctx.fillText(ln, pad + labelCol, ly);
							ly += rowH;
						});
						y += r.height;
					});

					if (note) {
						y += noteGap - 4;
						ctx.fillStyle = '#FBF3E9';
						ctx.fillRect(pad, y, W - pad * 2, noteH);
						ctx.fillStyle = '#E63946';
						ctx.fillRect(pad, y, 3, noteH);
						ctx.font = noteFontSize + 'px Poppins, Arial, sans-serif';
						var ny = y + notePadY + noteFontSize;
						noteLines.forEach(function (ln) {
							ctx.fillText(ln, pad + notePadX, ny);
							ny += noteLineH;
						});
					}

					var link = document.createElement('a');
					link.download = 'franchise-credentials-<?php echo (int) $row['id']; ?>.png';
					link.href = cv.toDataURL('image/png');
					link.click();
				});

				copyBtn.addEventListener('click', function () {
					var text = '';
					card.querySelectorAll('.rks-fcard__meta dt').forEach(function (dt) {
						var dd = dt.nextElementSibling;
						if (dd) text += dt.textContent.trim() + ': ' + dd.textContent.trim() + '\n';
					});
					if (navigator.clipboard) {
						navigator.clipboard.writeText(text).then(function () {
							copyBtn.textContent = 'Copied';
							setTimeout(function () { copyBtn.innerHTML = '<?php echo addslashes( Icons::svg( 'clipboard', 12 ) ); ?> Copy to Clipboard'; }, 2000);
						});
					}
				});
			})();
			</script>
			<?php endif; ?>

			<?php
			// Fallback: when the 24h transient is expired we still want admins to
			// see the franchise owner's username/email/login URL. WordPress stores
			// passwords as hashes — we can't display the old one. The reset button
			// (rendered elsewhere on this page) is the only way to obtain a fresh
			// password.
			if ( ! ( $card_data && is_array( $card_data ) ) ) :
				$owner_branch_row = $wpdb->get_row( $wpdb->prepare(
					'SELECT id, name, owner_user_id FROM ' . DB::table( 'branches' ) . ' WHERE franchise_id = %d ORDER BY id ASC LIMIT 1',
					(int) $row['id']
				), ARRAY_A );

				$owner_user = ( $owner_branch_row && (int) $owner_branch_row['owner_user_id'] > 0 )
					? get_userdata( (int) $owner_branch_row['owner_user_id'] )
					: null;

				if ( $owner_user ) :
			?>
			<div class="rks-admin-card">
				<div class="rks-section-head" style="margin-top:0;">
					<?php echo Icons::chip( 'idcard', 'teal' ); ?>
					<h2>Franchise Owner Account</h2>
				</div>
				<p class="description">Read-only owner credentials. The plain password is no longer cached (it was only kept for 24 hours after approval). Use the <strong>"Reset &amp; Show Password"</strong> button above to generate a fresh password.</p>
				<dl class="rks-meta-list">
					<li><dt>Branch</dt><dd><?php echo esc_html( (string) $owner_branch_row['name'] ); ?></dd></li>
					<li><dt>Username</dt><dd><code><?php echo esc_html( $owner_user->user_login ); ?></code></dd></li>
					<li><dt>Email</dt><dd><a href="mailto:<?php echo esc_attr( $owner_user->user_email ); ?>"><?php echo esc_html( $owner_user->user_email ); ?></a></dd></li>
					<li><dt>Login URL</dt><dd><code><?php echo esc_url( home_url( '/login/' ) ); ?></code></dd></li>
					<li><dt>Password</dt><dd><em>Hashed in database — cannot be displayed.</em><br><strong>Use the "Reset &amp; Show Password" button above to generate a new password.</strong></dd></li>
				</dl>
			</div>
			<?php
				endif;
			endif;
			?>

			<div class="rks-detail-grid">

				<!-- LEFT: main content -->
				<div>

					<div class="rks-admin-card">
						<div class="rks-section-head" style="margin-top:0;">
							<?php echo Icons::chip( 'user', 'teal' ); ?>
							<h2>Applicant Details</h2>
						</div>
						<dl class="rks-meta-list">
							<li><dt>Form Type</dt><dd><span class="rks-tag rks-tag--main"><?php echo esc_html( $form_label ); ?></span></dd></li>
							<li><dt>Full Name</dt><dd><?php echo esc_html( (string) $row['applicant_name'] ); ?></dd></li>
							<li><dt>Email</dt><dd><a href="mailto:<?php echo esc_attr( (string) $row['email'] ); ?>"><?php echo esc_html( (string) $row['email'] ); ?></a></dd></li>
							<li><dt>Phone</dt><dd><a href="tel:<?php echo esc_attr( (string) $row['phone'] ); ?>"><?php echo esc_html( (string) $row['phone'] ); ?></a></dd></li>
							<li><dt>City</dt><dd><?php echo esc_html( (string) $row['city'] ); ?></dd></li>
							<?php if ( ! empty( $row['territory'] ) ) : ?>
								<li><dt>Territory</dt><dd><?php echo esc_html( (string) $row['territory'] ); ?></dd></li>
							<?php endif; ?>
							<?php if ( ! empty( $row['franchise_fee'] ) ) : ?>
								<li><dt>Franchise Fee</dt><dd><?php echo esc_html( $currency . number_format( (float) $row['franchise_fee'], 2 ) ); ?></dd></li>
							<?php endif; ?>
						</dl>
					</div>

					<?php if ( 'onboarding' === $form_type ) : ?>
					<div class="rks-admin-card">
						<div class="rks-section-head" style="margin-top:0;">
							<?php echo Icons::chip( 'building', 'mint' ); ?>
							<h2>Onboarding Details</h2>
						</div>
						<dl class="rks-meta-list">
							<li><dt>Centre Name</dt><dd><?php echo esc_html( (string) ( $meta['branch_name'] ?? '' ) ); ?></dd></li>
							<li><dt>Aadhaar</dt><dd><?php echo esc_html( $this->mask_aadhar( (string) ( $meta['aadhar_number'] ?? '' ) ) ); ?></dd></li>
							<li><dt>Address</dt><dd><?php echo esc_html( (string) ( $location['address_line1'] ?? '' ) ); ?></dd></li>
							<?php if ( ! empty( $location['landmark'] ) ) : ?>
								<li><dt>Landmark</dt><dd><?php echo esc_html( (string) $location['landmark'] ); ?></dd></li>
							<?php endif; ?>
							<li><dt>District / State</dt><dd><?php echo esc_html( trim( (string) ( $location['district'] ?? '' ) . ', ' . (string) ( $location['state'] ?? '' ), ', ' ) ); ?></dd></li>
							<li><dt>Pincode</dt><dd><?php echo esc_html( (string) ( $location['pincode'] ?? '' ) ); ?></dd></li>
							<?php if ( ! empty( $location['map_link'] ) ) : ?>
								<li><dt>Map</dt><dd><a href="<?php echo esc_url( (string) $location['map_link'] ); ?>" target="_blank" rel="noopener">Open location</a></dd></li>
							<?php endif; ?>
							<li><dt>Premises</dt><dd><?php echo esc_html( ucfirst( (string) ( $premises['type'] ?? '' ) ) ); ?><?php echo ! empty( $premises['area_sqft'] ) ? ' - ' . (int) $premises['area_sqft'] . ' sq ft' : ''; ?></dd></li>
							<?php if ( ! empty( $row['budget'] ) ) : ?>
								<li><dt>Budget</dt><dd><?php echo esc_html( (string) $row['budget'] ); ?></dd></li>
							<?php endif; ?>
							<?php if ( ! empty( $premises['experience'] ) ) : ?>
								<li><dt>Experience</dt><dd><?php echo esc_html( (string) $premises['experience'] ); ?></dd></li>
							<?php endif; ?>
						</dl>
					</div>

					<div class="rks-admin-card">
						<div class="rks-section-head" style="margin-top:0;">
							<?php echo Icons::chip( 'file-text', 'gold' ); ?>
							<h2>Documents &amp; Agreement</h2>
						</div>
						<dl class="rks-meta-list">
							<li><dt>Aadhaar Card</dt><dd>
								<?php
								$aadhar_doc = is_array( $documents['aadhar_card'] ?? null ) ? $documents['aadhar_card'] : [];
								if ( ! empty( $aadhar_doc['path'] ) ) :
									$doc_url = wp_nonce_url(
										add_query_arg(
											[
												'action'       => 'rks_franchise_document',
												'franchise_id' => (int) $row['id'],
												'doc'          => 'aadhar_card',
											],
											admin_url( 'admin-post.php' )
										),
										'rks_franchise_document_' . (int) $row['id'] . '_aadhar_card'
									);
								?>
									<a class="button button-small" href="<?php echo esc_url( $doc_url ); ?>"><?php echo Icons::svg( 'download', 12 ); ?> Download Aadhaar</a>
									<small style="display:block;color:#6b7a82;margin-top:6px;"><?php echo esc_html( (string) ( $aadhar_doc['original_name'] ?? 'Aadhaar document' ) ); ?></small>
								<?php else : ?>
									<em>No document uploaded.</em>
								<?php endif; ?>
							</dd></li>
							<li><dt>Agreement PDF</dt><dd>
								<a class="button button-small button-primary" href="<?php echo esc_url( Franchises::agreement_print_url( (int) $row['id'] ) ); ?>" target="_blank" rel="noopener">
									<?php echo Icons::svg( 'download', 12 ); ?> Download / Print Agreement
								</a>
							</dd></li>
							<li><dt>Accepted By</dt><dd><?php echo esc_html( (string) ( $agreement['name'] ?? '' ) ); ?></dd></li>
							<li><dt>Place</dt><dd><?php echo esc_html( (string) ( $agreement['place'] ?? '' ) ); ?></dd></li>
							<li><dt>Accepted At</dt><dd><?php echo esc_html( (string) ( $agreement['accepted_at'] ?? '' ) ); ?></dd></li>
							<?php if ( ! empty( $agreement['ip'] ) ) : ?>
								<li><dt>IP</dt><dd><code><?php echo esc_html( (string) $agreement['ip'] ); ?></code></dd></li>
							<?php endif; ?>
						</dl>
					</div>
					<?php endif; ?>

					<?php if ( ! empty( $row['message'] ) ) : ?>
					<div class="rks-admin-card">
						<div class="rks-section-head" style="margin-top:0;">
							<?php echo Icons::chip( 'message', 'gold' ); ?>
							<h2>Applicant's Message</h2>
						</div>
						<p style="white-space:pre-line;color:#4a5763;line-height:1.6;"><?php echo esc_html( (string) $row['message'] ); ?></p>
					</div>
					<?php endif; ?>

					<?php if ( $branch_id && $branch ) : ?>
					<div class="rks-admin-card">
						<div class="rks-section-head" style="margin-top:0;">
							<?php echo Icons::chip( 'building', 'mint' ); ?>
							<h2>Linked Branch &amp; Admin</h2>
						</div>
						<dl class="rks-meta-list">
							<li><dt>Branch ID</dt><dd>#<?php echo (int) $branch['id']; ?></dd></li>
							<li><dt>Branch Name</dt><dd><strong><?php echo esc_html( (string) $branch['name'] ); ?></strong></dd></li>
							<li><dt>City</dt><dd><?php echo esc_html( (string) $branch['city'] ); ?></dd></li>
							<li><dt>Type</dt><dd><span class="rks-tag rks-tag--<?php echo esc_attr( (string) $branch['type'] ); ?>"><?php echo esc_html( (string) $branch['type'] ); ?></span></dd></li>
							<li><dt>Branch Admin</dt><dd>
								<?php if ( $admin_user ) : ?>
									<strong><?php echo esc_html( $admin_user->display_name ?: $admin_user->user_login ); ?></strong>
									<small style="display:block;color:#6b7a82;"><?php echo esc_html( $admin_user->user_email ); ?> · login: <code><?php echo esc_html( $admin_user->user_login ); ?></code></small>
								<?php else : ?>
									<em>Not assigned</em>
								<?php endif; ?>
							</dd></li>
						</dl>
					</div>

					<div class="rks-admin-card">
						<div class="rks-section-head" style="margin-top:0;">
							<?php echo Icons::chip( 'wallet', 'red' ); ?>
							<h2>Recent Payments</h2>
							<small>· this branch · last 10</small>
						</div>
						<?php if ( empty( $recent_payments ) ) : ?>
							<em>No payments recorded yet.</em>
						<?php else : ?>
							<table class="rks-summary-table">
								<thead><tr><th>ID</th><th>Amount</th><th>Method</th><th>UTR</th><th>Status</th><th>Date</th></tr></thead>
								<tbody>
								<?php foreach ( $recent_payments as $p ) : ?>
									<tr>
										<td>#<?php echo (int) $p['id']; ?></td>
										<td><strong><?php echo esc_html( $currency . number_format( (float) $p['amount'], 0 ) ); ?></strong></td>
										<td><span class="rks-tag rks-tag--main"><?php echo esc_html( strtoupper( (string) $p['method'] ) ); ?></span></td>
										<td><code><?php echo esc_html( (string) ( $p['utr'] ?: '—' ) ); ?></code></td>
										<td><span class="rks-pill rks-pill--<?php echo $p['status'] === 'approved' ? 'success' : ( $p['status'] === 'rejected' ? 'danger' : 'warn' ); ?>"><?php echo esc_html( (string) $p['status'] ); ?></span></td>
										<td><small><?php echo esc_html( (string) ( $p['verified_at'] ?: $p['created_at'] ) ); ?></small></td>
									</tr>
								<?php endforeach; ?>
								</tbody>
							</table>
						<?php endif; ?>
					</div>

					<div class="rks-admin-card">
						<div class="rks-section-head" style="margin-top:0;">
							<?php echo Icons::chip( 'inbox', 'gold' ); ?>
							<h2>Recent Admissions</h2>
							<small>· this branch · last 10</small>
						</div>
						<?php if ( empty( $recent_admissions ) ) : ?>
							<em>No admissions recorded yet.</em>
						<?php else : ?>
							<table class="rks-summary-table">
								<thead><tr><th>ID</th><th>Student</th><th>Course</th><th>Fee</th><th>Status</th><th>Date</th></tr></thead>
								<tbody>
								<?php foreach ( $recent_admissions as $a ) : ?>
									<tr>
										<td>#<?php echo (int) $a['id']; ?></td>
										<td><strong><?php echo esc_html( (string) $a['full_name'] ); ?></strong></td>
										<td><small><?php echo esc_html( (string) $a['course_title'] ); ?></small></td>
										<td><?php echo esc_html( $currency . number_format( (float) $a['fees_amount'], 0 ) ); ?></td>
										<td><span class="rks-pill rks-pill--<?php echo $a['status'] === 'approved' ? 'success' : ( $a['status'] === 'rejected' ? 'danger' : 'warn' ); ?>"><?php echo esc_html( str_replace( '_', ' ', (string) $a['status'] ) ); ?></span></td>
										<td><small><?php echo esc_html( (string) $a['created_at'] ); ?></small></td>
									</tr>
								<?php endforeach; ?>
								</tbody>
							</table>
						<?php endif; ?>
					</div>
					<?php endif; /* branch_id check */ ?>

					<?php if ( $row['status'] === 'rejected' && ! empty( $row['rejection_note'] ) ) : ?>
						<div class="rks-admin-card rks-admin-card--alert">
							<div class="rks-section-head" style="margin-top:0;">
								<?php echo Icons::chip( 'x-circle', 'red' ); ?>
								<h2>Rejection Note</h2>
							</div>
							<p style="color:#c92c39;font-style:italic;"><?php echo esc_html( (string) $row['rejection_note'] ); ?></p>
						</div>
					<?php endif; ?>

				</div>

				<!-- RIGHT: sidebar -->
				<div>

					<?php if ( $branch_id ) : ?>
					<div class="rks-admin-card">
						<div class="rks-section-head" style="margin-top:0;">
							<?php echo Icons::chip( 'chart-line', 'red' ); ?>
							<h2>Performance</h2>
						</div>
						<ul class="rks-quick-stats">
							<li><small>Students</small><strong><?php echo number_format( (int) $stats['students'] ); ?></strong></li>
							<li><small>Admissions</small><strong><?php echo number_format( (int) $stats['admissions'] ); ?></strong></li>
							<li><small>Approved</small><strong><?php echo number_format( (int) $stats['approved_adm'] ); ?></strong></li>
							<li><small>Certificates</small><strong><?php echo number_format( (int) $stats['certs'] ); ?></strong></li>
						</ul>
						<dl class="rks-meta-list">
							<li><dt>Total Revenue</dt><dd><strong style="color:#c92c39;font-size:18px;"><?php echo esc_html( $currency . number_format( $stats['revenue_total'], 0 ) ); ?></strong></dd></li>
							<li><dt>Last 30 days</dt><dd><strong><?php echo esc_html( $currency . number_format( $stats['revenue_30d'], 0 ) ); ?></strong></dd></li>
						</dl>
					</div>
					<?php endif; ?>

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
							<?php if ( ! empty( $row['reviewed_at'] ) ) : ?>
							<li class="<?php echo $row['status'] === 'rejected' ? 'is-error' : 'is-done'; ?>">
								<span class="rks-timeline__dot"><?php echo Icons::svg( $row['status'] === 'rejected' ? 'x' : 'check', 12 ); ?></span>
								<strong><?php echo $row['status'] === 'rejected' ? 'Rejected' : 'Approved'; ?></strong>
								<small><?php echo esc_html( (string) $row['reviewed_at'] ); ?> · by <?php echo esc_html( $reviewer ? ( $reviewer->display_name ?: $reviewer->user_login ) : 'system' ); ?></small>
							</li>
							<?php elseif ( $row['status'] === 'pending' ) : ?>
							<li class="is-pending">
								<span class="rks-timeline__dot"><?php echo Icons::svg( 'clock', 12 ); ?></span>
								<strong>Awaiting Review</strong>
								<small>Action needed</small>
							</li>
							<?php endif; ?>
							<?php if ( $row['status'] === 'onboarded' ) : ?>
							<li class="is-done">
								<span class="rks-timeline__dot"><?php echo Icons::svg( 'check', 12 ); ?></span>
								<strong>Onboarded</strong>
								<small>Branch #<?php echo (int) $branch_id; ?> created · admin user provisioned</small>
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
							<li><dt>App ID</dt><dd>#<?php echo (int) $row['id']; ?></dd></li>
							<li><dt>Created</dt><dd><?php echo esc_html( (string) $row['created_at'] ); ?></dd></li>
							<li><dt>Updated</dt><dd><?php echo esc_html( (string) $row['updated_at'] ); ?></dd></li>
							<?php if ( ! empty( $row['agreement_date'] ) ) : ?>
								<li><dt>Agreement</dt><dd><?php echo esc_html( (string) $row['agreement_date'] ); ?></dd></li>
							<?php endif; ?>
						</dl>
					</div>

				</div>

			</div><!-- /detail-grid -->

			<div class="rks-admin-card" style="border-left:4px solid #E63946; margin-top:24px;">
				<h2 style="color:#E63946; margin-top:0;"><?php echo Icons::svg( 'trash', 16 ); ?> Danger Zone</h2>
				<p>Permanently delete this franchise application record. This does NOT remove the linked branch or user — those must be removed separately from <em>Branches</em> and <em>Users</em>.</p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'rks_franchise_delete' ); ?>
					<input type="hidden" name="action" value="rks_franchise_delete">
					<input type="hidden" name="franchise_id" value="<?php echo (int) $row['id']; ?>">
					<input type="hidden" name="return_page" value="<?php echo esc_attr( $list_page ); ?>">
					<button type="submit" class="button button-link-delete" onclick="return confirm('Permanently delete this franchise application? This cannot be undone.');">
						<?php echo Icons::svg( 'trash', 12 ); ?> Delete Franchise
					</button>
				</form>
			</div>

		</div>
		<?php
	}

	private function mask_aadhar( string $value ): string {
		$digits = preg_replace( '/\D+/', '', $value );
		if ( ! is_string( $digits ) || strlen( $digits ) < 4 ) {
			return '';
		}
		return 'XXXX XXXX ' . substr( $digits, -4 );
	}
}
