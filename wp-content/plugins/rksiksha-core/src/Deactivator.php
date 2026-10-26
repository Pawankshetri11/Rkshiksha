<?php
/**
 * Plugin deactivation handler.
 * Note: Does NOT drop tables or roles. Use uninstall.php for cleanup.
 *
 * @package RKSiksha\Core
 */

declare( strict_types=1 );

namespace RKSiksha\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Deactivator {

	public static function deactivate(): void {
		flush_rewrite_rules();
		wp_clear_scheduled_hook( 'rks_daily_cron' );
	}
}
