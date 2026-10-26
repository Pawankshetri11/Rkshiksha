<?php
/**
 * Template Name: Login Page
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
?>

<section class="rks-auth-page">
	<div class="rks-container rks-auth-page__inner">

		<aside class="rks-auth-page__hero">
			<div>
				<h1 class="rks-auth-page__heading">Welcome to <span>Rishikul Shiksha</span></h1>
				<p class="rks-auth-page__lead">Continue your learning journey — courses, certificates, dashboards, all in one place.</p>
				<ul class="rks-auth-page__perks">
					<li>✓ 200+ expert-led courses</li>
					<li>✓ Earn industry certificates</li>
					<li>✓ Lifetime access to enrolled content</li>
					<li>✓ Mobile-friendly LMS</li>
				</ul>
			</div>
		</aside>

		<div class="rks-auth-page__form">
			<?php echo do_shortcode( '[rks_login_form]' ); ?>

			<?php if ( defined( 'RKS_DEV_MODE' ) && RKS_DEV_MODE && ! is_user_logged_in() ) : ?>
			<div class="rks-dev-quick">
				<div class="rks-dev-quick__head">
					<strong>[Dev] Quick Login</strong>
					<small>Click to autofill — disable by setting RKS_DEV_MODE=false</small>
				</div>
				<div class="rks-dev-quick__buttons">
					<button type="button" class="rks-dev-quick__btn" data-user="super.demo" data-pass="Demo@1234" data-color="red">
						<span>Super Admin</span><code>super.demo</code>
					</button>
					<button type="button" class="rks-dev-quick__btn" data-user="mainadmin" data-pass="Demo@1234" data-color="teal">
						<span>Main Branch Admin</span><code>mainadmin</code>
					</button>
					<button type="button" class="rks-dev-quick__btn" data-user="franchise.demo" data-pass="Demo@1234" data-color="mint">
						<span>Franchise Admin</span><code>franchise.demo</code>
					</button>
					<button type="button" class="rks-dev-quick__btn" data-user="student.demo" data-pass="Demo@1234" data-color="amber">
						<span>Student</span><code>student.demo</code>
					</button>
				</div>
				<small class="rks-dev-quick__hint">Password for all: <code>Demo@1234</code></small>
			</div>
			<script>
			(function () {
				var form = document.querySelector('.rks-auth-form');
				if (!form) return;
				document.querySelectorAll('.rks-dev-quick__btn').forEach(function (btn) {
					btn.addEventListener('click', function () {
						var u = btn.dataset.user, p = btn.dataset.pass;
						var ui = form.querySelector('input[name="user_login"]');
						var pi = form.querySelector('input[name="user_password"]');
						if (ui) ui.value = u;
						if (pi) pi.value = p;
						form.querySelectorAll('.rks-dev-quick__btn').forEach(function (b) { b.classList.remove('is-active'); });
						btn.classList.add('is-active');
						if (ui) ui.focus();
					});
				});
			})();
			</script>
			<?php endif; ?>
		</div>

	</div>
</section>

<?php get_footer(); ?>
