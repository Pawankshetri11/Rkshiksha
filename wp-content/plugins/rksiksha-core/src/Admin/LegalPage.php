<?php
/**
 * Legal — admin can upload PDF documents (DARPAN ID, UDYAM Certificate,
 * etc.) and showcase them publicly on /legal/. Documents are stored as an
 * ordered list in option `rks_legal_docs`. Each row references a WP Media
 * Library attachment ID so files live in the standard uploads pipeline.
 *
 * @package RKSiksha\Core
 */

declare( strict_types=1 );

namespace RKSiksha\Core\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LegalPage {

	public const SLUG       = 'rks-legal';
	public const OPTION_KEY = 'rks_legal_docs';
	public const NONCE      = 'rks_legal_save';

	public function register(): void {
		add_action( 'admin_menu',            [ $this, 'register_menu' ], 32 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'admin_post_rks_legal_save', [ $this, 'handle_save' ] );
	}

	public function register_menu(): void {
		add_submenu_page(
			Menu::SLUG_WEBSITE,
			'Legal',
			'Legal',
			'manage_options',
			self::SLUG,
			[ $this, 'render' ]
		);
	}

	public function enqueue_assets( string $hook ): void {
		if ( strpos( $hook, self::SLUG ) === false ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_script( 'jquery' );
	}

	/* ------------------------------------------------------------------ */
	/* Public getters                                                      */
	/* ------------------------------------------------------------------ */

	/**
	 * Return all stored documents (raw, unfiltered).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function all_documents(): array {
		$docs = (array) get_option( self::OPTION_KEY, [] );
		$clean = [];
		foreach ( $docs as $d ) {
			if ( ! is_array( $d ) ) {
				continue;
			}
			$clean[] = [
				'id'          => (string) ( $d['id'] ?? '' ),
				'title'       => (string) ( $d['title'] ?? '' ),
				'description' => (string) ( $d['description'] ?? '' ),
				'file_id'     => (int)    ( $d['file_id'] ?? 0 ),
				'order'       => (int)    ( $d['order'] ?? 0 ),
				'enabled'     => (bool)   ( $d['enabled'] ?? true ),
			];
		}
		usort( $clean, static function ( $a, $b ) {
			return ( $a['order'] <=> $b['order'] );
		} );
		return $clean;
	}

	/**
	 * Return only enabled documents with file URLs resolved.
	 * Used by the frontend.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_documents(): array {
		$out = [];
		foreach ( self::all_documents() as $d ) {
			if ( empty( $d['enabled'] ) || $d['file_id'] <= 0 ) {
				continue;
			}
			$url = wp_get_attachment_url( $d['file_id'] );
			if ( ! $url ) {
				continue;
			}
			$d['file_url']  = $url;
			$d['file_name'] = basename( get_attached_file( $d['file_id'] ) ?: '' );
			$out[] = $d;
		}
		return $out;
	}

	public static function document_proxy_url( array $doc ): string {
		$doc_id  = (string) ( $doc['id'] ?? '' );
		$file_id = (int) ( $doc['file_id'] ?? 0 );
		return add_query_arg( [
			'action' => 'rks_legal_document',
			'doc'    => rawurlencode( $doc_id ),
			'sig'    => self::document_signature( $doc_id, $file_id ),
		], admin_url( 'admin-post.php' ) );
	}

	public static function serve_public_document(): void {
		$doc_id = isset( $_GET['doc'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['doc'] ) ) : '';
		$sig    = isset( $_GET['sig'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['sig'] ) ) : '';
		$doc    = self::find_public_document( $doc_id );

		if ( ! $doc || ! hash_equals( self::document_signature( $doc_id, (int) $doc['file_id'] ), $sig ) ) {
			wp_die( 'Document unavailable.', 'Not found', [ 'response' => 404 ] );
		}

		$file = (string) get_attached_file( (int) $doc['file_id'] );
		$uploads = wp_upload_dir();
		$base = realpath( (string) $uploads['basedir'] );
		$real = realpath( $file );
		if ( ! $base || ! $real || ! str_starts_with( $real, $base ) || ! is_readable( $real ) ) {
			wp_die( 'Document unavailable.', 'Not found', [ 'response' => 404 ] );
		}

		$type = wp_check_filetype( $real );
		if ( 'pdf' !== strtolower( (string) ( $type['ext'] ?? '' ) ) ) {
			wp_die( 'Only PDF documents are supported.', 'Unsupported', [ 'response' => 415 ] );
		}

		$size = (int) filesize( $real );
		$start = 0;
		$end = max( 0, $size - 1 );
		$status = 200;

		if ( isset( $_SERVER['HTTP_RANGE'] ) && preg_match( '/bytes=(\d*)-(\d*)/', (string) $_SERVER['HTTP_RANGE'], $m ) ) {
			$status = 206;
			if ( '' !== $m[1] ) {
				$start = max( 0, (int) $m[1] );
			}
			if ( '' !== $m[2] ) {
				$end = min( $end, (int) $m[2] );
			}
			if ( '' === $m[1] && '' !== $m[2] ) {
				$suffix = min( $size, (int) $m[2] );
				$start = max( 0, $size - $suffix );
				$end = $size - 1;
			}
			if ( $start > $end || $start >= $size ) {
				header( 'Content-Range: bytes */' . $size );
				status_header( 416 );
				exit;
			}
		}

		$length = $end - $start + 1;
		status_header( $status );
		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: inline; filename="' . sanitize_file_name( (string) $doc['title'] ) . '.pdf"' );
		header( 'Content-Length: ' . $length );
		header( 'Accept-Ranges: bytes' );
		header( 'Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0' );
		header( 'Pragma: no-cache' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'X-Robots-Tag: noindex, nofollow, noarchive' );
		if ( 206 === $status ) {
			header( 'Content-Range: bytes ' . $start . '-' . $end . '/' . $size );
		}

		$handle = fopen( $real, 'rb' );
		if ( ! $handle ) {
			exit;
		}
		fseek( $handle, $start );
		$remaining = $length;
		while ( $remaining > 0 && ! feof( $handle ) ) {
			$chunk = fread( $handle, min( 8192, $remaining ) );
			if ( false === $chunk ) {
				break;
			}
			echo $chunk;
			$remaining -= strlen( $chunk );
			if ( connection_aborted() ) {
				break;
			}
		}
		fclose( $handle );
		exit;
	}

	private static function find_public_document( string $doc_id ): ?array {
		foreach ( self::all_documents() as $doc ) {
			if ( empty( $doc['enabled'] ) || $doc_id !== (string) $doc['id'] || (int) $doc['file_id'] <= 0 ) {
				continue;
			}
			return $doc;
		}
		return null;
	}

	private static function document_signature( string $doc_id, int $file_id ): string {
		return hash_hmac( 'sha256', $doc_id . '|' . $file_id, wp_salt( 'auth' ) );
	}

	/* ------------------------------------------------------------------ */
	/* Save handler                                                        */
	/* ------------------------------------------------------------------ */

	public function handle_save(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Insufficient permissions.' );
		}
		check_admin_referer( self::NONCE );

		$rows = isset( $_POST['rks_doc'] ) && is_array( $_POST['rks_doc'] )
			? wp_unslash( $_POST['rks_doc'] )
			: [];

		$clean = [];
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$title = trim( sanitize_text_field( (string) ( $row['title'] ?? '' ) ) );
			$file_id = (int) ( $row['file_id'] ?? 0 );
			// Skip blank rows (no title and no file)
			if ( $title === '' && $file_id <= 0 ) {
				continue;
			}
			$clean[] = [
				'id'          => (string) ( $row['id'] ?? wp_generate_uuid4() ),
				'title'       => $title !== '' ? $title : 'Untitled Document',
				'description' => sanitize_textarea_field( (string) ( $row['description'] ?? '' ) ),
				'file_id'     => $file_id,
				'order'       => (int) ( $row['order'] ?? 0 ),
				'enabled'     => ! empty( $row['enabled'] ),
			];
		}

		// Re-sort by order then reindex order so values are dense (1,2,3,...)
		usort( $clean, static function ( $a, $b ) {
			return ( $a['order'] <=> $b['order'] );
		} );
		foreach ( $clean as $i => &$row ) {
			$row['order'] = $i + 1;
		}
		unset( $row );

		update_option( self::OPTION_KEY, $clean, true );

		wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG, 'saved' => 1 ], admin_url( 'admin.php' ) ) );
		exit;
	}

	/* ------------------------------------------------------------------ */
	/* Render                                                              */
	/* ------------------------------------------------------------------ */

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Insufficient permissions.' );
		}
		$docs = self::all_documents();
		$this->inline_css();
		?>
		<div class="wrap rkslegal-wrap">

			<div class="rkslegal-hero">
				<div class="rkslegal-hero__icon">
					<svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
				</div>
				<div>
					<small>Verified Documents</small>
					<h1>Legal</h1>
					<p>Upload official registration certificates, licenses and compliance documents. They will appear on the public <code>/legal/</code> page as previewable, view-only cards (no download allowed).</p>
				</div>
			</div>

			<?php if ( isset( $_GET['saved'] ) ) : ?>
				<div class="rkslegal-notice">Legal documents updated successfully.</div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="rkslegal-form">
				<input type="hidden" name="action" value="rks_legal_save">
				<?php wp_nonce_field( self::NONCE ); ?>

				<div class="rkslegal-section">
					<div class="rkslegal-section__head">
						<span class="rkslegal-step">Documents</span>
						<h2>
							<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
							Manage Legal Documents
						</h2>
						<p>Each row produces one preview card on the public page. Disable a document to hide it temporarily without deleting it. Use <strong>Order</strong> to control how they appear (lowest first).</p>
					</div>

					<div id="rkslegal-rows" class="rkslegal-rows">
						<?php
						if ( empty( $docs ) ) {
							$this->render_row( [], 0 );
						} else {
							foreach ( $docs as $i => $doc ) {
								$this->render_row( $doc, $i );
							}
						}
						?>
					</div>

					<button type="button" class="button rkslegal-add" id="rkslegal-add">
						<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
						Add Document
					</button>
				</div>

				<div class="rkslegal-stickybar">
					<div class="rkslegal-stickybar__inner">
						<span class="rkslegal-stickybar__note">Changes only take effect after you save.</span>
						<button type="submit" class="button button-primary rkslegal-save">Save Documents</button>
					</div>
				</div>
			</form>
		</div>

		<template id="rkslegal-row-tpl">
			<?php $this->render_row( [], '__INDEX__' ); ?>
		</template>

		<?php $this->inline_js(); ?>
		<?php
	}

	/**
	 * Render one document row.
	 *
	 * @param array<string,mixed> $d
	 * @param int|string          $i Index (use '__INDEX__' for the JS template)
	 */
	private function render_row( array $d, $i ): void {
		$id          = (string) ( $d['id']          ?? '' );
		$title       = (string) ( $d['title']       ?? '' );
		$description = (string) ( $d['description'] ?? '' );
		$file_id     = (int)    ( $d['file_id']     ?? 0 );
		$order       = (int)    ( $d['order']       ?? ( is_int( $i ) ? $i + 1 : 99 ) );
		$enabled     = ! isset( $d['enabled'] ) || ! empty( $d['enabled'] );

		$file_name = '';
		$file_url  = '';
		if ( $file_id > 0 ) {
			$file_url  = (string) wp_get_attachment_url( $file_id );
			$path      = (string) get_attached_file( $file_id );
			$file_name = $path ? basename( $path ) : '';
		}
		?>
		<div class="rkslegal-row" data-index="<?php echo esc_attr( (string) $i ); ?>">
			<div class="rkslegal-row__handle" title="Document order">
				<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="6" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="18" r="1"/><circle cx="15" cy="6" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="18" r="1"/></svg>
				<input type="number" min="0" max="999" step="1" name="rks_doc[<?php echo esc_attr( (string) $i ); ?>][order]" value="<?php echo esc_attr( (string) $order ); ?>" class="rkslegal-order">
			</div>

			<div class="rkslegal-row__body">
				<input type="hidden" name="rks_doc[<?php echo esc_attr( (string) $i ); ?>][id]" value="<?php echo esc_attr( $id ); ?>" class="rkslegal-id">
				<input type="hidden" name="rks_doc[<?php echo esc_attr( (string) $i ); ?>][file_id]" value="<?php echo esc_attr( (string) $file_id ); ?>" class="rkslegal-file-id">

				<div class="rkslegal-field">
					<label>Title</label>
					<input type="text" name="rks_doc[<?php echo esc_attr( (string) $i ); ?>][title]" value="<?php echo esc_attr( $title ); ?>" placeholder="e.g. UDYAM Registration Certificate" required>
				</div>

				<div class="rkslegal-field">
					<label>Short description</label>
					<textarea rows="2" name="rks_doc[<?php echo esc_attr( (string) $i ); ?>][description]" placeholder="One-sentence summary shown under the preview card."><?php echo esc_textarea( $description ); ?></textarea>
				</div>

				<div class="rkslegal-field">
					<label>PDF File</label>
					<div class="rkslegal-filepicker">
						<button type="button" class="button rkslegal-pick">
							<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
							<?php echo $file_id ? 'Replace PDF' : 'Choose PDF'; ?>
						</button>
						<span class="rkslegal-filename"><?php echo $file_name !== '' ? esc_html( $file_name ) : '<em>No file selected</em>'; ?></span>
						<?php if ( $file_url ) : ?>
							<a href="<?php echo esc_url( $file_url ); ?>" target="_blank" rel="noopener" class="rkslegal-preview">Preview</a>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<div class="rkslegal-row__side">
				<label class="rkslegal-tog">
					<input type="checkbox" name="rks_doc[<?php echo esc_attr( (string) $i ); ?>][enabled]" value="1" <?php checked( $enabled, true ); ?>>
					<span>Enabled</span>
				</label>
				<button type="button" class="button button-link-delete rkslegal-remove">Remove</button>
			</div>
		</div>
		<?php
	}

	/* ------------------------------------------------------------------ */
	/* Inline CSS + JS                                                     */
	/* ------------------------------------------------------------------ */

	private function inline_css(): void { ?>
		<style>
			.rkslegal-wrap { max-width: 1240px; margin: 18px 20px 60px 0; font-family: -apple-system, system-ui, "Segoe UI", sans-serif; }

			.rkslegal-hero {
				background: linear-gradient(135deg, #E63946 0%, #2C687B 100%);
				color: #fff; border-radius: 12px; padding: 22px 26px;
				margin-bottom: 22px; display: flex; align-items: center; gap: 18px;
				box-shadow: 0 12px 28px rgba(44,104,123,0.22);
			}
			.rkslegal-hero__icon {
				width: 56px; height: 56px; border-radius: 14px;
				background: rgba(255,255,255,0.16);
				display: inline-flex; align-items: center; justify-content: center;
				color: #FBF3E9; flex-shrink: 0;
			}
			.rkslegal-hero h1 { margin: 0 0 2px; color: #fff; font-size: 22px; }
			.rkslegal-hero small { display: block; color: rgba(255,255,255,0.85); font-size: 11px; text-transform: uppercase; letter-spacing: 1.2px; font-weight: 700; margin-bottom: 4px; }
			.rkslegal-hero p { margin: 0; color: rgba(255,255,255,0.92); font-size: 13.5px; max-width: 760px; }
			.rkslegal-hero code { background: rgba(255,255,255,0.18); padding: 1px 6px; border-radius: 4px; font-size: 12px; }

			.rkslegal-notice {
				padding: 12px 16px; border-radius: 8px;
				border-left: 4px solid #00a32a; background: #f0fff4;
				color: #1f5b30; font-size: 13.5px; margin-bottom: 16px; font-weight: 500;
			}

			.rkslegal-section {
				background: #fff; border: 1px solid #e3e9eb; border-radius: 14px;
				padding: 22px 26px; margin-bottom: 22px;
				box-shadow: 0 2px 10px rgba(44,104,123,0.05);
			}
			.rkslegal-section__head { margin-bottom: 18px; }
			.rkslegal-section__head h2 {
				margin: 0 0 4px; font-size: 18px; color: #2C687B;
				display: flex; align-items: center; gap: 8px;
			}
			.rkslegal-section__head h2 svg { color: #E63946; }
			.rkslegal-section__head p { margin: 0; color: #7a8a90; font-size: 13px; line-height: 1.55; }
			.rkslegal-step {
				display: inline-block;
				font-size: 10.5px; font-weight: 800;
				letter-spacing: 0.14em; text-transform: uppercase;
				background: rgba(230,57,70,0.12); color: #E63946;
				padding: 3px 9px; border-radius: 999px;
				margin-bottom: 6px;
			}

			.rkslegal-rows { display: flex; flex-direction: column; gap: 14px; }
			.rkslegal-row {
				display: grid;
				grid-template-columns: 60px 1fr 200px;
				gap: 14px;
				background: linear-gradient(135deg, #FBF3E9, #fff);
				border: 1px solid rgba(44,104,123,0.14);
				border-radius: 12px;
				padding: 16px 18px;
				align-items: start;
			}
			@media (max-width: 900px) {
				.rkslegal-row { grid-template-columns: 1fr; }
				.rkslegal-row__handle { flex-direction: row; }
			}

			.rkslegal-row__handle {
				display: flex; flex-direction: column; align-items: center; gap: 8px;
				color: #2C687B;
			}
			.rkslegal-row__handle svg { opacity: 0.6; }
			.rkslegal-order {
				width: 50px; padding: 6px 4px;
				text-align: center; font-weight: 700;
				border-radius: 6px;
				border: 1.5px solid rgba(44,104,123,0.18);
				background: #fff;
			}

			.rkslegal-row__body { display: flex; flex-direction: column; gap: 12px; }
			.rkslegal-field label {
				display: block; font-size: 11.5px; font-weight: 700;
				color: #2C687B; margin-bottom: 4px;
				text-transform: uppercase; letter-spacing: 0.04em;
			}
			.rkslegal-field input[type="text"],
			.rkslegal-field textarea {
				width: 100%; padding: 8px 12px;
				border-radius: 8px;
				border: 1.5px solid rgba(44,104,123,0.18);
				font-size: 13.5px;
				background: #fff;
				box-sizing: border-box;
				font-family: inherit;
				resize: vertical;
			}
			.rkslegal-field input[type="text"]:focus,
			.rkslegal-field textarea:focus { border-color: #E63946; outline: none; }

			.rkslegal-filepicker {
				display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
				background: #fff; padding: 10px 12px;
				border-radius: 8px;
				border: 1.5px dashed rgba(44,104,123,0.25);
			}
			.rkslegal-filename {
				font-family: ui-monospace, monospace;
				font-size: 12px; color: #2C687B;
			}
			.rkslegal-filename em { color: #a0aab0; font-style: italic; }
			.rkslegal-preview {
				font-size: 12px; font-weight: 600;
				color: #E63946; text-decoration: none;
				margin-left: auto;
			}
			.rkslegal-preview:hover { text-decoration: underline; }

			.rkslegal-row__side {
				display: flex; flex-direction: column; gap: 10px;
				padding-top: 24px;
			}
			.rkslegal-tog {
				display: inline-flex; align-items: center; gap: 8px;
				font-size: 13px; font-weight: 600; color: #2C687B;
				background: #fff;
				padding: 8px 12px;
				border-radius: 8px;
				border: 1.5px solid rgba(44,104,123,0.18);
				cursor: pointer;
			}
			.rkslegal-tog input { margin: 0; cursor: pointer; }

			.rkslegal-remove { color: #b32431 !important; }

			.rkslegal-add {
				display: inline-block !important;
				margin-top: 28px !important;
				background: #fff;
				border: 1.5px dashed #E63946 !important;
				color: #E63946 !important;
				font-weight: 600;
				padding: 10px 18px !important;
				height: auto !important;
			}
			#rkslegal-rows + .rkslegal-add,
			.rkslegal-rows + .rkslegal-add { margin-top: 28px !important; }
			.rkslegal-add:hover { background: rgba(230,57,70,0.06) !important; }

			.rkslegal-stickybar {
				position: sticky; bottom: 0; z-index: 10;
				background: linear-gradient(180deg, transparent, rgba(255,255,255,0.92) 30%, #fff);
				padding: 14px 0;
				margin-top: 18px;
			}
			.rkslegal-stickybar__inner {
				display: flex; align-items: center; justify-content: space-between;
				background: #fff; border: 1px solid #e3e9eb;
				border-radius: 12px;
				padding: 12px 18px;
				box-shadow: 0 6px 20px rgba(44,104,123,0.10);
			}
			.rkslegal-stickybar__note { color: #7a8a90; font-size: 12.5px; }
			.rkslegal-save {
				background: #E63946 !important; border-color: #E63946 !important;
				color: #fff !important; font-weight: 600;
				box-shadow: 0 6px 14px rgba(230,57,70,0.28);
			}
			.rkslegal-save:hover { background: #c52d39 !important; border-color: #c52d39 !important; }
		</style>
		<?php
	}

	private function inline_js(): void { ?>
		<script>
		(function($){
			'use strict';
			var $rows = $('#rkslegal-rows');
			var $tpl  = $('#rkslegal-row-tpl');

			function nextIndex() {
				var max = -1;
				$rows.find('.rkslegal-row').each(function(){
					var i = parseInt($(this).attr('data-index'), 10);
					if (!isNaN(i) && i > max) max = i;
				});
				return max + 1;
			}

			// Add row
			$('#rkslegal-add').on('click', function(){
				var i = nextIndex();
				var html = $tpl.html().replace(/__INDEX__/g, i);
				var $row = $(html);
				$row.attr('data-index', i);
				// generate a fresh uuid for new row id input
				var $idInput = $row.find('.rkslegal-id');
				if (!$idInput.val()) {
					$idInput.val('new-' + Date.now() + '-' + Math.floor(Math.random() * 1000));
				}
				// set order to next sequential number
				var orderVal = $rows.find('.rkslegal-row').length + 1;
				$row.find('.rkslegal-order').val(orderVal);
				$rows.append($row);
			});

			// Remove row
			$rows.on('click', '.rkslegal-remove', function(){
				if (!confirm('Remove this document row?')) return;
				$(this).closest('.rkslegal-row').remove();
			});

			// PDF picker via WP Media
			$rows.on('click', '.rkslegal-pick', function(e){
				e.preventDefault();
				var $row = $(this).closest('.rkslegal-row');
				var frame = wp.media({
					title: 'Select Legal Document (PDF)',
					button: { text: 'Use this file' },
					multiple: false,
					library: { type: ['application/pdf', 'image'] }
				});
				frame.on('select', function(){
					var att = frame.state().get('selection').first().toJSON();
					$row.find('.rkslegal-file-id').val(att.id);
					var fname = att.filename || (att.url ? att.url.split('/').pop() : '');
					$row.find('.rkslegal-filename').text(fname);
					$row.find('.rkslegal-pick').contents().filter(function(){ return this.nodeType === 3; }).remove();
					$row.find('.rkslegal-pick').append(document.createTextNode(' Replace PDF'));
					// inject/replace preview link
					$row.find('.rkslegal-preview').remove();
					if (att.url) {
						$row.find('.rkslegal-filepicker').append(
							$('<a class="rkslegal-preview" target="_blank" rel="noopener">Preview</a>').attr('href', att.url)
						);
					}
				});
				frame.open();
			});
		})(jQuery);
		</script>
		<?php
	}
}
