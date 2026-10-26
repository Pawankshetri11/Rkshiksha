<?php
/**
 * Plugin Name: RKSiksha Gallery
 * Description: Photo gallery manager for Rishikul Shiksha — admin can upload, delete & reorder photos. Exposes rks_gallery_get_photos() helper for the theme.
 * Version:     0.1.0
 * Author:      RKSiksha
 * License:     GPL-2.0+
 * Text Domain: rksiksha-gallery
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'RKSG_VERSION',  '0.1.0' );
define( 'RKSG_FILE',     __FILE__ );
define( 'RKSG_DIR',      plugin_dir_path( __FILE__ ) );
define( 'RKSG_URL',      plugin_dir_url( __FILE__ ) );
define( 'RKSG_OPTION',   'rks_gallery_photos' );

require_once RKSG_DIR . 'src/Plugin.php';
require_once RKSG_DIR . 'src/AdminPage.php';
require_once RKSG_DIR . 'src/Helpers.php';

add_action( 'plugins_loaded', static function () {
	\RKSiksha\Gallery\Plugin::instance()->boot();
} );
