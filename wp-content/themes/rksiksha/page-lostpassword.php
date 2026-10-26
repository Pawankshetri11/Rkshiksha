<?php
/**
 * Template Name: Forgot Password Page
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
?>

<section class="rks-auth-page">
	<div class="rks-container rks-auth-page__inner">

		<aside class="rks-auth-page__hero">
			<div>
				<h1 class="rks-auth-page__heading">Reset your password</h1>
				<p class="rks-auth-page__lead">Forgot your password? No problem — we'll email you a link to set a new one.</p>
			</div>
		</aside>

		<div class="rks-auth-page__form">
			<?php echo do_shortcode( '[rks_lostpassword_form]' ); ?>
		</div>

	</div>
</section>

<?php get_footer(); ?>
