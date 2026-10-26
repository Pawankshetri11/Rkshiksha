<?php
/**
 * Autoloader for the standalone RK Shiksha Exam plugin.
 *
 * It also provides the legacy Core quiz classes from this plugin so existing
 * theme templates and course-builder links keep working after the split.
 *
 * @package RKSiksha\Exam
 */

declare( strict_types=1 );

namespace RKSiksha\Exam;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Autoloader {

	private const EXAM_PREFIX = 'RKSiksha\\Exam\\';
	private const CORE_PREFIX = 'RKSiksha\\Core\\';

	public static function register(): void {
		spl_autoload_register( [ self::class, 'load' ] );
	}

	public static function load( string $class ): void {
		$path = '';

		if ( strpos( $class, self::EXAM_PREFIX ) === 0 ) {
			$relative = substr( $class, strlen( self::EXAM_PREFIX ) );
			$path     = RKS_EXAM_PLUGIN_DIR . 'src/' . str_replace( '\\', DIRECTORY_SEPARATOR, $relative ) . '.php';
		} elseif ( strpos( $class, self::CORE_PREFIX ) === 0 ) {
			$relative = substr( $class, strlen( self::CORE_PREFIX ) );
			$path     = RKS_EXAM_PLUGIN_DIR . 'src/Core/' . str_replace( '\\', DIRECTORY_SEPARATOR, $relative ) . '.php';
		}

		if ( $path && is_readable( $path ) ) {
			require_once $path;
		}
	}
}
