<?php
/**
 * Dashboard — Payments view (read-only audit log)
 *
 * @var int $branch_id
 * @var string $currency
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$status_filter = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( (string) $_GET['status'] ) ) : 'all';
$args = [ 'branch_id' => $branch_id, 'limit' => 100 ];
if ( 'all' !== $status_filter ) $args['status'] = $status_filter;

$payments = ( new \RKSiksha\Core\Modules\Payments() )->list( $args );
?>

<header class="rks-dash__header">
	<div>
		<h1>Payments</h1>
		<p>Audit log of all UPI payments routed through your branch. Verify or reject from the Admissions tab.</p>
	</div>
</header>

<div class="rks-dash__filters">
	<?php foreach ( [ 'all' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected' ] as $s => $label ) : ?>
		<a href="<?php echo esc_url( add_query_arg( [ 'view' => 'payments', 'status' => $s ], home_url( '/branch-dashboard/' ) ) ); ?>"
		   class="rks-dash__filter <?php echo $status_filter === $s ? 'is-active' : ''; ?>">
			<?php echo esc_html( $label ); ?>
		</a>
	<?php endforeach; ?>
</div>

<div class="rks-dash__panel">
	<?php if ( empty( $payments ) ) : ?>
		<div class="rks-dash__empty">No payments match this filter.</div>
	<?php else : ?>
		<table class="rks-dash__table">
			<thead>
				<tr>
					<th>ID</th>
					<th>Admission</th>
					<th>Amount</th>
					<th>UTR</th>
					<th>Method</th>
					<th>Screenshot</th>
					<th>Status</th>
					<th>Created</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $payments as $p ) : ?>
					<tr>
						<td>#<?php echo (int) $p['id']; ?></td>
						<td><?php echo $p['admission_id'] ? '#' . (int) $p['admission_id'] : '—'; ?></td>
						<td><strong><?php echo esc_html( $currency . number_format( (float) $p['amount'], 0 ) ); ?></strong></td>
						<td><code><?php echo esc_html( (string) $p['utr'] ); ?></code></td>
						<td><?php echo esc_html( strtoupper( (string) $p['method'] ) ); ?></td>
						<td>
							<?php if ( ! empty( $p['screenshot_path'] ) ) : ?>
								<a href="<?php echo esc_url( \RKSiksha\Core\Modules\Payments::view_url( (int) $p['id'] ) ); ?>" target="_blank" rel="noopener">View →</a>
							<?php else : ?>—<?php endif; ?>
						</td>
						<td><span class="rks-status rks-status--<?php echo esc_attr( $p['status'] ); ?>"><?php echo esc_html( $p['status'] ); ?></span></td>
						<td><?php echo esc_html( (string) $p['created_at'] ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
