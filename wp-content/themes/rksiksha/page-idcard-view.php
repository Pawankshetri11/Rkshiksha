<?php
/**
 * Template Name: ID Card View (Printable)
 *
 * Renders a single ID card by `?id=RKS-ID-STU-XXXXXX`. Public — no login.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

use RKSiksha\Core\Modules\Branches;
use RKSiksha\Core\Modules\IdCard;

$id   = isset( $_GET['id'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['id'] ) ) : '';
$card = $id ? ( new IdCard() )->get_by_unique_id( $id ) : null;
$is_valid = $card && $card['status'] === 'active';

$branch = $is_valid ? ( new Branches() )->get( (int) $card['branch_id'] ) : null;
$verify_url = add_query_arg( 'id', $id, home_url( '/verify-id/' ) );
$qr_url     = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode( $verify_url );

$user = $is_valid ? get_userdata( (int) $card['user_id'] ) : null;
$initial = $is_valid ? strtoupper( substr( (string) $card['holder_name'], 0, 1 ) ) : '?';

// Pull uploaded profile photo if available
$photo_id  = $user ? (int) get_user_meta( $user->ID, 'rks_photo_id', true ) : 0;
$photo_url = $photo_id ? wp_get_attachment_image_url( $photo_id, 'medium' ) : '';

$rks_settings   = (array) get_option( 'rks_settings', [] );
$support_phone  = (string) ( $rks_settings['support_phone'] ?? '+91 89797 75664' );
$support_email  = (string) ( $rks_settings['support_email'] ?? 'info@rkshiksha.com' );

$mask_email = function( $email ) {
	if ( ! $email || strpos( $email, '@' ) === false ) return $email;
	list( $a, $b ) = explode( '@', $email );
	return substr( $a, 0, 2 ) . str_repeat( '*', max( 1, strlen( $a ) - 2 ) ) . '@' . $b;
};

$phone = '';
$enroll_count = 0;
$email_masked = '';
$father_name  = '';
if ( $is_valid && $user ) {
	$phone = (string) get_user_meta( (int) $card['user_id'], 'rks_phone', true );
	$email_masked = $mask_email( (string) $user->user_email );
	global $wpdb;
	$father_name = (string) $wpdb->get_var( $wpdb->prepare(
		"SELECT father_name FROM {$wpdb->prefix}rks_admissions WHERE user_id = %d AND father_name IS NOT NULL AND father_name <> '' ORDER BY id DESC LIMIT 1",
		(int) $card['user_id']
	) );
	if ( strtolower( (string) $card['holder_type'] ) === 'student' ) {
		$enroll_count = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}rks_enrollments WHERE user_id = %d",
			(int) $card['user_id']
		) );
	}
}

$share_text = $is_valid ? 'My Rishikul Shiksha ID — verify here:' : 'Verify this Rishikul Shiksha ID:';

get_header();
?>

<section class="rks-idcard-page">
	<div class="rks-container">

		<?php if ( ! $is_valid ) : ?>
			<div class="rks-cert-not-found">
				<div class="rks-cert-not-found__icon">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="40" height="40" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
				</div>
				<h1>ID Card Not Found</h1>
				<p>The ID card <code><?php echo esc_html( $id ); ?></code> is invalid or expired.</p>
				<a href="<?php echo esc_url( home_url( '/verify-id/' ) ); ?>" class="rks-btn rks-btn--primary">Verify an ID Card</a>
			</div>
		<?php else : ?>

			<div class="rks-cert-toolbar no-print">
				<div class="rks-cert-toolbar__info">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4"/><path d="M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9c2.5 0 4.76 1.02 6.4 2.66"/></svg>
					<span>Verified by <strong>Rishikul Shiksha</strong></span>
				</div>
				<div class="rks-cert-toolbar__actions">
					<button onclick="window.print()" class="rks-btn rks-btn--primary" type="button">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
						Print / PDF
					</button>
					<a href="<?php echo esc_url( $verify_url ); ?>" class="rks-btn rks-btn--ghost" target="_blank" rel="noopener">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
						Verify Online
					</a>

					<div class="rks-share">
						<span class="rks-share__label">Share</span>
						<a class="rks-share__btn rks-share__btn--wa" href="https://wa.me/?text=<?php echo urlencode( $share_text . ' ' . $verify_url ); ?>" target="_blank" rel="noopener" aria-label="Share on WhatsApp" title="WhatsApp">
							<svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M.057 24l1.687-6.163a11.867 11.867 0 0 1-1.587-5.946C.16 5.335 5.495 0 12.05 0a11.817 11.817 0 0 1 8.413 3.488 11.824 11.824 0 0 1 3.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892a11.9 11.9 0 0 1-5.688-1.448L.057 24zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884a9.86 9.86 0 0 0 1.516 5.26L3.5 19.85l1.654-1.657zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.296-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
						</a>
						<a class="rks-share__btn rks-share__btn--fb" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode( $verify_url ); ?>" target="_blank" rel="noopener" aria-label="Share on Facebook" title="Facebook">
							<svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M13 22v-8h3l1-4h-4V7.5c0-1.2.3-2 2-2h2V2.1c-.4 0-1.6-.1-2.9-.1-2.9 0-4.9 1.8-4.9 5v3H7v4h3v8h3z"/></svg>
						</a>
						<a class="rks-share__btn rks-share__btn--tw" href="https://twitter.com/intent/tweet?url=<?php echo urlencode( $verify_url ); ?>&text=<?php echo urlencode( $share_text ); ?>" target="_blank" rel="noopener" aria-label="Share on X" title="X / Twitter">
							<svg viewBox="0 0 24 24" fill="currentColor" width="14" height="14"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
						</a>
						<a class="rks-share__btn rks-share__btn--li" href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode( $verify_url ); ?>" target="_blank" rel="noopener" aria-label="Share on LinkedIn" title="LinkedIn">
							<svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M19 3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14zM8 17v-7H6v7h2zM7 8.5A1.5 1.5 0 1 0 7 5.5a1.5 1.5 0 0 0 0 3zM18 17v-4.2c0-2-1-3-2.6-3-1.3 0-1.9.7-2.2 1.2V10h-2v7h2v-3.8c0-1 .2-2 1.4-2s1.4 1.1 1.4 2V17h2z"/></svg>
						</a>
						<button type="button" id="rks-share-copy" class="rks-share__btn rks-share__btn--copy" aria-label="Copy link" title="Copy link">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
						</button>
						<button type="button" id="rks-share-native" class="rks-share__btn" aria-label="Share" title="Share" style="display:none">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
						</button>
					</div>
				</div>
			</div>

			<div class="rks-idcard-stack">

				<!-- FRONT -->
				<div class="rks-idcard rks-idcard--front">
					<div class="rks-idcard__head">
						<span class="rks-idcard__brand">
							<span class="rks-idcard__brand-rk">Rishikul</span><span class="rks-idcard__brand-shiksha">Shiksha</span>
						</span>
						<small><?php echo esc_html( strtoupper( str_replace( '_', ' ', (string) $card['holder_type'] ) ) ); ?> ID</small>
					</div>

					<div class="rks-idcard__body">
						<div class="rks-idcard__photo<?php echo $photo_url ? ' rks-idcard__photo--img' : ''; ?>">
								<?php if ( $photo_url ) : ?>
									<img src="<?php echo esc_url( $photo_url ); ?>" alt="<?php echo esc_attr( (string) $card['holder_name'] ); ?>">
								<?php else : ?>
									<?php echo esc_html( $initial ); ?>
								<?php endif; ?>
							</div>
						<div class="rks-idcard__info">
							<strong><?php echo esc_html( (string) $card['holder_name'] ); ?></strong>
							<span><?php echo esc_html( (string) $card['holder_role'] ); ?></span>
							<dl>
								<?php if ( ! empty( $father_name ) ) : ?>
									<dt>Father</dt><dd><?php echo esc_html( $father_name ); ?></dd>
								<?php endif; ?>
								<dt>ID</dt><dd><?php echo esc_html( (string) $card['unique_id'] ); ?></dd>
								<dt>Branch</dt><dd><?php echo esc_html( $branch ? $branch['name'] : '—' ); ?></dd>
								<dt>Issued</dt><dd><?php echo esc_html( date( 'd M Y', strtotime( (string) $card['issue_date'] ) ) ); ?></dd>
								<?php if ( ! empty( $card['expiry_date'] ) ) : ?>
									<dt>Valid</dt><dd>until <?php echo esc_html( date( 'd M Y', strtotime( (string) $card['expiry_date'] ) ) ); ?></dd>
								<?php endif; ?>
								<?php if ( ! empty( $phone ) ) : ?>
									<dt>Phone</dt><dd><?php echo esc_html( $phone ); ?></dd>
								<?php endif; ?>
								<?php if ( $enroll_count > 0 ) : ?>
									<dt>Courses</dt><dd><?php echo (int) $enroll_count; ?> enrolled</dd>
								<?php endif; ?>
								<?php if ( ! empty( $email_masked ) ) : ?>
									<dt>Email</dt><dd><?php echo esc_html( $email_masked ); ?></dd>
								<?php endif; ?>
							</dl>
						</div>
						<div class="rks-idcard__qr">
							<div class="rks-idcard__qr-img">
								<img src="<?php echo esc_url( $qr_url ); ?>" alt="QR">
							</div>
							<small>Scan to verify</small>
						</div>
					</div>

					<div class="rks-idcard__foot">
						<small>This card is property of Rishikul Shiksha. Verify at <?php echo esc_html( $verify_url ); ?></small>
					</div>
				</div>

				<!-- BACK -->
				<div class="rks-idcard rks-idcard--back">
					<div class="rks-idcard__head">
						<small>Authorized Signatory</small>
					</div>
					<div class="rks-idcard__back-body">
						<div>
							<p><strong>Terms of Use:</strong></p>
							<ul>
								<li>This card is non-transferable and remains property of Rishikul Shiksha.</li>
								<li>Must be returned upon completion or termination of association.</li>
								<li>If found, please return to the nearest Rishikul Shiksha branch.</li>
								<li>Any misuse will result in immediate revocation.</li>
							</ul>
							<p><strong>Emergency Contact:</strong> <?php echo esc_html( $support_phone ); ?></p>
							<p><strong>Support:</strong> <?php echo esc_html( $support_email ); ?></p>
							<p><strong>Web:</strong> <?php echo esc_html( str_replace( array( 'https://', 'http://' ), '', home_url() ) ); ?></p>

							<div class="rks-idcard__sign">
								<div class="rks-idcard__sign-line"></div>
								<small>Chairman, Rishikul Shiksha</small>
							</div>
						</div>

						<div class="rks-idcard__back-side">
							<div class="rks-idcard__qr-img">
								<img src="<?php echo esc_url( $qr_url ); ?>" alt="Verify QR">
							</div>
							<small>Scan to Verify</small>
							<code><?php echo esc_html( (string) $card['unique_id'] ); ?></code>
						</div>
					</div>
					<div class="rks-idcard__foot">
						<small><?php echo esc_html( $verify_url ); ?></small>
					</div>
				</div>

			</div>

			<div class="rks-share__toast" id="rks-toast">Link copied to clipboard!</div>

			<script>
			(function(){
				var url = '<?php echo esc_js( $verify_url ); ?>';
				var nativeShareBtn = document.getElementById('rks-share-native');
				if (nativeShareBtn && navigator.share) {
					nativeShareBtn.style.display = 'inline-flex';
					nativeShareBtn.addEventListener('click', function(){
						navigator.share({ title: document.title, url: url }).catch(function(){});
					});
				}
				var copyBtn = document.getElementById('rks-share-copy');
				if (copyBtn) {
					copyBtn.addEventListener('click', function(e){
						e.preventDefault();
						var done = function(){
							var t = document.getElementById('rks-toast');
							if (!t) return;
							t.classList.add('is-visible');
							setTimeout(function(){ t.classList.remove('is-visible'); }, 2000);
						};
						if (navigator.clipboard && navigator.clipboard.writeText) {
							navigator.clipboard.writeText(url).then(done).catch(function(){
								window.prompt('Copy this link:', url);
							});
						} else {
							var ta = document.createElement('textarea');
							ta.value = url; document.body.appendChild(ta);
							ta.select(); try { document.execCommand('copy'); done(); } catch(e){}
							document.body.removeChild(ta);
						}
					});
				}
			})();
			</script>

		<?php endif; ?>
	</div>
</section>

<?php get_footer(); ?>
