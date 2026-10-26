<?php
/**
 * PSR-4 autoloader for RKSiksha\Core namespace.
 *
 * @package RKSiksha\Core
 */

declare( strict_types=1 );

namespace RKSiksha\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Autoloader {

	private const NAMESPACE_PREFIX = 'RKSiksha\\Core\\';

	public static function register(): void {
		spl_autoload_register( [ self::class, 'load' ] );
	}

	public static function load( string $class ): void {
		if ( strpos( $class, self::NAMESPACE_PREFIX ) !== 0 ) {
			return;
		}

		$relative = substr( $class, strlen( self::NAMESPACE_PREFIX ) );
		$path     = RKS_PLUGIN_DIR . 'src/' . str_replace( '\\', DIRECTORY_SEPARATOR, $relative ) . '.php';

		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}
}
