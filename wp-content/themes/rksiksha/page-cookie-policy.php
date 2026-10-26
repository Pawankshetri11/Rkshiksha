<?php
/**
 * Template Name: Cookie Policy
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
					<span>Cookie Policy</span>
				</nav>
				<span class="rks-policy__eyebrow">
					<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8.5 8.5h.01"/><path d="M15.5 8.5h.01"/><path d="M15 15h.01"/><path d="M8 13h.01"/></svg>
					Transparent Tracking
				</span>
				<h1 class="rks-policy__title">Cookie Policy</h1>
				<p class="rks-policy__tagline">What cookies are, which ones we use, why we use them, and how you can control them — all explained clearly here.</p>
				<div class="rks-policy__meta">
					<span class="rks-policy__meta-item">
						<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
						Updated <strong><?php echo esc_html( $updated ); ?></strong>
					</span>
					<span class="rks-policy__meta-item">
						<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
						Privacy-respecting
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
					<li><a href="#what">What are Cookies?</a></li>
					<li><a href="#why">Why We Use Them</a></li>
					<li><a href="#types">Types We Use</a></li>
					<li><a href="#third-party">Third-Party Cookies</a></li>
					<li><a href="#duration">Cookie Lifetimes</a></li>
					<li><a href="#manage">How to Manage</a></li>
					<li><a href="#dnt">Do Not Track</a></li>
					<li><a href="#updates">Policy Updates</a></li>
					<li><a href="#contact">Contact</a></li>
				</ol>
			</aside>

			<div class="rks-policy__content">

				<p class="rks-policy__intro">
					<strong>Our website uses cookies.</strong> Some are essential, others are optional. These small text files are stored in your browser and help us deliver a better service. We do not run advertising trackers and we do not sell your data to third-party advertisers.
				</p>

				<section class="rks-policy__section" id="what">
					<h2><span class="rks-policy__num">1</span>What are Cookies?</h2>
					<p>Cookies are small text files that a website saves on your device (laptop, mobile, tablet) when you visit it. They help us remember:</p>
					<ul>
						<li>Who you are (your login session)</li>
						<li>Your preferences (language, theme)</li>
						<li>Course progress and LMS state</li>
						<li>Temporary state similar to a shopping cart</li>
					</ul>
					<p>Cookies cannot damage your device, nor can they access your personal files. They communicate only with our website.</p>
				</section>

				<section class="rks-policy__section" id="why">
					<h2><span class="rks-policy__num">2</span>Why We Use Cookies</h2>
					<ul>
						<li><strong>Login Session:</strong> So you don't have to log in on every page</li>
						<li><strong>Security:</strong> CSRF tokens, fraud prevention, and brute-force protection</li>
						<li><strong>Functionality:</strong> Remembering language, theme, and video player position</li>
						<li><strong>Performance:</strong> Improving site speed by analyzing usage patterns</li>
						<li><strong>Personalization:</strong> Recommending relevant courses to you</li>
					</ul>
				</section>

				<section class="rks-policy__section" id="types">
					<h2><span class="rks-policy__num">3</span>Types of Cookies We Use</h2>

					<h3>1. Essential Cookies (Required)</h3>
					<p>These cookies are required for basic site functions and cannot be disabled. Examples:</p>
					<dl class="rks-policy__defs">
						<dt>wordpress_logged_in_*</dt>
						<dd>Maintains your login session. Expires on logout.</dd>

						<dt>wp-settings-*</dt>
						<dd>WordPress admin preferences.</dd>

						<dt>rks_*_nonce</dt>
						<dd>CSRF protection — keeps form submissions secure.</dd>

						<dt>PHPSESSID</dt>
						<dd>Server-side session identifier.</dd>
					</dl>

					<h3>2. Functional Cookies</h3>
					<p>Used to remember your preferences:</p>
					<dl class="rks-policy__defs">
						<dt>rks_lesson_progress</dt>
						<dd>Remembers where you left off in the LMS player.</dd>

						<dt>rks_theme_pref</dt>
						<dd>Stores the light or dark mode preference (if applicable).</dd>

						<dt>rks_dismissed_notices</dt>
						<dd>Tracks which notifications you have dismissed.</dd>
					</dl>

					<h3>3. Analytics Cookies (Optional)</h3>
					<p>We use anonymous analytics to improve the site:</p>
					<dl class="rks-policy__defs">
						<dt>Self-hosted analytics</dt>
						<dd>Tracks page views, click patterns, and course completion rates in aggregated, anonymous form. No personal identification is involved.</dd>
					</dl>

					<div class="rks-policy__callout">
						<span class="rks-policy__callout-icon">
							<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
						</span>
						<div class="rks-policy__callout-body">
							<strong>No Advertising Cookies</strong>
							<p>We do not use Google Ads, Facebook Pixel, or any third-party advertising cookies. Your browsing history is never sold to anyone.</p>
						</div>
					</div>
				</section>

				<section class="rks-policy__section" id="third-party">
					<h2><span class="rks-policy__num">4</span>Third-Party Cookies</h2>
					<p>Some features use third-party services that may set their own cookies. These are limited to:</p>

					<dl class="rks-policy__defs">
						<dt>YouTube / Vimeo (Video Player)</dt>
						<dd>When you play lesson videos, YouTube or Vimeo's player sets its own cookies. We try to enable "privacy-enhanced mode" wherever possible.</dd>

						<dt>Razorpay / Cashfree (Payment Checkout)</dt>
						<dd>During payment, the gateway sets cookies for fraud prevention. These expire once the payment is complete.</dd>

						<dt>Font Loading</dt>
						<dd>Google Fonts (Poppins, Lora) are loaded — Google does not run analytics on font requests.</dd>
					</dl>
					<p>These services have their own privacy and cookie policies, which we recommend you also review.</p>
				</section>

				<section class="rks-policy__section" id="duration">
					<h2><span class="rks-policy__num">5</span>Cookie Lifetimes</h2>
					<dl class="rks-policy__defs">
						<dt>Session Cookies</dt>
						<dd>Deleted when you close your browser (for example, PHPSESSID, login nonces)</dd>

						<dt>Persistent Cookies</dt>
						<dd>Stay for a fixed time, usually up to 30 days. For example, the "Remember Me" cookie lasts for 14 days.</dd>

						<dt>Long-term Cookies</dt>
						<dd>Stay up to 1 year — used only for functional preferences such as theme or language.</dd>
					</dl>
				</section>

				<section class="rks-policy__section" id="manage">
					<h2><span class="rks-policy__num">6</span>How to Manage Cookies</h2>

					<h3>Browser Settings</h3>
					<p>You can control cookies through your browser settings:</p>
					<ul>
						<li><strong>Chrome:</strong> Settings → Privacy and security → Cookies and other site data</li>
						<li><strong>Firefox:</strong> Settings → Privacy &amp; Security → Cookies and Site Data</li>
						<li><strong>Safari:</strong> Preferences → Privacy → Manage Website Data</li>
						<li><strong>Edge:</strong> Settings → Cookies and site permissions → Cookies and site data</li>
						<li><strong>Mobile (Chrome/Safari):</strong> Settings → Site settings → Cookies</li>
					</ul>

					<h3>Delete Existing Cookies</h3>
					<p>Clearing your browser history will remove all our cookies. You will need to log in again afterward.</p>

					<div class="rks-policy__callout">
						<span class="rks-policy__callout-icon">
							<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/></svg>
						</span>
						<div class="rks-policy__callout-body">
							<strong>Essential Cookies Cannot Be Disabled</strong>
							<p>If you block all cookies, our website will not work properly. Login, payments, and LMS access will fail. Essential cookies must be allowed.</p>
						</div>
					</div>
				</section>

				<section class="rks-policy__section" id="dnt">
					<h2><span class="rks-policy__num">7</span>Do Not Track (DNT)</h2>
					<p>Our website respects the browser "Do Not Track" signal. If your browser sends a DNT signal, we do not set analytics cookies. Essential cookies still load so the site remains functional.</p>
				</section>

				<section class="rks-policy__section" id="updates">
					<h2><span class="rks-policy__num">8</span>Policy Updates</h2>
					<p>This cookie policy may be updated as we add new features or change tracking mechanisms. For material changes, we will show a notice at the top of the website.</p>
					<p>The latest version is always available on this page, and you can check back at any time.</p>
				</section>

				<section class="rks-policy__section" id="contact">
					<h2><span class="rks-policy__num">9</span>Contact Us</h2>
					<p>For any cookie-related questions:</p>
					<dl class="rks-policy__defs">
						<dt>Email</dt>
						<dd><a href="mailto:info@rkshiksha.com">info@rkshiksha.com</a></dd>

						<dt>Phone</dt>
						<dd><a href="tel:+918979775664">+91 89797 75664</a></dd>

						<dt>Address</dt>
						<dd>Rishikul Shiksha Career Academy<br>Brahamdutt Chowk, Barotiwala<br>Vikasnagar, Dehradun, UK 248198</dd>
					</dl>
					<p>Related: <a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>">Privacy Policy</a> · <a href="<?php echo esc_url( home_url( '/terms/' ) ); ?>">Terms of Service</a></p>
				</section>

				<div class="rks-policy__contact">
					<span class="rks-policy__contact-icon">
						<svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8.5 8.5h.01"/><path d="M15.5 8.5h.01"/><path d="M15 15h.01"/><path d="M8 13h.01"/></svg>
					</span>
					<div>
						<h3>Confused about cookies?</h3>
						<p>Ask us anything — we are here to explain.</p>
					</div>
					<div class="rks-policy__contact-actions">
						<a href="mailto:info@rkshiksha.com" class="rks-btn rks-btn--primary">Email Us</a>
						<a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="rks-btn">Contact</a>
					</div>
				</div>

			</div>
		</div>
	</div>
</div>

<?php get_footer(); ?>
