<?php
/** @var \WP_User $user */
/** @var \RKSiksha\Core\Modules\Lms $lms */
if ( ! defined( 'ABSPATH' ) ) exit;

use RKSiksha\Core\Modules\Courses;
use RKSiksha\Core\Modules\Subscriptions;

$course_id = isset( $_GET['course_id'] ) ? (int) $_GET['course_id'] : 0;
$lesson_id = isset( $_GET['lesson_id'] ) ? (int) $_GET['lesson_id'] : 0;

$enrollment = $course_id ? $lms->get_enrollment( $user->ID, $course_id ) : null;
if ( ! $enrollment ) {
	echo '<div class="rks-dash__panel"><div class="rks-dash__empty"><p>You\'re not enrolled in this course.</p><a href="' . esc_url( home_url( '/courses/' ) ) . '" class="rks-btn rks-btn--primary">Browse Courses</a></div></div>';
	return;
}
// Strictly allow only active/completed enrollments through. Any other status
// (pending, rejected, NULL, empty, unexpected) keeps the course locked.
if ( ! in_array( (string) ( $enrollment['status'] ?? '' ), [ 'active', 'completed' ], true ) ) {
	echo '<div class="rks-dash__panel"><div class="rks-dash__empty"><p><strong>Course Locked</strong></p><p>Your admission is awaiting admin approval. Lessons will unlock automatically once approved (usually within 24 hours).</p><a href="' . esc_url( add_query_arg( 'view', 'courses', home_url( '/student-dashboard/' ) ) ) . '" class="rks-btn rks-btn--primary">Back to My Courses</a></div></div>';
	return;
}

// Lock lessons if a monthly installment is overdue.
$rks_subs = new Subscriptions();
$rks_sub  = $rks_subs->get_for_user( $user->ID, $course_id );
if ( $rks_sub && $rks_subs->is_payment_due( $rks_sub ) ) {
	$pay_url = add_query_arg( [ 'view' => 'courses', 'pay_course' => $course_id ], home_url( '/student-dashboard/' ) );
	echo '<div class="rks-dash__panel"><div class="rks-dash__empty"><p><strong>Lessons Locked</strong></p><p>Please clear this month\'s payment to continue lessons.</p><a href="' . esc_url( $pay_url ) . '" class="rks-btn rks-btn--primary">Pay This Month →</a></div></div>';
	return;
}

$course  = get_post( $course_id );
$lessons = Courses::get_lessons( $course_id );

if ( ! $lesson_id && ! empty( $lessons ) ) $lesson_id = (int) $lessons[0]->ID;
$lesson = $lesson_id ? get_post( $lesson_id ) : null;

if ( ! $lesson || $lesson->post_type !== Courses::CPT_LESSON ) {
	echo '<div class="rks-dash__panel"><div class="rks-dash__empty"><p>Lesson not found.</p></div></div>';
	return;
}

$completed_ids = $lms->completed_lesson_ids( $user->ID, $course_id );
$is_completed  = in_array( $lesson_id, $completed_ids, true );
$progress      = (int) $enrollment['progress'];

$video_url  = (string) get_post_meta( $lesson_id, Courses::ML_VIDEO_URL, true );
$video_type = (string) get_post_meta( $lesson_id, Courses::ML_VIDEO_TYPE, true );
$duration   = (int) get_post_meta( $lesson_id, Courses::ML_DURATION_MIN, true );

$embed        = Courses::video_embed_url( $video_url, $video_type );
$direct_video = Courses::is_direct_video_url( $video_url );
?>

<header class="rks-dash__header">
	<div>
		<nav class="rks-dash__crumb" aria-label="Breadcrumb">
			<a href="<?php echo esc_url( add_query_arg( 'view', 'overview', home_url( '/student-dashboard/' ) ) ); ?>">Dashboard</a> <span>/</span>
			<a href="<?php echo esc_url( add_query_arg( 'view', 'courses', home_url( '/student-dashboard/' ) ) ); ?>">My Courses</a> <span>/</span>
			<span><?php echo esc_html( $course->post_title ); ?></span>
		</nav>
		<h1><?php echo esc_html( $lesson->post_title ); ?></h1>
		<p><?php echo esc_html( $course->post_title ); ?> &middot; Lesson progress: <strong><?php echo (int) $progress; ?>%</strong></p>
	</div>
