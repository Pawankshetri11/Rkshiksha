<?php
/** @var \WP_User $user */
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$rows = $wpdb->get_results( $wpdb->prepare(
	"SELECT * FROM {$wpdb->prefix}rks_certificates WHERE user_id = %d AND status = 'valid' ORDER BY id DESC",
	$user->ID
), ARRAY_A );
?>

<header class="rks-dash__header">
	<div>
		<h1>My Certificates</h1>
		<p>Earned certificates from courses you've completed.</p>
	</div>
</header>

<div class="rks-dash__panel">
	<?php if ( empty( $rows ) ) : ?>
		<div class="rks-dash__empty">
			<p>No certificates yet. Complete a course to earn your first certificate!</p>
			<a href="<?php echo esc_url( add_query_arg( 'view', 'courses', home_url( '/student-dashboard/' ) ) ); ?>" class="rks-btn rks-btn--primary">My Courses</a>
		</div>
	<?php else : ?>
		<div class="rks-stu-cert-grid">
			<?php foreach ( $rows as $c ) : ?>
				<article class="rks-stu-cert">
					<div class="rks-stu-cert__head">
						<span class="rks-stu-cert__seal">★</span>
						<small>Certificate of Completion</small>
					</div>
					<h3><?php echo esc_html( (string) $c['course_title'] ); ?></h3>
					<p>Issued: <?php echo esc_html( (string) $c['issue_date'] ); ?></p>
					<code><?php echo esc_html( (string) $c['unique_id'] ); ?></code>
					<div class="rks-stu-cert__actions">
						<a href="<?php echo esc_url( home_url( '/certificate/?id=' . urlencode( (string) $c['unique_id'] ) ) ); ?>" class="rks-btn rks-btn--primary" target="_blank">View / Print</a>
						<a href="<?php echo esc_url( add_query_arg( 'id', $c['unique_id'], home_url( '/verify-certificate/' ) ) ); ?>" class="rks-btn rks-btn--ghost" target="_blank">Verify</a>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
