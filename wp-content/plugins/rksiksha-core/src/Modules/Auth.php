<?php
/**
 * Auth module — custom login, register, password reset; role-based redirects.
 *
 * Provides shortcodes for auth forms used inside theme page templates and
 * handles all form submissions through `template_redirect` (no admin-ajax overhead).
 *
 * @package RKSiksha\Core
 */

declare( strict_types=1 );

namespace RKSiksha\Core\Modules;

use RKSiksha\Core\Roles;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Auth {

	public const NONCE_LOGIN    = 'rks_login';
	public const NONCE_REGISTER = 'rks_register';
	public const NONCE_LOSTPW   = 'rks_lostpassword';

	private const RATE_LIMIT_PREFIX = 'rks_auth_attempts_';
	private const RATE_LIMIT_MAX    = 8;
	private const RATE_LIMIT_WINDOW = 900; // 15 min

	public function register(): void {
		add_action( 'init', [ $this, 'register_shortcodes' ] );
		add_action( 'template_redirect', [ $this, 'handle_submissions' ], 5 );

		// Role-based redirect after wp_login
		add_filter( 'login_redirect', [ $this, 'role_based_redirect' ], 10, 3 );

		// Hide admin bar on frontend for non-admin roles
		add_action( 'after_setup_theme', [ $this, 'maybe_hide_admin_bar' ] );

		// Block wp-login.php for non-admins → redirect to /login/
		add_action( 'login_init', [ $this, 'maybe_redirect_wp_login' ] );

		// Block wp-admin access for students
		add_action( 'admin_init', [ $this, 'block_student_admin_access' ] );
	}

	public function register_shortcodes(): void {
		add_shortcode( 'rks_login_form',        [ $this, 'shortcode_login_form' ] );
		add_shortcode( 'rks_register_form',     [ $this, 'shortcode_register_form' ] );
		add_shortcode( 'rks_lostpassword_form', [ $this, 'shortcode_lostpassword_form' ] );
		add_shortcode( 'rks_resetpassword_form',[ $this, 'shortcode_resetpassword_form' ] );
	}

	/* ================================================================== */
	/* SHORTCODES                                                          */
	/* ================================================================== */

	public function shortcode_login_form( $atts = [] ): string {
		if ( is_user_logged_in() ) {
			$url = $this->dashboard_url_for_user( wp_get_current_user() );
			return '<div class="rks-auth-card rks-auth-card--info"><h3>You are already logged in.</h3><p><a href="' . esc_url( $url ) . '" class="rks-btn rks-btn--primary">Go to Dashboard</a> &nbsp; <a href="' . esc_url( wp_logout_url( home_url( '/' ) ) ) . '">Logout</a></p></div>';
		}

		$error    = isset( $_GET['rks_err'] )    ? sanitize_key( wp_unslash( (string) $_GET['rks_err'] ) ) : '';
		$msg      = isset( $_GET['rks_msg'] )    ? sanitize_key( wp_unslash( (string) $_GET['rks_msg'] ) ) : '';
		$redirect = isset( $_GET['redirect_to'] )? esc_url_raw( wp_unslash( (string) $_GET['redirect_to'] ) ) : '';

		ob_start();
		?>
		<div class="rks-auth-card">
			<h2 class="rks-auth-card__title">Welcome back</h2>
			<p class="rks-auth-card__sub">Login to continue your learning journey.</p>

			<?php $this->render_message( $error, $msg ); ?>

			<form method="post" class="rks-auth-form" action="<?php echo esc_url( home_url( '/login/' ) ); ?>">
				<?php wp_nonce_field( self::NONCE_LOGIN, '_rks_login_nonce' ); ?>
				<input type="hidden" name="rks_action" value="login">
				<?php if ( $redirect ) : ?>
					<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect ); ?>">
				<?php endif; ?>

				<div class="rks-form-row">
					<label for="rks_login_user">Username or Email</label>
					<input id="rks_login_user" type="text" name="user_login" required autocomplete="username">
				</div>
				<div class="rks-form-row">
					<label for="rks_login_pass">Password</label>
					<input id="rks_login_pass" type="password" name="user_password" required autocomplete="current-password">
				</div>
				<div class="rks-form-row rks-form-row--inline">
					<label class="rks-form-check">
						<input type="checkbox" name="rememberme" value="1"> Remember me
					</label>
					<a href="<?php echo esc_url( home_url( '/forgot-password/' ) ); ?>" class="rks-auth-link">Forgot password?</a>
				</div>

				<button type="submit" class="rks-btn rks-btn--primary rks-btn--block">Sign In</button>
			</form>

			<p class="rks-auth-foot">
				New to Rishikul Shiksha? <a href="<?php echo esc_url( home_url( '/register/' ) ); ?>">Create an account</a>
			</p>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	public function shortcode_register_form( $atts = [] ): string {
		if ( is_user_logged_in() ) {
			$url = $this->dashboard_url_for_user( wp_get_current_user() );
			return '<div class="rks-auth-card rks-auth-card--info"><h3>You are already logged in.</h3><p><a href="' . esc_url( $url ) . '" class="rks-btn rks-btn--primary">Go to Dashboard</a></p></div>';
		}

		$error = isset( $_GET['rks_err'] ) ? sanitize_key( wp_unslash( (string) $_GET['rks_err'] ) ) : '';
		$msg   = isset( $_GET['rks_msg'] ) ? sanitize_key( wp_unslash( (string) $_GET['rks_msg'] ) ) : '';
		$old   = isset( $_GET['rks_old'] ) ? (array) json_decode( base64_decode( (string) $_GET['rks_old'] ), true ) : [];

		ob_start();
		?>
		<div class="rks-auth-card">
			<h2 class="rks-auth-card__title">Create your account</h2>
			<p class="rks-auth-card__sub">Join thousands of learners on Rishikul Shiksha.</p>

			<?php $this->render_message( $error, $msg ); ?>

			<form method="post" class="rks-auth-form" action="<?php echo esc_url( home_url( '/register/' ) ); ?>">
				<?php wp_nonce_field( self::NONCE_REGISTER, '_rks_register_nonce' ); ?>
				<input type="hidden" name="rks_action" value="register">

				<div class="rks-form-grid">
					<div class="rks-form-row">
						<label for="rks_reg_first">First Name</label>
						<input id="rks_reg_first" type="text" name="first_name" required value="<?php echo esc_attr( (string) ( $old['first_name'] ?? '' ) ); ?>">
					</div>
					<div class="rks-form-row">
						<label for="rks_reg_last">Last Name</label>
						<input id="rks_reg_last" type="text" name="last_name" value="<?php echo esc_attr( (string) ( $old['last_name'] ?? '' ) ); ?>">
					</div>
				</div>

				<div class="rks-form-row">
					<label for="rks_reg_email">Email</label>
					<input id="rks_reg_email" type="email" name="user_email" required value="<?php echo esc_attr( (string) ( $old['user_email'] ?? '' ) ); ?>" autocomplete="email">
				</div>
				<div class="rks-form-row">
					<label for="rks_reg_user">Username</label>
					<input id="rks_reg_user" type="text" name="user_login" required value="<?php echo esc_attr( (string) ( $old['user_login'] ?? '' ) ); ?>" autocomplete="username">
					<small>3-30 chars. Letters, numbers, ._- only.</small>
				</div>
				<div class="rks-form-grid">
					<div class="rks-form-row">
						<label for="rks_reg_pass">Password</label>
						<input id="rks_reg_pass" type="password" name="user_password" required minlength="8" autocomplete="new-password">
						<small>Min 8 characters.</small>
					</div>
					<div class="rks-form-row">
						<label for="rks_reg_pass2">Confirm Password</label>
						<input id="rks_reg_pass2" type="password" name="user_password2" required minlength="8" autocomplete="new-password">
					</div>
				</div>
				<div class="rks-form-row">
					<label class="rks-form-check">
						<input type="checkbox" name="terms" value="1" required>
						I agree to the <a href="#" target="_blank" rel="noopener">Terms</a> and <a href="#" target="_blank" rel="noopener">Privacy Policy</a>.
					</label>
				</div>

				<button type="submit" class="rks-btn rks-btn--primary rks-btn--block">Create Account</button>
			</form>

			<p class="rks-auth-foot">
				Already have an account? <a href="<?php echo esc_url( home_url( '/login/' ) ); ?>">Sign in</a>
			</p>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	public function shortcode_lostpassword_form( $atts = [] ): string {
		$error = isset( $_GET['rks_err'] ) ? sanitize_key( wp_unslash( (string) $_GET['rks_err'] ) ) : '';
		$msg   = isset( $_GET['rks_msg'] ) ? sanitize_key( wp_unslash( (string) $_GET['rks_msg'] ) ) : '';

		ob_start();
		?>
		<div class="rks-auth-card">
			<h2 class="rks-auth-card__title">Forgot password?</h2>
			<p class="rks-auth-card__sub">Enter your email or username and we'll send you a reset link.</p>

			<?php $this->render_message( $error, $msg ); ?>

			<form method="post" class="rks-auth-form" action="<?php echo esc_url( home_url( '/forgot-password/' ) ); ?>">
				<?php wp_nonce_field( self::NONCE_LOSTPW, '_rks_lostpw_nonce' ); ?>
				<input type="hidden" name="rks_action" value="lostpassword">
				<div class="rks-form-row">
					<label for="rks_lp_login">Username or Email</label>
					<input id="rks_lp_login" type="text" name="user_login" required>
				</div>
				<button type="submit" class="rks-btn rks-btn--primary rks-btn--block">Send Reset Link</button>
			</form>

			<p class="rks-auth-foot">
				<a href="<?php echo esc_url( home_url( '/login/' ) ); ?>">← Back to login</a>
			</p>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	public function shortcode_resetpassword_form( $atts = [] ): string {
		$key   = isset( $_GET['key'] )   ? sanitize_text_field( wp_unslash( (string) $_GET['key'] ) ) : '';
		$login = isset( $_GET['login'] ) ? sanitize_user(       wp_unslash( (string) $_GET['login'] ) ) : '';
		$error = isset( $_GET['rks_err'] ) ? sanitize_key( wp_unslash( (string) $_GET['rks_err'] ) ) : '';
		$msg   = isset( $_GET['rks_msg'] ) ? sanitize_key( wp_unslash( (string) $_GET['rks_msg'] ) ) : '';

		if ( ! $key || ! $login ) {
			return '<div class="rks-auth-card rks-auth-card--info"><h3>Invalid reset link</h3><p>Please request a new reset link.</p><p><a href="' . esc_url( home_url( '/forgot-password/' ) ) . '" class="rks-btn rks-btn--primary">Request New Link</a></p></div>';
		}

		$user = check_password_reset_key( $key, $login );
		if ( is_wp_error( $user ) ) {
			return '<div class="rks-auth-card rks-auth-card--info"><h3>Reset link expired</h3><p>This password reset link is no longer valid.</p><p><a href="' . esc_url( home_url( '/forgot-password/' ) ) . '" class="rks-btn rks-btn--primary">Request New Link</a></p></div>';
		}

		ob_start();
		?>
		<div class="rks-auth-card">
			<h2 class="rks-auth-card__title">Set new password</h2>
			<p class="rks-auth-card__sub">Choose a strong new password for your account.</p>

			<?php $this->render_message( $error, $msg ); ?>

			<form method="post" class="rks-auth-form" action="<?php echo esc_url( home_url( '/reset-password/' ) ); ?>">
				<?php wp_nonce_field( 'rks_resetpassword', '_rks_resetpw_nonce' ); ?>
				<input type="hidden" name="rks_action" value="resetpassword">
				<input type="hidden" name="key"   value="<?php echo esc_attr( $key ); ?>">
				<input type="hidden" name="login" value="<?php echo esc_attr( $login ); ?>">

				<div class="rks-form-row">
					<label for="rks_rp_pass">New Password</label>
					<input id="rks_rp_pass" type="password" name="user_password" required minlength="8" autocomplete="new-password">
				</div>
				<div class="rks-form-row">
					<label for="rks_rp_pass2">Confirm New Password</label>
					<input id="rks_rp_pass2" type="password" name="user_password2" required minlength="8" autocomplete="new-password">
				</div>
				<button type="submit" class="rks-btn rks-btn--primary rks-btn--block">Set New Password</button>
			</form>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	private function render_message( string $error, string $msg ): void {
		$errors = [
			'invalid_credentials' => 'Invalid username or password.',
			'rate_limited'        => 'Too many attempts. Please try again in a few minutes.',
			'nonce'               => 'Security check failed. Refresh and try again.',
			'mismatch'            => 'Passwords do not match.',
			'weak_password'       => 'Password must be at least 8 characters.',
			'username_taken'      => 'That username is already taken.',
			'email_taken'         => 'An account with this email already exists.',
			'invalid_email'       => 'Please enter a valid email address.',
			'invalid_username'    => 'Username must be 3-30 chars: letters, numbers, ._- only.',
			'terms'               => 'You must accept the terms to continue.',
			'unknown_user'        => 'No account found with that email/username.',
			'reset_failed'        => 'Could not send reset link. Please try again.',
			'generic'             => 'Something went wrong. Please try again.',
		];
		$messages = [
			'registered'    => 'Account created successfully — you can now log in.',
			'reset_sent'    => 'Reset link sent to your email. Check your inbox.',
			'reset_done'    => 'Password reset. Please log in with your new password.',
			'logged_out'    => 'You have been logged out.',
		];

		if ( $error && isset( $errors[ $error ] ) ) {
			echo '<div class="rks-auth-alert rks-auth-alert--error">' . esc_html( $errors[ $error ] ) . '</div>';
		}
		if ( $msg && isset( $messages[ $msg ] ) ) {
			echo '<div class="rks-auth-alert rks-auth-alert--success">' . esc_html( $messages[ $msg ] ) . '</div>';
		}
	}

	/* ================================================================== */
	/* FORM HANDLERS                                                       */
	/* ================================================================== */

	public function handle_submissions(): void {
		if ( $_SERVER['REQUEST_METHOD'] !== 'POST' || empty( $_POST['rks_action'] ) ) {
			return;
		}
		$action = sanitize_key( wp_unslash( (string) $_POST['rks_action'] ) );

		switch ( $action ) {
			case 'login':           $this->handle_login();       break;
			case 'register':        $this->handle_register();    break;
			case 'lostpassword':    $this->handle_lostpassword();break;
			case 'resetpassword':   $this->handle_resetpassword(); break;
		}
	}

	private function handle_login(): void {
		if ( ! isset( $_POST['_rks_login_nonce'] ) || ! wp_verify_nonce( wp_unslash( (string) $_POST['_rks_login_nonce'] ), self::NONCE_LOGIN ) ) {
			$this->redirect_back( home_url( '/login/' ), 'nonce' );
		}

		if ( $this->is_rate_limited() ) {
			$this->redirect_back( home_url( '/login/' ), 'rate_limited' );
		}

		$creds = [
			'user_login'    => sanitize_user( wp_unslash( (string) ( $_POST['user_login'] ?? '' ) ), true ),
			'user_password' => (string) ( $_POST['user_password'] ?? '' ),
			'remember'      => ! empty( $_POST['rememberme'] ),
		];

		$user = wp_signon( $creds, is_ssl() );

		if ( is_wp_error( $user ) ) {
			$this->bump_rate_limit();
			$this->redirect_back( home_url( '/login/' ), 'invalid_credentials' );
		}

		$this->reset_rate_limit();
		$redirect = ! empty( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( (string) $_POST['redirect_to'] ) ) : '';
		$target   = $redirect ? $redirect : $this->dashboard_url_for_user( $user );

		wp_safe_redirect( $target );
		exit;
	}

	private function handle_register(): void {
		if ( ! isset( $_POST['_rks_register_nonce'] ) || ! wp_verify_nonce( wp_unslash( (string) $_POST['_rks_register_nonce'] ), self::NONCE_REGISTER ) ) {
			$this->redirect_back( home_url( '/register/' ), 'nonce' );
		}

		$first   = sanitize_text_field( wp_unslash( (string) ( $_POST['first_name']     ?? '' ) ) );
		$last    = sanitize_text_field( wp_unslash( (string) ( $_POST['last_name']      ?? '' ) ) );
		$email   = sanitize_email(      wp_unslash( (string) ( $_POST['user_email']     ?? '' ) ) );
		$login   = sanitize_user(       wp_unslash( (string) ( $_POST['user_login']     ?? '' ) ), true );
		$pass    = (string) ( $_POST['user_password']  ?? '' );
		$pass2   = (string) ( $_POST['user_password2'] ?? '' );
		$terms   = ! empty( $_POST['terms'] );

		$old_b64 = base64_encode( (string) wp_json_encode( [ 'first_name' => $first, 'last_name' => $last, 'user_email' => $email, 'user_login' => $login ] ) );
		$back    = home_url( '/register/?rks_old=' . rawurlencode( $old_b64 ) );

		if ( ! $terms )                                        $this->redirect_back( $back, 'terms' );
		if ( ! is_email( $email ) )                            $this->redirect_back( $back, 'invalid_email' );
		if ( ! preg_match( '/^[A-Za-z0-9._-]{3,30}$/', $login ) ) $this->redirect_back( $back, 'invalid_username' );
		if ( strlen( $pass ) < 8 )                             $this->redirect_back( $back, 'weak_password' );
		if ( $pass !== $pass2 )                                $this->redirect_back( $back, 'mismatch' );
		if ( username_exists( $login ) )                       $this->redirect_back( $back, 'username_taken' );
		if ( email_exists( $email ) )                          $this->redirect_back( $back, 'email_taken' );

		$user_id = wp_create_user( $login, $pass, $email );
		if ( is_wp_error( $user_id ) ) {
			$this->redirect_back( $back, 'generic' );
		}

		wp_update_user( [
			'ID'           => $user_id,
			'first_name'   => $first,
			'last_name'    => $last,
			'display_name' => trim( $first . ' ' . $last ) ?: $login,
		] );
		( get_user_by( 'id', $user_id ) )->set_role( Roles::STUDENT );

		// Auto sign-in after register
		wp_set_auth_cookie( $user_id, false, is_ssl() );
		wp_set_current_user( $user_id );

		$user = get_userdata( $user_id );
		wp_safe_redirect( $this->dashboard_url_for_user( $user ) );
		exit;
	}

	private function handle_lostpassword(): void {
		if ( ! isset( $_POST['_rks_lostpw_nonce'] ) || ! wp_verify_nonce( wp_unslash( (string) $_POST['_rks_lostpw_nonce'] ), self::NONCE_LOSTPW ) ) {
			$this->redirect_back( home_url( '/forgot-password/' ), 'nonce' );
		}

		$id_or_email = sanitize_text_field( wp_unslash( (string) ( $_POST['user_login'] ?? '' ) ) );
		$user = is_email( $id_or_email ) ? get_user_by( 'email', $id_or_email ) : get_user_by( 'login', $id_or_email );

		if ( ! $user ) {
			// Privacy: pretend success to prevent enumeration
			wp_safe_redirect( home_url( '/forgot-password/?rks_msg=reset_sent' ) );
			exit;
		}

		$key = get_password_reset_key( $user );
		if ( is_wp_error( $key ) ) {
			$this->redirect_back( home_url( '/forgot-password/' ), 'reset_failed' );
		}

		$reset_url = add_query_arg(
			[ 'key' => $key, 'login' => rawurlencode( $user->user_login ) ],
			home_url( '/reset-password/' )
		);

		$site = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		$subj = sprintf( '[%s] Password Reset', $site );
		$body = "Hi {$user->display_name},\n\n";
		$body .= "We received a request to reset your password. Click the link below to set a new one:\n\n";
		$body .= $reset_url . "\n\n";
		$body .= "If you didn't request this, ignore this email.\n\n";
		$body .= "— $site";

		wp_mail( $user->user_email, $subj, $body );

		wp_safe_redirect( home_url( '/forgot-password/?rks_msg=reset_sent' ) );
		exit;
	}

	private function handle_resetpassword(): void {
		if ( ! isset( $_POST['_rks_resetpw_nonce'] ) || ! wp_verify_nonce( wp_unslash( (string) $_POST['_rks_resetpw_nonce'] ), 'rks_resetpassword' ) ) {
			$this->redirect_back( home_url( '/reset-password/' ), 'nonce' );
		}

		$key   = sanitize_text_field( wp_unslash( (string) ( $_POST['key']   ?? '' ) ) );
		$login = sanitize_user(       wp_unslash( (string) ( $_POST['login'] ?? '' ) ) );
		$pass  = (string) ( $_POST['user_password']  ?? '' );
		$pass2 = (string) ( $_POST['user_password2'] ?? '' );

		$user = check_password_reset_key( $key, $login );
		if ( is_wp_error( $user ) ) {
			wp_safe_redirect( home_url( '/forgot-password/?rks_err=generic' ) );
			exit;
		}
		if ( strlen( $pass ) < 8 )    $this->redirect_back( add_query_arg( [ 'key'=>$key,'login'=>$login ], home_url( '/reset-password/' ) ), 'weak_password' );
		if ( $pass !== $pass2 )       $this->redirect_back( add_query_arg( [ 'key'=>$key,'login'=>$login ], home_url( '/reset-password/' ) ), 'mismatch' );

		reset_password( $user, $pass );
		wp_safe_redirect( home_url( '/login/?rks_msg=reset_done' ) );
		exit;
	}

	/* ================================================================== */
	/* HELPERS                                                             */
	/* ================================================================== */

	public function dashboard_url_for_user( \WP_User $user ): string {
		if ( in_array( Roles::STUDENT, (array) $user->roles, true ) && ! $this->is_admin_role( $user ) ) {
			return home_url( '/student-dashboard/' );
		}
		if ( in_array( Roles::BRANCH_ADMIN, (array) $user->roles, true ) ) {
			return home_url( '/branch-dashboard/' );
		}
		if ( $this->is_admin_role( $user ) ) {
			return admin_url();
		}
		return home_url( '/' );
	}

	private function is_admin_role( \WP_User $user ): bool {
		$r = (array) $user->roles;
		return in_array( 'administrator', $r, true ) || in_array( Roles::SUPER_ADMIN, $r, true );
	}

	public function role_based_redirect( $redirect_to, $requested_redirect_to, $user ) {
		if ( $user instanceof \WP_User && $user->ID > 0 ) {
			return $this->dashboard_url_for_user( $user );
		}
		return $redirect_to;
	}

	public function maybe_hide_admin_bar(): void {
		if ( ! is_user_logged_in() ) return;
		$user = wp_get_current_user();
		if ( ! $this->is_admin_role( $user ) ) {
			show_admin_bar( false );
		}
	}

	public function maybe_redirect_wp_login(): void {
		// Only redirect on the actual login page action (not logout / lostpassword / resetpass)
		if ( is_admin() ) return;
		$action = isset( $_REQUEST['action'] ) ? sanitize_key( (string) $_REQUEST['action'] ) : 'login';
		if ( 'login' !== $action ) return;
		if ( $_SERVER['REQUEST_METHOD'] !== 'GET' ) return;
		if ( apply_filters( 'rks_skip_wp_login_redirect', false ) ) return;

		// Allow if user is logging in to wp-admin specifically
		$redirect_to = isset( $_REQUEST['redirect_to'] ) ? esc_url_raw( wp_unslash( (string) $_REQUEST['redirect_to'] ) ) : '';
		if ( $redirect_to && 0 === strpos( $redirect_to, admin_url() ) ) return;

		wp_safe_redirect( home_url( '/login/' ) );
		exit;
	}

	public function block_student_admin_access(): void {
		if ( ! is_user_logged_in() ) return;
		if ( wp_doing_ajax() ) return;
		global $pagenow;
		if ( 'admin-post.php' === $pagenow ) return;

		$user = wp_get_current_user();
		if ( in_array( Roles::STUDENT, (array) $user->roles, true ) && ! $this->is_admin_role( $user ) ) {
			wp_safe_redirect( home_url( '/student-dashboard/' ) );
			exit;
		}
	}

	private function is_rate_limited(): bool {
		$key = self::RATE_LIMIT_PREFIX . md5( $this->client_ip() );
		$count = (int) get_transient( $key );
		return $count >= self::RATE_LIMIT_MAX;
	}

	private function bump_rate_limit(): void {
		$key = self::RATE_LIMIT_PREFIX . md5( $this->client_ip() );
		$count = (int) get_transient( $key );
		set_transient( $key, $count + 1, self::RATE_LIMIT_WINDOW );
	}

	private function reset_rate_limit(): void {
		$key = self::RATE_LIMIT_PREFIX . md5( $this->client_ip() );
		delete_transient( $key );
	}

	private function client_ip(): string {
		foreach ( [ 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR' ] as $h ) {
			if ( ! empty( $_SERVER[ $h ] ) ) {
				$ip = (string) $_SERVER[ $h ];
				$ip = trim( explode( ',', $ip )[0] );
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}
		return '0.0.0.0';
	}

	private function redirect_back( string $url, string $error_code ): void {
		wp_safe_redirect( add_query_arg( 'rks_err', $error_code, $url ) );
		exit;
	}
}
