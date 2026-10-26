<?php
/**
 * Public notice board module.
 *
 * @package RKSiksha\Core
 */

declare( strict_types=1 );

namespace RKSiksha\Core\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Notices {

	public const CPT          = 'rks_notice';
	public const M_ACTIVE     = '_rks_notice_is_active';
	public const M_IMPORTANT  = '_rks_notice_is_important';
	public const M_LINK_TEXT  = '_rks_notice_link_text';
	public const M_LINK_URL   = '_rks_notice_link_url';
	public const M_ATTACHMENT = '_rks_notice_attachment_id';
	public const M_EXPIRES_ON = '_rks_notice_expires_on';
	public const NONCE        = 'rks_notice_meta_nonce';

	public function register(): void {
		add_action( 'init', [ $this, 'register_post_type' ], 6 );
		add_action( 'init', [ $this, 'register_meta_fields' ], 7 );
		add_action( 'add_meta_boxes', [ $this, 'register_meta_box' ] );
		add_action( 'save_post_' . self::CPT, [ $this, 'save_meta' ], 10, 2 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );

		add_filter( 'manage_' . self::CPT . '_posts_columns', [ $this, 'columns' ] );
		add_action( 'manage_' . self::CPT . '_posts_custom_column', [ $this, 'render_column' ], 10, 2 );
	}

	public function register_post_type(): void {
		register_post_type(
			self::CPT,
			[
				'labels' => [
					'name'               => __( 'Notices', 'rksiksha' ),
					'singular_name'      => __( 'Notice', 'rksiksha' ),
					'add_new_item'       => __( 'Add New Notice', 'rksiksha' ),
					'edit_item'          => __( 'Edit Notice', 'rksiksha' ),
					'all_items'          => __( 'Notices', 'rksiksha' ),
					'menu_name'          => __( 'Notices', 'rksiksha' ),
					'not_found'          => __( 'No notices found.', 'rksiksha' ),
					'not_found_in_trash' => __( 'No notices found in Trash.', 'rksiksha' ),
				],
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => false,
				'show_in_rest'        => true,
				'supports'            => [ 'title', 'editor', 'excerpt', 'thumbnail' ],
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
			]
		);
	}

	public function register_meta_fields(): void {
		$bool = static fn( $value ) => filter_var( $value, FILTER_VALIDATE_BOOLEAN );
		$text = static fn( $value ) => sanitize_text_field( (string) $value );
		$url  = static fn( $value ) => esc_url_raw( (string) $value );
		$int  = static fn( $value ) => absint( $value );
		$date = static function ( $value ): string {
			$value = sanitize_text_field( (string) $value );
			return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ? $value : '';
		};

		$meta = [
			self::M_ACTIVE     => [ 'boolean', $bool ],
			self::M_IMPORTANT  => [ 'boolean', $bool ],
			self::M_LINK_TEXT  => [ 'string', $text ],
			self::M_LINK_URL   => [ 'string', $url ],
			self::M_ATTACHMENT => [ 'integer', $int ],
			self::M_EXPIRES_ON => [ 'string', $date ],
		];

		foreach ( $meta as $key => [ $type, $sanitize ] ) {
			register_post_meta(
				self::CPT,
				$key,
				[
					'show_in_rest'      => true,
					'single'            => true,
					'type'              => $type,
					'sanitize_callback' => $sanitize,
					'auth_callback'     => static fn() => current_user_can( 'edit_posts' ),
				]
			);
		}
	}

	public function register_meta_box(): void {
		add_meta_box(
			'rks_notice_details',
			__( 'Notice Details', 'rksiksha' ),
			[ $this, 'render_meta_box' ],
			self::CPT,
			'normal',
			'high'
		);
	}

