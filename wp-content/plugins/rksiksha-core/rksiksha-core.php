<?php
/**
 * Plugin Name:       Rishikul Shiksha Core
 * Plugin URI:        https://rishikulshiksha.com
 * Description:       Complete LMS + Admission + Franchise + Verification system for Rishikul Shiksha. Branches, payments, certificates, ID cards — all in one core plugin.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Rishikul Shiksha
 * Author URI:        https://rishikulshiksha.com
 * Text Domain:       rksiksha
 * Domain Path:       /languages
 * License:           GPL v2 or later
 *
 * @package RKSiksha\Core
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'RKS_VERSION' ) ) {
	return;
}

define( 'RKS_VERSION',         '0.3.3' );
define( 'RKS_DB_VERSION',      '10' );
define( 'RKS_PLUGIN_FILE',     __FILE__ );
define( 'RKS_PLUGIN_DIR',      plugin_dir_path( __FILE__ ) );
define( 'RKS_PLUGIN_URL',      plugin_dir_url( __FILE__ ) );
define( 'RKS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'RKS_TABLE_PREFIX',    'rks_' );
define( 'RKS_MIN_PHP',         '7.4' );

// Dev mode — enables demo user seeding + login autofill buttons. Set to false in production.
if ( ! defined( 'RKS_DEV_MODE' ) ) {
	define( 'RKS_DEV_MODE', false );
}

require_once RKS_PLUGIN_DIR . 'src/Autoloader.php';
\RKSiksha\Core\Autoloader::register();

register_activation_hook(   __FILE__, [ \RKSiksha\Core\Activator::class,   'activate'   ] );
register_deactivation_hook( __FILE__, [ \RKSiksha\Core\Deactivator::class, 'deactivate' ] );

add_action( 'plugins_loaded', static function (): void {
	\RKSiksha\Core\Plugin::instance()->boot();
}, 5 );
