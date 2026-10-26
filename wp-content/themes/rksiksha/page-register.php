<?php
/**
 * Template Name: Register Page
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
?>

<section class="rks-auth-page">
	<div class="rks-container rks-auth-page__inner">

		<aside class="rks-auth-page__hero">
			<div>
				<h1 class="rks-auth-page__heading">Join <span>Rishikul Shiksha</span></h1>
				<p class="rks-auth-page__lead">Create your free account in less than a minute and start learning today.</p>
				<ul class="rks-auth-page__perks">
					<li>✓ Free intro courses available</li>
					<li>✓ Save progress across devices</li>
					<li>✓ Personalized learning dashboard</li>
					<li>✓ Connect with instructors</li>
				</ul>
			</div>
		</aside>

		<div class="rks-auth-page__form">
			<?php echo do_shortcode( '[rks_register_form]' ); ?>
		</div>

	</div>
</section>

<?php get_footer(); ?>
