<?php
/**
 * Branches module — CRUD for Main + Franchise branches.
 *
 * @package RKSiksha\Core
 */

declare( strict_types=1 );

namespace RKSiksha\Core\Modules;

use RKSiksha\Core\Support\DB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Branches {

	public const TYPE_MAIN      = 'main';
	public const TYPE_FRANCHISE = 'franchise';

	public function register(): void {
		// Reserved for future hooks (e.g. clearing object cache on branch save).
	}

	/**
	 * @param array<string, mixed> $data
	 */
	public function create( array $data ): int {
		global $wpdb;
		$table = DB::table( 'branches' );

		$row = [
			'type'          => $data['type']          ?? self::TYPE_FRANCHISE,
			'name'          => (string) ( $data['name'] ?? '' ),
			'address'       => $data['address']       ?? null,
			'city'          => $data['city']          ?? null,
			'state'         => $data['state']         ?? null,
			'pincode'       => $data['pincode']       ?? null,
			'phone'         => $data['phone']         ?? null,
			'email'         => $data['email']         ?? null,
			'owner_user_id' => (int) ( $data['owner_user_id'] ?? 0 ),
			'franchise_id'  => isset( $data['franchise_id'] ) ? (int) $data['franchise_id'] : null,
			'status'        => $data['status']        ?? 'active',
			'meta'          => DB::encode_meta( (array) ( $data['meta'] ?? [] ) ),
		];

		$ok = $wpdb->insert(
			$table,
			$row,
			[ '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s' ]
		);

		return $ok ? (int) $wpdb->insert_id : 0;
	}

	/**
	 * @param array<string, mixed> $data
	 */
	public function update( int $id, array $data ): bool {
		global $wpdb;
		if ( $id <= 0 ) {
			return false;
		}

		$allowed = [ 'type', 'name', 'address', 'city', 'state', 'pincode', 'phone', 'email', 'owner_user_id', 'franchise_id', 'status' ];
		$update  = [];
		$format  = [];

		foreach ( $allowed as $field ) {
			if ( ! array_key_exists( $field, $data ) ) {
				continue;
			}
			$update[ $field ] = $data[ $field ];
			$format[]         = in_array( $field, [ 'owner_user_id', 'franchise_id' ], true ) ? '%d' : '%s';
		}

		if ( isset( $data['meta'] ) ) {
			$update['meta'] = DB::encode_meta( (array) $data['meta'] );
			$format[]       = '%s';
		}

		if ( empty( $update ) ) {
			return false;
		}

		return false !== $wpdb->update( DB::table( 'branches' ), $update, [ 'id' => $id ], $format, [ '%d' ] );
	}

	public function get( int $id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . DB::table( 'branches' ) . ' WHERE id = %d LIMIT 1', $id ),
			ARRAY_A
		);
		return $row ?: null;
	}

	public function delete( int $id ): bool {
		if ( $id <= 0 ) {
			return false;
		}

		$row = $this->get( $id );
		if ( ! $row || (string) ( $row['type'] ?? '' ) === self::TYPE_MAIN ) {
			return false;
		}

		global $wpdb;
		return false !== $wpdb->delete( DB::table( 'branches' ), [ 'id' => $id ], [ '%d' ] );
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function list( array $args = [] ): array {
		global $wpdb;
		$where  = [ '1=1' ];
		$params = [];

		if ( ! empty( $args['type'] ) ) {
			$where[] = 'type = %s';
			$params[] = (string) $args['type'];
		}
		if ( ! empty( $args['status'] ) ) {
			$where[] = 'status = %s';
			$params[] = (string) $args['status'];
		}
		if ( ! empty( $args['owner_user_id'] ) ) {
			$where[] = 'owner_user_id = %d';
			$params[] = (int) $args['owner_user_id'];
		}

		$limit  = max( 1, min( 200, (int) ( $args['limit']  ?? 50 ) ) );
		$offset = max( 0,           (int) ( $args['offset'] ?? 0  ) );

		$sql = 'SELECT * FROM ' . DB::table( 'branches' ) . ' WHERE ' . implode( ' AND ', $where ) . ' ORDER BY id DESC LIMIT %d OFFSET %d';
		$params[] = $limit;
		$params[] = $offset;

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );
		return $rows ?: [];
	}

	public function get_main_branch_id(): int {
		$id = (int) get_option( 'rks_main_branch_id', 0 );
		if ( $id > 0 ) {
			return $id;
		}
		global $wpdb;
		$found = (int) $wpdb->get_var(
			$wpdb->prepare( 'SELECT id FROM ' . DB::table( 'branches' ) . ' WHERE type = %s ORDER BY id ASC LIMIT 1', self::TYPE_MAIN )
		);
		if ( $found > 0 ) {
			update_option( 'rks_main_branch_id', $found, true );
		}
		return $found;
	}

	public function count( array $args = [] ): int {
		global $wpdb;
		$where  = [ '1=1' ];
		$params = [];

		if ( ! empty( $args['type'] ) ) {
			$where[]  = 'type = %s';
			$params[] = (string) $args['type'];
		}
		if ( ! empty( $args['status'] ) ) {
			$where[]  = 'status = %s';
			$params[] = (string) $args['status'];
		}

		$sql = 'SELECT COUNT(*) FROM ' . DB::table( 'branches' ) . ' WHERE ' . implode( ' AND ', $where );
		return (int) ( empty( $params ) ? $wpdb->get_var( $sql ) : $wpdb->get_var( $wpdb->prepare( $sql, $params ) ) );
	}
}
