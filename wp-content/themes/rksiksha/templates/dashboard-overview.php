<?php
/**
 * Dashboard — Overview tab
 *
 * @var \RKSiksha\Core\Modules\Dashboard $dash
 * @var int $branch_id
 * @var array|null $branch
 * @var string $currency
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$stats = $dash->stats( $branch_id );
?>

<header class="rks-dash__header">
	<div>
		<h1>Dashboard Overview</h1>
		<p>Welcome back. Here's what's happening at <strong><?php echo esc_html( $branch ? $branch['name'] : 'your branch' ); ?></strong>.</p>
	</div>
</header>

<div class="rks-dash__stats">
	<div class="rks-dash__stat rks-dash__stat--primary">
		<span class="rks-dash__stat-label">Total Students</span>
		<span class="rks-dash__stat-num"><?php echo number_format( (int) $stats['total_students'] ); ?></span>
	</div>
	<div class="rks-dash__stat <?php echo $stats['pending_admissions'] > 0 ? 'rks-dash__stat--alert' : ''; ?>">
		<span class="rks-dash__stat-label">Pending Admissions</span>
		<span class="rks-dash__stat-num"><?php echo number_format( (int) $stats['pending_admissions'] ); ?></span>
		<?php if ( $stats['pending_admissions'] > 0 ) : ?>
			<a href="<?php echo esc_url( add_query_arg( 'view', 'admissions', home_url( '/branch-dashboard/' ) ) ); ?>" class="rks-dash__stat-link">Review →</a>
		<?php endif; ?>
	</div>
	<div class="rks-dash__stat <?php echo $stats['pending_payments'] > 0 ? 'rks-dash__stat--alert' : ''; ?>">
		<span class="rks-dash__stat-label">Pending Payments</span>
		<span class="rks-dash__stat-num"><?php echo number_format( (int) $stats['pending_payments'] ); ?></span>
	</div>
	<div class="rks-dash__stat">
		<span class="rks-dash__stat-label">Approved This Month</span>
		<span class="rks-dash__stat-num"><?php echo number_format( (int) $stats['approved_this_month'] ); ?></span>
	</div>
	<div class="rks-dash__stat">
		<span class="rks-dash__stat-label">Revenue This Month</span>
		<span class="rks-dash__stat-num"><?php echo esc_html( $currency . number_format( (float) $stats['revenue_this_month'], 0 ) ); ?></span>
	</div>
	<div class="rks-dash__stat">
		<span class="rks-dash__stat-label">Total Revenue</span>
		<span class="rks-dash__stat-num"><?php echo esc_html( $currency . number_format( (float) $stats['total_revenue'], 0 ) ); ?></span>
	</div>
</div>

<?php
$recent_admissions = ( new \RKSiksha\Core\Modules\Admissions() )->list( [ 'branch_id' => $branch_id, 'limit' => 5 ] );
?>

<div class="rks-dash__panel">
	<header class="rks-dash__panel-head">
		<h2>Recent Admissions</h2>
		<a href="<?php echo esc_url( add_query_arg( 'view', 'admissions', home_url( '/branch-dashboard/' ) ) ); ?>">See all →</a>
	</header>

	<?php if ( empty( $recent_admissions ) ) : ?>
		<div class="rks-dash__empty">No admissions yet.</div>
	<?php else : ?>
		<table class="rks-dash__table">
			<thead>
				<tr><th>Reference</th><th>Student</th><th>Course</th><th>Status</th><th>Submitted</th></tr>
			</thead>
			<tbody>
				<?php foreach ( $recent_admissions as $r ) :
					$ref = 'ADM-' . substr( (string) $r['created_at'], 0, 4 ) . '-' . str_pad( (string) $r['id'], 5, '0', STR_PAD_LEFT );
				?>
					<tr>
						<td><code><?php echo esc_html( $ref ); ?></code></td>
						<td>
							<strong><?php echo esc_html( $r['full_name'] ); ?></strong>
							<small><?php echo esc_html( $r['email'] ); ?></small>
						</td>
						<td><?php echo esc_html( $r['course_title'] ); ?></td>
						<td><span class="rks-status rks-status--<?php echo esc_attr( $r['status'] ); ?>"><?php echo esc_html( str_replace( '_', ' ', $r['status'] ) ); ?></span></td>
						<td><?php echo esc_html( human_time_diff( strtotime( (string) $r['created_at'] ), current_time( 'timestamp' ) ) ); ?> ago</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>

<div class="rks-dash__cta-grid">
	<a href="<?php echo esc_url( add_query_arg( 'view', 'admissions', home_url( '/branch-dashboard/' ) ) ); ?>" class="rks-dash__cta">
		<strong>Review Pending</strong>
		<small>Approve or reject new admissions awaiting your action.</small>
	</a>
	<a href="<?php echo esc_url( add_query_arg( 'view', 'students', home_url( '/branch-dashboard/' ) ) ); ?>" class="rks-dash__cta">
		<strong>View Students</strong>
		<small>See enrolled students and their progress.</small>
	</a>
	<a href="<?php echo esc_url( add_query_arg( 'view', 'branch', home_url( '/branch-dashboard/' ) ) ); ?>" class="rks-dash__cta">
		<strong>Branch Profile</strong>
		<small>Update your branch contact details and address.</small>
	</a>
</div>
