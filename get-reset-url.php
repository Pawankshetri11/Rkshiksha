<?php
/**
 * One-shot: prints a working reset URL for any existing user.
 * Usage: php get-reset-url.php <username-or-email>
 * Delete this file after use.
 */

define( 'WP_USE_THEMES', false );
require_once __DIR__ . '/wp-load.php';

$arg  = $argv[1] ?? '';
if ( ! $arg ) {
	echo "Usage: php get-reset-url.php <username-or-email>\n";
	echo "Example: php get-reset-url.php aniwoodtv@gmail.com\n";
	exit( 1 );
}

$user = is_email( $arg ) ? get_user_by( 'email', $arg ) : get_user_by( 'login', $arg );
if ( ! $user ) {
	echo "❌ No user found for: $arg\n";
	exit( 1 );
}

$key = get_password_reset_key( $user );
if ( is_wp_error( $key ) ) {
	echo "❌ Could not generate key: " . $key->get_error_message() . "\n";
	exit( 1 );
}

$reset_url = add_query_arg(
	[ 'key' => $key, 'login' => rawurlencode( $user->user_login ) ],
	home_url( '/reset-password/' )
);

echo "✅ User: {$user->display_name} ({$user->user_login})\n";
echo "🔗 Reset URL:\n";
echo $reset_url . "\n\n";
echo "Open this in your browser to see the reset password page UI.\n";
echo "(Delete get-reset-url.php after you're done.)\n";
