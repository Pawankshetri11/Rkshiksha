<?php
/**
 * Blog Posts Index — Used when a static front page is set and a separate "Posts page" is selected.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

$svg_calendar = '<svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>';
$svg_clock    = '<svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>';
$svg_user     = '<svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>';
$svg_arrow    = '<svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>';
$svg_search   = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>';
$svg_chev     = '<svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>';
$svg_inbox    = '<svg viewBox="0 0 24 24" width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg>';
$svg_layers   = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>';
$svg_history  = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><polyline points="3 3 3 8 8 8"/><polyline points="12 7 12 12 15 14"/></svg>';
$svg_tag      = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>';
$svg_cap      = '<svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"/></svg>';

$rks_read_min = static function () : int {
	$wc = str_word_count( wp_strip_all_tags( get_the_content() ) );
	return max( 1, (int) ceil( $wc / 200 ) );
};
?>

<main id="primary" class="rks-blog">

	<!-- ============ HERO ============ -->
	<section class="rks-blog__hero">
		<div class="rks-container">
			<nav class="rks-blog__crumb" aria-label="Breadcrumb">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
				<span><?php echo $svg_chev; ?></span>
				<span aria-current="page">Blog</span>
			</nav>
			<span class="rks-eyebrow">Insights &amp; Stories</span>
			<h1>The Rishikul Shiksha Journal</h1>
			<p>Tutorials, career advice and learning tips from our instructors and counsellors.</p>
		</div>
	</section>

	<!-- ============ LAYOUT (main + sidebar) ============ -->
	<section class="rks-blog__wrap">
		<div class="rks-container rks-blog__layout">

			<main class="rks-blog__main">
				<?php if ( have_posts() ) : ?>

					<div class="rks-blog__grid">
						<?php while ( have_posts() ) : the_post(); ?>
							<?php $cats = get_the_category(); ?>
							<article <?php post_class( 'rks-blog__card' ); ?>>
								<a href="<?php the_permalink(); ?>" class="rks-blog__thumb">
									<?php if ( has_post_thumbnail() ) : ?>
										<?php the_post_thumbnail( 'medium_large' ); ?>
									<?php else : ?>
										<div class="rks-blog__fallback" aria-hidden="true"><span><?php echo esc_html( strtoupper( substr( get_the_title(), 0, 1 ) ) ); ?></span></div>
									<?php endif; ?>
									<?php if ( ! empty( $cats ) ) : ?>
										<span class="rks-blog__thumb-cat"><?php echo esc_html( $cats[0]->name ); ?></span>
									<?php endif; ?>
								</a>
								<div class="rks-blog__body">
									<h2 class="rks-blog__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
									<p class="rks-blog__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 18 ) ); ?></p>
									<div class="rks-blog__meta">
										<span><?php echo $svg_calendar; ?><?php echo esc_html( get_the_date( 'M j, Y' ) ); ?></span>
										<span class="rks-blog__sep"></span>
										<span><?php echo $svg_clock; ?><?php echo $rks_read_min(); ?> min</span>
										<span class="rks-blog__sep"></span>
										<span><?php echo $svg_user; ?><?php the_author(); ?></span>
									</div>
								</div>
							</article>
						<?php endwhile; ?>
					</div>

					<?php
					$pag = paginate_links( [
						'prev_text' => '&larr; Prev',
						'next_text' => 'Next &rarr;',
						'echo'      => false,
					] );
					if ( $pag ) :
					?>
					<nav class="rks-blog__pagination" aria-label="Posts pagination">
						<?php echo $pag; ?>
					</nav>
					<?php endif; ?>

				<?php else : ?>

					<div class="rks-blog__empty">
						<span class="rks-blog__empty-icon"><?php echo $svg_inbox; ?></span>
						<h2>No articles yet</h2>
						<p>We're working on fresh content. Meanwhile, explore our courses.</p>
						<a href="<?php echo esc_url( home_url( '/courses/' ) ); ?>" class="rks-blog__empty-cta">Browse Courses <?php echo $svg_arrow; ?></a>
					</div>

				<?php endif; ?>
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
						<?php
						wp_list_categories( [
							'title_li'   => '',
							'show_count' => true,
						] );
						?>
					</ul>
				</div>

				<div class="rks-blog__widget">
					<h3><span class="rks-blog__widget-ic"><?php echo $svg_history; ?></span>Recent Posts</h3>
					<ul class="rks-blog__recent">
						<?php
						$recent = new WP_Query( [ 'posts_per_page' => 5, 'ignore_sticky_posts' => 1 ] );
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

				<?php
				$tags = get_tags( [ 'number' => 12, 'orderby' => 'count', 'order' => 'DESC' ] );
				if ( ! empty( $tags ) ) :
				?>
				<div class="rks-blog__widget">
					<h3><span class="rks-blog__widget-ic"><?php echo $svg_tag; ?></span>Popular Tags</h3>
					<div class="rks-blog__tagcloud">
						<?php foreach ( $tags as $t ) : ?>
							<a href="<?php echo esc_url( get_tag_link( $t->term_id ) ); ?>"><?php echo esc_html( $t->name ); ?></a>
						<?php endforeach; ?>
					</div>
				</div>
				<?php endif; ?>

				<div class="rks-blog__widget rks-blog__widget--cta">
					<span class="rks-blog__widget-cta-ic"><?php echo $svg_cap; ?></span>
					<h3>Ready to learn?</h3>
					<p>Browse our courses and start your learning journey today.</p>
					<a href="<?php echo esc_url( home_url( '/courses/' ) ); ?>" class="rks-blog__widget-cta-btn">View Courses <?php echo $svg_arrow; ?></a>
				</div>

			</aside>

		</div>
	</section>

</main>

<?php get_footer(); ?>
