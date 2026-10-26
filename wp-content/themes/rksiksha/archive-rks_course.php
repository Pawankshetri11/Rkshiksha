<?php
/**
 * Course Archive — /courses/
 */
if ( ! defined( 'ABSPATH' ) ) exit;

use RKSiksha\Core\Modules\Courses;

get_header();

$settings = (array) get_option( 'rks_settings', [] );
$currency = (string) ( $settings['currency_symbol'] ?? '₹' );

$svg_search  = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>';
$svg_book    = '<svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>';
$svg_clock   = '<svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>';
$svg_signal  = '<svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>';
$svg_users   = '<svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>';
$svg_star    = '<svg viewBox="0 0 24 24" width="12" height="12" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';
$svg_arrow   = '<svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>';
$svg_chev    = '<svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>';
$svg_phone   = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13.5a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2.69h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.69 2.81a2 2 0 0 1-.45 2.11L7.91 10.5a16 16 0 0 0 5.61 5.61l1.27-.77a2 2 0 0 1 2.11-.45c.91.33 1.85.57 2.81.69A2 2 0 0 1 22 17.92z"/></svg>';
$svg_wa      = '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M.057 24l1.687-6.163a11.867 11.867 0 0 1-1.587-5.946C.16 5.335 5.495 0 12.05 0a11.817 11.817 0 0 1 8.413 3.488 11.824 11.824 0 0 1 3.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892a11.9 11.9 0 0 1-5.688-1.448L.057 24zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884a9.86 9.86 0 0 0 1.516 5.26L3.5 19.85l1.654-1.657zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.296-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>';
$svg_x       = '<svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';

$categories = get_terms( [
	'taxonomy'   => Courses::TAX_CATEGORY,
	'hide_empty' => true,
	'orderby'    => 'count',
	'order'      => 'DESC',
] );

$current_term  = is_tax( Courses::TAX_CATEGORY ) ? get_queried_object() : null;
$total_courses = (int) wp_count_posts( Courses::CPT_COURSE )->publish;
$cat_count     = ( ! empty( $categories ) && ! is_wp_error( $categories ) ) ? count( $categories ) : 0;
$current_sort  = isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : 'date';
$current_level = isset( $_GET['level'] ) ? sanitize_key( $_GET['level'] ) : '';
$current_price = isset( $_GET['price'] ) ? sanitize_key( $_GET['price'] ) : '';
$current_query = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
$archive_url   = get_post_type_archive_link( Courses::CPT_COURSE );

$build_url = static function ( array $params = [] ) use ( $archive_url, $current_term ) : string {
	$base = $current_term ? get_term_link( $current_term ) : $archive_url;
	return add_query_arg( $params, $base );
};

$has_filters = $current_level || $current_price || $current_query || $current_sort !== 'date';
?>

