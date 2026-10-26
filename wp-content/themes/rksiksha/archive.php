<?php
/**
 * Archive Template (categories, tags, author, date)
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
?>

<section class="rks-blog__hero">
	<div class="rks-container">
		<nav class="rks-blog__breadcrumb" aria-label="Breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
			<span>/</span>
			<span>Archive</span>
		</nav>
		<span class="rks-eyebrow"><?php echo esc_html( get_the_archive_title() ); ?></span>
		<h1 class="rks-blog__hero-title"><?php echo esc_html( wp_strip_all_tags( get_the_archive_title() ) ); ?></h1>
		<?php if ( get_the_archive_description() ) : ?>
			<p class="rks-blog__hero-sub"><?php echo wp_kses_post( get_the_archive_description() ); ?></p>
		<?php endif; ?>
	</div>
</section>

<section class="rks-blog">
	<div class="rks-container rks-blog__layout">
		<main class="rks-blog__main">
			<?php if ( have_posts() ) : ?>
				<div class="rks-blog__grid">
					<?php while ( have_posts() ) : the_post(); ?>
						<article <?php post_class( 'rks-blog__card' ); ?>>
							<a href="<?php the_permalink(); ?>" class="rks-blog__thumb">
								<?php if ( has_post_thumbnail() ) : the_post_thumbnail( 'medium_large' ); else : ?>
									<div class="rks-blog__thumb-fallback" aria-hidden="true">
										<span><?php echo esc_html( strtoupper( substr( get_the_title(), 0, 1 ) ) ); ?></span>
									</div>
								<?php endif; ?>
							</a>
							<div class="rks-blog__body">
								<div class="rks-blog__meta">
									<?php
									$cats = get_the_category();
									if ( ! empty( $cats ) ) : ?>
										<a href="<?php echo esc_url( get_category_link( $cats[0] ) ); ?>" class="rks-blog__cat"><?php echo esc_html( $cats[0]->name ); ?></a>
									<?php endif; ?>
									<span class="rks-blog__date"><?php echo esc_html( get_the_date() ); ?></span>
								</div>
								<h2 class="rks-blog__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
								<p class="rks-blog__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 22 ) ); ?></p>
								<div class="rks-blog__foot">
									<span class="rks-blog__author">By <?php the_author(); ?></span>
									<a href="<?php the_permalink(); ?>" class="rks-blog__readmore">Read More →</a>
								</div>
							</div>
						</article>
					<?php endwhile; ?>
				</div>
				<nav class="rks-blog__pagination">
					<?php echo paginate_links( [ 'prev_text' => '← Previous', 'next_text' => 'Next →' ] ); ?>
				</nav>
			<?php else : ?>
				<div class="rks-blog__empty">
					<h2>No posts found</h2>
					<p>Try a different search or browse other categories.</p>
				</div>
			<?php endif; ?>
		</main>

		<aside class="rks-blog__sidebar">
			<div class="rks-blog__widget">
				<h3>Search</h3>
				<?php get_search_form(); ?>
			</div>
			<div class="rks-blog__widget">
				<h3>Categories</h3>
				<ul class="rks-blog__cats">
					<?php wp_list_categories( [ 'title_li' => '', 'show_count' => true ] ); ?>
				</ul>
			</div>
		</aside>
	</div>
</section>

<?php get_footer(); ?>
