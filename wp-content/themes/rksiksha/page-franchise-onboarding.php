<?php
/**
 * Template Name: Franchise Onboarding
 */
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

$svg_chev  = '<svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>';
?>

<main id="primary" class="rks-fr rks-fr-onboard">
	<section class="rks-fr-onboard__section" id="onboarding">
		<div class="rks-container rks-fr-onboard__wrap">
			<header class="rks-fr-onboard__head">
				<nav class="rks-fr__crumb" aria-label="Breadcrumb">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
					<span><?php echo $svg_chev; ?></span>
					<a href="<?php echo esc_url( home_url( '/coaching/' ) ); ?>">Franchise</a>
					<span><?php echo $svg_chev; ?></span>
					<span aria-current="page">Register</span>
				</nav>
				<span class="rks-eyebrow">Franchise Register</span>
				<h1>Franchise Onboarding Form</h1>
				<p>Submit your owner details, Aadhaar document, proposed centre location and agreement confirmation for admin verification.</p>
			</header>

			<div class="rks-fr__form-card rks-fr-onboard__card">
				<?php echo do_shortcode( '[rks_franchise_onboarding_form]' ); ?>
			</div>
		</div>
	</section>
</main>

<?php get_footer(); ?>