	public function render_meta_box( \WP_Post $post ): void {
		wp_nonce_field( self::NONCE, self::NONCE );

		$active        = get_post_meta( $post->ID, self::M_ACTIVE, true );
		$is_active     = '' === $active || (bool) $active;
		$is_important  = (bool) get_post_meta( $post->ID, self::M_IMPORTANT, true );
		$link_text     = (string) get_post_meta( $post->ID, self::M_LINK_TEXT, true );
		$link_url      = (string) get_post_meta( $post->ID, self::M_LINK_URL, true );
		$attachment_id = (int) get_post_meta( $post->ID, self::M_ATTACHMENT, true );
		$expires_on    = (string) get_post_meta( $post->ID, self::M_EXPIRES_ON, true );
		$file_url      = $attachment_id ? (string) wp_get_attachment_url( $attachment_id ) : '';
		$file_label    = $attachment_id ? get_the_title( $attachment_id ) : '';
		?>
		<div class="rks-notice-fields">
			<div class="rks-notice-fields__row rks-notice-fields__row--checks">
				<label>
					<input type="checkbox" name="rks_notice_is_active" value="1" <?php checked( $is_active ); ?>>
					Show on homepage
				</label>
				<label>
					<input type="checkbox" name="rks_notice_is_important" value="1" <?php checked( $is_important ); ?>>
					Mark as important
				</label>
			</div>

			<div class="rks-notice-fields__grid">
				<p>
					<label for="rks_notice_link_text">Button text</label>
					<input type="text" id="rks_notice_link_text" name="rks_notice_link_text" class="widefat" value="<?php echo esc_attr( $link_text ); ?>" placeholder="Read More">
				</p>
				<p>
					<label for="rks_notice_link_url">Button link</label>
					<input type="url" id="rks_notice_link_url" name="rks_notice_link_url" class="widefat" value="<?php echo esc_attr( $link_url ); ?>" placeholder="https://... or /admission/">
				</p>
			</div>

			<div class="rks-notice-fields__grid">
				<p>
					<label for="rks_notice_expires_on">Expiry date</label>
					<input type="date" id="rks_notice_expires_on" name="rks_notice_expires_on" class="widefat" value="<?php echo esc_attr( $expires_on ); ?>">
					<span class="description">Leave blank to keep this notice active until you uncheck it.</span>
				</p>
				<div class="rks-notice-attachment">
					<label>Attachment</label>
					<input type="hidden" name="rks_notice_attachment_id" value="<?php echo esc_attr( (string) $attachment_id ); ?>">
					<div class="rks-notice-attachment__actions">
						<button type="button" class="button rks-notice-attachment-select">Select file</button>
						<button type="button" class="button rks-notice-attachment-remove" <?php echo $attachment_id ? '' : 'style="display:none"'; ?>>Remove</button>
					</div>
					<a class="rks-notice-attachment__name" href="<?php echo esc_url( $file_url ); ?>" target="_blank" rel="noopener" <?php echo $attachment_id ? '' : 'style="display:none"'; ?>>
						<?php echo esc_html( $file_label ?: basename( $file_url ) ); ?>
					</a>
				</div>
			</div>
		</div>
		<?php
	}

	public function save_meta( int $post_id, \WP_Post $post ): void {
		if ( self::CPT !== $post->post_type ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( (string) $_POST[ self::NONCE ] ) ), self::NONCE ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		update_post_meta( $post_id, self::M_ACTIVE, isset( $_POST['rks_notice_is_active'] ) ? 1 : 0 );
		update_post_meta( $post_id, self::M_IMPORTANT, isset( $_POST['rks_notice_is_important'] ) ? 1 : 0 );
		update_post_meta( $post_id, self::M_LINK_TEXT, sanitize_text_field( wp_unslash( (string) ( $_POST['rks_notice_link_text'] ?? '' ) ) ) );
		update_post_meta( $post_id, self::M_LINK_URL, esc_url_raw( wp_unslash( (string) ( $_POST['rks_notice_link_url'] ?? '' ) ) ) );
		update_post_meta( $post_id, self::M_ATTACHMENT, absint( $_POST['rks_notice_attachment_id'] ?? 0 ) );

		$expires_on = sanitize_text_field( wp_unslash( (string) ( $_POST['rks_notice_expires_on'] ?? '' ) ) );
		update_post_meta( $post_id, self::M_EXPIRES_ON, preg_match( '/^\d{4}-\d{2}-\d{2}$/', $expires_on ) ? $expires_on : '' );
	}

	public function enqueue_admin_assets( string $hook ): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || self::CPT !== $screen->post_type ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_script( 'jquery' );

