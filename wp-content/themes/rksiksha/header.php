<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php
$rks_home_notices_marquee = [];
if ( class_exists( '\RKSiksha\Core\Modules\Notices' ) ) {
	$rks_home_notices_marquee = \RKSiksha\Core\Modules\Notices::get_active( 3 );
}
?>
<?php if ( ! empty( $rks_home_notices_marquee ) && ! ( isset( $_GET['view'] ) && $_GET['view'] === 'quiz-take' ) ) : ?>
<div class="rks-ticker" aria-label="Latest notices">
	<div class="rks-ticker__track">
		<div class="rks-ticker__content">
			<?php foreach ( $rks_home_notices_marquee as $notice ) : ?>
				<span class="rks-ticker__item">
					<strong><?php echo esc_html( get_the_title( $notice->ID ) ); ?></strong>
					<?php if ( get_the_excerpt( $notice->ID ) ) : ?>
						<span class="rks-ticker__sep"> — </span>
						<?php echo esc_html( wp_trim_words( get_the_excerpt( $notice->ID ), 12, '...' ) ); ?>
					<?php endif; ?>
					<?php $link = (string) get_post_meta( $notice->ID, \RKSiksha\Core\Modules\Notices::M_LINK_URL, true ); ?>
					<?php if ( $link ) : ?>
						<a href="<?php echo esc_url( $link ); ?>" class="rks-ticker__link">Read more</a>
					<?php endif; ?>
				</span>
			<?php endforeach; ?>
		</div>
		<div class="rks-ticker__content" aria-hidden="true">
			<?php foreach ( $rks_home_notices_marquee as $notice ) : ?>
				<span class="rks-ticker__item">
					<strong><?php echo esc_html( get_the_title( $notice->ID ) ); ?></strong>
					<?php if ( get_the_excerpt( $notice->ID ) ) : ?>
						<span class="rks-ticker__sep"> — </span>
						<?php echo esc_html( wp_trim_words( get_the_excerpt( $notice->ID ), 12, '...' ) ); ?>
					<?php endif; ?>
					<?php $link = (string) get_post_meta( $notice->ID, \RKSiksha\Core\Modules\Notices::M_LINK_URL, true ); ?>
					<?php if ( $link ) : ?>
						<a href="<?php echo esc_url( $link ); ?>" class="rks-ticker__link">Read more</a>
					<?php endif; ?>
				</span>
			<?php endforeach; ?>
		</div>
	</div>
</div>
<?php endif; ?>

<?php
$rks_hf = class_exists( '\\RKSiksha\\Core\\Admin\\HeaderFooterPage' )
	? \RKSiksha\Core\Admin\HeaderFooterPage::all()
	: [];
if ( ! empty( $rks_hf['topbar_enabled'] ) && ! ( isset( $_GET['view'] ) && $_GET['view'] === 'quiz-take' ) ) : ?>
<div class="rks-topbar">
	<div class="rks-container rks-topbar__inner">
		<div class="rks-topbar__left">
			<?php if ( ! empty( $rks_hf['topbar_phone'] ) ) : ?>
				<span><i class="rks-icon">✆</i> <?php echo esc_html( $rks_hf['topbar_phone'] ); ?></span>
			<?php endif; ?>
			<?php if ( ! empty( $rks_hf['topbar_email'] ) ) : ?>
				<span><i class="rks-icon">✉</i> <?php echo esc_html( $rks_hf['topbar_email'] ); ?></span>
			<?php endif; ?>
		</div>
		<div class="rks-topbar__right">
			<a href="<?php echo esc_url( $rks_hf['topbar_help_url'] ?? '#' ); ?>">Help</a>
			<?php if ( ! empty( $rks_hf['topbar_cta_text'] ) ) : ?>
				<a href="<?php echo esc_url( $rks_hf['topbar_cta_url'] ?? '#' ); ?>"><?php echo esc_html( $rks_hf['topbar_cta_text'] ); ?></a>
			<?php endif; ?>
		</div>
	</div>
</div>
<?php endif; ?>

