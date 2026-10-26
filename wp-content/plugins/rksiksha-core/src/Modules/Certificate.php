<?php
/**
 * Certificate module — issues certificates on course completion.
 *
 * @package RKSiksha\Core
 */

declare( strict_types=1 );

namespace RKSiksha\Core\Modules;

use RKSiksha\Core\Admin\CertificateDesignerPage;
use RKSiksha\Core\Support\DB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Certificate {

	public const OPT_PREFIX_RULES = 'rks_cert_prefix_rules';
	public const OPT_ACTIVE_PREFIX_RULE = 'rks_cert_active_prefix_rule';

	public function register(): void {
		add_action( 'rks_course_completed', [ $this, 'on_course_completed' ], 10, 3 );
		add_action( 'init', [ $this, 'repair_existing_certificates' ], 20 );
	}

	/**
	 * One-time repair for courses completed before certificate issuing was fixed,
	 * and freeze the current design onto older certificate records.
	 */
	public function repair_existing_certificates(): void {
		global $wpdb;

		if ( ! get_option( 'rks_certificate_missing_records_repaired_v1', false ) ) {
			$enrollments = $wpdb->get_results(
				'SELECT e.id, e.user_id, e.course_id
				 FROM ' . DB::table( 'enrollments' ) . ' e
				 LEFT JOIN ' . DB::table( 'certificates' ) . " c
				   ON c.user_id = e.user_id AND c.course_id = e.course_id AND c.status = 'valid'
				 WHERE e.status = 'completed' AND c.id IS NULL",
				ARRAY_A
			) ?: [];

			foreach ( $enrollments as $enrollment ) {
				$this->on_course_completed(
					(int) $enrollment['user_id'],
					(int) $enrollment['course_id'],
					(int) $enrollment['id']
				);
			}

			update_option( 'rks_certificate_missing_records_repaired_v1', 1, false );
		}

		if ( get_option( 'rks_certificate_design_snapshots_repaired_v1', false ) ) {
			return;
		}

		$design = self::active_design_snapshot();
		if ( ! $design ) {
			// Retry after the first certificate template has been created.
			return;
		}

		$certificates = $wpdb->get_results(
			'SELECT id, meta FROM ' . DB::table( 'certificates' ),
			ARRAY_A
		) ?: [];

		foreach ( $certificates as $certificate ) {
			$meta = DB::decode_meta( isset( $certificate['meta'] ) ? (string) $certificate['meta'] : null );
			if ( ! empty( $meta['design_snapshot'] ) && is_array( $meta['design_snapshot'] ) ) {
				continue;
			}

			$meta['design_snapshot'] = $design;
			$wpdb->update(
				DB::table( 'certificates' ),
				[ 'meta' => DB::encode_meta( $meta ) ],
				[ 'id' => (int) $certificate['id'] ],
				[ '%s' ],
				[ '%d' ]
			);
		}

		update_option( 'rks_certificate_design_snapshots_repaired_v1', 1, false );
	}

	public function on_course_completed( int $user_id, int $course_id, int $enrollment_id ): void {
		// Avoid duplicates
		global $wpdb;
		$existing = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM " . DB::table( 'certificates' ) . " WHERE user_id = %d AND course_id = %d AND status = 'valid' LIMIT 1",
			$user_id, $course_id
		) );
		if ( $existing > 0 ) return;

		$user   = get_userdata( $user_id );
		$course = get_post( $course_id );
		if ( ! $user || ! $course ) return;

		$enrollment = $wpdb->get_row( $wpdb->prepare(
			"SELECT branch_id FROM " . DB::table( 'enrollments' ) . " WHERE id = %d",
			$enrollment_id
		), ARRAY_A );
		$branch_id = (int) ( $enrollment['branch_id'] ?? 0 );

		$this->generate( $user_id, $course_id, $enrollment_id, $branch_id, $user->display_name ?: $user->user_login, $course->post_title );
	}

	/**
	 * Generate a certificate record. Returns inserted certificate ID (DB primary key).
	 */
	public function generate( int $user_id, int $course_id, int $enrollment_id, int $branch_id, string $holder_name, string $course_title ): int {
		global $wpdb;
		$rule      = self::get_active_prefix_rule();

		$unique_id = $this->build_unique_id( $rule );
		$meta      = [
			'source'          => 'system',
			'prefix_rule_id'  => (string) $rule['id'],
			'prefix_label'    => (string) $rule['label'],
			'prefix'          => (string) $rule['prefix'],
			'year_mode'       => (string) $rule['year_mode'],
			'year_part'       => self::prefix_year_part( $rule ),
		];
		$design = self::active_design_snapshot();
		if ( $design ) {
			$meta['design_snapshot'] = $design;
		}

		$wpdb->insert(
			DB::table( 'certificates' ),
			[
				'unique_id'     => $unique_id,
				'user_id'       => $user_id,
				'course_id'     => $course_id,
				'enrollment_id' => $enrollment_id,
				'branch_id'     => $branch_id,
				'holder_name'   => $holder_name,
				'course_title'  => $course_title,
				'issue_date'    => current_time( 'Y-m-d' ),
				'status'        => 'valid',
				'meta'          => DB::encode_meta( $meta ),
			],
			[ '%s','%d','%d','%d','%d','%s','%s','%s','%s','%s' ]
		);

		$cert_id = (int) $wpdb->insert_id;

		// Link certificate to enrollment for quick lookup
		if ( $cert_id > 0 ) {
			if ( $enrollment_id > 0 ) {
				$wpdb->update(
					DB::table( 'enrollments' ),
					[ 'certificate_id' => $cert_id ],
					[ 'id' => $enrollment_id ],
					[ '%d' ],
					[ '%d' ]
				);
			}

			$this->notify_issued( $user_id, $unique_id, $course_title );
		}

		return $cert_id;
	}

	/**
	 * Return an immutable copy of the template that is active at issue time.
	 */
	public static function active_design_snapshot(): ?array {
		if ( ! class_exists( CertificateDesignerPage::class ) ) {
			return null;
		}

		$design = CertificateDesignerPage::get_active_design();
		if ( ! $design || empty( $design['bg_url'] ) || empty( $design['fields'] ) ) {
			return null;
		}

		return $design;
	}

	private function build_unique_id( array $rule ): string {
		// Format: PREFIX-YEAR-XXXXXX (or PREFIX-XXXXXX when year is disabled), collision-checked.
		global $wpdb;
		$prefix = (string) $rule['prefix'];
		$year   = self::prefix_year_part( $rule );
		do {
			$num = str_pad( (string) wp_rand( 1, 999999 ), 6, '0', STR_PAD_LEFT );
			$id  = $prefix . ( $year ? '-' . $year : '' ) . '-' . $num;
			$exists = (int) $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM " . DB::table( 'certificates' ) . " WHERE unique_id = %s",
				$id
			) );
		} while ( $exists > 0 );
		return $id;
	}

	/**
	 * @return array<int, array<string, string>>
	 */
	public static function get_prefix_rules(): array {
		$rules = get_option( self::OPT_PREFIX_RULES, [] );
		$rules = is_array( $rules ) ? $rules : [];

		if ( empty( $rules ) ) {
			$settings = (array) get_option( 'rks_settings', [] );
			$legacy_prefix = (string) ( $settings['cert_prefix'] ?? 'RKS-CERT' );
			$rules = [
				self::normalize_prefix_rule( [
					'id'         => 'default',
					'label'      => 'Default Certificate',
					'prefix'     => $legacy_prefix,
					'year_mode'  => 'current',
					'fixed_year' => '',
				] ),
			];
		}

		$clean = [];
		foreach ( $rules as $rule ) {
			if ( is_array( $rule ) ) {
				$clean[] = self::normalize_prefix_rule( $rule );
			}
		}

		return $clean ?: [
			self::normalize_prefix_rule( [
				'id'        => 'default',
				'label'     => 'Default Certificate',
				'prefix'    => 'RKS-CERT',
				'year_mode' => 'current',
			] ),
		];
	}

	public static function get_active_prefix_rule(): array {
		$rules = self::get_prefix_rules();
		$active_id = (string) get_option( self::OPT_ACTIVE_PREFIX_RULE, '' );
		foreach ( $rules as $rule ) {
			if ( (string) $rule['id'] === $active_id ) {
				return $rule;
			}
		}
		return $rules[0];
	}

	public static function normalize_prefix_rule( array $rule ): array {
		$id = sanitize_key( (string) ( $rule['id'] ?? '' ) );
		if ( '' === $id ) {
			$id = 'rule_' . wp_generate_password( 8, false, false );
		}

		$label = sanitize_text_field( (string) ( $rule['label'] ?? '' ) );
		if ( '' === $label ) {
			$label = 'Certificate Prefix';
		}

		$prefix = strtoupper( preg_replace( '/[^A-Z0-9\-]/i', '', (string) ( $rule['prefix'] ?? 'RKS-CERT' ) ) );
		$prefix = trim( preg_replace( '/\-+/', '-', $prefix ), '-' );
		if ( '' === $prefix ) {
			$prefix = 'RKS-CERT';
		}

		$year_mode = (string) ( $rule['year_mode'] ?? 'current' );
		if ( ! in_array( $year_mode, [ 'current', 'issue', 'fixed', 'none' ], true ) ) {
			$year_mode = 'current';
		}

		$fixed_year = preg_replace( '/\D+/', '', (string) ( $rule['fixed_year'] ?? '' ) );
		$fixed_year = strlen( $fixed_year ) === 4 ? $fixed_year : '';

		return [
			'id'         => $id,
			'label'      => $label,
			'prefix'     => substr( $prefix, 0, 30 ),
			'year_mode'  => $year_mode,
			'fixed_year' => $fixed_year,
		];
	}

	public static function prefix_year_part( array $rule, ?string $issue_date = null ): string {
		$mode = (string) ( $rule['year_mode'] ?? 'current' );
		if ( 'none' === $mode ) {
			return '';
		}
		if ( 'fixed' === $mode ) {
			return (string) ( $rule['fixed_year'] ?: date( 'Y' ) );
		}
		if ( 'issue' === $mode && $issue_date ) {
			$time = strtotime( $issue_date );
			return $time ? date( 'Y', $time ) : date( 'Y' );
		}
		return date( 'Y' );
	}

	public function get_by_unique_id( string $unique_id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare(
			'SELECT * FROM ' . DB::table( 'certificates' ) . ' WHERE unique_id = %s LIMIT 1',
			$unique_id
		), ARRAY_A );
		return $row ?: null;
	}

	private function notify_issued( int $user_id, string $unique_id, string $course_title ): void {
		$user = get_userdata( $user_id );
		if ( ! $user ) return;

		$mailer = new Emailer();
		$mailer->send_template(
			$user->user_email,
			'🏆 Your Rishikul Shiksha Certificate is ready',
			'Congratulations, ' . $user->display_name . '!',
			'<p>You\'ve successfully completed <strong>' . esc_html( $course_title ) . '</strong>. Your certificate is now ready to view, print, or share.</p>',
			[
				'Certificate ID' => $unique_id,
				'Course'         => $course_title,
				'Issue Date'     => current_time( 'd M Y' ),
			],
			[ 'label' => 'View Certificate', 'url' => add_query_arg( 'id', $unique_id, home_url( '/certificate/' ) ) ]
		);
	}
}
