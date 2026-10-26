<?php
/**
 * Payments module — UPI screenshot + UTR storage, verify/reject workflow.
 *
 * Screenshots stored in /wp-content/uploads/rksiksha-private/payments/
 * (private dir created by Activator with .htaccess deny rules).
 *
 * @package RKSiksha\Core
 */

declare( strict_types=1 );

namespace RKSiksha\Core\Modules;

use RKSiksha\Core\Support\DB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Payments {

	public const STATUS_PENDING  = 'pending';
	public const STATUS_APPROVED = 'approved';
	public const STATUS_REJECTED = 'rejected';
	public const STATUS_REFUNDED = 'refunded';

	private const ALLOWED_MIME = [ 'image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'application/pdf' ];
	private const MAX_BYTES    = 5242880; // 5MB

	public function register(): void {
		add_action( 'admin_post_rks_payment_screenshot', [ $this, 'handle_admin_view_screenshot' ] );
		add_action( 'admin_post_nopriv_rks_payment_screenshot', [ $this, 'handle_admin_view_screenshot' ] );
	}

	/**
	 * @param array<string, mixed> $data
	 */
	public function create( array $data ): int {
		global $wpdb;
		$row = [
			'branch_id'    => (int) ( $data['branch_id']    ?? 0 ),
			'amount'       => (float) ( $data['amount'] ?? 0 ),
			'method'       => (string) ( $data['method'] ?? 'upi' ),
			'status'       => (string) ( $data['status'] ?? self::STATUS_PENDING ),
			'meta'         => DB::encode_meta( (array) ( $data['meta'] ?? [] ) ),
		];
		$formats = [ '%d', '%f', '%s', '%s', '%s' ];

		if ( isset( $data['user_id'] ) ) { $row['user_id'] = (int) $data['user_id']; $formats[] = '%d'; }
		if ( isset( $data['admission_id'] ) ) { $row['admission_id'] = (int) $data['admission_id']; $formats[] = '%d'; }
		if ( isset( $data['utr'] ) ) { $row['utr'] = sanitize_text_field( (string) $data['utr'] ); $formats[] = '%s'; }
		if ( isset( $data['screenshot_path'] ) ) { $row['screenshot_path'] = (string) $data['screenshot_path']; $formats[] = '%s'; }
		if ( isset( $data['created_at'] ) ) { $row['created_at'] = (string) $data['created_at']; $formats[] = '%s'; }
		if ( isset( $data['verified_at'] ) ) { $row['verified_at'] = (string) $data['verified_at']; $formats[] = '%s'; }
		if ( isset( $data['verifier_user_id'] ) ) { $row['verifier_user_id'] = (int) $data['verifier_user_id']; $formats[] = '%d'; }

		$ok = $wpdb->insert(
			DB::table( 'payments' ),
			$row,
			$formats
		);
		return $ok ? (int) $wpdb->insert_id : 0;
	}

	public function get( int $id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . DB::table( 'payments' ) . ' WHERE id = %d LIMIT 1', $id ),
			ARRAY_A
		);
		return $row ?: null;
	}

	/** @return array<int, array<string, mixed>> */
	public function list( array $args = [] ): array {
		global $wpdb;
		$where  = [ '1=1' ];
		$params = [];

		if ( ! empty( $args['branch_id'] ) ) {
			$where[]  = 'branch_id = %d';
			$params[] = (int) $args['branch_id'];
		}
		if ( ! empty( $args['status'] ) ) {
			$where[]  = 'status = %s';
			$params[] = (string) $args['status'];
		}

		$limit  = max( 1, min( 200, (int) ( $args['limit']  ?? 50 ) ) );
		$offset = max( 0,           (int) ( $args['offset'] ?? 0  ) );
		$sql    = 'SELECT * FROM ' . DB::table( 'payments' ) . ' WHERE ' . implode( ' AND ', $where ) . ' ORDER BY id DESC LIMIT %d OFFSET %d';
		$params[] = $limit;
		$params[] = $offset;

		return $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A ) ?: [];
	}

	public function approve( int $id, int $verifier_user_id ): bool {
		global $wpdb;
		$ok = false !== $wpdb->update(
			DB::table( 'payments' ),
			[
				'status'           => self::STATUS_APPROVED,
				'verifier_user_id' => $verifier_user_id,
				'verified_at'      => current_time( 'mysql' ),
			],
			[ 'id' => $id ],
			[ '%s', '%d', '%s' ],
			[ '%d' ]
		);
		if ( $ok ) {
			do_action( 'rks_payment_approved', $id, $verifier_user_id );
		}
		return $ok;
	}

	public function refund( int $id, int $verifier_user_id, string $note = '' ): bool {
		global $wpdb;
		return false !== $wpdb->update(
			DB::table( 'payments' ),
			[
				'status'           => self::STATUS_REFUNDED,
				'verifier_user_id' => $verifier_user_id,
				'verified_at'      => current_time( 'mysql' ),
				'rejection_note'   => $note,
			],
			[ 'id' => $id ],
			[ '%s', '%d', '%s', '%s' ],
			[ '%d' ]
		);
	}

	public function reject( int $id, int $verifier_user_id, string $note = '' ): bool {
		global $wpdb;
		return false !== $wpdb->update(
			DB::table( 'payments' ),
			[
				'status'           => self::STATUS_REJECTED,
				'verifier_user_id' => $verifier_user_id,
				'verified_at'      => current_time( 'mysql' ),
				'rejection_note'   => $note,
			],
			[ 'id' => $id ],
			[ '%s', '%d', '%s', '%s' ],
			[ '%d' ]
		);
	}

	/**
	 * Save uploaded payment screenshot to the private dir.
	 *
	 * @param array<string, mixed> $file  $_FILES entry
	 * @return array{ok:bool, path?:string, error?:string}
	 */
	public function store_screenshot( array $file ): array {
		if ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
			return [ 'ok' => false, 'error' => 'No file uploaded.' ];
		}
		if ( ! empty( $file['error'] ) && UPLOAD_ERR_OK !== $file['error'] ) {
			return [ 'ok' => false, 'error' => 'Upload error code ' . (int) $file['error'] ];
		}
		if ( (int) ( $file['size'] ?? 0 ) > self::MAX_BYTES ) {
			return [ 'ok' => false, 'error' => 'File too large (max 5MB).' ];
		}

		$mime = function_exists( 'mime_content_type' ) ? (string) mime_content_type( $file['tmp_name'] ) : (string) ( $file['type'] ?? '' );
		if ( ! in_array( $mime, self::ALLOWED_MIME, true ) ) {
			return [ 'ok' => false, 'error' => 'Unsupported file type. Use JPG / PNG / WEBP / PDF.' ];
		}

		$ext = match ( $mime ) {
			'image/jpeg', 'image/jpg' => 'jpg',
			'image/png'               => 'png',
			'image/webp'              => 'webp',
			'application/pdf'         => 'pdf',
			default                   => 'bin',
		};

		$uploads = wp_upload_dir();
		$dir     = trailingslashit( $uploads['basedir'] ) . 'rksiksha-private/payments/' . date( 'Y/m' );
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		$basename = wp_generate_uuid4() . '.' . $ext;
		$dest     = $dir . '/' . $basename;

		if ( ! move_uploaded_file( $file['tmp_name'], $dest ) ) {
			return [ 'ok' => false, 'error' => 'Could not save file.' ];
		}

		// Store relative path (relative to uploads basedir)
		$rel = ltrim( str_replace( $uploads['basedir'], '', $dest ), DIRECTORY_SEPARATOR . '/' );

		return [ 'ok' => true, 'path' => $rel ];
	}

	/**
	 * Authenticated proxy to view a private payment screenshot.
	 * Only branch admins (matching branch) and super admins can view.
	 */
	public function handle_admin_view_screenshot(): void {
		if ( ! is_user_logged_in() ) wp_die( 'Forbidden', 'Forbidden', [ 'response' => 403 ] );

		$payment_id = isset( $_GET['pid'] ) ? (int) $_GET['pid'] : 0;
		$nonce      = isset( $_GET['_n'] )  ? (string) $_GET['_n'] : '';
		if ( ! wp_verify_nonce( $nonce, 'rks_view_payment_' . $payment_id ) ) {
			wp_die( 'Invalid token', 'Forbidden', [ 'response' => 403 ] );
		}

		$pay = $this->get( $payment_id );
		if ( ! $pay || empty( $pay['screenshot_path'] ) ) {
			wp_die( 'Not found', 'Not found', [ 'response' => 404 ] );
		}

		// Permission: super admin or owning-branch admin
		$user = wp_get_current_user();
		$is_super  = current_user_can( 'rks_view_all_payments' );
		$is_branch = current_user_can( 'rks_verify_payment' ) && (int) get_user_meta( $user->ID, 'rks_branch_id', true ) === (int) $pay['branch_id'];
		if ( ! $is_super && ! $is_branch ) {
			wp_die( 'Forbidden', 'Forbidden', [ 'response' => 403 ] );
		}

		$uploads = wp_upload_dir();
		$file    = trailingslashit( $uploads['basedir'] ) . $pay['screenshot_path'];
		if ( ! is_readable( $file ) ) {
			wp_die( 'File missing', 'Not found', [ 'response' => 404 ] );
		}

		$mime = function_exists( 'mime_content_type' ) ? (string) mime_content_type( $file ) : 'application/octet-stream';
		header( 'Content-Type: ' . $mime );
		header( 'Content-Length: ' . filesize( $file ) );
		header( 'Cache-Control: private, max-age=300' );
		readfile( $file );
		exit;
	}

	public static function view_url( int $payment_id ): string {
		return add_query_arg( [
			'action' => 'rks_payment_screenshot',
			'pid'    => $payment_id,
			'_n'     => wp_create_nonce( 'rks_view_payment_' . $payment_id ),
		], admin_url( 'admin-post.php' ) );
	}
}
