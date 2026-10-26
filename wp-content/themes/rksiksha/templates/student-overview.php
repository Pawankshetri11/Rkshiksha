<?php
/** @var \WP_User $user */
/** @var \RKSiksha\Core\Modules\StudentDashboard $student */
/** @var \RKSiksha\Core\Modules\Lms $lms */
if ( ! defined( 'ABSPATH' ) ) exit;

use RKSiksha\Core\Modules\Courses;

$stats = $student->stats( $user->ID );
$enrollments = $lms->user_enrollments( $user->ID );
?>

<header class="rks-dash__header">
	<div>
		<h1>Welcome back, <?php echo esc_html( explode( ' ', $user->display_name )[0] ?: $user->user_login ); ?></h1>
		<p>Continue learning where you left off, or pick a new course to enroll in.</p>
	</div>
</header>

<div class="rks-dash__stats">
	<div class="rks-dash__stat rks-dash__stat--primary">
		<span class="rks-dash__stat-label">Enrolled Courses</span>
		<span class="rks-dash__stat-num"><?php echo (int) $stats['total_enrolled']; ?></span>
	</div>
	<div class="rks-dash__stat">
		<span class="rks-dash__stat-label">In Progress</span>
		<span class="rks-dash__stat-num"><?php echo (int) $stats['in_progress']; ?></span>
	</div>
	<div class="rks-dash__stat">
		<span class="rks-dash__stat-label">Completed</span>
		<span class="rks-dash__stat-num"><?php echo (int) $stats['completed']; ?></span>
	</div>
	<div class="rks-dash__stat">
		<span class="rks-dash__stat-label">Certificates</span>
		<span class="rks-dash__stat-num"><?php echo (int) $stats['certificates']; ?></span>
	</div>
</div>

<div class="rks-dash__panel">
	<header class="rks-dash__panel-head">
		<h2>Continue Learning</h2>
		<a href="<?php echo esc_url( add_query_arg( 'view', 'courses', home_url( '/student-dashboard/' ) ) ); ?>">All courses →</a>
	</header>

	<?php if ( empty( $enrollments ) ) : ?>
		<div class="rks-dash__empty">
			<p>You're not enrolled in any course yet.</p>
			<a href="<?php echo esc_url( home_url( '/courses/' ) ); ?>" class="rks-btn rks-btn--primary" style="margin-top:12px;">Browse Courses</a>
		</div>
	<?php else : ?>
		<div class="rks-stu-grid">
			<?php foreach ( array_slice( $enrollments, 0, 6 ) as $e ) :
				$cid       = (int) $e['course_id'];
				$course    = get_post( $cid );
				if ( ! $course ) continue;
				$progress  = (int) $e['progress'];
				$lessons   = Courses::get_lessons( $cid );
				$completed_ids = $lms->completed_lesson_ids( $user->ID, $cid );

				// Find next incomplete lesson
				$next_lesson_id = 0;
				foreach ( $lessons as $l ) {
					if ( ! in_array( (int) $l->ID, $completed_ids, true ) ) {
						$next_lesson_id = (int) $l->ID;
						break;
					}
				}
				$resume_args = [ 'view' => 'lesson', 'course_id' => $cid ];
				if ( $next_lesson_id ) $resume_args['lesson_id'] = $next_lesson_id;
				$resume_url = add_query_arg( $resume_args, home_url( '/student-dashboard/' ) );
			?>
				<article class="rks-stu-card">
					<div class="rks-stu-card__progress">
						<div class="rks-stu-card__bar" style="width: <?php echo (int) $progress; ?>%;"></div>
						<span><?php echo (int) $progress; ?>%</span>
					</div>
					<h3><a href="<?php echo esc_url( $resume_url ); ?>"><?php echo esc_html( $course->post_title ); ?></a></h3>
					<p>
						<?php echo (int) $e['lessons_done']; ?> / <?php echo (int) $e['lessons_total']; ?> lessons
						&middot; <span class="rks-status rks-status--<?php echo esc_attr( $e['status'] ); ?>"><?php echo esc_html( $e['status'] ); ?></span>
					</p>
					<a href="<?php echo esc_url( $resume_url ); ?>" class="rks-btn rks-btn--primary rks-btn--block">
						<?php echo $progress > 0 ? 'Resume Course →' : 'Start Course →'; ?>
					</a>
				</article>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
