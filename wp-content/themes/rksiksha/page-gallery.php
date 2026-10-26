<?php
/**
 * Template Name: Gallery
 */
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

/* ---------- SVG ICONS ---------- */
$svg_chev   = '<svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>';
$svg_arrow  = '<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>';
$svg_zoom   = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>';
$svg_camera = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>';
$svg_spark  = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v18M3 12h18M5.6 5.6l12.8 12.8M5.6 18.4l12.8-12.8"/></svg>';
$svg_refresh= '<svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>';
$svg_users2 = '<svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>';
$svg_pin    = '<svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>';
$svg_heart  = '<svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>';
$svg_check  = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
$svg_phone  = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>';
$svg_close  = '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';
$svg_prev   = '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>';
$svg_next   = '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>';

/* ---------- DATA SOURCE ----------
   Prefer the rksiksha-gallery plugin. Fall back to scanning
   /wp-content/uploads/gallery/ for raw files (legacy mode).
----------------------------------*/
$photos = [];

if ( function_exists( 'rks_gallery_get_photos' ) ) {
	$photos = rks_gallery_get_photos();
}

if ( empty( $photos ) ) {
	$uploads = wp_get_upload_dir();
	$dir     = trailingslashit( $uploads['basedir'] ) . 'gallery';
	$base    = trailingslashit( $uploads['baseurl'] ) . 'gallery/';
	if ( is_dir( $dir ) ) {
		$files = glob( $dir . '/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE );
		if ( $files ) {
			natsort( $files );
			foreach ( $files as $i => $path ) {
				$file = basename( $path );
				$url  = $base . rawurlencode( $file );
				$photos[] = [
					'id'    => 0,
					'url'   => $url,
					'thumb' => $url,
					'alt'   => sprintf( 'Rishikul Shiksha gallery photo %d', $i + 1 ),
					'w'     => 0,
					'h'     => 0,
				];
			}
		}
	}
}

$photo_count = count( $photos );
$polaroids   = array_slice( $photos, 0, 8 );
?>

<main id="primary" class="rks-gallery">

	<!-- ============ HERO — Polaroid Grid ============ -->
	<section class="rks-gallery__hero">
		<div class="rks-gallery__hero-bg" aria-hidden="true">
			<span class="rks-gallery__blob rks-gallery__blob--a"></span>
			<span class="rks-gallery__blob rks-gallery__blob--b"></span>
		</div>

		<div class="rks-container">

			<div class="rks-gallery__hero-intro">
				<span class="rks-gallery__eyebrow"><?php echo $svg_camera; ?> Our Moments</span>
				<h1>Snapshots from <span>our journey</span></h1>
				<p>Classrooms full of curious learners, hands-on workshops, achievement moments and the everyday joy of building skills at Rishikul Shiksha.</p>
			</div>

			<?php if ( ! empty( $polaroids ) ) : ?>
				<div class="rks-gallery__polaroids" aria-hidden="true">
					<?php foreach ( $polaroids as $idx => $p ) : ?>
						<figure class="rks-gallery__polaroid rks-gallery__polaroid--<?php echo (int) ( $idx + 1 ); ?>">
							<span class="rks-gallery__polaroid-pin"></span>
							<span class="rks-gallery__polaroid-media">
								<img src="<?php echo esc_url( $p['thumb'] ); ?>" alt="" loading="eager">
							</span>
						</figure>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

		</div>
	</section>

	<!-- ============ GRID ============ -->
	<section class="rks-gallery__section">
		<div class="rks-container">

			<?php if ( empty( $photos ) ) : ?>
				<div class="rks-gallery__empty">
					<div class="rks-gallery__empty-ic"><?php echo $svg_camera; ?></div>
					<h3>No photos yet</h3>
					<p>Add photos from <strong>WP Admin &rarr; Gallery</strong> to populate this page.</p>
				</div>
			<?php else : ?>
				<div class="rks-gallery__grid" id="rks-gallery-grid">
					<?php foreach ( $photos as $i => $p ) :
						// Pinterest-style varied tile sizes (deterministic via index)
						$mod = $i % 7;
						$span_class = '';
						if ( $mod === 0 )      $span_class = ' rks-gallery__card--tall';
						elseif ( $mod === 3 )  $span_class = ' rks-gallery__card--wide';
						elseif ( $mod === 5 )  $span_class = ' rks-gallery__card--big';
						?>
						<a class="rks-gallery__card<?php echo esc_attr( $span_class ); ?>"
						   href="<?php echo esc_url( $p['url'] ); ?>"
						   data-rks-lightbox
						   data-index="<?php echo (int) $i; ?>"
						   aria-label="<?php echo esc_attr( $p['alt'] ); ?>"
						   style="animation-delay: <?php echo (int) ( min( $i, 12 ) * 60 ); ?>ms;">
							<span class="rks-gallery__card-media">
								<img src="<?php echo esc_url( $p['thumb'] ); ?>" alt="<?php echo esc_attr( $p['alt'] ); ?>" loading="lazy">
								<span class="rks-gallery__card-overlay">
									<span class="rks-gallery__card-ic"><?php echo $svg_zoom; ?></span>
								</span>
								<span class="rks-gallery__card-num"><?php echo (int) ( $i + 1 ); ?> / <?php echo (int) $photo_count; ?></span>
							</span>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

		</div>
	</section>

	<!-- ============ HIGHLIGHTS STATS STRIP ============ -->
	<?php
	$svg_hl_camera = '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>';
	$svg_hl_cal    = '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>';
	$svg_hl_users  = '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>';
	$svg_hl_spark  = '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l2.39 6.96L21 10l-5.5 4 1.74 7L12 17.27 6.76 21 8.5 14 3 10l6.61-1.04L12 2z"/></svg>';
	$svg_cat_book  = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>';
	$svg_cat_mic   = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="2" width="6" height="12" rx="3"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="23"/><line x1="8" y1="23" x2="16" y2="23"/></svg>';
	$svg_cat_award = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><polyline points="8.21 13.89 7 22 12 19 17 22 15.79 13.88"/></svg>';
	$svg_cat_heart = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>';
	$svg_quote     = '<svg viewBox="0 0 24 24" width="26" height="26" fill="currentColor"><path d="M7.17 6C4.32 6 2 8.32 2 11.17v5.83a1 1 0 0 0 1 1h4.5a1 1 0 0 0 1-1v-4.5a1 1 0 0 0-1-1H5.6c.27-1.4 1.5-2.5 2.97-2.5a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1h-1.4zm10 0c-2.85 0-5.17 2.32-5.17 5.17v5.83a1 1 0 0 0 1 1h4.5a1 1 0 0 0 1-1v-4.5a1 1 0 0 0-1-1H15.6c.27-1.4 1.5-2.5 2.97-2.5a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1h-1.4z"/></svg>';
	?>
	<section class="rks-gallery__highlights">
		<div class="rks-container">
			<div class="rks-gallery__highlights-grid">
				<div class="rks-gallery__hl-tile">
					<span class="rks-gallery__hl-ic rks-gallery__hl-ic--red"><?php echo $svg_hl_camera; ?></span>
					<strong>150+</strong>
					<span class="rks-gallery__hl-label">Photos Captured</span>
				</div>
				<div class="rks-gallery__hl-tile">
					<span class="rks-gallery__hl-ic rks-gallery__hl-ic--mint"><?php echo $svg_hl_cal; ?></span>
					<strong>12+</strong>
					<span class="rks-gallery__hl-label">Events Hosted</span>
				</div>
				<div class="rks-gallery__hl-tile">
					<span class="rks-gallery__hl-ic rks-gallery__hl-ic--teal"><?php echo $svg_hl_users; ?></span>
					<strong>10,000+</strong>
					<span class="rks-gallery__hl-label">Learners Trained</span>
				</div>
				<div class="rks-gallery__hl-tile">
					<span class="rks-gallery__hl-ic rks-gallery__hl-ic--gold"><?php echo $svg_hl_spark; ?></span>
					<strong>5+</strong>
					<span class="rks-gallery__hl-label">Years of Memories</span>
				</div>
			</div>
		</div>
	</section>

	<!-- ============ CATEGORIES ============ -->
	<section class="rks-gallery__categories">
		<div class="rks-container">
			<div class="rks-gallery__section-head">
				<span class="rks-gallery__eyebrow"><?php echo $svg_camera; ?> Behind the Lens</span>
				<h2>What you'll see in our gallery</h2>
				<p>From quiet classroom moments to loud celebrations — every photo is a slice of life at Rishikul Shiksha.</p>
			</div>
			<div class="rks-gallery__categories-grid">
				<article class="rks-gallery__cat-card">
					<span class="rks-gallery__cat-chip rks-gallery__cat-chip--red"><?php echo $svg_cat_book; ?></span>
					<h3>Classroom Moments</h3>
					<p>Students learning, asking questions, and getting hands-on with real practice.</p>
				</article>
				<article class="rks-gallery__cat-card">
					<span class="rks-gallery__cat-chip rks-gallery__cat-chip--mint"><?php echo $svg_cat_mic; ?></span>
					<h3>Workshops &amp; Events</h3>
					<p>Guest lectures, expert sessions and special workshops that go beyond the syllabus.</p>
				</article>
				<article class="rks-gallery__cat-card">
					<span class="rks-gallery__cat-chip rks-gallery__cat-chip--gold"><?php echo $svg_cat_award; ?></span>
					<h3>Achievement Days</h3>
					<p>Certificate distributions, toppers and the proud smiles that make the effort worth it.</p>
				</article>
				<article class="rks-gallery__cat-card">
					<span class="rks-gallery__cat-chip rks-gallery__cat-chip--teal"><?php echo $svg_cat_heart; ?></span>
					<h3>Campus Life</h3>
					<p>Friendships, festivals and the everyday joy of being part of the Rishikul family.</p>
				</article>
			</div>
		</div>
	</section>

	<!-- ============ VOICES ============ -->
	<section class="rks-gallery__voices">
		<div class="rks-container">
			<div class="rks-gallery__section-head">
				<span class="rks-gallery__eyebrow"><?php echo $svg_spark; ?> Student Voices</span>
				<h2>What our students remember most</h2>
				<p>Small moments, big memories — straight from the learners who lived them.</p>
			</div>
			<div class="rks-gallery__voices-grid">
				<figure class="rks-gallery__voice-card">
					<span class="rks-gallery__voice-mark"><?php echo $svg_quote; ?></span>
					<blockquote>The classroom never felt like a classroom. It was more like a workshop where every doubt got solved on the spot.</blockquote>
					<figcaption>
						<strong>Priya Sharma</strong>
						<span>Tally &amp; Accounting · Batch 2024</span>
					</figcaption>
				</figure>
				<figure class="rks-gallery__voice-card">
					<span class="rks-gallery__voice-mark"><?php echo $svg_quote; ?></span>
					<blockquote>The annual event was the best day. We performed, we laughed, and the photos still make me smile every time.</blockquote>
					<figcaption>
						<strong>Manoj Kumar</strong>
						<span>Computer Basics · Batch 2023</span>
					</figcaption>
				</figure>
				<figure class="rks-gallery__voice-card">
					<span class="rks-gallery__voice-mark"><?php echo $svg_quote; ?></span>
					<blockquote>Sir and ma'am explained things until we actually understood — not just memorised. That changed how I study now.</blockquote>
					<figcaption>
						<strong>Akash Verma</strong>
						<span>Spoken English · Batch 2025</span>
					</figcaption>
				</figure>
			</div>
		</div>
	</section>


</main>

<!-- ============ LIGHTBOX ============ -->
<?php if ( ! empty( $photos ) ) : ?>
<div class="rks-lightbox" id="rks-lightbox" aria-hidden="true" role="dialog" aria-modal="true" aria-label="Photo viewer">
	<button class="rks-lightbox__btn rks-lightbox__close" type="button" aria-label="Close">
		<?php echo $svg_close; ?>
	</button>
	<button class="rks-lightbox__btn rks-lightbox__prev" type="button" aria-label="Previous">
		<?php echo $svg_prev; ?>
	</button>
	<button class="rks-lightbox__btn rks-lightbox__next" type="button" aria-label="Next">
		<?php echo $svg_next; ?>
	</button>
	<figure class="rks-lightbox__stage">
		<img class="rks-lightbox__img" src="" alt="">
		<figcaption class="rks-lightbox__caption"><span class="rks-lightbox__index"></span></figcaption>
	</figure>
</div>
<script>
(function(){
	'use strict';
	var photos = <?php echo wp_json_encode( array_map( static function ( $p ) {
		return [ 'url' => $p['url'], 'alt' => $p['alt'] ];
	}, $photos ) ); ?>;
	if (!photos || !photos.length) return;

	var box   = document.getElementById('rks-lightbox');
	var img   = box.querySelector('.rks-lightbox__img');
	var idxEl = box.querySelector('.rks-lightbox__index');
	var idx   = 0;

	function open(i) {
		idx = (i + photos.length) % photos.length;
		img.src = photos[idx].url;
		img.alt = photos[idx].alt || '';
		idxEl.textContent = (idx + 1) + ' / ' + photos.length;
		box.classList.add('is-open');
		box.setAttribute('aria-hidden', 'false');
		document.body.style.overflow = 'hidden';
	}
	function close() {
		box.classList.remove('is-open');
		box.setAttribute('aria-hidden', 'true');
		document.body.style.overflow = '';
	}
	function next(){ open(idx + 1); }
	function prev(){ open(idx - 1); }

	document.querySelectorAll('[data-rks-lightbox]').forEach(function(a){
		a.addEventListener('click', function(e){
			e.preventDefault();
			open(parseInt(a.dataset.index || 0, 10));
		});
	});
	box.querySelector('.rks-lightbox__close').addEventListener('click', close);
	box.querySelector('.rks-lightbox__next').addEventListener('click', next);
	box.querySelector('.rks-lightbox__prev').addEventListener('click', prev);
	box.addEventListener('click', function(e){
		if (e.target === box || e.target.classList.contains('rks-lightbox__stage')) close();
	});
	document.addEventListener('keydown', function(e){
		if (!box.classList.contains('is-open')) return;
		if (e.key === 'Escape') close();
		else if (e.key === 'ArrowRight') next();
		else if (e.key === 'ArrowLeft')  prev();
	});

	// Scroll fade-in for cards
	if ('IntersectionObserver' in window) {
		var io = new IntersectionObserver(function(entries){
			entries.forEach(function(en){
				if (en.isIntersecting) {
					en.target.classList.add('is-in');
					io.unobserve(en.target);
				}
			});
		}, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });
		document.querySelectorAll('.rks-gallery__card').forEach(function(c){ io.observe(c); });
	} else {
		document.querySelectorAll('.rks-gallery__card').forEach(function(c){ c.classList.add('is-in'); });
	}
})();
</script>
<?php endif; ?>

<?php get_footer(); ?>
