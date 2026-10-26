<?php
/**
 * Admin: Quiz manager — list quizzes, create/edit, manage questions.
 *
 * @package RKSiksha\Core
 */

declare( strict_types=1 );

namespace RKSiksha\Core\Admin;

use RKSiksha\Core\Modules\Courses;
use RKSiksha\Core\Modules\Quiz;
use RKSiksha\Core\Roles as CoreRoles;
use RKSiksha\Core\Support\DB;
use RKSiksha\Core\Support\Icons;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class QuizPage {

	public const SLUG = 'rks-exams';

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'register_menu' ], 25 );
		add_action( 'admin_post_rks_quiz_save',     [ $this, 'handle_quiz_save' ] );
		add_action( 'admin_post_rks_quiz_delete',   [ $this, 'handle_quiz_delete' ] );
		add_action( 'admin_post_rks_quiz_release',  [ $this, 'handle_quiz_release' ] );
		add_action( 'admin_post_rks_quiz_allow_attempt', [ $this, 'handle_allow_attempt' ] );
		add_action( 'admin_post_rks_question_save', [ $this, 'handle_question_save' ] );
		add_action( 'admin_post_rks_question_delete', [ $this, 'handle_question_delete' ] );
		add_action( 'admin_post_rks_quiz_repair_titles', [ $this, 'handle_repair_titles' ] );
	}

	public function register_menu(): void {
		$capability = current_user_can( 'rks_manage_exams' ) ? 'rks_manage_exams' : 'manage_options';

		add_menu_page(
			'Exams',
			'Exams',
			$capability,
			self::SLUG,
			[ $this, 'render' ],
			'dashicons-clipboard',
			5
		);

		add_submenu_page(
			self::SLUG,
			'All Exams',
			'All Exams',
			$capability,
			self::SLUG,
			[ $this, 'render' ]
		);
	}

	public function render(): void {
		if ( ! $this->can_manage_exams() ) wp_die( 'Forbidden' );

		$mode    = isset( $_GET['mode'] )    ? sanitize_key( wp_unslash( (string) $_GET['mode'] ) ) : 'list';
		$quiz_id = isset( $_GET['quiz_id'] ) ? (int) $_GET['quiz_id'] : 0;

		switch ( $mode ) {
			case 'edit':      $this->render_edit_quiz( $quiz_id );    break;
			case 'questions': $this->render_questions( $quiz_id );    break;
			case 'attempt':   $this->render_attempt( isset( $_GET['attempt_id'] ) ? (int) $_GET['attempt_id'] : 0 ); break;
			case 'attempts':  $this->render_attempts_list( $quiz_id ); break;
			default:          $this->render_list();
		}
	}

	private function can_manage_exams(): bool {
		return current_user_can( 'rks_manage_exams' ) || current_user_can( 'manage_options' );
	}

	private function render_list(): void {
		$quiz = new Quiz();
		$rows = $quiz->list_quizzes( [ 'limit' => 200 ] );

		// Detect any mojibake in titles/descriptions so we can conditionally offer the repair tool.
		global $wpdb;
		$qz_table = DB::table( 'quizzes' );
		$has_mojibake = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$qz_table}
			  WHERE title LIKE '%\xC3\x94\xC3\x87\xC3\xB6%'
			     OR title LIKE '%\xC3\x83\xC2\xA2%'
			     OR description LIKE '%\xC3\x94\xC3\x87\xC3\xB6%'
			     OR description LIKE '%\xC3\x83\xC2\xA2%'"
		);
		?>
		<div class="wrap rks-admin-wrap">
			<header class="rks-page-hero">
				<div class="rks-page-hero__main">
					<div class="rks-page-hero__icon"><?php echo Icons::svg( 'file-text', 24 ); ?></div>
					<div>
						<small>Exam Engine</small>
						<h1>Exams &amp; Assessments</h1>
						<p>Create exams per course, attach questions, set pass criteria + time limits. Exams auto-grade on submit.</p>
					</div>
				</div>
				<div class="rks-page-hero__actions">
					<a href="<?php echo esc_url( add_query_arg( [ 'page' => self::SLUG, 'mode' => 'edit' ], admin_url( 'admin.php' ) ) ); ?>" class="button button-primary"><?php echo Icons::svg( 'plus', 14 ); ?> Add New Exam</a>
					<?php if ( $has_mojibake > 0 ) : ?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
							<?php wp_nonce_field( 'rks_quiz_repair_titles' ); ?>
							<input type="hidden" name="action" value="rks_quiz_repair_titles">
							<button type="submit" class="button" title="Fix garbled characters in quiz titles caused by encoding mismatch">
								<?php echo Icons::svg( 'tool', 12 ); ?> Repair Titles
							</button>
						</form>
					<?php endif; ?>
				</div>
			</header>

			<?php if ( ! empty( $_GET['rks_msg'] ) ) :
				$rks_msg_key = sanitize_key( wp_unslash( (string) $_GET['rks_msg'] ) );
				$rks_msg_map = [
					'saved'             => 'Saved successfully.',
					'deleted'           => 'Exam deleted.',
					'released'          => 'Exam released to students.',
					'hidden'            => 'Exam hidden from students.',
					'question_saved'    => 'Question saved.',
					'question_deleted'  => 'Question deleted.',
					'titles_repaired'   => 'Quiz titles repaired.',
					'attempt_allowed'   => 'Next attempt allowed for the selected student.',
				];
				$rks_msg_text = $rks_msg_map[ $rks_msg_key ] ?? ucfirst( str_replace( '_', ' ', $rks_msg_key ) );
			?>
				<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $rks_msg_text ); ?></p></div>
			<?php endif; ?>

			<table class="widefat striped rks-quiz-table">
				<thead>
					<tr>
						<th class="rks-col-id">ID</th>
						<th>Title</th>
						<th>Course</th>
						<th class="rks-col-num">Questions</th>
						<th class="rks-col-num">Pass %</th>
						<th class="rks-col-num">Attempts</th>
						<th class="rks-col-num">Assigned</th>
						<th class="rks-col-num">Final?</th>
						<th>Visibility</th>
						<th>Status</th>
						<th class="rks-col-actions">Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $rows ) ) : ?>
						<tr><td colspan="11"><em>No exams yet.</em> Click "Add New Exam" to create one.</td></tr>
					<?php else : foreach ( $rows as $r ) :
						$course      = get_post( (int) $r['course_id'] );
						$is_released = ! empty( $r['is_released'] );
						$assigned    = count( $quiz->assigned_user_ids( (int) $r['id'] ) );
					?>
						<tr>
							<td class="rks-col-id">#<?php echo (int) $r['id']; ?></td>
							<td><strong><?php echo esc_html( $r['title'] ); ?></strong></td>
							<td><?php echo $course ? esc_html( $course->post_title ) : '<em>—</em>'; ?></td>
							<td class="rks-col-num"><?php echo (int) $quiz->count_questions( (int) $r['id'] ); ?></td>
							<td class="rks-col-num"><?php echo (int) $r['passing_score']; ?>%</td>
							<td class="rks-col-num">Admin</td>
							<td class="rks-col-num"><?php echo (int) $assigned; ?></td>
							<td class="rks-col-num"><?php echo $r['is_final'] ? '<span class="rks-tag rks-tag--final">Final</span>' : '—'; ?></td>
							<td>
								<?php if ( $is_released ) : ?>
									<span class="rks-tag rks-tag--released">Released</span>
								<?php else : ?>
									<span class="rks-tag rks-tag--hidden">Hidden</span>
								<?php endif; ?>
							</td>
							<td><span class="rks-tag rks-tag--<?php echo esc_attr( $r['status'] ); ?>"><?php echo esc_html( $r['status'] ); ?></span></td>
							<td class="rks-quiz-actions rks-col-actions">
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
									<?php wp_nonce_field( 'rks_quiz_release' ); ?>
									<input type="hidden" name="action" value="rks_quiz_release">
									<input type="hidden" name="quiz_id" value="<?php echo (int) $r['id']; ?>">
									<input type="hidden" name="release" value="<?php echo $is_released ? '0' : '1'; ?>">
									<button type="submit" class="button button-small <?php echo $is_released ? '' : 'button-primary'; ?>">
										<?php echo $is_released ? 'Hide' : 'Release'; ?>
									</button>
								</form>
								<a class="button button-small" href="<?php echo esc_url( add_query_arg( [ 'page' => self::SLUG, 'mode' => 'questions', 'quiz_id' => $r['id'] ], admin_url( 'admin.php' ) ) ); ?>">Questions</a>
								<a class="button button-small" href="<?php echo esc_url( add_query_arg( [ 'page' => self::SLUG, 'mode' => 'attempts', 'quiz_id' => $r['id'] ], admin_url( 'admin.php' ) ) ); ?>">Attempts</a>
								<a class="button button-small" href="<?php echo esc_url( add_query_arg( [ 'page' => self::SLUG, 'mode' => 'edit', 'quiz_id' => $r['id'] ], admin_url( 'admin.php' ) ) ); ?>">Edit</a>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
									<?php wp_nonce_field( 'rks_quiz_delete' ); ?>
									<input type="hidden" name="action" value="rks_quiz_delete">
									<input type="hidden" name="quiz_id" value="<?php echo (int) $r['id']; ?>">
									<button type="submit" class="button button-small button-link-delete" onclick="return confirm('Delete this exam and all its questions/attempts?');">Delete</button>
								</form>
							</td>
						</tr>
					<?php endforeach; endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * @return array<int, array{id:int, name:string}>
	 */
	private function branch_options(): array {
		global $wpdb;
		$rows = $wpdb->get_results(
			'SELECT id, name FROM ' . DB::table( 'branches' ) . ' ORDER BY name ASC',
			ARRAY_A
		) ?: [];

		return array_map( static function ( array $row ): array {
			return [
				'id'   => (int) $row['id'],
				'name' => (string) $row['name'],
			];
		}, $rows );
	}

	/**
	 * @param array<int, \WP_User> $students
	 * @return array<int, array{branch_id:int, course_ids:array<int, int>}>
	 */
	private function student_filter_meta( array $students ): array {
		$meta = [];
		$ids  = [];
		foreach ( $students as $student ) {
			$user_id = (int) $student->ID;
			$ids[]   = $user_id;
			$meta[ $user_id ] = [
				'branch_id'  => (int) get_user_meta( $user_id, 'rks_branch_id', true ),
				'course_ids' => [],
			];
		}

		if ( empty( $ids ) ) {
			return $meta;
		}

		global $wpdb;
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT user_id, course_id FROM " . DB::table( 'enrollments' ) . " WHERE user_id IN ({$placeholders})",
			$ids
		), ARRAY_A ) ?: [];

		foreach ( $rows as $row ) {
			$user_id   = (int) $row['user_id'];
			$course_id = (int) $row['course_id'];
			if ( ! isset( $meta[ $user_id ] ) || $course_id <= 0 ) {
				continue;
			}
			$meta[ $user_id ]['course_ids'][] = $course_id;
		}

		foreach ( $meta as &$row ) {
			$row['course_ids'] = array_values( array_unique( array_map( 'intval', $row['course_ids'] ) ) );
		}
		unset( $row );

		return $meta;
	}

	private function render_edit_quiz( int $quiz_id ): void {
		$quiz = new Quiz();
		$data = $quiz_id > 0 ? $quiz->get_quiz( $quiz_id ) : null;
		$is_new = ! $data;

		$courses = get_posts( [ 'post_type' => Courses::CPT_COURSE, 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ] );
		$students = get_users( [
			'role'    => CoreRoles::STUDENT,
			'orderby' => 'display_name',
			'order'   => 'ASC',
			'fields'  => [ 'ID', 'display_name', 'user_login', 'user_email' ],
			'number'  => 500,
		] );
		$assigned_ids = $quiz_id > 0 ? $quiz->assigned_user_ids( $quiz_id ) : [];
		$branches     = $this->branch_options();
		$student_meta = $this->student_filter_meta( $students );

		?>
		<div class="wrap rks-admin-wrap">
			<header class="rks-page-hero">
				<div class="rks-page-hero__main">
					<div class="rks-page-hero__icon"><?php echo Icons::svg( $is_new ? 'plus' : 'edit', 24 ); ?></div>
					<div>
						<small>Exam Builder</small>
						<h1><?php echo $is_new ? 'Add New Exam' : 'Edit Exam #' . $quiz_id; ?></h1>
						<p>Set passing criteria, time limits, and attempts. Questions are managed separately.</p>
					</div>
				</div>
				<div class="rks-page-hero__actions">
					<a href="<?php echo esc_url( add_query_arg( 'page', self::SLUG, admin_url( 'admin.php' ) ) ); ?>" class="button"><?php echo Icons::svg( 'arrow-left', 12 ); ?> All Exams</a>
				</div>
			</header>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="rks-quiz-form">
				<?php wp_nonce_field( 'rks_quiz_save' ); ?>
				<input type="hidden" name="action" value="rks_quiz_save">
				<input type="hidden" name="quiz_id" value="<?php echo (int) $quiz_id; ?>">

				<div class="rks-quiz-grid">
					<section class="rks-admin-card">
						<h2 class="rks-card-title"><?php echo Icons::svg( 'info', 16 ); ?> Basics</h2>
						<div class="rks-field">
							<label>Exam Title *</label>
							<input type="text" name="title" class="regular-text" required value="<?php echo esc_attr( (string) ( $data['title'] ?? '' ) ); ?>">
						</div>
						<div class="rks-field">
							<label>Course *</label>
							<select name="course_id" required>
								<option value="">— Select Course —</option>
								<?php foreach ( $courses as $c ) : ?>
									<option value="<?php echo (int) $c->ID; ?>" <?php selected( (int) ( $data['course_id'] ?? 0 ), $c->ID ); ?>><?php echo esc_html( $c->post_title ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="rks-field">
							<label>Description</label>
							<textarea name="description" rows="3" class="large-text"><?php echo esc_textarea( (string) ( $data['description'] ?? '' ) ); ?></textarea>
							<p class="rks-field-hint">Optional intro shown to students before they start.</p>
						</div>
					</section>

					<section class="rks-admin-card">
						<h2 class="rks-card-title"><?php echo Icons::svg( 'target', 16 ); ?> Scoring & Attempts</h2>
						<div class="rks-field-row">
							<div class="rks-field">
								<label>Passing Score (%)</label>
								<input type="number" min="0" max="100" name="passing_score" class="small-text" value="<?php echo esc_attr( (string) ( $data['passing_score'] ?? 60 ) ); ?>">
							</div>
							<div class="rks-field">
								<label>Attempt Mode</label>
								<input type="number" min="0" name="max_attempts" class="small-text" value="<?php echo esc_attr( (string) ( $data['max_attempts'] ?? 3 ) ); ?>">
								<p class="rks-field-hint">Legacy value only. Student attempts are now controlled by assignment and admin retry approval.</p>
							</div>
							<div class="rks-field">
								<label>Time Limit (min)</label>
								<input type="number" min="0" name="time_limit_min" class="small-text" value="<?php echo esc_attr( (string) ( $data['time_limit_min'] ?? 0 ) ); ?>">
								<p class="rks-field-hint">0 = no limit</p>
							</div>
						</div>
					</section>

					<section class="rks-admin-card">
						<h2 class="rks-card-title"><?php echo Icons::svg( 'settings', 16 ); ?> Behaviour</h2>
						<label class="rks-check"><input type="checkbox" name="randomize" value="1" <?php checked( ! empty( $data['randomize'] ) ); ?>> Randomize question order</label>
						<label class="rks-check"><input type="checkbox" name="show_answers" value="1" <?php checked( ! empty( $data['show_answers'] ) || $is_new ); ?>> Show correct answers after submission</label>
						<label class="rks-check"><input type="checkbox" name="is_final" value="1" <?php checked( ! empty( $data['is_final'] ) || $is_new ); ?>> This is the <strong>final exam</strong> (passing required for certificate)</label>
					</section>

					<section class="rks-admin-card rks-admin-card--accent">
						<h2 class="rks-card-title"><?php echo Icons::svg( 'eye', 16 ); ?> Visibility</h2>
						<div class="rks-field">
							<label>Status</label>
							<select name="status">
								<option value="published" <?php selected( ( $data['status'] ?? 'published' ), 'published' ); ?>>Published</option>
								<option value="draft" <?php selected( ( $data['status'] ?? '' ), 'draft' ); ?>>Draft</option>
							</select>
							<p class="rks-field-hint">Published = exam exists in the system. Draft = not yet finalized.</p>
						</div>
						<label class="rks-check rks-check--highlight">
							<input type="checkbox" name="is_released" value="1" <?php checked( ! empty( $data['is_released'] ) ); ?>>
							<strong>Release to students</strong>
						</label>
						<p class="rks-field-hint">When OFF, the exam is hidden from students even if Published. Use this to prepare an exam in advance and reveal it when the batch is ready.</p>
					</section>

					<section class="rks-admin-card rks-admin-card--wide">
						<h2 class="rks-card-title"><?php echo Icons::svg( 'users', 16 ); ?> Assigned Students</h2>
						<p class="rks-field-hint">Only selected students can see this exam in their dashboard. Each selected student gets 1 attempt. If they fail, allow another attempt from the Attempts screen.</p>
						<?php if ( empty( $students ) ) : ?>
							<div class="rks-empty">
								<?php echo Icons::chip( 'user-circle', 'slate', 22 ); ?>
								<h3>No student accounts found</h3>
								<small>Create or approve students first, then return here to assign the exam.</small>
							</div>
						<?php else : ?>
							<div class="rks-student-filter" data-rks-student-filter>
								<div class="rks-student-filter__field rks-student-filter__field--search">
									<label for="rks-student-search">Search</label>
									<input type="search" id="rks-student-search" class="regular-text" placeholder="Name, email, username">
								</div>
								<div class="rks-student-filter__field">
									<label for="rks-student-branch-filter">Branch</label>
									<select id="rks-student-branch-filter">
										<option value="">All branches</option>
										<?php foreach ( $branches as $branch ) : ?>
											<option value="<?php echo (int) $branch['id']; ?>"><?php echo esc_html( (string) $branch['name'] ); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
								<div class="rks-student-filter__field">
									<label for="rks-student-course-filter">Course</label>
									<select id="rks-student-course-filter">
										<option value="">All courses</option>
										<?php foreach ( $courses as $c ) : ?>
											<option value="<?php echo (int) $c->ID; ?>"><?php echo esc_html( $c->post_title ); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
								<label class="rks-student-filter__selected">
									<input type="checkbox" id="rks-student-selected-filter">
									<span>Selected only</span>
								</label>
								<div class="rks-student-filter__count" aria-live="polite"><span data-rks-visible-count><?php echo count( $students ); ?></span> visible</div>
								<button type="button" class="button" data-rks-clear-student-filter>Clear</button>
							</div>

							<div class="rks-student-picker" data-rks-student-picker>
								<?php foreach ( $students as $student ) :
									$display = $student->display_name ?: $student->user_login;
									$student_id = (int) $student->ID;
									$filter_meta = $student_meta[ $student_id ] ?? [ 'branch_id' => 0, 'course_ids' => [] ];
									$branch_id   = (int) ( $filter_meta['branch_id'] ?? 0 );
									$course_ids  = array_map( 'intval', (array) ( $filter_meta['course_ids'] ?? [] ) );
									$search_text = strtolower( trim( $display . ' ' . $student->user_login . ' ' . $student->user_email ) );
								?>
									<label class="rks-student-picker__item" data-rks-student-item data-search="<?php echo esc_attr( $search_text ); ?>" data-branch-id="<?php echo (int) $branch_id; ?>" data-course-ids="<?php echo esc_attr( implode( ',', $course_ids ) ); ?>">
										<input type="checkbox" name="assigned_students[]" value="<?php echo (int) $student_id; ?>" <?php checked( in_array( (int) $student_id, $assigned_ids, true ) ); ?>>
										<span>
											<strong><?php echo esc_html( $display ); ?></strong>
											<small><?php echo esc_html( $student->user_email ); ?></small>
										</span>
									</label>
								<?php endforeach; ?>
							</div>
							<div class="rks-student-picker__empty" data-rks-student-empty hidden>No students match these filters.</div>
						<?php endif; ?>
					</section>
				</div>

				<div class="rks-form-footer">
					<button class="button button-primary button-large"><?php echo Icons::svg( 'check', 12 ); ?> <?php echo $is_new ? 'Create Exam' : 'Save Changes'; ?></button>
					<a href="<?php echo esc_url( add_query_arg( 'page', self::SLUG, admin_url( 'admin.php' ) ) ); ?>" class="button button-large">Cancel</a>
				</div>
			</form>
		</div>
		<script>
		(function () {
			var wrap = document.querySelector('[data-rks-student-filter]');
			var picker = document.querySelector('[data-rks-student-picker]');
			if (!wrap || !picker) return;

			var search = document.getElementById('rks-student-search');
			var branch = document.getElementById('rks-student-branch-filter');
			var course = document.getElementById('rks-student-course-filter');
			var selected = document.getElementById('rks-student-selected-filter');
			var count = wrap.querySelector('[data-rks-visible-count]');
			var clear = wrap.querySelector('[data-rks-clear-student-filter]');
			var empty = document.querySelector('[data-rks-student-empty]');
			var items = Array.prototype.slice.call(picker.querySelectorAll('[data-rks-student-item]'));

			function matchesCourse(item, value) {
				if (!value) return true;
				var ids = (item.dataset.courseIds || '').split(',').filter(Boolean);
				return ids.indexOf(String(value)) !== -1;
			}

			function applyFilters() {
				var q = (search && search.value ? search.value : '').toLowerCase().trim();
				var branchId = branch ? branch.value : '';
				var courseId = course ? course.value : '';
				var selectedOnly = selected ? selected.checked : false;
				var visible = 0;

				items.forEach(function (item) {
					var input = item.querySelector('input[type="checkbox"]');
					var ok = true;
					if (q && (item.dataset.search || '').indexOf(q) === -1) ok = false;
					if (branchId && item.dataset.branchId !== branchId) ok = false;
					if (!matchesCourse(item, courseId)) ok = false;
					if (selectedOnly && (!input || !input.checked)) ok = false;
					item.hidden = !ok;
					item.classList.toggle('is-hidden', !ok);
					item.style.display = ok ? '' : 'none';
					if (ok) visible++;
				});

				if (count) count.textContent = String(visible);
				if (empty) empty.hidden = visible > 0;
			}

			[search, branch, course, selected].forEach(function (control) {
				if (control) control.addEventListener('input', applyFilters);
				if (control) control.addEventListener('change', applyFilters);
			});
			items.forEach(function (item) {
				var input = item.querySelector('input[type="checkbox"]');
				if (input) input.addEventListener('change', applyFilters);
			});
			if (clear) {
				clear.addEventListener('click', function () {
					if (search) search.value = '';
					if (branch) branch.value = '';
					if (course) course.value = '';
					if (selected) selected.checked = false;
					applyFilters();
					if (search) search.focus();
				});
			}
			applyFilters();
		})();
		</script>
		<?php
	}

	private function render_questions( int $quiz_id ): void {
		if ( $quiz_id <= 0 ) wp_die( 'Invalid exam' );
		$quiz = new Quiz();
		$qz   = $quiz->get_quiz( $quiz_id );
		if ( ! $qz ) wp_die( 'Exam not found' );

		$questions = $quiz->get_questions( $quiz_id );
		$edit_qid  = isset( $_GET['question_id'] ) ? (int) $_GET['question_id'] : 0;
		$edit      = $edit_qid > 0 ? $quiz->get_question( $edit_qid ) : null;
		$is_new    = ! $edit;

		?>
		<div class="wrap rks-admin-wrap">
			<header class="rks-page-hero">
				<div class="rks-page-hero__main">
					<div class="rks-page-hero__icon"><?php echo Icons::svg( 'file-text', 24 ); ?></div>
					<div>
						<small>Exam #<?php echo (int) $quiz_id; ?> · <?php echo (int) $quiz->total_points( $quiz_id ); ?> total points · <?php echo (int) $qz['passing_score']; ?>% to pass</small>
						<h1>Questions for: <?php echo esc_html( $qz['title'] ); ?></h1>
						<p>Add multiple-choice, true/false or short-answer questions. Set points, ordering and explanations.</p>
					</div>
				</div>
				<div class="rks-page-hero__actions">
					<a href="<?php echo esc_url( add_query_arg( [ 'page' => self::SLUG, 'mode' => 'attempts', 'quiz_id' => $quiz_id ], admin_url( 'admin.php' ) ) ); ?>" class="button"><?php echo Icons::svg( 'clipboard-check', 12 ); ?> View Attempts</a>
					<a href="<?php echo esc_url( add_query_arg( [ 'page' => self::SLUG, 'mode' => 'edit', 'quiz_id' => $quiz_id ], admin_url( 'admin.php' ) ) ); ?>" class="button"><?php echo Icons::svg( 'edit', 12 ); ?> Edit Settings</a>
					<a href="<?php echo esc_url( add_query_arg( 'page', self::SLUG, admin_url( 'admin.php' ) ) ); ?>" class="button"><?php echo Icons::svg( 'arrow-left', 12 ); ?> All Exams</a>
				</div>
			</header>

			<?php if ( ! empty( $_GET['rks_msg'] ) ) :
				$rks_msg_key = sanitize_key( wp_unslash( (string) $_GET['rks_msg'] ) );
				$rks_msg_map = [
					'saved'             => 'Saved successfully.',
					'deleted'           => 'Exam deleted.',
					'released'          => 'Exam released to students.',
					'hidden'            => 'Exam hidden from students.',
					'question_saved'    => 'Question saved.',
					'question_deleted'  => 'Question deleted.',
					'titles_repaired'   => 'Quiz titles repaired.',
				];
				$rks_msg_text = $rks_msg_map[ $rks_msg_key ] ?? ucfirst( str_replace( '_', ' ', $rks_msg_key ) );
			?>
				<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $rks_msg_text ); ?></p></div>
			<?php endif; ?>

			<div class="rks-q-grid">

				<section class="rks-admin-card rks-q-list-card">
					<h2 class="rks-card-title">
						<?php echo Icons::svg( 'list', 16 ); ?>
						Existing Questions <span class="rks-q-count"><?php echo count( $questions ); ?></span>
					</h2>
					<?php if ( empty( $questions ) ) : ?>
						<div class="rks-empty">
							<?php echo Icons::chip( 'file-text', 'slate', 22 ); ?>
							<h3>No questions yet</h3>
							<small>Use the form on the right to add your first question.</small>
						</div>
					<?php else : ?>
						<ol class="rks-q-list">
							<?php foreach ( $questions as $i => $q ) :
								$qtype = strtoupper( (string) $q['type'] );
								$opts_count = count( (array) $q['options'] );
							?>
								<li class="rks-q-item">
									<div class="rks-q-item__num"><?php echo (int) ( $i + 1 ); ?></div>
									<div class="rks-q-item__body">
										<strong class="rks-q-item__text"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $q['question_text'] ), 22 ) ); ?></strong>
										<div class="rks-q-item__meta">
											<span class="rks-tag rks-tag--<?php echo esc_attr( strtolower( $qtype ) ); ?>"><?php echo esc_html( $qtype ); ?></span>
											<small><?php echo (int) $q['points']; ?> pt · <?php echo (int) $opts_count; ?> option<?php echo $opts_count === 1 ? '' : 's'; ?></small>
										</div>
									</div>
									<div class="rks-q-item__actions">
										<a class="button button-small" href="<?php echo esc_url( add_query_arg( [ 'page' => self::SLUG, 'mode' => 'questions', 'quiz_id' => $quiz_id, 'question_id' => $q['id'] ], admin_url( 'admin.php' ) ) ); ?>"><?php echo Icons::svg( 'edit', 12 ); ?> Edit</a>
										<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
											<?php wp_nonce_field( 'rks_question_delete' ); ?>
											<input type="hidden" name="action" value="rks_question_delete">
											<input type="hidden" name="quiz_id" value="<?php echo (int) $quiz_id; ?>">
											<input type="hidden" name="question_id" value="<?php echo (int) $q['id']; ?>">
											<button class="button button-small button-link-delete" onclick="return confirm('Delete this question?');"><?php echo Icons::svg( 'trash', 12 ); ?> Delete</button>
										</form>
									</div>
								</li>
							<?php endforeach; ?>
						</ol>
					<?php endif; ?>
				</section>

				<section class="rks-admin-card rks-q-form-card">
					<h2 class="rks-card-title">
						<?php echo Icons::svg( $is_new ? 'plus' : 'edit', 16 ); ?>
						<?php echo $is_new ? 'Add New Question' : 'Edit Question #' . (int) $edit_qid; ?>
					</h2>

					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="rks-q-form">
						<?php wp_nonce_field( 'rks_question_save' ); ?>
						<input type="hidden" name="action" value="rks_question_save">
						<input type="hidden" name="quiz_id" value="<?php echo (int) $quiz_id; ?>">
						<input type="hidden" name="question_id" value="<?php echo (int) $edit_qid; ?>">

						<div class="rks-field">
							<label>Question *</label>
							<textarea name="question_text" rows="3" required><?php echo esc_textarea( (string) ( $edit['question_text'] ?? '' ) ); ?></textarea>
						</div>

						<div class="rks-field">
							<label>Type</label>
							<select name="type" id="rks-q-type">
								<option value="mcq"   <?php selected( ( $edit['type'] ?? 'mcq' ), 'mcq' );   ?>>Multiple Choice (single correct)</option>
								<option value="tf"    <?php selected( ( $edit['type'] ?? '' ),    'tf' );    ?>>True / False</option>
								<option value="short" <?php selected( ( $edit['type'] ?? '' ),    'short' ); ?>>Short Answer (typed)</option>
							</select>
						</div>

						<div class="rks-field rks-q-options" id="rks-q-options-wrap">
							<label>Options <span class="rks-field-hint-inline">— tick correct answer(s)</span></label>
							<?php
							$opts = (array) ( $edit['options'] ?? [ '', '', '', '' ] );
							$correct = (array) ( $edit['correct_answer'] ?? [] );
							foreach ( $opts as $i => $o ) :
								$is_correct = in_array( (string) $i, array_map( 'strval', $correct ), true );
							?>
								<label class="rks-q-option<?php echo $is_correct ? ' is-correct' : ''; ?>">
									<input type="checkbox" name="correct[]" value="<?php echo (int) $i; ?>" <?php checked( $is_correct ); ?>>
									<span class="rks-q-option__letter"><?php echo chr( 65 + $i ); ?></span>
									<input type="text" name="options[]" value="<?php echo esc_attr( (string) $o ); ?>" placeholder="Option <?php echo $i + 1; ?>">
								</label>
							<?php endforeach; ?>
							<p class="rks-field-hint">For True/False, fill only first 2 options. For Short Answer, type accepted answer(s) and tick all.</p>
						</div>

						<div class="rks-field-row rks-field-row--2">
							<div class="rks-field">
								<label>Points</label>
								<input type="number" min="1" name="points" value="<?php echo esc_attr( (string) ( $edit['points'] ?? 1 ) ); ?>">
							</div>
							<div class="rks-field">
								<label>Display Order</label>
								<input type="number" min="0" name="display_order" value="<?php echo esc_attr( (string) ( $edit['display_order'] ?? count( $questions ) + 1 ) ); ?>">
							</div>
						</div>

						<div class="rks-field">
							<label>Explanation <span class="rks-field-hint-inline">— shown after submission</span></label>
							<textarea name="explanation" rows="2"><?php echo esc_textarea( (string) ( $edit['explanation'] ?? '' ) ); ?></textarea>
						</div>

						<div class="rks-form-footer">
							<button class="button button-primary"><?php echo Icons::svg( 'check', 12 ); ?> <?php echo $is_new ? 'Add Question' : 'Save Changes'; ?></button>
							<?php if ( ! $is_new ) : ?>
								<a href="<?php echo esc_url( add_query_arg( [ 'page' => self::SLUG, 'mode' => 'questions', 'quiz_id' => $quiz_id ], admin_url( 'admin.php' ) ) ); ?>" class="button">Cancel</a>
							<?php endif; ?>
						</div>
					</form>
				</section>

			</div>
		</div>
		<?php
	}

	/* ================================================================== */
	/* HANDLERS                                                             */
	/* ================================================================== */

	public function handle_quiz_save(): void {
		check_admin_referer( 'rks_quiz_save' );
		if ( ! $this->can_manage_exams() ) wp_die( 'Forbidden' );

		$id  = (int) ( $_POST['quiz_id'] ?? 0 );
		$post = wp_unslash( $_POST );

		$data = [
			'course_id'      => (int) ( $post['course_id'] ?? 0 ),
			'title'          => sanitize_text_field( (string) ( $post['title'] ?? '' ) ),
			'description'    => sanitize_textarea_field( (string) ( $post['description'] ?? '' ) ),
			'time_limit_min' => (int) ( $post['time_limit_min'] ?? 0 ),
			'passing_score'  => (int) ( $post['passing_score'] ?? 60 ),
			'max_attempts'   => (int) ( $post['max_attempts'] ?? 3 ),
			'randomize'      => ! empty( $post['randomize'] ) ? 1 : 0,
			'show_answers'   => ! empty( $post['show_answers'] ) ? 1 : 0,
			'is_final'       => ! empty( $post['is_final'] ) ? 1 : 0,
			'is_released'    => ! empty( $post['is_released'] ) ? 1 : 0,
			'status'         => sanitize_key( (string) ( $post['status'] ?? 'published' ) ),
		];

		$quiz = new Quiz();
		if ( $id > 0 ) {
			$quiz->update_quiz( $id, $data );
		} else {
			$id = $quiz->create_quiz( $data );
		}

		$assigned_students = array_map( 'intval', (array) ( $post['assigned_students'] ?? [] ) );
		if ( $id > 0 ) {
			$quiz->sync_assigned_students( $id, $assigned_students, get_current_user_id() );
		}

		wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG, 'mode' => 'questions', 'quiz_id' => $id, 'rks_msg' => 'saved' ], admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_quiz_delete(): void {
		check_admin_referer( 'rks_quiz_delete' );
		if ( ! $this->can_manage_exams() ) wp_die( 'Forbidden' );

		( new Quiz() )->delete_quiz( (int) ( $_POST['quiz_id'] ?? 0 ) );

		wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG, 'rks_msg' => 'deleted' ], admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_quiz_release(): void {
		check_admin_referer( 'rks_quiz_release' );
		if ( ! $this->can_manage_exams() ) wp_die( 'Forbidden' );

		$quiz_id = (int) ( $_POST['quiz_id'] ?? 0 );
		$release = ! empty( $_POST['release'] );

		( new Quiz() )->set_release( $quiz_id, $release );

		wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG, 'rks_msg' => $release ? 'released' : 'hidden' ], admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_allow_attempt(): void {
		check_admin_referer( 'rks_quiz_allow_attempt' );
		if ( ! $this->can_manage_exams() ) wp_die( 'Forbidden' );

		$quiz_id = (int) ( $_POST['quiz_id'] ?? 0 );
		$user_id = (int) ( $_POST['user_id'] ?? 0 );

		if ( $quiz_id > 0 && $user_id > 0 ) {
			( new Quiz() )->allow_next_attempt( $quiz_id, $user_id, get_current_user_id() );
		}

		wp_safe_redirect( add_query_arg(
			[ 'page' => self::SLUG, 'mode' => 'attempts', 'quiz_id' => $quiz_id, 'rks_msg' => 'attempt_allowed' ],
			admin_url( 'admin.php' )
		) );
		exit;
	}

	public function handle_question_save(): void {
		check_admin_referer( 'rks_question_save' );
		if ( ! $this->can_manage_exams() ) wp_die( 'Forbidden' );

		$qid     = (int) ( $_POST['question_id'] ?? 0 );
		$quiz_id = (int) ( $_POST['quiz_id'] ?? 0 );
		$post    = wp_unslash( $_POST );

		$options = array_values( array_filter( array_map( 'sanitize_text_field', (array) ( $post['options'] ?? [] ) ), static fn( $o ) => $o !== '' ) );
		$correct = array_map( 'strval', (array) ( $post['correct'] ?? [] ) );

		$data = [
			'quiz_id'        => $quiz_id,
			'question_text'  => wp_kses_post( (string) ( $post['question_text'] ?? '' ) ),
			'type'           => sanitize_key( (string) ( $post['type'] ?? 'mcq' ) ),
			'options'        => $options,
			'correct_answer' => $correct,
			'points'         => (int) ( $post['points'] ?? 1 ),
			'display_order'  => (int) ( $post['display_order'] ?? 0 ),
			'explanation'    => sanitize_textarea_field( (string) ( $post['explanation'] ?? '' ) ),
		];

		$quiz = new Quiz();
		if ( $qid > 0 ) {
			$quiz->update_question( $qid, $data );
		} else {
			$quiz->create_question( $data );
		}

		wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG, 'mode' => 'questions', 'quiz_id' => $quiz_id, 'rks_msg' => 'saved' ], admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * One-shot repair: reverse UTF-8-as-CP1252 mojibake in quiz title/description columns.
	 *
	 * Heuristic: a value is considered corrupted if it is valid UTF-8 AND contains a known
	 * mojibake marker (e.g. "ÔÇö" — what an em-dash becomes when UTF-8 bytes are interpreted
	 * as CP1252 then re-saved as UTF-8). We then attempt to reverse the corruption by
	 * decoding the UTF-8 bytes as CP1252 and re-interpreting them as UTF-8.
	 */
	public function handle_repair_titles(): void {
		check_admin_referer( 'rks_quiz_repair_titles' );
		if ( ! $this->can_manage_exams() ) {
			wp_die( 'Forbidden' );
		}

		global $wpdb;
		$table = DB::table( 'quizzes' );
		$rows  = $wpdb->get_results( "SELECT id, title, description FROM {$table}", ARRAY_A );

		$count = 0;
		foreach ( (array) $rows as $r ) {
			$id          = (int) $r['id'];
			$title       = (string) ( $r['title'] ?? '' );
			$description = (string) ( $r['description'] ?? '' );

			$new_title       = $this->maybe_fix_mojibake( $title );
			$new_description = $this->maybe_fix_mojibake( $description );

			if ( $new_title === $title && $new_description === $description ) {
				continue;
			}

			$updated = $wpdb->update(
				$table,
				[
					'title'       => $new_title,
					'description' => $new_description,
				],
				[ 'id' => $id ],
				[ '%s', '%s' ],
				[ '%d' ]
			);
			if ( false !== $updated ) {
				$count++;
			}
		}

		wp_safe_redirect( add_query_arg(
			[ 'page' => self::SLUG, 'rks_msg' => 'titles_repaired', 'count' => $count ],
			admin_url( 'admin.php' )
		) );
		exit;
	}

	/**
	 * Detect & reverse one round of "UTF-8 bytes interpreted as CP1252" corruption.
	 *
	 * Returns the fixed string, or the original if no corruption markers are present
	 * or the reversal would produce invalid UTF-8.
	 */
	private function maybe_fix_mojibake( string $value ): string {
		if ( $value === '' ) {
			return $value;
		}
		if ( ! function_exists( 'mb_check_encoding' ) || ! function_exists( 'mb_convert_encoding' ) ) {
			return $value;
		}
		// Must currently be valid UTF-8 to be mojibake of this type.
		if ( ! mb_check_encoding( $value, 'UTF-8' ) ) {
			return $value;
		}
		// Markers for UTF-8 re-encoded from CP1252: "ÔÇ" (em/en dash family) and "Ã¢" (a-tilde + circumflex prefix).
		$markers = [ 'ÔÇ', 'Ã¢', 'â€', 'Â' ];
		$found = false;
		foreach ( $markers as $m ) {
			if ( strpos( $value, $m ) !== false ) {
				$found = true;
				break;
			}
		}
		if ( ! $found ) {
			return $value;
		}
		// Reverse: treat current UTF-8 string as a sequence of CP1252 bytes, then re-decode as UTF-8.
		$fixed = @mb_convert_encoding( $value, 'UTF-8', 'Windows-1252' );
		if ( ! is_string( $fixed ) || $fixed === '' || ! mb_check_encoding( $fixed, 'UTF-8' ) ) {
			return $value;
		}
		return $fixed;
	}

	public function handle_question_delete(): void {
		check_admin_referer( 'rks_question_delete' );
		if ( ! $this->can_manage_exams() ) wp_die( 'Forbidden' );

		$qid     = (int) ( $_POST['question_id'] ?? 0 );
		$quiz_id = (int) ( $_POST['quiz_id'] ?? 0 );

		( new Quiz() )->delete_question( $qid );

		wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG, 'mode' => 'questions', 'quiz_id' => $quiz_id, 'rks_msg' => 'deleted' ], admin_url( 'admin.php' ) ) );
		exit;
	}

	/* ================================================================== */
	/* QUIZ ATTEMPTS — list + review                                        */
	/* ================================================================== */

	private function render_attempts_list( int $quiz_id ): void {
		if ( $quiz_id <= 0 ) wp_die( 'Invalid exam' );
		global $wpdb;
		$quiz = new Quiz();
		$qz   = $quiz->get_quiz( $quiz_id );
		if ( ! $qz ) wp_die( 'Exam not found' );

		$qa = DB::table( 'quiz_attempts' );
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT a.*, u.display_name, u.user_login, u.user_email
			   FROM $qa a
			   LEFT JOIN {$wpdb->users} u ON u.ID = a.user_id
			  WHERE a.quiz_id = %d
			  ORDER BY a.id DESC LIMIT 200",
			$quiz_id
		), ARRAY_A );

		$assignments = $quiz->assignment_rows( $quiz_id );
		$total   = (int) count( $rows );
		$passed  = 0; $failed = 0; $sum = 0; $count_pct = 0;
		foreach ( $rows as $r ) {
			if ( $r['status'] === 'passed' ) $passed++;
			if ( $r['status'] === 'failed' ) $failed++;
			if ( in_array( $r['status'], [ 'passed', 'failed', 'submitted' ], true ) ) {
				$sum += (float) $r['percentage'];
				$count_pct++;
			}
		}
		$avg = $count_pct ? round( $sum / $count_pct, 1 ) : 0;
		?>
		<div class="wrap rks-admin-wrap">
			<header class="rks-page-hero">
				<div class="rks-page-hero__main">
					<div class="rks-page-hero__icon"><?php echo Icons::svg( 'clipboard-check', 24 ); ?></div>
					<div>
						<small>Exam #<?php echo (int) $quiz_id; ?> · <?php echo (int) $qz['passing_score']; ?>% to pass</small>
						<h1>Attempts — <?php echo esc_html( (string) $qz['title'] ); ?></h1>
						<p>Every student attempt with score, status and a per-answer review.</p>
					</div>
				</div>
				<div class="rks-page-hero__actions">
					<a href="<?php echo esc_url( add_query_arg( [ 'page' => self::SLUG, 'mode' => 'questions', 'quiz_id' => $quiz_id ], admin_url( 'admin.php' ) ) ); ?>" class="button"><?php echo Icons::svg( 'arrow-left', 12 ); ?> Questions</a>
				</div>
			</header>

			<?php if ( ! empty( $_GET['rks_msg'] ) ) :
				$rks_msg_key = sanitize_key( wp_unslash( (string) $_GET['rks_msg'] ) );
				$rks_msg_map = [
					'attempt_allowed' => 'Next attempt allowed for the selected student.',
				];
				$rks_msg_text = $rks_msg_map[ $rks_msg_key ] ?? ucfirst( str_replace( '_', ' ', $rks_msg_key ) );
			?>
				<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $rks_msg_text ); ?></p></div>
			<?php endif; ?>

			<div class="rks-admin-grid">
				<div class="rks-stat-card"><span class="rks-stat-card__label">Assigned Students</span><span class="rks-stat-card__num"><?php echo (int) count( $assignments ); ?></span></div>
				<div class="rks-stat-card"><span class="rks-stat-card__label">Total Attempts</span><span class="rks-stat-card__num"><?php echo (int) $total; ?></span></div>
				<div class="rks-stat-card"><span class="rks-stat-card__label">Passed</span><span class="rks-stat-card__num" style="color:#3e7a1f;"><?php echo (int) $passed; ?></span></div>
				<div class="rks-stat-card"><span class="rks-stat-card__label">Failed</span><span class="rks-stat-card__num" style="color:#c92c39;"><?php echo (int) $failed; ?></span></div>
				<div class="rks-stat-card"><span class="rks-stat-card__label">Average Score</span><span class="rks-stat-card__num"><?php echo esc_html( (string) $avg ); ?>%</span></div>
			</div>

			<div class="rks-admin-card">
				<div class="rks-section-head" style="margin-top:0;">
					<?php echo Icons::chip( 'users', 'teal' ); ?>
					<h2>Assigned Students</h2>
					<small>Retry access is controlled here after a failed attempt.</small>
				</div>
				<table class="widefat striped">
					<thead><tr><th>Student</th><th>Attempts</th><th>Best</th><th>Status</th><th>Last Attempt</th><th></th></tr></thead>
					<tbody>
						<?php if ( empty( $assignments ) ) : ?>
							<tr><td colspan="6"><em>No students assigned. Edit this exam and select students first.</em></td></tr>
						<?php else : foreach ( $assignments as $assignment ) :
							$name = $assignment['display_name'] ?: ( $assignment['user_login'] ?: 'User #' . $assignment['user_id'] );
							$used = (int) $assignment['attempts_used'];
							$allowed = (int) $assignment['attempts_allowed'];
							$remaining = (int) $assignment['attempts_remaining'];
							$passed_assignment = ! empty( $assignment['has_passed'] ) || (string) $assignment['status'] === Quiz::ASSIGN_PASSED;
						?>
							<tr>
								<td><strong><?php echo esc_html( (string) $name ); ?></strong><br><small><?php echo esc_html( (string) $assignment['user_email'] ); ?></small></td>
								<td><?php echo (int) $used; ?> used / <?php echo (int) $allowed; ?> allowed<br><small><?php echo (int) $remaining; ?> remaining</small></td>
								<td><strong><?php echo number_format( (float) $assignment['best_percentage'], 1 ); ?>%</strong></td>
								<td>
									<?php if ( $passed_assignment ) : ?>
										<span class="rks-pill rks-pill--success">Passed</span>
									<?php elseif ( $used > 0 && $remaining <= 0 ) : ?>
										<span class="rks-pill rks-pill--danger">Failed - locked</span>
									<?php elseif ( $used > 0 ) : ?>
										<span class="rks-pill rks-pill--warn">Retry allowed</span>
									<?php else : ?>
										<span class="rks-pill rks-pill--warn">Assigned</span>
									<?php endif; ?>
								</td>
								<td>
									<?php if ( ! empty( $assignment['last_attempt_id'] ) ) : ?>
										<a href="<?php echo esc_url( add_query_arg( [ 'page' => self::SLUG, 'mode' => 'attempt', 'attempt_id' => (int) $assignment['last_attempt_id'] ], admin_url( 'admin.php' ) ) ); ?>">#<?php echo (int) $assignment['last_attempt_id']; ?></a>
									<?php else : ?>
										<em>-</em>
									<?php endif; ?>
								</td>
								<td>
									<?php if ( ! $passed_assignment ) : ?>
										<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
											<?php wp_nonce_field( 'rks_quiz_allow_attempt' ); ?>
											<input type="hidden" name="action" value="rks_quiz_allow_attempt">
											<input type="hidden" name="quiz_id" value="<?php echo (int) $quiz_id; ?>">
											<input type="hidden" name="user_id" value="<?php echo (int) $assignment['user_id']; ?>">
											<button type="submit" class="button button-small"><?php echo Icons::svg( 'refresh', 12 ); ?> Allow Next Attempt</button>
										</form>
									<?php else : ?>
										<button class="button button-small" disabled>Completed</button>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; endif; ?>
					</tbody>
				</table>
			</div>

			<table class="widefat striped">
				<thead><tr><th>ID</th><th>Student</th><th>Score</th><th>%</th><th>Status</th><th>Time Taken</th><th>Submitted</th><th></th></tr></thead>
				<tbody>
					<?php if ( empty( $rows ) ) : ?>
						<tr><td colspan="8"><div class="rks-empty"><?php echo Icons::chip( 'clipboard-check', 'slate', 22 ); ?><h3>No attempts yet</h3></div></td></tr>
					<?php else : foreach ( $rows as $a ) :
						$mins = (int) floor( ( (int) $a['time_taken_sec'] ) / 60 );
						$secs = ( (int) $a['time_taken_sec'] ) % 60;
					?>
						<tr>
							<td>#<?php echo (int) $a['id']; ?></td>
							<td><strong><?php echo esc_html( (string) ( $a['display_name'] ?: $a['user_login'] ?: 'User #' . $a['user_id'] ) ); ?></strong><br><small><?php echo esc_html( (string) $a['user_email'] ); ?></small></td>
							<td><?php echo (int) $a['score']; ?> / <?php echo (int) $a['max_score']; ?></td>
							<td><strong><?php echo number_format( (float) $a['percentage'], 1 ); ?>%</strong></td>
							<td><span class="rks-pill rks-pill--<?php echo $a['status'] === 'passed' ? 'success' : ( $a['status'] === 'failed' ? 'danger' : 'warn' ); ?>"><?php echo esc_html( str_replace( '_', ' ', (string) $a['status'] ) ); ?></span></td>
							<td><small><?php echo (int) $mins; ?>m <?php echo (int) $secs; ?>s</small></td>
							<td><small><?php echo esc_html( (string) ( $a['submitted_at'] ?: $a['started_at'] ) ); ?></small></td>
							<td><a href="<?php echo esc_url( add_query_arg( [ 'page' => self::SLUG, 'mode' => 'attempt', 'attempt_id' => (int) $a['id'] ], admin_url( 'admin.php' ) ) ); ?>" class="button button-primary button-small"><?php echo Icons::svg( 'eye', 12 ); ?> Review</a></td>
						</tr>
					<?php endforeach; endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	private function render_attempt( int $attempt_id ): void {
		if ( $attempt_id <= 0 ) wp_die( 'Invalid attempt' );
		global $wpdb;

		$qa  = DB::table( 'quiz_attempts' );
		$qan = DB::table( 'quiz_answers' );
		$qq  = DB::table( 'quiz_questions' );
		$qz  = DB::table( 'quizzes' );

		$attempt = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $qa WHERE id = %d", $attempt_id ), ARRAY_A );
		if ( ! $attempt ) wp_die( 'Attempt not found' );

		$user = get_userdata( (int) $attempt['user_id'] );
		$quiz = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $qz WHERE id = %d", (int) $attempt['quiz_id'] ), ARRAY_A );

		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT a.*, q.question_text, q.type, q.options, q.correct_answer, q.points, q.explanation, q.display_order
			   FROM $qan a
			   LEFT JOIN $qq q ON q.id = a.question_id
			  WHERE a.attempt_id = %d
			  ORDER BY q.display_order ASC, a.id ASC",
			$attempt_id
		), ARRAY_A );

		$mins = (int) floor( ( (int) $attempt['time_taken_sec'] ) / 60 );
		$secs = ( (int) $attempt['time_taken_sec'] ) % 60;
		?>
		<div class="wrap rks-admin-wrap">
			<p style="margin:14px 0;"><a href="<?php echo esc_url( add_query_arg( [ 'page' => self::SLUG, 'mode' => 'attempts', 'quiz_id' => (int) $attempt['quiz_id'] ], admin_url( 'admin.php' ) ) ); ?>" class="button"><?php echo Icons::svg( 'arrow-left', 12 ); ?> All Attempts</a></p>

			<div class="rks-detail-hero">
				<div class="rks-detail-hero__avatar <?php echo $attempt['status'] === 'failed' ? 'rks-detail-hero__avatar--accent' : ''; ?>">
					<?php echo Icons::svg( $attempt['status'] === 'passed' ? 'check' : ( $attempt['status'] === 'failed' ? 'x' : 'clock' ), 28 ); ?>
				</div>
				<div class="rks-detail-hero__main">
					<small>Attempt #<?php echo (int) $attempt['id']; ?> · <?php echo esc_html( (string) ( $quiz['title'] ?? 'Quiz' ) ); ?></small>
					<h1><?php echo esc_html( (string) ( $user ? ( $user->display_name ?: $user->user_login ) : 'User #' . $attempt['user_id'] ) ); ?></h1>
					<p>
						Score <strong><?php echo (int) $attempt['score']; ?> / <?php echo (int) $attempt['max_score']; ?></strong>
						&nbsp;·&nbsp; <strong><?php echo number_format( (float) $attempt['percentage'], 1 ); ?>%</strong>
						&nbsp;·&nbsp; <?php echo (int) $mins; ?>m <?php echo (int) $secs; ?>s
					</p>
					<p style="margin-top:8px;">
						<span class="rks-pill rks-pill--<?php echo $attempt['status'] === 'passed' ? 'success' : ( $attempt['status'] === 'failed' ? 'danger' : 'warn' ); ?>"><?php echo esc_html( str_replace( '_', ' ', (string) $attempt['status'] ) ); ?></span>
						&nbsp;<small>Submitted <?php echo esc_html( (string) ( $attempt['submitted_at'] ?: $attempt['started_at'] ) ); ?></small>
					</p>
				</div>
				<div class="rks-detail-hero__actions">
					<?php if ( $user ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=rks-students&user_id=' . (int) $user->ID ) ); ?>" class="button button-primary"><?php echo Icons::svg( 'user-circle', 12 ); ?> Student Profile</a>
					<?php endif; ?>
				</div>
			</div>

			<div class="rks-admin-card">
				<div class="rks-section-head" style="margin-top:0;">
					<?php echo Icons::chip( 'file-search', 'gold' ); ?>
					<h2>Answer Review</h2>
					<small>· <?php echo (int) count( $rows ); ?> questions</small>
				</div>

				<?php if ( empty( $rows ) ) : ?>
					<em>No answers recorded for this attempt.</em>
				<?php else : foreach ( $rows as $i => $r ) :
					$opts = (array) ( json_decode( (string) ( $r['options'] ?? '' ), true ) ?: [] );
					$correct = (array) ( json_decode( (string) ( $r['correct_answer'] ?? '' ), true ) ?: [] );
					$given   = (string) ( $r['given_answer'] ?? '' );
					$given_arr = (array) ( json_decode( $given, true ) ?: [ $given ] );
					$is_correct = (bool) $r['is_correct'];
				?>
					<div class="rks-q-review <?php echo $is_correct ? 'is-correct' : 'is-wrong'; ?>">
						<h4>
							Q<?php echo (int) ( $i + 1 ); ?>.
							<?php echo esc_html( wp_strip_all_tags( (string) $r['question_text'] ) ); ?>
							<small style="float:right;"><?php echo (int) $r['points_awarded']; ?> / <?php echo (int) $r['points']; ?> pt</small>
						</h4>
						<?php if ( ! empty( $opts ) ) : ?>
							<ul class="rks-q-options">
								<?php foreach ( $opts as $oi => $opt ) :
									$is_right = in_array( (string) $oi, array_map( 'strval', $correct ), true );
									$picked   = in_array( (string) $oi, array_map( 'strval', $given_arr ), true );
									$cls      = '';
									if ( $is_right ) $cls = 'is-correct';
									if ( $picked && ! $is_right ) $cls = 'is-wrong-pick';
									if ( $picked && $is_right ) $cls = 'is-correct is-picked';
								?>
									<li class="<?php echo esc_attr( $cls ); ?>">
										<?php if ( $picked ) echo '<strong>[Picked]</strong> '; ?>
										<?php if ( $is_right ) echo '<strong>✓</strong> '; ?>
										<?php echo esc_html( (string) $opt ); ?>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php else : ?>
							<p><strong>Student answer:</strong> <code><?php echo esc_html( $given ); ?></code></p>
							<?php if ( ! empty( $correct ) ) : ?>
								<p><strong>Accepted answer(s):</strong> <code><?php echo esc_html( implode( ' / ', $correct ) ); ?></code></p>
							<?php endif; ?>
						<?php endif; ?>
						<?php if ( ! empty( $r['explanation'] ) ) : ?>
							<div class="rks-q-review__expl"><?php echo Icons::svg( 'info', 12 ); ?> <strong>Explanation:</strong> <?php echo esc_html( (string) $r['explanation'] ); ?></div>
						<?php endif; ?>
					</div>
				<?php endforeach; endif; ?>
			</div>
		</div>
		<?php
	}
}
