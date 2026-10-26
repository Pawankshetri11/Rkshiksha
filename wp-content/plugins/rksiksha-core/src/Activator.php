<?php
/**
 * Plugin activation handler — creates tables, roles, default data.
 *
 * @package RKSiksha\Core
 */

declare( strict_types=1 );

namespace RKSiksha\Core;

use RKSiksha\Core\Modules\Branches;
use RKSiksha\Core\Modules\Courses;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Activator {

	public static function activate(): void {
		if ( version_compare( PHP_VERSION, RKS_MIN_PHP, '<' ) ) {
			deactivate_plugins( RKS_PLUGIN_BASENAME );
			wp_die( esc_html( sprintf( 'Rishikul Shiksha Core requires PHP %s or higher. You are running %s.', RKS_MIN_PHP, PHP_VERSION ) ) );
		}

		$log = [];

		// 1. Create custom tables
		Installer::install();
		$log[] = 'tables_created';

		// 2. Register roles (also done on every boot, but ensure on activate)
		Roles::install();
		$log[] = 'roles_registered';

		// 3. Upgrade current user to super admin role
		$current = wp_get_current_user();
		if ( $current && $current->ID && in_array( 'administrator', (array) $current->roles, true ) ) {
			$current->add_role( Roles::SUPER_ADMIN );
			$log[] = 'super_admin_added_to_user_' . $current->ID;
		}

		// 4. Create Main Branch (if not exists)
		$main_branch_id = (int) get_option( 'rks_main_branch_id', 0 );
		if ( $main_branch_id <= 0 ) {
			$branches = new Branches();
			$main_branch_id = $branches->create( [
				'type'    => 'main',
				'name'    => 'Rishikul Shiksha — Main Branch',
				'city'    => 'Dehradun',
				'state'   => 'Uttarakhand',
				'pincode' => '248001',
				'address' => 'Rajpur Road, Dehradun',
				'phone'   => '+91 0000000000',
				'email'   => 'main@rishikulshiksha.com',
				'status'  => 'active',
			] );
			update_option( 'rks_main_branch_id', $main_branch_id, true );
			$log[] = 'main_branch_created_id_' . $main_branch_id;
		}

		// 5. Create default Main Branch Admin user (if not exists)
		$existing = (int) get_option( 'rks_main_admin_user_id', 0 );
		if ( $existing <= 0 || ! get_userdata( $existing ) ) {
			$username = 'mainadmin';
			$email    = 'mainadmin@rishikulshiksha.com';
			$password = wp_generate_password( 16, true, false );

			if ( ! username_exists( $username ) && ! email_exists( $email ) ) {
				$user_id = wp_create_user( $username, $password, $email );
				if ( ! is_wp_error( $user_id ) ) {
					$user = get_user_by( 'id', $user_id );
					$user->set_role( Roles::BRANCH_ADMIN );
					update_user_meta( $user_id, 'rks_branch_id', $main_branch_id );
					update_user_meta( $user_id, 'first_name', 'Main' );
					update_user_meta( $user_id, 'last_name', 'Admin' );

					update_option( 'rks_main_admin_user_id', $user_id, true );
					update_option( 'rks_main_admin_initial_password', $password, false );

					// Link branch to owner
					global $wpdb;
					$wpdb->update(
						$wpdb->prefix . 'rks_branches',
						[ 'owner_user_id' => $user_id ],
						[ 'id' => $main_branch_id ],
						[ '%d' ],
						[ '%d' ]
					);

					$log[] = 'main_admin_created_id_' . $user_id;
				}
			}
		}

		// 6. Default settings
		$existing_settings = (array) get_option( 'rks_settings', [] );
		$defaults = [
			'site_brand'       => 'Rishikul Shiksha',
			'support_email'    => 'info@rkshiksha.com',
			'support_phone'    => '+91 8979775664',
			'upi_id'           => '',
			'upi_qr_image_id'  => 0,
			'cert_prefix'      => 'RKS-CERT',
			'idcard_prefix'    => 'RKS-ID',
			'currency_symbol'  => '₹',
			'verify_cert_url'  => home_url( '/verify-certificate/' ),
			'verify_id_url'    => home_url( '/verify-id/' ),
		];
		// Migrate old placeholder values to new real ones (one-time)
		if ( isset( $existing_settings['support_phone'] ) && $existing_settings['support_phone'] === '+91 0000000000' ) {
			unset( $existing_settings['support_phone'] );
		}
		if ( isset( $existing_settings['support_email'] ) && $existing_settings['support_email'] === 'info@rishikulshiksha.com' ) {
			unset( $existing_settings['support_email'] );
		}
		update_option( 'rks_settings', array_merge( $defaults, $existing_settings ), true );

		// 7. Create secure private upload dir for payment screenshots
		$uploads = wp_upload_dir();
		$private = trailingslashit( $uploads['basedir'] ) . 'rksiksha-private';
		if ( ! file_exists( $private ) ) {
			wp_mkdir_p( $private );
			file_put_contents( $private . '/.htaccess', "Order deny,allow\nDeny from all\n" );
			file_put_contents( $private . '/index.php', "<?php // Silence is golden.\n" );
		}

		// 8. Seed sample courses (runs once)
		// CPTs need to be registered before seeding — register them inline here
		( new Courses() )->register_post_types();
		( new Courses() )->register_taxonomy();
		$created = Courses::seed_samples();
		if ( $created > 0 ) {
			$log[] = 'sample_courses_seeded_' . $created;
		}

		update_option( 'rks_install_log', [
			'time'   => current_time( 'mysql' ),
			'events' => $log,
		], false );

		flush_rewrite_rules();
	}
}
