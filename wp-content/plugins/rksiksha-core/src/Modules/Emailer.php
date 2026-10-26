<?php
/**
 * Emailer — templated transactional emails (HTML or plain).
 *
 * @package RKSiksha\Core
 */

declare( strict_types=1 );

namespace RKSiksha\Core\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Emailer {

	public function register(): void {
		add_filter( 'wp_mail_content_type', [ $this, 'force_html' ] );
	}

	public function force_html(): string {
		return 'text/html';
	}

	/**
	 * Send a templated email.
	 *
	 * @param string|array $to
	 * @param string $subject
	 * @param string $heading
	 * @param string $body_html
	 * @param array<string, string> $rows  (key→value rows in a table)
	 * @param array{label:string, url:string}|null $cta
	 */
	public function send_template( $to, string $subject, string $heading, string $body_html = '', array $rows = [], ?array $cta = null ): bool {
		$site = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

		$rows_html = '';
		foreach ( $rows as $label => $value ) {
			$rows_html .= '<tr><td style="padding:6px 14px 6px 0;color:#6b7a82;font-size:13px;">' . esc_html( (string) $label ) . '</td><td style="padding:6px 0;color:#1f2a30;font-size:14px;font-weight:500;">' . esc_html( (string) $value ) . '</td></tr>';
		}
		$cta_html = '';
		if ( $cta && ! empty( $cta['label'] ) && ! empty( $cta['url'] ) ) {
			$cta_html = '<p style="margin:24px 0;text-align:center;"><a href="' . esc_url( $cta['url'] ) . '" style="background:#E63946;color:#fff;text-decoration:none;padding:12px 28px;border-radius:6px;font-weight:600;display:inline-block;">' . esc_html( (string) $cta['label'] ) . '</a></p>';
		}

		$html = '<!doctype html><html><body style="margin:0;background:#FBF3E9;font-family:Arial,sans-serif;color:#1f2a30;">'
			. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#FBF3E9;padding:30px 12px;">'
			. '<tr><td align="center">'
			. '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 6px 20px rgba(44,104,123,0.10);">'
			. '<tr><td style="background:#2C687B;color:#fff;padding:24px 32px;font-family:Georgia,serif;font-size:22px;font-weight:700;">' . esc_html( $site ) . '</td></tr>'
			. '<tr><td style="padding:32px;">'
			. '<h1 style="margin:0 0 14px;font-size:22px;color:#2C687B;">' . esc_html( $heading ) . '</h1>'
			. ( $body_html ? '<div style="color:#1f2a30;font-size:15px;line-height:1.6;margin:0 0 16px;">' . wp_kses_post( $body_html ) . '</div>' : '' )
			. ( $rows_html ? '<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;margin:14px 0;border-top:1px solid #e0e3e6;border-bottom:1px solid #e0e3e6;padding:6px 0;">' . $rows_html . '</table>' : '' )
			. $cta_html
			. '</td></tr>'
			. '<tr><td style="padding:18px 32px;background:#f5f7f8;color:#6b7a82;font-size:12px;text-align:center;">© ' . esc_html( (string) date( 'Y' ) ) . ' ' . esc_html( $site ) . ' • Automated message — do not reply directly.</td></tr>'
			. '</table></td></tr></table></body></html>';

		$headers = [
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . $site . ' <no-reply@' . wp_parse_url( home_url(), PHP_URL_HOST ) . '>',
		];

		return (bool) wp_mail( $to, $subject, $html, $headers );
	}
}
