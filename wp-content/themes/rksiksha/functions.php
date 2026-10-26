<?php
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'RKS_THEME_VERSION', '0.3.25' );

function rks_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', [ 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ] );
	add_theme_support( 'custom-logo', [
		'height'      => 60,
		'width'       => 200,
		'flex-height' => true,
		'flex-width'  => true,
	] );

	register_nav_menus( [
		'primary' => __( 'Primary Menu', 'rksiksha' ),
		'footer'  => __( 'Footer Menu', 'rksiksha' ),
	] );
}
add_action( 'after_setup_theme', 'rks_setup' );

function rks_normalize_menu_link_urls( $atts ) {
	if ( is_admin() || empty( $atts['href'] ) || ! is_string( $atts['href'] ) ) {
		return $atts;
	}

	$href = trim( $atts['href'] );

	if ( '' === $href || '/' !== $href[0] || ( isset( $href[1] ) && '/' === $href[1] ) ) {
		return $atts;
	}

	foreach ( [ '/wp-admin', '/wp-content', '/wp-includes' ] as $reserved_path ) {
		if ( 0 === stripos( $href, $reserved_path ) ) {
			return $atts;
		}
	}

	$atts['href'] = home_url( $href );
	return $atts;
}
add_filter( 'nav_menu_link_attributes', 'rks_normalize_menu_link_urls' );

function rks_current_request_slug() {
	$path = isset( $_SERVER['REQUEST_URI'] ) ? trim( (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ), '/' ) : '';
	return '' !== $path ? basename( $path ) : '';
}

function rks_is_franchise_register_request() {
	return 'franchise-register' === rks_current_request_slug();
}

function rks_assets() {
	wp_enqueue_style(
		'rks-main',
		get_template_directory_uri() . '/assets/css/main.css',
		[],
		RKS_THEME_VERSION
	);

	wp_enqueue_style(
		'rks-fonts',
		'https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Lora:wght@500;600&display=swap',
		[],
		null
	);

	wp_enqueue_script(
		'rks-main',
		get_template_directory_uri() . '/assets/js/main.js',
		[],
		RKS_THEME_VERSION,
		true
	);

	// Per-template page styles
	$theme_uri = get_template_directory_uri();
	$request_slug = rks_current_request_slug();
	$page_styles = [
		'page-about.php'         => [ 'rks-page-about',         '/assets/css/page-about.css' ],
		'page-contact.php'       => [ 'rks-page-contact',       '/assets/css/page-contact.css' ],
		'page-gallery.php'       => [ 'rks-page-gallery',       '/assets/css/page-gallery.css' ],
		'page-coaching.php'      => [ 'rks-page-coaching',      '/assets/css/page-coaching.css' ],
		'page-franchise-register.php' => [ 'rks-page-coaching', '/assets/css/page-coaching.css' ],
		'page-franchise-onboarding.php' => [ 'rks-page-coaching', '/assets/css/page-coaching.css' ],
		'page-university.php'    => [ 'rks-page-university',    '/assets/css/page-university.css' ],
		'page-login.php'         => [ 'rks-page-auth',          '/assets/css/page-auth.css' ],
		'page-register.php'      => [ 'rks-page-auth',          '/assets/css/page-auth.css' ],
		'page-lostpassword.php'  => [ 'rks-page-auth',          '/assets/css/page-auth.css' ],
		'page-resetpassword.php' => [ 'rks-page-auth',          '/assets/css/page-auth.css' ],
		'page-admission.php'         => [ 'rks-page-admission',     '/assets/css/page-admission.css' ],
		'page-branch-dashboard.php'  => [ 'rks-page-dashboard',     '/assets/css/page-dashboard.css' ],
		'page-student-dashboard.php' => [ 'rks-page-dashboard',     '/assets/css/page-dashboard.css' ],
		'page-certificate-view.php'  => [ 'rks-page-certificate',   '/assets/css/page-certificate.css' ],
		'page-idcard-view.php'       => [ 'rks-page-certificate',   '/assets/css/page-certificate.css' ],
		'page-verify-certificate.php' => [ 'rks-page-verify',       '/assets/css/page-verify.css' ],
		'page-verify-idcard.php'      => [ 'rks-page-verify',       '/assets/css/page-verify.css' ],
		'page-privacy-policy.php'     => [ 'rks-page-policy',       '/assets/css/page-policy.css' ],
		'page-terms.php'              => [ 'rks-page-policy',       '/assets/css/page-policy.css' ],
		'page-refund-policy.php'      => [ 'rks-page-policy',       '/assets/css/page-policy.css' ],
		'page-cookie-policy.php'      => [ 'rks-page-policy',       '/assets/css/page-policy.css' ],
		'page-legal.php'              => [ 'rks-page-legal',        '/assets/css/page-legal.css' ],
	];

	if ( rks_is_franchise_register_request() ) {
		wp_enqueue_style( 'rks-page-coaching', $theme_uri . '/assets/css/page-coaching.css', [ 'rks-main' ], RKS_THEME_VERSION );
	}

	// Student dashboard also loads extra LMS player styles
	if ( is_page_template( 'page-student-dashboard.php' ) ) {
		wp_enqueue_style( 'rks-page-dashboard-extras', $theme_uri . '/assets/css/page-dashboard-extras.css', [ 'rks-main', 'rks-page-dashboard' ], RKS_THEME_VERSION );
	}

	// Admission shares some auth form classes — also load page-auth.css
	if ( is_page_template( 'page-admission.php' ) ) {
		wp_enqueue_style( 'rks-page-auth-shared', $theme_uri . '/assets/css/page-auth.css', [ 'rks-main' ], RKS_THEME_VERSION );
	}
	foreach ( $page_styles as $template => $info ) {
		// Match by template OR by slug-fallback (WP auto-loads page-{slug}.php when a Page has that slug).
		$slug = preg_replace( '/^page-(.+)\.php$/', '$1', $template );
		if ( is_page_template( $template ) || ( $slug !== $template && is_page( $slug ) ) ) {
			wp_enqueue_style( $info[0], $theme_uri . $info[1], [ 'rks-main' ], RKS_THEME_VERSION );
		}
	}

	if ( is_page_template( 'page-legal.php' ) || is_page( 'legal' ) ) {
		wp_enqueue_script( 'rks-pdfjs', $theme_uri . '/assets/js/vendor/pdfjs/pdf.min.js', [], '3.11.174', false );
	}

	if ( is_front_page() ) {
		wp_enqueue_style( 'rks-page-home', $theme_uri . '/assets/css/page-home.css', [ 'rks-main' ], RKS_THEME_VERSION );
	}

	if ( is_home() || is_search() || ( is_archive() && ! is_post_type_archive( 'rks_course' ) && ! is_tax( 'rks_course_cat' ) ) || ( is_single() && get_post_type() !== 'rks_course' ) ) {
		wp_enqueue_style( 'rks-page-blog', $theme_uri . '/assets/css/page-blog.css', [ 'rks-main' ], RKS_THEME_VERSION );
	}

	if ( is_post_type_archive( 'rks_course' ) || is_tax( 'rks_course_cat' ) || is_singular( 'rks_course' ) ) {
		wp_enqueue_style( 'rks-page-courses', $theme_uri . '/assets/css/page-courses.css', [ 'rks-main' ], RKS_THEME_VERSION );
	}
}
add_action( 'wp_enqueue_scripts', 'rks_assets' );

