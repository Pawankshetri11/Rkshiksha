<?php
/**
 * Cashfree gateway implementation.
 *
 * @package RKSiksha\Core
 */

declare( strict_types=1 );

namespace RKSiksha\Core\Modules\Gateways;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Cashfree implements Gateway {

	private const API_VERSION = '2023-08-01';
	private const URL_SANDBOX = 'https://sandbox.cashfree.com/pg';
	private const URL_PROD    = 'https://api.cashfree.com/pg';

	private string $app_id;
	private string $secret_key;
	private string $env;

	public function __construct() {
		$settings         = (array) get_option( 'rks_settings', [] );
		$this->app_id     = (string) ( $settings['cashfree_app_id']     ?? '' );
		$this->secret_key = (string) ( $settings['cashfree_secret_key'] ?? '' );
		$env              = (string) ( $settings['cashfree_env']        ?? 'sandbox' );
		$this->env        = ( $env === 'production' ) ? 'production' : 'sandbox';
	}

	public function id(): string {
		return 'cashfree';
	}

	public function label(): string {
		return 'Cashfree';
	}

	public function is_configured(): bool {
		return $this->app_id !== '' && $this->secret_key !== '';
	}

	private function base_url(): string {
		return $this->env === 'production' ? self::URL_PROD : self::URL_SANDBOX;
	}

	private function headers(): array {
		return [
			'x-api-version'   => self::API_VERSION,
			'x-client-id'     => $this->app_id,
			'x-client-secret' => $this->secret_key,
			'Content-Type'    => 'application/json',
			'Accept'          => 'application/json',
		];
	}

	public function create_order( float $amount, string $currency, array $context ): array {
		if ( ! $this->is_configured() ) {
			return [ 'error' => 'Cashfree credentials not configured.' ];
		}

		$ctx_id   = isset( $context['id'] ) ? (string) $context['id'] : (string) wp_rand( 1000, 9999 );
		$order_id = 'ADM_' . preg_replace( '/[^A-Za-z0-9_]/', '', $ctx_id ) . '_' . time();

		$customer_id    = isset( $context['customer_id'] )    ? (string) $context['customer_id']    : 'guest_' . wp_rand( 1000, 999999 );
		$customer_email = isset( $context['customer_email'] ) ? (string) $context['customer_email'] : '';
		$customer_phone = isset( $context['customer_phone'] ) ? (string) $context['customer_phone'] : '';
		$customer_name  = isset( $context['customer_name'] )  ? (string) $context['customer_name']  : '';

		// Strip non-digits/plus for phone — Cashfree validates this.
		$customer_phone = preg_replace( '/[^0-9+]/', '', $customer_phone );

		$body = [
			'order_id'         => $order_id,
			'order_amount'     => round( $amount, 2 ),
			'order_currency'   => $currency ?: 'INR',
			'customer_details' => [
				'customer_id'    => preg_replace( '/[^A-Za-z0-9_]/', '_', $customer_id ),
				'customer_email' => $customer_email,
				'customer_phone' => $customer_phone ?: '9999999999',
				'customer_name'  => $customer_name,
			],
			'order_meta'       => [
				'return_url' => isset( $context['return_url'] ) ? (string) $context['return_url'] : home_url( '/admission/?cf_order_id={order_id}' ),
			],
		];

		$response = wp_remote_post( $this->base_url() . '/orders', [
			'timeout' => 30,
			'headers' => $this->headers(),
			'body'    => wp_json_encode( $body ),
		] );

		if ( is_wp_error( $response ) ) {
			return [ 'error' => 'Cashfree request failed: ' . $response->get_error_message() ];
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		if ( $code < 200 || $code >= 300 || ! is_array( $data ) || empty( $data['payment_session_id'] ) ) {
			$msg = is_array( $data ) && isset( $data['message'] ) ? (string) $data['message'] : ( 'HTTP ' . $code );
			return [ 'error' => 'Cashfree order error: ' . $msg ];
		}

		return [
			'order_id'     => $order_id,
			'gateway_data' => [
				'payment_session_id' => (string) $data['payment_session_id'],
				'env'                => $this->env,
				'order_amount'       => $body['order_amount'],
			],
		];
	}

	public function verify_payment( array $payload ): array {
		$order_id = isset( $payload['order_id'] ) ? (string) $payload['order_id'] : '';
		if ( ! $order_id ) {
			return [ 'ok' => false, 'error' => 'Missing Cashfree order_id.' ];
		}
		if ( ! $this->is_configured() ) {
			return [ 'ok' => false, 'error' => 'Cashfree not configured.' ];
		}

		$response = wp_remote_get( $this->base_url() . '/orders/' . rawurlencode( $order_id ) . '/payments', [
			'timeout' => 30,
			'headers' => $this->headers(),
		] );

		if ( is_wp_error( $response ) ) {
			return [ 'ok' => false, 'error' => 'Cashfree fetch failed: ' . $response->get_error_message() ];
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		if ( $code < 200 || $code >= 300 || ! is_array( $data ) ) {
			return [ 'ok' => false, 'error' => 'Cashfree returned HTTP ' . $code ];
		}

		// $data is a list of payment attempts; pick the SUCCESS one.
		$success = null;
		foreach ( $data as $row ) {
			if ( is_array( $row ) && isset( $row['payment_status'] ) && strtoupper( (string) $row['payment_status'] ) === 'SUCCESS' ) {
				$success = $row;
				break;
			}
		}

		if ( ! $success ) {
			return [ 'ok' => false, 'error' => 'No successful Cashfree payment found for order.' ];
		}

		$cf_payment_id = (string) ( $success['cf_payment_id'] ?? '' );
		$amount        = (float) ( $success['payment_amount'] ?? 0 );

		return [
			'ok'     => true,
			'utr'    => $cf_payment_id,
			'amount' => $amount,
			'meta'   => [
				'gateway'       => 'cashfree',
				'order_id'      => $order_id,
				'cf_payment_id' => $cf_payment_id,
				'method'        => isset( $success['payment_method'] ) ? $success['payment_method'] : null,
				'status'        => 'SUCCESS',
				'env'           => $this->env,
			],
		];
	}
}
