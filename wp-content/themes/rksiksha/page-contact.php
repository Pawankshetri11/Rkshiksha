<?php
/**
 * Template Name: Contact Page
 */
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

/* ---- inline SVGs (no .rks-icon class so global margin doesn't apply) ---- */
$svg_pin   = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>';
$svg_phone = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13.5a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2.69h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.69 2.81a2 2 0 0 1-.45 2.11L7.91 10.5a16 16 0 0 0 5.61 5.61l1.27-.77a2 2 0 0 1 2.11-.45c.91.33 1.85.57 2.81.69A2 2 0 0 1 22 17.92z"/></svg>';
$svg_mail  = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>';
$svg_clock = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 16 14"/></svg>';
$svg_wa    = '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M.057 24l1.687-6.163a11.867 11.867 0 0 1-1.587-5.946C.16 5.335 5.495 0 12.05 0a11.817 11.817 0 0 1 8.413 3.488 11.824 11.824 0 0 1 3.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892a11.9 11.9 0 0 1-5.688-1.448L.057 24zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884a9.86 9.86 0 0 0 1.516 5.26L3.5 19.85l1.654-1.657zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.296-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>';
$svg_arrow = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>';
$svg_check = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
$svg_chev  = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>';

$svg_fb = '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12c0 4.84 3.44 8.87 8 9.8V15H8v-3h2V9.5C10 7.57 11.57 6 13.5 6H16v3h-2c-.55 0-1 .45-1 1v2h3v3h-3v6.95c5.05-.5 9-4.76 9-9.95z"/></svg>';
$svg_ig = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>';
$svg_yt = '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M23.5 6.2a3 3 0 0 0-2.1-2.1C19.5 3.5 12 3.5 12 3.5s-7.5 0-9.4.6A3 3 0 0 0 .5 6.2C0 8.1 0 12 0 12s0 3.9.5 5.8a3 3 0 0 0 2.1 2.1c1.9.6 9.4.6 9.4.6s7.5 0 9.4-.6a3 3 0 0 0 2.1-2.1c.5-1.9.5-5.8.5-5.8s0-3.9-.5-5.8zM9.6 15.6V8.4l6.2 3.6-6.2 3.6z"/></svg>';
?>

