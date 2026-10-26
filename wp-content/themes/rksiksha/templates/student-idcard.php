<?php
/** @var \WP_User $user */
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$card = $wpdb->get_row( $wpdb->prepare(
	"SELECT * FROM {$wpdb->prefix}rks_idcards WHERE user_id = %d AND status = 'active' ORDER BY id DESC LIMIT 1",
	$user->ID
), ARRAY_A );
$father_name = $card ? (string) $wpdb->get_var( $wpdb->prepare(
	"SELECT father_name FROM {$wpdb->prefix}rks_admissions WHERE user_id = %d AND father_name IS NOT NULL AND father_name <> '' ORDER BY id DESC LIMIT 1",
	$user->ID
) ) : '';
?>

<header class="rks-dash__header">
	<div>
		<h1>My ID Card</h1>
		<p>Your official Rishikul Shiksha student ID card.</p>
	</div>
</header>

<div class="rks-dash__panel">
	<?php if ( ! $card ) : ?>
		<div class="rks-dash__empty">
			<p>No ID card issued yet. Your card will be generated automatically once your admission is approved.</p>
		</div>
	<?php else : ?>
		<div class="rks-stu-cert-grid">
			<article class="rks-stu-cert">
				<div class="rks-stu-cert__head">
					<span class="rks-stu-cert__seal">⌬</span>
					<small>Student ID Card</small>
				</div>
				<h3><?php echo esc_html( (string) $card['holder_name'] ); ?></h3>
				<?php if ( ! empty( $father_name ) ) : ?><p>Father's Name: <?php echo esc_html( $father_name ); ?></p><?php endif; ?>
				<p>Type: <?php echo esc_html( (string) $card['holder_type'] ); ?></p>
				<p>Issued: <?php echo esc_html( (string) $card['issue_date'] ); ?></p>
				<?php if ( $card['expiry_date'] ) : ?><p>Valid until: <?php echo esc_html( (string) $card['expiry_date'] ); ?></p><?php endif; ?>
				<code><?php echo esc_html( (string) $card['unique_id'] ); ?></code>
				<div class="rks-stu-cert__actions">
					<a href="<?php echo esc_url( home_url( '/idcard/?id=' . urlencode( (string) $card['unique_id'] ) ) ); ?>" class="rks-btn rks-btn--primary" target="_blank">View / Print</a>
					<a href="<?php echo esc_url( add_query_arg( 'id', $card['unique_id'], home_url( '/verify-id/' ) ) ); ?>" class="rks-btn rks-btn--ghost" target="_blank">Verify</a>
				</div>
			</article>
		</div>
	<?php endif; ?>
</div>
