<?php
/**
 * Certificate Designer — admin can upload a certificate background image and
 * visually drag-drop variables (student name, course, date, cert no, QR, etc.)
 * onto it. Designs stored as CPT `rks_cert_design` with field placements
 * in post meta. Also hosts prefix editing + active-design picker.
 *
 * @package RKSiksha\Core
 */

declare( strict_types=1 );

namespace RKSiksha\Core\Admin;

use RKSiksha\Core\Modules\Branches;
use RKSiksha\Core\Modules\Certificate;
use RKSiksha\Core\Support\DB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CertificateDesignerPage {

	public const CPT          = 'rks_cert_design';
	public const SLUG         = 'rks-cert-designer';
	public const SLUG_ISSUED  = 'rks-certificates';
	public const SLUG_OFFLINE = 'rks-cert-offline';
	public const SLUG_PREFIX  = 'rks-cert-prefixes';
	public const NONCE        = 'rks_cert_designer_save';
	public const META_BG      = '_rks_cd_bg_id';
	public const META_BG_URL  = '_rks_cd_bg_url';
	public const META_W       = '_rks_cd_canvas_w';
	public const META_H       = '_rks_cd_canvas_h';
	public const META_FIELDS  = '_rks_cd_fields';
	public const OPT_ACTIVE   = 'rks_cert_active_design';

	/**
	 * Font catalogue for the designer. Each entry:
	 *   key       => CSS font-family stack (saved to DB)
	 *   label     => label shown in dropdown
	 *   group     => Classic | Elegant Serif | Calligraphy / Script | Modern Sans | Display | Monospace
	 *   gf        => Google Fonts family + weight spec (or '' for system fonts)
	 *
	 * Curated specifically for diploma / certificate aesthetics.
	 */
	public static function fonts(): array {
		return [
			// ----- Classic certificate serifs -----
			"'Times New Roman', Times, serif"                   => [ 'label' => 'Times New Roman',     'group' => 'Classic',         'gf' => '' ],
			"Georgia, 'Times New Roman', serif"                 => [ 'label' => 'Georgia',             'group' => 'Classic',         'gf' => '' ],
			"'Playfair Display', Georgia, serif"                => [ 'label' => 'Playfair Display',    'group' => 'Elegant Serif',   'gf' => 'Playfair+Display:wght@400;600;700;900' ],
			"'Cormorant Garamond', Georgia, serif"              => [ 'label' => 'Cormorant Garamond',  'group' => 'Elegant Serif',   'gf' => 'Cormorant+Garamond:wght@400;600;700' ],
			"'EB Garamond', 'Times New Roman', serif"           => [ 'label' => 'EB Garamond',         'group' => 'Elegant Serif',   'gf' => 'EB+Garamond:wght@400;600;700' ],
			"'Cinzel', Georgia, serif"                          => [ 'label' => 'Cinzel (Roman caps)', 'group' => 'Elegant Serif',   'gf' => 'Cinzel:wght@400;600;700;900' ],
			"'Cormorant SC', Georgia, serif"                    => [ 'label' => 'Cormorant Small-Caps','group' => 'Elegant Serif',   'gf' => 'Cormorant+SC:wght@400;600;700' ],
			"'Crimson Text', Georgia, serif"                    => [ 'label' => 'Crimson Text',        'group' => 'Elegant Serif',   'gf' => 'Crimson+Text:wght@400;600;700' ],
			"'Libre Baskerville', Georgia, serif"               => [ 'label' => 'Libre Baskerville',   'group' => 'Elegant Serif',   'gf' => 'Libre+Baskerville:wght@400;700' ],
			"'Lora', Georgia, serif"                            => [ 'label' => 'Lora',                'group' => 'Elegant Serif',   'gf' => 'Lora:wght@400;500;600;700' ],
			"'Cardo', Georgia, serif"                           => [ 'label' => 'Cardo',               'group' => 'Elegant Serif',   'gf' => 'Cardo:wght@400;700' ],

			// ----- Script / Calligraphy (perfect for signatures + headings) -----
			"'Great Vibes', cursive"                            => [ 'label' => 'Great Vibes',         'group' => 'Calligraphy / Script', 'gf' => 'Great+Vibes' ],
			"'Allura', cursive"                                 => [ 'label' => 'Allura',              'group' => 'Calligraphy / Script', 'gf' => 'Allura' ],
			"'Pinyon Script', cursive"                          => [ 'label' => 'Pinyon Script',       'group' => 'Calligraphy / Script', 'gf' => 'Pinyon+Script' ],
			"'Tangerine', cursive"                              => [ 'label' => 'Tangerine',           'group' => 'Calligraphy / Script', 'gf' => 'Tangerine:wght@400;700' ],
			"'Dancing Script', cursive"                         => [ 'label' => 'Dancing Script',      'group' => 'Calligraphy / Script', 'gf' => 'Dancing+Script:wght@400;600;700' ],
			"'Parisienne', cursive"                             => [ 'label' => 'Parisienne',          'group' => 'Calligraphy / Script', 'gf' => 'Parisienne' ],
			"'Sacramento', cursive"                             => [ 'label' => 'Sacramento',          'group' => 'Calligraphy / Script', 'gf' => 'Sacramento' ],
			"'Alex Brush', cursive"                             => [ 'label' => 'Alex Brush',          'group' => 'Calligraphy / Script', 'gf' => 'Alex+Brush' ],
			"'Yellowtail', cursive"                             => [ 'label' => 'Yellowtail',          'group' => 'Calligraphy / Script', 'gf' => 'Yellowtail' ],
			"'Italianno', cursive"                              => [ 'label' => 'Italianno',           'group' => 'Calligraphy / Script', 'gf' => 'Italianno' ],
			"'Pacifico', cursive"                               => [ 'label' => 'Pacifico',            'group' => 'Calligraphy / Script', 'gf' => 'Pacifico' ],

			// ----- Modern sans (body / metadata text) -----
			"'Poppins', 'Helvetica Neue', sans-serif"           => [ 'label' => 'Poppins',             'group' => 'Modern Sans',     'gf' => 'Poppins:wght@400;500;600;700' ],
			"'Montserrat', 'Helvetica Neue', sans-serif"        => [ 'label' => 'Montserrat',          'group' => 'Modern Sans',     'gf' => 'Montserrat:wght@400;500;600;700;900' ],
			"'Open Sans', Arial, sans-serif"                    => [ 'label' => 'Open Sans',           'group' => 'Modern Sans',     'gf' => 'Open+Sans:wght@400;600;700' ],
			"'Inter', 'Helvetica Neue', sans-serif"             => [ 'label' => 'Inter',               'group' => 'Modern Sans',     'gf' => 'Inter:wght@400;500;600;700' ],
			'Arial, Helvetica, sans-serif'                      => [ 'label' => 'Arial',               'group' => 'Modern Sans',     'gf' => '' ],
			"'Helvetica Neue', Helvetica, Arial, sans-serif"    => [ 'label' => 'Helvetica',           'group' => 'Modern Sans',     'gf' => '' ],

			// ----- Display / Decorative (heading flair) -----
			"'Marcellus', Georgia, serif"                       => [ 'label' => 'Marcellus',           'group' => 'Display',         'gf' => 'Marcellus' ],
			"'Cinzel Decorative', Georgia, serif"               => [ 'label' => 'Cinzel Decorative',   'group' => 'Display',         'gf' => 'Cinzel+Decorative:wght@400;700;900' ],
			"'Italiana', Georgia, serif"                        => [ 'label' => 'Italiana',            'group' => 'Display',         'gf' => 'Italiana' ],
			"'Cormorant Upright', Georgia, serif"               => [ 'label' => 'Cormorant Upright',   'group' => 'Display',         'gf' => 'Cormorant+Upright:wght@400;500;600;700' ],
			"'UnifrakturMaguntia', serif"                       => [ 'label' => 'UnifrakturMaguntia',  'group' => 'Display',         'gf' => 'UnifrakturMaguntia' ],
			"'IM Fell English SC', Georgia, serif"              => [ 'label' => 'IM Fell English SC',  'group' => 'Display',         'gf' => 'IM+Fell+English+SC' ],

			// ----- Monospace -----
			'ui-monospace, "SF Mono", Menlo, Consolas, monospace' => [ 'label' => 'Monospace',         'group' => 'Monospace',       'gf' => '' ],
		];
	}

	/**
	 * Build the Google Fonts CSS URL that loads every font in our catalogue.
	 * Single request, lazy-loaded by browser.
	 */
	public static function google_fonts_url(): string {
		$families = [];
		foreach ( self::fonts() as $info ) {
			if ( ! empty( $info['gf'] ) ) {
				$families[] = 'family=' . $info['gf'];
			}
		}
		if ( empty( $families ) ) return '';
		return 'https://fonts.googleapis.com/css2?' . implode( '&', $families ) . '&display=swap';
	}

	/** Available variables that can be dragged onto the canvas. */
	public static function variables(): array {
		return [
			'academy_name' => [ 'label' => 'Academy Name',   'type' => 'text',  'sample' => 'RISHIKUL SHIKSHA CAREER ACADEMY' ],
			'academy_line' => [ 'label' => 'Academy Subtitle','type' => 'text', 'sample' => 'Under Registered Rishikul Shiksha Trust' ],
			'certificate_title' => [ 'label' => 'Certificate Title', 'type' => 'text', 'sample' => 'CERTIFICATE' ],
			'certify_label' => [ 'label' => 'Certify Label', 'type' => 'text', 'sample' => 'This is to certify' ],
			'father_label' => [ 'label' => 'Father Label', 'type' => 'text', 'sample' => "Father's Name" ],
			'certificate_no_label' => [ 'label' => 'Certificate No. Label', 'type' => 'text', 'sample' => 'Certificate No.' ],
			'completion_text' => [ 'label' => 'Completion Text', 'type' => 'text', 'sample' => 'Has successfully completed the course' ],
			'issue_label' => [ 'label' => 'Issue Label', 'type' => 'text', 'sample' => 'Issue Date' ],
			'signature_label' => [ 'label' => 'Signature Label', 'type' => 'text', 'sample' => 'Authorized Signature' ],
			'student_name' => [ 'label' => 'Student Name',   'type' => 'text',  'sample' => 'Aman Negi' ],
			'father_name'  => [ 'label' => "Father's Name",  'type' => 'text',  'sample' => 'Sh. Rakesh Negi' ],
			'course_name'  => [ 'label' => 'Course Name',    'type' => 'text',  'sample' => 'D.C.A. — Diploma in Computer Applications' ],
			'issue_date'   => [ 'label' => 'Issue Date',     'type' => 'text',  'sample' => '14 May 2026' ],
			'cert_no'      => [ 'label' => 'Certificate No', 'type' => 'text',  'sample' => 'RKS-CERT-2026-000123' ],
			'cert_prefix'  => [ 'label' => 'Prefix Only',    'type' => 'text',  'sample' => 'RKS-CERT' ],
			'branch_name'  => [ 'label' => 'Branch Name',    'type' => 'text',  'sample' => 'Dehradun Main Branch' ],
			'verify_url'   => [ 'label' => 'Verify URL',     'type' => 'text',  'sample' => 'https://rkshiksha.com/verify-certificate/?id=...' ],
			'qr_code'      => [ 'label' => 'QR Code',        'type' => 'image', 'sample' => '' ],
			'student_photo' => [ 'label' => 'Student Photo',  'type' => 'image', 'sample' => '' ],
		];
	}

	public function register(): void {
		add_action( 'init',               [ $this, 'register_cpt' ] );
		add_action( 'init',               [ self::class, 'seed_blue_academy_preset' ], 12 );
		add_action( 'admin_menu',         [ $this, 'register_menu' ], 30 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'admin_post_rks_cd_save',           [ $this, 'handle_save' ] );
		add_action( 'admin_post_rks_cd_delete',         [ $this, 'handle_delete' ] );
		add_action( 'admin_post_rks_cd_set_active',     [ $this, 'handle_set_active' ] );
		add_action( 'admin_post_rks_cd_save_prefix',    [ $this, 'handle_save_prefix' ] );
		add_action( 'admin_post_rks_cd_set_prefix',     [ $this, 'handle_set_prefix' ] );
		add_action( 'admin_post_rks_cd_delete_prefix',  [ $this, 'handle_delete_prefix' ] );
		add_action( 'admin_post_rks_cd_save_legacy',    [ $this, 'handle_save_legacy' ] );
		add_action( 'admin_post_rks_cd_status_legacy',  [ $this, 'handle_status_legacy' ] );
		add_action( 'admin_post_rks_cd_delete_legacy',  [ $this, 'handle_delete_legacy' ] );
		add_action( 'admin_post_rks_cd_status_cert',    [ $this, 'handle_status_certificate' ] );
		add_action( 'admin_post_rks_cd_delete_cert',    [ $this, 'handle_delete_certificate' ] );
	}

	public function register_cpt(): void {
		register_post_type( self::CPT, [
			'public'              => false,
			'show_ui'             => false,
			'show_in_menu'        => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'capability_type'     => 'post',
			'supports'            => [ 'title' ],
		] );
	}

	public function register_menu(): void {
		add_menu_page(
			'Certificates',
			'Certificates',
			'manage_options',
			self::SLUG_ISSUED,
			[ $this, 'render_issued' ],
			'dashicons-awards',
			6
		);
		add_submenu_page( self::SLUG_ISSUED, 'Issued Certificates', 'Issued Certificates', 'manage_options', self::SLUG_ISSUED, [ $this, 'render_issued' ] );
		add_submenu_page( self::SLUG_ISSUED, 'Offline Registry', 'Offline Registry', 'manage_options', self::SLUG_OFFLINE, [ $this, 'render_offline' ] );
		add_submenu_page( self::SLUG_ISSUED, 'Prefix Rules', 'Prefix Rules', 'manage_options', self::SLUG_PREFIX, [ $this, 'render_prefixes' ] );
		add_submenu_page( self::SLUG_ISSUED, 'Templates', 'Templates', 'manage_options', self::SLUG, [ $this, 'render' ] );
	}

	public function enqueue_assets( string $hook ): void {
		$certificate_hooks = [ self::SLUG_ISSUED, self::SLUG_OFFLINE, self::SLUG_PREFIX, self::SLUG ];
		$is_cert_hook = false;
		foreach ( $certificate_hooks as $slug ) {
			if ( false !== strpos( $hook, $slug ) ) {
				$is_cert_hook = true;
				break;
			}
		}
		if ( ! $is_cert_hook ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_script( 'jquery' );

		// Load Google Fonts for live preview inside the designer
		$gf_url = self::google_fonts_url();
		if ( $gf_url ) {
			wp_enqueue_style( 'rks-cert-fonts', $gf_url, [], null );
		}
	}

	/* ------------------------------------------------------------------ */
	/* Public getters used by frontend                                     */
	/* ------------------------------------------------------------------ */

	public static function get_active_design(): ?array {
		$id = (int) get_option( self::OPT_ACTIVE, 0 );
		if ( $id <= 0 ) return null;
		$post = get_post( $id );
		if ( ! $post || $post->post_type !== self::CPT || $post->post_status === 'trash' ) return null;
		return self::format_design( $post );
	}

	private static function format_design( \WP_Post $post ): array {
		$bg_id   = (int) get_post_meta( $post->ID, self::META_BG, true );
		$bg_url  = $bg_id ? wp_get_attachment_image_url( $bg_id, 'full' ) : '';
		if ( ! $bg_url ) {
			$bg_url = (string) get_post_meta( $post->ID, self::META_BG_URL, true );
		}
		$fields  = (array) get_post_meta( $post->ID, self::META_FIELDS, true );
		$canvas_w = (int) ( get_post_meta( $post->ID, self::META_W, true ) ?: 1240 );
		$canvas_h = (int) ( get_post_meta( $post->ID, self::META_H, true ) ?: 877 );
		return [
			'id'       => $post->ID,
			'title'    => $post->post_title,
			'bg_id'    => $bg_id,
			'bg_url'   => $bg_url ?: '',
			'canvas_w' => $canvas_w,
			'canvas_h' => $canvas_h,
			'fields'   => array_values( array_filter( $fields, static function ( $f ) {
				return is_array( $f ) && ! empty( $f['key'] );
			} ) ),
		];
	}

	/** Render a compact, data-filled certificate preview for admin template cards. */
	private static function render_design_preview( array $design ): void {
		$canvas_w = max( 100, (int) ( $design['canvas_w'] ?? 1240 ) );
		$canvas_h = max( 100, (int) ( $design['canvas_h'] ?? 877 ) );
		$aspect   = $canvas_w / $canvas_h;
		$vars     = self::variables();
		?>
		<div class="rkscd-mini-preview" style="aspect-ratio:<?php echo esc_attr( (string) $aspect ); ?> / 1;">
			<?php if ( ! empty( $design['bg_url'] ) ) : ?>
				<img class="rkscd-mini-preview__bg" src="<?php echo esc_url( (string) $design['bg_url'] ); ?>" alt="">
			<?php endif; ?>
			<?php foreach ( (array) ( $design['fields'] ?? [] ) as $field ) :
				$key = (string) ( $field['key'] ?? '' );
				if ( ! isset( $vars[ $key ] ) ) continue;
				$is_image = $vars[ $key ]['type'] === 'image';
				$font_cqw = max( 1.15, min( 4.5, ( (float) ( $field['font_size'] ?? 24 ) / $canvas_w ) * 100 ) );
				$style  = 'left:' . max( 0, min( 100, (float) ( $field['x'] ?? 0 ) ) ) . '%;';
				$style .= 'top:' . max( 0, min( 100, (float) ( $field['y'] ?? 0 ) ) ) . '%;';
				$style .= 'width:' . max( 1, min( 100, (float) ( $field['w'] ?? 30 ) ) ) . '%;';
				$style .= 'font-size:clamp(3px,' . $font_cqw . 'cqw,13px);';
				$style .= 'color:' . ( preg_match( '/^#[0-9a-f]{6}$/i', (string) ( $field['color'] ?? '' ) ) ? (string) $field['color'] : '#263238' ) . ';';
				$style .= 'font-family:' . (string) ( $field['font_family'] ?? "'Playfair Display', Georgia, serif" ) . ';';
				$style .= 'font-weight:' . ( ( $field['font_weight'] ?? '' ) === 'bold' ? 'bold' : 'normal' ) . ';';
				$style .= 'font-style:' . ( ( $field['font_style'] ?? '' ) === 'italic' ? 'italic' : 'normal' ) . ';';
				$style .= 'text-align:' . ( in_array( (string) ( $field['align'] ?? '' ), [ 'left', 'center', 'right' ], true ) ? (string) $field['align'] : 'center' ) . ';';
				?>
				<span class="rkscd-mini-preview__field<?php echo $is_image ? ' rkscd-mini-preview__field--media' : ''; ?>" style="<?php echo esc_attr( $style ); ?>">
					<?php if ( $key === 'student_photo' ) : ?>
						<span class="rkscd-mini-preview__photo"><span class="dashicons dashicons-admin-users"></span></span>
					<?php elseif ( $key === 'qr_code' ) : ?>
						<span class="rkscd-mini-preview__qr" aria-label="QR code preview"></span>
					<?php else : ?>
						<?php echo esc_html( (string) ( $vars[ $key ]['sample'] ?: '{' . $key . '}' ) ); ?>
					<?php endif; ?>
				</span>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/** Seed the reference-inspired blue certificate as an optional preset. */
	public static function seed_blue_academy_preset(): void {
		if ( get_option( 'rks_blue_academy_preset_seeded_v1', false ) ) {
			return;
		}

		$id = wp_insert_post( [
			'post_type'   => self::CPT,
			'post_status' => 'publish',
			'post_title'  => 'Blue Academy Classic',
		], true );
		if ( is_wp_error( $id ) || (int) $id <= 0 ) {
			return;
		}

		$serif = "'Playfair Display', Georgia, serif";
		$sans  = "'Poppins', 'Helvetica Neue', sans-serif";
		$cinzel = "'Cinzel', Georgia, serif";
		$field = static function ( string $key, float $x, float $y, float $w, int $size, string $color, string $font, string $weight = 'normal', string $align = 'left' ): array {
			return [
				'key' => $key, 'x' => $x, 'y' => $y, 'w' => $w,
				'font_size' => $size, 'color' => $color, 'font_family' => $font,
				'font_weight' => $weight, 'font_style' => 'normal', 'align' => $align,
			];
		};

		$fields = [
			$field( 'academy_name', 50, 8.5, 72, 35, '#b72b55', $serif, 'bold', 'center' ),
			$field( 'academy_line', 50, 13.5, 68, 15, '#34434a', $sans, 'normal', 'center' ),
			$field( 'certificate_title', 50, 24, 44, 46, '#2f3337', $cinzel, 'bold', 'center' ),
			$field( 'certify_label', 17, 36, 24, 21, '#263238', $sans ),
			$field( 'student_name', 47, 36, 40, 28, '#1f3945', $serif, 'bold' ),
			$field( 'father_label', 17, 44, 24, 21, '#263238', $sans ),
			$field( 'father_name', 47, 44, 40, 25, '#263238', $sans, 'bold' ),
			$field( 'certificate_no_label', 17, 52, 24, 21, '#263238', $sans ),
			$field( 'cert_no', 47, 52, 40, 23, '#263238', $sans, 'bold' ),
			$field( 'completion_text', 42, 62, 66, 22, '#263238', $sans, 'normal', 'center' ),
			$field( 'course_name', 50, 68, 72, 29, '#1f5266', $serif, 'bold', 'center' ),
			$field( 'student_photo', 83, 39, 11, 52, '#263238', $sans, 'normal', 'center' ),
			$field( 'qr_code', 10, 86, 10, 48, '#263238', $sans, 'normal', 'center' ),
			$field( 'issue_label', 24, 84, 20, 18, '#263238', $sans, 'bold', 'center' ),
			$field( 'issue_date', 24, 89, 24, 23, '#263238', $sans, 'bold', 'center' ),
			$field( 'branch_name', 74, 84, 30, 16, '#263238', $sans, 'bold', 'center' ),
			$field( 'signature_label', 74, 90, 30, 22, '#263238', $sans, 'bold', 'center' ),
		];

		update_post_meta( (int) $id, self::META_BG_URL, get_theme_file_uri( '/assets/certificate-blue-academy-v1.png' ) );
		update_post_meta( (int) $id, self::META_W, 1536 );
		update_post_meta( (int) $id, self::META_H, 1024 );
		update_post_meta( (int) $id, self::META_FIELDS, $fields );
		update_option( 'rks_blue_academy_preset_seeded_v1', (int) $id, false );

		// On a fresh installation, make the preset usable immediately.
		if ( (int) get_option( self::OPT_ACTIVE, 0 ) <= 0 ) {
			update_option( self::OPT_ACTIVE, (int) $id, false );
		}
	}

	/* ------------------------------------------------------------------ */
	/* Save handlers                                                       */
	/* ------------------------------------------------------------------ */

	public function handle_save(): void {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Insufficient permissions.' );
		check_admin_referer( self::NONCE );

		$id    = (int) ( $_POST['design_id'] ?? 0 );
		$title = sanitize_text_field( wp_unslash( (string) ( $_POST['title'] ?? 'Certificate Design' ) ) );
		$bg_id = (int) ( $_POST['bg_id'] ?? 0 );
		$cw    = max( 100, min( 4000, (int) ( $_POST['canvas_w'] ?? 1240 ) ) );
		$ch    = max( 100, min( 4000, (int) ( $_POST['canvas_h'] ?? 877 ) ) );

		$raw    = isset( $_POST['fields_json'] ) ? wp_unslash( (string) $_POST['fields_json'] ) : '[]';
		$parsed = json_decode( $raw, true );
		$clean  = [];
		$allowed_keys = array_keys( self::variables() );
		if ( is_array( $parsed ) ) {
			foreach ( $parsed as $f ) {
				if ( ! is_array( $f ) ) continue;
				$key = (string) ( $f['key'] ?? '' );
				if ( ! in_array( $key, $allowed_keys, true ) ) continue;
				$clean[] = [
					'key'         => $key,
					'x'           => max( 0,   min( 100, (float) ( $f['x'] ?? 0 ) ) ),
					'y'           => max( 0,   min( 100, (float) ( $f['y'] ?? 0 ) ) ),
					'w'           => max( 1,   min( 100, (float) ( $f['w'] ?? 30 ) ) ),
					'font_size'   => max( 6,   min( 200, (int)   ( $f['font_size'] ?? 24 ) ) ),
					'color'       => preg_match( '/^#[0-9a-f]{6}$/i', (string) ( $f['color'] ?? '#000000' ) ) ? (string) $f['color'] : '#000000',
					'font_family' => in_array( (string) ( $f['font_family'] ?? '' ), array_keys( self::fonts() ), true ) ? (string) $f['font_family'] : "'Playfair Display', Georgia, serif",
					'font_weight' => in_array( (string) ( $f['font_weight'] ?? '' ), [ 'normal', 'bold' ], true ) ? (string) $f['font_weight'] : 'normal',
					'font_style'  => in_array( (string) ( $f['font_style'] ?? '' ), [ 'normal', 'italic' ], true ) ? (string) $f['font_style'] : 'normal',
					'align'       => in_array( (string) ( $f['align'] ?? '' ), [ 'left', 'center', 'right' ], true ) ? (string) $f['align'] : 'center',
				];
			}
		}

		if ( $id > 0 && get_post_type( $id ) === self::CPT ) {
			wp_update_post( [ 'ID' => $id, 'post_title' => $title ] );
		} else {
			$id = (int) wp_insert_post( [
				'post_type'   => self::CPT,
				'post_status' => 'publish',
				'post_title'  => $title,
			] );
		}

		if ( $id > 0 ) {
			update_post_meta( $id, self::META_BG,     $bg_id );
			update_post_meta( $id, self::META_W,      $cw );
			update_post_meta( $id, self::META_H,      $ch );
			update_post_meta( $id, self::META_FIELDS, $clean );

			// First design becomes active automatically
			if ( (int) get_option( self::OPT_ACTIVE, 0 ) <= 0 ) {
				update_option( self::OPT_ACTIVE, $id, false );
			}
		}

		wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG, 'edit' => $id, 'saved' => 1 ], admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_delete(): void {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Insufficient permissions.' );
		check_admin_referer( self::NONCE );
		$id = (int) ( $_GET['id'] ?? 0 );
		if ( $id > 0 && get_post_type( $id ) === self::CPT ) {
			wp_delete_post( $id, true );
			if ( (int) get_option( self::OPT_ACTIVE, 0 ) === $id ) {
				update_option( self::OPT_ACTIVE, 0, false );
			}
		}
		wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG, 'deleted' => 1 ], admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_set_active(): void {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Insufficient permissions.' );
		check_admin_referer( self::NONCE );
		$id = (int) ( $_GET['id'] ?? 0 );
		if ( $id >= 0 ) {
			update_option( self::OPT_ACTIVE, $id, false );
		}
		wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG, 'activated' => $id ], admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_save_prefix(): void {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Insufficient permissions.' );
		check_admin_referer( self::NONCE );

		$rules       = Certificate::get_prefix_rules();
		$requested_id = isset( $_POST['rule_id'] ) ? sanitize_key( wp_unslash( (string) $_POST['rule_id'] ) ) : '';
		$rule_id     = $requested_id ?: 'rule_' . strtolower( wp_generate_password( 8, false, false ) );
		$rule        = Certificate::normalize_prefix_rule( [
			'id'         => $rule_id,
			'label'      => isset( $_POST['rule_label'] ) ? wp_unslash( (string) $_POST['rule_label'] ) : 'Certificate Prefix',
			'prefix'     => isset( $_POST['cert_prefix'] ) ? wp_unslash( (string) $_POST['cert_prefix'] ) : 'RKS-CERT',
			'year_mode'  => isset( $_POST['year_mode'] ) ? wp_unslash( (string) $_POST['year_mode'] ) : 'current',
			'fixed_year' => isset( $_POST['fixed_year'] ) ? wp_unslash( (string) $_POST['fixed_year'] ) : '',
		] );

		$updated = false;
		foreach ( $rules as $i => $existing ) {
			if ( (string) $existing['id'] === (string) $rule['id'] ) {
				$rules[ $i ] = $rule;
				$updated = true;
				break;
			}
		}
		if ( ! $updated ) {
			$rules[] = $rule;
		}

		update_option( Certificate::OPT_PREFIX_RULES, array_values( $rules ), false );

		if ( ! get_option( Certificate::OPT_ACTIVE_PREFIX_RULE, '' ) || ! empty( $_POST['set_active'] ) ) {
			update_option( Certificate::OPT_ACTIVE_PREFIX_RULE, (string) $rule['id'], false );
		}

		$active_rule = Certificate::get_active_prefix_rule();
		$settings = (array) get_option( 'rks_settings', [] );
		$settings['cert_prefix'] = (string) $active_rule['prefix'];
		update_option( 'rks_settings', $settings, true );

		wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG_PREFIX, 'prefix_msg' => 'saved' ], admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_set_prefix(): void {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Insufficient permissions.' );
		check_admin_referer( self::NONCE );

		$id = isset( $_GET['id'] ) ? sanitize_key( wp_unslash( (string) $_GET['id'] ) ) : '';
		$rules = Certificate::get_prefix_rules();
		$matched = null;
		foreach ( $rules as $rule ) {
			if ( (string) $rule['id'] === $id ) {
				$matched = $rule;
				break;
			}
		}
		if ( $matched ) {
			update_option( Certificate::OPT_ACTIVE_PREFIX_RULE, (string) $matched['id'], false );
			$settings = (array) get_option( 'rks_settings', [] );
			$settings['cert_prefix'] = (string) $matched['prefix'];
			update_option( 'rks_settings', $settings, true );
		}

		wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG_PREFIX, 'prefix_msg' => $matched ? 'active' : 'not_found' ], admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_delete_prefix(): void {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Insufficient permissions.' );
		check_admin_referer( self::NONCE );

		$id = isset( $_GET['id'] ) ? sanitize_key( wp_unslash( (string) $_GET['id'] ) ) : '';
		$rules = Certificate::get_prefix_rules();
		if ( count( $rules ) <= 1 ) {
			wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG_PREFIX, 'prefix_msg' => 'last_rule' ], admin_url( 'admin.php' ) ) );
			exit;
		}

		$active_id = (string) get_option( Certificate::OPT_ACTIVE_PREFIX_RULE, '' );
		$rules = array_values( array_filter( $rules, static function ( array $rule ) use ( $id ): bool {
			return (string) $rule['id'] !== $id;
		} ) );
		update_option( Certificate::OPT_PREFIX_RULES, $rules, false );

		if ( $active_id === $id || ! $active_id ) {
			update_option( Certificate::OPT_ACTIVE_PREFIX_RULE, (string) $rules[0]['id'], false );
		}

		$active_rule = Certificate::get_active_prefix_rule();
		$settings = (array) get_option( 'rks_settings', [] );
		$settings['cert_prefix'] = (string) $active_rule['prefix'];
		update_option( 'rks_settings', $settings, true );

		wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG_PREFIX, 'prefix_msg' => 'deleted' ], admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_save_legacy(): void {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Insufficient permissions.' );
		check_admin_referer( self::NONCE );

		global $wpdb;
		$table = DB::table( 'certificates' );

		$unique_id = isset( $_POST['unique_id'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['unique_id'] ) ) : '';
		$unique_id = strtoupper( preg_replace( '/\s+/', '', trim( $unique_id ) ) );
		$unique_id = substr( $unique_id, 0, 50 );

		$holder_name  = isset( $_POST['holder_name'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['holder_name'] ) ) : '';
		$father_name  = isset( $_POST['father_name'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['father_name'] ) ) : '';
		$course_title = isset( $_POST['course_title'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['course_title'] ) ) : '';
		$issue_date   = isset( $_POST['issue_date'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['issue_date'] ) ) : '';
		$branch_id    = (int) ( $_POST['branch_id'] ?? 0 );
		$status       = isset( $_POST['status'] ) && 'revoked' === (string) $_POST['status'] ? 'revoked' : 'valid';
		$old_ref      = isset( $_POST['old_ref'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['old_ref'] ) ) : '';
		$photo_url    = isset( $_POST['student_photo_url'] ) ? esc_url_raw( wp_unslash( (string) $_POST['student_photo_url'] ) ) : '';
		$notes        = isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( (string) $_POST['notes'] ) ) : '';

		if ( '' === $unique_id || '' === $holder_name || '' === $course_title ) {
			$this->redirect_legacy( 'missing' );
		}

		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $issue_date ) ) {
			$issue_date = current_time( 'Y-m-d' );
		}

		if ( $branch_id <= 0 ) {
			$branch_id = ( new Branches() )->get_main_branch_id();
		}
		if ( $branch_id <= 0 ) {
			$branch_id = 1;
		}

		$exists = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE unique_id = %s LIMIT 1", $unique_id ) );
		if ( $exists > 0 ) {
			$this->redirect_legacy( 'duplicate' );
		}

		$meta = [
			'source'            => 'legacy_offline',
			'old_reference'     => $old_ref,
			'father_name'       => $father_name,
			'student_photo_url' => $photo_url,
			'notes'             => $notes,
			'entered_by'        => get_current_user_id(),
			'entered_at'        => current_time( 'mysql' ),
		];
		$design = Certificate::active_design_snapshot();
		if ( $design ) {
			$meta['design_snapshot'] = $design;
		}

		$ok = $wpdb->insert(
			$table,
			[
				'unique_id'     => $unique_id,
				'user_id'       => 0,
				'course_id'     => 0,
				'enrollment_id' => null,
				'branch_id'     => $branch_id,
				'holder_name'   => $holder_name,
				'course_title'  => $course_title,
				'issue_date'    => $issue_date,
				'pdf_path'      => null,
				'qr_path'       => null,
				'status'        => $status,
				'meta'          => DB::encode_meta( array_filter( $meta, static function ( $value ) {
					return '' !== $value && null !== $value;
				} ) ),
			],
			[ '%s', '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
		);

		$this->redirect_legacy( $ok ? 'saved' : 'error' );
	}

	public function handle_status_legacy(): void {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Insufficient permissions.' );
		check_admin_referer( self::NONCE );

		$id     = (int) ( $_GET['id'] ?? 0 );
		$status = isset( $_GET['status'] ) && 'revoked' === (string) $_GET['status'] ? 'revoked' : 'valid';
		$row    = $this->get_legacy_certificate( $id );

		if ( ! $row ) {
			$this->redirect_legacy( 'not_found' );
		}

		global $wpdb;
		$ok = $wpdb->update(
			DB::table( 'certificates' ),
			[ 'status' => $status ],
			[ 'id' => $id ],
			[ '%s' ],
			[ '%d' ]
		);

		$this->redirect_legacy( false === $ok ? 'error' : ( 'valid' === $status ? 'restored' : 'revoked' ) );
	}

	public function handle_delete_legacy(): void {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Insufficient permissions.' );
		check_admin_referer( self::NONCE );

		$id  = (int) ( $_GET['id'] ?? 0 );
		$row = $this->get_legacy_certificate( $id );
		if ( ! $row ) {
			$this->redirect_legacy( 'not_found' );
		}

		global $wpdb;
		$deleted = $wpdb->delete( DB::table( 'certificates' ), [ 'id' => $id ], [ '%d' ] );
		$this->redirect_legacy( false === $deleted ? 'error' : 'deleted_legacy' );
	}

	public function handle_status_certificate(): void {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Insufficient permissions.' );
		check_admin_referer( self::NONCE );

		$id     = (int) ( $_GET['id'] ?? 0 );
		$status = isset( $_GET['status'] ) && 'revoked' === (string) $_GET['status'] ? 'revoked' : 'valid';
		$row    = $this->get_certificate( $id );

		if ( ! $row ) {
			$this->redirect_issued( 'not_found' );
		}

		global $wpdb;
		$ok = $wpdb->update(
			DB::table( 'certificates' ),
			[ 'status' => $status ],
			[ 'id' => $id ],
			[ '%s' ],
			[ '%d' ]
		);

		$this->redirect_issued( false === $ok ? 'error' : ( 'valid' === $status ? 'restored' : 'revoked' ) );
	}

	public function handle_delete_certificate(): void {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Insufficient permissions.' );
		check_admin_referer( self::NONCE );

		$id  = (int) ( $_GET['id'] ?? 0 );
		$row = $this->get_certificate( $id );
		if ( ! $row ) {
			$this->redirect_issued( 'not_found' );
		}

		global $wpdb;
		$deleted = $wpdb->delete( DB::table( 'certificates' ), [ 'id' => $id ], [ '%d' ] );
		$this->redirect_issued( false === $deleted ? 'error' : 'deleted' );
	}

	private function redirect_issued( string $message ): void {
		wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG_ISSUED, 'cert_msg' => $message ], admin_url( 'admin.php' ) ) );
		exit;
	}

	private function redirect_legacy( string $message ): void {
		wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG_OFFLINE, 'legacy_msg' => $message ], admin_url( 'admin.php' ) ) );
		exit;
	}

	private function get_certificate( int $id ): ?array {
		if ( $id <= 0 ) {
			return null;
		}

		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . DB::table( 'certificates' ) . ' WHERE id = %d LIMIT 1', $id ),
			ARRAY_A
		);

		return $row ?: null;
	}

	private function get_legacy_certificate( int $id ): ?array {
		if ( $id <= 0 ) {
			return null;
		}

		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . DB::table( 'certificates' ) . ' WHERE id = %d LIMIT 1', $id ),
			ARRAY_A
		);
		if ( ! $row || ! $this->is_legacy_certificate( $row ) ) {
			return null;
		}

		return $row;
	}

	private function is_legacy_certificate( array $row ): bool {
		$meta = DB::decode_meta( isset( $row['meta'] ) ? (string) $row['meta'] : null );
		return 'legacy_offline' === (string) ( $meta['source'] ?? '' ) || (int) ( $row['user_id'] ?? 0 ) === 0;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private function get_legacy_certificates(): array {
		global $wpdb;
		$table = DB::table( 'certificates' );
		$rows = $wpdb->get_results(
			"SELECT * FROM $table WHERE user_id = 0 OR meta LIKE '%\"source\":\"legacy_offline\"%' ORDER BY id DESC LIMIT 100",
			ARRAY_A
		);
		return $rows ?: [];
	}

	/* ------------------------------------------------------------------ */
	/* Render                                                              */
	/* ------------------------------------------------------------------ */

	public function render_issued(): void {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Insufficient permissions.' );

		global $wpdb;
		$table = DB::table( 'certificates' );
		$status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( (string) $_GET['status'] ) ) : '';
		$source = isset( $_GET['source'] ) ? sanitize_key( wp_unslash( (string) $_GET['source'] ) ) : '';
		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['s'] ) ) : '';

		$where  = [ '1=1' ];
		$params = [];
		if ( in_array( $status, [ 'valid', 'revoked' ], true ) ) {
			$where[]  = 'status = %s';
			$params[] = $status;
		}
		if ( 'offline' === $source ) {
			$where[] = '(user_id = 0 OR meta LIKE %s)';
			$params[] = '%"source":"legacy_offline"%';
		} elseif ( 'system' === $source ) {
			$where[] = 'user_id > 0';
		}
		if ( '' !== $search ) {
			$like = '%' . $wpdb->esc_like( $search ) . '%';
			$where[] = '(unique_id LIKE %s OR holder_name LIKE %s OR course_title LIKE %s)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		$sql = "SELECT * FROM $table WHERE " . implode( ' AND ', $where ) . ' ORDER BY id DESC LIMIT 200';
		$rows = empty( $params ) ? $wpdb->get_results( $sql, ARRAY_A ) : $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );
		$rows = $rows ?: [];

		$stats = [
			'total'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table" ),
			'valid'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE status='valid'" ),
			'revoked' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE status='revoked'" ),
			'offline' => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE user_id = 0 OR meta LIKE %s", '%"source":"legacy_offline"%' ) ),
		];

		$branch_names = $this->branch_name_map();
		$msg = isset( $_GET['cert_msg'] ) ? sanitize_key( wp_unslash( (string) $_GET['cert_msg'] ) ) : '';
		$messages = [
			'revoked'   => 'Certificate revoked. Public verification will now fail for that ID.',
			'restored'  => 'Certificate restored. Public verification is active again.',
			'deleted'   => 'Certificate record deleted.',
			'not_found' => 'Certificate record was not found.',
			'error'     => 'Something went wrong. Please try again.',
		];

		$this->inline_css();
		?>
		<div class="wrap rkscd-wrap">
			<?php $this->render_module_hero( 'Issued Certificates', 'Search, verify, revoke, restore, or delete every certificate record from one place.' ); ?>
			<?php $this->render_nav( self::SLUG_ISSUED ); ?>

			<?php if ( $msg ) : ?>
				<div class="rkscd-notice <?php echo in_array( $msg, [ 'error', 'not_found' ], true ) ? 'rkscd-notice--error' : ''; ?>"><?php echo esc_html( $messages[ $msg ] ?? 'Certificate updated.' ); ?></div>
			<?php endif; ?>

			<div class="rkscd-stat-grid">
				<div><small>Total</small><strong><?php echo number_format( $stats['total'] ); ?></strong></div>
				<div><small>Valid</small><strong><?php echo number_format( $stats['valid'] ); ?></strong></div>
				<div><small>Revoked</small><strong><?php echo number_format( $stats['revoked'] ); ?></strong></div>
				<div><small>Offline</small><strong><?php echo number_format( $stats['offline'] ); ?></strong></div>
			</div>

			<div class="rkscd-section">
				<form class="rkscd-filters" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
					<input type="hidden" name="page" value="<?php echo esc_attr( self::SLUG_ISSUED ); ?>">
					<label>
						<span>Search</span>
						<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Certificate ID, student, course">
					</label>
					<label>
						<span>Status</span>
						<select name="status">
							<option value="">All statuses</option>
							<option value="valid" <?php selected( $status, 'valid' ); ?>>Valid</option>
							<option value="revoked" <?php selected( $status, 'revoked' ); ?>>Revoked</option>
						</select>
					</label>
					<label>
						<span>Source</span>
						<select name="source">
							<option value="">All sources</option>
							<option value="system" <?php selected( $source, 'system' ); ?>>System issued</option>
							<option value="offline" <?php selected( $source, 'offline' ); ?>>Offline / legacy</option>
						</select>
					</label>
					<button type="submit" class="button button-primary">Filter</button>
					<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::SLUG_ISSUED ) ); ?>">Clear</a>
				</form>

				<?php $this->render_certificate_table( $rows, $branch_names, true ); ?>
			</div>
		</div>
		<?php
	}

	public function render_prefixes(): void {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Insufficient permissions.' );

		$rules = Certificate::get_prefix_rules();
		$active = Certificate::get_active_prefix_rule();
		$edit_id = isset( $_GET['edit_prefix'] ) ? sanitize_key( wp_unslash( (string) $_GET['edit_prefix'] ) ) : '';
		$edit_rule = null;
		foreach ( $rules as $rule ) {
			if ( (string) $rule['id'] === $edit_id ) {
				$edit_rule = $rule;
				break;
			}
		}
		$form_rule = $edit_rule ?: Certificate::normalize_prefix_rule( [
			'id'         => '',
			'label'      => '',
			'prefix'     => 'RKS-CERT',
			'year_mode'  => 'current',
			'fixed_year' => '',
		] );

		$msg = isset( $_GET['prefix_msg'] ) ? sanitize_key( wp_unslash( (string) $_GET['prefix_msg'] ) ) : '';
		$messages = [
			'saved'     => 'Prefix rule saved.',
			'active'    => 'Active certificate prefix updated.',
			'deleted'   => 'Prefix rule deleted.',
			'last_rule' => 'At least one prefix rule is required.',
			'not_found' => 'Prefix rule was not found.',
		];

		$this->inline_css();
		?>
		<div class="wrap rkscd-wrap">
			<?php $this->render_module_hero( 'Prefix Rules', 'Create multiple certificate ID formats and choose which one new certificates use.' ); ?>
			<?php $this->render_nav( self::SLUG_PREFIX ); ?>

			<?php if ( $msg ) : ?>
				<div class="rkscd-notice <?php echo in_array( $msg, [ 'last_rule', 'not_found' ], true ) ? 'rkscd-notice--error' : ''; ?>"><?php echo esc_html( $messages[ $msg ] ?? 'Prefix updated.' ); ?></div>
			<?php endif; ?>

			<div class="rkscd-section">
				<div class="rkscd-section__head">
					<h2><span class="dashicons dashicons-tag"></span> <?php echo $edit_rule ? 'Edit Prefix Rule' : 'Add Prefix Rule'; ?></h2>
					<p>Examples: <code>RKS-CERT-2026-000123</code>, <code>RKS-DIP-2026-000123</code>, or without year <code>RKS-000123</code>.</p>
				</div>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="rkscd-prefix-rule-form">
					<input type="hidden" name="action" value="rks_cd_save_prefix">
					<input type="hidden" name="rule_id" value="<?php echo esc_attr( $edit_rule ? (string) $form_rule['id'] : '' ); ?>">
					<?php wp_nonce_field( self::NONCE ); ?>
					<label>
						<span>Label</span>
						<input type="text" name="rule_label" value="<?php echo esc_attr( (string) $form_rule['label'] ); ?>" placeholder="Diploma Certificates" required>
					</label>
					<label>
						<span>Prefix</span>
						<input type="text" name="cert_prefix" value="<?php echo esc_attr( (string) $form_rule['prefix'] ); ?>" pattern="[A-Z0-9\-]+" maxlength="30" required>
					</label>
					<label>
						<span>Year</span>
						<select name="year_mode">
							<option value="current" <?php selected( $form_rule['year_mode'], 'current' ); ?>>Current year</option>
							<option value="fixed" <?php selected( $form_rule['year_mode'], 'fixed' ); ?>>Fixed year</option>
							<option value="none" <?php selected( $form_rule['year_mode'], 'none' ); ?>>No year</option>
						</select>
					</label>
					<label>
						<span>Fixed Year</span>
						<input type="text" name="fixed_year" value="<?php echo esc_attr( (string) $form_rule['fixed_year'] ); ?>" placeholder="<?php echo esc_attr( date( 'Y' ) ); ?>" maxlength="4" pattern="[0-9]{4}">
					</label>
					<label class="rkscd-check">
						<input type="checkbox" name="set_active" value="1" <?php checked( ! $edit_rule ); ?>>
						<span>Use this rule for new certificates</span>
					</label>
					<div class="rkscd-prefix-rule-form__actions">
						<button type="submit" class="button button-primary"><?php echo $edit_rule ? 'Save Rule' : 'Add Rule'; ?></button>
						<?php if ( $edit_rule ) : ?>
							<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::SLUG_PREFIX ) ); ?>">Cancel Edit</a>
						<?php endif; ?>
					</div>
				</form>
			</div>

			<div class="rkscd-section">
				<div class="rkscd-section__head">
					<h2><span class="dashicons dashicons-list-view"></span> Saved Prefix Rules</h2>
					<p>Active rule: <strong><?php echo esc_html( (string) $active['label'] ); ?></strong>.</p>
				</div>
				<div class="rkscd-prefix-rules">
					<?php foreach ( $rules as $rule ) :
						$is_active = (string) $rule['id'] === (string) $active['id'];
						$sample_year = Certificate::prefix_year_part( $rule );
						$sample = (string) $rule['prefix'] . ( $sample_year ? '-' . $sample_year : '' ) . '-000123';
						?>
						<div class="rkscd-prefix-rule <?php echo $is_active ? 'is-active' : ''; ?>">
							<div>
								<strong><?php echo esc_html( (string) $rule['label'] ); ?></strong>
								<code><?php echo esc_html( $sample ); ?></code>
								<small><?php echo esc_html( 'Year mode: ' . (string) $rule['year_mode'] ); ?></small>
							</div>
							<div class="rkscd-prefix-rule__actions">
								<?php if ( $is_active ) : ?>
									<span class="rkscd-pick__active-tag">ACTIVE</span>
								<?php else : ?>
									<a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'rks_cd_set_prefix', 'id' => (string) $rule['id'] ], admin_url( 'admin-post.php' ) ), self::NONCE ) ); ?>">Use</a>
								<?php endif; ?>
								<a class="button" href="<?php echo esc_url( add_query_arg( [ 'page' => self::SLUG_PREFIX, 'edit_prefix' => (string) $rule['id'] ], admin_url( 'admin.php' ) ) ); ?>">Edit</a>
								<a class="button button-link-delete" onclick="return confirm('Delete this prefix rule? Existing certificate IDs will remain valid.');" href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'rks_cd_delete_prefix', 'id' => (string) $rule['id'] ], admin_url( 'admin-post.php' ) ), self::NONCE ) ); ?>">Delete</a>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<?php
	}

	public function render_offline(): void {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Insufficient permissions.' );

		$branches = ( new Branches() )->list( [ 'limit' => 200 ] );
		$branch_names = [];
		foreach ( $branches as $branch ) {
			$branch_names[ (int) $branch['id'] ] = (string) $branch['name'];
		}
		$legacy_rows = $this->get_legacy_certificates();
		$legacy_msg = isset( $_GET['legacy_msg'] ) ? sanitize_key( wp_unslash( (string) $_GET['legacy_msg'] ) ) : '';
		$legacy_messages = [
			'saved'          => 'Offline certificate entry saved. It can now be verified publicly.',
			'duplicate'      => 'That Certificate ID already exists. Use the original unique ID from the offline certificate.',
			'missing'        => 'Certificate ID, student name, and course title are required.',
			'revoked'        => 'Offline certificate revoked. Public verification will now fail.',
			'restored'       => 'Offline certificate restored. Public verification is active again.',
			'deleted_legacy' => 'Offline certificate entry deleted.',
			'not_found'      => 'Offline certificate entry was not found.',
			'error'          => 'Something went wrong. Please try again.',
		];

		$this->inline_css();
		?>
		<div class="wrap rkscd-wrap">
			<?php $this->render_module_hero( 'Offline Certificate Registry', 'Add old certificates issued outside the system so users can verify them online.' ); ?>
			<?php $this->render_nav( self::SLUG_OFFLINE ); ?>

			<?php if ( $legacy_msg ) : ?>
				<div class="rkscd-notice <?php echo in_array( $legacy_msg, [ 'duplicate', 'missing', 'error', 'not_found' ], true ) ? 'rkscd-notice--error' : ''; ?>">
					<?php echo esc_html( $legacy_messages[ $legacy_msg ] ?? 'Certificate registry updated.' ); ?>
				</div>
			<?php endif; ?>

			<div class="rkscd-section" id="legacy-certificates">
				<div class="rkscd-section__head">
					<h2><span class="dashicons dashicons-clipboard"></span> Add Offline Certificate</h2>
					<p>Enter the exact certificate ID printed on the old certificate. If status is <strong>Valid</strong>, public verification will pass immediately.</p>
				</div>
				<div class="rkscd-legacy">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="rkscd-legacy-form">
						<input type="hidden" name="action" value="rks_cd_save_legacy">
						<?php wp_nonce_field( self::NONCE ); ?>
						<label><span>Certificate ID *</span><input type="text" name="unique_id" maxlength="50" placeholder="RKS-CERT-2022-000145" required></label>
						<label><span>Student Name *</span><input type="text" name="holder_name" maxlength="190" required></label>
						<label><span>Father's Name</span><input type="text" name="father_name" maxlength="190"></label>
						<label><span>Course / Program *</span><input type="text" name="course_title" maxlength="190" required></label>
						<label><span>Issue Date</span><input type="date" name="issue_date" value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>"></label>
						<label>
							<span>Branch</span>
							<select name="branch_id">
								<?php foreach ( $branches as $branch ) : ?>
									<option value="<?php echo (int) $branch['id']; ?>"><?php echo esc_html( (string) $branch['name'] ); ?></option>
								<?php endforeach; ?>
							</select>
						</label>
						<label><span>Status</span><select name="status"><option value="valid">Valid</option><option value="revoked">Revoked</option></select></label>
						<label><span>Old Ref / Roll No.</span><input type="text" name="old_ref" maxlength="100" placeholder="Optional"></label>
						<label class="rkscd-legacy-form__wide">
							<span>Student Photo URL</span>
							<div class="rkscd-media-row">
								<input type="url" name="student_photo_url" id="rkscd-legacy-photo-url" placeholder="Optional: https://...">
								<button type="button" class="button" id="rkscd-legacy-pick-photo">Choose Photo</button>
							</div>
						</label>
						<label class="rkscd-legacy-form__wide"><span>Internal Notes</span><textarea name="notes" rows="2" placeholder="Only visible to admin"></textarea></label>
						<div class="rkscd-legacy-form__actions">
							<button type="submit" class="button button-primary">Add Offline Certificate</button>
							<a class="button" href="<?php echo esc_url( home_url( '/verify-certificate/' ) ); ?>" target="_blank" rel="noopener">Open Verify Page</a>
						</div>
					</form>

					<div class="rkscd-legacy-list">
						<h3>Recent Offline Entries</h3>
						<?php $this->render_certificate_table( $legacy_rows, $branch_names, false ); ?>
					</div>
				</div>
			</div>
			<?php $this->render_offline_media_script(); ?>
		</div>
		<?php
	}

	private function render_module_hero( string $title, string $description ): void {
		?>
		<div class="rkscd-hero">
			<div class="rkscd-hero__icon"><span class="dashicons dashicons-awards"></span></div>
			<div>
				<small>Certificate Module</small>
				<h1><?php echo esc_html( $title ); ?></h1>
				<p><?php echo esc_html( $description ); ?></p>
			</div>
		</div>
		<?php
	}

	private function render_nav( string $active_slug ): void {
		$items = [
			self::SLUG_ISSUED  => [ 'Issued Certificates', 'dashicons-awards' ],
			self::SLUG_OFFLINE => [ 'Offline Registry', 'dashicons-clipboard' ],
			self::SLUG_PREFIX  => [ 'Prefix Rules', 'dashicons-tag' ],
			self::SLUG         => [ 'Templates', 'dashicons-format-image' ],
		];
		?>
		<nav class="rkscd-nav" aria-label="Certificate module navigation">
			<?php foreach ( $items as $slug => $item ) : ?>
				<a class="<?php echo $slug === $active_slug ? 'is-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=' . $slug ) ); ?>">
					<span class="dashicons <?php echo esc_attr( $item[1] ); ?>"></span>
					<?php echo esc_html( $item[0] ); ?>
				</a>
			<?php endforeach; ?>
		</nav>
		<?php
	}

	private function render_certificate_table( array $rows, array $branch_names, bool $show_source ): void {
		if ( empty( $rows ) ) {
			echo '<div class="rkscd-empty"><p>No certificate records found.</p></div>';
			return;
		}
		?>
		<table class="widefat striped rkscd-cert-table">
			<thead>
				<tr>
					<th>Certificate</th>
					<th>Student</th>
					<th>Course</th>
					<th>Branch</th>
					<?php if ( $show_source ) : ?><th>Source</th><?php endif; ?>
					<th>Status</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $rows as $row ) :
					$meta = DB::decode_meta( isset( $row['meta'] ) ? (string) $row['meta'] : null );
					$is_valid = 'valid' === (string) $row['status'];
					$is_legacy = $this->is_legacy_certificate( $row );
					$status_action = ( ! $show_source && $is_legacy ) ? 'rks_cd_status_legacy' : 'rks_cd_status_cert';
					$delete_action = ( ! $show_source && $is_legacy ) ? 'rks_cd_delete_legacy' : 'rks_cd_delete_cert';
					$status_url = wp_nonce_url(
						add_query_arg(
							[
								'action' => $status_action,
								'id'     => (int) $row['id'],
								'status' => $is_valid ? 'revoked' : 'valid',
							],
							admin_url( 'admin-post.php' )
						),
						self::NONCE
					);
					$delete_url = wp_nonce_url(
						add_query_arg( [ 'action' => $delete_action, 'id' => (int) $row['id'] ], admin_url( 'admin-post.php' ) ),
						self::NONCE
					);
					$verify_url = add_query_arg( 'id', (string) $row['unique_id'], home_url( '/verify-certificate/' ) );
					$cert_url = add_query_arg( 'id', (string) $row['unique_id'], home_url( '/certificate/' ) );
					?>
					<tr>
						<td>
							<code><?php echo esc_html( (string) $row['unique_id'] ); ?></code>
							<?php if ( ! empty( $meta['old_reference'] ) ) : ?>
								<br><small>Ref: <?php echo esc_html( (string) $meta['old_reference'] ); ?></small>
							<?php endif; ?>
							<br><small>Issued <?php echo esc_html( date( 'd M Y', strtotime( (string) $row['issue_date'] ) ) ); ?></small>
						</td>
						<td>
							<strong><?php echo esc_html( (string) $row['holder_name'] ); ?></strong>
							<?php if ( ! empty( $meta['father_name'] ) ) : ?>
								<br><small>Father: <?php echo esc_html( (string) $meta['father_name'] ); ?></small>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( (string) $row['course_title'] ); ?></td>
						<td><?php echo esc_html( $branch_names[ (int) $row['branch_id'] ] ?? '#' . (int) $row['branch_id'] ); ?></td>
						<?php if ( $show_source ) : ?>
							<td><span class="rkscd-source rkscd-source--<?php echo $is_legacy ? 'offline' : 'system'; ?>"><?php echo $is_legacy ? 'Offline' : 'System'; ?></span></td>
						<?php endif; ?>
						<td><span class="rkscd-status rkscd-status--<?php echo $is_valid ? 'valid' : 'revoked'; ?>"><?php echo esc_html( ucfirst( (string) $row['status'] ) ); ?></span></td>
						<td class="rkscd-legacy-actions">
							<a class="button button-small" href="<?php echo esc_url( $verify_url ); ?>" target="_blank" rel="noopener">Verify</a>
							<a class="button button-small" href="<?php echo esc_url( $cert_url ); ?>" target="_blank" rel="noopener">View</a>
							<a class="button button-small" href="<?php echo esc_url( $status_url ); ?>"><?php echo $is_valid ? 'Revoke' : 'Restore'; ?></a>
							<a class="button button-small button-link-delete" onclick="return confirm('Delete this certificate record? Verification will stop working for this ID.');" href="<?php echo esc_url( $delete_url ); ?>">Delete</a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	private function branch_name_map(): array {
		$branches = ( new Branches() )->list( [ 'limit' => 200 ] );
		$branch_names = [];
		foreach ( $branches as $branch ) {
			$branch_names[ (int) $branch['id'] ] = (string) $branch['name'];
		}
		return $branch_names;
	}

	private function render_offline_media_script(): void {
		?>
		<script>
		jQuery(function($){
			var frame;
			$('#rkscd-legacy-pick-photo').on('click', function(e){
				e.preventDefault();
				if (frame) {
					frame.open();
					return;
				}
				frame = wp.media({
					title: 'Select student photo',
					button: { text: 'Use Photo' },
					multiple: false,
					library: { type: 'image' }
				});
				frame.on('select', function(){
					var att = frame.state().get('selection').first().toJSON();
					$('#rkscd-legacy-photo-url').val(att.url || '');
				});
				frame.open();
			});
		});
		</script>
		<?php
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Insufficient permissions.' );

		$view    = isset( $_GET['edit'] ) || isset( $_GET['new'] ) ? 'editor' : 'list';
		if ( $view === 'editor' ) {
			$this->render_editor();
		} else {
			$this->render_list();
		}
	}

	private function render_list(): void {
		$designs = get_posts( [
			'post_type'   => self::CPT,
			'post_status' => 'publish',
			'numberposts' => -1,
			'orderby'     => 'date',
			'order'       => 'DESC',
		] );
		$active_id = (int) get_option( self::OPT_ACTIVE, 0 );
		$settings  = (array) get_option( 'rks_settings', [] );
		$prefix    = (string) ( $settings['cert_prefix'] ?? 'RKS-CERT' );
		$branches  = ( new Branches() )->list( [ 'limit' => 200 ] );
		$branch_names = [];
		foreach ( $branches as $branch ) {
			$branch_names[ (int) $branch['id'] ] = (string) $branch['name'];
		}
		$legacy_rows = $this->get_legacy_certificates();
		$this->inline_css();
		?>
		<?php
		// Resolve active design info
		$active_post  = $active_id > 0 ? get_post( $active_id ) : null;
		$active_label = ( $active_post && $active_post->post_type === self::CPT ) ? $active_post->post_title : 'Built-in Default Template';
		$set_active_url = static function ( $id ) {
			return wp_nonce_url(
				add_query_arg( [ 'action' => 'rks_cd_set_active', 'id' => (int) $id ], admin_url( 'admin-post.php' ) ),
				self::NONCE
			);
		};
		?>
		<div class="wrap rkscd-wrap">
			<div class="rkscd-hero">
				<div class="rkscd-hero__icon"><span class="dashicons dashicons-awards"></span></div>
				<div>
					<small>Certificate Module</small>
					<h1>Certificate Templates</h1>
					<p>Choose which template gets printed on certificates and manage custom certificate designs.</p>
				</div>
			</div>
			<?php $this->render_nav( self::SLUG ); ?>

			<?php if ( isset( $_GET['saved'] ) )        echo '<div class="rkscd-notice">Design saved.</div>'; ?>
			<?php if ( isset( $_GET['deleted'] ) )      echo '<div class="rkscd-notice">Design deleted.</div>'; ?>
			<?php if ( isset( $_GET['activated'] ) )    echo '<div class="rkscd-notice">Active template updated.</div>'; ?>
			<?php if ( isset( $_GET['prefix_saved'] ) ) echo '<div class="rkscd-notice">Certificate prefix updated.</div>'; ?>
			<?php if ( isset( $_GET['legacy_msg'] ) ) : ?>
				<?php
				$legacy_msg = sanitize_key( wp_unslash( (string) $_GET['legacy_msg'] ) );
				$legacy_messages = [
					'saved'          => 'Offline certificate entry saved. It can now be verified publicly.',
					'duplicate'      => 'That Certificate ID already exists. Use the original unique ID from the offline certificate.',
					'missing'        => 'Certificate ID, student name, and course title are required.',
					'revoked'        => 'Offline certificate revoked. Public verification will now fail.',
					'restored'       => 'Offline certificate restored. Public verification is active again.',
					'deleted_legacy' => 'Offline certificate entry deleted.',
					'not_found'      => 'Offline certificate entry was not found.',
					'error'          => 'Something went wrong. Please try again.',
				];
				?>
				<div class="rkscd-notice <?php echo in_array( $legacy_msg, [ 'duplicate', 'missing', 'error', 'not_found' ], true ) ? 'rkscd-notice--error' : ''; ?>">
					<?php echo esc_html( $legacy_messages[ $legacy_msg ] ?? 'Certificate registry updated.' ); ?>
				</div>
			<?php endif; ?>

			<!-- ===================== STEP 1: ACTIVE TEMPLATE PICKER ===================== -->
			<div class="rkscd-section">
				<div class="rkscd-section__head">
					<span class="rkscd-step">Template</span>
					<h2><span class="dashicons dashicons-yes-alt"></span> Active Certificate Template</h2>
					<p>Pick which template the system uses when a certificate is issued. Currently active: <strong><?php echo esc_html( $active_label ); ?></strong>.</p>
				</div>

				<div class="rkscd-picker">
					<!-- Default option -->
					<div class="rkscd-pick <?php echo $active_id === 0 ? 'is-active' : ''; ?>">
						<div class="rkscd-pick__thumb rkscd-pick__thumb--default">
							<span class="dashicons dashicons-awards"></span>
							<span>Built-in</span>
						</div>
						<div class="rkscd-pick__body">
							<strong>Built-in Default Template</strong>
							<small>Original certificate layout (text-based, no background image).</small>
						</div>
						<div class="rkscd-pick__actions">
							<?php if ( $active_id === 0 ) : ?>
								<span class="rkscd-pick__active-tag">ACTIVE</span>
							<?php else : ?>
								<a class="button button-primary" href="<?php echo esc_url( $set_active_url( 0 ) ); ?>">Use This</a>
							<?php endif; ?>
						</div>
					</div>

					<!-- Custom designs -->
					<?php foreach ( $designs as $d ) :
						$preview   = self::format_design( $d );
						$is_active = $d->ID === $active_id;
						$edit_url  = add_query_arg( [ 'page' => self::SLUG, 'edit' => $d->ID ], admin_url( 'admin.php' ) );
						?>
						<div class="rkscd-pick <?php echo $is_active ? 'is-active' : ''; ?>">
							<div class="rkscd-pick__thumb">
								<?php self::render_design_preview( $preview ); ?>
							</div>
							<div class="rkscd-pick__body">
								<strong><?php echo esc_html( $d->post_title ); ?></strong>
								<small><?php echo (int) count( (array) get_post_meta( $d->ID, self::META_FIELDS, true ) ); ?> variables placed</small>
							</div>
							<div class="rkscd-pick__actions">
								<a class="button" href="<?php echo esc_url( $edit_url ); ?>">Preview / Edit</a>
								<?php if ( $is_active ) : ?>
									<span class="rkscd-pick__active-tag">ACTIVE</span>
								<?php else : ?>
									<a class="button button-primary" href="<?php echo esc_url( $set_active_url( $d->ID ) ); ?>">Use This</a>
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- ===================== STEP 2: DESIGN LIBRARY ===================== -->
			<div class="rkscd-section">
				<div class="rkscd-section__head">
					<span class="rkscd-step">Designs</span>
					<h2><span class="dashicons dashicons-format-image"></span> Design Library</h2>
					<p>Create as many designs as you want. Edit, preview, or delete from here. Only the design marked <strong>ACTIVE</strong> above is printed.</p>
					<a class="button button-primary rkscd-section__cta"
					   href="<?php echo esc_url( add_query_arg( [ 'page' => self::SLUG, 'new' => 1 ], admin_url( 'admin.php' ) ) ); ?>">
						<span class="dashicons dashicons-plus-alt2" style="vertical-align:text-bottom;"></span> Add New Design
					</a>
				</div>

				<?php if ( empty( $designs ) ) : ?>
					<div class="rkscd-empty">
						<p>No custom designs yet. Click <strong>Add New Design</strong> to upload your first certificate image.</p>
						<p><small>Until then, the built-in default template is used.</small></p>
					</div>
				<?php else : ?>
					<div class="rkscd-grid">
						<?php foreach ( $designs as $d ) :
							$preview   = self::format_design( $d );
							$is_active = $d->ID === $active_id;
							$edit_url  = add_query_arg( [ 'page' => self::SLUG, 'edit' => $d->ID ], admin_url( 'admin.php' ) );
							?>
							<div class="rkscd-tile <?php echo $is_active ? 'is-active' : ''; ?>">
								<div class="rkscd-tile__thumb">
									<?php self::render_design_preview( $preview ); ?>
									<?php if ( $is_active ) : ?>
										<span class="rkscd-tile__badge">ACTIVE</span>
									<?php endif; ?>
								</div>
								<div class="rkscd-tile__body">
									<strong><?php echo esc_html( $d->post_title ); ?></strong>
									<small><?php echo (int) count( (array) get_post_meta( $d->ID, self::META_FIELDS, true ) ); ?> variable(s) placed</small>
								</div>
								<div class="rkscd-tile__actions">
									<a class="button button-primary" href="<?php echo esc_url( $edit_url ); ?>">Edit</a>
									<a class="button button-link-delete"
									   onclick="return confirm('Delete this design? Cannot be undone.');"
									   href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'rks_cd_delete', 'id' => $d->ID ], admin_url( 'admin-post.php' ) ), self::NONCE ) ); ?>">
										Delete
									</a>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return;
		?>

			<!-- ===================== STEP 3: PREFIX SETTING ===================== -->
			<div class="rkscd-section">
				<div class="rkscd-section__head">
					<span class="rkscd-step">Step 3</span>
					<h2><span class="dashicons dashicons-tag"></span> Certificate ID Prefix</h2>
					<p>Format: <code>PREFIX-YEAR-NNNNNN</code> &mdash; e.g. <code>RKS-CERT-2026-000123</code>.</p>
				</div>

				<div class="rkscd-prefix-card">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="rkscd-row">
						<input type="hidden" name="action" value="rks_cd_save_prefix">
						<?php wp_nonce_field( self::NONCE ); ?>
						<label class="rkscd-prefix-label">
							<span>Prefix</span>
							<input type="text" name="cert_prefix" value="<?php echo esc_attr( $prefix ); ?>" pattern="[A-Z0-9\-]+" maxlength="20" required>
						</label>
						<button type="submit" class="button button-primary">Save Prefix</button>
					</form>
					<div class="rkscd-prefix-note">
						<span class="dashicons dashicons-info"></span>
						<div>
							<strong>Already-issued certificates are safe.</strong>
							<small>Existing certificate IDs are stored permanently in the database with their original prefix. Verification keeps working for every old certificate even after you change this prefix &mdash; only newly issued certificates use the new value.</small>
						</div>
					</div>
				</div>
			</div>

			<!-- ===================== STEP 4: OFFLINE CERTIFICATE REGISTRY ===================== -->
			<div class="rkscd-section" id="legacy-certificates">
				<div class="rkscd-section__head">
					<span class="rkscd-step">Step 4</span>
					<h2><span class="dashicons dashicons-clipboard"></span> Offline Certificate Registry</h2>
					<p>Add old certificates that were issued offline. Once saved with status <strong>Valid</strong>, the same public certificate verification page will verify that ID.</p>
				</div>

				<div class="rkscd-legacy">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="rkscd-legacy-form">
						<input type="hidden" name="action" value="rks_cd_save_legacy">
						<?php wp_nonce_field( self::NONCE ); ?>

						<label>
							<span>Certificate ID *</span>
							<input type="text" name="unique_id" maxlength="50" placeholder="RKS-CERT-2022-000145" required>
						</label>
						<label>
							<span>Student Name *</span>
							<input type="text" name="holder_name" maxlength="190" required>
						</label>
						<label>
							<span>Father's Name</span>
							<input type="text" name="father_name" maxlength="190">
						</label>
						<label>
							<span>Course / Program *</span>
							<input type="text" name="course_title" maxlength="190" required>
						</label>
						<label>
							<span>Issue Date</span>
							<input type="date" name="issue_date" value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>">
						</label>
						<label>
							<span>Branch</span>
							<select name="branch_id">
								<?php foreach ( $branches as $branch ) : ?>
									<option value="<?php echo (int) $branch['id']; ?>"><?php echo esc_html( (string) $branch['name'] ); ?></option>
								<?php endforeach; ?>
							</select>
						</label>
						<label>
							<span>Status</span>
							<select name="status">
								<option value="valid">Valid</option>
								<option value="revoked">Revoked</option>
							</select>
						</label>
						<label>
							<span>Old Ref / Roll No.</span>
							<input type="text" name="old_ref" maxlength="100" placeholder="Optional">
						</label>
						<label class="rkscd-legacy-form__wide">
							<span>Student Photo URL</span>
							<div class="rkscd-media-row">
								<input type="url" name="student_photo_url" id="rkscd-legacy-photo-url" placeholder="Optional: https://...">
								<button type="button" class="button" id="rkscd-legacy-pick-photo">Choose Photo</button>
							</div>
						</label>
						<label class="rkscd-legacy-form__wide">
							<span>Internal Notes</span>
							<textarea name="notes" rows="2" placeholder="Only visible to admin"></textarea>
						</label>

						<div class="rkscd-legacy-form__actions">
							<button type="submit" class="button button-primary">Add Offline Certificate</button>
							<a class="button" href="<?php echo esc_url( home_url( '/verify-certificate/' ) ); ?>" target="_blank" rel="noopener">Open Verify Page</a>
						</div>
					</form>

					<div class="rkscd-legacy-list">
						<h3>Recent Offline Entries</h3>
						<?php if ( empty( $legacy_rows ) ) : ?>
							<div class="rkscd-empty">
								<p>No offline certificates added yet.</p>
							</div>
						<?php else : ?>
							<table class="widefat striped">
								<thead>
									<tr>
										<th>Certificate</th>
										<th>Student</th>
										<th>Course</th>
										<th>Branch</th>
										<th>Status</th>
										<th>Actions</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $legacy_rows as $row ) :
										$meta = DB::decode_meta( isset( $row['meta'] ) ? (string) $row['meta'] : null );
										$is_valid = 'valid' === (string) $row['status'];
										$status_url = wp_nonce_url(
											add_query_arg(
												[
													'action' => 'rks_cd_status_legacy',
													'id'     => (int) $row['id'],
													'status' => $is_valid ? 'revoked' : 'valid',
												],
												admin_url( 'admin-post.php' )
											),
											self::NONCE
										);
										$delete_url = wp_nonce_url(
											add_query_arg( [ 'action' => 'rks_cd_delete_legacy', 'id' => (int) $row['id'] ], admin_url( 'admin-post.php' ) ),
											self::NONCE
										);
										$verify_url = add_query_arg( 'id', (string) $row['unique_id'], home_url( '/verify-certificate/' ) );
										?>
										<tr>
											<td>
												<code><?php echo esc_html( (string) $row['unique_id'] ); ?></code>
												<?php if ( ! empty( $meta['old_reference'] ) ) : ?>
													<br><small>Ref: <?php echo esc_html( (string) $meta['old_reference'] ); ?></small>
												<?php endif; ?>
												<br><small>Issued <?php echo esc_html( date( 'd M Y', strtotime( (string) $row['issue_date'] ) ) ); ?></small>
											</td>
											<td>
												<strong><?php echo esc_html( (string) $row['holder_name'] ); ?></strong>
												<?php if ( ! empty( $meta['father_name'] ) ) : ?>
													<br><small>Father: <?php echo esc_html( (string) $meta['father_name'] ); ?></small>
												<?php endif; ?>
											</td>
											<td><?php echo esc_html( (string) $row['course_title'] ); ?></td>
											<td><?php echo esc_html( $branch_names[ (int) $row['branch_id'] ] ?? '#' . (int) $row['branch_id'] ); ?></td>
											<td><span class="rkscd-status rkscd-status--<?php echo $is_valid ? 'valid' : 'revoked'; ?>"><?php echo esc_html( ucfirst( (string) $row['status'] ) ); ?></span></td>
											<td class="rkscd-legacy-actions">
												<a class="button button-small" href="<?php echo esc_url( $verify_url ); ?>" target="_blank" rel="noopener">Verify</a>
												<a class="button button-small" href="<?php echo esc_url( $status_url ); ?>"><?php echo $is_valid ? 'Revoke' : 'Restore'; ?></a>
												<a class="button button-small button-link-delete" onclick="return confirm('Delete this offline certificate entry? Verification will stop working for this ID.');" href="<?php echo esc_url( $delete_url ); ?>">Delete</a>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						<?php endif; ?>
					</div>
				</div>
			</div>
			<script>
			jQuery(function($){
				var frame;
				$('#rkscd-legacy-pick-photo').on('click', function(e){
					e.preventDefault();
					if (frame) {
						frame.open();
						return;
					}
					frame = wp.media({
						title: 'Select student photo',
						button: { text: 'Use Photo' },
						multiple: false,
						library: { type: 'image' }
					});
					frame.on('select', function(){
						var att = frame.state().get('selection').first().toJSON();
						$('#rkscd-legacy-photo-url').val(att.url || '');
					});
					frame.open();
				});
			});
			</script>
		</div>
		<?php
	}

	private function render_editor(): void {
		$id = (int) ( $_GET['edit'] ?? 0 );
		$design = null;
		if ( $id > 0 && get_post_type( $id ) === self::CPT ) {
			$design = self::format_design( get_post( $id ) );
		}
		$title = $design['title'] ?? 'New Certificate Design';
		$bg_id = $design['bg_id'] ?? 0;
		$bg_url = $design['bg_url'] ?? '';
		$canvas_w = $design['canvas_w'] ?? 1240;
		$canvas_h = $design['canvas_h'] ?? 877;
		$fields = $design['fields'] ?? [];

		$vars   = self::variables();
		$this->inline_css();
		?>
		<div class="wrap rkscd-wrap rkscd-wrap--editor">
			<div class="rkscd-hero">
				<div class="rkscd-hero__icon"><span class="dashicons dashicons-awards"></span></div>
				<div>
					<small>Drag &amp; Drop Editor</small>
					<h1><?php echo esc_html( $title ); ?></h1>
					<p>Drag variables from the right panel onto the canvas. Click any placed field to edit its font, size and color. Click <strong>Save Design</strong> when done.</p>
				</div>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::SLUG ) ); ?>" class="button" style="margin-left:auto;">
					<span class="dashicons dashicons-arrow-left-alt" style="vertical-align:text-bottom;"></span> Back to Designs
				</a>
			</div>

			<form id="rkscd-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="rks_cd_save">
				<?php wp_nonce_field( self::NONCE ); ?>
				<input type="hidden" name="design_id" value="<?php echo (int) $id; ?>">
				<input type="hidden" name="bg_id"     id="rkscd-bg-id"     value="<?php echo (int) $bg_id; ?>">
				<input type="hidden" name="canvas_w"  id="rkscd-canvas-w"  value="<?php echo (int) $canvas_w; ?>">
				<input type="hidden" name="canvas_h"  id="rkscd-canvas-h"  value="<?php echo (int) $canvas_h; ?>">
				<input type="hidden" name="fields_json" id="rkscd-fields-json" value="<?php echo esc_attr( wp_json_encode( $fields ) ); ?>">

				<div class="rkscd-editor">

					<!-- Left: Canvas -->
					<div class="rkscd-editor__main">
						<div class="rkscd-editor__top">
							<label>
								<strong>Design title</strong>
								<input type="text" name="title" value="<?php echo esc_attr( $title ); ?>" required style="margin-left:8px;min-width:280px;">
							</label>
							<button type="button" id="rkscd-pick-bg" class="button">
								<span class="dashicons dashicons-format-image" style="vertical-align:text-bottom;"></span>
								<?php echo $bg_id ? 'Change Background' : 'Upload Background'; ?>
							</button>
							<button type="submit" class="button button-primary">Save Design</button>
						</div>

						<div class="rkscd-canvas-wrap">
							<div class="rkscd-canvas <?php echo $bg_url ? 'has-bg' : ''; ?>" id="rkscd-canvas" data-aspect="<?php echo esc_attr( (string) ( $canvas_h > 0 ? $canvas_w / $canvas_h : 1.414 ) ); ?>" style="<?php echo $bg_url && $canvas_h > 0 ? 'aspect-ratio:' . (int) $canvas_w . '/' . (int) $canvas_h . ';' : ''; ?>">
								<img id="rkscd-bg" src="<?php echo esc_url( $bg_url ); ?>" alt="" <?php echo $bg_url ? '' : 'style="display:none"'; ?>>
								<div class="rkscd-canvas__empty" <?php echo $bg_url ? 'style="display:none"' : ''; ?>>
									<span class="dashicons dashicons-format-image"></span>
									<p>Upload a certificate background image to start.</p>
									<p><small>Recommended: 1240×877 JPG/PNG (A4 landscape at 150 DPI).</small></p>
								</div>
								<!-- Field chips inserted by JS -->
							</div>
							<p class="description" style="margin-top:8px;">All positions stored as % of the image — your design scales perfectly on any screen.</p>
						</div>
					</div>

					<!-- Right: Variables + Selected field panel -->
					<aside class="rkscd-editor__side">

						<div class="rkscd-side-card">
							<h3><span class="dashicons dashicons-move"></span> Drag Variables</h3>
							<p class="rkscd-card__sub">Drag any chip onto the canvas. Drop where you want it to appear.</p>
							<div class="rkscd-vars">
								<?php foreach ( $vars as $key => $info ) : ?>
									<span class="rkscd-var" draggable="true"
										data-key="<?php echo esc_attr( $key ); ?>"
										data-sample="<?php echo esc_attr( $info['sample'] ); ?>"
										data-type="<?php echo esc_attr( $info['type'] ); ?>">
										<?php if ( $info['type'] === 'image' ) : ?>
											<span class="dashicons dashicons-format-image"></span>
										<?php else : ?>
											<span class="dashicons dashicons-tag"></span>
										<?php endif; ?>
										<?php echo esc_html( $info['label'] ); ?>
										<small>{<?php echo esc_html( $key ); ?>}</small>
									</span>
								<?php endforeach; ?>
							</div>
						</div>

						<div class="rkscd-side-card" id="rkscd-selected-card" style="display:none;">
							<h3><span class="dashicons dashicons-edit"></span> <span id="rkscd-selected-label">Selected Field</span></h3>
							<div class="rkscd-fld">
								<label>Font size (px)</label>
								<input type="number" id="rkscd-fld-size" min="6" max="200" step="1">
							</div>
							<div class="rkscd-fld">
								<label>Color</label>
								<input type="color" id="rkscd-fld-color">
							</div>
							<div class="rkscd-fld">
								<label>Width (% of canvas)</label>
								<input type="number" id="rkscd-fld-w" min="1" max="100" step="1">
							</div>
							<div class="rkscd-fld">
								<label>Font</label>
								<?php
								$fonts_grouped = [];
								foreach ( self::fonts() as $stack => $info ) {
									$fonts_grouped[ $info['group'] ][ $stack ] = $info['label'];
								}
								?>
								<select id="rkscd-fld-font">
									<?php foreach ( $fonts_grouped as $group => $items ) : ?>
										<optgroup label="<?php echo esc_attr( $group ); ?>">
											<?php foreach ( $items as $stack => $label ) : ?>
												<option value="<?php echo esc_attr( $stack ); ?>" style="font-family: <?php echo esc_attr( $stack ); ?>;">
													<?php echo esc_html( $label ); ?>
												</option>
											<?php endforeach; ?>
										</optgroup>
									<?php endforeach; ?>
								</select>
								<p class="description" style="margin-top:6px;font-size:11px;color:#7a8a90;">Calligraphy &amp; script fonts work great for names and signatures.</p>
							</div>
							<div class="rkscd-fld rkscd-fld--row">
								<label class="rkscd-tog"><input type="checkbox" id="rkscd-fld-bold"> Bold</label>
								<label class="rkscd-tog"><input type="checkbox" id="rkscd-fld-italic"> Italic</label>
							</div>
							<div class="rkscd-fld">
								<label>Align</label>
								<div class="rkscd-align">
									<button type="button" data-a="left">Left</button>
									<button type="button" data-a="center">Center</button>
									<button type="button" data-a="right">Right</button>
								</div>
							</div>
							<button type="button" class="button button-link-delete" id="rkscd-fld-remove" style="margin-top:8px;">Remove field</button>
						</div>
					</aside>
				</div>
			</form>
		</div>

		<?php $this->inline_js( array_keys( $vars ), $vars ); ?>
		<?php
	}

	/* ------------------------------------------------------------------ */
	/* Inline styles & JS                                                  */
	/* ------------------------------------------------------------------ */

	private function inline_css(): void { ?>
		<style>
			.rkscd-wrap { max-width: 1280px; margin: 18px 20px 40px 0; font-family: -apple-system, system-ui, "Segoe UI", sans-serif; }
			.rkscd-hero {
				background: linear-gradient(135deg, #2C687B 0%, #1f4d5b 100%);
				color: #fff; border-radius: 12px; padding: 22px 26px;
				margin-bottom: 22px; display: flex; align-items: center; gap: 18px;
				box-shadow: 0 12px 28px rgba(31,77,91,0.22);
			}
			.rkscd-hero__icon {
				width: 56px; height: 56px; border-radius: 14px;
				background: rgba(255,255,255,0.16);
				display: inline-flex; align-items: center; justify-content: center;
				color: #8EC9C4; flex-shrink: 0;
			}
			.rkscd-hero__icon .dashicons { font-size: 30px; width: 30px; height: 30px; }
			.rkscd-hero h1 { margin: 0 0 2px; color: #fff; font-size: 22px; }
			.rkscd-hero small { display: block; color: #8EC9C4; font-size: 11px; text-transform: uppercase; letter-spacing: 1.2px; font-weight: 700; margin-bottom: 4px; }
			.rkscd-hero p { margin: 0; color: rgba(255,255,255,0.85); font-size: 13.5px; max-width: 760px; }

			.rkscd-notice {
				padding: 12px 16px; border-radius: 8px;
				border-left: 4px solid #00a32a; background: #f0fff4;
				color: #1f5b30; font-size: 13.5px; margin-bottom: 16px; font-weight: 500;
			}
			.rkscd-card {
				background: #fff; border: 1px solid #e3e9eb; border-radius: 12px;
				padding: 22px 24px; margin-bottom: 18px;
				box-shadow: 0 2px 8px rgba(44,104,123,0.05);
			}
			.rkscd-card h2 { margin: 0 0 4px; font-size: 16px; color: #2C687B; display: flex; align-items: center; gap: 8px; }
			.rkscd-card h2 .dashicons { color: #E63946; font-size: 18px; width: 18px; height: 18px; }
			.rkscd-card__sub { color: #7a8a90; font-size: 12.5px; margin: 0 0 14px; }
			.rkscd-card__head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
			.rkscd-card__head h2 { margin: 0; }

			.rkscd-row { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
			.rkscd-row input[type="text"] {
				flex: 1; max-width: 320px;
				padding: 8px 12px; border-radius: 8px; border: 1.5px solid rgba(44,104,123,0.2);
				font-size: 13.5px; text-transform: uppercase;
			}
			.rkscd-row .button-primary { background: #E63946; border-color: #E63946; }
			.rkscd-row .button-primary:hover { background: #c52d39; border-color: #c52d39; }

			/* Designs grid */
			.rkscd-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px; }
			.rkscd-tile {
				background: #fff; border: 2px solid #e3e9eb; border-radius: 12px;
				overflow: hidden; transition: border-color .2s ease, transform .2s ease;
			}
			.rkscd-tile:hover { transform: translateY(-2px); border-color: #8EC9C4; }
			.rkscd-tile.is-active { border-color: #E63946; box-shadow: 0 10px 24px rgba(230,57,70,0.18); }
			.rkscd-tile__thumb {
				position: relative; aspect-ratio: 1.414 / 1;
				background: linear-gradient(135deg, #FBF3E9, #fff);
				display: flex; align-items: center; justify-content: center;
				overflow: hidden;
			}
			.rkscd-tile__thumb img { max-width: 100%; max-height: 100%; display: block; }
			.rkscd-tile__nobg { color: #a0aab0; font-size: 12px; font-style: italic; }
			.rkscd-mini-preview {
				position: relative; width: 100%; overflow: hidden; container-type: inline-size;
				background: #fffdf8; isolation: isolate;
			}
			.rkscd-mini-preview__bg {
				position: absolute; inset: 0; width: 100%; height: 100%; object-fit: contain;
				z-index: 1; pointer-events: none;
			}
			.rkscd-mini-preview__field {
				position: absolute; z-index: 2; transform: translate(-50%, -50%);
				box-sizing: border-box; line-height: 1.12; white-space: nowrap; overflow: hidden;
			}
			.rkscd-mini-preview__field--media { overflow: visible; }
			.rkscd-mini-preview__photo {
				display: flex; align-items: center; justify-content: center; width: 100%; aspect-ratio: 3 / 4;
				background: linear-gradient(135deg, #eef6f7, #dcebed); border: 1px solid rgba(44,104,123,.32);
				color: #2C687B;
			}
			.rkscd-mini-preview__photo .dashicons { width: 40%; height: auto; font-size: clamp(9px,4cqw,22px); }
			.rkscd-mini-preview__qr {
				display: block; width: 100%; aspect-ratio: 1; border: 2px solid #fff;
				background: repeating-conic-gradient(#17242a 0 25%, #fff 0 50%) 50% / 5px 5px;
				box-shadow: 0 0 0 1px rgba(23,36,42,.2);
			}
			.rkscd-tile__badge {
				position: absolute; top: 8px; right: 8px;
				background: #E63946; color: #fff;
				padding: 3px 9px; border-radius: 999px;
				font-size: 10px; font-weight: 800; letter-spacing: 0.06em;
				box-shadow: 0 4px 10px rgba(230,57,70,0.32);
			}
			.rkscd-tile__body {
				padding: 12px 14px 6px;
				display: flex; flex-direction: column; gap: 2px;
			}
			.rkscd-tile__body strong { color: #2C687B; font-size: 14px; }
			.rkscd-tile__body small { color: #7a8a90; font-size: 11px; }
			.rkscd-tile__actions {
				display: flex; gap: 6px; flex-wrap: wrap;
				padding: 8px 14px 14px;
			}
			.rkscd-tile__actions .button { padding: 4px 10px; font-size: 12px; }

			.rkscd-empty {
				text-align: center; padding: 30px;
				background: #FBF3E9; border-radius: 10px;
				color: #7a4f10;
			}

			/* Sectioned list page */
			.rkscd-section {
				background: #fff; border: 1px solid #e3e9eb; border-radius: 14px;
				padding: 22px 26px; margin-bottom: 22px;
				box-shadow: 0 2px 10px rgba(44,104,123,0.05);
			}
			.rkscd-section__head { position: relative; margin-bottom: 18px; padding-right: 200px; }
			.rkscd-section__head h2 {
				margin: 0 0 4px; font-size: 18px; color: #2C687B;
				display: flex; align-items: center; gap: 8px;
			}
			.rkscd-section__head h2 .dashicons { color: #E63946; font-size: 20px; width: 20px; height: 20px; }
			.rkscd-section__head p { margin: 0; color: #7a8a90; font-size: 13px; line-height: 1.55; }
			.rkscd-section__cta {
				position: absolute; top: 0; right: 0;
				background: #E63946; border-color: #E63946;
				box-shadow: 0 6px 14px rgba(230,57,70,0.25);
			}
			.rkscd-section__cta:hover { background: #c52d39; border-color: #c52d39; }
			.rkscd-step {
				display: inline-block;
				font-size: 10.5px; font-weight: 800;
				letter-spacing: 0.14em; text-transform: uppercase;
				background: rgba(230,57,70,0.12); color: #E63946;
				padding: 3px 9px; border-radius: 999px;
				margin-bottom: 6px;
			}

			/* Active picker */
			.rkscd-picker {
				display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
				gap: 14px;
			}
			.rkscd-pick {
				display: flex; flex-direction: column;
				background: #fff; border: 2px solid #e3e9eb;
				border-radius: 12px; padding: 14px;
				transition: border-color .18s ease, transform .15s ease, box-shadow .18s ease;
			}
			.rkscd-pick:hover { border-color: #8EC9C4; transform: translateY(-1px); }
			.rkscd-pick.is-active {
				border-color: #E63946;
				box-shadow: 0 10px 22px rgba(230,57,70,0.16);
				background: linear-gradient(135deg, #fff, #fff7f3);
			}
			.rkscd-pick__thumb {
				width: 100%; aspect-ratio: 1.414 / 1;
				background: linear-gradient(135deg, #FBF3E9, #fff);
				border-radius: 8px; overflow: hidden;
				display: flex; align-items: center; justify-content: center;
				margin-bottom: 12px;
				border: 1px solid rgba(44,104,123,0.10);
			}
			.rkscd-pick__thumb img { max-width: 100%; max-height: 100%; display: block; }
			.rkscd-pick__thumb--default {
				flex-direction: column; gap: 6px;
				color: #2C687B; font-weight: 600; font-size: 12px;
				background: linear-gradient(135deg, #f5e7d0, #fff4dc);
			}
			.rkscd-pick__thumb--default .dashicons { font-size: 36px; width: 36px; height: 36px; color: #E63946; }
			.rkscd-pick__nobg { color: #a0aab0; font-size: 12px; font-style: italic; }
			.rkscd-pick__body strong { display: block; color: #2C687B; font-size: 14px; margin-bottom: 2px; }
			.rkscd-pick__body small { display: block; color: #7a8a90; font-size: 11.5px; }
			.rkscd-pick__actions { margin-top: 10px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
			.rkscd-pick__actions .button-primary {
				background: #E63946; border-color: #E63946;
				font-weight: 600;
				box-shadow: 0 4px 10px rgba(230,57,70,0.22);
			}
			.rkscd-pick__actions .button-primary:hover { background: #c52d39; border-color: #c52d39; }
			.rkscd-pick__active-tag {
				display: inline-flex; align-items: center; gap: 5px;
				font-size: 11px; font-weight: 800;
				letter-spacing: 0.1em;
				background: #E63946; color: #fff;
				padding: 5px 12px; border-radius: 999px;
				box-shadow: 0 4px 10px rgba(230,57,70,0.32);
			}
			.rkscd-pick__active-tag::before {
				content: ''; display: inline-block;
				width: 6px; height: 6px; border-radius: 50%;
				background: #fff;
			}

			/* Prefix */
			.rkscd-prefix-card {
				display: grid; grid-template-columns: 1fr 1fr; gap: 18px; align-items: stretch;
			}
			@media (max-width: 900px) { .rkscd-prefix-card { grid-template-columns: 1fr; } }
			.rkscd-prefix-card .rkscd-row {
				background: linear-gradient(135deg, #FBF3E9, #fff);
				border-radius: 10px; padding: 16px 18px;
				border: 1px solid rgba(44,104,123,0.12);
			}
			.rkscd-prefix-label {
				display: flex; flex-direction: column; gap: 4px;
				flex: 1;
			}
			.rkscd-prefix-label span {
				font-size: 11px; font-weight: 700;
				letter-spacing: 0.06em; text-transform: uppercase;
				color: #2C687B;
			}
			.rkscd-prefix-label input {
				font-family: ui-monospace, monospace;
				font-size: 15px; font-weight: 700;
			}
			.rkscd-prefix-note {
				display: flex; gap: 12px;
				background: #fff8e1;
				border-left: 4px solid #d49a1e;
				border-radius: 8px; padding: 14px 16px;
			}
			.rkscd-prefix-note .dashicons {
				color: #d49a1e; font-size: 22px; width: 22px; height: 22px;
				flex-shrink: 0; margin-top: 2px;
			}
			.rkscd-prefix-note strong { display: block; color: #7a4f10; font-size: 13.5px; margin-bottom: 2px; }
			.rkscd-prefix-note small { color: #8a6d1c; font-size: 12.5px; line-height: 1.55; }
			.rkscd-notice--error {
				background: #fff2f2;
				color: #a62531;
				border-left-color: #E63946;
			}
			.rkscd-nav {
				display: flex;
				flex-wrap: wrap;
				gap: 8px;
				margin: -8px 0 18px;
			}
			.rkscd-nav a {
				display: inline-flex;
				align-items: center;
				gap: 7px;
				padding: 9px 13px;
				border: 1px solid rgba(44,104,123,0.18);
				border-radius: 8px;
				background: #fff;
				color: #2C687B;
				text-decoration: none;
				font-weight: 700;
				box-shadow: 0 2px 8px rgba(44,104,123,0.04);
			}
			.rkscd-nav a.is-active {
				background: #E63946;
				border-color: #E63946;
				color: #fff;
				box-shadow: 0 8px 16px rgba(230,57,70,0.20);
			}
			.rkscd-nav .dashicons {
				font-size: 16px;
				width: 16px;
				height: 16px;
			}
			.rkscd-stat-grid {
				display: grid;
				grid-template-columns: repeat(4, minmax(140px, 1fr));
				gap: 12px;
				margin: 0 0 18px;
			}
			.rkscd-stat-grid div {
				background: #fff;
				border: 1px solid rgba(44,104,123,0.14);
				border-radius: 10px;
				padding: 14px 16px;
				box-shadow: 0 2px 8px rgba(44,104,123,0.05);
			}
			.rkscd-stat-grid small {
				display: block;
				color: #71838a;
				font-weight: 800;
				text-transform: uppercase;
				font-size: 10.5px;
				letter-spacing: 0.08em;
			}
			.rkscd-stat-grid strong {
				display: block;
				margin-top: 5px;
				color: #2C687B;
				font-size: 25px;
				line-height: 1;
			}
			@media (max-width: 900px) { .rkscd-stat-grid { grid-template-columns: repeat(2, 1fr); } }
			.rkscd-filters {
				display: grid;
				grid-template-columns: minmax(220px, 1fr) 180px 180px auto auto;
				gap: 10px;
				align-items: end;
				margin-bottom: 14px;
			}
			.rkscd-filters label,
			.rkscd-prefix-rule-form label {
				display: flex;
				flex-direction: column;
				gap: 5px;
			}
			.rkscd-filters label span,
			.rkscd-prefix-rule-form label span {
				font-size: 10.5px;
				font-weight: 800;
				letter-spacing: 0.06em;
				text-transform: uppercase;
				color: #2C687B;
			}
			.rkscd-filters input,
			.rkscd-filters select,
			.rkscd-prefix-rule-form input,
			.rkscd-prefix-rule-form select {
				width: 100%;
				max-width: none;
				border: 1.5px solid rgba(44,104,123,0.18);
				border-radius: 7px;
				padding: 7px 10px;
				box-sizing: border-box;
			}
			@media (max-width: 1000px) { .rkscd-filters { grid-template-columns: 1fr 1fr; } }
			.rkscd-cert-table code {
				background: #f0f4f5;
				padding: 2px 5px;
				border-radius: 4px;
				font-size: 12px;
			}
			.rkscd-cert-table small {
				color: #71838a;
			}
			.rkscd-source {
				display: inline-flex;
				border-radius: 999px;
				padding: 4px 9px;
				font-size: 11px;
				font-weight: 800;
				text-transform: uppercase;
				letter-spacing: 0.04em;
			}
			.rkscd-source--system {
				background: #e4f2ff;
				color: #176085;
			}
			.rkscd-source--offline {
				background: #fff0d7;
				color: #9a5c00;
			}
			.rkscd-prefix-rule-form {
				display: grid;
				grid-template-columns: repeat(4, minmax(150px, 1fr));
				gap: 12px;
				align-items: end;
			}
			.rkscd-prefix-rule-form .rkscd-check,
			.rkscd-prefix-rule-form__actions {
				grid-column: 1 / -1;
			}
			.rkscd-check {
				flex-direction: row !important;
				align-items: center;
			}
			.rkscd-check input {
				width: auto;
			}
			.rkscd-prefix-rule-form__actions {
				display: flex;
				gap: 8px;
				flex-wrap: wrap;
			}
			@media (max-width: 1000px) { .rkscd-prefix-rule-form { grid-template-columns: 1fr 1fr; } }
			@media (max-width: 650px) { .rkscd-prefix-rule-form { grid-template-columns: 1fr; } }
			.rkscd-prefix-rules {
				display: grid;
				gap: 10px;
			}
			.rkscd-prefix-rule {
				display: flex;
				justify-content: space-between;
				gap: 14px;
				align-items: center;
				background: #fff;
				border: 1px solid rgba(44,104,123,0.16);
				border-radius: 10px;
				padding: 13px 14px;
			}
			.rkscd-prefix-rule.is-active {
				border-color: #E63946;
				box-shadow: 0 8px 18px rgba(230,57,70,0.12);
			}
			.rkscd-prefix-rule strong,
			.rkscd-prefix-rule code,
			.rkscd-prefix-rule small {
				display: block;
			}
			.rkscd-prefix-rule strong {
				color: #2C687B;
				margin-bottom: 4px;
			}
			.rkscd-prefix-rule code {
				width: fit-content;
				background: #f0f4f5;
				padding: 3px 6px;
				border-radius: 5px;
				margin-bottom: 4px;
			}
			.rkscd-prefix-rule small {
				color: #71838a;
			}
			.rkscd-prefix-rule__actions {
				display: flex;
				gap: 6px;
				flex-wrap: wrap;
				justify-content: flex-end;
			}

			/* Offline certificate registry */
			.rkscd-legacy {
				display: grid;
				grid-template-columns: minmax(320px, 0.85fr) minmax(520px, 1.4fr);
				gap: 18px;
				align-items: start;
			}
			@media (max-width: 1150px) { .rkscd-legacy { grid-template-columns: 1fr; } }
			.rkscd-legacy-form,
			.rkscd-legacy-list {
				background: linear-gradient(135deg, #FBF3E9, #fff);
				border: 1px solid rgba(44,104,123,0.12);
				border-radius: 12px;
				padding: 16px;
			}
			.rkscd-legacy-form {
				display: grid;
				grid-template-columns: 1fr 1fr;
				gap: 12px;
			}
			.rkscd-legacy-form label {
				display: flex;
				flex-direction: column;
				gap: 5px;
				margin: 0;
			}
			.rkscd-legacy-form label span {
				font-size: 10.5px;
				font-weight: 800;
				letter-spacing: 0.06em;
				text-transform: uppercase;
				color: #2C687B;
			}
			.rkscd-legacy-form input,
			.rkscd-legacy-form select,
			.rkscd-legacy-form textarea {
				width: 100%;
				max-width: none;
				border: 1.5px solid rgba(44,104,123,0.18);
				border-radius: 7px;
				padding: 7px 10px;
				background: #fff;
				box-sizing: border-box;
			}
			.rkscd-legacy-form__wide,
			.rkscd-legacy-form__actions {
				grid-column: 1 / -1;
			}
			.rkscd-legacy-form__actions {
				display: flex;
				gap: 8px;
				flex-wrap: wrap;
				align-items: center;
				padding-top: 4px;
			}
			.rkscd-media-row {
				display: flex;
				gap: 8px;
				align-items: center;
			}
			.rkscd-media-row input {
				flex: 1;
			}
			.rkscd-legacy-form__actions .button-primary {
				background: #E63946;
				border-color: #E63946;
				box-shadow: 0 6px 14px rgba(230,57,70,0.22);
			}
			@media (max-width: 782px) { .rkscd-legacy-form { grid-template-columns: 1fr; } }
			.rkscd-legacy-list h3 {
				margin: 0 0 12px;
				color: #2C687B;
				font-size: 15px;
			}
			.rkscd-legacy-list table {
				background: #fff;
				border-radius: 8px;
				overflow: hidden;
			}
			.rkscd-legacy-list code {
				background: #f0f4f5;
				padding: 2px 5px;
				border-radius: 4px;
				font-size: 12px;
			}
			.rkscd-legacy-list small {
				color: #71838a;
			}
			.rkscd-status {
				display: inline-flex;
				align-items: center;
				border-radius: 999px;
				padding: 4px 9px;
				font-size: 11px;
				font-weight: 800;
				letter-spacing: 0.04em;
				text-transform: uppercase;
			}
			.rkscd-status--valid {
				background: #dcf8d8;
				color: #297a22;
			}
			.rkscd-status--revoked {
				background: #ffe1e1;
				color: #b32632;
			}
			.rkscd-legacy-actions {
				display: flex;
				gap: 5px;
				flex-wrap: wrap;
			}

			/* Editor layout */
			.rkscd-wrap--editor { max-width: 1500px; }
			.rkscd-editor {
				display: grid;
				grid-template-columns: 1fr 320px;
				gap: 18px;
			}
			@media (max-width: 1100px) { .rkscd-editor { grid-template-columns: 1fr; } }

			.rkscd-editor__main {
				background: #fff; border: 1px solid #e3e9eb; border-radius: 12px;
				padding: 16px; box-shadow: 0 2px 8px rgba(44,104,123,0.05);
			}
			.rkscd-editor__top {
				display: flex; gap: 10px; align-items: center; flex-wrap: wrap;
				margin-bottom: 12px;
			}
			.rkscd-editor__top label strong { color: #2C687B; font-size: 13px; }
			.rkscd-editor__top input[type="text"] {
				padding: 7px 10px;
				border-radius: 6px;
				border: 1.5px solid rgba(44,104,123,0.18);
				font-size: 13.5px;
			}
			.rkscd-editor__top .button-primary {
				background: #E63946; border-color: #E63946;
				margin-left: auto;
				box-shadow: 0 6px 14px rgba(230,57,70,0.28);
			}

			.rkscd-canvas-wrap { background: repeating-conic-gradient(#f5f5f5 0% 25%, #fff 0% 50%) 50% / 24px 24px; border-radius: 10px; padding: 14px; }
			.rkscd-canvas {
				position: relative;
				width: 100%;
				max-width: 1280px;
				margin: 0 auto;
				background: #fff;
				box-shadow: 0 18px 42px rgba(0,0,0,0.18);
				border-radius: 4px;
				overflow: hidden;
				user-select: none;
				/* aspect-ratio set inline once bg loads; default A4 landscape */
			}
			.rkscd-canvas:not(.has-bg) { aspect-ratio: 1.414 / 1; }
			.rkscd-canvas img#rkscd-bg {
				display: block;
				width: 100%;
				height: 100%;
				object-fit: contain;
				position: absolute;
				inset: 0;
			}
			.rkscd-canvas__empty {
				aspect-ratio: 1.414 / 1;
				display: flex; flex-direction: column;
				align-items: center; justify-content: center;
				color: #a0aab0; text-align: center; padding: 30px;
			}
			.rkscd-canvas__empty .dashicons { font-size: 64px; width: 64px; height: 64px; opacity: 0.4; margin-bottom: 10px; }

			.rkscd-fchip {
				position: absolute;
				transform: translate(-50%, -50%);
				padding: 4px 6px;
				background: rgba(230,57,70,0.06);
				border: 1.5px dashed rgba(230,57,70,0.55);
				color: #1f2a30;
				cursor: move;
				white-space: nowrap;
				border-radius: 4px;
				box-sizing: border-box;
				text-align: center;
				min-width: 60px;
				line-height: 1.15;
			}
			.rkscd-fchip:hover { background: rgba(230,57,70,0.10); }
			.rkscd-fchip.is-selected {
				border-style: solid;
				border-color: #E63946;
				background: rgba(230,57,70,0.10);
				box-shadow: 0 4px 14px rgba(230,57,70,0.18);
			}
			.rkscd-fchip__img {
				display: block;
				width: 100%;
				height: 100%;
				min-height: 60px;
				background: rgba(31,77,91,0.08);
				border: 1px dashed rgba(31,77,91,0.4);
				display: flex; align-items: center; justify-content: center;
				color: #2C687B; font-size: 11px; font-weight: 600;
				border-radius: 2px;
			}

			/* Side panel */
			.rkscd-editor__side { display: flex; flex-direction: column; gap: 14px; }
			.rkscd-side-card {
				background: #fff; border: 1px solid #e3e9eb; border-radius: 12px;
				padding: 16px 18px; box-shadow: 0 2px 8px rgba(44,104,123,0.05);
			}
			.rkscd-side-card h3 {
				margin: 0 0 8px; font-size: 14px; color: #2C687B;
				display: flex; align-items: center; gap: 6px;
			}
			.rkscd-side-card h3 .dashicons { color: #E63946; font-size: 16px; width: 16px; height: 16px; }

			.rkscd-vars { display: flex; flex-direction: column; gap: 6px; }
			.rkscd-var {
				display: flex; align-items: center; gap: 8px;
				padding: 8px 12px;
				background: linear-gradient(135deg, #FBF3E9, #fff);
				border: 1px solid rgba(44,104,123,0.16);
				border-radius: 8px;
				cursor: grab;
				font-size: 13px; font-weight: 600;
				color: #2C687B;
				transition: border-color .15s ease, transform .12s ease, box-shadow .15s ease;
			}
			.rkscd-var:active { cursor: grabbing; }
			.rkscd-var:hover {
				border-color: #E63946;
				transform: translateX(2px);
				box-shadow: 0 4px 10px rgba(230,57,70,0.10);
			}
			.rkscd-var .dashicons { color: #E63946; font-size: 16px; width: 16px; height: 16px; }
			.rkscd-var small {
				margin-left: auto; color: #7a8a90; font-size: 10.5px;
				font-family: ui-monospace, monospace; font-weight: 500;
			}

			.rkscd-fld { margin-bottom: 10px; }
			.rkscd-fld label { display: block; font-size: 11.5px; font-weight: 700; color: #4a5b62; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.04em; }
			.rkscd-fld input[type="number"],
			.rkscd-fld input[type="color"],
			.rkscd-fld select {
				width: 100%; padding: 7px 10px;
				border: 1.5px solid rgba(44,104,123,0.18);
				border-radius: 6px; font-size: 13px;
				box-sizing: border-box;
			}
			.rkscd-fld input[type="color"] { padding: 2px; height: 36px; cursor: pointer; }
			.rkscd-fld--row { display: flex; gap: 14px; }
			.rkscd-tog { display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; font-weight: 600; color: #2C687B; }
			.rkscd-align { display: flex; gap: 4px; }
			.rkscd-align button {
				flex: 1; padding: 6px 4px;
				background: #f5f8f9; border: 1px solid rgba(44,104,123,0.18);
				border-radius: 6px; cursor: pointer;
				font-size: 12px; font-weight: 600; color: #2C687B;
			}
			.rkscd-align button.is-active { background: #E63946; color: #fff; border-color: #E63946; }

			/* Mouse-tracked drag ghost (sticks to cursor) */
			.rkscd-drag-ghost {
				transform: translate(-50%, -50%);
				background: #E63946;
				color: #fff;
				font-size: 13px;
				font-weight: 700;
				padding: 8px 14px;
				border-radius: 8px;
				box-shadow: 0 12px 28px rgba(230,57,70,0.36);
				white-space: nowrap;
				letter-spacing: 0.02em;
				opacity: 0.95;
			}
			.rkscd-canvas.is-drop-target {
				outline: 3px dashed #E63946;
				outline-offset: -3px;
			}
			.rkscd-var { user-select: none; -webkit-user-drag: none; }
		</style>
		<?php
	}

	private function inline_js( array $var_keys, array $vars ): void {
		$vars_js = wp_json_encode( $vars );
		?>
		<script>
		(function($){
			'use strict';
			var VARS = <?php echo $vars_js; ?>;
			var $canvas = $('#rkscd-canvas');
			var $bg     = $('#rkscd-bg');
			var $empty  = $('.rkscd-canvas__empty');
			var $bgId   = $('#rkscd-bg-id');
			var $cw     = $('#rkscd-canvas-w');
			var $ch     = $('#rkscd-canvas-h');
			var $store  = $('#rkscd-fields-json');

			var fields = [];
			try { fields = JSON.parse($store.val() || '[]') || []; } catch(e) { fields = []; }
			var selectedIdx = -1;

			// ----- Apply aspect-ratio to canvas based on bg image -----
			function applyAspect(w, h) {
				if (!w || !h) return;
				$canvas.addClass('has-bg').css('aspect-ratio', w + ' / ' + h);
			}
			// If bg already present on load, set ratio after image natural-loads
			if ($bg.attr('src')) {
				var imgEl = $bg[0];
				if (imgEl.complete && imgEl.naturalWidth) {
					applyAspect(imgEl.naturalWidth, imgEl.naturalHeight);
				} else {
					$bg.on('load', function(){
						applyAspect(this.naturalWidth, this.naturalHeight);
					});
				}
			}

			// ----- Background picker -----
			var frame;
			$('#rkscd-pick-bg').on('click', function(e){
				e.preventDefault();
				if (frame) { frame.open(); return; }
				frame = wp.media({
					title: 'Select certificate background image',
					button: { text: 'Use this image' },
					multiple: false,
					library: { type: 'image' }
				});
				frame.on('select', function(){
					var att = frame.state().get('selection').first().toJSON();
					$bgId.val(att.id);
					$bg.attr('src', att.url).show();
					$empty.hide();
					if (att.width && att.height) {
						$cw.val(att.width);
						$ch.val(att.height);
						applyAspect(att.width, att.height);
					} else {
						// Fallback: read from naturalWidth after load
						$bg.one('load', function(){
							if (this.naturalWidth && this.naturalHeight) {
								$cw.val(this.naturalWidth);
								$ch.val(this.naturalHeight);
								applyAspect(this.naturalWidth, this.naturalHeight);
							}
						});
					}
				});
				frame.open();
			});

			// ----- Render placed fields -----
			function render() {
				$canvas.find('.rkscd-fchip').remove();
				fields.forEach(function(f, i){
					var sample = (VARS[f.key] && VARS[f.key].sample) || '';
					var label  = (VARS[f.key] && VARS[f.key].label) || f.key;
					var type   = (VARS[f.key] && VARS[f.key].type) || 'text';
					var content;
					if (type === 'image') {
						content = '<span class="rkscd-fchip__img">' + label + '</span>';
					} else {
						content = sample || ('{' + f.key + '}');
					}
					var $chip = $('<div class="rkscd-fchip" data-i="' + i + '">' + content + '</div>');
					$chip.css({
						left:        f.x + '%',
						top:         f.y + '%',
						width:       f.w + '%',
						fontSize:    f.font_size + 'px',
						color:       f.color,
						fontFamily:  f.font_family,
						fontWeight:  f.font_weight,
						fontStyle:   f.font_style,
						textAlign:   f.align,
					});
					if (selectedIdx === i) $chip.addClass('is-selected');
					$canvas.append($chip);
				});
				$store.val(JSON.stringify(fields));
				updateSidePanel();
			}

			// ----- Drag from var palette onto canvas (mouse-tracking — chip follows cursor) -----
			// Disable native HTML5 drag so we can pin a custom ghost element directly at cursor.
			$('.rkscd-var').attr('draggable', 'false');

			var paletteDrag = null;
			$(document).on('mousedown', '.rkscd-var', function(e){
				if (e.button !== 0) return;
				var key = $(this).data('key');
				if (!key || !VARS[key]) return;
				e.preventDefault();
				var label = VARS[key].label;
				var $ghost = $('<div class="rkscd-drag-ghost"></div>').text(label);
				$ghost.css({
					position: 'fixed',
					left: e.clientX + 'px',
					top:  e.clientY + 'px',
					pointerEvents: 'none',
					zIndex: 999999
				}).appendTo('body');
				paletteDrag = { key: key, $ghost: $ghost };
			});
			$(document).on('mousemove', function(e){
				if (!paletteDrag) return;
				paletteDrag.$ghost.css({ left: e.clientX + 'px', top: e.clientY + 'px' });
				// Hover feedback on canvas
				var r = $canvas[0].getBoundingClientRect();
				var over = e.clientX >= r.left && e.clientX <= r.right && e.clientY >= r.top && e.clientY <= r.bottom;
				$canvas.toggleClass('is-drop-target', over);
			});
			$(document).on('mouseup', function(e){
				if (!paletteDrag) return;
				var key = paletteDrag.key;
				paletteDrag.$ghost.remove();
				$canvas.removeClass('is-drop-target');
				paletteDrag = null;

				var r = $canvas[0].getBoundingClientRect();
				var inside = e.clientX >= r.left && e.clientX <= r.right && e.clientY >= r.top && e.clientY <= r.bottom;
				if (!inside) return;
				var x = ((e.clientX - r.left) / r.width)  * 100;
				var y = ((e.clientY - r.top)  / r.height) * 100;
				var type = VARS[key].type;
				fields.push({
					key: key,
					x: Math.max(0, Math.min(100, x)),
					y: Math.max(0, Math.min(100, y)),
					w: type === 'image' ? 12 : 40,
					font_size: type === 'image' ? 48 : 28,
					color: '#1a1a1a',
					font_family: "'Playfair Display', Georgia, serif",
					font_weight: 'normal',
					font_style: 'normal',
					align: 'center'
				});
				selectedIdx = fields.length - 1;
				render();
			});

			// ----- Drag-move placed chips -----
			// IMPORTANT: never re-render while a chip is being interacted with — that
			// destroys the live DOM ref and the chip "disappears" visually. Update only
			// inline styles during drag, then re-render on release.
			var dragInfo = null;
			$canvas.on('mousedown', '.rkscd-fchip', function(e){
				if (e.button !== 0) return;
				if (paletteDrag) return; // ignore if palette drag is active
				var $chip = $(this);
				var i = parseInt($chip.attr('data-i'), 10);
				if (isNaN(i)) return;

				// Mark selected via class only — no full re-render.
				$canvas.find('.rkscd-fchip').removeClass('is-selected');
				$chip.addClass('is-selected');
				selectedIdx = i;
				updateSidePanel();

				var rect = $canvas[0].getBoundingClientRect();
				var chipRect = this.getBoundingClientRect();
				dragInfo = {
					i: i,
					$chip: $chip,
					canvasRect: rect,
					offX: e.clientX - (chipRect.left + chipRect.width/2),
					offY: e.clientY - (chipRect.top  + chipRect.height/2),
					moved: false
				};
				e.preventDefault();
				e.stopPropagation();
			});
			$(document).on('mousemove', function(e){
				if (!dragInfo) return;
				dragInfo.moved = true;
				var r = dragInfo.canvasRect;
				var cx = e.clientX - dragInfo.offX;
				var cy = e.clientY - dragInfo.offY;
				var xp = Math.max(0, Math.min(100, ((cx - r.left) / r.width)  * 100));
				var yp = Math.max(0, Math.min(100, ((cy - r.top)  / r.height) * 100));
				if (fields[dragInfo.i]) {
					fields[dragInfo.i].x = xp;
					fields[dragInfo.i].y = yp;
				}
				// Move DOM element directly — no full re-render.
				dragInfo.$chip.css({ left: xp + '%', top: yp + '%' });
			});
			$(document).on('mouseup', function(){
				if (!dragInfo) return;
				// Persist final state to JSON store; no full re-render needed.
				$store.val(JSON.stringify(fields));
				dragInfo = null;
			});

			// ----- Side panel: edit selected -----
			function updateSidePanel() {
				var $panel = $('#rkscd-selected-card');
				if (selectedIdx < 0 || !fields[selectedIdx]) {
					$panel.hide();
					return;
				}
				var f = fields[selectedIdx];
				var label = (VARS[f.key] && VARS[f.key].label) || f.key;
				$('#rkscd-selected-label').text(label + '  ·  {' + f.key + '}');
				$panel.show();
				$('#rkscd-fld-size').val(f.font_size);
				$('#rkscd-fld-color').val(f.color);
				$('#rkscd-fld-w').val(Math.round(f.w));
				$('#rkscd-fld-font').val(f.font_family);
				$('#rkscd-fld-bold').prop('checked', f.font_weight === 'bold');
				$('#rkscd-fld-italic').prop('checked', f.font_style === 'italic');
				$('.rkscd-align button').removeClass('is-active');
				$('.rkscd-align button[data-a="' + f.align + '"]').addClass('is-active');
			}
			$('#rkscd-fld-size').on('input',  function(){ if (fields[selectedIdx]) { fields[selectedIdx].font_size = parseInt(this.value, 10) || 24; render(); } });
			$('#rkscd-fld-color').on('input', function(){ if (fields[selectedIdx]) { fields[selectedIdx].color = this.value; render(); } });
			$('#rkscd-fld-w').on('input',     function(){ if (fields[selectedIdx]) { fields[selectedIdx].w = parseInt(this.value, 10) || 40; render(); } });
			$('#rkscd-fld-font').on('change', function(){ if (fields[selectedIdx]) { fields[selectedIdx].font_family = this.value; render(); } });
			$('#rkscd-fld-bold').on('change', function(){ if (fields[selectedIdx]) { fields[selectedIdx].font_weight = this.checked ? 'bold' : 'normal'; render(); } });
			$('#rkscd-fld-italic').on('change', function(){ if (fields[selectedIdx]) { fields[selectedIdx].font_style = this.checked ? 'italic' : 'normal'; render(); } });
			$('.rkscd-align button').on('click', function(){
				if (fields[selectedIdx]) { fields[selectedIdx].align = $(this).data('a'); render(); }
			});
			$('#rkscd-fld-remove').on('click', function(){
				if (selectedIdx >= 0) {
					fields.splice(selectedIdx, 1);
					selectedIdx = -1;
					render();
				}
			});

			// ----- Deselect on canvas blank-area click (only on bg/empty — never on a chip) -----
			$canvas.on('click', function(e){
				if ($(e.target).closest('.rkscd-fchip').length > 0) return; // clicked on a chip; ignore
				selectedIdx = -1;
				$canvas.find('.rkscd-fchip').removeClass('is-selected');
				updateSidePanel();
			});

			// Initial render
			render();
		})(jQuery);
		</script>
		<?php
	}
}
