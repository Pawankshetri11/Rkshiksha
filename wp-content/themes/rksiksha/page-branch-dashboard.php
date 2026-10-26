<?php
/**
 * Template Name: Branch Dashboard
 */
if ( ! defined( 'ABSPATH' ) ) exit;

use RKSiksha\Core\Modules\Admissions;
use RKSiksha\Core\Modules\Branches;
use RKSiksha\Core\Modules\Dashboard;
use RKSiksha\Core\Modules\Payments;

if ( ! is_user_logged_in() || ! current_user_can( 'rks_view_branch_dashboard' ) ) {
	wp_safe_redirect( home_url( '/login/' ) );
	exit;
}

get_header();

$dash      = new Dashboard();
$user      = wp_get_current_user();
$branch_id = $dash->user_branch_id( $user );
$branch    = $branch_id ? ( new Branches() )->get( $branch_id ) : null;
$is_super  = $dash->is_super( $user );

$view = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( (string) $_GET['view'] ) ) : 'overview';
if ( ! in_array( $view, Dashboard::VIEWS, true ) ) $view = 'overview';

$msg = isset( $_GET['rks_msg'] ) ? sanitize_key( wp_unslash( (string) $_GET['rks_msg'] ) ) : '';

$settings = (array) get_option( 'rks_settings', [] );
$currency = (string) ( $settings['currency_symbol'] ?? '₹' );
?>

<section class="rks-dash">
	<div class="rks-container rks-dash__shell">

		<!-- Sidebar -->
		<aside class="rks-dash__side">
			<div class="rks-dash__brand">
				<small>Branch Admin</small>
				<strong><?php echo esc_html( $branch ? $branch['name'] : 'Branch' ); ?></strong>
				<?php if ( $branch ) : ?>
					<span class="rks-tag rks-tag--<?php echo esc_attr( (string) $branch['type'] ); ?>"><?php echo esc_html( (string) $branch['type'] ); ?></span>
				<?php endif; ?>
			</div>

			<nav class="rks-dash__nav">
				<?php
				$tabs = [
					'overview'   => [ 'Overview',           'grid' ],
					'admissions' => [ 'Admissions',         'inbox' ],
					'payments'   => [ 'Payments',           'wallet' ],
					'students'   => [ 'Students',           'users' ],
					'branch'     => [ 'Branch Profile',     'building' ],
				];
				foreach ( $tabs as $key => [ $label, $icon ] ) :
					$active = $view === $key;
					$url    = add_query_arg( 'view', $key, home_url( '/branch-dashboard/' ) );
				?>
					<a href="<?php echo esc_url( $url ); ?>" class="rks-dash__nav-item <?php echo $active ? 'is-active' : ''; ?>">
						<span class="rks-dash__nav-icon"><?php echo \RKSiksha\Core\Support\Icons::svg( $icon, 18 ); ?></span>
						<?php echo esc_html( $label ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<div class="rks-dash__side-foot">
				<small>Logged in as</small>
				<strong><?php echo esc_html( $user->display_name ?: $user->user_login ); ?></strong>
				<?php if ( $is_super ) : ?>
					<a href="<?php echo esc_url( admin_url() ); ?>" class="rks-dash__side-link">WP Admin →</a>
				<?php endif; ?>
				<a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>" class="rks-dash__side-link">Logout</a>
			</div>
		</aside>

		<!-- Main content -->
		<main class="rks-dash__main">

			<?php if ( $msg ) : ?>
				<div class="rks-dash__alert rks-dash__alert--<?php echo in_array( $msg, [ 'approved', 'rejected', 'saved' ], true ) ? 'success' : 'error'; ?>">
					<?php
					$msg_text = [
						'approved'     => 'Admission approved. Student account created and welcome email sent.',
						'rejected'     => 'Admission rejected. Student notified by email.',
						'saved'        => 'Branch profile updated.',
						'error'        => 'Action failed. Please try again.',
						'not_found'    => 'Record not found.',
						'wrong_branch' => 'You can only act on admissions in your own branch.',
						'forbidden'    => 'Permission denied.',
					][ $msg ] ?? '';
					echo esc_html( $msg_text );
					?>
				</div>
			<?php endif; ?>

			<?php
			switch ( $view ) {
				case 'overview':   include __DIR__ . '/templates/dashboard-overview.php';   break;
				case 'admissions': include __DIR__ . '/templates/dashboard-admissions.php'; break;
				case 'payments':   include __DIR__ . '/templates/dashboard-payments.php';   break;
				case 'students':   include __DIR__ . '/templates/dashboard-students.php';   break;
					case 'branch':     include __DIR__ . '/templates/dashboard-branch.php';     break;
			}
			?>
		</main>

	</div>
</section>

<?php get_footer(); ?>
