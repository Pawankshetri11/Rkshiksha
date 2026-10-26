<?php
/**
 * Template Name: Admission Page
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
?>

<section class="rks-adm-page">
	<div class="rks-container">
		<header class="rks-adm-head">
			<nav class="rks-adm-crumb" aria-label="Breadcrumb">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a> <span>/</span> <span>Admission</span>
			</nav>
			<span class="rks-eyebrow">Quick Enrollment</span>
			<h1>Apply for Admission</h1>
			<p>Fill the form below to enroll. Most applications are approved within 24 hours after payment verification.</p>
		</header>

		<div class="rks-adm-wrap">
			<?php echo do_shortcode( '[rks_admission_form]' ); ?>
		</div>
	</div>
</section>

<?php get_footer(); ?>