		$script = <<<'JS'
jQuery(function($) {
	$('.rks-notice-attachment-select').on('click', function(e) {
		e.preventDefault();
		var $wrap = $(this).closest('.rks-notice-attachment');
		var frame = wp.media({
			title: 'Select notice attachment',
			button: { text: 'Use this file' },
			multiple: false
		});
		frame.on('select', function() {
			var file = frame.state().get('selection').first().toJSON();
			$wrap.find('input[name="rks_notice_attachment_id"]').val(file.id);
			$wrap.find('.rks-notice-attachment__name')
				.attr('href', file.url)
				.text(file.filename || file.title || file.url)
				.show();
			$wrap.find('.rks-notice-attachment-remove').show();
		});
		frame.open();
	});

	$('.rks-notice-attachment-remove').on('click', function(e) {
		e.preventDefault();
		var $wrap = $(this).closest('.rks-notice-attachment');
		$wrap.find('input[name="rks_notice_attachment_id"]').val('0');
		$wrap.find('.rks-notice-attachment__name').attr('href', '').text('').hide();
		$(this).hide();
	});
});
JS;

		wp_add_inline_script( 'jquery-core', $script );
		add_action( 'admin_print_styles', [ $this, 'print_admin_css' ] );
	}

	public function print_admin_css(): void {
		?>
		<style>
			.rks-notice-fields { padding: 4px 0; }
			.rks-notice-fields label { display: block; margin-bottom: 6px; font-weight: 600; color: #26353b; }
			.rks-notice-fields__row--checks { display: flex; flex-wrap: wrap; gap: 18px; margin-bottom: 14px; }
			.rks-notice-fields__row--checks label { display: inline-flex; align-items: center; gap: 7px; margin: 0; }
			.rks-notice-fields__grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; margin-bottom: 12px; }
			.rks-notice-fields__grid p { margin: 0; }
			.rks-notice-fields input[type="text"],
			.rks-notice-fields input[type="url"],
			.rks-notice-fields input[type="date"] { min-height: 38px; }
			.rks-notice-attachment__actions { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 8px; }
			.rks-notice-attachment__name { display: inline-block; font-weight: 600; text-decoration: none; }
			@media (max-width: 782px) { .rks-notice-fields__grid { grid-template-columns: 1fr; } }
		</style>
		<?php
	}

	public function columns( array $columns ): array {
		$new = [];
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['rks_notice_active']     = __( 'Active', 'rksiksha' );
				$new['rks_notice_important']  = __( 'Important', 'rksiksha' );
				$new['rks_notice_attachment'] = __( 'Attachment', 'rksiksha' );
				$new['rks_notice_expires']    = __( 'Expires', 'rksiksha' );
			}
		}
		return $new;
	}

	public function render_column( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'rks_notice_active':
				echo (bool) get_post_meta( $post_id, self::M_ACTIVE, true ) ? 'Yes' : 'No';
				break;
			case 'rks_notice_important':
				echo (bool) get_post_meta( $post_id, self::M_IMPORTANT, true ) ? 'Yes' : '-';
				break;
			case 'rks_notice_attachment':
				$attachment_id = (int) get_post_meta( $post_id, self::M_ATTACHMENT, true );
				if ( $attachment_id ) {
					$url = wp_get_attachment_url( $attachment_id );
					echo $url ? '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">View file</a>' : '-';
				} else {
					echo '-';
				}
				break;
			case 'rks_notice_expires':
				$expires = (string) get_post_meta( $post_id, self::M_EXPIRES_ON, true );
				echo $expires ? esc_html( mysql2date( get_option( 'date_format' ), $expires ) ) : '-';
				break;
		}
	}

	/**
	 * @return \WP_Post[]
	 */
	public static function get_active( int $limit = 4 ): array {
		$today = current_time( 'Y-m-d' );

		$query = new \WP_Query(
			[
				'post_type'      => self::CPT,
				'post_status'    => 'publish',
				'posts_per_page' => max( 1, $limit ),
				'meta_key'       => self::M_IMPORTANT,
				'orderby'        => [
					'meta_value_num' => 'DESC',
					'date'           => 'DESC',
				],
				'order'          => 'DESC',
				'no_found_rows'  => true,
				'meta_query'     => [
					'relation' => 'AND',
					[
						'key'     => self::M_ACTIVE,
						'value'   => '1',
						'compare' => '=',
					],
					[
						'relation' => 'OR',
						[
							'key'     => self::M_EXPIRES_ON,
							'compare' => 'NOT EXISTS',
						],
						[
							'key'     => self::M_EXPIRES_ON,
							'value'   => '',
							'compare' => '=',
						],
						[
							'key'     => self::M_EXPIRES_ON,
							'value'   => $today,
							'compare' => '>=',
							'type'    => 'DATE',
						],
					],
				],
			]
		);

		return $query->posts;
	}
}
