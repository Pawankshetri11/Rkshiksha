<?php
/**
 * Settings page — UPI QR upload, branding, system config, universities.
 *
 * @package RKSiksha\Core
 */

declare( strict_types=1 );

namespace RKSiksha\Core\Admin;

use RKSiksha\Core\Admin\Menu;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Settings {

	public const PAGE_SLUG  = 'rks-settings';
	public const OPTION_KEY = 'rks_settings';
	public const NONCE      = 'rks_save_settings';

	public static function get_default_universities(): array {
		return [
			[
				'name'        => 'Maya Devi University',
				'badge'       => 'MDU',
				'tag'         => 'Private University',
				'location'    => 'Selaqui, Dehradun',
				'recognition' => 'UGC Approved',
				'color'       => 'linear-gradient(135deg,#6c3fc5,#a855f7)',
			],
			[
				'name'        => 'Dev Bhoomi Uttarakhand University',
				'badge'       => 'DBUU',
				'tag'         => 'Private University',
				'location'    => 'Manduwala, Dehradun',
				'recognition' => 'UGC, AICTE, PCI',
				'color'       => 'linear-gradient(135deg,#1a7f5a,#34d399)',
			],
			[
				'name'        => 'JB Institute of Technology',
				'badge'       => 'JBIT',
				'tag'         => 'Institute',
				'location'    => 'Dehradun, Uttarakhand',
				'recognition' => 'AICTE Approved',
				'color'       => 'linear-gradient(135deg,#c94f1a,#f97316)',
			],
			[
				'name'        => 'Shri Guru Ram Rai University',
				'badge'       => 'SGRR',
				'tag'         => 'Private University',
				'location'    => 'Patel Nagar, Dehradun',
				'recognition' => 'UGC, NMC, AYUSH',
				'color'       => 'linear-gradient(135deg,#1a5fa8,#38bdf8)',
			],
			[
				'name'        => 'Uttaranchal University',
				'badge'       => 'UU',
				'tag'         => 'Private University',
				'location'    => 'Arcadia Grant, Dehradun',
				'recognition' => 'UGC, BCI, PCI, AICTE',
				'color'       => 'linear-gradient(135deg,#a8391a,#ef4444)',
			],
			[
				'name'        => 'Graphic Era (Deemed to be University)',
				'badge'       => 'GEU',
				'tag'         => 'Deemed University',
				'location'    => 'Clement Town, Dehradun',
				'recognition' => 'UGC Recognised',
				'color'       => 'linear-gradient(135deg,#2c7a7a,#22d3ee)',
			],
		];
	}

	/** Predefined card gradients shown as swatches in the Universities tab. */
	public static function get_preset_gradients(): array {
		return [
			'linear-gradient(135deg,#6c3fc5,#a855f7)' => 'Purple Amethyst',
			'linear-gradient(135deg,#1a5fa8,#38bdf8)' => 'Ocean Blue',
			'linear-gradient(135deg,#1a7f5a,#34d399)' => 'Emerald Green',
			'linear-gradient(135deg,#c94f1a,#f97316)' => 'Sunrise Orange',
			'linear-gradient(135deg,#a8391a,#ef4444)' => 'Deep Red',
			'linear-gradient(135deg,#2c7a7a,#22d3ee)' => 'Teal Aqua',
			'linear-gradient(135deg,#7c3d8c,#f472b6)' => 'Pink Violet',
			'linear-gradient(135deg,#1e3a5f,#6366f1)' => 'Midnight Indigo',
			'linear-gradient(135deg,#854d0e,#facc15)' => 'Golden Dusk',
			'linear-gradient(135deg,#164e63,#0ea5e9)' => 'Navy Sky',
			'linear-gradient(135deg,#365314,#84cc16)' => 'Forest Lime',
			'linear-gradient(135deg,#3b1a6e,#e879f9)' => 'Galaxy Purple',
		];
	}

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'register_menu' ], 20 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_media' ] );
	}

	public function register_menu(): void {
		add_menu_page(
			'Settings',
			'Settings',
			'rks_manage_settings',
			self::PAGE_SLUG,
			[ $this, 'render' ],
			'dashicons-admin-generic',
			29
		);
	}

	public function enqueue_media( string $hook ): void {
		if ( strpos( $hook, self::PAGE_SLUG ) === false ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_script(
			'rks-admin-settings',
			RKS_PLUGIN_URL . 'assets/js/admin-settings.js',
			[ 'jquery' ],
			RKS_VERSION,
			true
		);
	}

	public function render(): void {
		if ( ! current_user_can( 'rks_manage_settings' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'rksiksha' ) );
		}

		$saved   = false;
		$current = (array) get_option( self::OPTION_KEY, [] );

		if ( isset( $_POST['rks_settings_submit'] ) && check_admin_referer( self::NONCE ) ) {
			$current = $this->save_from_post( $current );
			$saved   = true;
		}

		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( (string) $_GET['tab'] ) ) : 'branding';
		$tabs = [
			'branding'     => 'Branding',
			'payments'     => 'Payments',
			'system'       => 'System',
			'universities' => 'Universities',
		];

		$qr_id  = (int) ( $current['upi_qr_image_id'] ?? 0 );
		$qr_url = $qr_id ? wp_get_attachment_image_url( $qr_id, 'medium' ) : '';

		$preset_gradients = self::get_preset_gradients();
		?>
		<div class="wrap rks-admin-wrap">
			<header class="rks-page-hero">
				<div class="rks-page-hero__main">
					<div class="rks-page-hero__icon"><?php echo \RKSiksha\Core\Support\Icons::svg( 'cog', 24 ); ?></div>
					<div>
						<small>System Configuration</small>
						<h1>Settings</h1>
						<p>Branding, UPI payment QR, certificate prefix, universities — all system-wide preferences.</p>
					</div>
				</div>
			</header>

			<?php if ( $saved ) : ?>
				<div class="notice notice-success is-dismissible"><p>✓ Settings saved successfully.</p></div>
			<?php endif; ?>

			<!-- Tabbed Nav -->
			<nav class="nav-tab-wrapper" style="margin-bottom:0;">
				<?php foreach ( $tabs as $slug => $label ) :
					$url = add_query_arg( [ 'page' => self::PAGE_SLUG, 'tab' => $slug ], admin_url( 'admin.php' ) );
					$cls = $active_tab === $slug ? 'nav-tab nav-tab-active' : 'nav-tab';
				?>
					<a href="<?php echo esc_url( $url ); ?>" class="<?php echo esc_attr( $cls ); ?>"><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</nav>

			<form method="post" class="rks-settings-form" style="background:#fff;border:1px solid #c3c4c7;border-top:none;padding:24px 20px;max-width:1100px;">
				<?php wp_nonce_field( self::NONCE ); ?>
				<input type="hidden" name="rks_settings_tab" value="<?php echo esc_attr( $active_tab ); ?>">

				<?php /* ===== TAB: BRANDING ===== */ if ( $active_tab === 'branding' ) : ?>
				<h2 style="margin-top:0;">Branding</h2>
				<table class="form-table" role="presentation">
					<tr>
						<th><label for="rks_site_brand">Site Brand</label></th>
						<td><input type="text" id="rks_site_brand" name="site_brand" class="regular-text" value="<?php echo esc_attr( (string) ( $current['site_brand'] ?? 'Rishikul Shiksha' ) ); ?>"></td>
					</tr>
					<tr>
						<th><label for="rks_support_email">Support Email</label></th>
						<td><input type="email" id="rks_support_email" name="support_email" class="regular-text" value="<?php echo esc_attr( (string) ( $current['support_email'] ?? '' ) ); ?>"></td>
					</tr>
					<tr>
						<th><label for="rks_support_phone">Support Phone</label></th>
						<td><input type="text" id="rks_support_phone" name="support_phone" class="regular-text" value="<?php echo esc_attr( (string) ( $current['support_phone'] ?? '' ) ); ?>"></td>
					</tr>
				</table>

				<h2>Franchise Brochure</h2>
				<p class="description">Upload a PDF that visitors can download from the Franchise page brochure strip.</p>
				<?php
				$brochure_id   = (int) ( $current['franchise_brochure_id'] ?? 0 );
				$brochure_url  = $brochure_id ? wp_get_attachment_url( $brochure_id ) : '';
				$brochure_name = $brochure_id ? basename( (string) get_attached_file( $brochure_id ) ) : '';
				?>
				<table class="form-table" role="presentation">
					<tr>
						<th>Brochure PDF</th>
						<td>
							<div class="rks-broch-wrap">
								<input type="hidden" id="rks_brochure_id" name="franchise_brochure_id" value="<?php echo (int) $brochure_id; ?>">
								<div id="rks_brochure_meta" style="margin-bottom:10px;<?php echo $brochure_url ? '' : 'display:none;'; ?>">
									<strong id="rks_brochure_name"><?php echo esc_html( $brochure_name ); ?></strong>
									&nbsp;&middot;&nbsp;
									<a id="rks_brochure_link" href="<?php echo esc_url( $brochure_url ); ?>" target="_blank" rel="noopener">View file</a>
								</div>
								<button type="button" class="button" id="rks_brochure_pick">Select / Upload Brochure</button>
								<button type="button" class="button button-link-delete" id="rks_brochure_remove" <?php echo $brochure_id ? '' : 'style="display:none;"'; ?>>Remove</button>
								<p class="description">Recommended: PDF, &lt; 5MB. Used by the "Download Brochure" button on the Franchise page.</p>
							</div>
						</td>
					</tr>
				</table>

				<?php /* ===== TAB: PAYMENTS ===== */ elseif ( $active_tab === 'payments' ) : ?>
				<h2 style="margin-top:0;">UPI Payment QR</h2>
				<table class="form-table" role="presentation">
					<tr>
						<th><label for="rks_upi_id">UPI ID</label></th>
						<td>
							<input type="text" id="rks_upi_id" name="upi_id" class="regular-text" value="<?php echo esc_attr( (string) ( $current['upi_id'] ?? '' ) ); ?>" placeholder="example@oksbi">
							<p class="description">Used as fallback if QR is not scannable.</p>
						</td>
					</tr>
					<tr>
						<th>UPI QR Image</th>
						<td>
							<div class="rks-upi-qr-wrap">
								<input type="hidden" id="rks_upi_qr_image_id" name="upi_qr_image_id" value="<?php echo (int) $qr_id; ?>">
								<img id="rks_upi_qr_preview" src="<?php echo esc_url( $qr_url ); ?>" style="max-width:240px;<?php echo $qr_url ? '' : 'display:none;'; ?>" alt="UPI QR">
								<p>
									<button type="button" class="button" id="rks_upi_qr_pick">Select / Upload QR Image</button>
									<button type="button" class="button button-link-delete" id="rks_upi_qr_remove" <?php echo $qr_id ? '' : 'style="display:none;"'; ?>>Remove</button>
								</p>
							</div>
						</td>
					</tr>
				</table>

				<h2>Payment Gateways</h2>
				<p class="description">Enable one or more payment methods. Razorpay/Cashfree auto-verify; UPI Manual requires admin to verify UTR + screenshot.</p>
				<?php
				$enabled_gateways = (array) ( $current['payment_gateways_enabled'] ?? [ 'upi' ] );
				$cf_env           = (string) ( $current['cashfree_env'] ?? 'sandbox' );
				?>
				<table class="form-table" role="presentation">
					<tr>
						<th>Enabled Methods</th>
						<td>
							<label style="display:block;margin-bottom:6px;">
								<input type="checkbox" name="payment_gateways_enabled[]" value="razorpay" <?php checked( in_array( 'razorpay', $enabled_gateways, true ) ); ?>>
								Razorpay (auto-verify)
							</label>
							<label style="display:block;margin-bottom:6px;">
								<input type="checkbox" name="payment_gateways_enabled[]" value="cashfree" <?php checked( in_array( 'cashfree', $enabled_gateways, true ) ); ?>>
								Cashfree (auto-verify)
							</label>
							<label style="display:block;margin-bottom:6px;">
								<input type="checkbox" name="payment_gateways_enabled[]" value="upi" <?php checked( in_array( 'upi', $enabled_gateways, true ) ); ?>>
								UPI QR (manual screenshot + UTR)
							</label>
							<label style="display:block;margin-bottom:6px;">
								<input type="checkbox" name="payment_gateways_enabled[]" value="offline" <?php checked( in_array( 'offline', $enabled_gateways, true ) ); ?>>
								Offline Payment (requires admin approval)
							</label>
						</td>
					</tr>
					<tr>
						<th><label for="rks_razorpay_key_id">Razorpay Key ID</label></th>
						<td><input type="text" id="rks_razorpay_key_id" name="razorpay_key_id" class="regular-text" value="<?php echo esc_attr( (string) ( $current['razorpay_key_id'] ?? '' ) ); ?>" placeholder="rzp_test_xxxxx"></td>
					</tr>
					<tr>
						<th><label for="rks_razorpay_key_secret">Razorpay Key Secret</label></th>
						<td><input type="password" id="rks_razorpay_key_secret" name="razorpay_key_secret" class="regular-text" value="<?php echo esc_attr( (string) ( $current['razorpay_key_secret'] ?? '' ) ); ?>" autocomplete="new-password"></td>
					</tr>
					<tr>
						<th><label for="rks_cashfree_app_id">Cashfree App ID</label></th>
						<td><input type="text" id="rks_cashfree_app_id" name="cashfree_app_id" class="regular-text" value="<?php echo esc_attr( (string) ( $current['cashfree_app_id'] ?? '' ) ); ?>"></td>
					</tr>
					<tr>
						<th><label for="rks_cashfree_secret_key">Cashfree Secret Key</label></th>
						<td><input type="password" id="rks_cashfree_secret_key" name="cashfree_secret_key" class="regular-text" value="<?php echo esc_attr( (string) ( $current['cashfree_secret_key'] ?? '' ) ); ?>" autocomplete="new-password"></td>
					</tr>
					<tr>
						<th><label for="rks_cashfree_env">Cashfree Environment</label></th>
						<td>
							<select id="rks_cashfree_env" name="cashfree_env">
								<option value="sandbox"    <?php selected( $cf_env, 'sandbox' ); ?>>Sandbox (testing)</option>
								<option value="production" <?php selected( $cf_env, 'production' ); ?>>Production (live)</option>
							</select>
						</td>
					</tr>
				</table>

				<?php /* ===== TAB: SYSTEM ===== */ elseif ( $active_tab === 'system' ) : ?>
				<h2 style="margin-top:0;">Identifier Prefixes</h2>
				<table class="form-table" role="presentation">
					<tr>
						<th><label for="rks_cert_prefix">Certificate ID Prefix</label></th>
						<td><input type="text" id="rks_cert_prefix" name="cert_prefix" class="regular-text" value="<?php echo esc_attr( (string) ( $current['cert_prefix'] ?? 'RKS-CERT' ) ); ?>"></td>
					</tr>
					<tr>
						<th><label for="rks_idcard_prefix">ID Card Prefix</label></th>
						<td><input type="text" id="rks_idcard_prefix" name="idcard_prefix" class="regular-text" value="<?php echo esc_attr( (string) ( $current['idcard_prefix'] ?? 'RKS-ID' ) ); ?>"></td>
					</tr>
					<tr>
						<th><label for="rks_currency_symbol">Currency Symbol</label></th>
						<td><input type="text" id="rks_currency_symbol" name="currency_symbol" class="small-text" value="<?php echo esc_attr( (string) ( $current['currency_symbol'] ?? '₹' ) ); ?>"></td>
					</tr>
				</table>

				<h2>Late Payment Penalty / Fee Setup</h2>
				<table class="form-table" role="presentation">
					<tr>
						<th><label for="rks_late_fee_grace_days">Grace Period (Days)</label></th>
						<td>
							<input type="number" id="rks_late_fee_grace_days" name="late_fee_grace_days" class="small-text" min="0" step="1" value="<?php echo esc_attr( (string) ( $current['late_fee_grace_days'] ?? 0 ) ); ?>">
							<p class="description">Days after the due date before penalty starts.</p>
						</td>
					</tr>
					<tr>
						<th><label for="rks_late_fee_type">Penalty Type</label></th>
						<td>
							<select id="rks_late_fee_type" name="late_fee_type">
								<option value="fixed" <?php selected( ( $current['late_fee_type'] ?? 'fixed' ), 'fixed' ); ?>>Fixed / One-time</option>
								<option value="per_day" <?php selected( ( $current['late_fee_type'] ?? 'fixed' ), 'per_day' ); ?>>Per Day</option>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="rks_late_fee_amount">Penalty Amount</label></th>
						<td>
							<input type="number" id="rks_late_fee_amount" name="late_fee_amount" class="regular-text" min="0" step="any" value="<?php echo esc_attr( (string) ( $current['late_fee_amount'] ?? 0 ) ); ?>">
							<p class="description">The penalty amount to apply (e.g. ₹100 fixed, or ₹10 per day).</p>
						</td>
					</tr>
				</table>

				<?php /* ===== TAB: UNIVERSITIES ===== */ elseif ( $active_tab === 'universities' ) : ?>
				<h2 style="margin-top:0;">Partner Universities</h2>
				<p class="description">Manage universities shown on the counselling page. Choose a <strong>card gradient</strong> by clicking one of the preset swatches directly on each university row.</p>

				<table class="widefat striped" id="rks-universities-table" style="margin-top:14px;">
					<thead>
						<tr>
							<th style="width:28px;">#</th>
							<th>Name *</th>
							<th style="width:64px;">Badge</th>
							<th style="width:120px;">Type/Tag</th>
							<th>Location</th>
							<th>Recognition</th>
							<th style="width:220px;">Card Gradient</th>
							<th style="width:64px;">Actions</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$universities = isset( $current['universities'] ) ? (array) $current['universities'] : self::get_default_universities();
						$row_idx = 0;
						foreach ( $universities as $u ) :
							$row_idx++;
							$card_color = ! empty( $u['color'] ) ? (string) $u['color'] : 'linear-gradient(135deg,#6c3fc5,#a855f7)';
							if ( str_starts_with( $card_color, '#' ) ) {
								$card_color = 'linear-gradient(135deg,' . $card_color . ',' . $card_color . 'cc)';
							}
							$uid = 'rks_univ_' . $row_idx;
						?>
							<tr class="rks-univ-row">
								<td style="color:#999;font-size:12px;vertical-align:top;padding-top:12px;"><?php echo (int) $row_idx; ?></td>
								<td><input type="text" name="univ_name[]" value="<?php echo esc_attr( $u['name'] ); ?>" class="regular-text" required style="width:100%;"></td>
								<td><input type="text" name="univ_badge[]" value="<?php echo esc_attr( $u['badge'] ); ?>" class="small-text" style="width:100%;"></td>
								<td><input type="text" name="univ_tag[]" value="<?php echo esc_attr( $u['tag'] ); ?>" class="regular-text" style="width:100%;"></td>
								<td><input type="text" name="univ_location[]" value="<?php echo esc_attr( $u['location'] ); ?>" class="regular-text" style="width:100%;"></td>
								<td><input type="text" name="univ_recognition[]" value="<?php echo esc_attr( $u['recognition'] ); ?>" class="regular-text" style="width:100%;"></td>
								<td>
									<!-- Hidden field for color value to preserve order across rows -->
									<input type="hidden" name="univ_color[]" class="rks-univ-color-input" value="<?php echo esc_attr( $card_color ); ?>">
									<!-- Preset gradient radio swatches (inline per row) -->
									<div style="display:flex;flex-wrap:wrap;gap:6px;align-items:center;">
										<?php foreach ( $preset_gradients as $grad => $grad_label ) :
											$is_checked = ( $grad === $card_color );
											$radio_id   = esc_attr( $uid . '_' . md5( $grad ) );
										?>
										<label title="<?php echo esc_attr( $grad_label ); ?>" style="cursor:pointer;">
											<input type="radio" name="univ_color_swatch_<?php echo (int) $row_idx; ?>" value="<?php echo esc_attr( $grad ); ?>" id="<?php echo $radio_id; ?>" <?php checked( $is_checked ); ?> style="position:absolute;opacity:0;width:0;height:0;">
											<span class="rks-preset-swatch <?php echo $is_checked ? 'is-selected' : ''; ?>" style="display:block;width:28px;height:28px;border-radius:6px;background:<?php echo esc_attr( $grad ); ?>;border:3px solid <?php echo $is_checked ? '#2271b1' : 'transparent'; ?>;box-shadow:0 1px 4px rgba(0,0,0,.2);transition:border-color .12s,transform .12s;"></span>
										</label>
										<?php endforeach; ?>
									</div>
									<small style="display:block;margin-top:5px;color:#888;font-size:11px;"><?php echo esc_html( $preset_gradients[ $card_color ] ?? 'Custom' ); ?></small>
								</td>
								<td><button type="button" class="button button-link-delete rks-remove-university-row">Remove</button></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<p style="margin-top:12px;">
					<button type="button" class="button button-primary" id="rks-add-university-row">Add University</button>
				</p>

				<?php endif; ?>

				<p class="submit" style="border-top:1px solid #e0e0e0;padding-top:18px;margin-top:4px;">
					<button type="submit" name="rks_settings_submit" class="button button-primary button-hero">Save Settings</button>
				</p>
			</form>
		</div>
		<?php
	}

	private function save_from_post( array $current ): array {
		$fields = [
			'site_brand'      => 'sanitize_text_field',
			'support_email'   => 'sanitize_email',
			'support_phone'   => 'sanitize_text_field',
			'upi_id'          => 'sanitize_text_field',
			'cert_prefix'     => 'sanitize_text_field',
			'idcard_prefix'   => 'sanitize_text_field',
			'currency_symbol' => 'sanitize_text_field',
		];
		// Only update scalar fields that are actually present in POST
		foreach ( $fields as $key => $sanitizer ) {
			if ( isset( $_POST[ $key ] ) ) {
				$current[ $key ] = $sanitizer( wp_unslash( (string) $_POST[ $key ] ) );
			}
		}

		// System tab: late payment penalty setup
		if ( isset( $_POST['late_fee_grace_days'] ) ) {
			$current['late_fee_grace_days'] = absint( $_POST['late_fee_grace_days'] );
		}
		if ( isset( $_POST['late_fee_type'] ) ) {
			$type = sanitize_key( wp_unslash( (string) $_POST['late_fee_type'] ) );
			$current['late_fee_type'] = in_array( $type, [ 'fixed', 'per_day' ], true ) ? $type : 'fixed';
		}
		if ( isset( $_POST['late_fee_amount'] ) ) {
			$current['late_fee_amount'] = floatval( wp_unslash( $_POST['late_fee_amount'] ) );
		}

		// Branding tab: franchise brochure
		if ( isset( $_POST['franchise_brochure_id'] ) ) {
			$current['franchise_brochure_id'] = (int) $_POST['franchise_brochure_id'];
		}

		// Payments tab — only update when payment fields are submitted
		if ( isset( $_POST['upi_qr_image_id'] ) ) {
			$current['upi_qr_image_id'] = (int) $_POST['upi_qr_image_id'];
		}
		if ( isset( $_POST['razorpay_key_id'] ) ) {
			$current['razorpay_key_id']     = sanitize_text_field( wp_unslash( (string) $_POST['razorpay_key_id'] ) );
			$current['razorpay_key_secret'] = sanitize_text_field( wp_unslash( (string) ( $_POST['razorpay_key_secret'] ?? '' ) ) );
			$current['cashfree_app_id']     = sanitize_text_field( wp_unslash( (string) ( $_POST['cashfree_app_id'] ?? '' ) ) );
			$current['cashfree_secret_key'] = sanitize_text_field( wp_unslash( (string) ( $_POST['cashfree_secret_key'] ?? '' ) ) );
			$env = sanitize_key( wp_unslash( (string) ( $_POST['cashfree_env'] ?? 'sandbox' ) ) );
			$current['cashfree_env'] = in_array( $env, [ 'sandbox', 'production' ], true ) ? $env : 'sandbox';

			// payment_gateways_enabled[] checkboxes — present only on payments tab
			$raw_gw     = isset( $_POST['payment_gateways_enabled'] ) && is_array( $_POST['payment_gateways_enabled'] )
				? (array) wp_unslash( $_POST['payment_gateways_enabled'] )
				: [];
			$allowed_gw = [ 'razorpay', 'cashfree', 'upi', 'offline' ];
			$gateways   = array_values( array_intersect( $allowed_gw, array_map( 'sanitize_key', $raw_gw ) ) );
			$current['payment_gateways_enabled'] = empty( $gateways ) ? [ 'upi' ] : $gateways;
		}

		// Universities tab — ONLY update when the active tab is universities.
		// If the user saves from another tab, existing universities are preserved as-is.
		if ( isset( $_POST['rks_settings_tab'] ) && 'universities' === $_POST['rks_settings_tab'] ) {
			if ( isset( $_POST['univ_name'] ) && is_array( $_POST['univ_name'] ) ) {
				$names        = array_map( 'sanitize_text_field', wp_unslash( $_POST['univ_name'] ) );
				$badges       = isset( $_POST['univ_badge'] ) && is_array( $_POST['univ_badge'] )       ? array_map( 'sanitize_text_field', wp_unslash( $_POST['univ_badge'] ) )       : [];
				$tags         = isset( $_POST['univ_tag'] ) && is_array( $_POST['univ_tag'] )           ? array_map( 'sanitize_text_field', wp_unslash( $_POST['univ_tag'] ) )         : [];
				$locations    = isset( $_POST['univ_location'] ) && is_array( $_POST['univ_location'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['univ_location'] ) )    : [];
				$recognitions = isset( $_POST['univ_recognition'] ) && is_array( $_POST['univ_recognition'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['univ_recognition'] ) ) : [];
				$raw_colors   = isset( $_POST['univ_color'] ) && is_array( $_POST['univ_color'] )
					? array_map( static fn( $c ) => wp_strip_all_tags( wp_unslash( (string) $c ) ), $_POST['univ_color'] )
					: [];

				$allowed_preset_grads = array_keys( self::get_preset_gradients() );
				$default_grad         = 'linear-gradient(135deg,#6c3fc5,#a855f7)';

				$universities = [];
				for ( $i = 0; $i < count( $names ); $i++ ) {
					if ( '' === trim( (string) $names[ $i ] ) ) {
						continue;
					}
					$raw_color = trim( (string) ( $raw_colors[ $i ] ?? '' ) );
					if ( ! in_array( $raw_color, $allowed_preset_grads, true ) ) {
						$hex       = sanitize_hex_color( $raw_color );
						$raw_color = $hex
							? 'linear-gradient(135deg,' . $hex . ',' . $hex . 'cc)'
							: $default_grad;
					}
					$universities[] = [
						'name'        => trim( (string) $names[ $i ] ),
						'badge'       => trim( (string) ( $badges[ $i ] ?? '' ) ),
						'tag'         => trim( (string) ( $tags[ $i ] ?? '' ) ),
						'location'    => trim( (string) ( $locations[ $i ] ?? '' ) ),
						'recognition' => trim( (string) ( $recognitions[ $i ] ?? '' ) ),
						'color'       => $raw_color,
					];
				}
				$current['universities'] = $universities;
			} else {
				$current['universities'] = [];
			}
		}
		// else: universities unchanged — preserve existing DB data

		update_option( self::OPTION_KEY, $current, true );
		return $current;
	}
}
