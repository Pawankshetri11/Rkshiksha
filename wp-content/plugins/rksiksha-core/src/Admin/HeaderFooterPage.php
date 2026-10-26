<?php
/**
 * Header & Footer Editor — top-level admin menu with two pages
 * (Header Editor, Footer Editor). Includes logo upload (overrides
 * WP core custom logo) and footer branding.
 *
 * Saves under option key 'rks_header_footer'.
 *
 * @package RKSiksha\Core
 */

declare( strict_types=1 );

namespace RKSiksha\Core\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class HeaderFooterPage {

	public const PARENT_SLUG = 'rks-hf';
	public const HEADER_SLUG = 'rks-hf';
	public const FOOTER_SLUG = 'rks-hf-footer';
	public const OPTION_KEY  = 'rks_header_footer';
	public const NONCE       = 'rks_save_header_footer';

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'register_menu' ], 25 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	public function register_menu(): void {
		add_submenu_page(
			Menu::SLUG_WEBSITE,
			'Header Editor',
			'Header',
			'manage_options',
			self::PARENT_SLUG,
			[ $this, 'render_header' ]
		);
		add_submenu_page(
			Menu::SLUG_WEBSITE,
			'Footer Editor',
			'Footer',
			'manage_options',
			self::FOOTER_SLUG,
			[ $this, 'render_footer' ]
		);
	}

	public function enqueue_assets( string $hook ): void {
		if ( strpos( $hook, self::PARENT_SLUG ) === false ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_script( 'jquery' );
		add_action( 'admin_print_footer_scripts', [ $this, 'print_inline_script' ], 99 );
	}

	/* ----- Defaults & getters ----- */
	public static function defaults(): array {
		return [
			'logo_id'         => 0,
			'logo_width'      => 160,
			'logo_footer_id'  => 0,

			'topbar_enabled'  => 0,
			'topbar_phone'    => '+91 89797 75664',
			'topbar_email'    => 'info@rkshiksha.com',
			'topbar_help_url' => '#',
			'topbar_cta_text' => 'Become an Instructor',
			'topbar_cta_url'  => '#',

			'footer_tagline'  => 'Rishikul Shiksha Career Academy — empowering learners across India with quality online & offline education. Learn at your own pace, from anywhere.',
			'footer_address'  => "Brahamdutt Chowk, Barotiwala,\nVikasnagar, Dehradun, UK 248198",
			'footer_phone'    => '+91 89797 75664',
			'footer_email'    => 'info@rkshiksha.com',

			'office_hours_weekday' => '9:00 AM – 7:00 PM',
			'office_hours_weekend' => 'Closed',
			'office_hours_label_w' => 'Mon - Sat',
			'office_hours_label_e' => 'Sunday',

			'social_facebook'  => 'https://www.facebook.com/share/1H6UzYmRHZ/',
			'social_instagram' => 'https://www.instagram.com/ravi.belwal.555',
			'social_youtube'   => 'https://youtube.com/@rkbelwal999',
			'social_whatsapp'  => '918979775664',

			'footer_copyright' => 'Rishikul Shiksha Career Academy. All rights reserved.',

			// WP nav menu assignments (0 = use theme_location fallback)
			'header_menu_id' => 0,
			'footer_menu_id' => 0,

			// Header CTA buttons (Login / Sign Up)
			'cta_login_enabled'  => 1,
			'cta_login_label'    => 'Login',
			'cta_login_url'      => '/login/',
			'cta_signup_enabled' => 1,
			'cta_signup_label'   => 'Sign Up',
			'cta_signup_url'     => '/register/',
		];
	}

	public static function get( string $key, $default = '' ) {
		$opts = (array) get_option( self::OPTION_KEY, [] );
		$opts = array_merge( self::defaults(), $opts );
		return $opts[ $key ] ?? $default;
	}

	public static function all(): array {
		$opts = (array) get_option( self::OPTION_KEY, [] );
		return array_merge( self::defaults(), $opts );
	}

	/* ----- Save helpers ----- */
	private function save_header_fields( array $current ): array {
		$text_fields = [
			'topbar_phone', 'topbar_email', 'topbar_help_url', 'topbar_cta_text', 'topbar_cta_url',
			'cta_login_label', 'cta_login_url',
			'cta_signup_label', 'cta_signup_url',
		];
		foreach ( $text_fields as $f ) {
			$current[ $f ] = sanitize_text_field( wp_unslash( (string) ( $_POST[ $f ] ?? '' ) ) );
		}
		$current['topbar_enabled']     = isset( $_POST['topbar_enabled'] ) ? 1 : 0;
		$current['cta_login_enabled']  = isset( $_POST['cta_login_enabled'] ) ? 1 : 0;
		$current['cta_signup_enabled'] = isset( $_POST['cta_signup_enabled'] ) ? 1 : 0;
		$current['logo_id']            = (int) ( $_POST['logo_id'] ?? 0 );
		$current['logo_width']         = max( 60, min( 400, (int) ( $_POST['logo_width'] ?? 160 ) ) );
		$current['header_menu_id']     = max( 0, (int) ( $_POST['header_menu_id'] ?? 0 ) );
		update_option( self::OPTION_KEY, $current );
		return $current;
	}

	private function save_footer_fields( array $current ): array {
		$text_fields = [
			'footer_phone', 'footer_email',
			'office_hours_weekday', 'office_hours_weekend',
			'office_hours_label_w', 'office_hours_label_e',
			'social_facebook', 'social_instagram', 'social_youtube', 'social_whatsapp',
			'footer_copyright',
		];
		foreach ( $text_fields as $f ) {
			$current[ $f ] = sanitize_text_field( wp_unslash( (string) ( $_POST[ $f ] ?? '' ) ) );
		}
		$current['footer_tagline']  = sanitize_textarea_field( wp_unslash( (string) ( $_POST['footer_tagline'] ?? '' ) ) );
		$current['footer_address']  = sanitize_textarea_field( wp_unslash( (string) ( $_POST['footer_address'] ?? '' ) ) );
		$current['logo_footer_id']  = (int) ( $_POST['logo_footer_id'] ?? 0 );
		$current['footer_menu_id']  = max( 0, (int) ( $_POST['footer_menu_id'] ?? 0 ) );
		update_option( self::OPTION_KEY, $current );
		return $current;
	}

	/** List all registered WP nav menus for dropdowns. */
	private function menu_dropdown_options(): array {
		$menus = wp_get_nav_menus();
		$out   = [];
		if ( $menus && ! is_wp_error( $menus ) ) {
			foreach ( $menus as $m ) {
				$out[ (int) $m->term_id ] = $m->name;
			}
		}
		return $out;
	}

	/* ----- Shared styles ----- */
	private function inline_css(): void {
		?>
		<style>
			.rks-hf-wrap { max-width: 1180px; margin: 18px 20px 40px 0; }
			.rks-hf-tabs { display: flex; gap: 2px; margin: 0 0 22px; border-bottom: 2px solid #e3e9eb; }
			.rks-hf-tab {
				display: inline-flex; align-items: center; gap: 8px;
				padding: 12px 22px; text-decoration: none;
				color: #5a6e76; font-weight: 600; font-size: 14px;
				border: 0; border-bottom: 3px solid transparent;
				margin-bottom: -2px; background: transparent;
				transition: color .15s ease, border-color .15s ease;
			}
			.rks-hf-tab:hover { color: #2C687B; }
			.rks-hf-tab.is-active { color: #2C687B; border-bottom-color: #E63946; }
			.rks-hf-tab .dashicons { font-size: 18px; width: 18px; height: 18px; }

			.rks-hf-hero {
				background: linear-gradient(135deg, #2C687B 0%, #1f4d5b 100%);
				color: #fff; border-radius: 10px; padding: 22px 26px;
				margin-bottom: 22px; display: flex; align-items: center; gap: 18px;
				box-shadow: 0 8px 20px rgba(31, 77, 91, 0.18);
			}
			.rks-hf-hero__icon {
				width: 54px; height: 54px; border-radius: 12px;
				background: rgba(255,255,255,0.14);
				display: inline-flex; align-items: center; justify-content: center;
				color: #8EC9C4; flex-shrink: 0;
			}
			.rks-hf-hero__icon .dashicons { font-size: 28px; width: 28px; height: 28px; }
			.rks-hf-hero h1 { margin: 0 0 2px; color: #fff; font-size: 22px; }
			.rks-hf-hero small { display: block; color: #8EC9C4; font-size: 11px; text-transform: uppercase; letter-spacing: 1.4px; font-weight: 600; margin-bottom: 4px; }
			.rks-hf-hero p { margin: 0; color: rgba(255,255,255,0.85); font-size: 13.5px; }

			.rks-hf-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
			@media (max-width: 1100px) { .rks-hf-grid { grid-template-columns: 1fr; } }
			.rks-hf-card {
				background: #fff; border: 1px solid #e3e9eb; border-radius: 10px;
				padding: 22px 24px;
				box-shadow: 0 2px 6px rgba(44,104,123,0.05);
			}
			.rks-hf-card h2 {
				margin: 0 0 4px; font-size: 16px; color: #2C687B;
				display: flex; align-items: center; gap: 8px;
			}
			.rks-hf-card h2 .dashicons { color: #E63946; font-size: 18px; width: 18px; height: 18px; }
			.rks-hf-card__sub { color: #7a8a90; font-size: 12.5px; margin: 0 0 16px; }
			.rks-hf-card--full { grid-column: 1 / -1; }

			.rks-hf-row { margin-bottom: 14px; }
			.rks-hf-row:last-child { margin-bottom: 0; }
			.rks-hf-row label, .rks-hf-row > strong {
				display: block; font-size: 12.5px; font-weight: 600;
				color: #4a5b62; margin-bottom: 5px;
			}
			.rks-hf-row input[type="text"],
			.rks-hf-row input[type="email"],
			.rks-hf-row input[type="url"],
			.rks-hf-row input[type="number"],
			.rks-hf-row textarea {
				width: 100%; padding: 9px 12px;
				border: 1.5px solid rgba(44,104,123,0.18);
				border-radius: 6px; font-size: 13.5px;
				transition: border-color .15s ease, box-shadow .15s ease;
				box-sizing: border-box;
			}
			.rks-hf-row input:focus, .rks-hf-row textarea:focus {
				outline: 0; border-color: #2C687B;
				box-shadow: 0 0 0 3px rgba(142,201,196,0.25);
			}
			.rks-hf-row .description { font-size: 11.5px; color: #7a8a90; margin-top: 4px; }

			.rks-hf-double { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
			@media (max-width: 600px) { .rks-hf-double { grid-template-columns: 1fr; } }

			.rks-hf-toggle {
				display: flex; align-items: center; gap: 10px;
				padding: 12px 14px; background: #f5f8f9;
				border-radius: 8px; cursor: pointer;
				border: 1px solid #e3e9eb;
			}
			.rks-hf-toggle input { margin: 0; }
			.rks-hf-toggle-label { font-weight: 600; color: #2C687B; font-size: 13.5px; }
			.rks-hf-toggle-hint { font-size: 11.5px; color: #7a8a90; margin-left: auto; }

			.rks-hf-logo-box {
				display: flex; align-items: center; gap: 18px;
				padding: 16px; background: #f8fafa;
				border: 1.5px dashed #cdd9dd; border-radius: 8px;
			}
			.rks-hf-logo-preview {
				width: 140px; height: 80px;
				background: #fff; border-radius: 6px;
				display: flex; align-items: center; justify-content: center;
				overflow: hidden; border: 1px solid #e3e9eb;
				flex-shrink: 0;
			}
			.rks-hf-logo-preview img { max-width: 100%; max-height: 100%; display: block; }
			.rks-hf-logo-preview--dark { background: linear-gradient(135deg, #2C687B, #1f4d5b); }
			.rks-hf-logo-preview.is-empty:after {
				content: 'No logo'; color: #a8b3b9; font-size: 11px;
			}
			.rks-hf-logo-actions { flex: 1; }
			.rks-hf-logo-actions .button { margin-right: 6px; }

			.rks-hf-social-grid {
				display: grid; grid-template-columns: 32px 1fr; gap: 10px;
				align-items: center; margin-bottom: 12px;
			}
			.rks-hf-social-grid:last-child { margin-bottom: 0; }
			.rks-hf-social-icon {
				width: 32px; height: 32px; border-radius: 7px;
				display: inline-flex; align-items: center; justify-content: center;
				color: #fff;
			}
			.rks-hf-social-icon--fb { background: #1877F2; }
			.rks-hf-social-icon--ig { background: linear-gradient(135deg,#feda75,#fa7e1e,#d62976,#962fbf); }
			.rks-hf-social-icon--yt { background: #FF0000; }
			.rks-hf-social-icon--wa { background: #25D366; }
			.rks-hf-social-icon .dashicons { font-size: 16px; width: 16px; height: 16px; }

			.rks-hf-actions {
				display: flex; align-items: center; justify-content: space-between;
				gap: 12px; margin-top: 22px; padding: 16px 22px;
				background: #fff; border: 1px solid #e3e9eb; border-radius: 10px;
				position: sticky; bottom: 0; z-index: 10;
				box-shadow: 0 -6px 16px rgba(0,0,0,0.06);
			}
			.rks-hf-actions .button-primary {
				padding: 8px 22px; font-size: 14px; font-weight: 600;
			}
			.rks-hf-actions .rks-hf-hint-small {
				font-size: 12px; color: #7a8a90;
			}

			.rks-hf-notice {
				padding: 12px 16px; border-radius: 8px;
				border-left: 4px solid #00a32a; background: #f0fff4;
				color: #1f5b30; font-size: 13px; margin-bottom: 18px;
			}
		</style>
		<?php
	}

	public function print_inline_script(): void {
		if ( ! isset( $_GET['page'] ) || strpos( (string) $_GET['page'], self::PARENT_SLUG ) === false ) {
			return;
		}
		?>
		<script>
		(function($){
			'use strict';
			function bindLogoPicker(prefix){
				var $hidden = $('#'+prefix+'_id');
				var $preview = $('#'+prefix+'_preview');
				var $previewImg = $('#'+prefix+'_img');
				var $remove = $('#'+prefix+'_remove');
				var $pick = $('#'+prefix+'_pick');
				if (!$pick.length) return;
				var frame;
				$pick.on('click', function(e){
					e.preventDefault();
					if (frame) { frame.open(); return; }
					frame = wp.media({
						title: 'Select / Upload Logo',
						button: { text: 'Use this image' },
						multiple: false,
						library: { type: 'image' }
					});
					frame.on('select', function(){
						var att = frame.state().get('selection').first().toJSON();
						$hidden.val(att.id);
						var url = att.sizes && att.sizes.medium ? att.sizes.medium.url : att.url;
						$previewImg.attr('src', url).show();
						$preview.removeClass('is-empty');
						$remove.show();
					});
					frame.open();
				});
				$remove.on('click', function(e){
					e.preventDefault();
					$hidden.val(0);
					$previewImg.attr('src','').hide();
					$preview.addClass('is-empty');
					$(this).hide();
				});
			}
			$(function(){
				bindLogoPicker('logo');
				bindLogoPicker('logo_footer');
			});
		})(jQuery);
		</script>
		<?php
	}

	/* ----- Render: HEADER ----- */
	public function render_header(): void {
		if ( ! current_user_can( 'rks_manage_settings' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'rksiksha' ) );
		}
		$saved = false;
		$current = self::all();
		if ( isset( $_POST['rks_hf_header_submit'] ) && check_admin_referer( self::NONCE ) ) {
			$current = $this->save_header_fields( $current );
			$saved = true;
		}
		$this->inline_css();

		$logo_id  = (int) $current['logo_id'];
		$logo_url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
		?>
		<div class="wrap rks-hf-wrap">
			<div class="rks-hf-hero">
				<div class="rks-hf-hero__icon"><span class="dashicons dashicons-admin-customizer"></span></div>
				<div>
					<small>Site Customization</small>
					<h1>Header &amp; Footer Editor</h1>
					<p>Manage your site's branding, topbar, footer details, social links and copyright — all in one place.</p>
				</div>
			</div>

			<div class="rks-hf-tabs">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::HEADER_SLUG ) ); ?>" class="rks-hf-tab is-active"><span class="dashicons dashicons-align-pull-left"></span> Header</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::FOOTER_SLUG ) ); ?>" class="rks-hf-tab"><span class="dashicons dashicons-align-pull-right"></span> Footer</a>
				<a href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>" class="rks-hf-tab" target="_blank" rel="noopener"><span class="dashicons dashicons-menu"></span> Manage Menus <span class="dashicons dashicons-external" style="font-size:13px;width:13px;height:13px;margin-left:2px;"></span></a>
			</div>

			<?php if ( $saved ) : ?>
				<div class="rks-hf-notice"><strong>Saved.</strong> Your header changes are now live.</div>
			<?php endif; ?>

			<form method="post">
				<?php wp_nonce_field( self::NONCE ); ?>

				<div class="rks-hf-grid">

					<!-- LOGO CARD -->
					<div class="rks-hf-card rks-hf-card--full">
						<h2><span class="dashicons dashicons-format-image"></span> Site Logo</h2>
						<p class="rks-hf-card__sub">Upload your brand logo. If empty, the theme falls back to the "Rishikul Shiksha" text mark.</p>

						<div class="rks-hf-logo-box">
							<div id="logo_preview" class="rks-hf-logo-preview <?php echo $logo_url ? '' : 'is-empty'; ?>">
								<img id="logo_img" src="<?php echo esc_url( $logo_url ); ?>" style="<?php echo $logo_url ? '' : 'display:none;'; ?>" alt="Logo">
							</div>
							<input type="hidden" id="logo_id" name="logo_id" value="<?php echo (int) $logo_id; ?>">
							<div class="rks-hf-logo-actions">
								<button type="button" id="logo_pick" class="button button-primary">Select / Upload Logo</button>
								<button type="button" id="logo_remove" class="button button-link-delete" <?php echo $logo_id ? '' : 'style="display:none;"'; ?>>Remove</button>
								<p class="description" style="margin-top:8px;">Recommended size: 300×80 px transparent PNG/SVG. The logo replaces the text mark in the header automatically.</p>
							</div>
						</div>

						<div class="rks-hf-row" style="margin-top:14px;max-width:240px;">
							<label for="logo_width">Logo display width (px)</label>
							<input type="number" id="logo_width" name="logo_width" min="60" max="400" value="<?php echo (int) $current['logo_width']; ?>">
							<p class="description">Height auto-scales to maintain aspect ratio.</p>
						</div>
					</div>

					<!-- TOPBAR ENABLE -->
					<div class="rks-hf-card rks-hf-card--full">
						<h2><span class="dashicons dashicons-megaphone"></span> Top Bar</h2>
						<p class="rks-hf-card__sub">Optional thin strip above the main header. Disable to hide entirely.</p>

						<label class="rks-hf-toggle">
							<input type="checkbox" name="topbar_enabled" value="1" <?php checked( (int) $current['topbar_enabled'], 1 ); ?>>
							<span class="rks-hf-toggle-label">Show top bar on all pages</span>
							<span class="rks-hf-toggle-hint">Currently <?php echo (int) $current['topbar_enabled'] ? 'enabled' : 'disabled'; ?></span>
						</label>
					</div>

					<!-- TOPBAR LEFT -->
					<div class="rks-hf-card">
						<h2><span class="dashicons dashicons-phone"></span> Top Bar — Left side</h2>
						<p class="rks-hf-card__sub">Phone &amp; email shown on the left.</p>

						<div class="rks-hf-row">
							<label for="topbar_phone">Phone</label>
							<input type="text" id="topbar_phone" name="topbar_phone" value="<?php echo esc_attr( $current['topbar_phone'] ); ?>" placeholder="+91 89797 75664">
						</div>
						<div class="rks-hf-row">
							<label for="topbar_email">Email</label>
							<input type="email" id="topbar_email" name="topbar_email" value="<?php echo esc_attr( $current['topbar_email'] ); ?>" placeholder="info@rkshiksha.com">
						</div>
					</div>

					<!-- TOPBAR RIGHT -->
					<div class="rks-hf-card">
						<h2><span class="dashicons dashicons-external"></span> Top Bar — Right side</h2>
						<p class="rks-hf-card__sub">Help link and CTA shown on the right.</p>

						<div class="rks-hf-row">
							<label for="topbar_help_url">Help link URL</label>
							<input type="text" id="topbar_help_url" name="topbar_help_url" value="<?php echo esc_attr( $current['topbar_help_url'] ); ?>" placeholder="#">
						</div>
						<div class="rks-hf-double">
							<div class="rks-hf-row">
								<label for="topbar_cta_text">CTA text</label>
								<input type="text" id="topbar_cta_text" name="topbar_cta_text" value="<?php echo esc_attr( $current['topbar_cta_text'] ); ?>" placeholder="Become an Instructor">
							</div>
							<div class="rks-hf-row">
								<label for="topbar_cta_url">CTA URL</label>
								<input type="text" id="topbar_cta_url" name="topbar_cta_url" value="<?php echo esc_attr( $current['topbar_cta_url'] ); ?>" placeholder="#">
							</div>
						</div>
					</div>

					<!-- HEADER NAVIGATION MENU -->
					<div class="rks-hf-card rks-hf-card--full">
						<h2><span class="dashicons dashicons-menu"></span> Header Navigation Menu</h2>
						<p class="rks-hf-card__sub">Choose which WP menu shows in the header. Manage links from <a href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>" target="_blank" rel="noopener">Appearance &rarr; Menus</a>.</p>

						<div class="rks-hf-row" style="max-width:480px;">
							<label for="header_menu_id">Header Menu</label>
							<?php $menu_opts = $this->menu_dropdown_options(); ?>
							<select id="header_menu_id" name="header_menu_id" style="width:100%;padding:9px 12px;border:1.5px solid rgba(44,104,123,0.18);border-radius:6px;font-size:13.5px;">
								<option value="0">— Use theme's "Primary" location (default) —</option>
								<?php foreach ( $menu_opts as $mid => $mname ) : ?>
									<option value="<?php echo (int) $mid; ?>" <?php selected( (int) $current['header_menu_id'], (int) $mid ); ?>><?php echo esc_html( $mname ); ?></option>
								<?php endforeach; ?>
							</select>
							<p class="description">If unset, theme falls back to the menu assigned to the "Primary Menu" location.</p>
						</div>
					</div>

					<!-- HEADER CTA BUTTONS -->
					<div class="rks-hf-card rks-hf-card--full">
						<h2><span class="dashicons dashicons-admin-users"></span> Header CTA Buttons</h2>
						<p class="rks-hf-card__sub">Login &amp; Sign Up buttons shown on the right side of the header (hidden when user is logged in).</p>

						<div class="rks-hf-double">
							<div>
								<label class="rks-hf-toggle">
									<input type="checkbox" name="cta_login_enabled" value="1" <?php checked( (int) $current['cta_login_enabled'], 1 ); ?>>
									<span class="rks-hf-toggle-label">Show Login button</span>
								</label>
								<div class="rks-hf-row" style="margin-top:12px;">
									<label for="cta_login_label">Login button label</label>
									<input type="text" id="cta_login_label" name="cta_login_label" value="<?php echo esc_attr( $current['cta_login_label'] ); ?>" placeholder="Login">
								</div>
								<div class="rks-hf-row">
									<label for="cta_login_url">Login URL</label>
									<input type="text" id="cta_login_url" name="cta_login_url" value="<?php echo esc_attr( $current['cta_login_url'] ); ?>" placeholder="/login/">
								</div>
							</div>
							<div>
								<label class="rks-hf-toggle">
									<input type="checkbox" name="cta_signup_enabled" value="1" <?php checked( (int) $current['cta_signup_enabled'], 1 ); ?>>
									<span class="rks-hf-toggle-label">Show Sign Up button</span>
								</label>
								<div class="rks-hf-row" style="margin-top:12px;">
									<label for="cta_signup_label">Sign Up button label</label>
									<input type="text" id="cta_signup_label" name="cta_signup_label" value="<?php echo esc_attr( $current['cta_signup_label'] ); ?>" placeholder="Sign Up">
								</div>
								<div class="rks-hf-row">
									<label for="cta_signup_url">Sign Up URL</label>
									<input type="text" id="cta_signup_url" name="cta_signup_url" value="<?php echo esc_attr( $current['cta_signup_url'] ); ?>" placeholder="/register/">
								</div>
							</div>
						</div>
					</div>

				</div>

				<div class="rks-hf-actions">
					<span class="rks-hf-hint-small">Changes apply across the whole site instantly.</span>
					<button type="submit" name="rks_hf_header_submit" class="button button-primary">Save Header Settings</button>
				</div>
			</form>
		</div>
		<?php
	}

	/* ----- Render: FOOTER ----- */
	public function render_footer(): void {
		if ( ! current_user_can( 'rks_manage_settings' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'rksiksha' ) );
		}
		$saved = false;
		$current = self::all();
		if ( isset( $_POST['rks_hf_footer_submit'] ) && check_admin_referer( self::NONCE ) ) {
			$current = $this->save_footer_fields( $current );
			$saved = true;
		}
		$this->inline_css();

		$flogo_id  = (int) $current['logo_footer_id'];
		$flogo_url = $flogo_id ? wp_get_attachment_image_url( $flogo_id, 'medium' ) : '';
		?>
		<div class="wrap rks-hf-wrap">
			<div class="rks-hf-hero">
				<div class="rks-hf-hero__icon"><span class="dashicons dashicons-admin-customizer"></span></div>
				<div>
					<small>Site Customization</small>
					<h1>Header &amp; Footer Editor</h1>
					<p>Manage your site's branding, topbar, footer details, social links and copyright — all in one place.</p>
				</div>
			</div>

			<div class="rks-hf-tabs">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::HEADER_SLUG ) ); ?>" class="rks-hf-tab"><span class="dashicons dashicons-align-pull-left"></span> Header</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::FOOTER_SLUG ) ); ?>" class="rks-hf-tab is-active"><span class="dashicons dashicons-align-pull-right"></span> Footer</a>
				<a href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>" class="rks-hf-tab" target="_blank" rel="noopener"><span class="dashicons dashicons-menu"></span> Manage Menus <span class="dashicons dashicons-external" style="font-size:13px;width:13px;height:13px;margin-left:2px;"></span></a>
			</div>

			<?php if ( $saved ) : ?>
				<div class="rks-hf-notice"><strong>Saved.</strong> Your footer changes are now live.</div>
			<?php endif; ?>

			<form method="post">
				<?php wp_nonce_field( self::NONCE ); ?>

				<div class="rks-hf-grid">

					<!-- FOOTER LOGO -->
					<div class="rks-hf-card rks-hf-card--full">
						<h2><span class="dashicons dashicons-format-image"></span> Footer Logo <span style="font-size:11px;color:#7a8a90;font-weight:400;letter-spacing:0;">(optional)</span></h2>
						<p class="rks-hf-card__sub">Use a light/white-mark logo for the dark footer. If empty, the header logo (or text mark) is shown.</p>

						<div class="rks-hf-logo-box">
							<div id="logo_footer_preview" class="rks-hf-logo-preview rks-hf-logo-preview--dark <?php echo $flogo_url ? '' : 'is-empty'; ?>">
								<img id="logo_footer_img" src="<?php echo esc_url( $flogo_url ); ?>" style="<?php echo $flogo_url ? '' : 'display:none;'; ?>" alt="Footer Logo">
							</div>
							<input type="hidden" id="logo_footer_id" name="logo_footer_id" value="<?php echo (int) $flogo_id; ?>">
							<div class="rks-hf-logo-actions">
								<button type="button" id="logo_footer_pick" class="button button-primary">Select / Upload Footer Logo</button>
								<button type="button" id="logo_footer_remove" class="button button-link-delete" <?php echo $flogo_id ? '' : 'style="display:none;"'; ?>>Remove</button>
								<p class="description" style="margin-top:8px;">Recommended: white/light PNG on transparent background.</p>
							</div>
						</div>
					</div>

					<!-- ABOUT / TAGLINE -->
					<div class="rks-hf-card">
						<h2><span class="dashicons dashicons-info"></span> About / Tagline</h2>
						<p class="rks-hf-card__sub">Short paragraph shown next to the footer logo.</p>
						<div class="rks-hf-row">
							<label for="footer_tagline">Tagline</label>
							<textarea id="footer_tagline" name="footer_tagline" rows="4"><?php echo esc_textarea( $current['footer_tagline'] ); ?></textarea>
						</div>
					</div>

					<!-- ADDRESS -->
					<div class="rks-hf-card">
						<h2><span class="dashicons dashicons-location"></span> Address</h2>
						<p class="rks-hf-card__sub">Use line breaks for multi-line address.</p>
						<div class="rks-hf-row">
							<label for="footer_address">Office Address</label>
							<textarea id="footer_address" name="footer_address" rows="4"><?php echo esc_textarea( $current['footer_address'] ); ?></textarea>
						</div>
					</div>

					<!-- CONTACT -->
					<div class="rks-hf-card">
						<h2><span class="dashicons dashicons-email-alt"></span> Contact</h2>
						<p class="rks-hf-card__sub">Footer phone &amp; email links.</p>
						<div class="rks-hf-row">
							<label for="footer_phone">Phone</label>
							<input type="text" id="footer_phone" name="footer_phone" value="<?php echo esc_attr( $current['footer_phone'] ); ?>">
						</div>
						<div class="rks-hf-row">
							<label for="footer_email">Email</label>
							<input type="email" id="footer_email" name="footer_email" value="<?php echo esc_attr( $current['footer_email'] ); ?>">
						</div>
					</div>

					<!-- OFFICE HOURS -->
					<div class="rks-hf-card">
						<h2><span class="dashicons dashicons-clock"></span> Office Hours</h2>
						<p class="rks-hf-card__sub">Shown in the "Get in Touch" footer column.</p>
						<div class="rks-hf-double">
							<div class="rks-hf-row">
								<label for="office_hours_label_w">Weekday label</label>
								<input type="text" id="office_hours_label_w" name="office_hours_label_w" value="<?php echo esc_attr( $current['office_hours_label_w'] ); ?>">
							</div>
							<div class="rks-hf-row">
								<label for="office_hours_weekday">Weekday hours</label>
								<input type="text" id="office_hours_weekday" name="office_hours_weekday" value="<?php echo esc_attr( $current['office_hours_weekday'] ); ?>">
							</div>
						</div>
						<div class="rks-hf-double">
							<div class="rks-hf-row">
								<label for="office_hours_label_e">Weekend label</label>
								<input type="text" id="office_hours_label_e" name="office_hours_label_e" value="<?php echo esc_attr( $current['office_hours_label_e'] ); ?>">
							</div>
							<div class="rks-hf-row">
								<label for="office_hours_weekend">Weekend hours</label>
								<input type="text" id="office_hours_weekend" name="office_hours_weekend" value="<?php echo esc_attr( $current['office_hours_weekend'] ); ?>">
							</div>
						</div>
					</div>

					<!-- SOCIAL -->
					<div class="rks-hf-card rks-hf-card--full">
						<h2><span class="dashicons dashicons-share"></span> Social Links</h2>
						<p class="rks-hf-card__sub">Links shown in the footer social icons row. Leave blank to hide an icon.</p>

						<div class="rks-hf-double">
							<div class="rks-hf-social-grid">
								<span class="rks-hf-social-icon rks-hf-social-icon--fb"><span class="dashicons dashicons-facebook-alt"></span></span>
								<input type="url" name="social_facebook" placeholder="https://facebook.com/yourpage" value="<?php echo esc_attr( $current['social_facebook'] ); ?>">
							</div>
							<div class="rks-hf-social-grid">
								<span class="rks-hf-social-icon rks-hf-social-icon--ig"><span class="dashicons dashicons-instagram"></span></span>
								<input type="url" name="social_instagram" placeholder="https://instagram.com/yourhandle" value="<?php echo esc_attr( $current['social_instagram'] ); ?>">
							</div>
							<div class="rks-hf-social-grid">
								<span class="rks-hf-social-icon rks-hf-social-icon--yt"><span class="dashicons dashicons-video-alt3"></span></span>
								<input type="url" name="social_youtube" placeholder="https://youtube.com/@yourchannel" value="<?php echo esc_attr( $current['social_youtube'] ); ?>">
							</div>
							<div class="rks-hf-social-grid">
								<span class="rks-hf-social-icon rks-hf-social-icon--wa"><span class="dashicons dashicons-whatsapp"></span></span>
								<input type="text" name="social_whatsapp" placeholder="918979775664" value="<?php echo esc_attr( $current['social_whatsapp'] ); ?>">
							</div>
						</div>
						<p class="description" style="margin-top:6px;">For WhatsApp, enter country code + number with no spaces (e.g. <code>918979775664</code>).</p>
					</div>

					<!-- FOOTER NAVIGATION MENU -->
					<div class="rks-hf-card rks-hf-card--full">
						<h2><span class="dashicons dashicons-menu"></span> Footer Quick Links Menu</h2>
						<p class="rks-hf-card__sub">Choose which WP menu shows under "Quick Links" in the footer. Manage links from <a href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>" target="_blank" rel="noopener">Appearance &rarr; Menus</a>.</p>

						<div class="rks-hf-row" style="max-width:480px;">
							<label for="footer_menu_id">Footer Menu</label>
							<?php $footer_menu_opts = $this->menu_dropdown_options(); ?>
							<select id="footer_menu_id" name="footer_menu_id" style="width:100%;padding:9px 12px;border:1.5px solid rgba(44,104,123,0.18);border-radius:6px;font-size:13.5px;">
								<option value="0">— Use theme's "Footer" location (default) —</option>
								<?php foreach ( $footer_menu_opts as $mid => $mname ) : ?>
									<option value="<?php echo (int) $mid; ?>" <?php selected( (int) $current['footer_menu_id'], (int) $mid ); ?>><?php echo esc_html( $mname ); ?></option>
								<?php endforeach; ?>
							</select>
							<p class="description">If unset, theme falls back to the menu assigned to the "Footer Menu" location.</p>
						</div>
					</div>

					<!-- COPYRIGHT -->
					<div class="rks-hf-card rks-hf-card--full">
						<h2><span class="dashicons dashicons-editor-paste-text"></span> Copyright</h2>
						<p class="rks-hf-card__sub">Year is prepended automatically. e.g. "© <?php echo esc_html( date( 'Y' ) ); ?> [your text]".</p>
						<div class="rks-hf-row">
							<label for="footer_copyright">Copyright text</label>
							<input type="text" id="footer_copyright" name="footer_copyright" value="<?php echo esc_attr( $current['footer_copyright'] ); ?>">
						</div>
					</div>

				</div>

				<div class="rks-hf-actions">
					<span class="rks-hf-hint-small">Changes apply across the whole site instantly.</span>
					<button type="submit" name="rks_hf_footer_submit" class="button button-primary">Save Footer Settings</button>
				</div>
			</form>
		</div>
		<?php
	}

}
