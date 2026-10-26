<?php
/**
 * Fired when the plugin is uninstalled.
 * Drops custom tables and removes options. Roles + users left intact for safety.
 *
 * @package RKSiksha\Core
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$tables = [
	'rks_branches',
	'rks_franchises',
	'rks_admissions',
	'rks_payments',
	'rks_enrollments',
	'rks_course_progress',
	'rks_certificates',
	'rks_idcards',
	'rks_support_tickets',
	'rks_counselling_leads',
	'rks_settings',
];

foreach ( $tables as $t ) {
	$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->prefix}{$t}`" );
}

$options = [
	'rks_db_version',
	'rks_settings',
	'rks_main_branch_id',
	'rks_main_admin_user_id',
	'rks_install_log',
];

foreach ( $options as $o ) {
	delete_option( $o );
}
