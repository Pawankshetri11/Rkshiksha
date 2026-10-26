<?php
/** @var \WP_User $user */
/** @var \RKSiksha\Core\Modules\Lms $lms */
if ( ! defined( 'ABSPATH' ) ) exit;

use RKSiksha\Core\Modules\Subscriptions;
use RKSiksha\Core\Support\DB;
use RKSiksha\Core\Support\Icons;

global $wpdb;

$subs        = new Subscriptions();
$enrollments = $lms->user_enrollments( $user->ID );
$settings    = (array) get_option( 'rks_settings', [] );
$currency    = (string) ( $settings['currency_symbol'] ?? '₹' );

// Fetch payment history for this user.
// Payments may be linked directly (user_id) OR indirectly via admission (admission_id -> admissions.user_id),
// because earlier admission flows wrote payments before the student account existed.
$pay_tbl  = DB::table( 'payments' );
$adm_tbl  = DB::table( 'admissions' );
$payments = $wpdb->get_results( $wpdb->prepare(
	"SELECT p.* FROM {$pay_tbl} p
	 LEFT JOIN {$adm_tbl} a ON p.admission_id = a.id
	 WHERE p.user_id = %d OR a.user_id = %d
	 GROUP BY p.id
	 ORDER BY p.created_at DESC
	 LIMIT 100",
	$user->ID,
	$user->ID
), ARRAY_A ) ?: [];

// Active monthly subscriptions
$active_subs = [];
foreach ( $enrollments as $e ) {
	$sub = $subs->get_for_user( $user->ID, (int) $e['course_id'] );
	if ( $sub && $sub['status'] !== Subscriptions::STATUS_COMPLETED && $sub['status'] !== Subscriptions::STATUS_CANCELLED ) {
		$course = get_post( (int) $e['course_id'] );
		if ( $course ) {
			$active_subs[] = [ 'sub' => $sub, 'course' => $course ];
		}
	}
}

$total_paid    = 0.0;
$total_pending = 0.0;
foreach ( $payments as $p ) {
	if ( $p['status'] === 'approved' ) $total_paid += (float) $p['amount'];
	if ( $p['status'] === 'pending' )  $total_pending += (float) $p['amount'];
}
?>

<header class="rks-dash__header">
	<div>
		<h1>Payments</h1>
		<p>Your installment schedule, payment history and pending verifications.</p>
	</div>
</header>

<div class="rks-pay-summary">
	<div class="rks-pay-summary__card">
		<small>Total Paid</small>
		<strong><?php echo esc_html( $currency . number_format( $total_paid, 0 ) ); ?></strong>
	</div>
	<div class="rks-pay-summary__card">
		<small>Awaiting Verification</small>
		<strong><?php echo esc_html( $currency . number_format( $total_pending, 0 ) ); ?></strong>
	</div>
	<div class="rks-pay-summary__card">
		<small>Active Plans</small>
		<strong><?php echo count( $active_subs ); ?></strong>
	</div>
</div>

