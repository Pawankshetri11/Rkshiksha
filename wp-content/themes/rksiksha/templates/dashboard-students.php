<?php
/**
 * Dashboard — Students list
 *
 * @var \RKSiksha\Core\Modules\Dashboard $dash
 * @var int $branch_id
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$students = $dash->students( $branch_id, 100, 0 );
?>

<header class="rks-dash__header">
	<div>
		<h1>Students</h1>
		<p>All enrolled students at your branch.</p>
	</div>
</header>

<div class="rks-dash__panel">
	<?php if ( empty( $students ) ) : ?>
		<div class="rks-dash__empty">No students enrolled yet.</div>
	<?php else : ?>
		<table class="rks-dash__table">
			<thead>
				<tr>
					<th>Name</th>
					<th>Username</th>
					<th>Email</th>
					<th>Enrollments</th>
					<th>Completed</th>
					<th>Last Enrolled</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $students as $s ) : ?>
					<tr>
						<td><strong><?php echo esc_html( (string) ( $s['display_name'] ?: $s['user_login'] ) ); ?></strong></td>
						<td><code><?php echo esc_html( (string) $s['user_login'] ); ?></code></td>
						<td><a href="mailto:<?php echo esc_attr( (string) $s['user_email'] ); ?>"><?php echo esc_html( (string) $s['user_email'] ); ?></a></td>
						<td><?php echo (int) $s['enrollment_count']; ?></td>
						<td><?php echo (int) $s['completed_count']; ?></td>
						<td><?php echo $s['last_enrolled'] ? esc_html( human_time_diff( strtotime( (string) $s['last_enrolled'] ), current_time( 'timestamp' ) ) ) . ' ago' : '—'; ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
