<?php
/**
 * Template Name: Coaching & Franchise Page
 */
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

$rks_settings  = (array) get_option( 'rks_settings', [] );
$brochure_id   = (int) ( $rks_settings['franchise_brochure_id'] ?? 0 );
$brochure_url  = $brochure_id ? wp_get_attachment_url( $brochure_id ) : '';
$franchise_register_url = home_url( '/franchise-register/' );

$svg_check    = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
$svg_chev     = '<svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>';
$svg_arrow    = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>';
$svg_building = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2"/><path d="M9 22v-4h6v4"/></svg>';
$svg_users    = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>';
$svg_chart    = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><polyline points="7 14 11 9 14 12 21 5"/></svg>';
$svg_trophy   = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/></svg>';
$svg_shield   = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>';
$svg_sparkle  = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v18M3 12h18M5.6 5.6l12.8 12.8M5.6 18.4l12.8-12.8"/></svg>';
$svg_target   = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>';
$svg_book     = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>';
$svg_quote    = '<svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor"><path d="M9.583 17.321C8.553 16.227 8 15 8 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 0 1-3.5 3.5 3.871 3.871 0 0 1-2.748-1.179zm10 0C18.553 16.227 18 15 18 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 0 1-3.5 3.5 3.871 3.871 0 0 1-2.748-1.179z" transform="translate(-4)"/></svg>';
$svg_dl       = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>';
$svg_phone    = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13.5a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2.69h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.69 2.81a2 2 0 0 1-.45 2.11L7.91 10.5a16 16 0 0 0 5.61 5.61l1.27-.77a2 2 0 0 1 2.11-.45c.91.33 1.85.57 2.81.69A2 2 0 0 1 22 17.92z"/></svg>';
$svg_wa       = '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M.057 24l1.687-6.163a11.867 11.867 0 0 1-1.587-5.946C.16 5.335 5.495 0 12.05 0a11.817 11.817 0 0 1 8.413 3.488 11.824 11.824 0 0 1 3.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892a11.9 11.9 0 0 1-5.688-1.448L.057 24zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884a9.86 9.86 0 0 0 1.516 5.26L3.5 19.85l1.654-1.657zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.296-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>';
?>

