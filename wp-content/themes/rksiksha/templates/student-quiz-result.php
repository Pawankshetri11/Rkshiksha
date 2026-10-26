<?php
/** @var \WP_User $user */
if ( ! defined( 'ABSPATH' ) ) exit;

use RKSiksha\Core\Modules\Quiz;
use RKSiksha\Core\Support\DB;

$attempt_id = isset( $_GET['attempt_id'] ) ? (int) $_GET['attempt_id'] : 0;
$quiz = new Quiz();
$attempt = $quiz->get_attempt( $attempt_id );

if ( ! $attempt || (int) $attempt['user_id'] !== (int) $user->ID ) {
	echo '<div class="rks-dash__panel"><div class="rks-dash__empty"><p>Result not found.</p><a href="' . esc_url( add_query_arg( 'view', 'quizzes', home_url( '/student-dashboard/' ) ) ) . '" class="rks-btn rks-btn--primary">Back to Exams</a></div></div>';
	return;
}

$qz = $quiz->get_quiz( (int) $attempt['quiz_id'] );
$pct = (float) $attempt['percentage'];
$passed = $attempt['status'] === Quiz::STATUS_PASSED;

global $wpdb;
$certificate = null;
if ( $passed && ! empty( $qz['is_final'] ) ) {
	$certificate = $wpdb->get_row( $wpdb->prepare(
		"SELECT unique_id FROM " . DB::table( 'certificates' ) . " WHERE user_id = %d AND course_id = %d AND status = 'valid' LIMIT 1",
		$user->ID,
		(int) $qz['course_id']
	), ARRAY_A );
}

$user_answers = $wpdb->get_results( $wpdb->prepare(
	'SELECT * FROM ' . DB::table( 'quiz_answers' ) . ' WHERE attempt_id = %d',
	$attempt_id
), ARRAY_A );
$ans_map = [];
foreach ( $user_answers as $a ) $ans_map[ (int) $a['question_id'] ] = $a;

$questions = $quiz->get_questions( (int) $qz['id'] );
?>

<header class="rks-dash__header">
	<div>
		<h1><?php echo esc_html( $qz['title'] ); ?> — Result</h1>
	</div>
</header>

<div class="rks-quiz-result <?php echo $passed ? 'rks-quiz-result--pass' : 'rks-quiz-result--fail'; ?>">
	<div class="rks-quiz-result__icon"><?php echo $passed ? '✓' : '✗'; ?></div>
	<h2><?php echo $passed ? 'Congratulations — You passed!' : 'Not quite there yet'; ?></h2>
	<div class="rks-quiz-result__stats">
		<div><strong><?php echo (int) $attempt['score']; ?> / <?php echo (int) $attempt['max_score']; ?></strong><small>Score</small></div>
		<div><strong><?php echo number_format( $pct, 1 ); ?>%</strong><small>Percentage</small></div>
		<div><strong><?php echo (int) $qz['passing_score']; ?>%</strong><small>Required</small></div>
		<div><strong><?php echo gmdate( 'i:s', (int) $attempt['time_taken_sec'] ); ?></strong><small>Time taken</small></div>
	</div>
	<div class="rks-quiz-result__actions">
		<?php if ( $certificate ) : ?>
			<a href="<?php echo esc_url( add_query_arg( 'id', $certificate['unique_id'], home_url( '/certificate/' ) ) ); ?>" class="rks-btn rks-btn--primary" target="_blank" rel="noopener">View Certificate</a>
		<?php endif; ?>
		<a href="<?php echo esc_url( add_query_arg( 'view', 'quizzes', home_url( '/student-dashboard/' ) ) ); ?>" class="rks-btn rks-btn--ghost">Back to Exams</a>
	</div>
</div>

<?php if ( $qz['show_answers'] ) : ?>
<div class="rks-dash__panel">
	<header class="rks-dash__panel-head"><h2>Review Answers</h2></header>
	<?php foreach ( $questions as $i => $q ) :
		$qid       = (int) $q['id'];
		$ans       = $ans_map[ $qid ] ?? null;
		$correct   = (array) $q['correct_answer'];
		$given_raw = $ans ? (string) $ans['given_answer'] : '';
		$given_arr = $given_raw && $given_raw[0] === '[' ? (array) json_decode( $given_raw, true ) : [ $given_raw ];
		$is_correct = $ans && (int) $ans['is_correct'] === 1;
	?>
		<div class="rks-quiz-q rks-quiz-q--review <?php echo $is_correct ? 'is-correct' : 'is-wrong'; ?>">
			<div class="rks-quiz-q__head">
				<span class="rks-quiz-q__num">Q<?php echo (int) ( $i + 1 ); ?></span>
				<span class="rks-status rks-status--<?php echo $is_correct ? 'approved' : 'rejected'; ?>"><?php echo $is_correct ? '✓ Correct' : '✗ Wrong'; ?></span>
			</div>
			<div class="rks-quiz-q__text"><?php echo wp_kses_post( $q['question_text'] ); ?></div>

			<?php if ( $q['type'] === Quiz::TYPE_SHORT ) : ?>
				<p>Your answer: <code><?php echo esc_html( (string) $given_raw ); ?></code></p>
				<p>Correct answer(s): <code><?php echo esc_html( implode( ' / ', array_map( 'strval', $correct ) ) ); ?></code></p>
			<?php else :
				$labels = $q['type'] === Quiz::TYPE_TF ? [ 'True', 'False' ] : (array) $q['options'];
				foreach ( $labels as $idx => $label ) :
					if ( $label === '' ) continue;
					$is_given   = in_array( (string) $idx, array_map( 'strval', $given_arr ), true );
					$is_correct_opt = in_array( (string) $idx, array_map( 'strval', $correct ), true );
					$cls = '';
					if ( $is_correct_opt ) $cls = 'is-answer';
					if ( $is_given && ! $is_correct_opt ) $cls = 'is-given-wrong';
			?>
				<div class="rks-quiz-q__opt rks-quiz-q__opt--<?php echo esc_attr( $cls ); ?>">
					<?php if ( $is_correct_opt ) echo '✓ '; elseif ( $is_given ) echo '✗ '; else echo '&nbsp;&nbsp;'; ?>
					<span><?php echo esc_html( (string) $label ); ?></span>
				</div>
			<?php endforeach; endif; ?>

			<?php if ( ! empty( $q['explanation'] ) ) : ?>
				<p class="rks-quiz-q__explain"><strong>Explanation:</strong> <?php echo esc_html( (string) $q['explanation'] ); ?></p>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
</div>
<?php endif; ?>
