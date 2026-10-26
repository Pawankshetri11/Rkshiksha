<?php
/**
 * Exam-specific capabilities.
 *
 * @package RKSiksha\Exam
 */

declare( strict_types=1 );

namespace RKSiksha\Exam;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Roles {

	/** @var string[] */
	private const CAPS = [
		'rks_manage_exams',
		'rks_review_exam_attempts',
	];

	/** @var string[] */
	private const ROLES = [
		'administrator',
		'rks_super_admin',
		'rks_branch_admin',
	];

	public static function install(): void {
		foreach ( self::ROLES as $role_name ) {
			$role = get_role( $role_name );
			if ( ! $role ) {
				continue;
			}
			foreach ( self::CAPS as $cap ) {
				$role->add_cap( $cap );
			}
		}
	}
}
