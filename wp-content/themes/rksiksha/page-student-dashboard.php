<?php
/**
 * Template Name: Student Dashboard
 */
if ( ! defined( 'ABSPATH' ) ) exit;

use RKSiksha\Core\Modules\Courses;
use RKSiksha\Core\Modules\Lms;
use RKSiksha\Core\Modules\StudentDashboard;

if ( ! is_user_logged_in() ) {
	wp_safe_redirect( home_url( '/login/' ) );
	exit;
}

get_header();

$user      = wp_get_current_user();
$student   = new StudentDashboard();
$lms       = new Lms();

$view = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( (string) $_GET['view'] ) ) : 'overview';
if ( ! in_array( $view, StudentDashboard::VIEWS, true ) ) $view = 'overview';

$exam_mode = ( $view === 'quiz-take' );

$msg = isset( $_GET['rks_msg'] ) ? sanitize_key( wp_unslash( (string) $_GET['rks_msg'] ) ) : '';

$settings = (array) get_option( 'rks_settings', [] );
$currency = (string) ( $settings['currency_symbol'] ?? '₹' );
?>

<section class="rks-dash <?php echo $exam_mode ? 'rks-dash--exam' : ''; ?>">
	<div class="rks-container rks-dash__shell <?php echo $exam_mode ? 'rks-dash__shell--exam' : ''; ?>">

		<?php if ( ! $exam_mode ) : ?>
		<aside class="rks-dash__side">
			<?php
			$display_name = $user->display_name ?: $user->user_login;
			$avatar_letter = mb_strtoupper( mb_substr( $display_name, 0, 1 ) );
			?>
			<div class="rks-dash__brand">
				<span class="rks-dash__brand-avatar" aria-hidden="true"><?php echo esc_html( $avatar_letter ); ?></span>
				<span class="rks-dash__brand-text">
					<small>Student</small>
					<strong><?php echo esc_html( $display_name ); ?></strong>
				</span>
			</div>

			<nav class="rks-dash__nav">
				<?php
				$tabs = [
					'overview'     => [ 'Overview',      'grid' ],
					'courses'      => [ 'My Courses',    'book' ],
					'quizzes'      => [ 'Exams',         'file-text' ],
					'certificates' => [ 'Certificates',  'trophy' ],
					'idcard'       => [ 'My ID Card',    'idcard' ],
					'payments'     => [ 'Payments',      'wallet' ],
					'profile'      => [ 'Profile',       'cog' ],
				];
				foreach ( $tabs as $key => [ $label, $icon ] ) :
					$url = add_query_arg( 'view', $key, home_url( '/student-dashboard/' ) );
				?>
					<a href="<?php echo esc_url( $url ); ?>" class="rks-dash__nav-item <?php echo $view === $key ? 'is-active' : ''; ?>">
						<span class="rks-dash__nav-icon"><?php echo \RKSiksha\Core\Support\Icons::svg( $icon, 18 ); ?></span>
						<?php echo esc_html( $label ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<div class="rks-dash__side-foot">
				<a href="<?php echo esc_url( home_url( '/courses/' ) ); ?>" class="rks-dash__side-link">Browse More Courses →</a>
				<a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>" class="rks-dash__side-link">Logout</a>
			</div>
		</aside>
		<?php endif; ?>

		<main class="rks-dash__main">
			<?php if ( ! $exam_mode ) : ?>
			<button type="button" class="rks-dash__menu-toggle" aria-label="Open menu" aria-controls="rks-dash-side" aria-expanded="false">
				<?php echo \RKSiksha\Core\Support\Icons::svg( 'menu', 22 ); ?>
				<span class="rks-dash__menu-toggle-label">Menu</span>
			</button>
			<?php endif; ?>

			<?php if ( $msg ) : ?>
				<?php
				$msg_text = [
					'completed'          => 'Lesson marked complete. Keep going!',
					'saved'              => 'Profile updated.',
					'password_changed'   => 'Password updated successfully.',
					'wrong_current'      => 'Current password is incorrect.',
					'weak_password'      => 'New password must be at least 8 characters.',
					'mismatch'          => 'Passwords do not match.',
					'monthly_submitted'  => 'Payment submitted! Awaiting admin verification.',
				];
				$type = in_array( $msg, [ 'completed', 'saved', 'password_changed', 'monthly_submitted' ], true ) ? 'success' : 'error';
				?>
				<div class="rks-dash__alert rks-dash__alert--<?php echo esc_attr( $type ); ?>">
					<?php echo esc_html( $msg_text[ $msg ] ?? '' ); ?>
				</div>
			<?php endif; ?>

			<?php
			switch ( $view ) {
				case 'overview':     include __DIR__ . '/templates/student-overview.php';     break;
				case 'courses':      include __DIR__ . '/templates/student-courses.php';      break;
				case 'lesson':       include __DIR__ . '/templates/student-lesson.php';       break;
				case 'quizzes':      include __DIR__ . '/templates/student-quizzes.php';      break;
				case 'quiz-take':    include __DIR__ . '/templates/student-quiz-take.php';    break;
				case 'quiz-result':  include __DIR__ . '/templates/student-quiz-result.php';  break;
				case 'certificates': include __DIR__ . '/templates/student-certificates.php'; break;
				case 'idcard':       include __DIR__ . '/templates/student-idcard.php';       break;
				case 'payments':     include __DIR__ . '/templates/student-payments.php';     break;
				case 'profile':      include __DIR__ . '/templates/student-profile.php';      break;
				case 'pay-monthly':  include __DIR__ . '/templates/student-pay-monthly.php';  break;
			}
			?>
		</main>

		<?php if ( ! $exam_mode ) : ?>
		<div class="rks-dash__overlay" aria-hidden="true"></div>
		<?php endif; ?>
	</div>
</section>

<script>
(function () {
	var shell   = document.querySelector('.rks-dash__shell');
	if ( ! shell ) return;
	var toggle  = shell.querySelector('.rks-dash__menu-toggle');
	var overlay = shell.querySelector('.rks-dash__overlay');
	var navItems = shell.querySelectorAll('.rks-dash__nav-item');

	function open() {
		shell.classList.add('is-open');
		if ( toggle ) toggle.setAttribute('aria-expanded', 'true');
		document.body.classList.add('rks-dash-locked');
	}
	function close() {
		shell.classList.remove('is-open');
		if ( toggle ) toggle.setAttribute('aria-expanded', 'false');
		document.body.classList.remove('rks-dash-locked');
	}

	if ( toggle ) {
		toggle.addEventListener('click', function () {
			if ( shell.classList.contains('is-open') ) close(); else open();
		});
	}
	if ( overlay ) overlay.addEventListener('click', close);
	navItems.forEach(function (a) { a.addEventListener('click', close); });

	window.addEventListener('resize', function () {
		if ( window.innerWidth > 1024 ) close();
	});

	document.addEventListener('keydown', function (e) {
		if ( e.key === 'Escape' && shell.classList.contains('is-open') ) close();
	});
})();
</script>

<?php get_footer(); ?>
