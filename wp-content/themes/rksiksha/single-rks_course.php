<?php
/**
 * Single Course Template
 */
if ( ! defined( 'ABSPATH' ) ) exit;

use RKSiksha\Core\Modules\Courses;

get_header();

$settings = (array) get_option( 'rks_settings', [] );
$currency = (string) ( $settings['currency_symbol'] ?? '₹' );

$svg_check  = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
$svg_play   = '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><polygon points="6 4 20 12 6 20 6 4"/></svg>';
$svg_chev   = '<svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>';
$svg_book   = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>';
$svg_clock  = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>';
$svg_signal = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>';
$svg_users  = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>';
$svg_star   = '<svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';
$svg_award  = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/></svg>';
$svg_lock   = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>';
$svg_play_s = '<svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><polygon points="6 4 20 12 6 20 6 4"/></svg>';
$svg_globe  = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>';
$svg_dl     = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>';
$svg_mob    = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>';
$svg_msg    = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>';
$svg_wa     = '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M.057 24l1.687-6.163a11.867 11.867 0 0 1-1.587-5.946C.16 5.335 5.495 0 12.05 0a11.817 11.817 0 0 1 8.413 3.488 11.824 11.824 0 0 1 3.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892a11.9 11.9 0 0 1-5.688-1.448L.057 24zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884a9.86 9.86 0 0 0 1.516 5.26L3.5 19.85l1.654-1.657zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.296-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>';
$svg_share  = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>';
$svg_target = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>';

while ( have_posts() ) : the_post();
	$id         = get_the_ID();
	$is_paid    = (bool) get_post_meta( $id, Courses::M_IS_PAID, true );
	$price      = (float) get_post_meta( $id, Courses::M_PRICE, true );
	$rating     = (float) get_post_meta( $id, Courses::M_RATING, true );
	$students   = (int) get_post_meta( $id, Courses::M_STUDENTS_COUNT, true );
	$level      = (string) get_post_meta( $id, Courses::M_LEVEL, true );
	$duration   = (string) get_post_meta( $id, Courses::M_DURATION, true );
	$instructor = (string) get_post_meta( $id, Courses::M_INSTRUCTOR, true );
	$short_desc = (string) get_post_meta( $id, Courses::M_SHORT_DESC, true );
	$intro_url  = (string) get_post_meta( $id, Courses::M_VIDEO_INTRO, true );
	$intro_embed = Courses::video_embed_url( $intro_url );
	$intro_direct_video = Courses::is_direct_video_url( $intro_url );
	$cats       = wp_get_object_terms( $id, Courses::TAX_CATEGORY );
	$cat        = ( ! is_wp_error( $cats ) && ! empty( $cats ) ) ? $cats[0] : null;
	$lessons    = Courses::get_lessons( $id );

	$logged_in     = is_user_logged_in();
	$enrolled      = false;
	$dashboard_url = home_url( '/student-dashboard/' );
	$admission_url = home_url( '/admission/?course_id=' . $id );

	if ( $logged_in ) {
		$user_id = get_current_user_id();
		global $wpdb;
		// Only count "enrolled" when the row is in an access-granting state.
		// Pending/rejected/unexpected statuses must NOT show the Continue Learning CTA.
		$enrolled = (bool) $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}rks_enrollments WHERE user_id = %d AND course_id = %d AND status IN ('active','completed') LIMIT 1",
			$user_id,
			$id
		) );
	}
?>

