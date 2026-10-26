<?php
/**
 * Course Builder — visual admin page for managing courses, curriculum (drag-drop),
 * lessons, and linking exams. Replaces native CPT post-edit for power users.
 *
 * @package RKSiksha\Core
 */

declare( strict_types=1 );

namespace RKSiksha\Core\Admin;

use RKSiksha\Core\Modules\Courses;
use RKSiksha\Core\Modules\Quiz;
use RKSiksha\Core\Support\Icons;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CourseBuilderPage {

	public const SLUG = 'rks-course-builder';

	public function register(): void {
		add_action( 'admin_menu',                       [ $this, 'register_menu' ], 28 );
		add_action( 'admin_post_rks_course_save',       [ $this, 'handle_course_save' ] );
		add_action( 'admin_post_rks_course_delete',     [ $this, 'handle_course_delete' ] );
		add_action( 'admin_post_rks_lesson_save',       [ $this, 'handle_lesson_save' ] );
		add_action( 'admin_post_rks_lesson_delete',     [ $this, 'handle_lesson_delete' ] );
		add_action( 'wp_ajax_rks_lessons_reorder',      [ $this, 'ajax_reorder' ] );
		add_action( 'admin_enqueue_scripts',            [ $this, 'enqueue_assets' ] );
	}

	public function register_menu(): void {
		add_submenu_page(
			'edit.php?post_type=rks_course',
			'Course Builder',
			'Course Builder',
			'rks_manage_courses',
			self::SLUG,
			[ $this, 'render' ]
		);
	}

	public function enqueue_assets( string $hook ): void {
		if ( strpos( $hook, self::SLUG ) === false ) return;
		wp_enqueue_media();
	}

	public function render(): void {
		if ( ! current_user_can( 'rks_manage_courses' ) ) wp_die( 'Forbidden' );

		$course_id = isset( $_GET['course_id'] ) ? (int) $_GET['course_id'] : 0;
		$tab       = isset( $_GET['tab'] )       ? sanitize_key( wp_unslash( (string) $_GET['tab'] ) ) : 'details';

		if ( $course_id <= 0 ) {
			$this->render_list();
			return;
		}

		$course = get_post( $course_id );
		if ( ! $course || $course->post_type !== Courses::CPT_COURSE ) {
			echo '<div class="wrap"><h1>Course not found</h1></div>';
			return;
		}

		$this->render_builder( $course, $tab );
	}

	/* ================================================================== */
	/* LIST                                                                 */
	/* ================================================================== */

	private function render_list(): void {
		$courses = get_posts( [
			'post_type'      => Courses::CPT_COURSE,
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'post_status'    => 'any',
		] );

		$msg       = isset( $_GET['rks_msg'] )    ? sanitize_key( wp_unslash( (string) $_GET['rks_msg'] ) ) : '';

		?>
		<div class="wrap rks-admin-wrap">
			<h1>
				Course Builder
				<a href="<?php echo esc_url( add_query_arg( [ 'page' => self::SLUG, 'course_id' => 0, 'tab' => 'details', 'new' => 1 ], admin_url( 'admin.php' ) ) ); ?>" class="page-title-action">+ Add New Course</a>
			</h1>
			<p>Manage your full course catalog — details, curriculum, exams, and settings — from one place.</p>

			<?php if ( $msg ) : ?>
				<div class="notice notice-<?php echo $msg === 'error' ? 'error' : 'success'; ?> is-dismissible"><p><?php echo esc_html( str_replace( '_', ' ', $msg ) ); ?></p></div>
			<?php endif; ?>

			<?php if ( isset( $_GET['new'] ) ) : ?>
				<div class="rks-admin-card">
					<h2>Quick Create</h2>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'rks_course_save' ); ?>
						<input type="hidden" name="action" value="rks_course_save">
						<input type="hidden" name="course_id" value="0">
						<input type="hidden" name="quick_create" value="1">
						<table class="form-table">
							<tr>
								<th><label>Course Title *</label></th>
								<td><input type="text" name="title" class="regular-text" required></td>
							</tr>
							<tr>
								<th><label>Short Description</label></th>
								<td><textarea name="short_desc" rows="2" class="large-text"></textarea></td>
							</tr>
							<tr>
								<th><label>Mode</label></th>
								<td>
									<select name="mode">
										<option value="online">Online (videos)</option>
										<option value="offline">Offline (coaching, no videos)</option>
										<option value="hybrid">Hybrid</option>
									</select>
								</td>
							</tr>
						</table>
						<button class="button button-primary">Create & Open Builder</button>
						<a href="<?php echo esc_url( add_query_arg( 'page', self::SLUG, admin_url( 'admin.php' ) ) ); ?>" class="button">Cancel</a>
					</form>
				</div>
			<?php endif; ?>

			<div class="rks-cb-grid">
				<?php if ( empty( $courses ) ) : ?>
					<p><em>No courses yet. Click "Add New Course" to create your first.</em></p>
				<?php else : foreach ( $courses as $c ) :
					$lesson_count = (int) get_post_meta( $c->ID, Courses::M_LESSON_COUNT, true );
					$is_paid      = (bool) get_post_meta( $c->ID, Courses::M_IS_PAID, true );
					$price        = (float) get_post_meta( $c->ID, Courses::M_PRICE, true );
					$mode         = (string) get_post_meta( $c->ID, Courses::M_MODE, true ) ?: 'online';
					$thumb        = get_the_post_thumbnail_url( $c->ID, 'medium' );
					$cats         = wp_get_object_terms( $c->ID, Courses::TAX_CATEGORY );
					$cat          = ( ! is_wp_error( $cats ) && ! empty( $cats ) ) ? $cats[0]->name : '';

					$quiz_count   = (int) ( new \WP_Query() )->query_vars; // placeholder; we'll compute via Quiz module
					global $wpdb;
					$quiz_count = (int) $wpdb->get_var( $wpdb->prepare(
						"SELECT COUNT(*) FROM {$wpdb->prefix}rks_quizzes WHERE course_id = %d",
						$c->ID
					) );
				?>
					<div>
					<a class="rks-cb-card" href="<?php echo esc_url( add_query_arg( [ 'page' => self::SLUG, 'course_id' => $c->ID, 'tab' => 'details' ], admin_url( 'admin.php' ) ) ); ?>">
						<div class="rks-cb-card__thumb" <?php echo $thumb ? 'style="background-image:url(' . esc_url( $thumb ) . ')"' : ''; ?>>
							<?php if ( ! $thumb ) : ?>
								<span><?php echo esc_html( strtoupper( substr( $c->post_title, 0, 2 ) ) ); ?></span>
							<?php endif; ?>
							<span class="rks-cb-card__mode rks-cb-card__mode--<?php echo esc_attr( $mode ); ?>"><?php echo esc_html( strtoupper( $mode ) ); ?></span>
							<?php if ( $c->post_status !== 'publish' ) : ?>
								<span class="rks-cb-card__status"><?php echo esc_html( $c->post_status ); ?></span>
							<?php endif; ?>
						</div>
						<div class="rks-cb-card__body">
							<?php if ( $cat ) : ?><small><?php echo esc_html( $cat ); ?></small><?php endif; ?>
							<h3><?php echo esc_html( $c->post_title ); ?></h3>
							<div class="rks-cb-card__stats">
								<span><strong><?php echo (int) $lesson_count; ?></strong> lessons</span>
								<span><strong><?php echo (int) $quiz_count; ?></strong> exams</span>
								<span><?php echo $is_paid ? '₹' . number_format( $price, 0 ) : 'Free'; ?></span>
							</div>
						</div>
					</a>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:8px;text-align:right;">
						<?php wp_nonce_field( 'rks_course_delete' ); ?>
						<input type="hidden" name="action" value="rks_course_delete">
						<input type="hidden" name="course_id" value="<?php echo (int) $c->ID; ?>">
						<button type="submit" class="button button-small button-link-delete" onclick="return confirm('Permanently delete this course and all its lessons? Admissions, payments, enrollments, and exams will not be removed.');"><?php echo Icons::svg( 'trash', 12 ); ?> Delete</button>
					</form>
					</div>
				<?php endforeach; endif; ?>
			</div>
		</div>
		<?php
	}

	/* ================================================================== */
	/* BUILDER (single course)                                              */
	/* ================================================================== */

	private function render_builder( \WP_Post $course, string $tab ): void {
		$nav_url = fn( $t ) => add_query_arg( [ 'page' => self::SLUG, 'course_id' => $course->ID, 'tab' => $t ], admin_url( 'admin.php' ) );

		$msg = isset( $_GET['rks_msg'] ) ? sanitize_key( wp_unslash( (string) $_GET['rks_msg'] ) ) : '';
		?>
		<div class="wrap rks-admin-wrap">
			<h1>
				<a href="<?php echo esc_url( add_query_arg( 'page', self::SLUG, admin_url( 'admin.php' ) ) ); ?>" class="rks-cb-back">← All Courses</a>
				<?php echo esc_html( $course->post_title ); ?>
				<a href="<?php echo esc_url( get_permalink( $course->ID ) ); ?>" target="_blank" class="page-title-action">View on Site</a>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-left:6px;">
					<?php wp_nonce_field( 'rks_course_delete' ); ?>
					<input type="hidden" name="action" value="rks_course_delete">
					<input type="hidden" name="course_id" value="<?php echo (int) $course->ID; ?>">
					<button type="submit" class="button button-link-delete" onclick="return confirm('Permanently delete this course and all its lessons? This cannot be undone.');"><?php echo Icons::svg( 'trash', 12 ); ?> Delete Course</button>
				</form>
			</h1>

			<?php if ( $msg ) : ?>
				<div class="notice notice-<?php echo $msg === 'error' ? 'error' : 'success'; ?> is-dismissible"><p><?php echo esc_html( str_replace( '_', ' ', $msg ) ); ?></p></div>
			<?php endif; ?>

			<nav class="nav-tab-wrapper rks-cb-tabs">
				<a href="<?php echo esc_url( $nav_url( 'details' ) ); ?>"    class="nav-tab <?php echo $tab === 'details'    ? 'nav-tab-active' : ''; ?>"><?php echo \RKSiksha\Core\Support\Icons::svg( 'edit', 14 ); ?> Details</a>
				<a href="<?php echo esc_url( $nav_url( 'curriculum' ) ); ?>" class="nav-tab <?php echo $tab === 'curriculum' ? 'nav-tab-active' : ''; ?>"><?php echo \RKSiksha\Core\Support\Icons::svg( 'book-open', 14 ); ?> Curriculum</a>
				<a href="<?php echo esc_url( $nav_url( 'quizzes' ) ); ?>"    class="nav-tab <?php echo $tab === 'quizzes'    ? 'nav-tab-active' : ''; ?>"><?php echo \RKSiksha\Core\Support\Icons::svg( 'clipboard-check', 14 ); ?> Exams</a>
				<a href="<?php echo esc_url( $nav_url( 'settings' ) ); ?>"   class="nav-tab <?php echo $tab === 'settings'   ? 'nav-tab-active' : ''; ?>"><?php echo \RKSiksha\Core\Support\Icons::svg( 'cog', 14 ); ?> Settings</a>
			</nav>

			<?php
			switch ( $tab ) {
				case 'details':    $this->render_tab_details( $course );    break;
				case 'curriculum': $this->render_tab_curriculum( $course ); break;
				case 'quizzes':    $this->render_tab_quizzes( $course );    break;
				case 'settings':   $this->render_tab_settings( $course );   break;
				default:           $this->render_tab_details( $course );
			}
			?>
		</div>
		<?php
	}

	private function render_tab_details( \WP_Post $course ): void {
		$is_paid       = (bool) get_post_meta( $course->ID, Courses::M_IS_PAID, true );
		$price         = (float) get_post_meta( $course->ID, Courses::M_PRICE, true );
		$short_desc    = (string) get_post_meta( $course->ID, Courses::M_SHORT_DESC, true );
		$instructor    = (string) get_post_meta( $course->ID, Courses::M_INSTRUCTOR, true );
		$duration      = (string) get_post_meta( $course->ID, Courses::M_DURATION, true );
		$level         = (string) get_post_meta( $course->ID, Courses::M_LEVEL, true );
		$intro         = (string) get_post_meta( $course->ID, Courses::M_VIDEO_INTRO, true );
		$thumb_id      = (int) get_post_thumbnail_id( $course->ID );
		$thumb_url     = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'medium' ) : '';
		$pay_mode      = (string) get_post_meta( $course->ID, Courses::M_PAYMENT_MODE, true ) ?: 'one_time';
		$monthly_fee   = (float) get_post_meta( $course->ID, Courses::M_MONTHLY_FEE, true );
		$total_months  = (int) get_post_meta( $course->ID, Courses::M_TOTAL_MONTHS, true );
		$dur_value     = (int) get_post_meta( $course->ID, Courses::M_DURATION_VALUE, true );
		$dur_unit      = (string) get_post_meta( $course->ID, Courses::M_DURATION_UNIT, true ) ?: 'months';
		$seat_limit    = (int) get_post_meta( $course->ID, Courses::M_SEAT_LIMIT, true );
		$enrolled_cnt  = (int) get_post_meta( $course->ID, Courses::M_ENROLLED_COUNT, true );
		?>
		<div class="rks-admin-card">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="rks-cb-form">
				<?php wp_nonce_field( 'rks_course_save' ); ?>
				<input type="hidden" name="action" value="rks_course_save">
				<input type="hidden" name="course_id" value="<?php echo (int) $course->ID; ?>">

				<div class="rks-cb-form__grid">
					<div>
						<table class="form-table">
							<tr>
								<th><label>Title *</label></th>
								<td><input type="text" name="title" class="regular-text" required value="<?php echo esc_attr( $course->post_title ); ?>"></td>
							</tr>
							<tr>
								<th><label>Status</label></th>
								<td>
									<select name="post_status">
										<option value="publish" <?php selected( $course->post_status, 'publish' ); ?>>Published</option>
										<option value="draft"   <?php selected( $course->post_status, 'draft' );   ?>>Draft</option>
									</select>
								</td>
							</tr>
							<tr>
								<th><label>Short Description</label></th>
								<td><textarea name="short_desc" rows="2" class="large-text"><?php echo esc_textarea( $short_desc ); ?></textarea></td>
							</tr>
							<tr>
								<th><label>Description</label></th>
								<td><?php
									wp_editor( $course->post_content, 'rks_course_content', [
										'textarea_name' => 'content',
										'textarea_rows' => 8,
										'media_buttons' => true,
										'teeny'         => true,
									] );
								?></td>
							</tr>
							<tr>
								<th><label>Instructor</label></th>
								<td><input type="text" name="instructor" class="regular-text" value="<?php echo esc_attr( $instructor ); ?>"></td>
							</tr>
							<tr>
								<th><label>Duration</label></th>
								<td>
									<input type="number" min="0" step="1" name="duration_value" class="small-text" value="<?php echo esc_attr( $dur_value > 0 ? (string) $dur_value : '' ); ?>" placeholder="e.g. 12">
									<select name="duration_unit">
										<?php foreach ( [ 'months' => 'Months', 'weeks' => 'Weeks', 'days' => 'Days' ] as $uv => $ul ) : ?>
											<option value="<?php echo esc_attr( $uv ); ?>" <?php selected( $dur_unit, $uv ); ?>><?php echo esc_html( $ul ); ?></option>
										<?php endforeach; ?>
									</select>
									<p class="description">Numeric duration + unit. Used for catalogue display and certificate copy.</p>
									<?php if ( $duration && $dur_value <= 0 ) : ?>
										<p class="description"><strong>Legacy value:</strong> <code><?php echo esc_html( $duration ); ?></code> — please re-enter using number + unit above.</p>
										<input type="hidden" name="duration_legacy" value="<?php echo esc_attr( $duration ); ?>">
									<?php endif; ?>
								</td>
							</tr>
							<tr>
								<th><label>Level</label></th>
								<td>
									<select name="level">
										<?php foreach ( [ 'beginner' => 'Beginner', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced', 'all-levels' => 'All Levels' ] as $v => $l ) : ?>
											<option value="<?php echo esc_attr( $v ); ?>" <?php selected( $level, $v ); ?>><?php echo esc_html( $l ); ?></option>
										<?php endforeach; ?>
									</select>
								</td>
							</tr>
							<tr>
								<th><label>Pricing &amp; Payment</label></th>
								<td>
									<label style="font-weight:normal;"><input type="checkbox" name="is_paid" value="1" <?php checked( $is_paid ); ?>> This is a paid course</label>
									<br><br>
									<label for="rks_cb_pay_mode" style="display:block;font-weight:600;margin-bottom:4px;">Payment Mode</label>
									<select id="rks_cb_pay_mode" name="payment_mode">
										<option value="one_time" <?php selected( $pay_mode, 'one_time' ); ?>>One-time payment</option>
										<option value="monthly"  <?php selected( $pay_mode, 'monthly' );  ?>>Monthly installments</option>
									</select>

									<div class="rks-cb-pay-onetime" style="margin-top:10px;">
										<label style="display:block;font-weight:600;margin-bottom:4px;">Total Price (&#8377;)</label>
										<input type="number" min="0" step="1" name="price" class="small-text" value="<?php echo esc_attr( (string) $price ); ?>">
									</div>

									<div class="rks-cb-pay-monthly" style="margin-top:10px;display:flex;gap:14px;flex-wrap:wrap;">
										<div>
											<label style="display:block;font-weight:600;margin-bottom:4px;">Monthly Fee (&#8377;)</label>
											<input type="number" min="0" step="1" name="monthly_fee" class="small-text" value="<?php echo esc_attr( $monthly_fee > 0 ? (string) $monthly_fee : '' ); ?>">
										</div>
										<div>
											<label style="display:block;font-weight:600;margin-bottom:4px;">Total Months</label>
											<input type="number" min="1" step="1" name="total_months" class="small-text" value="<?php echo esc_attr( $total_months > 0 ? (string) $total_months : '' ); ?>">
										</div>
									</div>
									<p class="description">Monthly mode: total price is auto-derived as <em>monthly fee × months</em>.</p>
								</td>
							</tr>
							<tr>
								<th><label>Enrollment Limit</label></th>
								<td>
									<label style="display:block;font-weight:600;margin-bottom:4px;">Seat Limit</label>
									<input type="number" min="0" step="1" name="seat_limit" class="small-text" value="<?php echo esc_attr( (string) $seat_limit ); ?>">
									<p class="description"><strong>0</strong> = unlimited. For offline batches, set classroom capacity.</p>
									<label style="display:block;font-weight:600;margin:10px 0 4px;">Current Enrollments</label>
									<input type="text" class="small-text" value="<?php echo esc_attr( (string) $enrolled_cnt ); ?>" readonly disabled>
									<?php if ( $seat_limit > 0 ) : ?>
										<p class="description"><?php echo (int) max( 0, $seat_limit - $enrolled_cnt ); ?> seat(s) remaining.</p>
									<?php else : ?>
										<p class="description">Auto-tracked. Unlimited seats.</p>
									<?php endif; ?>
								</td>
							</tr>
							<tr>
								<th><label>Intro Video URL</label></th>
								<td><input type="url" name="video_intro" class="regular-text" value="<?php echo esc_attr( $intro ); ?>"></td>
							</tr>
						</table>
					</div>

					<aside>
						<div class="rks-cb-aside">
							<h3>Thumbnail</h3>
							<input type="hidden" name="thumbnail_id" id="rks_cb_thumb_id" value="<?php echo (int) $thumb_id; ?>">
							<div class="rks-cb-thumb-preview" id="rks_cb_thumb_preview" style="<?php echo $thumb_url ? 'background-image:url(' . esc_url( $thumb_url ) . ')' : ''; ?>">
								<?php if ( ! $thumb_url ) echo '<span>No image</span>'; ?>
							</div>
							<button type="button" class="button" id="rks_cb_thumb_pick">Choose Image</button>
							<button type="button" class="button button-link-delete" id="rks_cb_thumb_clear" <?php echo $thumb_id ? '' : 'style="display:none;"'; ?>>Remove</button>
						</div>

						<div class="rks-cb-aside">
							<h3>Category</h3>
							<?php
							$cats = get_terms( [ 'taxonomy' => Courses::TAX_CATEGORY, 'hide_empty' => false ] );
							$selected_cats = wp_get_object_terms( $course->ID, Courses::TAX_CATEGORY, [ 'fields' => 'ids' ] );
							?>
							<select name="course_category">
								<option value="0">— None —</option>
								<?php if ( ! is_wp_error( $cats ) ) foreach ( $cats as $t ) : ?>
									<option value="<?php echo (int) $t->term_id; ?>" <?php selected( in_array( $t->term_id, (array) $selected_cats, true ) ); ?>><?php echo esc_html( $t->name ); ?></option>
								<?php endforeach; ?>
							</select>
							<input type="text" name="new_category" class="widefat" placeholder="Or type new category" style="margin-top:8px;">
						</div>
					</aside>
				</div>

				<p class="submit"><button class="button button-primary button-large">Save Course</button></p>
			</form>
		</div>

		<script>
		(function () {
			var picker;
			document.getElementById('rks_cb_thumb_pick').addEventListener('click', function (e) {
				e.preventDefault();
				if (picker) { picker.open(); return; }
				picker = wp.media({ title: 'Course Thumbnail', button: { text: 'Use this image' }, multiple: false, library: { type: 'image' } });
				picker.on('select', function () {
					var att = picker.state().get('selection').first().toJSON();
					document.getElementById('rks_cb_thumb_id').value = att.id;
					var pv = document.getElementById('rks_cb_thumb_preview');
					pv.style.backgroundImage = 'url(' + (att.sizes && att.sizes.medium ? att.sizes.medium.url : att.url) + ')';
					pv.innerHTML = '';
					document.getElementById('rks_cb_thumb_clear').style.display = '';
				});
				picker.open();
			});
			document.getElementById('rks_cb_thumb_clear').addEventListener('click', function () {
				document.getElementById('rks_cb_thumb_id').value = 0;
				var pv = document.getElementById('rks_cb_thumb_preview');
				pv.style.backgroundImage = '';
				pv.innerHTML = '<span>No image</span>';
				this.style.display = 'none';
			});

			// Payment-mode toggle: one-time vs monthly
			var paySel  = document.getElementById('rks_cb_pay_mode');
			var payOne  = document.querySelector('.rks-cb-pay-onetime');
			var payMon  = document.querySelector('.rks-cb-pay-monthly');
			function applyPayMode() {
				if (!paySel) return;
				var monthly = paySel.value === 'monthly';
				if (payOne) payOne.style.display = monthly ? 'none' : '';
				if (payMon) payMon.style.display = monthly ? '' : 'none';
			}
			if (paySel) {
				paySel.addEventListener('change', applyPayMode);
				applyPayMode();
			}
		})();
		</script>
		<?php
	}

	private function render_tab_curriculum( \WP_Post $course ): void {
		$lessons   = Courses::get_lessons( $course->ID, false );
		$edit_lid  = isset( $_GET['lesson_id'] ) ? (int) $_GET['lesson_id'] : 0;
		$edit      = $edit_lid > 0 ? get_post( $edit_lid ) : null;
		$is_new    = ! $edit;
		?>
		<div class="rks-cb-curriculum">

			<section class="rks-admin-card rks-cb-lesson-list-wrap">
				<h2 class="rks-card-title">
					<?php echo \RKSiksha\Core\Support\Icons::svg( 'list', 16 ); ?>
					Lessons (<?php echo count( $lessons ); ?>)
				</h2>
				<p class="rks-cb-hint"><small>Drag to reorder. Order saves automatically.</small></p>

				<?php if ( empty( $lessons ) ) : ?>
					<div class="rks-empty">
						<?php echo \RKSiksha\Core\Support\Icons::chip( 'book', 'slate', 22 ); ?>
						<h3>No lessons yet</h3>
						<small>Use the form on the right to add your first lesson. Drag to reorder once you have a few.</small>
					</div>
				<?php else : ?>
					<ol class="rks-cb-lesson-list" id="rks-lessons" data-course="<?php echo (int) $course->ID; ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'rks_lessons_reorder' ) ); ?>">
						<?php foreach ( $lessons as $l ) :
							$dur     = (int) get_post_meta( $l->ID, Courses::ML_DURATION_MIN, true );
							$preview = (bool) get_post_meta( $l->ID, Courses::ML_FREE_PREVIEW, true );
							$vt      = (string) get_post_meta( $l->ID, Courses::ML_VIDEO_TYPE, true );
						?>
							<li class="rks-cb-lesson" draggable="true" data-id="<?php echo (int) $l->ID; ?>">
								<span class="rks-cb-lesson__handle" aria-hidden="true">⋮⋮</span>
								<div class="rks-cb-lesson__body">
									<strong><?php echo esc_html( $l->post_title ); ?></strong>
									<small>
										<?php if ( $vt ) echo esc_html( strtoupper( $vt ) ) . ' · '; ?>
										<?php if ( $dur > 0 ) echo (int) $dur . ' min · '; ?>
										<?php echo $preview ? '<span class="rks-cb-lesson__preview">Free preview</span>' : 'Locked'; ?>
										<?php if ( $l->post_status !== 'publish' ) echo ' · <em>' . esc_html( $l->post_status ) . '</em>'; ?>
									</small>
								</div>
								<div class="rks-cb-lesson__actions">
									<a class="button button-small" href="<?php echo esc_url( add_query_arg( [ 'page' => self::SLUG, 'course_id' => $course->ID, 'tab' => 'curriculum', 'lesson_id' => $l->ID ], admin_url( 'admin.php' ) ) ); ?>">Edit</a>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
										<?php wp_nonce_field( 'rks_lesson_delete' ); ?>
										<input type="hidden" name="action" value="rks_lesson_delete">
										<input type="hidden" name="course_id" value="<?php echo (int) $course->ID; ?>">
										<input type="hidden" name="lesson_id" value="<?php echo (int) $l->ID; ?>">
										<button class="button-link button-link-delete" onclick="return confirm('Delete this lesson?');">Delete</button>
									</form>
								</div>
							</li>
						<?php endforeach; ?>
					</ol>
				<?php endif; ?>
			</section>

			<section class="rks-admin-card rks-cb-lesson-form">
				<h2 class="rks-card-title">
					<?php echo \RKSiksha\Core\Support\Icons::svg( $is_new ? 'plus' : 'edit', 16 ); ?>
					<?php echo $is_new ? 'Add New Lesson' : 'Edit Lesson'; ?>
				</h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="rks-cb-lesson-form__body">
					<?php wp_nonce_field( 'rks_lesson_save' ); ?>
					<input type="hidden" name="action" value="rks_lesson_save">
					<input type="hidden" name="course_id" value="<?php echo (int) $course->ID; ?>">
					<input type="hidden" name="lesson_id" value="<?php echo (int) ( $edit ? $edit->ID : 0 ); ?>">

					<div class="rks-field">
						<label for="rks_lesson_title">Title <span class="rks-field-req">*</span></label>
						<input type="text" id="rks_lesson_title" name="title" class="regular-text" required value="<?php echo esc_attr( $edit ? $edit->post_title : '' ); ?>">
					</div>

					<div class="rks-field">
						<label for="rks_lesson_content">Content / Notes</label>
						<textarea id="rks_lesson_content" name="content" rows="4" class="large-text"><?php echo esc_textarea( $edit ? $edit->post_content : '' ); ?></textarea>
					</div>

					<div class="rks-field rks-field-row rks-field-row--2">
						<div>
							<label for="rks_lesson_video_type">Video Provider</label>
							<?php $cur_vt = $edit ? (string) get_post_meta( $edit->ID, Courses::ML_VIDEO_TYPE, true ) : ''; ?>
							<select id="rks_lesson_video_type" name="video_type">
								<option value="">— None —</option>
								<option value="youtube" <?php selected( $cur_vt, 'youtube' ); ?>>YouTube</option>
								<option value="vimeo"   <?php selected( $cur_vt, 'vimeo' ); ?>>Vimeo</option>
							</select>
						</div>
						<div>
							<label for="rks_lesson_duration">Duration (min)</label>
							<input type="number" id="rks_lesson_duration" min="0" name="duration_min" class="small-text" value="<?php echo esc_attr( $edit ? (string) get_post_meta( $edit->ID, Courses::ML_DURATION_MIN, true ) : '' ); ?>">
						</div>
					</div>

					<div class="rks-field">
						<label for="rks_lesson_video_url">Video URL</label>
						<input type="url" id="rks_lesson_video_url" name="video_url" class="regular-text" value="<?php echo esc_attr( $edit ? (string) get_post_meta( $edit->ID, Courses::ML_VIDEO_URL, true ) : '' ); ?>">
					</div>

					<div class="rks-field rks-check">
						<label>
							<input type="checkbox" name="free_preview" value="1" <?php checked( $edit && (bool) get_post_meta( $edit->ID, Courses::ML_FREE_PREVIEW, true ) ); ?>>
							<span><strong>Free preview</strong><br><small>Allow non-enrolled visitors to preview this lesson</small></span>
						</label>
					</div>

					<div class="rks-field">
						<label for="rks_lesson_status">Status</label>
						<select id="rks_lesson_status" name="post_status">
							<option value="publish" <?php selected( $edit ? $edit->post_status : 'publish', 'publish' ); ?>>Published</option>
							<option value="draft"   <?php selected( $edit ? $edit->post_status : '', 'draft' );   ?>>Draft</option>
						</select>
					</div>

					<div class="rks-form-footer">
						<button type="submit" class="button button-primary"><?php echo $is_new ? 'Add Lesson' : 'Save Changes'; ?></button>
						<?php if ( ! $is_new ) : ?>
							<a href="<?php echo esc_url( add_query_arg( [ 'page' => self::SLUG, 'course_id' => $course->ID, 'tab' => 'curriculum' ], admin_url( 'admin.php' ) ) ); ?>" class="button">Cancel</a>
						<?php endif; ?>
					</div>
				</form>
			</section>
		</div>

		<script>
		(function () {
			var list = document.getElementById('rks-lessons');
			if (!list) return;
			var dragged = null;

			list.querySelectorAll('.rks-cb-lesson').forEach(function (li) {
				li.addEventListener('dragstart', function (e) { dragged = li; li.classList.add('is-dragging'); e.dataTransfer.effectAllowed = 'move'; });
				li.addEventListener('dragend',   function () { li.classList.remove('is-dragging'); dragged = null; });
				li.addEventListener('dragover',  function (e) { e.preventDefault(); var t = e.target.closest('.rks-cb-lesson'); if (!t || t === dragged) return; var rect = t.getBoundingClientRect(); var midY = rect.top + rect.height / 2; t.parentNode.insertBefore(dragged, e.clientY < midY ? t : t.nextSibling); });
				li.addEventListener('drop', function (e) { e.preventDefault(); save(); });
			});

			function save() {
				var ids = Array.from(list.children).map(function (li) { return li.dataset.id; });
				var fd = new FormData();
				fd.append('action', 'rks_lessons_reorder');
				fd.append('course_id', list.dataset.course);
				fd.append('_wpnonce', list.dataset.nonce);
				ids.forEach(function (id) { fd.append('order[]', id); });
				fetch(ajaxurl, { method: 'POST', credentials: 'same-origin', body: fd })
					.then(function (r) { return r.json(); })
					.then(function (j) {
						list.classList.add(j.success ? 'is-saved' : 'is-error');
						setTimeout(function () { list.classList.remove('is-saved'); list.classList.remove('is-error'); }, 1200);
					});
			}
		})();
		</script>
		<?php
	}

	private function render_tab_quizzes( \WP_Post $course ): void {
		if ( ! class_exists( Quiz::class ) ) {
			?>
			<div class="rks-admin-card">
				<h2>Exams for this Course</h2>
				<p>The separate Rishikul Shiksha Exam plugin is not active, so exam management is currently unavailable.</p>
			</div>
			<?php
			return;
		}

		$quiz = new Quiz();
		$quizzes = $quiz->list_quizzes( [ 'course_id' => $course->ID, 'limit' => 200 ] );
		?>
		<div class="rks-admin-card">
			<h2>Exams for this Course (<?php echo count( $quizzes ); ?>)</h2>
			<p>
				<a href="<?php echo esc_url( add_query_arg( [ 'page' => QuizPage::SLUG, 'mode' => 'edit' ], admin_url( 'admin.php' ) ) ); ?>" class="button button-primary">+ Create New Exam</a>
				<small>(Set Course = "<?php echo esc_html( $course->post_title ); ?>" in the form)</small>
			</p>

			<?php if ( empty( $quizzes ) ) : ?>
				<div class="rks-dash__empty"><em>No exams attached to this course yet.</em></div>
			<?php else : ?>
				<table class="widefat striped">
					<thead><tr><th>Title</th><th>Questions</th><th>Pass %</th><th>Final?</th><th>Status</th><th>Actions</th></tr></thead>
					<tbody>
						<?php foreach ( $quizzes as $q ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $q['title'] ); ?></strong></td>
								<td><?php echo (int) $quiz->count_questions( (int) $q['id'] ); ?></td>
								<td><?php echo (int) $q['passing_score']; ?>%</td>
								<td><?php echo $q['is_final'] ? '✓ Final' : '—'; ?></td>
								<td><span class="rks-tag rks-tag--<?php echo esc_attr( $q['status'] ); ?>"><?php echo esc_html( $q['status'] ); ?></span></td>
								<td>
									<a href="<?php echo esc_url( add_query_arg( [ 'page' => QuizPage::SLUG, 'mode' => 'questions', 'quiz_id' => $q['id'] ], admin_url( 'admin.php' ) ) ); ?>">Manage Questions</a> |
									<a href="<?php echo esc_url( add_query_arg( [ 'page' => QuizPage::SLUG, 'mode' => 'edit', 'quiz_id' => $q['id'] ], admin_url( 'admin.php' ) ) ); ?>">Edit Exam</a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	private function render_tab_settings( \WP_Post $course ): void {
		$mode       = (string) get_post_meta( $course->ID, Courses::M_MODE, true ) ?: 'online';
		$completion = (string) get_post_meta( $course->ID, Courses::M_COMPLETION, true ) ?: 'lessons';
		?>
		<div class="rks-admin-card">
			<h2>Course Settings</h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'rks_course_save' ); ?>
				<input type="hidden" name="action" value="rks_course_save">
				<input type="hidden" name="course_id" value="<?php echo (int) $course->ID; ?>">
				<input type="hidden" name="settings_only" value="1">

				<table class="form-table">
					<tr>
						<th><label>Mode</label></th>
						<td>
							<select name="mode">
								<option value="online"  <?php selected( $mode, 'online' );  ?>>Online — video lessons</option>
								<option value="offline" <?php selected( $mode, 'offline' ); ?>>Offline — coaching institute (no videos, final exam only)</option>
								<option value="hybrid"  <?php selected( $mode, 'hybrid' );  ?>>Hybrid — videos + offline classes</option>
							</select>
						</td>
					</tr>
					<tr>
						<th><label>Completion Rule</label></th>
						<td>
							<select name="completion">
								<option value="lessons" <?php selected( $completion, 'lessons' ); ?>>All lessons watched</option>
								<option value="quiz"    <?php selected( $completion, 'quiz' );    ?>>Pass final exam only</option>
								<option value="both"    <?php selected( $completion, 'both' );    ?>>Lessons watched + Pass final exam</option>
							</select>
							<p class="description">Determines when the certificate is issued automatically.</p>
						</td>
					</tr>
				</table>

				<p class="submit"><button class="button button-primary">Save Settings</button></p>
			</form>
		</div>
		<?php
	}

	/* ================================================================== */
	/* HANDLERS                                                             */
	/* ================================================================== */

	public function handle_course_save(): void {
		check_admin_referer( 'rks_course_save' );
		if ( ! current_user_can( 'rks_manage_courses' ) ) wp_die( 'Forbidden' );

		$id   = (int) ( $_POST['course_id'] ?? 0 );
		$post = wp_unslash( $_POST );

		// Quick-create or full save
		$post_data = [
			'post_type'    => Courses::CPT_COURSE,
			'post_status'  => sanitize_key( (string) ( $post['post_status'] ?? 'publish' ) ),
		];
		if ( isset( $post['title'] ) )      $post_data['post_title']   = sanitize_text_field( (string) $post['title'] );
		if ( isset( $post['content'] ) )    $post_data['post_content'] = wp_kses_post( (string) $post['content'] );
		if ( isset( $post['short_desc'] ) ) $post_data['post_excerpt'] = sanitize_textarea_field( (string) $post['short_desc'] );

		if ( $id > 0 ) {
			$post_data['ID'] = $id;
			wp_update_post( $post_data );
		} else {
			$id = (int) wp_insert_post( $post_data );
		}

		if ( $id <= 0 ) {
			wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG, 'rks_msg' => 'error' ], admin_url( 'admin.php' ) ) );
			exit;
		}

		// Settings-only save (Settings tab)
		if ( ! empty( $post['settings_only'] ) ) {
			update_post_meta( $id, Courses::M_MODE,       sanitize_key( (string) ( $post['mode']       ?? 'online' ) ) );
			update_post_meta( $id, Courses::M_COMPLETION, sanitize_key( (string) ( $post['completion'] ?? 'lessons' ) ) );
			wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG, 'course_id' => $id, 'tab' => 'settings', 'rks_msg' => 'saved' ], admin_url( 'admin.php' ) ) );
			exit;
		}

		// Quick create — set mode from form, redirect to curriculum
		if ( ! empty( $post['quick_create'] ) ) {
			update_post_meta( $id, Courses::M_MODE, sanitize_key( (string) ( $post['mode'] ?? 'online' ) ) );
			update_post_meta( $id, Courses::M_SHORT_DESC, sanitize_textarea_field( (string) ( $post['short_desc'] ?? '' ) ) );
			wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG, 'course_id' => $id, 'tab' => 'curriculum' ], admin_url( 'admin.php' ) ) );
			exit;
		}

		// Full details save
		if ( isset( $post['short_desc'] ) ) update_post_meta( $id, Courses::M_SHORT_DESC, sanitize_textarea_field( (string) $post['short_desc'] ) );
		if ( isset( $post['instructor'] ) ) update_post_meta( $id, Courses::M_INSTRUCTOR, sanitize_text_field( (string) $post['instructor'] ) );
		if ( isset( $post['duration'] ) )   update_post_meta( $id, Courses::M_DURATION,   sanitize_text_field( (string) $post['duration'] ) );
		if ( isset( $post['level'] ) )      update_post_meta( $id, Courses::M_LEVEL,      sanitize_text_field( (string) $post['level'] ) );
		if ( isset( $post['video_intro'] ) )update_post_meta( $id, Courses::M_VIDEO_INTRO, esc_url_raw( (string) $post['video_intro'] ) );
		update_post_meta( $id, Courses::M_IS_PAID, ! empty( $post['is_paid'] ) ? 1 : 0 );
		if ( isset( $post['price'] ) )      update_post_meta( $id, Courses::M_PRICE,      (float) $post['price'] );

		// Payment mode (one-time vs monthly installments)
		$pay_mode = sanitize_key( (string) ( $post['payment_mode'] ?? 'one_time' ) );
		if ( ! in_array( $pay_mode, [ 'one_time', 'monthly' ], true ) ) $pay_mode = 'one_time';
		update_post_meta( $id, Courses::M_PAYMENT_MODE, $pay_mode );
		update_post_meta( $id, Courses::M_MONTHLY_FEE,  (float) ( $post['monthly_fee']  ?? 0 ) );
		update_post_meta( $id, Courses::M_TOTAL_MONTHS, absint( $post['total_months'] ?? 0 ) );

		// If monthly, derive total price (monthly × months) for backward-compat
		if ( $pay_mode === 'monthly' ) {
			$derived = (float) ( $post['monthly_fee'] ?? 0 ) * absint( $post['total_months'] ?? 0 );
			if ( $derived > 0 ) update_post_meta( $id, Courses::M_PRICE, $derived );
		}

		// Duration (number + unit). Also mirror to legacy string field for back-compat.
		$dur_value = absint( $post['duration_value'] ?? 0 );
		$dur_unit  = sanitize_key( (string) ( $post['duration_unit'] ?? 'months' ) );
		if ( ! in_array( $dur_unit, [ 'months', 'weeks', 'days' ], true ) ) $dur_unit = 'months';
		update_post_meta( $id, Courses::M_DURATION_VALUE, $dur_value );
		update_post_meta( $id, Courses::M_DURATION_UNIT,  $dur_unit );
		if ( $dur_value > 0 ) update_post_meta( $id, Courses::M_DURATION, $dur_value . ' ' . $dur_unit );

		// Seat limit (0 = unlimited)
		update_post_meta( $id, Courses::M_SEAT_LIMIT, absint( $post['seat_limit'] ?? 0 ) );

		// Thumbnail
		$thumb_id = (int) ( $post['thumbnail_id'] ?? 0 );
		if ( $thumb_id > 0 ) set_post_thumbnail( $id, $thumb_id );
		else                  delete_post_thumbnail( $id );

		// Category — existing or new
		$cat_id = (int) ( $post['course_category'] ?? 0 );
		$new    = sanitize_text_field( (string) ( $post['new_category'] ?? '' ) );
		if ( $new ) {
			$term = wp_insert_term( $new, Courses::TAX_CATEGORY );
			if ( ! is_wp_error( $term ) ) $cat_id = (int) $term['term_id'];
		}
		if ( $cat_id > 0 ) wp_set_object_terms( $id, [ $cat_id ], Courses::TAX_CATEGORY );
		else               wp_set_object_terms( $id, [], Courses::TAX_CATEGORY );

		wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG, 'course_id' => $id, 'tab' => 'details', 'rks_msg' => 'saved' ], admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_course_delete(): void {
		check_admin_referer( 'rks_course_delete' );
		if ( ! current_user_can( 'rks_manage_courses' ) ) wp_die( 'Forbidden' );

		$course_id = (int) ( $_POST['course_id'] ?? 0 );
		$course    = $course_id > 0 ? get_post( $course_id ) : null;
		if ( ! $course || $course->post_type !== Courses::CPT_COURSE ) {
			wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG, 'rks_msg' => 'error' ], admin_url( 'admin.php' ) ) );
			exit;
		}

		foreach ( Courses::get_lessons( $course_id, false ) as $lesson ) {
			wp_delete_post( (int) $lesson->ID, true );
		}

		$deleted = wp_delete_post( $course_id, true );
		wp_safe_redirect( add_query_arg(
			[ 'page' => self::SLUG, 'rks_msg' => $deleted ? 'deleted' : 'error' ],
			admin_url( 'admin.php' )
		) );
		exit;
	}

	public function handle_lesson_save(): void {
		check_admin_referer( 'rks_lesson_save' );
		if ( ! current_user_can( 'rks_manage_courses' ) ) wp_die( 'Forbidden' );

		$post      = wp_unslash( $_POST );
		$course_id = (int) ( $post['course_id'] ?? 0 );
		$lesson_id = (int) ( $post['lesson_id'] ?? 0 );

		$data = [
			'post_type'    => Courses::CPT_LESSON,
			'post_status'  => sanitize_key( (string) ( $post['post_status'] ?? 'publish' ) ),
			'post_title'   => sanitize_text_field( (string) ( $post['title']   ?? '' ) ),
			'post_content' => wp_kses_post(       (string) ( $post['content'] ?? '' ) ),
		];

		if ( $lesson_id > 0 ) {
			$data['ID'] = $lesson_id;
			wp_update_post( $data );
		} else {
			$lesson_id = (int) wp_insert_post( $data );
		}

		if ( $lesson_id <= 0 ) {
			wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG, 'course_id' => $course_id, 'tab' => 'curriculum', 'rks_msg' => 'error' ], admin_url( 'admin.php' ) ) );
			exit;
		}

		update_post_meta( $lesson_id, Courses::ML_COURSE_ID,    $course_id );
		update_post_meta( $lesson_id, Courses::ML_VIDEO_TYPE,   sanitize_text_field( (string) ( $post['video_type']  ?? '' ) ) );
		update_post_meta( $lesson_id, Courses::ML_VIDEO_URL,    esc_url_raw(         (string) ( $post['video_url']   ?? '' ) ) );
		update_post_meta( $lesson_id, Courses::ML_DURATION_MIN, (int) ( $post['duration_min'] ?? 0 ) );
		update_post_meta( $lesson_id, Courses::ML_FREE_PREVIEW, ! empty( $post['free_preview'] ) ? 1 : 0 );

		// Auto-assign display order if missing
		$existing_order = (int) get_post_meta( $lesson_id, Courses::ML_ORDER, true );
		if ( $existing_order <= 0 ) {
			$max = (int) ( new \WP_Query() )->query_vars; // placeholder
			global $wpdb;
			$max = (int) $wpdb->get_var( $wpdb->prepare(
				"SELECT COALESCE(MAX(CAST(meta_value AS UNSIGNED)), 0) FROM {$wpdb->postmeta} pm
				 INNER JOIN {$wpdb->postmeta} pc ON pc.post_id = pm.post_id AND pc.meta_key = %s AND pc.meta_value = %d
				 WHERE pm.meta_key = %s",
				Courses::ML_COURSE_ID, $course_id, Courses::ML_ORDER
			) );
			update_post_meta( $lesson_id, Courses::ML_ORDER, $max + 1 );
		}

		wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG, 'course_id' => $course_id, 'tab' => 'curriculum', 'rks_msg' => 'saved' ], admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_lesson_delete(): void {
		check_admin_referer( 'rks_lesson_delete' );
		if ( ! current_user_can( 'rks_manage_courses' ) ) wp_die( 'Forbidden' );

		$lesson_id = (int) ( $_POST['lesson_id'] ?? 0 );
		$course_id = (int) ( $_POST['course_id'] ?? 0 );

		wp_delete_post( $lesson_id, true );

		wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG, 'course_id' => $course_id, 'tab' => 'curriculum', 'rks_msg' => 'deleted' ], admin_url( 'admin.php' ) ) );
		exit;
	}

	public function ajax_reorder(): void {
		if ( ! current_user_can( 'rks_manage_courses' ) ) wp_send_json_error( 'Forbidden', 403 );
		if ( ! check_ajax_referer( 'rks_lessons_reorder', '_wpnonce', false ) ) wp_send_json_error( 'Bad nonce', 400 );

		$order = (array) ( $_POST['order'] ?? [] );
		foreach ( $order as $i => $lid ) {
			$lid = (int) $lid;
			if ( $lid > 0 ) update_post_meta( $lid, Courses::ML_ORDER, (int) ( $i + 1 ) );
		}
		wp_send_json_success( [ 'count' => count( $order ) ] );
	}
}
