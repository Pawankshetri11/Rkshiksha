<?php
/**
 * Razorpay gateway implementation.
 *
 * @package RKSiksha\Core
 */

declare( strict_types=1 );

namespace RKSiksha\Core\Modules\Gateways;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Razorpay implements Gateway {

	private const API_BASE = 'https://api.razorpay.com/v1';

	private string $key_id;
	private string $key_secret;

	public function __construct() {
		$settings         = (array) get_option( 'rks_settings', [] );
		$this->key_id     = (string) ( $settings['razorpay_key_id']     ?? '' );
		$this->key_secret = (string) ( $settings['razorpay_key_secret'] ?? '' );
	}

	public function id(): string {
		return 'razorpay';
	}

	public function label(): string {
		return 'Razorpay';
	}

	public function is_configured(): bool {
		return $this->key_id !== '' && $this->key_secret !== '';
	}

	public function create_order( float $amount, string $currency, array $context ): array {
		if ( ! $this->is_configured() ) {
			return [ 'error' => 'Razorpay credentials not configured.' ];
		}

		$amount_minor = (int) round( $amount * 100 );
		$ctx_id       = isset( $context['id'] ) ? (string) $context['id'] : (string) time();

		// Razorpay notes must be string values.
		$notes = [];
		foreach ( $context as $k => $v ) {
			if ( is_scalar( $v ) ) {
				$notes[ (string) $k ] = (string) $v;
			}
		}

		$body = [
			'amount'   => $amount_minor,
			'currency' => $currency ?: 'INR',
			'receipt'  => 'ADM_' . substr( $ctx_id, 0, 30 ),
			'notes'    => $notes,
		];

		$response = wp_remote_post( self::API_BASE . '/orders', [
			'timeout' => 30,
			'headers' => [
				'Authorization' => 'Basic ' . base64_encode( $this->key_id . ':' . $this->key_secret ),
				'Content-Type'  => 'application/json',
			],
			'body'    => wp_json_encode( $body ),
		] );

		if ( is_wp_error( $response ) ) {
			return [ 'error' => 'Razorpay request failed: ' . $response->get_error_message() ];
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( $code < 200 || $code >= 300 || ! is_array( $data ) || empty( $data['id'] ) ) {
			$msg = is_array( $data ) && isset( $data['error']['description'] ) ? (string) $data['error']['description'] : ( 'HTTP ' . $code );
			return [ 'error' => 'Razorpay order error: ' . $msg ];
		}

		return [
			'order_id'     => (string) $data['id'],
			'gateway_data' => [
				'key_id'   => $this->key_id,
				'amount'   => $amount_minor,
				'currency' => $body['currency'],
			],
		];
	}

	public function verify_payment( array $payload ): array {
		$order_id   = isset( $payload['razorpay_order_id'] )   ? (string) $payload['razorpay_order_id']   : '';
		$payment_id = isset( $payload['razorpay_payment_id'] ) ? (string) $payload['razorpay_payment_id'] : '';
		$signature  = isset( $payload['razorpay_signature'] )  ? (string) $payload['razorpay_signature']  : '';

		if ( ! $order_id || ! $payment_id || ! $signature ) {
			return [ 'ok' => false, 'error' => 'Missing Razorpay verification fields.' ];
		}
		if ( ! $this->is_configured() ) {
			return [ 'ok' => false, 'error' => 'Razorpay not configured.' ];
		}

		$expected = hash_hmac( 'sha256', $order_id . '|' . $payment_id, $this->key_secret );
		if ( ! hash_equals( $expected, $signature ) ) {
			return [ 'ok' => false, 'error' => 'Invalid Razorpay signature.' ];
		}

		// Fetch payment details for the amount.
		$amount = 0.0;
		$status = '';
		$resp   = wp_remote_get( self::API_BASE . '/payments/' . rawurlencode( $payment_id ), [
			'timeout' => 30,
			'headers' => [
				'Authorization' => 'Basic ' . base64_encode( $this->key_id . ':' . $this->key_secret ),
			],
		] );
		if ( ! is_wp_error( $resp ) ) {
			$body = json_decode( (string) wp_remote_retrieve_body( $resp ), true );
			if ( is_array( $body ) ) {
				$amount = isset( $body['amount'] ) ? ( (int) $body['amount'] ) / 100 : 0.0;
				$status = (string) ( $body['status'] ?? '' );
			}
		}

		return [
			'ok'     => true,
			'utr'    => $payment_id,
			'amount' => $amount,
			'meta'   => [
				'gateway'    => 'razorpay',
				'order_id'   => $order_id,
				'payment_id' => $payment_id,
				'status'     => $status,
				'signature'  => $signature,
			],
		];
	}
}