<header class="rks-header">
	<div class="rks-container rks-header__inner">

		<div class="rks-brand">
			<?php
			$rks_logo_id    = (int) ( $rks_hf['logo_id'] ?? 0 );
			$rks_logo_url   = $rks_logo_id ? wp_get_attachment_image_url( $rks_logo_id, 'full' ) : '';
			$rks_logo_width = (int) ( $rks_hf['logo_width'] ?? 160 );
			if ( $rks_logo_url ) : ?>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="rks-brand__logo" rel="home">
					<img src="<?php echo esc_url( $rks_logo_url ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" style="max-width:<?php echo (int) $rks_logo_width; ?>px;height:auto;">
				</a>
			<?php elseif ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php else : ?>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="rks-brand__text">
					<span class="rks-brand__rk">Rishikul</span><span class="rks-brand__shiksha">Shiksha</span>
				</a>
			<?php endif; ?>
		</div>

		<?php
		// Pre-calculate user authentication details and CTA config so they can be shared between desktop CTA and mobile navigation CTA.
		$rks_show_login   = ! isset( $rks_hf['cta_login_enabled'] )  || (int) $rks_hf['cta_login_enabled']  === 1;
		$rks_show_signup  = ! isset( $rks_hf['cta_signup_enabled'] ) || (int) $rks_hf['cta_signup_enabled'] === 1;
		$rks_login_label  = (string) ( $rks_hf['cta_login_label']  ?? 'Login' );
		$rks_signup_label = (string) ( $rks_hf['cta_signup_label'] ?? 'Sign Up' );
		$rks_login_url    = (string) ( $rks_hf['cta_login_url']    ?? '/login/' );
		$rks_signup_url   = (string) ( $rks_hf['cta_signup_url']   ?? '/register/' );
		$rks_resolve_url  = function( $u ) {
			return ( preg_match( '#^https?://#i', $u ) || strpos( $u, '#' ) === 0 ) ? $u : home_url( $u );
		};
		$rks_dashboard    = home_url( '/' );

		if ( is_user_logged_in() ) {
			$rks_user  = wp_get_current_user();
			$rks_roles = (array) $rks_user->roles;
			if ( in_array( 'rks_student', $rks_roles, true ) ) {
				$rks_dashboard = home_url( '/student-dashboard/' );
			} elseif ( in_array( 'rks_branch_admin', $rks_roles, true ) ) {
				$rks_dashboard = home_url( '/branch-dashboard/' );
			} elseif ( in_array( 'administrator', $rks_roles, true ) || in_array( 'rks_super_admin', $rks_roles, true ) ) {
				$rks_dashboard = admin_url();
			}
		}
		?>

		<nav class="rks-nav" aria-label="Primary">
			<?php
			$rks_header_menu_id = (int) ( $rks_hf['header_menu_id'] ?? 0 );
			$rks_menu_args = [
				'container'  => false,
				'menu_class' => 'rks-nav__list',
				'depth'      => 2,
				'fallback_cb' => false,
			];
			if ( $rks_header_menu_id > 0 ) {
				$rks_menu_args['menu'] = $rks_header_menu_id;
				wp_nav_menu( $rks_menu_args );
			} elseif ( has_nav_menu( 'primary' ) ) {
				$rks_menu_args['theme_location'] = 'primary';
				wp_nav_menu( $rks_menu_args );
			} else { ?>
				<ul class="rks-nav__list">
					<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a></li>
					<li><a href="<?php echo esc_url( home_url( '/courses/' ) ); ?>">Courses</a></li>
					<li><a href="<?php echo esc_url( home_url( '/about/' ) ); ?>">About</a></li>
					<li><a href="<?php echo esc_url( home_url( '/coaching/' ) ); ?>">Franchise</a></li>
					<li><a href="<?php echo esc_url( home_url( '/university-counselling/' ) ); ?>">University <span class="rks-nav__badge">Counselling</span></a></li>
					<li><a href="<?php echo esc_url( home_url( '/admission/' ) ); ?>">Admission</a></li>
					<li><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Contact</a></li>
				</ul>
			<?php } ?>

			<!-- Mobile navigation CTA wrapper -->
			<div class="rks-nav__cta">
				<?php if ( is_user_logged_in() ) : ?>
					<a href="<?php echo esc_url( $rks_dashboard ); ?>" class="rks-btn rks-btn--ghost">Dashboard</a>
					<a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>" class="rks-btn rks-btn--primary">Logout</a>
				<?php else : ?>
					<?php if ( $rks_show_login ) : ?>
						<a href="<?php echo esc_url( $rks_resolve_url( $rks_login_url ) ); ?>" class="rks-btn rks-btn--ghost"><?php echo esc_html( $rks_login_label ); ?></a>
					<?php endif; ?>
					<?php if ( $rks_show_signup ) : ?>
						<a href="<?php echo esc_url( $rks_resolve_url( $rks_signup_url ) ); ?>" class="rks-btn rks-btn--primary"><?php echo esc_html( $rks_signup_label ); ?></a>
					<?php endif; ?>
				<?php endif; ?>
			</div>
		</nav>

		<div class="rks-header__cta">
			<?php if ( is_user_logged_in() ) : ?>
				<a href="<?php echo esc_url( $rks_dashboard ); ?>" class="rks-btn rks-btn--ghost">Dashboard</a>
				<a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>" class="rks-btn rks-btn--primary">Logout</a>
			<?php else : ?>
				<?php if ( $rks_show_login ) : ?>
					<a href="<?php echo esc_url( $rks_resolve_url( $rks_login_url ) ); ?>" class="rks-btn rks-btn--ghost"><?php echo esc_html( $rks_login_label ); ?></a>
				<?php endif; ?>
				<?php if ( $rks_show_signup ) : ?>
					<a href="<?php echo esc_url( $rks_resolve_url( $rks_signup_url ) ); ?>" class="rks-btn rks-btn--primary"><?php echo esc_html( $rks_signup_label ); ?></a>
				<?php endif; ?>
			<?php endif; ?>
		</div>

		<button class="rks-burger" aria-label="Toggle menu" aria-expanded="false">
			<span></span><span></span><span></span>
		</button>

	</div>
</header>

<main class="rks-main">
