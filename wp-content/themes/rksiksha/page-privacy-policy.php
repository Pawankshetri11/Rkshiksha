<?php
/**
 * Template Name: Privacy Policy
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
$updated = 'May 2026';
?>

<div class="rks-policy">

	<section class="rks-policy__hero">
		<div class="rks-container">
			<div class="rks-policy__hero-inner">
				<nav class="rks-policy__breadcrumb" aria-label="Breadcrumb">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a> &rsaquo;
					<span>Privacy Policy</span>
				</nav>
				<span class="rks-policy__eyebrow">
					<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
					Your Data, Your Trust
				</span>
				<h1 class="rks-policy__title">Privacy Policy</h1>
				<p class="rks-policy__tagline">Rishikul Shiksha Career Academy is committed to protecting your personal data. This policy explains what information we collect, why we collect it, and the control you have over your data.</p>
				<div class="rks-policy__meta">
					<span class="rks-policy__meta-item">
						<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
						Updated <strong><?php echo esc_html( $updated ); ?></strong>
					</span>
					<span class="rks-policy__meta-item">
						<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
						DPDP Act 2023 compliant
					</span>
				</div>
			</div>
		</div>
	</section>

	<div class="rks-policy__body">
		<div class="rks-policy__wrap">

			<aside class="rks-policy__toc">
				<span class="rks-policy__toc-label">On this page</span>
				<ol>
					<li><a href="#info-collect">Information We Collect</a></li>
					<li><a href="#how-use">How We Use Your Data</a></li>
					<li><a href="#sharing">Sharing &amp; Disclosure</a></li>
					<li><a href="#payments">Payments &amp; Financial Data</a></li>
					<li><a href="#cookies">Cookies &amp; Tracking</a></li>
					<li><a href="#retention">Data Retention</a></li>
					<li><a href="#rights">Your Rights</a></li>
					<li><a href="#security">Security Measures</a></li>
					<li><a href="#children">Children's Privacy</a></li>
					<li><a href="#changes">Changes to Policy</a></li>
					<li><a href="#contact">Contact Us</a></li>
				</ol>
			</aside>

			<div class="rks-policy__content">

				<p class="rks-policy__intro">
					<strong>This Privacy Policy</strong> describes the relationship between you and Rishikul Shiksha Career Academy ("we", "us", "RK Shiksha") regarding the handling of your personal information. It applies whenever you visit <a href="<?php echo esc_url( home_url( '/' ) ); ?>">rkshiksha.com</a>, purchase a course, or apply for admission. We have written it clearly so you know exactly what data we collect and what we do with it.
				</p>

				<section class="rks-policy__section" id="info-collect">
					<h2><span class="rks-policy__num">1</span>Information We Collect</h2>
					<p>We collect only the information necessary to deliver quality education services to you.</p>

					<h3>Personal Information</h3>
					<ul>
						<li><strong>Identity:</strong> Full name, date of birth, gender, qualification</li>
						<li><strong>Contact:</strong> Email address, mobile number, postal address, city, state</li>
						<li><strong>Account:</strong> Username, encrypted password, profile photo (optional)</li>
						<li><strong>Educational:</strong> Course preferences, enrollment history, lesson progress, quiz attempts, certificates earned</li>
					</ul>

					<h3>Payment Information</h3>
					<ul>
						<li>UPI ID, transaction reference (UTR), payment screenshot</li>
						<li>For Razorpay and Cashfree gateways, card details are captured directly by the gateway. We never store them.</li>
					</ul>

					<h3>Technical &amp; Usage Data</h3>
					<ul>
						<li>IP address, browser type, device information, operating system</li>
						<li>Pages visited, time spent, click patterns (for site improvement)</li>
						<li>Login timestamps and session information</li>
					</ul>

					<div class="rks-policy__callout">
						<span class="rks-policy__callout-icon">
							<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
						</span>
						<div class="rks-policy__callout-body">
							<strong>Sensitive Data</strong>
							<p>We do <strong>not</strong> collect Aadhaar numbers, PAN, biometric data, or health records. If any additional identity documents are required during admission (for example, ID proof for certificate issuance), they are deleted immediately after verification.</p>
						</div>
					</div>
				</section>

				<section class="rks-policy__section" id="how-use">
					<h2><span class="rks-policy__num">2</span>How We Use Your Data</h2>
					<p>Your data is used only for the following purposes:</p>
					<ul>
						<li>Creating your account and providing access to the LMS</li>
						<li>Completing admission, verifying payments, and issuing certificates and ID cards</li>
						<li>Sending course content, schedules, exam reminders, and important updates by email or SMS</li>
						<li>Handling support tickets and responding to your queries</li>
						<li>Improving website usability and preventing fraud</li>
						<li>Meeting legal requirements (Income Tax, GST, court orders where applicable)</li>
					</ul>
					<p>We <strong>never sell your data</strong> to marketing companies. Any promotional content is limited to our own services, and you can unsubscribe at any time.</p>
				</section>

				<section class="rks-policy__section" id="sharing">
					<h2><span class="rks-policy__num">3</span>Sharing &amp; Disclosure</h2>
					<p>Your personal information is shared only in the following cases:</p>
					<dl class="rks-policy__defs">
						<dt>Service Providers</dt>
						<dd>Hosting providers, email delivery services (Brevo, SendGrid), and payment gateways (Razorpay, Cashfree) — only to deliver the service. All providers operate under GDPR or DPDP-compliant data processing agreements.</dd>

						<dt>Franchise Partners</dt>
						<dd>If you enroll at a franchise branch, the authorized branch admin can view your contact and course information strictly for operational purposes.</dd>

						<dt>Legal Compliance</dt>
						<dd>Where required by a court order, government investigation, or fraud prevention activity.</dd>

						<dt>Business Transfer</dt>
						<dd>In the event of a merger or acquisition, you will be given prior notice and an opt-out option.</dd>
					</dl>
				</section>

				<section class="rks-policy__section" id="payments">
					<h2><span class="rks-policy__num">4</span>Payments &amp; Financial Data</h2>
					<p>We support three payment methods:</p>
					<ul>
						<li><strong>UPI QR (Manual):</strong> You pay via UPI and submit a screenshot plus UTR. This data is stored in our encrypted private storage and accessible only to authorized admins.</li>
						<li><strong>Razorpay:</strong> Card and net banking details are processed on Razorpay's PCI-DSS Level 1 certified servers. We only receive the payment status.</li>
						<li><strong>Cashfree:</strong> Similarly, we only receive the payment session ID and final status.</li>
					</ul>
					<p>We <strong>never</strong> store your card number, CVV, or bank password.</p>
				</section>

				<section class="rks-policy__section" id="cookies">
					<h2><span class="rks-policy__num">5</span>Cookies &amp; Tracking</h2>
					<p>Our website uses a small number of cookies. Full details are available on our <a href="<?php echo esc_url( home_url( '/cookie-policy/' ) ); ?>">Cookie Policy</a> page. In short:</p>
					<ul>
						<li><strong>Essential:</strong> Login session and security tokens (required)</li>
						<li><strong>Functional:</strong> Language, theme, and course progress preferences</li>
						<li><strong>Analytics:</strong> Anonymous traffic patterns used only to improve the site</li>
					</ul>
				</section>

				<section class="rks-policy__section" id="retention">
					<h2><span class="rks-policy__num">6</span>Data Retention</h2>
					<ul>
						<li>As long as your account is active, your data remains securely stored</li>
						<li>Inactive accounts: retained for up to 3 years, then automatically deleted</li>
						<li>Certificates and transcripts: retained for life so verification remains possible</li>
						<li>Payment records: retained for 8 years (mandatory under the Income Tax Act)</li>
						<li>Support tickets: retained for 2 years</li>
					</ul>
				</section>

				<section class="rks-policy__section" id="rights">
					<h2><span class="rks-policy__num">7</span>Your Rights (DPDP Act 2023)</h2>
					<p>Under the Indian Digital Personal Data Protection Act, you have the following rights:</p>
					<ul>
						<li><strong>Access:</strong> View all data we hold about you (by request)</li>
						<li><strong>Correction:</strong> Correct inaccurate or outdated information</li>
						<li><strong>Erasure:</strong> Request account deletion (subject to legal retention requirements)</li>
						<li><strong>Withdraw Consent:</strong> Stop marketing communication at any time</li>
						<li><strong>Grievance:</strong> Raise concerns with our Data Protection Officer</li>
					</ul>
					<p>To exercise any of these rights, email <a href="mailto:info@rkshiksha.com">info@rkshiksha.com</a>. We respond within 30 days.</p>
				</section>

				<section class="rks-policy__section" id="security">
					<h2><span class="rks-policy__num">8</span>Security Measures</h2>
					<ul>
						<li>HTTPS encryption (SSL/TLS) across the entire website</li>
						<li>Passwords stored using bcrypt hashing — never in plain text</li>
						<li>Payment screenshots kept in a private, auth-gated directory</li>
						<li>Role-based access control: branch admins can only see data for their own branch</li>
						<li>Rate-limiting and nonce protection on all forms (CSRF prevention)</li>
						<li>Regular security audits and backups</li>
					</ul>
					<p>Despite our best efforts, no internet service can guarantee 100% security. If you suspect any unauthorized access or breach, please contact <a href="mailto:info@rkshiksha.com">info@rkshiksha.com</a> immediately.</p>
				</section>

				<section class="rks-policy__section" id="children">
					<h2><span class="rks-policy__num">9</span>Children's Privacy</h2>
					<p>Our services are not intended for children under 13 years of age. If you are under 18, enrollment requires consent from a parent or guardian. We do not knowingly collect data from children under 13; if such data is collected by mistake, it is deleted promptly.</p>
				</section>

				<section class="rks-policy__section" id="changes">
					<h2><span class="rks-policy__num">10</span>Changes to This Policy</h2>
					<p>This policy may be updated from time to time. For material changes, we will notify you by email at least 30 days in advance. The "Updated" date at the top reflects the latest revision, and this page always shows the current version.</p>
				</section>

				<section class="rks-policy__section" id="contact">
					<h2><span class="rks-policy__num">11</span>Contact Us</h2>
					<p>For any privacy-related question, complaint, or request, please contact:</p>
					<dl class="rks-policy__defs">
						<dt>Data Protection Officer</dt>
						<dd>Rishikul Shiksha Career Academy<br>Brahamdutt Chowk, Barotiwala, Vikasnagar<br>Dehradun, Uttarakhand 248198</dd>

						<dt>Email</dt>
						<dd><a href="mailto:info@rkshiksha.com">info@rkshiksha.com</a></dd>

						<dt>Phone</dt>
						<dd><a href="tel:+918979775664">+91 89797 75664</a> (Mon–Sat, 9 AM – 7 PM)</dd>
					</dl>
				</section>

				<div class="rks-policy__contact">
					<span class="rks-policy__contact-icon">
						<svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
					</span>
					<div>
						<h3>Have a question?</h3>
						<p>Anything related to privacy — we are here to help. Replies within 24 hours.</p>
					</div>
					<div class="rks-policy__contact-actions">
						<a href="mailto:info@rkshiksha.com" class="rks-btn rks-btn--primary">Email Us</a>
						<a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="rks-btn">Contact Form</a>
					</div>
				</div>

			</div>
		</div>
	</div>
</div>

<?php get_footer(); ?>
