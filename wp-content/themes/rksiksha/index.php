<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$rks_request_path = isset( $_SERVER['REQUEST_URI'] ) ? trim( (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ), '/' ) : '';
$rks_request_slug = $rks_request_path !== '' ? basename( $rks_request_path ) : '';
if ( 'franchise-register' === $rks_request_slug ) {
	require get_template_directory() . '/page-franchise-onboarding.php';
	return;
}

get_header();
?>

<section class="rks-placeholder rks-container">
	<h1>Welcome to Rishikul Shiksha</h1>
	<p>This is a placeholder fallback template. The custom landing page (front-page.php) and other templates will be built next.</p>

	<?php if ( have_posts() ) : ?>
		<div class="rks-posts">
			<?php while ( have_posts() ) : the_post(); ?>
				<article class="rks-post">
					<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
					<div><?php the_excerpt(); ?></div>
				</article>
			<?php endwhile; ?>
		</div>
	<?php endif; ?>
</section>

<?php get_footer(); ?>
