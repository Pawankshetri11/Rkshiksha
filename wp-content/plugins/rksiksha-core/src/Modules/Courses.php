<?php
/**
 * Courses module — registers `rks_course` + `rks_lesson` CPTs and `rks_course_cat` taxonomy.
 *
 * Lessons are linked to courses via post_meta (`_rks_lesson_course_id`) — faster than
 * hierarchical post_parent at scale, and keeps lesson permalinks flat.
 *
 * @package RKSiksha\Core
 */

declare( strict_types=1 );

namespace RKSiksha\Core\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Courses {

	public const CPT_COURSE   = 'rks_course';
	public const CPT_LESSON   = 'rks_lesson';
	public const TAX_CATEGORY = 'rks_course_cat';

	public const CAT_CERTIFICATE = 'certificate-courses';
	public const CAT_DIPLOMA     = 'diploma-courses';

	/* Meta keys — single source of truth */
	public const M_PRICE          = '_rks_course_price';
	public const M_IS_PAID        = '_rks_course_is_paid';
	public const M_DURATION       = '_rks_course_duration';
	public const M_LEVEL          = '_rks_course_level';
	public const M_INSTRUCTOR     = '_rks_course_instructor';
	public const M_SHORT_DESC     = '_rks_course_short_desc';
	public const M_LESSON_COUNT   = '_rks_course_lesson_count';
	public const M_VIDEO_INTRO    = '_rks_course_video_intro';
	public const M_RATING         = '_rks_course_rating';
	public const M_STUDENTS_COUNT = '_rks_course_students_count';
	public const M_MODE           = '_rks_course_mode';            // online | offline | hybrid
	public const M_COMPLETION     = '_rks_course_completion_rule'; // lessons | quiz | both
	public const M_PAYMENT_MODE   = '_rks_course_payment_mode';    // one_time | monthly
	public const M_MONTHLY_FEE    = '_rks_course_monthly_fee';
	public const M_TOTAL_MONTHS   = '_rks_course_total_months';
	public const M_DURATION_VALUE = '_rks_course_duration_value';  // number
	public const M_DURATION_UNIT  = '_rks_course_duration_unit';   // months | weeks
	public const M_SEAT_LIMIT     = '_rks_course_seat_limit';      // 0 = unlimited
	public const M_ENROLLED_COUNT = '_rks_course_enrolled_count';  // denormalized

	public const ML_COURSE_ID    = '_rks_lesson_course_id';
	public const ML_VIDEO_TYPE   = '_rks_lesson_video_type';
	public const ML_VIDEO_URL    = '_rks_lesson_video_url';
	public const ML_DURATION_MIN = '_rks_lesson_duration_min';
	public const ML_ORDER        = '_rks_lesson_order';
	public const ML_FREE_PREVIEW = '_rks_lesson_free_preview';

	public function register(): void {
		add_action( 'init', [ $this, 'register_taxonomy' ], 5 );
		add_action( 'init', [ $this, 'register_post_types' ], 6 );
		add_action( 'init', [ $this, 'register_meta_fields' ], 7 );
		add_action( 'init', [ self::class, 'ensure_admission_categories' ], 8 );

		// Maintain cached lesson count on the parent course
		add_action( 'save_post_' . self::CPT_LESSON, [ $this, 'recount_course_lessons' ], 20, 1 );
		add_action( 'deleted_post', [ $this, 'on_lesson_deleted' ], 20, 2 );

		add_filter( 'manage_' . self::CPT_COURSE . '_posts_columns', [ $this, 'course_columns' ] );
		add_action( 'manage_' . self::CPT_COURSE . '_posts_custom_column', [ $this, 'render_course_column' ], 10, 2 );

		// Meta boxes (lightweight — Phase 2 admin UX)
		add_action( 'add_meta_boxes', [ $this, 'register_meta_boxes' ] );
		add_action( 'save_post_' . self::CPT_COURSE, [ $this, 'save_course_meta' ], 10, 2 );
		add_action( 'save_post_' . self::CPT_LESSON, [ $this, 'save_lesson_meta' ], 10, 2 );
	}

	/**
	 * Convert supported public video URLs into privacy-friendly inline embeds.
	 * Handles normal YouTube links, youtu.be links, Shorts, Live and Vimeo.
	 */
	public static function video_embed_url( string $url, string $provider = '' ): string {
		$url = trim( $url );
		if ( '' === $url ) {
			return '';
		}

		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		$host = preg_replace( '/^(?:www\.|m\.)/', '', $host );
		$path = trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' );
		$provider = strtolower( trim( $provider ) );

		$is_youtube = 'youtube' === $provider
			|| in_array( $host, [ 'youtube.com', 'youtube-nocookie.com', 'youtu.be' ], true );

		if ( $is_youtube ) {
			$video_id = '';
			if ( 'youtu.be' === $host ) {
				$segments = explode( '/', $path );
				$video_id = (string) ( $segments[0] ?? '' );
			} else {
				$query = [];
				parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query );
				if ( ! empty( $query['v'] ) ) {
					$video_id = (string) $query['v'];
				} else {
					$segments = explode( '/', $path );
					if ( isset( $segments[0], $segments[1] ) && in_array( $segments[0], [ 'embed', 'shorts', 'live' ], true ) ) {
						$video_id = (string) $segments[1];
					}
				}
			}

			if ( preg_match( '/^[A-Za-z0-9_-]{6,20}$/', $video_id ) ) {
				return 'https://www.youtube-nocookie.com/embed/' . rawurlencode( $video_id ) . '?rel=0&modestbranding=1';
			}
		}

