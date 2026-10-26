<?php
/**
 * Template Name: Certificate View (Printable)
 *
 * Renders a single certificate by `?id=RKS-CERT-2026-XXXXXX`. Public — no login.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

use RKSiksha\Core\Modules\Branches;
use RKSiksha\Core\Modules\Certificate;
use RKSiksha\Core\Admin\CertificateDesignerPage;
use RKSiksha\Core\Support\DB;

$id = isset( $_GET['id'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['id'] ) ) : '';
$cert = $id ? ( new Certificate() )->get_by_unique_id( $id ) : null;
$is_valid = $cert && $cert['status'] === 'valid';
$cert_meta = $is_valid && class_exists( '\\RKSiksha\\Core\\Support\\DB' )
	? DB::decode_meta( isset( $cert['meta'] ) ? (string) $cert['meta'] : null )
	: [];

$branch = $is_valid ? ( new Branches() )->get( (int) $cert['branch_id'] ) : null;
$verify_url = add_query_arg( 'id', $id, home_url( '/verify-certificate/' ) );
$qr_url     = 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=' . urlencode( $verify_url );

$share_text = $is_valid ? sprintf( 'I just earned a certificate in %s from RK Shiksha! Verify it here:', (string) $cert['course_title'] ) : 'Verify this RK Shiksha certificate:';

// Use the exact design frozen when this certificate was issued. If that
// template has since been deleted, deliberately fall back to the built-in
// default certificate instead of another custom template or a broken image.
$has_design_snapshot = $is_valid
	&& ! empty( $cert_meta['design_snapshot'] )
	&& is_array( $cert_meta['design_snapshot'] );
$active_design = null;
if ( $has_design_snapshot ) {
	$snapshot        = $cert_meta['design_snapshot'];
	$snapshot_id     = (int) ( $snapshot['id'] ?? 0 );
	$snapshot_design = $snapshot_id > 0 ? get_post( $snapshot_id ) : null;
	if ( $snapshot_design
		&& $snapshot_design->post_type === CertificateDesignerPage::CPT
		&& 'trash' !== $snapshot_design->post_status ) {
		$active_design = $snapshot;
	}
} elseif ( $is_valid && class_exists( '\\RKSiksha\\Core\\Admin\\CertificateDesignerPage' ) ) {
	// Legacy certificate without a snapshot: use the currently active design.
	$active_design = CertificateDesignerPage::get_active_design();
}
$use_design = $active_design && ! empty( $active_design['bg_url'] ) && ! empty( $active_design['fields'] );

// Build value map for variable substitution
if ( $is_valid ) {
	$settings = (array) get_option( 'rks_settings', [] );
	$prefix_only = (string) ( $cert_meta['prefix'] ?? ( $settings['cert_prefix'] ?? 'RKS-CERT' ) );
	$student_user = ! empty( $cert['user_id'] ) ? get_userdata( (int) $cert['user_id'] ) : null;
	$student_initial = strtoupper( substr( (string) $cert['holder_name'], 0, 1 ) );
	$student_photo_id = $student_user ? (int) get_user_meta( $student_user->ID, 'rks_photo_id', true ) : 0;
	$student_photo_url = $student_photo_id ? wp_get_attachment_image_url( $student_photo_id, 'medium' ) : '';
	if ( ! $student_photo_url && ! empty( $cert_meta['student_photo_url'] ) ) {
		$student_photo_url = esc_url_raw( (string) $cert_meta['student_photo_url'] );
	}

	// Father's name from admission record (latest by user)
	$father_name = '';
	if ( ! empty( $cert['user_id'] ) ) {
		global $wpdb;
		$father_name = (string) $wpdb->get_var( $wpdb->prepare(
			'SELECT father_name FROM ' . $wpdb->prefix . 'rks_admissions WHERE user_id = %d ORDER BY id DESC LIMIT 1',
			(int) $cert['user_id']
		) );
	}
	if ( '' === trim( $father_name ) && ! empty( $cert_meta['father_name'] ) ) {
		$father_name = (string) $cert_meta['father_name'];
	}

	$design_values = [
		'academy_name' => 'RISHIKUL SHIKSHA CAREER ACADEMY',
		'academy_line' => 'Under Registered Rishikul Shiksha Trust',
		'certificate_title' => 'CERTIFICATE',
		'certify_label' => 'This is to certify',
		'father_label' => "Father's Name",
		'certificate_no_label' => 'Certificate No.',
		'completion_text' => 'Has successfully completed the course',
		'issue_label' => 'Issue Date',
		'signature_label' => 'Authorized Signature',
		'student_name' => (string) $cert['holder_name'],
		'father_name'  => $father_name,
		'course_name'  => (string) $cert['course_title'],
		'issue_date'   => date( 'jS F Y', strtotime( (string) $cert['issue_date'] ) ),
		'cert_no'      => (string) $cert['unique_id'],
		'cert_prefix'  => $prefix_only,
		'branch_name'  => $branch ? (string) $branch['name'] : '',
		'verify_url'   => $verify_url,
		'qr_code'      => $qr_url,
		'student_photo'=> $student_photo_url,
	];
}

get_header();
?>

<section class="rks-cert-page">
	<div class="rks-container">

		<?php if ( ! $is_valid ) : ?>
			<div class="rks-cert-not-found">
				<div class="rks-cert-not-found__icon">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="40" height="40" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
				</div>
				<h1>Certificate Not Found</h1>
				<p>The certificate ID <code><?php echo esc_html( $id ); ?></code> is invalid or has been revoked.</p>
				<a href="<?php echo esc_url( home_url( '/verify-certificate/' ) ); ?>" class="rks-btn rks-btn--primary">Verify a Certificate</a>
			</div>
		<?php else : ?>

			<div class="rks-cert-layout">

				<!-- LEFT: certificate (fills viewport, fixed ratio) -->
				<div class="rks-cert-paper<?php echo $use_design ? ' rks-cert-paper--custom' : ''; ?>">
					<?php if ( $use_design ) :
						$aspect = ( (int) $active_design['canvas_h'] > 0 )
							? ( (int) $active_design['canvas_w'] / (int) $active_design['canvas_h'] )
							: 1.414;
						?>
						<div class="rks-cert-design" style="aspect-ratio:<?php echo esc_attr( $aspect ); ?> / 1;">
							<img class="rks-cert-design__bg" src="<?php echo esc_url( $active_design['bg_url'] ); ?>" alt="">
							<?php foreach ( $active_design['fields'] as $f ) :
								$key = (string) $f['key'];
								$val = (string) ( $design_values[ $key ] ?? '' );
								$is_image = in_array( $key, [ 'qr_code', 'student_photo' ], true );
								$style  = 'left:' . esc_attr( (string) $f['x'] ) . '%;';
								$style .= 'top:' . esc_attr( (string) $f['y'] ) . '%;';
								$style .= 'width:' . esc_attr( (string) $f['w'] ) . '%;';
								$style .= 'font-size:' . esc_attr( (string) $f['font_size'] ) . 'px;';
								$style .= 'color:' . esc_attr( (string) $f['color'] ) . ';';
								$style .= 'font-family:' . esc_attr( (string) $f['font_family'] ) . ';';
								$style .= 'font-weight:' . esc_attr( (string) $f['font_weight'] ) . ';';
								$style .= 'font-style:' . esc_attr( (string) $f['font_style'] ) . ';';
								$style .= 'text-align:' . esc_attr( (string) $f['align'] ) . ';';
								?>
								<div class="rks-cert-design__field" style="<?php echo $style; ?>">
									<?php if ( $is_image ) : ?>
										<?php if ( $key === 'student_photo' ) : ?>
											<span class="rks-cert-design__student-photo-frame">
												<?php if ( $val ) : ?>
													<img class="rks-cert-design__image rks-cert-design__image--student_photo" src="<?php echo esc_url( $val ); ?>" alt="<?php echo esc_attr( (string) $cert['holder_name'] ); ?>">
												<?php else : ?>
													<span class="rks-cert-design__photo-placeholder"><?php echo esc_html( $student_initial ?: 'S' ); ?></span>
												<?php endif; ?>
											</span>
										<?php elseif ( $val ) : ?>
											<img class="rks-cert-design__image rks-cert-design__image--<?php echo esc_attr( $key ); ?>" src="<?php echo esc_url( $val ); ?>" alt="<?php echo esc_attr( $key === 'qr_code' ? 'QR' : (string) $cert['holder_name'] ); ?>">
										<?php endif; ?>
									<?php else : ?>
										<?php echo esc_html( $val ); ?>
									<?php endif; ?>
								</div>
							<?php endforeach; ?>
						</div>
					<?php else : ?>
					<div class="rks-cert-paper__border">
						<div class="rks-cert-paper__inner">

							<div class="rks-cert-paper__header">
								<div class="rks-cert-paper__brand">
									<span class="rks-cert-paper__brand-rk">Rishikul</span><span class="rks-cert-paper__brand-shiksha">Shiksha</span>
								</div>
								<small>Online Learning Platform &amp; Computer Coaching Institute</small>
							</div>

							<div class="rks-cert-paper__student-photo<?php echo $student_photo_url ? ' rks-cert-paper__student-photo--img' : ''; ?>">
								<div class="rks-cert-paper__student-photo-frame">
									<?php if ( $student_photo_url ) : ?>
										<img src="<?php echo esc_url( $student_photo_url ); ?>" alt="<?php echo esc_attr( (string) $cert['holder_name'] ); ?>">
									<?php else : ?>
										<span><?php echo esc_html( $student_initial ?: 'S' ); ?></span>
									<?php endif; ?>
								</div>
								<small>Student Photo</small>
							</div>

							<div class="rks-cert-paper__body">
								<h1>Certificate of Completion</h1>
								<p class="rks-cert-paper__intro">This is to certify that</p>
								<h2 class="rks-cert-paper__name"><?php echo esc_html( (string) $cert['holder_name'] ); ?></h2>
								<p class="rks-cert-paper__text">has successfully completed the course</p>
								<h3 class="rks-cert-paper__course"><?php echo esc_html( (string) $cert['course_title'] ); ?></h3>
								<p class="rks-cert-paper__text">on <strong><?php echo esc_html( date( 'jS F Y', strtotime( (string) $cert['issue_date'] ) ) ); ?></strong></p>
							</div>

							<div class="rks-cert-paper__footer">
								<div class="rks-cert-paper__sign">
									<div class="rks-cert-paper__sign-line"></div>
									<small>Chairman, Rishikul Shiksha</small>
								</div>

								<div class="rks-cert-paper__seal">
									<div class="rks-cert-paper__seal-circle">
										<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
										<small>Rishikul Shiksha<br>Verified</small>
									</div>
									<div class="rks-cert-paper__qr">
										<img src="<?php echo esc_url( $qr_url ); ?>" alt="Scan to verify">
										<small>Scan to Verify</small>
									</div>
								</div>

								<div class="rks-cert-paper__sign">
									<div class="rks-cert-paper__sign-line"></div>
									<small><?php echo esc_html( $branch ? $branch['name'] : 'Issuing Branch' ); ?></small>
								</div>
							</div>

							<div class="rks-cert-paper__verify">
								<div>
									<small>Certificate ID</small>
									<code><?php echo esc_html( (string) $cert['unique_id'] ); ?></code>
								</div>
								<div>
									<small>Verify Online</small>
									<code><?php echo esc_html( $verify_url ); ?></code>
								</div>
							</div>

						</div>
					</div>
					<?php endif; ?>
				</div>

				<!-- RIGHT: actions + share sidebar -->
				<aside class="rks-cert-sidebar no-print">

					<div class="rks-cert-sidebar__card">
						<div class="rks-cert-sidebar__verified">
							<span class="rks-cert-sidebar__verified-badge">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4"/><path d="M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9c2.5 0 4.76 1.02 6.4 2.66"/></svg>
							</span>
							<div>
								<strong>Verified</strong>
								<small>Issued by Rishikul Shiksha</small>
							</div>
						</div>

						<button onclick="window.print()" class="rks-btn rks-btn--primary rks-cert-sidebar__btn" type="button">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
							Print / Save PDF
						</button>
						<a href="<?php echo esc_url( $verify_url ); ?>" class="rks-btn rks-btn--ghost rks-cert-sidebar__btn" target="_blank" rel="noopener">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
							Verify Online
						</a>
					</div>

					<div class="rks-cert-sidebar__card">
						<h4 class="rks-cert-sidebar__title">Share This Certificate</h4>

						<div class="rks-share-grid">
							<a class="rks-share-tile rks-share-tile--wa" href="https://wa.me/?text=<?php echo urlencode( $share_text . ' ' . $verify_url ); ?>" target="_blank" rel="noopener" aria-label="Share on WhatsApp">
								<svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M.057 24l1.687-6.163a11.867 11.867 0 0 1-1.587-5.946C.16 5.335 5.495 0 12.05 0a11.817 11.817 0 0 1 8.413 3.488 11.824 11.824 0 0 1 3.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892a11.9 11.9 0 0 1-5.688-1.448L.057 24zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884a9.86 9.86 0 0 0 1.516 5.26L3.5 19.85l1.654-1.657zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.296-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
								<span>WhatsApp</span>
							</a>
							<a class="rks-share-tile rks-share-tile--fb" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode( $verify_url ); ?>" target="_blank" rel="noopener" aria-label="Share on Facebook">
								<svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12c0 4.84 3.44 8.87 8 9.8V15H8v-3h2V9.5C10 7.57 11.57 6 13.5 6H16v3h-2c-.55 0-1 .45-1 1v2h3v3h-3v6.95c5.05-.5 9-4.76 9-9.95z"/></svg>
								<span>Facebook</span>
							</a>
							<a class="rks-share-tile rks-share-tile--tw" href="https://twitter.com/intent/tweet?url=<?php echo urlencode( $verify_url ); ?>&text=<?php echo urlencode( $share_text ); ?>" target="_blank" rel="noopener" aria-label="Share on X">
								<svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
								<span>X / Twitter</span>
							</a>
							<a class="rks-share-tile rks-share-tile--li" href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode( $verify_url ); ?>" target="_blank" rel="noopener" aria-label="Share on LinkedIn">
								<svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M19 3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14zM8 17v-7H6v7h2zM7 8.5A1.5 1.5 0 1 0 7 5.5a1.5 1.5 0 0 0 0 3zM18 17v-4.2c0-2-1-3-2.6-3-1.3 0-1.9.7-2.2 1.2V10h-2v7h2v-3.8c0-1 .2-2 1.4-2s1.4 1.1 1.4 2V17h2z"/></svg>
								<span>LinkedIn</span>
							</a>
							<button type="button" id="rks-share-copy" class="rks-share-tile rks-share-tile--copy" aria-label="Copy link">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
								<span>Copy Link</span>
							</button>
							<button type="button" id="rks-share-native" class="rks-share-tile rks-share-tile--more" aria-label="More share options" style="display:none">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
								<span>More</span>
							</button>
						</div>
					</div>

					<div class="rks-cert-sidebar__meta">
						<small>Certificate ID</small>
						<code><?php echo esc_html( (string) $cert['unique_id'] ); ?></code>
					</div>

				</aside>

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
