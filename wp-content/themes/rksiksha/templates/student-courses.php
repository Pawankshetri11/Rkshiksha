<?php
/** @var \WP_User $user */
/** @var \RKSiksha\Core\Modules\Lms $lms */
if ( ! defined( 'ABSPATH' ) ) exit;

use RKSiksha\Core\Modules\Courses;
use RKSiksha\Core\Modules\Subscriptions;
use RKSiksha\Core\Support\Icons;

$enrollments = $lms->user_enrollments( $user->ID );
$subs        = new Subscriptions();
$settings    = (array) get_option( 'rks_settings', [] );
$currency    = ! empty( $settings['currency_symbol'] ) ? (string) $settings['currency_symbol'] : '₹';
$upi_id      = (string) ( $settings['upi_id'] ?? '' );
$qr_id       = (int)    ( $settings['upi_qr_image_id'] ?? 0 );
$qr_url      = $qr_id ? wp_get_attachment_image_url( $qr_id, 'medium' ) : '';

$flash_msg = isset( $_GET['rks_msg'] ) ? sanitize_key( wp_unslash( (string) $_GET['rks_msg'] ) ) : '';
$flash_err = isset( $_GET['rks_err'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['rks_err'] ) ) : '';
$open_pay  = isset( $_GET['pay_course'] ) ? (int) $_GET['pay_course'] : 0;
?>

<header class="rks-dash__header">
	<div>
		<h1>My Courses</h1>
		<p>All courses you've enrolled in.</p>
	</div>
</header>

<?php if ( 'monthly_submitted' === $flash_msg ) : ?>
	<div class="rks-flash rks-flash--success">
		<?php echo Icons::svg( 'check', 18 ); ?>
		Your monthly payment has been submitted and is awaiting admin verification.
	</div>
<?php elseif ( 'enrolled' === $flash_msg ) : ?>
	<div class="rks-flash rks-flash--success">
		<?php echo Icons::svg( 'check', 18 ); ?>
		Enrolled successfully.
	</div>
<?php endif; ?>

<?php if ( $flash_err ) : ?>
	<div class="rks-flash rks-flash--error">
		<?php echo Icons::svg( 'alert', 18 ); ?>
		<?php echo esc_html( $flash_err ); ?>
	</div>
<?php endif; ?>