function rks_force_franchise_register_template( $template ) {
	if ( rks_is_franchise_register_request() ) {
		$forced = get_template_directory() . '/page-franchise-onboarding.php';
		if ( file_exists( $forced ) ) {
			return $forced;
		}
	}

	return $template;
}
add_filter( 'template_include', 'rks_force_franchise_register_template', 99 );

function rks_franchise_register_normalize_query_state( $query = null ) {
	if ( ! rks_is_franchise_register_request() ) {
		return;
	}

	if ( ! $query instanceof WP_Query ) {
		global $wp_query;
		$query = $wp_query;
	}

	if ( ! $query instanceof WP_Query ) {
		return;
	}

	$query->is_404      = false;
	$query->is_page     = false;
	$query->is_single   = false;
	$query->is_singular = false;

	$query->queried_object    = null;
	$query->queried_object_id = 0;
}

function rks_franchise_register_parse_query( $query ) {
	if ( $query instanceof WP_Query && $query->is_main_query() ) {
		rks_franchise_register_normalize_query_state( $query );
	}
}
add_action( 'parse_query', 'rks_franchise_register_parse_query', 999 );

function rks_franchise_register_prevent_404( $preempt, $wp_query ) {
	if ( ! rks_is_franchise_register_request() ) {
		return $preempt;
	}

	rks_franchise_register_normalize_query_state( $wp_query );

	return true;
}
add_filter( 'pre_handle_404', 'rks_franchise_register_prevent_404', 10, 2 );

function rks_franchise_register_status_header() {
	if ( ! rks_is_franchise_register_request() ) {
		return;
	}

	rks_franchise_register_normalize_query_state();

	status_header( 200 );
}
add_action( 'template_redirect', 'rks_franchise_register_status_header', 0 );

function rks_franchise_register_document_title( $title ) {
	if ( rks_is_franchise_register_request() ) {
		return 'Franchise Register - ' . get_bloginfo( 'name' );
	}

	return $title;
}
add_filter( 'pre_get_document_title', 'rks_franchise_register_document_title', 20 );

function rks_franchise_register_body_classes( $classes ) {
	if ( ! rks_is_franchise_register_request() ) {
		return $classes;
	}

	$classes = array_diff( $classes, [ 'error404' ] );
	$classes[] = 'page';
	$classes[] = 'page-franchise-register';

	return array_values( array_unique( $classes ) );
}
add_filter( 'body_class', 'rks_franchise_register_body_classes', 20 );

function rks_filter_menu_labels( $items ) {
	if ( is_admin() ) {
		return $items;
	}
	foreach ( $items as $item ) {
		if ( 'Counselling' === $item->title || ( isset( $item->url ) && str_contains( (string) $item->url, '/university-counselling/' ) ) ) {
			$item->title = 'University <span class="rks-nav__badge">Counselling</span>';
		}
	}
	return $items;
}
add_filter( 'wp_nav_menu_objects', 'rks_filter_menu_labels' );