<main id="primary" class="rks-contact">

	<!-- ============ HERO ============ -->
	<section class="rks-contact__hero">
		<div class="rks-container">
			<nav class="rks-contact__crumb" aria-label="Breadcrumb">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
				<span>/</span>
				<span aria-current="page">Contact</span>
			</nav>
			<span class="rks-eyebrow">Get in Touch</span>
			<h1>We'd love to hear from you</h1>
			<p>Course advice, admissions, franchise queries — our team replies within 24 hours, and within minutes on WhatsApp.</p>

			<div class="rks-contact__hero-actions">
				<a href="https://wa.me/918979775664" target="_blank" rel="noopener" class="rks-contact__wa-cta">
					<?php echo $svg_wa; ?> Chat on WhatsApp
				</a>
				<a href="tel:+918979775664" class="rks-contact__call-cta">
					<?php echo $svg_phone; ?> +91 89797 75664
				</a>
			</div>
		</div>
	</section>


	<!-- ============ INFO STRIP ============ -->
	<section class="rks-contact__strip">
		<div class="rks-container">
			<div class="rks-contact__strip-grid">
				<a href="https://maps.google.com/?q=Brahamdutt+Chowk+Barotiwala+Vikasnagar+Dehradun" target="_blank" rel="noopener" class="rks-contact__strip-item">
					<span class="rks-contact__strip-ic"><?php echo $svg_pin; ?></span>
					<div>
						<h4>Visit Us</h4>
						<p>Brahamdutt Chowk, Barotiwala,<br>Vikasnagar, Dehradun, UK 248198</p>
					</div>
				</a>
				<a href="tel:+918979775664" class="rks-contact__strip-item">
					<span class="rks-contact__strip-ic"><?php echo $svg_phone; ?></span>
					<div>
						<h4>Call Us</h4>
						<p>+91 89797 75664<br><small>Mon &mdash; Sat, 9 AM &mdash; 7 PM</small></p>
					</div>
				</a>
				<a href="mailto:info@rkshiksha.com" class="rks-contact__strip-item">
					<span class="rks-contact__strip-ic"><?php echo $svg_mail; ?></span>
					<div>
						<h4>Email Us</h4>
						<p>info@rkshiksha.com<br><small>We reply within 24 hours</small></p>
					</div>
				</a>
				<a href="https://wa.me/918979775664" target="_blank" rel="noopener" class="rks-contact__strip-item rks-contact__strip-item--wa">
					<span class="rks-contact__strip-ic rks-contact__strip-ic--wa"><?php echo $svg_wa; ?></span>
					<div>
						<h4>WhatsApp</h4>
						<p>+91 89797 75664<br><small>Replies in minutes</small></p>
					</div>
				</a>
			</div>
		</div>
	</section>


	<!-- ============ FORM + ASIDE ============ -->
	<section class="rks-contact__main">
		<div class="rks-container rks-contact__main-grid">

			<div class="rks-contact__form-wrap">
				<header>
					<span class="rks-eyebrow">Send a Message</span>
					<h2>Tell us how we can help</h2>
					<p>Fill the form below — a real human will get back within 24 hours.</p>
				</header>

				<?php
				$rks_lead_status = isset( $_GET['lead'] ) ? sanitize_key( wp_unslash( (string) $_GET['lead'] ) ) : '';
				if ( 'success' === $rks_lead_status ) :
				?>
					<div class="rks-contact__alert rks-contact__alert--success" style="padding:14px 18px;border-radius:10px;background:#e8f7ee;border:1px solid #b6e3c6;color:#1b6a3a;margin-bottom:18px;">
						<strong>Thanks!</strong> We&rsquo;ve received your message and will reply within 24 hours.
					</div>
				<?php elseif ( 'error' === $rks_lead_status ) : ?>
					<div class="rks-contact__alert rks-contact__alert--error" style="padding:14px 18px;border-radius:10px;background:#fdecec;border:1px solid #f5b5b5;color:#8a1c1c;margin-bottom:18px;">
						<strong>Sorry,</strong> we couldn&rsquo;t send your message. Please check your details and try again.
					</div>
				<?php endif; ?>

				<form class="rks-contact__form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST" novalidate>
					<input type="hidden" name="action" value="rks_submit_contact">
					<?php wp_nonce_field( 'rks_submit_contact', '_rks_nonce' ); ?>
					<input type="text" name="rks_hp" value="" style="position:absolute;left:-9999px;" tabindex="-1" autocomplete="off" aria-hidden="true">
					<div class="rks-contact__row">
						<div class="rks-contact__field">
							<label for="rks-c-name">Full Name <span>*</span></label>
							<input type="text" id="rks-c-name" name="rks_name" placeholder="e.g. Aanya Sharma" required>
						</div>
						<div class="rks-contact__field">
							<label for="rks-c-email">Email Address <span>*</span></label>
							<input type="email" id="rks-c-email" name="rks_email" placeholder="you@example.com" required>
						</div>
					</div>

					<div class="rks-contact__row">
						<div class="rks-contact__field">
							<label for="rks-c-phone">Phone Number</label>
							<input type="tel" id="rks-c-phone" name="rks_phone" placeholder="+91 98765 43210">
						</div>
						<div class="rks-contact__field">
							<label for="rks-c-subject">I'm asking about</label>
							<select id="rks-c-subject" name="rks_subject">
								<option value="general">General Enquiry</option>
								<option value="course">Course / Admission</option>
								<option value="franchise">Franchise Opportunity</option>
								<option value="counselling">University Counselling</option>
								<option value="support">Student Support</option>
								<option value="other">Something else</option>
							</select>
						</div>
					</div>

					<div class="rks-contact__field">
						<label for="rks-c-msg">Your Message <span>*</span></label>
						<textarea id="rks-c-msg" name="rks_message" rows="6" placeholder="Tell us a little about what you're looking for..." required></textarea>
					</div>

					<label class="rks-contact__consent">
						<input type="checkbox" required>
						<span>I agree to be contacted by Rishikul Shiksha about my enquiry.</span>
					</label>

					<button type="submit" class="rks-contact__submit">
						Send Message <?php echo $svg_arrow; ?>
					</button>
				</form>
			</div>

			<aside class="rks-contact__aside">
				<div class="rks-contact__card">
					<h3>Office Hours</h3>
					<ul class="rks-contact__hours">
						<li><span>Monday &mdash; Saturday</span><strong>9:00 AM &mdash; 7:00 PM</strong></li>
						<li class="is-closed"><span>Sunday</span><strong>Closed</strong></li>
					</ul>
					<div class="rks-contact__open-now"><?php echo $svg_clock; ?> Open now &middot; replies fast</div>
				</div>

				<div class="rks-contact__card">
					<h3>Follow Us</h3>
					<p>Stay in the loop with new courses, success stories, and tips.</p>
					<div class="rks-contact__socials">
						<a href="https://www.facebook.com/share/1H6UzYmRHZ/" target="_blank" rel="noopener" aria-label="Facebook" class="rks-contact__social rks-contact__social--fb"><?php echo $svg_fb; ?></a>
						<a href="https://www.instagram.com/ravi.belwal.555" target="_blank" rel="noopener" aria-label="Instagram" class="rks-contact__social rks-contact__social--ig"><?php echo $svg_ig; ?></a>
						<a href="https://youtube.com/@rkbelwal999" target="_blank" rel="noopener" aria-label="YouTube" class="rks-contact__social rks-contact__social--yt"><?php echo $svg_yt; ?></a>
						<a href="https://wa.me/918979775664" target="_blank" rel="noopener" aria-label="WhatsApp" class="rks-contact__social rks-contact__social--wa"><?php echo $svg_wa; ?></a>
					</div>
				</div>

				<div class="rks-contact__card rks-contact__card--dark">
					<h3>Prefer to talk?</h3>
					<p>WhatsApp is the fastest way to reach our team &mdash; we usually reply in minutes.</p>
					<a href="https://wa.me/918979775664" target="_blank" rel="noopener" class="rks-contact__wa-cta rks-contact__wa-cta--block">
						<?php echo $svg_wa; ?> Chat on WhatsApp
					</a>
				</div>
			</aside>

		</div>
	</section>


	<!-- ============ MAP ============ -->
	<section class="rks-contact__map-section">
		<div class="rks-container">
			<div class="rks-contact__map-wrap">
				<div class="rks-contact__map-card">
					<span class="rks-eyebrow">Find Us</span>
					<h3>Rishikul Shiksha Campus</h3>
					<p>Brahamdutt Chowk, Barotiwala,<br>Vikasnagar, Dehradun, UK 248198</p>
					<ul>
						<li><span class="rks-contact__tick"><?php echo $svg_check; ?></span> Walk-in demo classes daily</li>
						<li><span class="rks-contact__tick"><?php echo $svg_check; ?></span> Free parking on premises</li>
						<li><span class="rks-contact__tick"><?php echo $svg_check; ?></span> Easy access from Vikasnagar main road</li>
					</ul>
					<a href="https://maps.google.com/?q=Brahamdutt+Chowk+Barotiwala+Vikasnagar+Dehradun" target="_blank" rel="noopener" class="rks-contact__map-btn">
						Get Directions <?php echo $svg_arrow; ?>
					</a>
				</div>
				<div class="rks-contact__map-iframe">
					<iframe
						src="https://www.google.com/maps?q=Brahamdutt+Chowk+Barotiwala+Vikasnagar+Dehradun+248198&output=embed"
						width="100%" height="100%" style="border:0;" allowfullscreen=""
						loading="lazy" referrerpolicy="no-referrer-when-downgrade"
						title="Rishikul Shiksha Office Location - Vikasnagar, Dehradun"></iframe>
				</div>
			</div>
		</div>
	</section>


	<!-- ============ FAQ ============ -->
	<section class="rks-contact__faq">
		<div class="rks-container">
			<header class="rks-contact__faq-head">
				<span class="rks-eyebrow">FAQ</span>
				<h2>Frequently asked questions</h2>
				<p>Quick answers to questions we hear most often. Can't find what you need? Use the form above.</p>
			</header>

			<div class="rks-contact__faq-list">
				<details class="rks-contact__faq-item" open>
					<summary>How do I enroll in a course?<span class="rks-contact__faq-chev"><?php echo $svg_chev; ?></span></summary>
					<div class="rks-contact__faq-a">Browse the Courses page, pick a program and click "Enroll Now". Pay online via UPI and your dashboard unlocks instantly. Our team also reaches out within 24 hours to help you get started.</div>
				</details>
				<details class="rks-contact__faq-item">
					<summary>Do you offer offline classes?<span class="rks-contact__faq-chev"><?php echo $svg_chev; ?></span></summary>
					<div class="rks-contact__faq-a">Yes &mdash; our offline coaching runs daily and weekend batches at our Vikasnagar campus. Walk in for a free demo or call <a href="tel:+918979775664">+91 89797 75664</a> to book a slot.</div>
				</details>
				<details class="rks-contact__faq-item">
					<summary>How can I get a franchise?<span class="rks-contact__faq-chev"><?php echo $svg_chev; ?></span></summary>
					<div class="rks-contact__faq-a">We offer franchise partnerships across India with low setup costs and full training. Submit the form above with subject "Franchise" and our partnership team will share the brochure, fees and next steps.</div>
				</details>
				<details class="rks-contact__faq-item">
					<summary>What university counselling do you provide?<span class="rks-contact__faq-chev"><?php echo $svg_chev; ?></span></summary>
					<div class="rks-contact__faq-a">We guide students on course selection, university shortlisting, application paperwork, scholarships and admission interviews &mdash; for Indian and overseas universities. The first counselling session is free.</div>
				</details>
				<details class="rks-contact__faq-item">
					<summary>What payment methods do you accept?<span class="rks-contact__faq-chev"><?php echo $svg_chev; ?></span></summary>
					<div class="rks-contact__faq-a">UPI, net banking, debit/credit cards, and easy installments on most premium courses. After payment, a branch admin verifies the transaction and your dashboard unlocks instantly.</div>
				</details>
				<details class="rks-contact__faq-item">
					<summary>Are fees refundable?<span class="rks-contact__faq-chev"><?php echo $svg_chev; ?></span></summary>
					<div class="rks-contact__faq-a">No. Admission, course and service fees are non-refundable once paid. Please confirm the details before making payment.</div>
				</details>
			</div>
		</div>
	</section>

</main>

<?php get_footer(); ?>
