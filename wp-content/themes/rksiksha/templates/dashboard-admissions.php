<?php
/**
 * Dashboard — Admissions queue
 *
 * @var \RKSiksha\Core\Modules\Dashboard $dash
 * @var int $branch_id
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$status_filter = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( (string) $_GET['status'] ) ) : 'all';

$args = [ 'branch_id' => $branch_id, 'limit' => 100 ];
if ( 'all' !== $status_filter ) $args['status'] = $status_filter;

$admissions = ( new \RKSiksha\Core\Modules\Admissions() )->list( $args );
?>

<header class="rks-dash__header">
	<div>
		<h1>Admissions</h1>
		<p>Review applications, verify payments, and approve students for your branch.</p>
	</div>
</header>

<div class="rks-dash__filters">
	<?php foreach ( [ 'all' => 'All', 'pending' => 'Pending', 'payment_review' => 'Payment Review', 'approved' => 'Approved', 'rejected' => 'Rejected' ] as $s => $label ) : ?>
		<a href="<?php echo esc_url( add_query_arg( [ 'view' => 'admissions', 'status' => $s ], home_url( '/branch-dashboard/' ) ) ); ?>"
		   class="rks-dash__filter <?php echo $status_filter === $s ? 'is-active' : ''; ?>">
			<?php echo esc_html( $label ); ?>
		</a>
	<?php endforeach; ?>
</div>

<div class="rks-dash__panel">
	<?php if ( empty( $admissions ) ) : ?>
		<div class="rks-dash__empty">No admissions match this filter.</div>
	<?php else : ?>
		<div class="rks-dash__cards">
			<?php foreach ( $admissions as $r ) :
				$ref = 'ADM-' . substr( (string) $r['created_at'], 0, 4 ) . '-' . str_pad( (string) $r['id'], 5, '0', STR_PAD_LEFT );
				$pay = $r['payment_id'] ? ( new \RKSiksha\Core\Modules\Payments() )->get( (int) $r['payment_id'] ) : null;
				$can_act = in_array( $r['status'], [ 'pending', 'payment_review' ], true );
			?>
				<article class="rks-dash__card">
					<div class="rks-dash__card-head">
						<div>
							<code><?php echo esc_html( $ref ); ?></code>
							<span class="rks-status rks-status--<?php echo esc_attr( $r['status'] ); ?>"><?php echo esc_html( str_replace( '_', ' ', $r['status'] ) ); ?></span>
						</div>
						<small><?php echo esc_html( human_time_diff( strtotime( (string) $r['created_at'] ), current_time( 'timestamp' ) ) ); ?> ago</small>
					</div>

					<div class="rks-dash__card-body">
						<div class="rks-dash__card-col">
							<small>Student</small>
							<strong><?php echo esc_html( $r['full_name'] ); ?></strong>
							<a href="mailto:<?php echo esc_attr( $r['email'] ); ?>"><?php echo esc_html( $r['email'] ); ?></a>
							<a href="tel:<?php echo esc_attr( $r['phone'] ); ?>"><?php echo esc_html( $r['phone'] ); ?></a>
						</div>
						<div class="rks-dash__card-col">
							<small>Course</small>
							<strong><?php echo esc_html( $r['course_title'] ); ?></strong>
							<span>Fees: <?php echo esc_html( $currency . number_format( (float) $r['fees_amount'], 0 ) ); ?></span>
							<?php if ( $r['qualification'] ) : ?>
								<span>Qualification: <?php echo esc_html( $r['qualification'] ); ?></span>
							<?php endif; ?>
						</div>
						<div class="rks-dash__card-col">
							<small>Payment</small>
							<?php if ( $pay ) : ?>
								<strong><?php echo esc_html( $currency . number_format( (float) $pay['amount'], 0 ) ); ?></strong>
								<span>UTR: <code><?php echo esc_html( (string) $pay['utr'] ); ?></code></span>
								<?php if ( ! empty( $pay['screenshot_path'] ) ) : ?>
									<a href="<?php echo esc_url( \RKSiksha\Core\Modules\Payments::view_url( (int) $pay['id'] ) ); ?>" target="_blank" rel="noopener">View screenshot →</a>
								<?php endif; ?>
								<span class="rks-status rks-status--<?php echo esc_attr( $pay['status'] ); ?>"><?php echo esc_html( $pay['status'] ); ?></span>
							<?php else : ?>
								<em>No payment recorded</em>
							<?php endif; ?>
						</div>
					</div>

					<?php if ( $can_act ) : ?>
						<footer class="rks-dash__card-foot">
							<form method="post" class="rks-dash__inline-form">
								<?php wp_nonce_field( 'rks_dash_admission' ); ?>
								<input type="hidden" name="rks_action" value="admission_approve">
								<input type="hidden" name="admission_id" value="<?php echo (int) $r['id']; ?>">
								<button type="submit" class="rks-btn rks-btn--primary" onclick="return confirm('Approve this admission? Student account will be created and they will be auto-enrolled.');">✓ Approve</button>
							</form>

							<details class="rks-dash__reject">
								<summary class="rks-btn rks-btn--ghost">✗ Reject</summary>
								<form method="post" class="rks-dash__inline-form">
									<?php wp_nonce_field( 'rks_dash_admission' ); ?>
									<input type="hidden" name="rks_action" value="admission_reject">
									<input type="hidden" name="admission_id" value="<?php echo (int) $r['id']; ?>">
									<textarea name="note" rows="2" placeholder="Reason for rejection (will be emailed to student)" required></textarea>
									<button type="submit" class="rks-btn rks-btn--danger">Confirm Reject</button>
								</form>
							</details>
						</footer>
					<?php elseif ( 'rejected' === $r['status'] && ! empty( $r['rejection_note'] ) ) : ?>
						<footer class="rks-dash__card-foot">
							<small><strong>Rejection note:</strong> <?php echo esc_html( (string) $r['rejection_note'] ); ?></small>
						</footer>
					<?php elseif ( 'approved' === $r['status'] ) : ?>
						<footer class="rks-dash__card-foot">
							<?php
							$student = $r['user_id'] ? get_userdata( (int) $r['user_id'] ) : null;
							?>
							<small>✓ Approved <?php echo $r['reviewed_at'] ? esc_html( human_time_diff( strtotime( (string) $r['reviewed_at'] ), current_time( 'timestamp' ) ) ) . ' ago' : ''; ?> · Account: <code><?php echo esc_html( $student ? $student->user_login : '—' ); ?></code></small>
						</footer>
					<?php endif; ?>
				</article>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
