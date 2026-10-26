<?php if ( ! defined( 'ABSPATH' ) ) exit;
$rks_hf = class_exists( '\\RKSiksha\\Core\\Admin\\HeaderFooterPage' )
	? \RKSiksha\Core\Admin\HeaderFooterPage::all()
	: [];
$rks_wa_num = preg_replace( '/[^0-9]/', '', (string) ( $rks_hf['social_whatsapp'] ?? '918979775664' ) );
$rks_phone_link = preg_replace( '/[^0-9+]/', '', (string) ( $rks_hf['footer_phone'] ?? '+918979775664' ) );
?>
</main>

<?php if ( ! ( isset( $_GET['view'] ) && $_GET['view'] === 'quiz-take' ) ) : ?>
<footer class="rks-footer">
	<div class="rks-container rks-footer__grid">

		<div class="rks-footer__col rks-footer__about">
			<?php
			$rks_flogo_id  = (int) ( $rks_hf['logo_footer_id'] ?? 0 );
			$rks_flogo_url = $rks_flogo_id ? wp_get_attachment_image_url( $rks_flogo_id, 'full' ) : '';
			$rks_hlogo_id  = (int) ( $rks_hf['logo_id'] ?? 0 );
			$rks_hlogo_url = $rks_hlogo_id ? wp_get_attachment_image_url( $rks_hlogo_id, 'full' ) : '';
			$rks_footer_logo = $rks_flogo_url ?: $rks_hlogo_url;
			?>
			<?php if ( $rks_footer_logo ) : ?>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="rks-brand__logo rks-brand__logo--light">
					<img src="<?php echo esc_url( $rks_footer_logo ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" style="max-width:170px;height:auto;">
				</a>
			<?php else : ?>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="rks-brand__text rks-brand__text--light">
					<span class="rks-brand__rk">Rishikul</span><span class="rks-brand__shiksha">Shiksha</span>
				</a>
			<?php endif; ?>
			<p class="rks-footer__tag"><?php echo esc_html( $rks_hf['footer_tagline'] ?? '' ); ?></p>

			<div class="rks-footer__contact-card">
				<div class="rks-footer__contact-item">
					<span class="rks-footer__contact-icon">
						<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
					</span>
					<span><?php echo nl2br( esc_html( $rks_hf['footer_address'] ?? '' ) ); ?></span>
				</div>
				<div class="rks-footer__contact-item">
					<span class="rks-footer__contact-icon">
						<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13.5a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2.69h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.69 2.81a2 2 0 0 1-.45 2.11L7.91 10.5a16 16 0 0 0 5.61 5.61l1.27-.77a2 2 0 0 1 2.11-.45c.91.33 1.85.57 2.81.69A2 2 0 0 1 22 17.92z"/></svg>
					</span>
					<a href="tel:<?php echo esc_attr( $rks_phone_link ); ?>"><?php echo esc_html( $rks_hf['footer_phone'] ?? '' ); ?></a>
				</div>
				<div class="rks-footer__contact-item">
					<span class="rks-footer__contact-icon">
						<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
					</span>
					<a href="mailto:<?php echo esc_attr( $rks_hf['footer_email'] ?? '' ); ?>"><?php echo esc_html( $rks_hf['footer_email'] ?? '' ); ?></a>
				</div>
			</div>

			<div class="rks-social">
				<a href="<?php echo esc_url( $rks_hf['social_facebook'] ?? '#' ); ?>" target="_blank" rel="noopener" aria-label="Facebook" class="rks-social__fb">
					<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12c0 4.84 3.44 8.87 8 9.8V15H8v-3h2V9.5C10 7.57 11.57 6 13.5 6H16v3h-2c-.55 0-1 .45-1 1v2h3v3h-3v6.95c5.05-.5 9-4.76 9-9.95z"/></svg>
				</a>
				<a href="<?php echo esc_url( $rks_hf['social_instagram'] ?? '#' ); ?>" target="_blank" rel="noopener" aria-label="Instagram" class="rks-social__ig">
					<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
				</a>
				<a href="<?php echo esc_url( $rks_hf['social_youtube'] ?? '#' ); ?>" target="_blank" rel="noopener" aria-label="YouTube" class="rks-social__yt">
					<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M23.5 6.2a3 3 0 0 0-2.1-2.1C19.5 3.5 12 3.5 12 3.5s-7.5 0-9.4.6A3 3 0 0 0 .5 6.2C0 8.1 0 12 0 12s0 3.9.5 5.8a3 3 0 0 0 2.1 2.1c1.9.6 9.4.6 9.4.6s7.5 0 9.4-.6a3 3 0 0 0 2.1-2.1c.5-1.9.5-5.8.5-5.8s0-3.9-.5-5.8zM9.6 15.6V8.4l6.2 3.6-6.2 3.6z"/></svg>
				</a>
				<a href="https://wa.me/<?php echo esc_attr( $rks_wa_num ); ?>" target="_blank" rel="noopener" aria-label="WhatsApp" class="rks-social__wa">
					<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M.057 24l1.687-6.163a11.867 11.867 0 0 1-1.587-5.946C.16 5.335 5.495 0 12.05 0a11.817 11.817 0 0 1 8.413 3.488 11.824 11.824 0 0 1 3.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892a11.9 11.9 0 0 1-5.688-1.448L.057 24zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884a9.86 9.86 0 0 0 1.516 5.26L3.5 19.85l1.654-1.657zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.296-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
				</a>
			</div>
		</div>

		<div class="rks-footer__col rks-footer__quick">
			<h4>Quick Links</h4>
			<?php
			$rks_footer_menu_id = (int) ( $rks_hf['footer_menu_id'] ?? 0 );
			$rks_fmenu_args = [
				'container'   => false,
				'menu_class'  => '',
				'depth'       => 1,
				'fallback_cb' => false,
			];
			if ( $rks_footer_menu_id > 0 ) {
				$rks_fmenu_args['menu'] = $rks_footer_menu_id;
				wp_nav_menu( $rks_fmenu_args );
			} elseif ( has_nav_menu( 'footer' ) ) {
				$rks_fmenu_args['theme_location'] = 'footer';
				wp_nav_menu( $rks_fmenu_args );
			} else {
				?>
				<ul>
					<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a></li>
					<li><a href="<?php echo esc_url( home_url( '/courses/' ) ); ?>">All Courses</a></li>
					<li><a href="<?php echo esc_url( home_url( '/about/' ) ); ?>">About Us</a></li>
					<li><a href="<?php echo esc_url( home_url( '/coaching/' ) ); ?>">Franchise</a></li>
					<li><a href="<?php echo esc_url( home_url( '/university-counselling/' ) ); ?>">University Counselling</a></li>
					<li><a href="<?php echo esc_url( home_url( '/admission/' ) ); ?>">Admission</a></li>
					<li><a href="<?php echo esc_url( home_url( '/blog/' ) ); ?>">Events &amp; Updates</a></li>
					<li><a href="<?php echo esc_url( home_url( '/gallery/' ) ); ?>">Gallery</a></li>
					<li><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Contact</a></li>
				</ul>
				<?php
			}
			?>
		</div>

		<div class="rks-footer__col rks-footer__verify">
			<h4>Verify &amp; Tools</h4>
			<ul>
				<li>
					<a href="<?php echo esc_url( home_url( '/verify-certificate/' ) ); ?>" class="rks-footer__tool">
						<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l2.4 2.4L18 4l1.6 3.6L23 9.6l-1.6 3.4L23 16.4 19.6 18 18 21.6 14.4 21 12 23 9.6 21 6 21.6 4.4 18 1 16.4l1.6-3.4L1 9.6 4.4 8 6 4.4 9.6 4z"/><polyline points="9 12 11 14 15 10"/></svg>
						Verify Certificate
					</a>
				</li>
				<li>
					<a href="<?php echo esc_url( home_url( '/verify-id/' ) ); ?>" class="rks-footer__tool">
						<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="M5 17a4 4 0 0 1 8 0"/><line x1="15" y1="8" x2="19" y2="8"/><line x1="15" y1="12" x2="19" y2="12"/></svg>
						Verify ID Card
					</a>
				</li>
				<li>
					<a href="<?php echo esc_url( home_url( '/admission/' ) ); ?>" class="rks-footer__tool">
						<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg>
						Take Admission
					</a>
				</li>
				<li>
					<a href="<?php echo esc_url( home_url( '/student-dashboard/' ) ); ?>" class="rks-footer__tool">
						<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"/></svg>
						Student Dashboard
					</a>
				</li>
				<li>
					<a href="<?php echo esc_url( home_url( '/login/' ) ); ?>" class="rks-footer__tool">
						<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
						Login / Register
					</a>
				</li>
			</ul>
		</div>

		<div class="rks-footer__col">
			<h4>Get in Touch</h4>
			<p class="rks-footer__news-tag">Need help with admissions, courses, or franchise? Talk to us directly.</p>

			<a href="https://wa.me/<?php echo esc_attr( $rks_wa_num ); ?>" target="_blank" rel="noopener" class="rks-footer__wa-cta">
				<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M.057 24l1.687-6.163a11.867 11.867 0 0 1-1.587-5.946C.16 5.335 5.495 0 12.05 0a11.817 11.817 0 0 1 8.413 3.488 11.824 11.824 0 0 1 3.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892a11.9 11.9 0 0 1-5.688-1.448L.057 24zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884a9.86 9.86 0 0 0 1.516 5.26L3.5 19.85l1.654-1.657zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.296-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
				Chat on WhatsApp
			</a>

			<div class="rks-footer__hours">
				<h5>Office Hours</h5>
				<p><strong><?php echo esc_html( $rks_hf['office_hours_label_w'] ?? '' ); ?>:</strong> <?php echo esc_html( $rks_hf['office_hours_weekday'] ?? '' ); ?><br><strong><?php echo esc_html( $rks_hf['office_hours_label_e'] ?? '' ); ?>:</strong> <?php echo esc_html( $rks_hf['office_hours_weekend'] ?? '' ); ?></p>
			</div>
		</div>

	</div>

	<div class="rks-footer__bottom">
		<div class="rks-container rks-footer__bottom-inner">
			<span>&copy; <?php echo esc_html( date( 'Y' ) ); ?> <?php echo esc_html( $rks_hf['footer_copyright'] ?? '' ); ?></span>
			<div class="rks-footer__legal">
				<a href="<?php echo esc_url( home_url( '/legal/' ) ); ?>">Legal</a>
				<a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>">Privacy Policy</a>
				<a href="<?php echo esc_url( home_url( '/terms/' ) ); ?>">Terms of Service</a>
				<a href="<?php echo esc_url( home_url( '/refund-policy/' ) ); ?>">Non-Refundable Policy</a>
				<a href="<?php echo esc_url( home_url( '/cookie-policy/' ) ); ?>">Cookies</a>
			</div>
		</div>
	</div>
</footer>
<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>
