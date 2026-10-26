<?php
/**
 * Leads module — handles public-facing lead capture forms.
 *
 * Wires the University counselling form and the Contact form on the front-end
 * to `admin-post.php` handlers, validates + sanitises input, and persists rows
 * into the `rks_counselling_leads` / `rks_contact_leads` tables.
 *
 * @package RKSiksha\Core
 */

declare( strict_types=1 );

namespace RKSiksha\Core\Modules;

use RKSiksha\Core\Support\DB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Leads {

	public const NONCE_COUNSELLING = 'rks_submit_counselling';
	public const NONCE_CONTACT     = 'rks_submit_contact';

	public function register(): void {
		add_action( 'admin_post_nopriv_rks_submit_counselling', [ $this, 'handle_counselling' ] );
		add_action( 'admin_post_rks_submit_counselling',        [ $this, 'handle_counselling' ] );

		add_action( 'admin_post_nopriv_rks_submit_contact', [ $this, 'handle_contact' ] );
		add_action( 'admin_post_rks_submit_contact',        [ $this, 'handle_contact' ] );
	}

	/* ================================================================== */
	/* HANDLERS                                                            */
	/* ================================================================== */

	public function handle_counselling(): void {
		$this->honeypot_guard();

		$referrer = $this->referrer_url();

		if ( ! isset( $_POST['_rks_nonce'] ) || ! wp_verify_nonce( wp_unslash( (string) $_POST['_rks_nonce'] ), self::NONCE_COUNSELLING ) ) {
			$this->redirect_back( $referrer, 'error' );
		}

		$full_name = sanitize_text_field(     wp_unslash( (string) ( $_POST['student_name']       ?? '' ) ) );
		$email     = sanitize_email(          wp_unslash( (string) ( $_POST['email']              ?? '' ) ) );
		$phone     = sanitize_text_field(     wp_unslash( (string) ( $_POST['phone']              ?? '' ) ) );
		$city      = sanitize_text_field(     wp_unslash( (string) ( $_POST['city']               ?? '' ) ) );
		$qualif    = sanitize_text_field(     wp_unslash( (string) ( $_POST['qualification']      ?? '' ) ) );
		$stream    = sanitize_text_field(     wp_unslash( (string) ( $_POST['stream']             ?? '' ) ) );
		$colleges  = sanitize_textarea_field( wp_unslash( (string) ( $_POST['preferred_colleges'] ?? '' ) ) );
		$message   = sanitize_textarea_field( wp_unslash( (string) ( $_POST['message']            ?? '' ) ) );

		if ( '' === $full_name || '' === $phone || ! is_email( $email ) ) {
			$this->redirect_back( $referrer, 'error' );
		}

		global $wpdb;
		$table = DB::table( 'counselling_leads' );

		$inserted = $wpdb->insert(
			$table,
			[
				'full_name'          => $full_name,
				'email'              => $email,
				'phone'              => $phone,
				'city'               => $city,
				'qualification'      => $qualif,
				'stream'             => $stream,
				'preferred_colleges' => $colleges,
				'message'            => $message,
				'status'             => 'new',
				'created_at'         => current_time( 'mysql' ),
				'updated_at'         => current_time( 'mysql' ),
			],
			[ '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
		);

		$this->redirect_back( $referrer, false === $inserted ? 'error' : 'success' );
	}

	public function handle_contact(): void {
		$this->honeypot_guard();

		$referrer = $this->referrer_url();

		if ( ! isset( $_POST['_rks_nonce'] ) || ! wp_verify_nonce( wp_unslash( (string) $_POST['_rks_nonce'] ), self::NONCE_CONTACT ) ) {
			$this->redirect_back( $referrer, 'error' );
		}

		$full_name = sanitize_text_field(     wp_unslash( (string) ( $_POST['rks_name']    ?? '' ) ) );
		$email     = sanitize_email(          wp_unslash( (string) ( $_POST['rks_email']   ?? '' ) ) );
		$phone     = sanitize_text_field(     wp_unslash( (string) ( $_POST['rks_phone']   ?? '' ) ) );
		$subject   = sanitize_text_field(     wp_unslash( (string) ( $_POST['rks_subject'] ?? 'general' ) ) );
		$message   = sanitize_textarea_field( wp_unslash( (string) ( $_POST['rks_message'] ?? '' ) ) );

		if ( '' === $full_name || ! is_email( $email ) || '' === $message ) {
			$this->redirect_back( $referrer, 'error' );
		}

		global $wpdb;
		$table = DB::table( 'contact_leads' );

		$inserted = $wpdb->insert(
			$table,
			[
				'full_name'  => $full_name,
				'email'      => $email,
				'phone'      => $phone,
				'subject'    => $subject,
				'message'    => $message,
				'ip_address' => $this->client_ip(),
				'status'     => 'new',
				'created_at' => current_time( 'mysql' ),
				'updated_at' => current_time( 'mysql' ),
			],
			[ '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
		);

		$this->redirect_back( $referrer, false === $inserted ? 'error' : 'success' );
	}

	/* ================================================================== */
	/* HELPERS                                                             */
	/* ================================================================== */

	private function honeypot_guard(): void {
		if ( ! empty( $_POST['rks_hp'] ) ) {
			// Silent success-looking exit — don't leak that we detected the bot.
			$ref = $this->referrer_url();
			wp_safe_redirect( add_query_arg( 'lead', 'success', $ref ) );
			exit;
		}
	}

	private function referrer_url(): string {
		$ref = isset( $_POST['_wp_http_referer'] )
			? esc_url_raw( wp_unslash( (string) $_POST['_wp_http_referer'] ) )
			: '';
		if ( '' === $ref ) {
			$ref = wp_get_referer() ?: home_url( '/' );
		}
		return $ref;
	}

	private function redirect_back( string $url, string $status ): void {
		// Strip any existing ?lead= and re-append clean.
		$url = remove_query_arg( 'lead', $url );
		wp_safe_redirect( add_query_arg( 'lead', $status, $url ) . '#inquiry' );
		exit;
	}

	private function client_ip(): string {
		foreach ( [ 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR' ] as $h ) {
			if ( ! empty( $_SERVER[ $h ] ) ) {
				$ip = (string) $_SERVER[ $h ];
				$ip = trim( explode( ',', $ip )[0] );
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}
		return '';
	}
}
