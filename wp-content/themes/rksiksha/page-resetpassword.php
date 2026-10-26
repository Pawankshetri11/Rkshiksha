<?php
/**
 * Template Name: Reset Password Page
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
?>

<section class="rks-auth-page">
	<div class="rks-container rks-auth-page__inner">

		<aside class="rks-auth-page__hero">
			<div>
				<h1 class="rks-auth-page__heading">Set a new password</h1>
				<p class="rks-auth-page__lead">Choose a strong new password for your Rishikul Shiksha account.</p>
			</div>
		</aside>

		<div class="rks-auth-page__form">
			<?php echo do_shortcode( '[rks_resetpassword_form]' ); ?>
		</div>

	</div>
</section>

<?php get_footer(); ?>
