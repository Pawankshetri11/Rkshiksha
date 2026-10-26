<?php
/**
 * Custom user roles and capabilities.
 *
 * @package RKSiksha\Core
 */

declare( strict_types=1 );

namespace RKSiksha\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Roles {

	public const SUPER_ADMIN  = 'rks_super_admin';
	public const BRANCH_ADMIN = 'rks_branch_admin';
	public const STUDENT      = 'rks_student';

	/** @var array<string, array<string, bool>> */
	private static array $capabilities_cache = [];

	public static function install(): void {
		self::add_role( self::SUPER_ADMIN,  'RKS Super Admin',  self::super_admin_caps() );
		self::add_role( self::BRANCH_ADMIN, 'RKS Branch Admin', self::branch_admin_caps() );
		self::add_role( self::STUDENT,      'RKS Student',      self::student_caps() );

		// Grant all rks_* caps to the WordPress administrator role
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			foreach ( array_keys( self::super_admin_caps() ) as $cap ) {
				$admin->add_cap( $cap );
			}
		}
	}

	public static function uninstall(): void {
		remove_role( self::SUPER_ADMIN );
		remove_role( self::BRANCH_ADMIN );
		remove_role( self::STUDENT );

		$admin = get_role( 'administrator' );
		if ( $admin ) {
			foreach ( array_keys( self::super_admin_caps() ) as $cap ) {
				$admin->remove_cap( $cap );
			}
		}
	}

	private static function add_role( string $slug, string $label, array $caps ): void {
		$existing = get_role( $slug );
		if ( null === $existing ) {
			add_role( $slug, $label, array_merge( [ 'read' => true ], $caps ) );
		} else {
			foreach ( $caps as $cap => $grant ) {
				$existing->add_cap( $cap, $grant );
			}
		}
	}

	/** @return array<string, bool> */
	public static function super_admin_caps(): array {
		if ( isset( self::$capabilities_cache['super'] ) ) {
			return self::$capabilities_cache['super'];
		}
		return self::$capabilities_cache['super'] = [
			'rks_view_all_branches'        => true,
			'rks_manage_branches'          => true,
			'rks_approve_franchise'        => true,
			'rks_view_all_admissions'      => true,
			'rks_view_all_payments'        => true,
			'rks_manage_courses'           => true,
			'rks_manage_settings'          => true,
			'rks_view_global_reports'      => true,
			'rks_revoke_certificate'       => true,
			'rks_revoke_idcard'            => true,
			// Inherit branch admin caps
			'rks_view_branch_dashboard'    => true,
			'rks_approve_admission'        => true,
			'rks_verify_payment'           => true,
			'rks_issue_certificate'        => true,
			'rks_issue_idcard'             => true,
			'rks_manage_branch_students'   => true,
			'rks_resolve_support_ticket'   => true,
			'rks_view_branch_reports'      => true,
		];
	}

	/** @return array<string, bool> */
	public static function branch_admin_caps(): array {
		if ( isset( self::$capabilities_cache['branch'] ) ) {
			return self::$capabilities_cache['branch'];
		}
		return self::$capabilities_cache['branch'] = [
			'rks_view_branch_dashboard'    => true,
			'rks_approve_admission'        => true,
			'rks_verify_payment'           => true,
			'rks_issue_certificate'        => true,
			'rks_issue_idcard'             => true,
			'rks_manage_branch_students'   => true,
			'rks_resolve_support_ticket'   => true,
			'rks_view_branch_reports'      => true,
			// Course builder access (allow branch admins to manage course catalogue too)
			'rks_manage_courses'           => true,
			// Read posts of CPT
			'edit_posts'                   => true,
			'edit_published_posts'         => true,
			'publish_posts'                => true,
			'upload_files'                 => true,
		];
	}

	/** @return array<string, bool> */
	public static function student_caps(): array {
		if ( isset( self::$capabilities_cache['student'] ) ) {
			return self::$capabilities_cache['student'];
		}
		return self::$capabilities_cache['student'] = [
			'rks_access_lms'              => true,
			'rks_download_certificate'    => true,
			'rks_download_idcard'         => true,
			'rks_submit_support_ticket'   => true,
		];
	}
}
