<?php
/**
 * Plugin Name:       Rishikul Shiksha Exam
 * Plugin URI:        https://rishikulshiksha.com
 * Description:       Separate exam, quiz, attempt review, and exam-certificate automation for Rishikul Shiksha.
 * Version:           0.2.3
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Rishikul Shiksha
 * Author URI:        https://rishikulshiksha.com
 * Text Domain:       rksiksha-exam
 * License:           GPL v2 or later
 *
 * @package RKSiksha\Exam
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'RKS_EXAM_VERSION' ) ) {
	return;
}

define( 'RKS_EXAM_VERSION',         '0.2.3' );
define( 'RKS_EXAM_DB_VERSION',      '2' );
define( 'RKS_EXAM_PLUGIN_FILE',     __FILE__ );
define( 'RKS_EXAM_PLUGIN_DIR',      plugin_dir_path( __FILE__ ) );
define( 'RKS_EXAM_PLUGIN_URL',      plugin_dir_url( __FILE__ ) );
define( 'RKS_EXAM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once RKS_EXAM_PLUGIN_DIR . 'src/Autoloader.php';
\RKSiksha\Exam\Autoloader::register();

register_activation_hook( __FILE__, [ \RKSiksha\Exam\Installer::class, 'install' ] );

add_action( 'plugins_loaded', static function (): void {
	\RKSiksha\Exam\Plugin::instance()->boot();
}, 20 );