<main id="primary" class="rks-courses">

	<!-- ============ HERO ============ -->
	<section class="rks-courses__hero">
		<div class="rks-container">
			<nav class="rks-courses__crumb" aria-label="Breadcrumb">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
				<span><?php echo $svg_chev; ?></span>
				<?php if ( $current_term ) : ?>
					<a href="<?php echo esc_url( $archive_url ); ?>">Courses</a>
					<span><?php echo $svg_chev; ?></span>
					<span aria-current="page"><?php echo esc_html( $current_term->name ); ?></span>
				<?php else : ?>
					<span aria-current="page">Courses</span>
				<?php endif; ?>
			</nav>

			<span class="rks-eyebrow">Learning Catalog</span>
			<h1><?php echo esc_html( $current_term ? $current_term->name : 'Build skills that get you hired' ); ?></h1>
			<p><?php echo $current_term && $current_term->description
				? esc_html( $current_term->description )
				: 'Expert-led, hands-on courses across coding, design, accounting and career-readiness — learn at your own pace, certified on completion.'; ?></p>

			<form role="search" method="get" class="rks-courses__search-hero" action="<?php echo esc_url( $archive_url ); ?>">
				<span><?php echo $svg_search; ?></span>
				<input type="search" name="s" placeholder="Search courses by name, topic or skill…" value="<?php echo esc_attr( $current_query ); ?>">
				<input type="hidden" name="post_type" value="<?php echo esc_attr( Courses::CPT_COURSE ); ?>">
				<button type="submit">Search</button>
			</form>
		</div>
	</section>

	<!-- ============ FILTER BAR ============ -->
	<section class="rks-courses__filter">
		<div class="rks-container">
			<form method="get" class="rks-courses__filter-form" action="<?php echo esc_url( $archive_url ); ?>" id="rks-courses-filters">
				<?php if ( $current_query ) : ?>
					<input type="hidden" name="s" value="<?php echo esc_attr( $current_query ); ?>">
					<input type="hidden" name="post_type" value="<?php echo esc_attr( Courses::CPT_COURSE ); ?>">
				<?php endif; ?>

				<?php if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
				<div class="rks-courses__select">
					<label for="rks-category">Category</label>
					<select id="rks-category" onchange="if(this.value){window.location.href=this.value}else{window.location.href='<?php echo esc_url( $archive_url ); ?>'}">
						<option value=""><?php echo ! $current_term ? '⭐ All Courses (' . (int) $total_courses . ')' : 'All Courses (' . (int) $total_courses . ')'; ?></option>
						<?php foreach ( $categories as $cat ) : ?>
							<option value="<?php echo esc_url( get_term_link( $cat ) ); ?>" <?php echo $current_term && $current_term->term_id === $cat->term_id ? 'selected' : ''; ?>>
								<?php echo esc_html( $cat->name ); ?> (<?php echo (int) $cat->count; ?>)
							</option>
						<?php endforeach; ?>
					</select>
				</div>
				<?php endif; ?>

				<div class="rks-courses__select">
					<label for="rks-level">Level</label>
					<select id="rks-level" name="level" onchange="this.form.submit()">
						<option value="">Any level</option>
						<option value="beginner" <?php selected( $current_level, 'beginner' ); ?>>Beginner</option>
						<option value="intermediate" <?php selected( $current_level, 'intermediate' ); ?>>Intermediate</option>
						<option value="advanced" <?php selected( $current_level, 'advanced' ); ?>>Advanced</option>
					</select>
				</div>
				<div class="rks-courses__select">
					<label for="rks-price">Price</label>
					<select id="rks-price" name="price" onchange="this.form.submit()">
						<option value="">All prices</option>
						<option value="free" <?php selected( $current_price, 'free' ); ?>>Free</option>
						<option value="paid" <?php selected( $current_price, 'paid' ); ?>>Paid</option>
					</select>
				</div>
				<div class="rks-courses__select">
					<label for="rks-sort">Sort by</label>
					<select id="rks-sort" name="orderby" onchange="this.form.submit()">
						<option value="date" <?php selected( $current_sort, 'date' ); ?>>Newest</option>
						<option value="title" <?php selected( $current_sort, 'title' ); ?>>A – Z</option>
						<option value="price_low" <?php selected( $current_sort, 'price_low' ); ?>>Price: Low → High</option>
						<option value="price_high" <?php selected( $current_sort, 'price_high' ); ?>>Price: High → Low</option>
						<option value="rating" <?php selected( $current_sort, 'rating' ); ?>>Top rated</option>
					</select>
				</div>

				<?php if ( $has_filters || $current_term ) : ?>
					<a href="<?php echo esc_url( $archive_url ); ?>" class="rks-courses__filter-reset">Reset filters</a>
				<?php endif; ?>
			</form>
		</div>
	</section>

	<!-- ============ RESULT BAR + GRID ============ -->
	<section class="rks-courses__wrap">
		<div class="rks-container">

			<div class="rks-courses__result-bar">
				<div>
					<strong><?php echo (int) $GLOBALS['wp_query']->found_posts; ?></strong>
					course<?php echo $GLOBALS['wp_query']->found_posts === 1 ? '' : 's'; ?> found
					<?php if ( $current_term ) : ?>
						in <em><?php echo esc_html( $current_term->name ); ?></em>
					<?php endif; ?>
				</div>
				<?php if ( $has_filters ) : ?>
					<div class="rks-courses__active">
						<small>Filters:</small>
						<?php if ( $current_query ) : ?>
							<a href="<?php echo esc_url( $build_url( array_filter( [ 'level' => $current_level ?: null, 'price' => $current_price ?: null ] ) ) ); ?>" class="rks-courses__active-tag">"<?php echo esc_html( $current_query ); ?>" <?php echo $svg_x; ?></a>
						<?php endif; ?>
						<?php if ( $current_level ) : ?>
							<a href="<?php echo esc_url( $build_url( array_filter( [ 'price' => $current_price ?: null, 's' => $current_query ?: null ] ) ) ); ?>" class="rks-courses__active-tag"><?php echo esc_html( ucfirst( $current_level ) ); ?> <?php echo $svg_x; ?></a>
						<?php endif; ?>
						<?php if ( $current_price ) : ?>
							<a href="<?php echo esc_url( $build_url( array_filter( [ 'level' => $current_level ?: null, 's' => $current_query ?: null ] ) ) ); ?>" class="rks-courses__active-tag"><?php echo $current_price === 'free' ? 'Free' : 'Paid'; ?> <?php echo $svg_x; ?></a>
						<?php endif; ?>
						<a href="<?php echo esc_url( $current_term ? get_term_link( $current_term ) : $archive_url ); ?>" class="rks-courses__active-clear">Clear all</a>
					</div>
				<?php endif; ?>
			</div>

			<?php if ( have_posts() ) : ?>
				<div class="rks-courses__grid">
					<?php while ( have_posts() ) : the_post();
						$id          = get_the_ID();
						$is_paid     = (bool) get_post_meta( $id, Courses::M_IS_PAID, true );
						$price       = (float) get_post_meta( $id, Courses::M_PRICE, true );
						$rating      = (float) get_post_meta( $id, Courses::M_RATING, true );
						$students    = (int) get_post_meta( $id, Courses::M_STUDENTS_COUNT, true );
						$lessons     = (int) get_post_meta( $id, Courses::M_LESSON_COUNT, true );
						$level       = (string) get_post_meta( $id, Courses::M_LEVEL, true );
						$duration    = (string) get_post_meta( $id, Courses::M_DURATION, true );
						$short_desc  = (string) get_post_meta( $id, Courses::M_SHORT_DESC, true );
						$cats        = wp_get_object_terms( $id, Courses::TAX_CATEGORY );
						$cat         = ( ! is_wp_error( $cats ) && ! empty( $cats ) ) ? $cats[0] : null;
					?>
						<article class="rks-courses__card">
							<a href="<?php the_permalink(); ?>" class="rks-courses__card-thumb">
								<?php if ( has_post_thumbnail() ) : the_post_thumbnail( 'medium_large' ); else : ?>
									<div class="rks-courses__card-fallback" aria-hidden="true">
										<span><?php echo esc_html( strtoupper( substr( get_the_title(), 0, 1 ) ) ); ?></span>
									</div>
								<?php endif; ?>
								<div class="rks-courses__card-overlay">
									<?php if ( $cat ) : ?>
										<span class="rks-courses__card-badge"><?php echo esc_html( $cat->name ); ?></span>
									<?php endif; ?>
									<?php if ( ! $is_paid ) : ?>
										<span class="rks-courses__card-free">Free</span>
									<?php endif; ?>
								</div>
							</a>

							<div class="rks-courses__card-body">
								<h3 class="rks-courses__card-title">
									<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
								</h3>

								<?php if ( $short_desc ) : ?>
									<p class="rks-courses__card-desc"><?php echo esc_html( wp_trim_words( $short_desc, 16 ) ); ?></p>
								<?php elseif ( has_excerpt() ) : ?>
									<p class="rks-courses__card-desc"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 16 ) ); ?></p>
								<?php endif; ?>

								<div class="rks-courses__card-meta">
									<?php if ( $lessons > 0 ) : ?>
										<span><?php echo $svg_book; ?> <?php echo (int) $lessons; ?> lessons</span>
									<?php endif; ?>
									<?php if ( $duration ) : ?>
										<span><?php echo $svg_clock; ?> <?php echo esc_html( $duration ); ?></span>
									<?php endif; ?>
									<?php if ( $level ) : ?>
										<span><?php echo $svg_signal; ?> <?php echo esc_html( ucfirst( str_replace( '-', ' ', $level ) ) ); ?></span>
									<?php endif; ?>
								</div>

								<?php if ( $rating > 0 || $students > 0 ) : ?>
									<div class="rks-courses__card-social">
										<?php if ( $rating > 0 ) : ?>
											<span class="rks-courses__card-rating"><?php echo $svg_star; ?> <?php echo esc_html( number_format( $rating, 1 ) ); ?></span>
										<?php endif; ?>
										<?php if ( $students > 0 ) : ?>
											<span class="rks-courses__card-students"><?php echo $svg_users; ?> <?php echo number_format( $students ); ?> learners</span>
										<?php endif; ?>
									</div>
								<?php endif; ?>

								<div class="rks-courses__card-foot">
									<span class="rks-courses__card-price">
										<?php if ( $is_paid ) : ?>
											<?php echo esc_html( $currency . number_format( $price, 0 ) ); ?>
										<?php else : ?>
											<em>Free</em>
										<?php endif; ?>
									</span>
									<a href="<?php the_permalink(); ?>" class="rks-courses__card-link">View <?php echo $svg_arrow; ?></a>
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
				<nav class="rks-courses__pagination" aria-label="Courses pagination">
					<?php echo $pag; ?>
				</nav>
				<?php endif; ?>

			<?php else : ?>
				<div class="rks-courses__empty">
					<div class="rks-courses__empty-ic"><?php echo $svg_search; ?></div>
					<h2>No courses found</h2>
					<p>We couldn't find any courses<?php echo $current_term ? ' in <strong>' . esc_html( $current_term->name ) . '</strong>' : ''; ?> matching your filters. Try widening your search.</p>
					<a href="<?php echo esc_url( $archive_url ); ?>" class="rks-courses__empty-cta">Browse all courses <?php echo $svg_arrow; ?></a>
				</div>
			<?php endif; ?>

		</div>
	</section>

	<!-- ============ CTA ============ -->
	<section class="rks-courses__cta">
		<div class="rks-container">
			<div class="rks-courses__cta-card">
				<div>
					<span class="rks-eyebrow rks-courses__cta-eyebrow">Need Guidance?</span>
					<h2>Not sure which course is right for you?</h2>
					<p>Our counsellors will help you pick the right learning path based on your goals, budget, and time.</p>
				</div>
				<div class="rks-courses__cta-actions">
					<a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="rks-courses__cta-btn rks-courses__cta-btn--primary">
						<?php echo $svg_phone; ?> Talk to a Counsellor
					</a>
					<a href="https://wa.me/918979775664" target="_blank" rel="noopener" class="rks-courses__cta-btn rks-courses__cta-btn--wa">
						<?php echo $svg_wa; ?> WhatsApp Us
					</a>
				</div>
			</div>
		</div>
	</section>

</main>

<?php get_footer(); ?>