		$is_vimeo = 'vimeo' === $provider || false !== strpos( $host, 'vimeo.com' );
		if ( $is_vimeo ) {
			$segments = array_reverse( array_filter( explode( '/', $path ) ) );
			foreach ( $segments as $segment ) {
				if ( ctype_digit( (string) $segment ) ) {
					return 'https://player.vimeo.com/video/' . rawurlencode( (string) $segment );
				}
			}
		}

		return '';
	}

	/** Whether the URL points to a browser-playable uploaded video file. */
	public static function is_direct_video_url( string $url ): bool {
		$path = (string) wp_parse_url( trim( $url ), PHP_URL_PATH );
		$ext  = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
		return in_array( $ext, [ 'mp4', 'm4v', 'webm', 'ogv', 'ogg', 'mov' ], true );
	}

	public function register_taxonomy(): void {
		register_taxonomy(
			self::TAX_CATEGORY,
			[ self::CPT_COURSE ],
			[
				'label'             => __( 'Course Categories', 'rksiksha' ),
				'public'            => true,
				'hierarchical'      => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => [ 'slug' => 'course-category' ],
			]
		);
	}

	public static function ensure_admission_categories(): void {
		$terms = [
			self::CAT_CERTIFICATE => 'Certificate Courses',
			self::CAT_DIPLOMA     => 'Diploma Courses',
		];

		foreach ( $terms as $slug => $name ) {
			if ( ! term_exists( $slug, self::TAX_CATEGORY ) ) {
				wp_insert_term( $name, self::TAX_CATEGORY, [ 'slug' => $slug ] );
			}
		}

		$course_ids = get_posts( [
			'post_type'      => self::CPT_COURSE,
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		] );

		foreach ( $course_ids as $course_id ) {
			$slugs = wp_get_object_terms( (int) $course_id, self::TAX_CATEGORY, [ 'fields' => 'slugs' ] );
			if ( is_wp_error( $slugs ) || array_intersect( [ self::CAT_CERTIFICATE, self::CAT_DIPLOMA ], (array) $slugs ) ) {
				continue;
			}

			wp_set_object_terms( (int) $course_id, self::classify_admission_category( (int) $course_id ), self::TAX_CATEGORY, true );
		}
	}

	public static function classify_admission_category( int $course_id ): string {
		$terms = wp_get_object_terms( $course_id, self::TAX_CATEGORY, [ 'fields' => 'slugs' ] );
		if ( ! is_wp_error( $terms ) ) {
			if ( in_array( self::CAT_DIPLOMA, $terms, true ) ) {
				return self::CAT_DIPLOMA;
			}
			if ( in_array( self::CAT_CERTIFICATE, $terms, true ) ) {
				return self::CAT_CERTIFICATE;
			}
		}

		$title = strtolower( (string) get_the_title( $course_id ) );
		return ( str_contains( $title, 'diploma' ) || str_contains( $title, 'd.c.a' ) || str_contains( $title, 'a.d.c.a' ) )
			? self::CAT_DIPLOMA
			: self::CAT_CERTIFICATE;
	}

	public function register_post_types(): void {
		register_post_type(
			self::CPT_COURSE,
			[
				'labels' => [
					'name'                  => __( 'Courses', 'rksiksha' ),
					'singular_name'         => __( 'Course', 'rksiksha' ),
					'add_new_item'          => __( 'Add New Course', 'rksiksha' ),
					'edit_item'             => __( 'Edit Course', 'rksiksha' ),
					'all_items'             => __( 'All Courses', 'rksiksha' ),
					'menu_name'             => __( 'Courses', 'rksiksha' ),
					'featured_image'        => __( 'Course Thumbnail', 'rksiksha' ),
					'set_featured_image'    => __( 'Set course thumbnail', 'rksiksha' ),
				],
				'public'              => true,
				'has_archive'         => 'courses',
				'show_in_rest'        => true,
				'menu_icon'           => 'dashicons-book-alt',
				'menu_position'       => 4,
				'supports'            => [ 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields' ],
				'taxonomies'          => [ self::TAX_CATEGORY ],
				'rewrite'             => [ 'slug' => 'courses', 'with_front' => false ],
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
			]
		);

		register_post_type(
			self::CPT_LESSON,
			[
				'labels' => [
					'name'              => __( 'Lessons', 'rksiksha' ),
					'singular_name'     => __( 'Lesson', 'rksiksha' ),
					'add_new_item'      => __( 'Add New Lesson', 'rksiksha' ),
					'edit_item'         => __( 'Edit Lesson', 'rksiksha' ),
					'all_items'         => __( 'All Lessons', 'rksiksha' ),
					'menu_name'         => __( 'Lessons', 'rksiksha' ),
				],
				'public'              => true,
				'has_archive'         => false,
				'show_in_rest'        => true,
				'show_in_menu'        => 'edit.php?post_type=' . self::CPT_COURSE,
				'supports'            => [ 'title', 'editor', 'thumbnail', 'custom-fields' ],
				'rewrite'             => [ 'slug' => 'lessons', 'with_front' => false ],
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
			]
		);
	}

	public function register_meta_fields(): void {
		// Sanitizers wrapped to ignore extra args ($key, $object_type, $subtype) that WP forwards.
		$float = static fn( $v ) => (float) $v;
		$bool  = static fn( $v ) => filter_var( $v, FILTER_VALIDATE_BOOLEAN );
		$int   = static fn( $v ) => absint( $v );
		$text  = static fn( $v ) => sanitize_text_field( (string) $v );
		$ttext = static fn( $v ) => sanitize_textarea_field( (string) $v );
		$url   = static fn( $v ) => esc_url_raw( (string) $v );

		$course_meta = [
			self::M_PRICE          => [ 'number',  $float ],
			self::M_IS_PAID        => [ 'boolean', $bool ],
			self::M_DURATION       => [ 'string',  $text ],
			self::M_LEVEL          => [ 'string',  $text ],
			self::M_INSTRUCTOR     => [ 'string',  $text ],
			self::M_SHORT_DESC     => [ 'string',  $ttext ],
			self::M_LESSON_COUNT   => [ 'integer', $int ],
			self::M_VIDEO_INTRO    => [ 'string',  $url ],
			self::M_RATING         => [ 'number',  $float ],
			self::M_STUDENTS_COUNT => [ 'integer', $int ],
			self::M_MODE           => [ 'string',  $text ],
			self::M_COMPLETION     => [ 'string',  $text ],
			self::M_PAYMENT_MODE   => [ 'string',  $text ],
			self::M_MONTHLY_FEE    => [ 'number',  $float ],
			self::M_TOTAL_MONTHS   => [ 'integer', $int ],
			self::M_DURATION_VALUE => [ 'integer', $int ],
			self::M_DURATION_UNIT  => [ 'string',  $text ],
			self::M_SEAT_LIMIT     => [ 'integer', $int ],
			self::M_ENROLLED_COUNT => [ 'integer', $int ],
		];

		foreach ( $course_meta as $key => [$type, $sanitize] ) {
			register_post_meta( self::CPT_COURSE, $key, [
				'show_in_rest'      => true,
				'single'            => true,
				'type'              => $type,
				'sanitize_callback' => $sanitize,
				'auth_callback'     => static fn() => current_user_can( 'edit_posts' ),
			] );
		}

		$lesson_meta = [
			self::ML_COURSE_ID    => [ 'integer', $int ],
			self::ML_VIDEO_TYPE   => [ 'string',  $text ],
			self::ML_VIDEO_URL    => [ 'string',  $url ],
			self::ML_DURATION_MIN => [ 'integer', $int ],
			self::ML_ORDER        => [ 'integer', $int ],
			self::ML_FREE_PREVIEW => [ 'boolean', $bool ],
		];

		foreach ( $lesson_meta as $key => [$type, $sanitize] ) {
			register_post_meta( self::CPT_LESSON, $key, [
				'show_in_rest'      => true,
				'single'            => true,
				'type'              => $type,
				'sanitize_callback' => $sanitize,
				'auth_callback'     => static fn() => current_user_can( 'edit_posts' ),
			] );
		}
	}

	public function sanitize_bool( $value ): bool {
		return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}

	/* ------------------------------------------------------------------ */
	/* QUERY HELPERS                                                       */
	/* ------------------------------------------------------------------ */

	/**
	 * Fetch lessons of a course in display order.
	 *
	 * @return \WP_Post[]
	 */
	public static function get_lessons( int $course_id, bool $only_published = true ): array {
		$args = [
			'post_type'      => self::CPT_LESSON,
			'posts_per_page' => 200,
			'meta_key'       => self::ML_ORDER,
			'orderby'        => 'meta_value_num',
			'order'          => 'ASC',
			'no_found_rows'  => true,
			'meta_query'     => [
				[
					'key'   => self::ML_COURSE_ID,
					'value' => $course_id,
					'type'  => 'NUMERIC',
				],
			],
		];
		if ( $only_published ) {
			$args['post_status'] = 'publish';
		}
		$q = new \WP_Query( $args );
		return $q->posts;
	}

	public static function get_course_for_lesson( int $lesson_id ): ?\WP_Post {
		$cid = (int) get_post_meta( $lesson_id, self::ML_COURSE_ID, true );
		if ( $cid <= 0 ) {
			return null;
		}
		$post = get_post( $cid );
		return $post && self::CPT_COURSE === $post->post_type ? $post : null;
	}

	public function recount_course_lessons( int $lesson_id ): void {
		$course_id = (int) get_post_meta( $lesson_id, self::ML_COURSE_ID, true );
		if ( $course_id <= 0 ) {
			return;
		}
		$count = (int) ( new \WP_Query( [
			'post_type'      => self::CPT_LESSON,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => [ [ 'key' => self::ML_COURSE_ID, 'value' => $course_id, 'type' => 'NUMERIC' ] ],
		] ) )->found_posts;

		// found_posts is 0 when no_found_rows — use post_count from query results instead
		$ids = ( new \WP_Query( [
			'post_type'      => self::CPT_LESSON,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => [ [ 'key' => self::ML_COURSE_ID, 'value' => $course_id, 'type' => 'NUMERIC' ] ],
		] ) )->posts;
		update_post_meta( $course_id, self::M_LESSON_COUNT, count( $ids ) );
	}

	public function on_lesson_deleted( int $post_id, \WP_Post $post ): void {
		if ( $post && self::CPT_LESSON === $post->post_type ) {
			$this->recount_course_lessons( $post_id );
		}
	}

	/* ------------------------------------------------------------------ */
	/* ADMIN COLUMNS                                                       */
	/* ------------------------------------------------------------------ */

	public function course_columns( array $cols ): array {
		$insert_after = 'title';
		$new          = [];
		foreach ( $cols as $k => $v ) {
			$new[ $k ] = $v;
			if ( $k === $insert_after ) {
				$new['rks_lessons']    = __( 'Lessons', 'rksiksha' );
				$new['rks_price']      = __( 'Price', 'rksiksha' );
				$new['rks_level']      = __( 'Level', 'rksiksha' );
			}
		}
		return $new;
	}

	public function render_course_column( string $col, int $post_id ): void {
		switch ( $col ) {
			case 'rks_lessons':
				echo (int) get_post_meta( $post_id, self::M_LESSON_COUNT, true );
				break;
			case 'rks_price':
				$paid = (bool) get_post_meta( $post_id, self::M_IS_PAID, true );
				if ( ! $paid ) { echo '<em>Free</em>'; break; }
				$settings = (array) get_option( 'rks_settings', [] );
				$sym      = (string) ( $settings['currency_symbol'] ?? '₹' );
				echo esc_html( $sym . number_format( (float) get_post_meta( $post_id, self::M_PRICE, true ), 0 ) );
				break;
			case 'rks_level':
				echo esc_html( (string) get_post_meta( $post_id, self::M_LEVEL, true ) );
				break;
		}
	}

	/* ------------------------------------------------------------------ */
	/* META BOXES                                                          */
	/* ------------------------------------------------------------------ */

	public function register_meta_boxes(): void {
		add_meta_box(
			'rks_course_meta',
			__( 'Course Configuration', 'rksiksha' ),
			[ $this, 'render_course_meta_box' ],
			self::CPT_COURSE,
			'normal',
			'high'
		);
		add_meta_box(
			'rks_lesson_meta',
			__( 'Lesson Details', 'rksiksha' ),
			[ $this, 'render_lesson_meta_box' ],
			self::CPT_LESSON,
			'normal',
			'high'
		);
	}

	public function render_course_meta_box( \WP_Post $post ): void {
		wp_nonce_field( 'rks_course_meta', 'rks_course_meta_nonce' );
		$paid          = (bool) get_post_meta( $post->ID, self::M_IS_PAID, true );
		$cur_level     = (string) get_post_meta( $post->ID, self::M_LEVEL, true );
		$cur_mode      = (string) get_post_meta( $post->ID, self::M_MODE, true ) ?: 'online';
		$cur_comp      = (string) get_post_meta( $post->ID, self::M_COMPLETION, true ) ?: 'lessons';
		$cur_pay_mode  = (string) get_post_meta( $post->ID, self::M_PAYMENT_MODE, true ) ?: 'one_time';
		$dur_value     = (int) get_post_meta( $post->ID, self::M_DURATION_VALUE, true );
		$dur_unit      = (string) get_post_meta( $post->ID, self::M_DURATION_UNIT, true ) ?: 'months';
		$monthly_fee   = (float) get_post_meta( $post->ID, self::M_MONTHLY_FEE, true );
		$total_months  = (int) get_post_meta( $post->ID, self::M_TOTAL_MONTHS, true );
		$seat_limit    = (int) get_post_meta( $post->ID, self::M_SEAT_LIMIT, true );
		$enrolled_cnt  = (int) get_post_meta( $post->ID, self::M_ENROLLED_COUNT, true );
		$legacy_dur    = (string) get_post_meta( $post->ID, self::M_DURATION, true );
		?>
		<div class="rks-course-grid">
			<section class="rks-course-section">
				<h3><span class="dashicons dashicons-info"></span> Basic Information</h3>
				<div class="rks-field">
					<label>Short Description</label>
					<textarea name="rks_short_desc" rows="3" class="widefat"><?php echo esc_textarea( (string) get_post_meta( $post->ID, self::M_SHORT_DESC, true ) ); ?></textarea>
					<p class="description">One-line summary shown in course cards.</p>
				</div>
				<div class="rks-field-row">
					<div class="rks-field">
						<label>Instructor</label>
						<input type="text" name="rks_instructor" class="widefat" value="<?php echo esc_attr( (string) get_post_meta( $post->ID, self::M_INSTRUCTOR, true ) ); ?>">
					</div>
					<div class="rks-field">
						<label>Level</label>
						<select name="rks_level" class="widefat">
							<?php foreach ( [ 'beginner' => 'Beginner', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced', 'all-levels' => 'All Levels' ] as $val => $label ) :
								printf( '<option value="%s"%s>%s</option>', esc_attr( $val ), selected( $cur_level, $val, false ), esc_html( $label ) );
							endforeach; ?>
						</select>
					</div>
				</div>
				<div class="rks-field">
					<label>Intro Video URL (YouTube / Vimeo)</label>
					<input type="url" name="rks_video_intro" class="widefat" value="<?php echo esc_attr( (string) get_post_meta( $post->ID, self::M_VIDEO_INTRO, true ) ); ?>">
				</div>
			</section>

			<section class="rks-course-section">
				<h3><span class="dashicons dashicons-clock"></span> Duration & Mode</h3>
				<div class="rks-field-row">
					<div class="rks-field">
						<label>Duration</label>
						<div class="rks-inline">
							<input type="number" min="0" step="1" name="rks_duration_value" style="width:80px;" value="<?php echo esc_attr( (string) $dur_value ); ?>">
							<select name="rks_duration_unit" style="width:120px;">
								<option value="months" <?php selected( $dur_unit, 'months' ); ?>>Months</option>
								<option value="weeks"  <?php selected( $dur_unit, 'weeks' ); ?>>Weeks</option>
								<option value="days"   <?php selected( $dur_unit, 'days' ); ?>>Days</option>
							</select>
						</div>
						<?php if ( $legacy_dur && ! $dur_value ) : ?>
							<p class="description">Legacy value: <em><?php echo esc_html( $legacy_dur ); ?></em> — please re-enter using number + unit.</p>
						<?php endif; ?>
					</div>
					<div class="rks-field">
						<label>Course Mode</label>
						<select name="rks_mode" class="widefat">
							<?php foreach ( [ 'online' => 'Online (video lessons)', 'offline' => 'Offline (coaching institute)', 'hybrid' => 'Hybrid (both)' ] as $val => $label ) :
								printf( '<option value="%s"%s>%s</option>', esc_attr( $val ), selected( $cur_mode, $val, false ), esc_html( $label ) );
							endforeach; ?>
						</select>
					</div>
				</div>
				<div class="rks-field">
					<label>Completion Rule</label>
					<select name="rks_completion" class="widefat">
						<?php foreach ( [ 'lessons' => 'All lessons watched', 'quiz' => 'Pass final exam only', 'both' => 'Lessons + final exam' ] as $val => $label ) :
							printf( '<option value="%s"%s>%s</option>', esc_attr( $val ), selected( $cur_comp, $val, false ), esc_html( $label ) );
						endforeach; ?>
					</select>
					<p class="description">Decides when the certificate is auto-issued.</p>
				</div>
			</section>

			<section class="rks-course-section rks-course-section--pricing">
				<h3><span class="dashicons dashicons-money-alt"></span> Pricing & Payment</h3>
				<label class="rks-check">
					<input type="checkbox" name="rks_is_paid" value="1" <?php checked( $paid ); ?>>
					<strong>This is a paid course</strong>
				</label>

				<div class="rks-field-row">
					<div class="rks-field">
						<label>Payment Mode</label>
						<select name="rks_payment_mode" class="widefat" id="rks-pay-mode">
							<option value="one_time" <?php selected( $cur_pay_mode, 'one_time' ); ?>>One-time payment</option>
							<option value="monthly"  <?php selected( $cur_pay_mode, 'monthly' ); ?>>Monthly (installments)</option>
						</select>
					</div>
					<div class="rks-field rks-pay-onetime">
						<label>Total Price (₹)</label>
						<input type="number" min="0" step="1" name="rks_price" class="widefat" value="<?php echo esc_attr( (string) get_post_meta( $post->ID, self::M_PRICE, true ) ); ?>">
					</div>
				</div>

				<div class="rks-field-row rks-pay-monthly" style="<?php echo $cur_pay_mode === 'monthly' ? '' : 'display:none;'; ?>">
					<div class="rks-field">
						<label>Monthly Fee (₹)</label>
						<input type="number" min="0" step="1" name="rks_monthly_fee" class="widefat" value="<?php echo esc_attr( (string) ( $monthly_fee ?: '' ) ); ?>">
					</div>
					<div class="rks-field">
						<label>Total Months</label>
						<input type="number" min="1" step="1" name="rks_total_months" class="widefat" value="<?php echo esc_attr( (string) ( $total_months ?: '' ) ); ?>">
						<p class="description">Total = Monthly fee × months. Student pays one month at a time.</p>
					</div>
				</div>
			</section>

			<section class="rks-course-section">
				<h3><span class="dashicons dashicons-groups"></span> Enrollment Limit</h3>
				<div class="rks-field-row">
					<div class="rks-field">
						<label>Seat Limit</label>
						<input type="number" min="0" step="1" name="rks_seat_limit" class="widefat" value="<?php echo esc_attr( (string) $seat_limit ); ?>">
						<p class="description"><strong>0</strong> = unlimited seats. For offline batches, set the classroom capacity.</p>
					</div>
					<div class="rks-field">
						<label>Current Enrollments</label>
						<input type="text" class="widefat" value="<?php echo esc_attr( (string) $enrolled_cnt ); ?>" readonly disabled>
						<p class="description">Auto-tracked. <?php if ( $seat_limit > 0 ) : ?><?php echo max( 0, $seat_limit - $enrolled_cnt ); ?> seat(s) remaining.<?php endif; ?></p>
					</div>
				</div>
			</section>
		</div>

		<script>
		(function () {
			var sel  = document.getElementById('rks-pay-mode');
			var onet = document.querySelector('.rks-pay-onetime');
			var mon  = document.querySelector('.rks-pay-monthly');
			if (!sel) return;
			sel.addEventListener('change', function () {
				var monthly = sel.value === 'monthly';
				if (mon)  mon.style.display  = monthly ? '' : 'none';
				if (onet) onet.style.display = monthly ? 'none' : '';
			});
			if (sel.value === 'monthly' && onet) onet.style.display = 'none';
		})();
		</script>
		<style>
			.rks-course-grid { display: grid; gap: 16px; margin-top: 8px; }
			.rks-course-section { background:#fff; border:1px solid #e5e7eb; border-radius:8px; padding:16px 18px; }
			.rks-course-section h3 { margin: 0 0 14px; padding-bottom: 10px; border-bottom: 1px solid #f3f4f6; font-size: 14px; display:flex; align-items:center; gap:6px; color:#2C687B; }
			.rks-course-section h3 .dashicons { color:#8EC9C4; }
			.rks-course-section--pricing { background:#FBF3E9; border-color: rgba(230,57,70,0.15); }
			.rks-field { margin-bottom: 12px; }
			.rks-field label { display:block; font-weight:600; margin-bottom:4px; font-size:12px; color:#374151; }
			.rks-field-row { display:grid; grid-template-columns: 1fr 1fr; gap:14px; }
			.rks-field-row .rks-field { margin-bottom:0; }
			.rks-field .description { margin-top: 4px !important; font-size: 11px; color:#6b7280; }
			.rks-inline { display:flex; gap:8px; align-items:center; }
			.rks-check { display:flex; align-items:center; gap:8px; margin-bottom: 12px; padding: 8px 10px; background:#fff; border-radius:6px; border:1px solid #fde7ea; }
			@media (max-width: 782px) { .rks-field-row { grid-template-columns: 1fr; } }
		</style>
		<?php
	}

	public function render_lesson_meta_box( \WP_Post $post ): void {
		wp_nonce_field( 'rks_lesson_meta', 'rks_lesson_meta_nonce' );

		$courses = get_posts( [
			'post_type'      => self::CPT_COURSE,
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		] );
		$current_course = (int) get_post_meta( $post->ID, self::ML_COURSE_ID, true );
		?>
		<table class="form-table">
			<tr>
				<th><label>Parent Course</label></th>
				<td>
					<select name="rks_lesson_course_id" class="widefat">
						<option value="0">— Select Course —</option>
						<?php foreach ( $courses as $c ) : ?>
							<option value="<?php echo (int) $c->ID; ?>" <?php selected( $current_course, $c->ID ); ?>><?php echo esc_html( $c->post_title ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label>Order</label></th>
				<td><input type="number" min="0" step="1" name="rks_lesson_order" value="<?php echo esc_attr( (string) get_post_meta( $post->ID, self::ML_ORDER, true ) ); ?>"></td>
			</tr>
			<tr>
				<th><label>Video Provider</label></th>
				<td>
					<?php $vt = (string) get_post_meta( $post->ID, self::ML_VIDEO_TYPE, true ); ?>
					<select name="rks_lesson_video_type">
						<option value="">— None —</option>
						<option value="youtube" <?php selected( $vt, 'youtube' ); ?>>YouTube</option>
						<option value="vimeo"   <?php selected( $vt, 'vimeo' ); ?>>Vimeo</option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label>Video URL</label></th>
				<td><input type="url" name="rks_lesson_video_url" class="widefat" value="<?php echo esc_attr( (string) get_post_meta( $post->ID, self::ML_VIDEO_URL, true ) ); ?>"></td>
			</tr>
			<tr>
				<th><label>Duration (minutes)</label></th>
				<td><input type="number" min="0" step="1" name="rks_lesson_duration" value="<?php echo esc_attr( (string) get_post_meta( $post->ID, self::ML_DURATION_MIN, true ) ); ?>"></td>
			</tr>
			<tr>
				<th><label>Free Preview</label></th>
				<td>
					<label>
						<input type="checkbox" name="rks_lesson_free_preview" value="1" <?php checked( (bool) get_post_meta( $post->ID, self::ML_FREE_PREVIEW, true ) ); ?>>
						Allow non-enrolled visitors to preview this lesson
					</label>
				</td>
			</tr>
		</table>
		<?php
	}

	public function save_course_meta( int $post_id, \WP_Post $post ): void {
		if ( ! isset( $_POST['rks_course_meta_nonce'] ) || ! wp_verify_nonce( wp_unslash( (string) $_POST['rks_course_meta_nonce'] ), 'rks_course_meta' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( ! current_user_can( 'edit_post', $post_id ) ) return;

		$post_data = wp_unslash( $_POST );

		update_post_meta( $post_id, self::M_SHORT_DESC, sanitize_textarea_field( (string) ( $post_data['rks_short_desc'] ?? '' ) ) );
		update_post_meta( $post_id, self::M_INSTRUCTOR, sanitize_text_field(    (string) ( $post_data['rks_instructor'] ?? '' ) ) );
		update_post_meta( $post_id, self::M_LEVEL,      sanitize_text_field(    (string) ( $post_data['rks_level']      ?? '' ) ) );
		update_post_meta( $post_id, self::M_IS_PAID,    ! empty( $post_data['rks_is_paid'] ) ? 1 : 0 );
		update_post_meta( $post_id, self::M_PRICE,      (float) ( $post_data['rks_price'] ?? 0 ) );
		update_post_meta( $post_id, self::M_VIDEO_INTRO, esc_url_raw( (string) ( $post_data['rks_video_intro'] ?? '' ) ) );
		update_post_meta( $post_id, self::M_MODE,       sanitize_key( (string) ( $post_data['rks_mode']       ?? 'online' ) ) );
		update_post_meta( $post_id, self::M_COMPLETION, sanitize_key( (string) ( $post_data['rks_completion'] ?? 'lessons' ) ) );

		// Duration (new structured field) — also keep legacy text in sync for old templates.
		$dur_value = absint( $post_data['rks_duration_value'] ?? 0 );
		$dur_unit  = sanitize_key( (string) ( $post_data['rks_duration_unit'] ?? 'months' ) );
		if ( ! in_array( $dur_unit, [ 'months', 'weeks', 'days' ], true ) ) $dur_unit = 'months';
		update_post_meta( $post_id, self::M_DURATION_VALUE, $dur_value );
		update_post_meta( $post_id, self::M_DURATION_UNIT,  $dur_unit );
		if ( $dur_value > 0 ) {
			update_post_meta( $post_id, self::M_DURATION, $dur_value . ' ' . $dur_unit );
		}

		// Payment mode + monthly fields
		$pay_mode = sanitize_key( (string) ( $post_data['rks_payment_mode'] ?? 'one_time' ) );
		if ( ! in_array( $pay_mode, [ 'one_time', 'monthly' ], true ) ) $pay_mode = 'one_time';
		update_post_meta( $post_id, self::M_PAYMENT_MODE, $pay_mode );
		update_post_meta( $post_id, self::M_MONTHLY_FEE,  (float) ( $post_data['rks_monthly_fee']  ?? 0 ) );
		update_post_meta( $post_id, self::M_TOTAL_MONTHS, absint( $post_data['rks_total_months'] ?? 0 ) );

		// If monthly, derive total price (monthly × months) so existing one_time logic still works as fallback.
		if ( $pay_mode === 'monthly' ) {
			$derived = (float) ( $post_data['rks_monthly_fee'] ?? 0 ) * absint( $post_data['rks_total_months'] ?? 0 );
			if ( $derived > 0 ) {
				update_post_meta( $post_id, self::M_PRICE, $derived );
			}
		}

		// Seat limit
		update_post_meta( $post_id, self::M_SEAT_LIMIT, absint( $post_data['rks_seat_limit'] ?? 0 ) );
	}

	public function save_lesson_meta( int $post_id, \WP_Post $post ): void {
		if ( ! isset( $_POST['rks_lesson_meta_nonce'] ) || ! wp_verify_nonce( wp_unslash( (string) $_POST['rks_lesson_meta_nonce'] ), 'rks_lesson_meta' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( ! current_user_can( 'edit_post', $post_id ) ) return;

		$post_data = wp_unslash( $_POST );

		update_post_meta( $post_id, self::ML_COURSE_ID,    absint( $post_data['rks_lesson_course_id'] ?? 0 ) );
		update_post_meta( $post_id, self::ML_ORDER,        absint( $post_data['rks_lesson_order'] ?? 0 ) );
		update_post_meta( $post_id, self::ML_VIDEO_TYPE,   sanitize_text_field( (string) ( $post_data['rks_lesson_video_type'] ?? '' ) ) );
		update_post_meta( $post_id, self::ML_VIDEO_URL,    esc_url_raw(         (string) ( $post_data['rks_lesson_video_url']  ?? '' ) ) );
		update_post_meta( $post_id, self::ML_DURATION_MIN, absint( $post_data['rks_lesson_duration'] ?? 0 ) );
		update_post_meta( $post_id, self::ML_FREE_PREVIEW, ! empty( $post_data['rks_lesson_free_preview'] ) ? 1 : 0 );
	}

	/* ------------------------------------------------------------------ */
	/* SAMPLE DATA SEED — runs once via Activator on first install        */
	/* ------------------------------------------------------------------ */

	public static function seed_samples(): int {
		// Bail if seed already ran or any course already exists
		if ( get_option( 'rks_courses_seeded' ) ) {
			return 0;
		}
		$existing = (int) ( new \WP_Query( [
			'post_type'      => self::CPT_COURSE,
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => false,
		] ) )->found_posts;
		if ( $existing > 0 ) {
			update_option( 'rks_courses_seeded', 1, true );
			return 0;
		}

		$samples = [
			[
				'title'       => 'D.C.A. — Diploma in Computer Applications',
				'category'    => 'Computer',
				'short_desc'  => 'Foundation diploma covering MS Office, Internet, computer fundamentals and practical office skills.',
				'level'       => 'beginner',
				'duration'    => '12 months',
				'instructor'  => 'Rishikul Shiksha Faculty',
				'paid'        => true,
				'price'       => 6000,
				'rating'      => 4.7,
				'students'    => 1850,
				'lessons'     => [
					[ 'Computer Fundamentals', 20 ],
					[ 'Operating System Basics', 18 ],
					[ 'MS Word',               25 ],
					[ 'MS Excel',              30 ],
					[ 'MS PowerPoint',         20 ],
					[ 'Internet & Email',      15 ],
				],
			],
			[
				'title'       => 'Certificate in Hindi Typing (Kruti Dev & Mangal)',
				'category'    => 'Typing',
				'short_desc'  => 'Master Kruti Dev & Mangal font typing with speed and accuracy for govt jobs.',
				'level'       => 'beginner',
				'duration'    => '3 months',
				'instructor'  => 'Rishikul Shiksha Faculty',
				'paid'        => true,
				'price'       => 2000,
				'rating'      => 4.6,
				'students'    => 1420,
				'lessons'     => [
					[ 'Kruti Dev Keyboard Layout', 15 ],
					[ 'Mangal Font Setup',          12 ],
					[ 'Speed Drills',               20 ],
					[ 'Accuracy & Practice Tests',  18 ],
				],
			],
			[
				'title'       => 'Accounting — Tally Prime & Tally ERP 9.0',
				'category'    => 'Accounting',
				'short_desc'  => 'Hands-on accounting with Tally Prime & ERP 9.0 — GST, ledgers, invoicing, payroll.',
				'level'       => 'intermediate',
				'duration'    => '4 months',
				'instructor'  => 'Rishikul Shiksha Faculty',
				'paid'        => true,
				'price'       => 4500,
				'rating'      => 4.8,
				'students'    => 1280,
				'lessons'     => [
					[ 'Accounting Fundamentals', 20 ],
					[ 'Tally Prime Setup',       18 ],
					[ 'Ledgers & Vouchers',      25 ],
					[ 'GST in Tally',            30 ],
					[ 'Inventory & Invoicing',   25 ],
					[ 'Payroll Basics',          22 ],
				],
			],
			[
				'title'       => 'D.T.P. Course — CorelDraw (Desktop Publishing)',
				'category'    => 'Design',
				'short_desc'  => 'Desktop Publishing course covering CorelDraw for design, layout, banners and print work.',
				'level'       => 'beginner',
				'duration'    => '3 months',
				'instructor'  => 'Rishikul Shiksha Faculty',
				'paid'        => true,
				'price'       => 3500,
				'rating'      => 4.6,
				'students'    => 760,
				'lessons'     => [
					[ 'CorelDraw Interface',    15 ],
					[ 'Shapes & Tools',         20 ],
					[ 'Typography & Layout',    22 ],
					[ 'Banners & Posters',      25 ],
					[ 'Print Prep & Export',    18 ],
				],
			],
			[
				'title'       => 'A.D.C.A. — Advance Diploma in Computer Applications',
				'category'    => 'Computer',
				'short_desc'  => 'Advanced diploma — Office, Tally, internet, DTP, typing and more for a complete IT skillset.',
				'level'       => 'advanced',
				'duration'    => '12 months',
				'instructor'  => 'Rishikul Shiksha Faculty',
				'paid'        => true,
				'price'       => 9000,
				'rating'      => 4.8,
				'students'    => 980,
				'lessons'     => [
					[ 'Computer Fundamentals',  18 ],
					[ 'MS Office Suite',        35 ],
					[ 'Tally Essentials',       28 ],
					[ 'DTP Tools',              25 ],
					[ 'Typing Practice',        20 ],
					[ 'Internet & Networking',  20 ],
				],
			],
			[
				'title'       => 'Certificate in HTML & CSS',
				'category'    => 'Web',
				'short_desc'  => 'Build modern responsive web pages with HTML5 & CSS3 — short certificate program.',
				'level'       => 'beginner',
				'duration'    => '2 months',
				'instructor'  => 'Rishikul Shiksha Faculty',
				'paid'        => true,
				'price'       => 2500,
				'rating'      => 4.7,
				'students'    => 640,
				'lessons'     => [
					[ 'HTML Foundations',       20 ],
					[ 'Semantic Tags & Forms',  22 ],
					[ 'CSS Basics',             25 ],
					[ 'Flexbox & Grid',         28 ],
					[ 'Responsive Design',      20 ],
				],
			],
			[
				'title'       => 'Certificate in English Typing',
				'category'    => 'Typing',
				'short_desc'  => 'Improve English typing speed & accuracy for govt-exam grade tests and offices.',
				'level'       => 'beginner',
				'duration'    => '2 months',
				'instructor'  => 'Rishikul Shiksha Faculty',
				'paid'        => true,
				'price'       => 1500,
				'rating'      => 4.5,
				'students'    => 1610,
				'lessons'     => [
					[ 'Keyboard Layout & Posture', 12 ],
					[ 'Home Row Drills',           15 ],
					[ 'Speed Building',            20 ],
					[ 'Accuracy Tests',            18 ],
				],
			],
			[
				'title'       => 'Certificate in Computer Applications (CCA)',
				'category'    => 'Computer',
				'short_desc'  => 'Short certificate in computer applications — basics, MS Office and internet for beginners.',
				'level'       => 'beginner',
				'duration'    => '3 months',
				'instructor'  => 'Rishikul Shiksha Faculty',
				'paid'        => true,
				'price'       => 2500,
				'rating'      => 4.6,
				'students'    => 1320,
				'lessons'     => [
					[ 'Intro to Computers', 15 ],
					[ 'MS Word Essentials', 22 ],
					[ 'MS Excel Essentials', 25 ],
					[ 'Internet & Email',   18 ],
				],
			],
			[
				'title'       => 'Diploma in HTML & CSS Programming',
				'category'    => 'Web',
				'short_desc'  => 'Diploma in front-end fundamentals — HTML5, CSS3, layouts, forms and basic JS intro.',
				'level'       => 'intermediate',
				'duration'    => '6 months',
				'instructor'  => 'Rishikul Shiksha Faculty',
				'paid'        => true,
				'price'       => 5500,
				'rating'      => 4.7,
				'students'    => 520,
				'lessons'     => [
					[ 'HTML5 Deep Dive',        25 ],
					[ 'CSS3 Advanced',          28 ],
					[ 'Forms & Validation',     22 ],
					[ 'Layouts — Flex & Grid',  30 ],
					[ 'Responsive Patterns',    25 ],
					[ 'Intro to JavaScript',    30 ],
				],
			],
		];

		$created = 0;
		foreach ( $samples as $sample ) {
			$course_id = wp_insert_post( [
				'post_type'    => self::CPT_COURSE,
				'post_status'  => 'publish',
				'post_title'   => $sample['title'],
				'post_content' => '<p>' . esc_html( $sample['short_desc'] ) . '</p><p>This course is part of Rishikul Shiksha\'s growing catalog of expert-led training. Lessons combine video, written content, and hands-on tasks.</p>',
				'post_excerpt' => $sample['short_desc'],
			] );

			if ( ! $course_id || is_wp_error( $course_id ) ) {
				continue;
			}

			update_post_meta( $course_id, self::M_PRICE,          $sample['price'] );
			update_post_meta( $course_id, self::M_IS_PAID,        $sample['paid'] ? 1 : 0 );
			update_post_meta( $course_id, self::M_DURATION,       $sample['duration'] );
			update_post_meta( $course_id, self::M_LEVEL,          $sample['level'] );
			update_post_meta( $course_id, self::M_INSTRUCTOR,     $sample['instructor'] );
			update_post_meta( $course_id, self::M_SHORT_DESC,     $sample['short_desc'] );
			update_post_meta( $course_id, self::M_RATING,         $sample['rating'] );
			update_post_meta( $course_id, self::M_STUDENTS_COUNT, $sample['students'] );
			update_post_meta( $course_id, self::M_LESSON_COUNT,   count( $sample['lessons'] ) );

			// Category
			$term = term_exists( $sample['category'], self::TAX_CATEGORY );
			if ( ! $term ) {
				$term = wp_insert_term( $sample['category'], self::TAX_CATEGORY );
			}
			if ( ! is_wp_error( $term ) && isset( $term['term_id'] ) ) {
				wp_set_object_terms( $course_id, [ (int) $term['term_id'] ], self::TAX_CATEGORY );
			}

			// Lessons
			foreach ( $sample['lessons'] as $i => $lesson ) {
				[ $title, $duration ] = $lesson;
				$lesson_id = wp_insert_post( [
					'post_type'    => self::CPT_LESSON,
					'post_status'  => 'publish',
					'post_title'   => $title,
					'post_content' => '<p>Lesson content for "' . esc_html( $title ) . '". Replace this with real lesson notes when authoring.</p>',
				] );
				if ( $lesson_id && ! is_wp_error( $lesson_id ) ) {
					update_post_meta( $lesson_id, self::ML_COURSE_ID,    $course_id );
					update_post_meta( $lesson_id, self::ML_ORDER,        $i + 1 );
					update_post_meta( $lesson_id, self::ML_DURATION_MIN, $duration );
					update_post_meta( $lesson_id, self::ML_FREE_PREVIEW, 0 === $i ? 1 : 0 );
				}
			}

			$created++;
		}

		update_option( 'rks_courses_seeded', 1, true );
		return $created;
	}
}