<div class="rks-dash__panel">
	<?php if ( empty( $enrollments ) ) : ?>
		<div class="rks-dash__empty">
			<p>No enrollments yet.</p>
			<a href="<?php echo esc_url( home_url( '/courses/' ) ); ?>" class="rks-btn rks-btn--primary">Browse Courses</a>
		</div>
	<?php else : ?>
		<div class="rks-stu-grid">
			<?php foreach ( $enrollments as $e ) :
				$cid       = (int) $e['course_id'];
				$course    = get_post( $cid );
				if ( ! $course ) continue;
				$progress       = (int) $e['progress'];
				// Allow only known-good states; lock everything else (pending, rejected, NULL, empty, unexpected).
				$is_locked      = ! in_array( (string) ( $e['status'] ?? '' ), [ 'active', 'completed' ], true );
				$lessons        = Courses::get_lessons( $cid );
				$completed_ids  = $lms->completed_lesson_ids( $user->ID, $cid );
				$next_lesson_id = 0;
				foreach ( $lessons as $l ) {
					if ( ! in_array( (int) $l->ID, $completed_ids, true ) ) { $next_lesson_id = (int) $l->ID; break; }
				}
				$resume_args = [ 'view' => 'lesson', 'course_id' => $cid ];
				if ( $next_lesson_id ) $resume_args['lesson_id'] = $next_lesson_id;
				$resume_url = add_query_arg( $resume_args, home_url( '/student-dashboard/' ) );

				// Subscription state for this course
				$sub        = $subs->get_for_user( $user->ID, $cid );
				$pay_due    = $sub ? $subs->is_payment_due( $sub ) : false;
				$can_pay    = $sub ? $subs->can_pay_now( $sub ) : false;
				$pay_pending= ( $sub && $subs->has_pending_payment( (int) $sub['id'] ) );
				$sub_done   = ( $sub && $sub['status'] === Subscriptions::STATUS_COMPLETED );
				$form_open  = ( $open_pay === $cid && $can_pay && ! $pay_pending );
				$pay_early  = ( $can_pay && ! $pay_due && ! $pay_pending );

				// Lesson access is also blocked while a payment is overdue
				$financial_lock = $pay_due && ! $is_locked;
			?>
				<article class="rks-stu-card<?php echo $is_locked ? ' rks-stu-card--locked' : ''; ?><?php echo $financial_lock ? ' rks-stu-card--due' : ''; ?>">
					<?php if ( $sub && ! $is_locked ) : ?>
						<?php if ( $pay_due ) : ?>
							<span class="rks-stu-card__badge rks-stu-card__badge--due"><?php echo Icons::svg( 'alert', 12 ); ?> Payment Due</span>
						<?php elseif ( $pay_pending ) : ?>
							<span class="rks-stu-card__badge rks-stu-card__badge--pending"><?php echo Icons::svg( 'clock', 12 ); ?> Pending Review</span>
						<?php elseif ( $sub_done ) : ?>
							<span class="rks-stu-card__badge rks-stu-card__badge--paid"><?php echo Icons::svg( 'check', 12 ); ?> Fully Paid</span>
						<?php endif; ?>
					<?php endif; ?>

					<div class="rks-stu-card__progress">
						<div class="rks-stu-card__bar" style="width: <?php echo (int) $progress; ?>%;"></div>
						<span><?php echo (int) $progress; ?>%</span>
					</div>
					<h3>
						<?php if ( $is_locked || $financial_lock ) : ?>
							<?php echo esc_html( $course->post_title ); ?>
						<?php else : ?>
							<a href="<?php echo esc_url( $resume_url ); ?>"><?php echo esc_html( $course->post_title ); ?></a>
						<?php endif; ?>
					</h3>
					<?php if ( $is_locked ) : ?>
						<div class="rks-stu-card__lock-status">
							<?php echo Icons::svg( 'clock', 12 ); ?> Awaiting Approval
						</div>
						<p class="rks-stu-card__lock-meta"><?php echo (int) $e['lessons_total']; ?> lessons &middot; unlocks after admin approves</p>
					<?php else : ?>
						<p>
							<?php echo (int) $e['lessons_done']; ?> / <?php echo (int) $e['lessons_total']; ?> lessons
							&middot; <span class="rks-status rks-status--<?php echo esc_attr( $e['status'] ); ?>">
								<?php echo esc_html( $e['status'] ); ?>
							</span>
						</p>
					<?php endif; ?>

					<?php if ( $sub && ! $sub_done ) : ?>
						<p class="rks-stu-card__sub-info">
							<?php echo Icons::svg( 'calendar', 14 ); ?>
							<?php echo (int) $sub['months_paid']; ?> / <?php echo (int) $sub['total_months']; ?> months paid
							<?php if ( ! empty( $sub['next_due_date'] ) && ! $sub_done ) : ?>
								&middot; Next due: <strong><?php echo esc_html( mysql2date( get_option( 'date_format', 'F j, Y' ), $sub['next_due_date'] ) ); ?></strong>
							<?php endif; ?>
						</p>
					<?php endif; ?>

					<?php if ( $is_locked ) : ?>
						<button type="button" class="rks-btn rks-btn--ghost rks-btn--block" disabled>Locked — Admin Approval Pending</button>
					<?php elseif ( $pay_due && ! $pay_pending ) : ?>
						<?php $pay_url = add_query_arg( [ 'view' => 'pay-monthly', 'course_id' => $cid ], home_url( '/student-dashboard/' ) ); ?>
						<a href="<?php echo esc_url( $pay_url ); ?>" class="rks-btn rks-btn--primary rks-btn--block">
							<?php echo Icons::svg( 'wallet', 16 ); ?> Pay This Month
						</a>
					<?php elseif ( $pay_pending ) : ?>
						<button type="button" class="rks-btn rks-btn--ghost rks-btn--block" disabled>Awaiting Payment Verification</button>
					<?php else : ?>
						<a href="<?php echo esc_url( $resume_url ); ?>" class="rks-btn rks-btn--primary rks-btn--block">
							<?php
							if ( $progress >= 100 ) echo 'Review Course →';
							elseif ( $progress > 0 ) echo 'Continue Learning →';
							else echo 'Start Course →';
							?>
						</a>
						<?php if ( $pay_early ) : ?>
							<?php $early_url = add_query_arg( [ 'view' => 'pay-monthly', 'course_id' => $cid ], home_url( '/student-dashboard/' ) ); ?>
							<a href="<?php echo esc_url( $early_url ); ?>" class="rks-btn rks-btn--ghost rks-btn--block rks-btn--small">
								<?php echo Icons::svg( 'wallet', 14 ); ?> Pay Next Month Early
							</a>
						<?php endif; ?>
					<?php endif; ?>
				</article>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>

<style>
.rks-flash {
	display: flex;
	align-items: center;
	gap: 8px;
	padding: 12px 16px;
	border-radius: 8px;
	margin-bottom: 16px;
	font-size: 14px;
	font-family: var(--rks-font-body, 'Poppins', sans-serif);
}
.rks-flash--success {
	background: rgba(142, 201, 196, 0.18);
	color: var(--rks-teal-dark, #1f4e5e);
	border: 1px solid rgba(142, 201, 196, 0.45);
}
.rks-flash--error {
	background: rgba(230, 57, 70, 0.08);
	color: var(--rks-red, #E63946);
	border: 1px solid rgba(230, 57, 70, 0.3);
}
.rks-stu-card__badge {
	position: absolute;
	top: 10px; right: 12px;
	font-size: 10px;
	font-weight: 700;
	letter-spacing: 0.5px;
	padding: 4px 8px;
	border-radius: 4px;
	display: inline-flex;
	align-items: center;
	gap: 4px;
	text-transform: uppercase;
}
.rks-stu-card__badge--due    { background: rgba(230, 57, 70, 0.12);  color: #E63946; }
.rks-stu-card__badge--pending{ background: rgba(44, 104, 123, 0.12); color: #2C687B; }
.rks-stu-card__badge--paid   { background: rgba(142, 201, 196, 0.2); color: #1f4e5e; }
.rks-stu-card--due { border-color: rgba(230, 57, 70, 0.35); }
.rks-stu-card__sub-info {
	display: flex; align-items: center; gap: 6px;
	font-size: 12px;
	color: var(--rks-muted, #6b7280);
	margin: 0;
}
.rks-stu-card__sub-info strong { color: var(--rks-teal, #2C687B); }
.rks-pay-form__actions .rks-btn { display: inline-flex; align-items: center; gap: 6px; }
</style>
