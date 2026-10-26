<?php
/**
 * ID Card module — issues ID cards for students (on admission approval) and branch admins.
 *
 * @package RKSiksha\Core
 */

declare( strict_types=1 );

namespace RKSiksha\Core\Modules;

use RKSiksha\Core\Roles;
use RKSiksha\Core\Support\DB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class IdCard {

	public function register(): void {
		add_action( 'rks_admission_approved', [ $this, 'on_admission_approved' ], 10, 3 );
	}

	public function on_admission_approved( int $admission_id, int $user_id, int $branch_id ): void {
		// Avoid duplicates
		global $wpdb;
		$existing = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM " . DB::table( 'idcards' ) . " WHERE user_id = %d AND status = 'active' LIMIT 1",
			$user_id
		) );
		if ( $existing > 0 ) return;

		$user = get_userdata( $user_id );
		if ( ! $user ) return;

		$this->generate( $user_id, 'student', $user->display_name ?: $user->user_login, 'Student', $branch_id );
	}

	public function generate( int $user_id, string $holder_type, string $holder_name, string $holder_role, int $branch_id, ?string $expiry_date = null ): int {
		global $wpdb;
		$settings = (array) get_option( 'rks_settings', [] );
		$prefix   = (string) ( $settings['idcard_prefix'] ?? 'RKS-ID' );

		$type_code = match ( $holder_type ) {
			'student'         => 'STU',
			'branch_admin'    => 'BRN',
			'franchise_admin' => 'FRN',
			default           => 'GEN',
		};

		$unique_id = $this->build_unique_id( $prefix . '-' . $type_code );

		$wpdb->insert(
			DB::table( 'idcards' ),
			[
				'unique_id'     => $unique_id,
				'user_id'       => $user_id,
				'branch_id'     => $branch_id,
				'holder_type'   => $holder_type,
				'holder_name'   => $holder_name,
				'holder_role'   => $holder_role,
				'issue_date'    => current_time( 'Y-m-d' ),
				'expiry_date'   => $expiry_date,
				'status'        => 'active',
			],
			[ '%s','%d','%d','%s','%s','%s','%s','%s','%s' ]
		);

		$id = (int) $wpdb->insert_id;

		if ( $id > 0 ) {
			$this->notify_issued( $user_id, $unique_id, $holder_role );
		}

		return $id;
	}

	private function build_unique_id( string $prefix ): string {
		global $wpdb;
		do {
			$num = str_pad( (string) wp_rand( 1, 999999 ), 6, '0', STR_PAD_LEFT );
			$id  = $prefix . '-' . $num;
			$exists = (int) $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM " . DB::table( 'idcards' ) . " WHERE unique_id = %s",
				$id
			) );
		} while ( $exists > 0 );
		return $id;
	}

	public function get_by_unique_id( string $unique_id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare(
			'SELECT * FROM ' . DB::table( 'idcards' ) . ' WHERE unique_id = %s LIMIT 1',
			$unique_id
		), ARRAY_A );
		return $row ?: null;
	}

	private function notify_issued( int $user_id, string $unique_id, string $holder_role ): void {
		$user = get_userdata( $user_id );
		if ( ! $user ) return;

		$mailer = new Emailer();
		$mailer->send_template(
			$user->user_email,
			'Your Rishikul Shiksha ID Card has been issued',
			'Hi ' . $user->display_name . ', here\'s your ID card',
			'<p>An official Rishikul Shiksha ID card has been issued to you. You can view it from your dashboard or use the link below.</p>',
			[
				'ID Number' => $unique_id,
				'Role'      => $holder_role,
				'Issued'    => current_time( 'd M Y' ),
			],
			[ 'label' => 'View ID Card', 'url' => add_query_arg( 'id', $unique_id, home_url( '/idcard/' ) ) ]
		);
	}
}
