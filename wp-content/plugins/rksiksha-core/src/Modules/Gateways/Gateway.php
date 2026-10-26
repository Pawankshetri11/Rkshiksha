<?php
/**
 * Payment Gateway interface.
 *
 * @package RKSiksha\Core
 */

declare( strict_types=1 );

namespace RKSiksha\Core\Modules\Gateways;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface Gateway {

	/** Gateway identifier slug (e.g. 'razorpay', 'cashfree', 'upi'). */
	public function id(): string;

	/** Human-readable label. */
	public function label(): string;

	/** Whether credentials are configured. */
	public function is_configured(): bool;

	/**
	 * Create an order with the gateway.
	 *
	 * @param float  $amount   Amount in major units (e.g. INR rupees).
	 * @param string $currency ISO code, typically 'INR'.
	 * @param array  $context  Arbitrary context (admission/course/branch ids, customer info).
	 * @return array{order_id?:string, gateway_data?:array, error?:string}
	 */
	public function create_order( float $amount, string $currency, array $context ): array;

	/**
	 * Verify a payment payload (signature / status fetch).
	 *
	 * @param array $payload Posted/returned fields from gateway.
	 * @return array{ok:bool, utr?:string, amount?:float, meta?:array, error?:string}
	 */
	public function verify_payment( array $payload ): array;
}