<?php if ( ! empty( $active_subs ) ) : ?>
	<div class="rks-dash__panel">
		<header class="rks-dash__panel-head">
			<h2>Active Monthly Plans</h2>
		</header>

		<div class="rks-pay-plans">
			<?php foreach ( $active_subs as $row ) :
				$sub        = $row['sub'];
				$course     = $row['course'];
				$cid        = (int) $sub['course_id'];
				$pay_due    = $subs->is_payment_due( $sub );
				$can_pay    = $subs->can_pay_now( $sub );
				$pay_pending= $subs->has_pending_payment( (int) $sub['id'] );
				$paid_pct   = $sub['total_months'] > 0 ? (int) round( ( $sub['months_paid'] / $sub['total_months'] ) * 100 ) : 0;
				$pay_url    = add_query_arg( [ 'view' => 'pay-monthly', 'course_id' => $cid ], home_url( '/student-dashboard/' ) );
			?>
				<article class="rks-pay-plan">
					<div class="rks-pay-plan__head">
						<h3><?php echo esc_html( $course->post_title ); ?></h3>
						<?php if ( $pay_due ) : ?>
							<span class="rks-pay-plan__badge rks-pay-plan__badge--due"><?php echo Icons::svg( 'alert', 12 ); ?> Due Now</span>
						<?php elseif ( $pay_pending ) : ?>
							<span class="rks-pay-plan__badge rks-pay-plan__badge--pending"><?php echo Icons::svg( 'clock', 12 ); ?> Pending Review</span>
						<?php else : ?>
							<span class="rks-pay-plan__badge rks-pay-plan__badge--ok"><?php echo Icons::svg( 'check', 12 ); ?> Up to date</span>
						<?php endif; ?>
					</div>

					<div class="rks-pay-plan__progress">
						<div class="rks-pay-plan__bar" style="width: <?php echo (int) $paid_pct; ?>%;"></div>
					</div>
					<p class="rks-pay-plan__meta">
						<strong><?php echo (int) $sub['months_paid']; ?> / <?php echo (int) $sub['total_months']; ?></strong> months paid
						&middot; <?php echo esc_html( $currency . number_format( (float) $sub['monthly_fee'], 0 ) ); ?>/month
						<?php if ( ! empty( $sub['next_due_date'] ) ) : ?>
							&middot; Next due: <strong><?php echo esc_html( mysql2date( get_option( 'date_format', 'F j, Y' ), $sub['next_due_date'] ) ); ?></strong>
						<?php endif; ?>
					</p>

					<?php if ( $can_pay && ! $pay_pending ) : ?>
						<a href="<?php echo esc_url( $pay_url ); ?>" class="rks-btn <?php echo $pay_due ? 'rks-btn--primary' : 'rks-btn--ghost'; ?>">
							<?php echo Icons::svg( 'wallet', 14 ); ?>
							<?php echo $pay_due ? 'Pay This Month' : 'Pay Early'; ?>
						</a>
					<?php elseif ( $pay_pending ) : ?>
						<button class="rks-btn rks-btn--ghost" disabled>Awaiting Verification</button>
					<?php endif; ?>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
<?php endif; ?>

<div class="rks-dash__panel">
	<header class="rks-dash__panel-head">
		<h2>Payment History</h2>
		<small><?php echo count( $payments ); ?> total</small>
	</header>

	<?php if ( empty( $payments ) ) : ?>
		<div class="rks-dash__empty">
			<p>No payments yet.</p>
		</div>
	<?php else : ?>
		<div class="rks-pay-history">
			<table class="rks-pay-history__table">
				<thead>
					<tr>
						<th>Date</th>
						<th>Amount</th>
						<th>Method</th>
						<th>UTR / Reference</th>
						<th>Status</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $payments as $p ) :
						$status = (string) $p['status'];
					?>
						<tr>
							<td><?php echo esc_html( mysql2date( 'd M Y', $p['created_at'] ) ); ?></td>
							<td><strong><?php echo esc_html( $currency . number_format( (float) $p['amount'], 0 ) ); ?></strong></td>
							<td><?php echo esc_html( strtoupper( (string) $p['method'] ) ); ?></td>
							<td><code><?php echo esc_html( (string) ( $p['utr'] ?: '—' ) ); ?></code></td>
							<td>
								<span class="rks-status rks-status--<?php echo esc_attr( $status ); ?>">
									<?php echo esc_html( ucfirst( $status ) ); ?>
								</span>
								<?php if ( ! empty( $p['rejection_note'] ) ) : ?>
									<div class="rks-pay-history__note" style="color: var(--rks-red, #E63946); font-size: 11px; margin-top: 4px; max-width: 200px; font-style: italic;">
										<strong>Reason:</strong> <?php echo esc_html( (string) $p['rejection_note'] ); ?>
									</div>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</div>
