<?php
/**
 * Single Post Template
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

$svg_calendar = '<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>';
$svg_clock    = '<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>';
$svg_user     = '<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>';
$svg_eye      = '<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
$svg_chev     = '<svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>';
$svg_arrow_l  = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>';
$svg_arrow_r  = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>';
$svg_search   = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>';
$svg_layers   = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>';
$svg_history  = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><polyline points="3 3 3 8 8 8"/><polyline points="12 7 12 12 15 14"/></svg>';
$svg_tag      = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>';
$svg_share    = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>';
$svg_cap      = '<svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"/></svg>';

$svg_twitter = '<svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>';
$svg_fb_brand = '<svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12c0 4.84 3.44 8.87 8 9.8V15H8v-3h2V9.5C10 7.57 11.57 6 13.5 6H16v3h-2c-.55 0-1 .45-1 1v2h3v3h-3v6.95c5.05-.5 9-4.76 9-9.95z"/></svg>';
$svg_wa_brand = '<svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M.057 24l1.687-6.163a11.867 11.867 0 0 1-1.587-5.946C.16 5.335 5.495 0 12.05 0a11.817 11.817 0 0 1 8.413 3.488 11.824 11.824 0 0 1 3.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892a11.9 11.9 0 0 1-5.688-1.448L.057 24z"/></svg>';
$svg_link = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>';

$rks_word_count = str_word_count( wp_strip_all_tags( get_post()->post_content ?? '' ) );
$rks_read_min   = max( 1, (int) ceil( $rks_word_count / 200 ) );
?>

<main id="primary" class="rks-blog rks-blog--single">

	<section class="rks-blog__post-hero">
		<div class="rks-container">
			<nav class="rks-blog__crumb" aria-label="Breadcrumb">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
				<span><?php echo $svg_chev; ?></span>
				<a href="<?php echo esc_url( get_post_type_archive_link( 'post' ) ?: home_url( '/blog/' ) ); ?>">Blog</a>
				<span><?php echo $svg_chev; ?></span>
				<span aria-current="page"><?php echo esc_html( wp_trim_words( get_the_title(), 8 ) ); ?></span>
			</nav>

			<?php
			$cats = get_the_category();
			if ( ! empty( $cats ) ) :
			?>
				<a href="<?php echo esc_url( get_category_link( $cats[0] ) ); ?>" class="rks-blog__post-cat"><?php echo esc_html( $cats[0]->name ); ?></a>
			<?php endif; ?>

			<h1 class="rks-blog__post-title"><?php the_title(); ?></h1>

			<div class="rks-blog__post-byline">
				<div class="rks-blog__post-author">
					<?php echo get_avatar( get_the_author_meta( 'ID' ), 40, '', '', [ 'class' => 'rks-blog__post-avatar' ] ); ?>
					<div>
						<strong><?php the_author(); ?></strong>
						<small><?php echo $svg_calendar; ?><?php echo esc_html( get_the_date() ); ?> &middot; <?php echo $svg_clock; ?><?php echo esc_html( $rks_read_min ); ?> min read</small>
					</div>
				</div>
				<div class="rks-blog__post-share">
					<span><?php echo $svg_share; ?> Share</span>
					<a href="https://twitter.com/intent/tweet?url=<?php echo urlencode( get_permalink() ); ?>&text=<?php echo urlencode( get_the_title() ); ?>" target="_blank" rel="noopener" aria-label="Share on Twitter" class="rks-blog__share-btn rks-blog__share-btn--tw"><?php echo $svg_twitter; ?></a>
					<a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode( get_permalink() ); ?>" target="_blank" rel="noopener" aria-label="Share on Facebook" class="rks-blog__share-btn rks-blog__share-btn--fb"><?php echo $svg_fb_brand; ?></a>
					<a href="https://wa.me/?text=<?php echo urlencode( get_the_title() . ' ' . get_permalink() ); ?>" target="_blank" rel="noopener" aria-label="Share on WhatsApp" class="rks-blog__share-btn rks-blog__share-btn--wa"><?php echo $svg_wa_brand; ?></a>
					<button type="button" class="rks-blog__share-btn rks-blog__share-btn--link" data-copy="<?php echo esc_attr( get_permalink() ); ?>" aria-label="Copy link"><?php echo $svg_link; ?></button>
				</div>
			</div>
		</div>
	</section>

	<section class="rks-blog__wrap">
		<div class="rks-container rks-blog__layout">

			<main class="rks-blog__main rks-blog__main--single">
				<?php while ( have_posts() ) : the_post(); ?>

					<article <?php post_class( 'rks-blog__post' ); ?>>

						<?php if ( has_post_thumbnail() ) : ?>
							<figure class="rks-blog__post-cover">
								<?php the_post_thumbnail( 'large' ); ?>
							</figure>
						<?php endif; ?>

						<div class="rks-blog__post-content">
							<?php the_content(); ?>
						</div>

						<?php
						$tags = get_the_tags();
						if ( ! empty( $tags ) ) :
						?>
							<div class="rks-blog__post-tags">
								<strong><?php echo $svg_tag; ?> Tags</strong>
								<?php foreach ( $tags as $tag ) : ?>
									<a href="<?php echo esc_url( get_tag_link( $tag->term_id ) ); ?>"><?php echo esc_html( $tag->name ); ?></a>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>

						<div class="rks-blog__post-author-card">
							<?php echo get_avatar( get_the_author_meta( 'ID' ), 64, '', '', [ 'class' => 'rks-blog__author-avatar' ] ); ?>
							<div>
								<small>Written by</small>
								<strong><?php the_author(); ?></strong>
								<p><?php echo esc_html( get_the_author_meta( 'description' ) ?: 'Member of the Rishikul Shiksha team — writing about courses, careers, and learning.' ); ?></p>
							</div>
						</div>

					</article>

					<?php
					$prev_post = get_previous_post();
					$next_post = get_next_post();
					if ( $prev_post || $next_post ) :
					?>
					<nav class="rks-blog__post-nav" aria-label="Post navigation">
						<?php if ( $prev_post ) : ?>
							<a href="<?php echo esc_url( get_permalink( $prev_post ) ); ?>" class="rks-blog__post-nav-link rks-blog__post-nav-link--prev">
								<small><?php echo $svg_arrow_l; ?> Previous</small>
								<strong><?php echo esc_html( wp_trim_words( get_the_title( $prev_post ), 10 ) ); ?></strong>
							</a>
						<?php else : ?>
							<span></span>
						<?php endif; ?>
						<?php if ( $next_post ) : ?>
							<a href="<?php echo esc_url( get_permalink( $next_post ) ); ?>" class="rks-blog__post-nav-link rks-blog__post-nav-link--next">
								<small>Next <?php echo $svg_arrow_r; ?></small>
								<strong><?php echo esc_html( wp_trim_words( get_the_title( $next_post ), 10 ) ); ?></strong>
							</a>
						<?php endif; ?>
					</nav>
					<?php endif; ?>

					<?php
					$related_args = [
						'posts_per_page'      => 3,
						'post__not_in'        => [ get_the_ID() ],
						'ignore_sticky_posts' => 1,
					];
					if ( ! empty( $cats ) ) {
						$related_args['category__in'] = wp_list_pluck( $cats, 'term_id' );
					}
					$related = new WP_Query( $related_args );
					if ( $related->have_posts() ) :
					?>
					<section class="rks-blog__related">
						<header>
							<span class="rks-eyebrow">Keep Reading</span>
							<h2>Related Articles</h2>
						</header>
						<div class="rks-blog__related-grid">
							<?php while ( $related->have_posts() ) : $related->the_post(); ?>
								<article class="rks-blog__related-card">
									<a href="<?php the_permalink(); ?>" class="rks-blog__related-thumb">
										<?php if ( has_post_thumbnail() ) : the_post_thumbnail( 'medium' ); else : ?>
											<div class="rks-blog__fallback rks-blog__fallback--mini"><span><?php echo esc_html( strtoupper( substr( get_the_title(), 0, 1 ) ) ); ?></span></div>
										<?php endif; ?>
									</a>
									<div>
										<?php $rc = get_the_category(); if ( ! empty( $rc ) ) : ?>
											<span class="rks-blog__related-cat"><?php echo esc_html( $rc[0]->name ); ?></span>
										<?php endif; ?>
										<h3><a href="<?php the_permalink(); ?>"><?php echo esc_html( wp_trim_words( get_the_title(), 12 ) ); ?></a></h3>
										<small><?php echo $svg_calendar; ?><?php echo esc_html( get_the_date( 'M j' ) ); ?></small>
									</div>
								</article>
							<?php endwhile; wp_reset_postdata(); ?>
						</div>
					</section>
					<?php endif; ?>

					<?php if ( comments_open() || get_comments_number() ) : ?>
						<div class="rks-blog__comments">
							<?php comments_template(); ?>
						</div>
					<?php endif; ?>

				<?php endwhile; ?>
			</main>

			<aside class="rks-blog__sidebar">

				<div class="rks-blog__widget">
					<h3><span class="rks-blog__widget-ic"><?php echo $svg_search; ?></span>Search</h3>
					<form role="search" method="get" class="rks-blog__search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
						<input type="search" name="s" placeholder="Search articles…" value="<?php echo esc_attr( get_search_query() ); ?>">
						<button type="submit" aria-label="Search"><?php echo $svg_search; ?></button>
					</form>
				</div>

				<div class="rks-blog__widget">
					<h3><span class="rks-blog__widget-ic"><?php echo $svg_layers; ?></span>Categories</h3>
					<ul class="rks-blog__cats">
						<?php wp_list_categories( [ 'title_li' => '', 'show_count' => true ] ); ?>
					</ul>
				</div>

				<div class="rks-blog__widget">
					<h3><span class="rks-blog__widget-ic"><?php echo $svg_history; ?></span>Recent Posts</h3>
					<ul class="rks-blog__recent">
						<?php
						$recent = new WP_Query( [ 'posts_per_page' => 5, 'ignore_sticky_posts' => 1, 'post__not_in' => [ get_the_ID() ] ] );
						if ( $recent->have_posts() ) :
							while ( $recent->have_posts() ) : $recent->the_post();
								?>
								<li>
									<a href="<?php the_permalink(); ?>" class="rks-blog__recent-thumb">
										<?php if ( has_post_thumbnail() ) : the_post_thumbnail( 'thumbnail' ); else : ?>
											<div class="rks-blog__fallback rks-blog__fallback--mini"><span><?php echo esc_html( strtoupper( substr( get_the_title(), 0, 1 ) ) ); ?></span></div>
										<?php endif; ?>
									</a>
									<div>
										<a href="<?php the_permalink(); ?>" class="rks-blog__recent-title"><?php echo esc_html( wp_trim_words( get_the_title(), 11 ) ); ?></a>
										<small><?php echo $svg_calendar; ?><?php echo esc_html( get_the_date( 'M j' ) ); ?></small>
									</div>
								</li>
								<?php
							endwhile;
							wp_reset_postdata();
						endif;
						?>
					</ul>
				</div>

				<div class="rks-blog__widget rks-blog__widget--cta">
					<span class="rks-blog__widget-cta-ic"><?php echo $svg_cap; ?></span>
					<h3>Ready to learn?</h3>
					<p>Browse our courses and start your learning journey today.</p>
					<a href="<?php echo esc_url( home_url( '/courses/' ) ); ?>" class="rks-blog__widget-cta-btn">View Courses</a>
				</div>

			</aside>

		</div>
	</section>

</main>

<script>
document.addEventListener('click', function(e){
	var btn = e.target.closest('.rks-blog__share-btn--link');
	if (!btn) return;
	e.preventDefault();
	var url = btn.getAttribute('data-copy');
	if (navigator.clipboard && url) {
		navigator.clipboard.writeText(url).then(function(){
			var old = btn.getAttribute('aria-label');
			btn.setAttribute('aria-label', 'Link copied');
			btn.classList.add('is-copied');
			setTimeout(function(){
				btn.setAttribute('aria-label', old);
				btn.classList.remove('is-copied');
			}, 1500);
		});
	}
});
</script>

<?php get_footer(); ?>
