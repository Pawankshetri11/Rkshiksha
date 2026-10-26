<?php
/**
 * Template Name: About Page
 */
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

$svg_check    = '<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
$svg_target   = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>';
$svg_eye      = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
$svg_sparkle  = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v18M3 12h18M5.6 5.6l12.8 12.8M5.6 18.4l12.8-12.8"/></svg>';
$svg_wallet   = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/></svg>';
$svg_zap      = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>';
$svg_cap      = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"/></svg>';
$svg_cap_lg   = '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"/></svg>';
$svg_globe    = '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>';
$svg_building = '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01"/><path d="M16 6h.01"/><path d="M12 6h.01"/><path d="M12 10h.01"/><path d="M12 14h.01"/><path d="M16 10h.01"/><path d="M16 14h.01"/><path d="M8 10h.01"/><path d="M8 14h.01"/></svg>';
$svg_arrow    = '<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>';
$svg_chev     = '<svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>';
$svg_users    = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>';
$svg_book     = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>';
$svg_calendar = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>';
$svg_trophy   = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/></svg>';
$svg_shield   = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>';
$svg_badge    = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="15" r="6"/><path d="M9 11l-2-9h10l-2 9"/><path d="M12 15v6"/></svg>';
?>

<main id="primary" class="rks-about">

	<!-- ============ HERO ============ -->
	<section class="rks-about__hero">
		<div class="rks-container">
			<nav class="rks-about__crumb" aria-label="Breadcrumb">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
				<span><?php echo $svg_chev; ?></span>
				<span aria-current="page">About</span>
			</nav>

			<div class="rks-about__hero-grid">
				<div class="rks-about__hero-text">
					<span class="rks-eyebrow">Who We Are</span>
					<h1>Empowering India&rsquo;s next generation of <span>learners</span>.</h1>
					<p>Rishikul Shiksha Career Academy &mdash; making quality online &amp; offline education accessible across India. Learn at your own pace, from anywhere.</p>
					<div class="rks-about__hero-actions">
						<a href="<?php echo esc_url( home_url( '/courses/' ) ); ?>" class="rks-about__btn-primary">Explore Courses <?php echo $svg_arrow; ?></a>
						<a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="rks-about__btn-ghost">Get in Touch</a>
					</div>
				</div>
				<div class="rks-about__hero-media">
					<div class="rks-about__hero-frame">
						<img src="https://images.unsplash.com/photo-1523580494863-6f3031224c94?w=720&amp;q=80" alt="Students learning at Rishikul Shiksha" loading="lazy">
					</div>
					<div class="rks-about__hero-float rks-about__hero-float--badge">
						<span class="rks-about__hero-float-ic"><?php echo $svg_trophy; ?></span>
						<div>
							<small>Trusted by</small>
							<strong>10,000+ students</strong>
						</div>
					</div>
					<div class="rks-about__hero-float rks-about__hero-float--rating">
						<span class="rks-about__hero-float-star">&#9733;</span>
						<div>
							<strong>4.9 / 5</strong>
							<small>Student rating</small>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>

	<!-- ============ STATS STRIP ============ -->
	<section class="rks-about__stats">
		<div class="rks-container">
			<div class="rks-about__stats-grid">
				<div>
					<span class="rks-about__stats-ic"><?php echo $svg_calendar; ?></span>
					<strong>5+</strong>
					<small>Years of Excellence</small>
				</div>
				<div>
					<span class="rks-about__stats-ic"><?php echo $svg_users; ?></span>
					<strong>10,000+</strong>
					<small>Students Empowered</small>
				</div>
				<div>
					<span class="rks-about__stats-ic"><?php echo $svg_book; ?></span>
					<strong>200+</strong>
					<small>Courses Offered</small>
				</div>
				<div>
					<span class="rks-about__stats-ic"><?php echo $svg_building; ?></span>
					<strong>50+</strong>
					<small>Branches &amp; Partners</small>
				</div>
			</div>
		</div>
	</section>

	<!-- ============ STORY ============ -->
	<section class="rks-about__story">
		<div class="rks-container rks-about__story-grid">
			<div>
				<span class="rks-eyebrow">Our Story</span>
				<h2>From a single classroom to a nationwide learning movement.</h2>
				<p>Rishikul Shiksha began in 2018 in a small classroom in Dehradun with one belief &mdash; quality education should never be a privilege. What started as an offline computer coaching institute soon grew into something much bigger.</p>
				<p>As demand for flexible learning rose, we launched our online platform, bringing structured courses in coding, design, business and competitive exams to learners across India. Our offline campus continues to thrive alongside a growing network of franchise partners.</p>
				<ul class="rks-about__story-points">
					<li><span class="rks-about__tick"><?php echo $svg_check; ?></span> Hybrid learning &mdash; online &amp; offline</li>
					<li><span class="rks-about__tick"><?php echo $svg_check; ?></span> Industry-aligned curriculum</li>
					<li><span class="rks-about__tick"><?php echo $svg_check; ?></span> Dedicated mentors &amp; counsellors</li>
					<li><span class="rks-about__tick"><?php echo $svg_check; ?></span> Verified certificates &amp; ID cards</li>
				</ul>
			</div>
			<aside class="rks-about__story-side">
				<?php $rks_uploads = wp_get_upload_dir(); ?>
				<figure class="rks-about__quote">
					<div class="rks-about__quote-media">
						<img src="<?php echo esc_url( trailingslashit( $rks_uploads['baseurl'] ) . 'chairman.png' ); ?>" alt="Ravi Belwal, Chairman of Rishikul Shiksha">
						<span class="rks-about__quote-badge">
							<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l2.4 2.4L18 4l1.6 3.6L23 9.6l-1.6 3.4L23 16.4 19.6 18 18 21.6 14.4 21 12 23 9.6 21 6 21.6 4.4 18 1 16.4l1.6-3.4L1 9.6 4.4 8 6 4.4 9.6 4z"/><polyline points="9 12 11 14 15 10"/></svg>
							Founder's Note
						</span>
					</div>
					<blockquote class="rks-about__quote-body">
						<svg class="rks-about__quote-glyph" viewBox="0 0 32 32" width="36" height="36" aria-hidden="true"><path d="M9.3 8c-3 2-5 5.6-5 9.8 0 3.6 2.3 6 5.6 6 2.6 0 4.7-2 4.7-4.6 0-2.4-1.7-4.2-4-4.2-.5 0-.9.1-1 .1.4-2.8 3.1-6 5.6-7.6L9.3 8zm14 0c-3 2-5 5.6-5 9.8 0 3.6 2.3 6 5.6 6 2.6 0 4.7-2 4.7-4.6 0-2.4-1.7-4.2-4-4.2-.5 0-.9.1-1 .1.4-2.8 3.1-6 5.6-7.6L23.3 8z" fill="currentColor"/></svg>
						<p>Education changed my life. I wanted to build a place where it could change someone else&rsquo;s &mdash; without the price tag I had to pay.</p>
					</blockquote>
					<figcaption class="rks-about__quote-author">
						<div>
							<strong>Ravi Belwal</strong>
							<small>Founder, Rishikul Shiksha</small>
						</div>
						<a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="rks-about__quote-link" aria-label="Get in touch with the founder">
							Meet the team
							<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
						</a>
					</figcaption>
				</figure>
			</aside>
		</div>
	</section>

	<!-- ============ MISSION & VISION ============ -->
	<section class="rks-about__mv">
		<div class="rks-container">
			<header class="rks-about__head">
				<span class="rks-eyebrow">What Drives Us</span>
				<h2>Mission &amp; Vision</h2>
			</header>
			<div class="rks-about__mv-grid">
				<article class="rks-about__mv-card">
					<span class="rks-about__mv-ic rks-about__mv-ic--mint"><?php echo $svg_target; ?></span>
					<h3>Our Mission</h3>
					<p>To make high-quality, career-ready education accessible and affordable for every Indian learner &mdash; whether they prefer learning online from their hometown or face-to-face at our coaching centres.</p>
				</article>
				<article class="rks-about__mv-card">
					<span class="rks-about__mv-ic rks-about__mv-ic--red"><?php echo $svg_eye; ?></span>
					<h3>Our Vision</h3>
					<p>To become India&rsquo;s most trusted hybrid learning brand by 2030 &mdash; combining modern technology, dedicated mentors and reliable counselling to shape the careers of one million students.</p>
				</article>
			</div>
		</div>
	</section>

	<!-- ============ VALUES ============ -->
	<section class="rks-about__values">
		<div class="rks-container">
			<header class="rks-about__head">
				<span class="rks-eyebrow">Our Core Values</span>
				<h2>What we stand for</h2>
			</header>
			<div class="rks-about__values-grid">
				<article class="rks-about__value">
					<span class="rks-about__value-ic"><?php echo $svg_sparkle; ?></span>
					<h3>Quality Education</h3>
					<p>Carefully designed curriculum, expert mentors and updated content that meets industry standards.</p>
				</article>
				<article class="rks-about__value">
					<span class="rks-about__value-ic"><?php echo $svg_wallet; ?></span>
					<h3>Affordability</h3>
					<p>Honest pricing, easy installments and free resources so cost is never a barrier to learning.</p>
				</article>
				<article class="rks-about__value">
					<span class="rks-about__value-ic"><?php echo $svg_zap; ?></span>
					<h3>Innovation</h3>
					<p>Live classes, AI-powered practice and modern tools that make learning engaging and effective.</p>
				</article>
				<article class="rks-about__value">
					<span class="rks-about__value-ic"><?php echo $svg_cap; ?></span>
					<h3>Student-First</h3>
					<p>Every decision starts with one question: does this genuinely help our students grow and succeed?</p>
				</article>
			</div>
		</div>
	</section>

	<!-- ============ PATHWAYS ============ -->
	<section class="rks-about__paths">
		<div class="rks-container">
			<header class="rks-about__head">
				<span class="rks-eyebrow">What We Do</span>
				<h2>Three pathways, one promise</h2>
			</header>
			<div class="rks-about__paths-grid">
				<article class="rks-about__path rks-about__path--mint">
					<span class="rks-about__path-ic"><?php echo $svg_globe; ?></span>
					<h3>Online Learning Platform</h3>
					<p>Self-paced and live courses in coding, design, business and competitive exam prep &mdash; learn anytime, anywhere in India.</p>
					<a href="<?php echo esc_url( home_url( '/courses/' ) ); ?>" class="rks-about__path-link">Browse courses <?php echo $svg_arrow; ?></a>
				</article>
				<article class="rks-about__path rks-about__path--red">
					<span class="rks-about__path-ic"><?php echo $svg_building; ?></span>
					<h3>Offline Coaching</h3>
					<p>Hands-on classroom training at our Vikasnagar campus covering computer basics, Tally, web development and digital skills.</p>
					<a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="rks-about__path-link">Visit our centre <?php echo $svg_arrow; ?></a>
				</article>
				<article class="rks-about__path rks-about__path--teal">
					<span class="rks-about__path-ic"><?php echo $svg_cap_lg; ?></span>
					<h3>University Counselling</h3>
					<p>End-to-end guidance for admissions to top colleges and universities &mdash; from course selection to documentation.</p>
					<a href="<?php echo esc_url( home_url( '/university-counselling/' ) ); ?>" class="rks-about__path-link">Get guidance <?php echo $svg_arrow; ?></a>
				</article>
			</div>
		</div>
	</section>

	<!-- ============ WHY US ============ -->
	<section class="rks-about__why">
		<div class="rks-container">
			<header class="rks-about__head">
				<span class="rks-eyebrow">Why Students Choose Us</span>
				<h2>Built around learners, not curriculums</h2>
			</header>
			<div class="rks-about__why-grid">
				<div class="rks-about__why-item">
					<span class="rks-about__why-ic"><?php echo $svg_shield; ?></span>
					<div>
						<h4>Verified certificates</h4>
						<p>Every certificate is publicly verifiable with a unique code &mdash; trusted by recruiters.</p>
					</div>
				</div>
				<div class="rks-about__why-item">
					<span class="rks-about__why-ic"><?php echo $svg_users; ?></span>
					<div>
						<h4>Real mentor support</h4>
						<p>1-on-1 doubt sessions and WhatsApp groups &mdash; not chatbots.</p>
					</div>
				</div>
				<div class="rks-about__why-item">
					<span class="rks-about__why-ic"><?php echo $svg_wallet; ?></span>
					<div>
						<h4>Fair pricing</h4>
						<p>Easy installments, scholarship spots, and free resources for serious learners.</p>
					</div>
				</div>
				<div class="rks-about__why-item">
					<span class="rks-about__why-ic"><?php echo $svg_badge; ?></span>
					<div>
						<h4>Industry-aligned</h4>
						<p>Curriculum refreshed every 6 months with practising professionals.</p>
					</div>
				</div>
				<div class="rks-about__why-item">
					<span class="rks-about__why-ic"><?php echo $svg_zap; ?></span>
					<div>
						<h4>Hybrid by design</h4>
						<p>Mix online lessons with offline practice at any of our partner centres.</p>
					</div>
				</div>
				<div class="rks-about__why-item">
					<span class="rks-about__why-ic"><?php echo $svg_cap; ?></span>
					<div>
						<h4>Career &amp; admission help</h4>
						<p>From resume reviews to university applications &mdash; one team for both.</p>
					</div>
				</div>
			</div>
		</div>
	</section>

	<!-- ============ CTA ============ -->
	<section class="rks-about__cta">
		<div class="rks-container">
			<div class="rks-about__cta-card">
				<div>
					<span class="rks-eyebrow rks-about__cta-eyebrow">Ready to Begin?</span>
					<h2>Start your learning journey today</h2>
					<p>Browse our courses or talk to a counsellor &mdash; either way, we&rsquo;ll help you figure out the next step.</p>
				</div>
				<div class="rks-about__cta-actions">
					<a href="<?php echo esc_url( home_url( '/courses/' ) ); ?>" class="rks-about__btn-primary">Explore Courses <?php echo $svg_arrow; ?></a>
					<a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="rks-about__btn-ghost rks-about__btn-ghost--light">Talk to a Counsellor</a>
				</div>
			</div>
		</div>
	</section>

</main>

<?php get_footer(); ?>
