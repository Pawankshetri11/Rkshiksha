<?php
declare( strict_types=1 );

namespace RKSiksha\Gallery;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AdminPage {

	public const SLUG  = 'rks-gallery';
	public const NONCE = 'rks_gallery_save';

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'menu' ], 30 );
		add_action( 'admin_enqueue_scripts', [ $this, 'assets' ] );
		add_action( 'admin_post_rks_gallery_save',   [ $this, 'handle_save' ] );
		add_action( 'admin_post_rks_gallery_import', [ $this, 'handle_import' ] );
	}

	public function menu(): void {
		if ( class_exists( '\RKSiksha\Core\Admin\Menu' ) ) {
			add_submenu_page(
				\RKSiksha\Core\Admin\Menu::SLUG_WEBSITE,
				'Gallery',
				'Gallery',
				'manage_options',
				self::SLUG,
				[ $this, 'render' ]
			);
			return;
		}

		add_menu_page(
			'Gallery',
			'Gallery',
			'manage_options',
			self::SLUG,
			[ $this, 'render' ],
			'dashicons-format-gallery',
			29
		);
	}

	public function assets( $hook = '' ): void {
		// Match both the top-level page hook and any defensive variants.
		$is_gallery = false;
		if ( is_string( $hook ) && $hook !== '' ) {
			$is_gallery = ( strpos( $hook, self::SLUG ) !== false );
		}
		if ( ! $is_gallery && function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();
			if ( $screen && ( $screen->id === 'toplevel_page_' . self::SLUG || $screen->id === self::SLUG ) ) {
				$is_gallery = true;
			}
		}
		if ( ! $is_gallery ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-sortable' );
		// Load core jQuery UI base styles so the sortable placeholder is visible.
		wp_enqueue_style( 'wp-jquery-ui-dialog' );
	}

	/* ---------- Data ---------- */
	public static function get_ids(): array {
		return array_values( array_map( 'intval', (array) get_option( RKSG_OPTION, [] ) ) );
	}

	private static function save_ids( array $ids ): void {
		$ids = array_values( array_unique( array_filter( array_map( 'intval', $ids ), static fn( $i ) => $i > 0 ) ) );
		update_option( RKSG_OPTION, $ids, false );
	}

	/* ---------- Handlers ---------- */
	public function handle_save(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Insufficient permissions.' );
		}
		check_admin_referer( self::NONCE );

		$ids = isset( $_POST['photo_ids'] ) && is_string( $_POST['photo_ids'] )
			? array_map( 'intval', array_filter( explode( ',', sanitize_text_field( wp_unslash( $_POST['photo_ids'] ) ) ) ) )
			: [];
		self::save_ids( $ids );

		wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG, 'saved' => 1 ], admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Import the legacy /wp-content/uploads/gallery/*.jpeg photos
	 * into WP Media Library and save their IDs in this plugin.
	 */
	public function handle_import(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Insufficient permissions.' );
		}
		check_admin_referer( self::NONCE );

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$uploads = wp_get_upload_dir();
		$dir     = trailingslashit( $uploads['basedir'] ) . 'gallery';
		$created = 0;

		if ( is_dir( $dir ) ) {
			$files = glob( $dir . '/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE );
			natsort( $files );
			$existing = self::get_ids();
			foreach ( $files as $path ) {
				$filename = basename( $path );
				$wp_filetype = wp_check_filetype( $filename );
				$attachment_id = wp_insert_attachment( [
					'guid'           => trailingslashit( $uploads['baseurl'] ) . 'gallery/' . $filename,
					'post_mime_type' => $wp_filetype['type'],
					'post_title'     => sanitize_file_name( pathinfo( $filename, PATHINFO_FILENAME ) ),
					'post_content'   => '',
					'post_status'    => 'inherit',
				], $path );

				if ( ! is_wp_error( $attachment_id ) && $attachment_id > 0 ) {
					$meta = wp_generate_attachment_metadata( $attachment_id, $path );
					wp_update_attachment_metadata( $attachment_id, $meta );
					$existing[] = (int) $attachment_id;
					$created++;
				}
			}
			self::save_ids( $existing );
		}

		wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG, 'imported' => $created ], admin_url( 'admin.php' ) ) );
		exit;
	}

	/* ---------- Render ---------- */
	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Insufficient permissions.' );
		}
		$ids   = self::get_ids();
		$saved = isset( $_GET['saved'] );
		$imp   = isset( $_GET['imported'] ) ? (int) $_GET['imported'] : -1;
		?>
		<style>
			.rksg-wrap {
				max-width: 1200px; margin: 18px 20px 40px 0;
				font-family: -apple-system, system-ui, "Segoe UI", sans-serif;
			}
			.rksg-hero {
				background: linear-gradient(135deg, #ff6675 0%, #E63946 45%, #2C687B 100%);
				color: #fff; border-radius: 14px; padding: 26px 30px;
				margin-bottom: 22px; display: flex; align-items: center; gap: 20px;
				box-shadow: 0 14px 32px rgba(230, 57, 70, 0.22);
				position: relative; overflow: hidden;
			}
			.rksg-hero::after {
				content: ''; position: absolute;
				top: -60px; right: -60px;
				width: 220px; height: 220px;
				background: rgba(255,255,255,0.16);
				border-radius: 50%;
			}
			.rksg-hero__icon {
				width: 58px; height: 58px; border-radius: 14px;
				background: rgba(255,255,255,0.22);
				display: inline-flex; align-items: center; justify-content: center;
				color: #fff; flex-shrink: 0; position: relative; z-index: 1;
			}
			.rksg-hero__icon .dashicons { font-size: 30px; width: 30px; height: 30px; }
			.rksg-hero h1 { margin: 0 0 2px; color: #fff; font-size: 24px; position: relative; z-index: 1; }
			.rksg-hero small { display: block; color: rgba(255,255,255,0.85); font-size: 11px; text-transform: uppercase; letter-spacing: 1.4px; font-weight: 700; margin-bottom: 4px; }
			.rksg-hero p { margin: 0; color: rgba(255,255,255,0.92); font-size: 13.5px; position: relative; z-index: 1; }

			.rksg-toolbar {
				display: flex; gap: 10px; align-items: center;
				margin-bottom: 18px; flex-wrap: wrap;
				background: #fff; padding: 14px 18px;
				border: 1px solid #e3e9eb; border-radius: 12px;
				box-shadow: 0 2px 8px rgba(44,104,123,0.06);
			}
			.rksg-toolbar .button {
				border-radius: 8px;
				padding: 6px 16px;
				height: auto;
				font-weight: 600;
			}
			.rksg-toolbar .button-primary {
				background: #E63946; border-color: #E63946;
				box-shadow: 0 6px 14px rgba(230,57,70,0.28);
			}
			.rksg-toolbar .button-primary:hover { background: #c52d39; border-color: #c52d39; }
			.rksg-count {
				color: #2C687B; font-size: 13px; margin-left: auto;
				font-weight: 600;
			}
			.rksg-count strong { color: #E63946; font-size: 16px; }

			.rksg-card {
				background: #fff; border: 1px solid #e3e9eb; border-radius: 12px;
				padding: 22px; box-shadow: 0 4px 12px rgba(44,104,123,0.06);
			}
			.rksg-card h2 {
				margin: 0 0 4px; font-size: 16px; color: #2C687B;
				display: flex; align-items: center; gap: 8px;
			}
			.rksg-card h2 .dashicons { color: #E63946; font-size: 18px; width: 18px; height: 18px; }
			.rksg-card__sub { color: #7a8a90; font-size: 12.5px; margin: 0 0 16px; }

			/* GRID layout — sortable-friendly. Tiles get a fixed aspect-ratio frame
			   but image inside uses object-fit: contain so NO CROP. */
			.rksg-grid {
				display: grid;
				grid-template-columns: repeat(5, 1fr);
				gap: 14px;
				min-height: 80px;
			}
			@media (max-width: 1100px) { .rksg-grid { grid-template-columns: repeat(4, 1fr); } }
			@media (max-width: 900px)  { .rksg-grid { grid-template-columns: repeat(3, 1fr); } }
			@media (max-width: 640px)  { .rksg-grid { grid-template-columns: repeat(2, 1fr); } }

			.rksg-tile {
				position: relative;
				width: 100%;
				aspect-ratio: 1 / 1;
				border-radius: 12px;
				overflow: hidden;
				background: linear-gradient(135deg, #FBF3E9, #fff);
				border: 2px solid rgba(44,104,123,0.10);
				cursor: grab;
				box-shadow: 0 4px 12px rgba(31,45,51,0.08);
				transition: border-color .18s ease, transform .15s ease, box-shadow .18s ease;
				display: flex; align-items: center; justify-content: center;
			}
			.rksg-tile:active { cursor: grabbing; }
			.rksg-tile:hover {
				border-color: #E63946;
				box-shadow: 0 10px 24px rgba(230,57,70,0.18);
				transform: translateY(-2px);
			}
			.rksg-tile:nth-child(4n+1) { border-color: rgba(230, 57, 70, 0.22); }
			.rksg-tile:nth-child(4n+2) { border-color: rgba(142, 201, 196, 0.32); }
			.rksg-tile:nth-child(4n+3) { border-color: rgba(212, 154, 30, 0.22); }
			.rksg-tile:nth-child(4n+4) { border-color: rgba(44, 104, 123, 0.22); }
			/* Dragged clone — MUST be locked to inline width/height set in start()
			   so it doesn't balloon out to the image's intrinsic size once it
			   leaves the CSS grid track. aspect-ratio is reset to auto since the
			   start() handler supplies explicit pixel dimensions. */
			.rksg-tile.ui-sortable-helper {
				position: absolute !important;
				box-sizing: border-box !important;
				aspect-ratio: auto !important;
				max-width: none !important;
				max-height: none !important;
				min-width: 0 !important;
				min-height: 0 !important;
				transform: rotate(2deg) scale(1.04);
				box-shadow: 0 16px 36px rgba(0,0,0,0.22);
				border-color: #E63946;
				pointer-events: none;
				z-index: 50;
				opacity: 0.95;
			}
			.rksg-tile.ui-sortable-helper img {
				width: 100% !important;
				height: 100% !important;
				max-width: 100% !important;
				max-height: 100% !important;
				object-fit: contain;
			}
			.rksg-tile.ui-sortable-placeholder,
			.rksg-tile.rksg-tile--placeholder {
				visibility: visible !important;
				background: rgba(142,201,196,0.22);
				border: 2px dashed #8EC9C4;
				box-shadow: none;
				aspect-ratio: auto;
				transform: none;
				box-sizing: border-box;
			}
			.rksg-grid .ui-sortable-helper {
				pointer-events: none;
				z-index: 50;
			}
			.rksg-tile img {
				max-width: 100%;
				max-height: 100%;
				width: auto; height: auto;
				display: block;
				pointer-events: none;
				object-fit: contain;
				/* NO CROP — full image visible inside square frame */
			}
			.rksg-tile__num {
				position: absolute; top: 8px; left: 8px;
				background: rgba(230,57,70,0.92);
				color: #fff;
				font-size: 11px; font-weight: 800;
				padding: 4px 9px; border-radius: 999px;
				box-shadow: 0 4px 10px rgba(0,0,0,0.22);
				letter-spacing: 0.4px;
			}
			.rksg-tile__del {
				position: absolute; top: 8px; right: 8px;
				width: 30px; height: 30px; border-radius: 50%;
				background: #fff; color: #E63946;
				border: 1.5px solid #E63946;
				display: inline-flex; align-items: center; justify-content: center;
				cursor: pointer; padding: 0;
				box-shadow: 0 4px 10px rgba(0,0,0,0.18);
				opacity: 0; transform: scale(0.85);
				transition: opacity .18s ease, transform .15s ease, background .15s ease, color .15s ease;
			}
			.rksg-tile:hover .rksg-tile__del { opacity: 1; transform: scale(1); }
			.rksg-tile__del:hover {
				background: #E63946; color: #fff; transform: scale(1.1);
			}
			.rksg-tile__del .dashicons { font-size: 16px; width: 16px; height: 16px; }

			.rksg-empty {
				background: linear-gradient(135deg, #fff4dc, #ffe5e8);
				border: 2px dashed #f0b87a;
				color: #7a4f10;
				padding: 40px 30px; border-radius: 14px;
				text-align: center; font-size: 14px;
				line-height: 1.6;
			}
			.rksg-empty strong { color: #E63946; }
			.rksg-empty code {
				background: rgba(255,255,255,0.7);
				padding: 2px 8px; border-radius: 4px;
				font-size: 12px; color: #2C687B;
			}

			.rksg-actions {
				display: flex; align-items: center; justify-content: space-between;
				gap: 12px; margin-top: 22px; padding: 18px 24px;
				background: #fff; border: 1px solid #e3e9eb; border-radius: 12px;
				position: sticky; bottom: 0; z-index: 10;
				box-shadow: 0 -8px 20px rgba(0,0,0,0.08);
			}
			.rksg-actions .button-primary {
				background: #E63946; border-color: #E63946;
				padding: 8px 24px; font-weight: 600;
				border-radius: 8px;
				box-shadow: 0 6px 14px rgba(230,57,70,0.28);
			}
			.rksg-actions .button-primary:hover { background: #c52d39; border-color: #c52d39; }

			.rksg-notice {
				padding: 14px 18px; border-radius: 10px;
				border-left: 4px solid #00a32a;
				background: linear-gradient(90deg, #e8fbef 0%, #f5fff8 100%);
				color: #1f5b30; font-size: 13.5px;
				margin-bottom: 18px; font-weight: 500;
				box-shadow: 0 4px 12px rgba(0,163,42,0.10);
			}
		</style>

		<div class="wrap rksg-wrap">
			<div class="rksg-hero">
				<div class="rksg-hero__icon"><span class="dashicons dashicons-format-gallery"></span></div>
				<div>
					<small>Photo Gallery</small>
					<h1>Gallery Manager</h1>
					<p>Add, remove and reorder gallery photos. Drag tiles to change order — changes save on click.</p>
				</div>
			</div>

			<?php if ( $saved ) : ?>
				<div class="rksg-notice"><strong>Saved.</strong> Gallery updated.</div>
			<?php elseif ( $imp >= 0 ) : ?>
				<div class="rksg-notice"><strong>Imported <?php echo (int) $imp; ?> photo(s)</strong> from <code>wp-content/uploads/gallery/</code>.</div>
			<?php endif; ?>

			<form id="rksg-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="rks_gallery_save">
				<?php wp_nonce_field( self::NONCE ); ?>
				<input type="hidden" name="photo_ids" id="rksg-photo-ids" value="<?php echo esc_attr( implode( ',', $ids ) ); ?>">

				<div class="rksg-toolbar">
					<button type="button" id="rksg-add" class="button button-primary">
						<span class="dashicons dashicons-plus-alt2" style="vertical-align:text-bottom;"></span> Add Photos
					</button>

					<button type="submit" form="rksg-import-form" class="button" onclick="return confirm('Import existing photos from /wp-content/uploads/gallery/ into the Media Library?');">
						<span class="dashicons dashicons-download" style="vertical-align:text-bottom;"></span> Import existing folder photos
					</button>

					<span class="rksg-count"><strong id="rksg-count"><?php echo count( $ids ); ?></strong> photo(s)</span>
				</div>

				<div class="rksg-card">
					<h2><span class="dashicons dashicons-move"></span> Drag to reorder · Hover to remove</h2>
					<p class="rksg-card__sub">First photo becomes the first card on the frontend. Click <strong>Save Gallery</strong> when done.</p>

					<div class="rksg-grid" id="rksg-grid">
						<?php if ( empty( $ids ) ) : ?>
							<div class="rksg-empty">
								No photos yet. Click <strong>Add Photos</strong> to upload from your computer or pick from the Media Library, or use <strong>Import existing folder photos</strong> to bring in the 12 photos already uploaded to <code>/wp-content/uploads/gallery/</code>.
							</div>
						<?php else :
							foreach ( $ids as $i => $att_id ) :
								$src = wp_get_attachment_image_src( $att_id, 'medium' );
								if ( ! $src ) continue;
								?>
								<div class="rksg-tile" data-id="<?php echo (int) $att_id; ?>">
									<img src="<?php echo esc_url( $src[0] ); ?>" alt="">
									<span class="rksg-tile__num"><?php echo (int) ( $i + 1 ); ?></span>
									<button type="button" class="rksg-tile__del" aria-label="Remove">
										<span class="dashicons dashicons-no-alt"></span>
									</button>
								</div>
								<?php
							endforeach;
						endif; ?>
					</div>
				</div>

				<div class="rksg-actions">
					<span style="font-size:12px;color:#7a8a90;">Saving applies new order &amp; removes any deleted photos.</span>
					<button type="submit" class="button button-primary">Save Gallery</button>
				</div>
			</form>

			<form id="rksg-import-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:none;">
				<input type="hidden" name="action" value="rks_gallery_import">
				<?php wp_nonce_field( self::NONCE ); ?>
			</form>
		</div>

		<script>
		jQuery(function($){
			'use strict';
			var $grid   = $('#rksg-grid');
			var $idsFld = $('#rksg-photo-ids');
			var $count  = $('#rksg-count');

			if ( ! $grid.length ) { return; }

			function syncIds() {
				var ids = [];
				$grid.find('.rksg-tile').each(function(i){
					var id = $(this).data('id');
					if (id) {
						ids.push(id);
						$(this).find('.rksg-tile__num').text(i + 1);
					}
				});
				$idsFld.val(ids.join(','));
				$count.text(ids.length);
				$grid.find('.rksg-empty').toggle(ids.length === 0);
			}

			// Guard: if sortable failed to load, bail gracefully instead of throwing.
			if ( typeof $.fn.sortable !== 'function' ) {
				if ( window.console ) { console.warn( 'RKSG: jQuery UI Sortable not loaded.' ); }
			} else {
				$grid.sortable({
					items: '> .rksg-tile',
					cursor: 'grabbing',
					tolerance: 'pointer',
					placeholder: 'rksg-tile rksg-tile--placeholder ui-sortable-placeholder',
					forcePlaceholderSize: false,
					forceHelperSize: true,
					opacity: 0.95,
					helper: 'clone',
					// Keep the clone INSIDE the grid so it inherits grid-cell
					// sizing context (without this, the clone gets appended to
					// <body> and loses its width, then the inner <img> expands
					// to its natural pixel size — the bug shown in the
					// screenshot).
					appendTo: $grid,
					start: function(e, ui){
						var w = ui.item.outerWidth();
						var h = ui.item.outerHeight();
						// Match placeholder to the dragged tile so grid layout stays stable.
						ui.placeholder.css({
							width:  w + 'px',
							height: h + 'px',
							visibility: 'visible'
						});
						// Hide the original (sortable's "items" element) so only helper shows.
						ui.item.css('opacity', 0.001);
						// Lock the clone helper to the exact pixel size of the
						// source tile. Without this the clone — once it stops
						// being a grid item — collapses to fit its <img>'s
						// intrinsic dimensions and balloons out.
						if ( ui.helper && ui.helper.length ) {
							ui.helper.css({
								width:  w + 'px',
								height: h + 'px',
								maxWidth:  w + 'px',
								maxHeight: h + 'px',
								boxSizing: 'border-box'
							});
						}
					},
					stop: function(e, ui){
						ui.item.css('opacity', '');
					},
					update: syncIds
				});
				$grid.disableSelection();
			}

			// Remove
			$grid.on('click', '.rksg-tile__del', function(e){
				e.preventDefault();
				$(this).closest('.rksg-tile').remove();
				if (!$grid.find('.rksg-tile').length) {
					$grid.append('<div class="rksg-empty">No photos yet. Click <strong>Add Photos</strong> to upload, or import existing folder photos.</div>');
				}
				syncIds();
			});

			// Add (WP media)
			var frame;
			$('#rksg-add').on('click', function(e){
				e.preventDefault();
				if (frame) { frame.open(); return; }
				frame = wp.media({
					title:    'Add photos to Gallery',
					button:   { text: 'Add to Gallery' },
					multiple: true,
					library:  { type: 'image' }
				});
				frame.on('select', function(){
					var sel = frame.state().get('selection').toJSON();
					$grid.find('.rksg-empty').remove();
					sel.forEach(function(a){
						if (!a.id) return;
						if ($grid.find('.rksg-tile[data-id="' + a.id + '"]').length) return;
						var thumb = (a.sizes && a.sizes.medium ? a.sizes.medium.url : a.url);
						$grid.append(
							'<div class="rksg-tile" data-id="' + a.id + '">' +
								'<img src="' + thumb + '" alt="">' +
								'<span class="rksg-tile__num"></span>' +
								'<button type="button" class="rksg-tile__del" aria-label="Remove"><span class="dashicons dashicons-no-alt"></span></button>' +
							'</div>'
						);
					});
					syncIds();
				});
				frame.open();
			});
		});
		</script>
		<?php
	}
}