<main id="primary" class="rks-cs">

	<!-- ============ HERO ============ -->
	<section class="rks-cs__hero">
		<div class="rks-container rks-cs__hero-grid">

			<div class="rks-cs__hero-main">
				<nav class="rks-cs__crumb" aria-label="Breadcrumb">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
					<span><?php echo $svg_chev; ?></span>
					<a href="<?php echo esc_url( get_post_type_archive_link( Courses::CPT_COURSE ) ); ?>">Courses</a>
					<?php if ( $cat ) : ?>
						<span><?php echo $svg_chev; ?></span>
						<a href="<?php echo esc_url( get_term_link( $cat ) ); ?>"><?php echo esc_html( $cat->name ); ?></a>
					<?php endif; ?>
				</nav>

				<?php if ( $cat ) : ?>
					<span class="rks-cs__hero-cat"><?php echo esc_html( $cat->name ); ?></span>
				<?php endif; ?>

				<h1><?php the_title(); ?></h1>

				<?php if ( $short_desc ) : ?>
					<p class="rks-cs__hero-lede"><?php echo esc_html( $short_desc ); ?></p>
				<?php endif; ?>

				<div class="rks-cs__hero-meta">
					<?php if ( $rating > 0 ) : ?>
						<span class="rks-cs__hero-rating"><?php echo $svg_star; ?> <strong><?php echo esc_html( number_format( $rating, 1 ) ); ?></strong> rating</span>
					<?php endif; ?>
					<?php if ( $students > 0 ) : ?>
						<span><?php echo $svg_users; ?> <?php echo number_format( $students ); ?> learners</span>
					<?php endif; ?>
					<?php if ( $instructor ) : ?>
						<span>By <strong><?php echo esc_html( $instructor ); ?></strong></span>
					<?php endif; ?>
				</div>

				<div class="rks-cs__hero-chips">
					<?php if ( $level ) : ?>
						<span class="rks-cs__hero-chip"><?php echo $svg_signal; ?> <?php echo esc_html( ucfirst( str_replace( '-', ' ', $level ) ) ); ?></span>
					<?php endif; ?>
					<?php if ( count( $lessons ) > 0 ) : ?>
						<span class="rks-cs__hero-chip"><?php echo $svg_book; ?> <?php echo count( $lessons ); ?> lessons</span>
					<?php endif; ?>
					<?php if ( $duration ) : ?>
						<span class="rks-cs__hero-chip"><?php echo $svg_clock; ?> <?php echo esc_html( $duration ); ?></span>
					<?php endif; ?>
					<span class="rks-cs__hero-chip"><?php echo $svg_award; ?> Certificate</span>
				</div>
			</div>

			<?php
			$is_popular     = ( $rating >= 4.7 ) || ( $students >= 500 );
			$original_price = $is_paid ? (int) ( ceil( ( $price * 1.4 ) / 100 ) * 100 ) : 0;
			$discount_pct   = $is_paid && $original_price > $price ? (int) round( ( ( $original_price - $price ) / $original_price ) * 100 ) : 0;
			?>
			<!-- Side enrollment card -->
			<aside class="rks-cs__buy">
				<?php if ( $is_popular ) : ?>
					<span class="rks-cs__buy-ribbon"><?php echo $svg_star; ?> <?php echo $rating >= 4.7 ? 'Highly Rated' : 'Popular'; ?></span>
				<?php endif; ?>
				<div class="rks-cs__buy-thumb">
					<?php if ( $intro_embed ) : ?>
						<iframe class="rks-cs__buy-video" src="<?php echo esc_url( $intro_embed ); ?>"
						        title="<?php echo esc_attr( get_the_title() . ' introduction video' ); ?>"
						        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
						        allowfullscreen></iframe>
					<?php elseif ( $intro_direct_video ) : ?>
						<video class="rks-cs__buy-video" src="<?php echo esc_url( $intro_url ); ?>" controls preload="metadata" playsinline></video>
					<?php elseif ( has_post_thumbnail() ) : ?>
						<?php the_post_thumbnail( 'medium_large' ); ?>
					<?php else : ?>
						<div class="rks-cs__buy-fallback">
							<span class="rks-cs__buy-fallback-ic"><?php echo $svg_award; ?></span>
							<span class="rks-cs__buy-fallback-letter"><?php echo esc_html( strtoupper( substr( get_the_title(), 0, 2 ) ) ); ?></span>
						</div>
					<?php endif; ?>
				</div>

				<div class="rks-cs__buy-body">
					<div class="rks-cs__buy-price">
						<?php if ( $is_paid ) : ?>
							<div class="rks-cs__buy-price-row">
								<strong><?php echo esc_html( $currency . number_format( $price, 0 ) ); ?></strong>
								<?php if ( $original_price > $price ) : ?>
									<del><?php echo esc_html( $currency . number_format( $original_price, 0 ) ); ?></del>
									<span class="rks-cs__buy-discount">−<?php echo (int) $discount_pct; ?>%</span>
								<?php endif; ?>
							</div>
							<small>One-time payment · lifetime access</small>
						<?php else : ?>
							<strong class="rks-cs__buy-price--free">Free</strong>
							<small>No payment required</small>
						<?php endif; ?>
					</div>

					<?php if ( $rating > 0 || $students > 0 ) : ?>
					<div class="rks-cs__buy-social">
						<?php if ( $rating > 0 ) : ?>
							<span><?php echo $svg_star; ?> <strong><?php echo esc_html( number_format( $rating, 1 ) ); ?></strong></span>
						<?php endif; ?>
						<?php if ( $students > 0 ) : ?>
							<span><?php echo $svg_users; ?> <?php echo number_format( $students ); ?> learners</span>
						<?php endif; ?>
					</div>
					<?php endif; ?>

					<div class="rks-cs__buy-cta">
						<?php if ( $enrolled ) : ?>
							<a href="<?php echo esc_url( $dashboard_url ); ?>" class="rks-cs__btn rks-cs__btn--primary">Continue Learning <?php echo $svg_play_s; ?></a>
						<?php elseif ( ! $is_paid && $logged_in ) : ?>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
								<?php wp_nonce_field( 'rks_free_enroll' ); ?>
								<input type="hidden" name="action" value="rks_free_enroll">
								<input type="hidden" name="course_id" value="<?php echo (int) $id; ?>">
								<button type="submit" class="rks-cs__btn rks-cs__btn--primary">Get Started Free <?php echo $svg_play_s; ?></button>
							</form>
						<?php elseif ( ! $is_paid && ! $logged_in ) : ?>
							<a href="<?php echo esc_url( wp_login_url( get_permalink( $id ) ) ); ?>" class="rks-cs__btn rks-cs__btn--primary">Login to Enroll Free</a>
						<?php else : ?>
							<a href="<?php echo esc_url( $admission_url ); ?>" class="rks-cs__btn rks-cs__btn--primary">Enroll Now <?php echo $svg_play_s; ?></a>
						<?php endif; ?>
						<a href="https://wa.me/918979775664?text=<?php echo urlencode( 'Hi, I have a question about the course: ' . get_the_title() ); ?>" target="_blank" rel="noopener" class="rks-cs__btn rks-cs__btn--ghost"><?php echo $svg_wa; ?> Ask on WhatsApp</a>
					</div>

					<div class="rks-cs__buy-mini">
						<span><span class="rks-cs__buy-mini-ic"><?php echo $svg_check; ?></span>Lifetime</span>
						<span><span class="rks-cs__buy-mini-ic"><?php echo $svg_award; ?></span>Certificate</span>
						<span><span class="rks-cs__buy-mini-ic"><?php echo $svg_msg; ?></span>Q&amp;A</span>
					</div>

				</div>
			</aside>

		</div>
	</section>

	<!-- ============ FEATURES STRIP ============ -->
	<section class="rks-cs__features">
		<div class="rks-container">
			<div class="rks-cs__features-grid">
				<div class="rks-cs__feature">
					<span class="rks-cs__feature-ic"><?php echo $svg_globe; ?></span>
					<div>
						<strong>Anywhere access</strong>
						<small>Online lessons on web &amp; mobile</small>
					</div>
				</div>
				<div class="rks-cs__feature">
					<span class="rks-cs__feature-ic"><?php echo $svg_dl; ?></span>
					<div>
						<strong>Downloadable</strong>
						<small>Resources, notes &amp; assignments</small>
					</div>
				</div>
				<div class="rks-cs__feature">
					<span class="rks-cs__feature-ic"><?php echo $svg_award; ?></span>
					<div>
						<strong>Verified certificate</strong>
						<small>Publicly verifiable on completion</small>
					</div>
				</div>
				<div class="rks-cs__feature">
					<span class="rks-cs__feature-ic"><?php echo $svg_msg; ?></span>
					<div>
						<strong>Mentor support</strong>
						<small>Get unstuck with real instructors</small>
					</div>
				</div>
			</div>
		</div>
	</section>

	<!-- ============ BODY ============ -->
	<section class="rks-cs__body">
		<div class="rks-container rks-cs__body-grid">

			<main class="rks-cs__main">

				<article class="rks-cs__block">
					<header class="rks-cs__block-head">
						<span class="rks-cs__block-ic"><?php echo $svg_target; ?></span>
						<h2>About this course</h2>
					</header>
					<div class="rks-cs__content">
						<?php the_content(); ?>
					</div>
				</article>

				<?php if ( ! empty( $lessons ) ) : ?>
				<article class="rks-cs__block">
					<header class="rks-cs__block-head">
						<span class="rks-cs__block-ic"><?php echo $svg_book; ?></span>
						<h2>Course Curriculum</h2>
						<small><?php echo count( $lessons ); ?> lessons</small>
					</header>
					<ol class="rks-cs__curriculum">
						<?php foreach ( $lessons as $i => $lesson ) :
							$dur     = (int) get_post_meta( $lesson->ID, Courses::ML_DURATION_MIN, true );
							$preview = (bool) get_post_meta( $lesson->ID, Courses::ML_FREE_PREVIEW, true );
						?>
							<li class="rks-cs__lesson">
								<span class="rks-cs__lesson-num"><?php echo (int) ( $i + 1 ); ?></span>
								<div class="rks-cs__lesson-title">
									<span class="rks-cs__lesson-ic"><?php echo $preview ? $svg_play_s : $svg_lock; ?></span>
									<span><?php echo esc_html( $lesson->post_title ); ?></span>
									<?php if ( $preview ) : ?>
										<span class="rks-cs__lesson-preview">Preview</span>
									<?php endif; ?>
								</div>
								<span class="rks-cs__lesson-dur"><?php echo $dur > 0 ? esc_html( $dur . ' min' ) : '—'; ?></span>
							</li>
						<?php endforeach; ?>
					</ol>
				</article>
				<?php endif; ?>

				<article class="rks-cs__block">
					<header class="rks-cs__block-head">
						<span class="rks-cs__block-ic"><?php echo $svg_check; ?></span>
						<h2>What you'll learn</h2>
					</header>
					<ul class="rks-cs__learn">
						<li><span class="rks-cs__tick"><?php echo $svg_check; ?></span> Practical, project-based learning you can apply day one</li>
						<li><span class="rks-cs__tick"><?php echo $svg_check; ?></span> Industry-relevant tools and workflows</li>
						<li><span class="rks-cs__tick"><?php echo $svg_check; ?></span> A complete portfolio piece by the end</li>
						<li><span class="rks-cs__tick"><?php echo $svg_check; ?></span> Confidence to take on freelance or full-time work</li>
						<li><span class="rks-cs__tick"><?php echo $svg_check; ?></span> Live doubt-clearing sessions with instructors</li>
						<li><span class="rks-cs__tick"><?php echo $svg_check; ?></span> Verified certificate to share with recruiters</li>
					</ul>
				</article>

				<?php if ( $instructor ) : ?>
				<article class="rks-cs__block">
					<header class="rks-cs__block-head">
						<span class="rks-cs__block-ic"><?php echo $svg_users; ?></span>
						<h2>Your Instructor</h2>
					</header>
					<div class="rks-cs__instructor">
						<div class="rks-cs__instructor-avatar">
							<?php echo esc_html( strtoupper( substr( $instructor, 0, 1 ) ) ); ?>
						</div>
						<div>
							<strong><?php echo esc_html( $instructor ); ?></strong>
							<small>Lead Instructor at Rishikul Shiksha</small>
							<p>A Rishikul Shiksha instructor with 7 Years' Experience in structured, hands-on lessons and practical project work.</p>
						</div>
					</div>
				</article>
				<?php endif; ?>

				<article class="rks-cs__block">
					<header class="rks-cs__block-head">
						<span class="rks-cs__block-ic"><?php echo $svg_share; ?></span>
						<h2>FAQ</h2>
					</header>
					<div class="rks-cs__faq">
						<details>
							<summary>How long do I have access to this course?<span><?php echo $svg_chev; ?></span></summary>
							<p>You get lifetime access. Buy once and revisit lessons anytime — including future updates.</p>
						</details>
						<details>
							<summary>Will I receive a certificate?<span><?php echo $svg_chev; ?></span></summary>
							<p>Yes — every learner receives a digital certificate on completion. It is publicly verifiable with a unique code.</p>
						</details>
						<details>
							<summary>Is there mentor support?<span><?php echo $svg_chev; ?></span></summary>
							<p>Absolutely. You can ask questions in our private learner group and book 1-on-1 doubt sessions with instructors.</p>
						</details>
						<details>
							<summary>Are course fees refundable?<span><?php echo $svg_chev; ?></span></summary>
							<p>No. Course fees are non-refundable once paid. Please review the course details before enrollment.</p>
						</details>
					</div>
				</article>

			</main>

			<aside class="rks-cs__side">
				<div class="rks-cs__side-card">
					<h3>Course Details</h3>
					<dl class="rks-cs__dl">
						<?php if ( $level )    : ?><dt>Level</dt><dd><?php echo esc_html( ucfirst( str_replace( '-', ' ', $level ) ) ); ?></dd><?php endif; ?>
						<?php if ( $duration ) : ?><dt>Duration</dt><dd><?php echo esc_html( $duration ); ?></dd><?php endif; ?>
						<?php if ( count( $lessons ) > 0 ) : ?><dt>Lessons</dt><dd><?php echo count( $lessons ); ?></dd><?php endif; ?>
						<?php if ( $instructor ) : ?><dt>Instructor</dt><dd><?php echo esc_html( $instructor ); ?></dd><?php endif; ?>
						<?php if ( $cat ) : ?><dt>Category</dt><dd><?php echo esc_html( $cat->name ); ?></dd><?php endif; ?>
						<dt>Language</dt><dd>English &amp; Hindi</dd>
						<dt>Certificate</dt><dd>Yes</dd>
					</dl>
				</div>

				<div class="rks-cs__side-card rks-cs__side-card--cta">
					<h3>Have questions?</h3>
					<p>Talk to a counsellor — we'll help you decide if this course fits your goals.</p>
					<a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="rks-cs__btn rks-cs__btn--primary rks-cs__btn--block">Talk to a Counsellor <?php echo $svg_chev; ?></a>
					<a href="https://wa.me/918979775664" target="_blank" rel="noopener" class="rks-cs__btn rks-cs__btn--wa rks-cs__btn--block"><?php echo $svg_wa; ?> WhatsApp Us</a>
				</div>
			</aside>

		</div>
	</section>

	<!-- ============ RELATED ============ -->
	<?php
	$related = new WP_Query( [
		'post_type'      => Courses::CPT_COURSE,
		'posts_per_page' => 3,
		'post__not_in'   => [ $id ],
		'tax_query'      => $cat ? [ [
			'taxonomy' => Courses::TAX_CATEGORY,
			'field'    => 'term_id',
			'terms'    => [ $cat->term_id ],
		] ] : [],
		'orderby'        => 'rand',
	] );
	if ( $related->have_posts() ) :
	?>
	<section class="rks-cs__related">
		<div class="rks-container">
			<header class="rks-cs__related-head">
				<span class="rks-eyebrow">Keep Exploring</span>
				<h2>You might also like</h2>
			</header>
			<div class="rks-cs__related-grid">
				<?php while ( $related->have_posts() ) : $related->the_post();
					$r_id    = get_the_ID();
					$r_paid  = (bool) get_post_meta( $r_id, Courses::M_IS_PAID, true );
					$r_price = (float) get_post_meta( $r_id, Courses::M_PRICE, true );
					$r_dur   = (string) get_post_meta( $r_id, Courses::M_DURATION, true );
					$r_les   = (int) get_post_meta( $r_id, Courses::M_LESSON_COUNT, true );
					$r_cats  = wp_get_object_terms( $r_id, Courses::TAX_CATEGORY );
					$r_cat   = ( ! is_wp_error( $r_cats ) && ! empty( $r_cats ) ) ? $r_cats[0] : null;
				?>
					<article class="rks-cs__related-card">
						<a href="<?php the_permalink(); ?>" class="rks-cs__related-thumb">
							<?php if ( has_post_thumbnail() ) : the_post_thumbnail( 'medium' ); else : ?>
								<div class="rks-cs__buy-fallback"><span><?php echo esc_html( strtoupper( substr( get_the_title(), 0, 1 ) ) ); ?></span></div>
							<?php endif; ?>
							<?php if ( $r_cat ) : ?>
								<span class="rks-cs__related-badge"><?php echo esc_html( $r_cat->name ); ?></span>
							<?php endif; ?>
						</a>
						<div class="rks-cs__related-body">
							<h3><a href="<?php the_permalink(); ?>"><?php echo esc_html( wp_trim_words( get_the_title(), 12 ) ); ?></a></h3>
							<div class="rks-cs__related-meta">
								<?php if ( $r_les > 0 ) : ?><span><?php echo $svg_book; ?> <?php echo (int) $r_les; ?></span><?php endif; ?>
								<?php if ( $r_dur ) : ?><span><?php echo $svg_clock; ?> <?php echo esc_html( $r_dur ); ?></span><?php endif; ?>
							</div>
							<div class="rks-cs__related-foot">
								<span class="rks-cs__related-price"><?php echo $r_paid ? esc_html( $currency . number_format( $r_price, 0 ) ) : 'Free'; ?></span>
								<a href="<?php the_permalink(); ?>" class="rks-cs__related-link">View <?php echo $svg_chev; ?></a>
							</div>
						</div>
					</article>
				<?php endwhile; wp_reset_postdata(); ?>
			</div>
		</div>
	</section>
	<?php endif; ?>

</main>

<?php endwhile; ?>

<?php get_footer(); ?>