</header>

<div class="rks-stu-lesson">

	<main class="rks-stu-lesson__main">
		<?php if ( $embed ) : ?>
			<div class="rks-stu-lesson__video">
				<iframe src="<?php echo esc_url( $embed ); ?>" frameborder="0"
				        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
				        allowfullscreen></iframe>
			</div>
		<?php elseif ( $direct_video ) : ?>
			<div class="rks-stu-lesson__video">
				<video src="<?php echo esc_url( $video_url ); ?>" controls preload="metadata" playsinline></video>
			</div>
		<?php elseif ( $video_url ) : ?>
			<div class="rks-stu-lesson__video rks-stu-lesson__video--placeholder">
				<span>This video URL cannot be played inline. Please update the lesson with a YouTube, Vimeo, MP4 or WebM URL.</span>
			</div>
		<?php else : ?>
			<div class="rks-stu-lesson__video rks-stu-lesson__video--placeholder">
				<span>Video coming soon</span>
			</div>
		<?php endif; ?>

		<?php if ( $duration > 0 ) : ?>
			<p class="rks-stu-lesson__meta">⏱ Approx. <?php echo (int) $duration; ?> minutes</p>
		<?php endif; ?>

		<?php if ( ! empty( $lesson->post_content ) ) : ?>
			<div class="rks-stu-lesson__notes">
				<h2>Lesson Notes</h2>
				<?php echo apply_filters( 'the_content', $lesson->post_content ); ?>
			</div>
		<?php endif; ?>

		<div class="rks-stu-lesson__actions">
			<?php if ( $is_completed ) : ?>
				<span class="rks-stu-lesson__done">✓ Completed</span>
			<?php else : ?>
				<form method="post">
					<?php wp_nonce_field( 'rks_lesson_complete' ); ?>
					<input type="hidden" name="rks_action" value="mark_complete">
					<input type="hidden" name="lesson_id" value="<?php echo (int) $lesson_id; ?>">
					<input type="hidden" name="course_id" value="<?php echo (int) $course_id; ?>">
					<button type="submit" class="rks-btn rks-btn--primary rks-btn--lg">Mark as Complete →</button>
				</form>
			<?php endif; ?>
		</div>
	</main>

	<aside class="rks-stu-lesson__side">
		<div class="rks-stu-lesson__progress">
			<div class="rks-stu-card__progress">
				<div class="rks-stu-card__bar" style="width: <?php echo (int) $progress; ?>%;"></div>
				<span><?php echo (int) $progress; ?>%</span>
			</div>
			<small><?php echo (int) $enrollment['lessons_done']; ?> / <?php echo (int) $enrollment['lessons_total']; ?> lessons completed</small>
		</div>

		<h3>Course Lessons</h3>
		<ol class="rks-stu-lesson__list">
			<?php foreach ( $lessons as $i => $l ) :
				$lid    = (int) $l->ID;
				$active = $lid === $lesson_id;
				$done   = in_array( $lid, $completed_ids, true );
				$url    = add_query_arg( [ 'view' => 'lesson', 'course_id' => $course_id, 'lesson_id' => $lid ], home_url( '/student-dashboard/' ) );
			?>
				<li class="<?php echo $active ? 'is-active' : ''; ?> <?php echo $done ? 'is-done' : ''; ?>">
					<a href="<?php echo esc_url( $url ); ?>">
						<span class="rks-stu-lesson__list-num"><?php echo $done ? '✓' : ( $i + 1 ); ?></span>
						<span class="rks-stu-lesson__list-title"><?php echo esc_html( $l->post_title ); ?></span>
						<?php
						$d = (int) get_post_meta( $lid, Courses::ML_DURATION_MIN, true );
						if ( $d > 0 ) echo '<span class="rks-stu-lesson__list-dur">' . (int) $d . 'm</span>';
						?>
					</a>
				</li>
			<?php endforeach; ?>
		</ol>
	</aside>
</div>