<main id="primary" class="rks-fr">

	<!-- ============ HERO (split with image) ============ -->
	<section class="rks-fr__hero">
		<div class="rks-fr__hero-bg" aria-hidden="true">
			<span class="rks-fr__blob rks-fr__blob--a"></span>
			<span class="rks-fr__blob rks-fr__blob--b"></span>
		</div>
		<div class="rks-container rks-fr__hero-grid">

			<div class="rks-fr__hero-text">
				<nav class="rks-fr__crumb" aria-label="Breadcrumb">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
					<span><?php echo $svg_chev; ?></span>
					<span aria-current="page">Franchise</span>
				</nav>
				<span class="rks-eyebrow">Franchise Opportunity</span>
				<h1>Build a future-proof <span>education business</span> in your city</h1>
				<p>Partner with India's fast-growing computer-training brand. We bring 7 Years' Experience, ready curriculum, and end-to-end support &mdash; you bring the local entrepreneurship.</p>
				<div class="rks-fr__hero-actions">
					<a href="<?php echo esc_url( $franchise_register_url ); ?>" class="rks-fr__btn rks-fr__btn--primary">Franchise Register <?php echo $svg_arrow; ?></a>
					<a href="#apply" class="rks-fr__btn rks-fr__btn--ghost">Quick Enquiry</a>
					<a href="https://wa.me/918979775664?text=<?php echo urlencode( 'Hi, I am interested in your franchise opportunity.' ); ?>" target="_blank" rel="noopener" class="rks-fr__btn rks-fr__btn--wa"><?php echo $svg_wa; ?> WhatsApp Us</a>
				</div>
				<ul class="rks-fr__hero-ticks">
					<li><span><?php echo $svg_check; ?></span> Exclusive territory</li>
					<li><span><?php echo $svg_check; ?></span> Ready curriculum</li>
					<li><span><?php echo $svg_check; ?></span> End-to-end support</li>
				</ul>
			</div>

			<aside class="rks-fr__hero-media">
				<div class="rks-fr__hero-frame">
					<img src="https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=720&amp;q=80" alt="Rishikul Shiksha computer training centre" loading="lazy">
				</div>
				<div class="rks-fr__hero-float rks-fr__hero-float--badge">
					<span class="rks-fr__hero-float-ic"><?php echo $svg_trophy; ?></span>
					<div>
						<small>Trusted by</small>
						<strong>50+ partners</strong>
					</div>
				</div>
				<div class="rks-fr__hero-float rks-fr__hero-float--rating">
					<span class="rks-fr__hero-float-star">&#9733;</span>
					<div>
						<strong>4.9 / 5</strong>
						<small>Partner satisfaction</small>
					</div>
				</div>
			</aside>

		</div>
	</section>

	<!-- ============ TRUST STRIP (no investment) ============ -->
	<section class="rks-fr__stats">
		<div class="rks-container">
			<div class="rks-fr__stats-grid">
				<div>
					<span class="rks-fr__stat-ic"><?php echo $svg_users; ?></span>
					<strong>10,000+</strong>
					<small>Students trained</small>
				</div>
				<div>
					<span class="rks-fr__stat-ic"><?php echo $svg_building; ?></span>
					<strong>50+</strong>
					<small>Partner centres</small>
				</div>
				<div>
					<span class="rks-fr__stat-ic"><?php echo $svg_book; ?></span>
					<strong>20+</strong>
					<small>Courses ready to teach</small>
				</div>
				<div>
					<span class="rks-fr__stat-ic"><?php echo $svg_trophy; ?></span>
					<strong>5+</strong>
					<small>Years of operations</small>
				</div>
			</div>
		</div>
	</section>

	<!-- ============ FORM + WHY (TWO COLUMN) ============ -->
	<section class="rks-fr__apply" id="apply">
		<div class="rks-container rks-fr__apply-grid">

			<div class="rks-fr__form-card">
				<header>
					<span class="rks-eyebrow">Quick Enquiry</span>
					<h2>Franchise Enquiry</h2>
					<p>Ask your questions here. Final franchise registration is done from the dedicated onboarding form.</p>
				</header>
				<?php echo do_shortcode( '[rks_franchise_form]' ); ?>
			</div>

			<aside class="rks-fr__why">
				<header>
					<span class="rks-eyebrow">Why Partner With Us</span>
					<h2>5 reasons our partners stay</h2>
				</header>
				<ul class="rks-fr__why-list">
					<li>
						<span class="rks-fr__why-ic"><?php echo $svg_shield; ?></span>
						<div>
							<strong>Exclusive territory</strong>
							<p>Defined catchment area &mdash; no internal competition.</p>
						</div>
					</li>
					<li>
						<span class="rks-fr__why-ic"><?php echo $svg_book; ?></span>
						<div>
							<strong>Ready curriculum</strong>
							<p>Industry-aligned syllabus, content updated every 6 months.</p>
						</div>
					</li>
					<li>
						<span class="rks-fr__why-ic"><?php echo $svg_users; ?></span>
						<div>
							<strong>Trainer &amp; staff support</strong>
							<p>We help you hire and train your team end-to-end.</p>
						</div>
					</li>
					<li>
						<span class="rks-fr__why-ic"><?php echo $svg_sparkle; ?></span>
						<div>
							<strong>Launch marketing kit</strong>
							<p>Branding, banners, social creatives, opening campaign.</p>
						</div>
					</li>
					<li>
						<span class="rks-fr__why-ic"><?php echo $svg_chart; ?></span>
						<div>
							<strong>Tech &amp; admin platform</strong>
							<p>Built-in LMS, attendance, payments &amp; certificates.</p>
						</div>
					</li>
				</ul>
				<a href="https://wa.me/918979775664?text=<?php echo urlencode( 'Hi, I am interested in your franchise opportunity.' ); ?>" target="_blank" rel="noopener" class="rks-fr__why-wa">
					<?php echo $svg_wa; ?> Prefer WhatsApp? Chat now
				</a>
			</aside>

		</div>
	</section>

	<!-- ============ WHO CAN APPLY ============ -->
	<section class="rks-fr__who">
		<div class="rks-container">
			<header class="rks-fr__head">
				<span class="rks-eyebrow">Who Can Apply</span>
				<h2>This is for you if&hellip;</h2>
				<p>You don't need a teaching background. You just need drive, a space, and a city you understand.</p>
			</header>
			<div class="rks-fr__who-grid">
				<article>
					<span class="rks-fr__who-ic"><?php echo $svg_target; ?></span>
					<h3>Aspiring entrepreneurs</h3>
					<p>First-time business owners who want a proven model with low risk and a recurring revenue stream.</p>
				</article>
				<article>
					<span class="rks-fr__who-ic"><?php echo $svg_trophy; ?></span>
					<h3>Working professionals</h3>
					<p>Mid-career people looking to build an asset alongside their day job &mdash; many partners start this way.</p>
				</article>
				<article>
					<span class="rks-fr__who-ic"><?php echo $svg_users; ?></span>
					<h3>Educators &amp; coaches</h3>
					<p>Teachers and small coaching owners who want to upgrade their setup with a recognised brand &amp; systems.</p>
				</article>
				<article>
					<span class="rks-fr__who-ic"><?php echo $svg_building; ?></span>
					<h3>Multi-business owners</h3>
					<p>Established business owners adding education to their portfolio in tier-2 and tier-3 cities.</p>
				</article>
			</div>
		</div>
	</section>

	<!-- ============ WHAT YOU GET ============ -->
	<section class="rks-fr__perks">
		<div class="rks-container">
			<header class="rks-fr__head">
				<span class="rks-eyebrow">What's Included</span>
				<h2>Everything you need to launch</h2>
				<p>One franchise package, end-to-end &mdash; no hidden costs after onboarding.</p>
			</header>
			<div class="rks-fr__perks-grid">
				<article>
					<span class="rks-fr__perk-ic"><?php echo $svg_building; ?></span>
					<h3>Brand &amp; identity</h3>
					<p>License to operate as a Rishikul Shiksha centre with full branding kit, signage design, and centre layout templates.</p>
				</article>
				<article>
					<span class="rks-fr__perk-ic"><?php echo $svg_book; ?></span>
					<h3>Full curriculum</h3>
					<p>20+ courses ready to teach &mdash; basics, Tally, web dev, design, digital marketing, and competitive prep.</p>
				</article>
				<article>
					<span class="rks-fr__perk-ic"><?php echo $svg_users; ?></span>
					<h3>Hiring &amp; training</h3>
					<p>Help you screen, hire, and train your trainers and counsellors with our certified train-the-trainer programme.</p>
				</article>
				<article>
					<span class="rks-fr__perk-ic"><?php echo $svg_sparkle; ?></span>
					<h3>Marketing rollout</h3>
					<p>Pre-launch campaign, Google/Facebook ad templates, social creatives, and parent-outreach kits.</p>
				</article>
				<article>
					<span class="rks-fr__perk-ic"><?php echo $svg_chart; ?></span>
					<h3>Tech platform</h3>
					<p>Built-in admissions, fees, attendance, LMS, certificates &amp; reporting &mdash; everything in one dashboard.</p>
				</article>
				<article>
					<span class="rks-fr__perk-ic"><?php echo $svg_trophy; ?></span>
					<h3>Ongoing support</h3>
					<p>Monthly business reviews, growth playbook, priority partnership desk, and quarterly partner meet-ups.</p>
				</article>
			</div>
		</div>
	</section>

	<!-- ============ PROCESS ============ -->
	<section class="rks-fr__process">
		<div class="rks-container">
			<header class="rks-fr__head">
				<span class="rks-eyebrow">How It Works</span>
				<h2>From enquiry to launch &mdash; 4 simple steps</h2>
				<p>Most partners go live within 6&ndash;10 weeks of their first call with us.</p>
			</header>
			<ol class="rks-fr__steps">
				<li>
					<span class="rks-fr__step-num">1</span>
					<h4>Enquire</h4>
					<p>Submit a quick enquiry with your city &amp; background.</p>
				</li>
				<li>
					<span class="rks-fr__step-num">2</span>
					<h4>Discuss</h4>
					<p>Discovery call &mdash; we share the model, expectations &amp; next steps.</p>
				</li>
				<li>
					<span class="rks-fr__step-num">3</span>
					<h4>Register</h4>
					<p>Fill the onboarding form with Aadhaar, location and agreement details.</p>
				</li>
				<li>
					<span class="rks-fr__step-num">4</span>
					<h4>Launch</h4>
					<p>Setup, staff trained, marketing live &mdash; you're live!</p>
				</li>
			</ol>
		</div>
	</section>

	<!-- ============ ABOUT FLAGSHIP ============ -->
	<section class="rks-fr__about">
		<div class="rks-container rks-fr__about-grid">
			<div class="rks-fr__about-media">
				<img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=720&amp;q=80" alt="Modern classroom at Rishikul Shiksha" loading="lazy">
				<div class="rks-fr__about-badge">
					<small>Since</small>
					<strong>2019</strong>
				</div>
			</div>
			<div>
				<span class="rks-eyebrow">Our Flagship Centre</span>
				<h2>7 Years' Experience. 10,000+ learners. One proven model.</h2>
				<p>The franchise model is built on 7 Years' Experience at our Vikasnagar, Dehradun campus &mdash; the same curriculum, software, and student-success framework that powers our flagship centre.</p>
				<ul class="rks-fr__about-list">
					<li><span><?php echo $svg_check; ?></span> Modern computer lab with 30+ systems</li>
					<li><span><?php echo $svg_check; ?></span> Certified trainers with 7 Years' Experience</li>
					<li><span><?php echo $svg_check; ?></span> Industry-recognised certificates</li>
					<li><span><?php echo $svg_check; ?></span> 100% placement assistance for students</li>
				</ul>
			</div>
		</div>
	</section>

	<!-- ============ TESTIMONIALS ============ -->
	<section class="rks-fr__voices">
		<div class="rks-container">
			<header class="rks-fr__head">
				<span class="rks-eyebrow">Partner Stories</span>
				<h2>What our partners say</h2>
			</header>
			<div class="rks-fr__voices-grid">
				<article class="rks-fr__voice">
					<span class="rks-fr__voice-ic"><?php echo $svg_quote; ?></span>
					<p>"The brand and curriculum made my opening smooth. By month 8 I had 90 students enrolled &mdash; their marketing playbook genuinely works for tier-3 cities."</p>
					<footer>
						<div class="rks-fr__voice-avatar">RG</div>
						<div>
							<strong>Ravi Gupta</strong>
							<small>Haldwani · Partner since 2023</small>
						</div>
					</footer>
				</article>
				<article class="rks-fr__voice">
					<span class="rks-fr__voice-ic"><?php echo $svg_quote; ?></span>
					<p>"I came from a non-teaching background &mdash; they trained my staff, set up the dashboard, and handled launch marketing. I just focused on the centre."</p>
					<footer>
						<div class="rks-fr__voice-avatar">PN</div>
						<div>
							<strong>Priya Negi</strong>
							<small>Roorkee · Partner since 2024</small>
						</div>
					</footer>
				</article>
				<article class="rks-fr__voice">
					<span class="rks-fr__voice-ic"><?php echo $svg_quote; ?></span>
					<p>"Best decision was choosing them over a generic franchise. The tech platform alone saves me 15 hours a week &mdash; admissions, fees, attendance, all in one place."</p>
					<footer>
						<div class="rks-fr__voice-avatar">AK</div>
						<div>
							<strong>Aman Kumar</strong>
							<small>Saharanpur · Partner since 2022</small>
						</div>
					</footer>
				</article>
			</div>
		</div>
	</section>

	<!-- ============ BROCHURE STRIP ============ -->
	<section class="rks-fr__broch">
		<div class="rks-container">
			<div class="rks-fr__broch-card">
				<div>
					<span class="rks-eyebrow rks-fr__cta-eyebrow">Want the full details?</span>
					<h3>Download the franchise brochure</h3>
					<p>Detailed model, partner FAQs, sample centre layout &amp; financial framework in one PDF.</p>
				</div>
				<div class="rks-fr__broch-actions">
					<?php if ( $brochure_url ) : ?>
						<a href="<?php echo esc_url( $brochure_url ); ?>" target="_blank" rel="noopener" class="rks-fr__btn rks-fr__btn--white"><?php echo $svg_dl; ?> Download Brochure</a>
					<?php else : ?>
						<a href="#apply" class="rks-fr__btn rks-fr__btn--white"><?php echo $svg_dl; ?> Request Brochure</a>
					<?php endif; ?>
					<a href="tel:+918979775664" class="rks-fr__btn rks-fr__btn--ghost"><?php echo $svg_phone; ?> Or call us</a>
				</div>
			</div>
		</div>
	</section>

	<!-- ============ FAQ ============ -->
	<section class="rks-fr__faq">
		<div class="rks-container">
			<header class="rks-fr__head">
				<span class="rks-eyebrow">FAQ</span>
				<h2>Common questions</h2>
			</header>
			<div class="rks-fr__faq-grid">
				<details>
					<summary>What kind of space do I need?<span><?php echo $svg_chev; ?></span></summary>
					<p>A clean 600&ndash;1,200 sq ft commercial space on the ground or first floor with good road visibility works best. We help you evaluate and approve the site before you commit.</p>
				</details>
				<details>
					<summary>Do I need teaching background?<span><?php echo $svg_chev; ?></span></summary>
					<p>Not at all. Most of our partners come from non-teaching backgrounds. We handle staff hiring, trainer onboarding, and curriculum &mdash; you focus on running the business.</p>
				</details>
				<details>
					<summary>Do I get an exclusive territory?<span><?php echo $svg_chev; ?></span></summary>
					<p>Yes. Each partner gets a clearly defined exclusive territory so no other Rishikul Shiksha centre opens within your catchment area.</p>
				</details>
				<details>
					<summary>How long does setup take?<span><?php echo $svg_chev; ?></span></summary>
					<p>Most partners go live within 6&ndash;10 weeks of agreement signing &mdash; including site prep, branding, staff hiring, and launch marketing.</p>
				</details>
				<details>
					<summary>What ongoing support do you provide?<span><?php echo $svg_chev; ?></span></summary>
					<p>Monthly business reviews, marketing campaigns, curriculum updates, a partnership desk for queries, and quarterly partner meet-ups.</p>
				</details>
				<details>
					<summary>Can I run it part-time?<span><?php echo $svg_chev; ?></span></summary>
					<p>We recommend full-time ownership for best results, but several partners run it alongside a primary business by hiring a centre manager. We help with both.</p>
				</details>
			</div>
		</div>
	</section>

	<!-- ============ FINAL CTA ============ -->
	<section class="rks-fr__cta">
		<div class="rks-container">
			<div class="rks-fr__cta-card">
				<div>
					<span class="rks-eyebrow rks-fr__cta-eyebrow">Ready to Start?</span>
					<h2>Build a career-changing business in your city</h2>
					<p>Register your franchise or talk to our partnership team &mdash; the first conversation is free, no commitment.</p>
				</div>
				<div class="rks-fr__cta-actions">
					<a href="<?php echo esc_url( $franchise_register_url ); ?>" class="rks-fr__btn rks-fr__btn--primary">Franchise Register <?php echo $svg_arrow; ?></a>
					<a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="rks-fr__btn rks-fr__btn--ghost">Contact Team</a>
				</div>
			</div>
		</div>
	</section>

</main>

<?php get_footer(); ?>
