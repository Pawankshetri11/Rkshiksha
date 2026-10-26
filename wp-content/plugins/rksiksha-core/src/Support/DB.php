<?php
/**
 * Lightweight DB helper — table name resolution and common utilities.
 *
 * @package RKSiksha\Core
 */

declare( strict_types=1 );

namespace RKSiksha\Core\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DB {

	public static function table( string $name ): string {
		global $wpdb;
		return $wpdb->prefix . 'rks_' . ltrim( $name, '_' );
	}

	/**
	 * Encode an array as JSON for the `meta` column. Empty arrays return null.
	 */
	public static function encode_meta( array $meta ): ?string {
		if ( empty( $meta ) ) {
			return null;
		}
		$json = wp_json_encode( $meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		return false === $json ? null : $json;
	}

	/**
	 * Decode JSON meta. Returns empty array on failure.
	 *
	 * @return array<string, mixed>
	 */
	public static function decode_meta( ?string $json ): array {
		if ( empty( $json ) ) {
			return [];
		}
		$data = json_decode( $json, true );
		return is_array( $data ) ? $data : [];
	}
}
