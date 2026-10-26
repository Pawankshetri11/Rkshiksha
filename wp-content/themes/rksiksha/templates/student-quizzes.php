<?php
/** @var \WP_User $user */
if ( ! defined( 'ABSPATH' ) ) exit;

use RKSiksha\Core\Modules\Quiz;

$quiz = new Quiz();
$assigned_exams = $quiz->assigned_quizzes_for_user( $user->ID );
$msg = isset( $_GET['rks_msg'] ) ? sanitize_key( wp_unslash( (string) $_GET['rks_msg'] ) ) : '';
?>

<header class="rks-dash__header">
	<div>
		<h1>Exams</h1>
		<p>Exams assigned by the admin. Pass an assigned final exam to unlock its certificate.</p>
	</div>
</header>

<?php if ( 'attempts_exceeded' === $msg ) : ?>
	<div class="rks-dash__alert rks-dash__alert--error">This exam is not available right now. If you failed, please contact admin to allow your next attempt.</div>
<?php endif; ?>

<?php if ( empty( $assigned_exams ) ) : ?>
	<div class="rks-dash__panel">
		<div class="rks-dash__empty">
			<p>No exam has been assigned to you yet.</p>
			<a href="<?php echo esc_url( home_url( '/courses/' ) ); ?>" class="rks-btn rks-btn--primary">Browse Courses</a>
		</div>
	</div>
<?php else : ?>
	<div class="rks-dash__panel">
		<header class="rks-dash__panel-head">
			<h2>Assigned Exams</h2>
			<small><?php echo count( $assigned_exams ); ?> exam(s)</small>
		</header>

		<div class="rks-stu-grid">
			<?php foreach ( $assigned_exams as $q ) :
				$attempts = $quiz->user_attempts( $user->ID, (int) $q['id'] );
				$in_progress = null;
				$best_pct = 0;
				foreach ( $attempts as $attempt ) {
					$best_pct = max( $best_pct, (float) $attempt['percentage'] );
					if ( (string) $attempt['status'] === Quiz::STATUS_IN_PROGRESS ) {
						$in_progress = $attempt;
					}
				}
				$used      = (int) ( $q['attempts_used'] ?? 0 );
				$remaining = (int) ( $q['attempts_remaining'] ?? 0 );
				$passed    = ! empty( $q['has_passed'] );
				$qcount    = $quiz->count_questions( (int) $q['id'] );
				$course    = get_post( (int) $q['course_id'] );
				$can_attempt = ! $passed && $qcount > 0 && ( $remaining > 0 || $in_progress );
			?>
				<article class="rks-stu-card">
					<h3>
						<?php echo esc_html( $q['title'] ); ?>
						<?php if ( $q['is_final'] ) : ?> <span class="rks-status rks-status--approved">Certificate Exam</span><?php endif; ?>
					</h3>
					<p>
						<?php if ( $course ) : ?>
							<?php echo esc_html( $course->post_title ); ?> &middot;
						<?php endif; ?>
						<?php echo (int) $qcount; ?> questions &middot;
						Pass: <?php echo (int) $q['passing_score']; ?>%
						<?php if ( (int) $q['time_limit_min'] > 0 ) : ?> &middot; <?php echo (int) $q['time_limit_min']; ?> min<?php endif; ?>
					</p>
					<p>
						Attempts: <?php echo (int) $used; ?> used / <?php echo (int) $q['attempts_allowed']; ?> allowed
						<?php if ( $remaining > 0 ) : ?>
							&middot; <?php echo (int) $remaining; ?> remaining
						<?php endif; ?>
					</p>

					<?php if ( $passed ) : ?>
						<p><span class="rks-status rks-status--approved">Passed (Best <?php echo number_format( $best_pct, 1 ); ?>%)</span></p>
					<?php elseif ( $in_progress ) : ?>
						<p><span class="rks-status rks-status--pending">In progress</span></p>
					<?php elseif ( $used > 0 && $remaining <= 0 ) : ?>
						<p><span class="rks-status rks-status--rejected">Failed - admin must allow next attempt</span></p>
					<?php elseif ( $used > 0 ) : ?>
						<p><span class="rks-status rks-status--pending">Retry allowed. Best score: <?php echo number_format( $best_pct, 1 ); ?>%</span></p>
					<?php endif; ?>

					<?php if ( $can_attempt ) : ?>
						<form method="post" style="margin:0;">
							<?php wp_nonce_field( 'rks_quiz_start' ); ?>
							<input type="hidden" name="rks_action" value="quiz_start">
							<input type="hidden" name="quiz_id" value="<?php echo (int) $q['id']; ?>">
							<button type="submit" class="rks-btn rks-btn--primary rks-btn--block">
								<?php echo $in_progress ? 'Resume Exam' : ( $used > 0 ? 'Try Again' : 'Start Exam' ); ?>
							</button>
						</form>
					<?php elseif ( $qcount === 0 ) : ?>
						<button class="rks-btn rks-btn--ghost rks-btn--block" disabled>No questions added yet</button>
					<?php elseif ( $passed ) : ?>
						<button class="rks-btn rks-btn--ghost rks-btn--block" disabled>Completed</button>
					<?php else : ?>
						<button class="rks-btn rks-btn--ghost rks-btn--block" disabled>Waiting for admin retry approval</button>
					<?php endif; ?>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
<?php endif; ?>
