<?php
/** @var \WP_User $user */
if ( ! defined( 'ABSPATH' ) ) exit;

use RKSiksha\Core\Modules\Quiz;
use RKSiksha\Core\Support\Icons;

$attempt_id = isset( $_GET['attempt_id'] ) ? (int) $_GET['attempt_id'] : 0;
$quiz = new Quiz();
$attempt = $quiz->get_attempt( $attempt_id );

if ( ! $attempt || (int) $attempt['user_id'] !== (int) $user->ID ) {
	echo '<div class="rks-dash__panel"><div class="rks-dash__empty"><p>Attempt not found.</p><a href="' . esc_url( add_query_arg( 'view', 'quizzes', home_url( '/student-dashboard/' ) ) ) . '" class="rks-btn rks-btn--primary">Back to Exams</a></div></div>';
	return;
}

if ( $attempt['status'] !== Quiz::STATUS_IN_PROGRESS ) {
	wp_safe_redirect( add_query_arg( [ 'view' => 'quiz-result', 'attempt_id' => $attempt_id ], home_url( '/student-dashboard/' ) ) );
	exit;
}

$qz = $quiz->get_quiz( (int) $attempt['quiz_id'] );
$questions = $quiz->get_questions( (int) $attempt['quiz_id'] );
if ( $qz['randomize'] ) shuffle( $questions );

$started = strtotime( (string) $attempt['started_at'] );
$time_limit_sec = (int) $qz['time_limit_min'] * 60;
$elapsed = time() - $started;
$remaining = $time_limit_sec > 0 ? max( 0, $time_limit_sec - $elapsed ) : 0;
?>

<!-- Exam mode lockdown bar -->
<div class="rks-exam-bar">
	<div class="rks-exam-bar__title">
		<?php echo Icons::svg( 'shield', 16 ); ?>
		Exam Mode — do not leave this tab
	</div>
	<div class="rks-exam-bar__warn-count" id="rks-exam-warn-count">Violations: <span>0</span> / 3</div>
</div>

<header class="rks-dash__header">
	<div>
		<h1><?php echo esc_html( $qz['title'] ); ?></h1>
		<p><?php echo (int) count( $questions ); ?> questions · Pass: <?php echo (int) $qz['passing_score']; ?>% · Total points: <?php echo (int) $quiz->total_points( (int) $qz['id'] ); ?></p>
		<?php if ( $time_limit_sec > 0 ) : ?>
			<p style="color:var(--rks-red);font-weight:700;">⏱ Time remaining: <span id="rks-quiz-timer" data-remaining="<?php echo (int) $remaining; ?>"><?php echo gmdate( 'i:s', $remaining ); ?></span></p>
		<?php endif; ?>

		<!-- Live stats banner -->
		<div class="rks-quiz-live-stats" style="margin-top:16px; padding:16px 20px; background:var(--rks-white); border:1px solid rgba(44, 104, 123, 0.1); border-radius:8px; box-shadow:var(--rks-shadow); display:inline-flex; gap:24px; align-items:center; flex-wrap:wrap;">
			<div>Live Score: <strong id="rks-live-score" style="color:var(--rks-teal); font-size:16px;">0</strong> / <?php echo (int) $quiz->total_points( (int) $qz['id'] ); ?></div>
			<div>Current Passing: <strong id="rks-live-pct" style="color:var(--rks-teal); font-size:16px;">0.0%</strong></div>
			<div>Passing Required: <strong style="font-size:16px;"><?php echo (int) $qz['passing_score']; ?>%</strong></div>
			<div>Status: <span id="rks-live-status" class="rks-status rks-status--pending" style="background:#fff3cd; color:#8a5a00; font-weight:700; padding:4px 10px; border-radius:12px; font-size:12px; text-transform:uppercase; letter-spacing:0.5px;">Failing</span></div>
		</div>
	</div>
</header>

<form method="post" class="rks-dash__form rks-quiz-form" id="rks-quiz-form">
	<?php wp_nonce_field( 'rks_quiz_submit' ); ?>
	<input type="hidden" name="rks_action" value="quiz_submit">
	<input type="hidden" name="attempt_id" value="<?php echo (int) $attempt_id; ?>">

	<?php foreach ( $questions as $i => $q ) :
		$qid = (int) $q['id'];
		$correct_answers = array_map( 'strval', (array) $q['correct_answer'] );
		if ( $q['type'] === Quiz::TYPE_SHORT ) {
			$correct_answers = array_map( 'strtolower', array_map( 'trim', $correct_answers ) );
		}
		$correct_json = wp_json_encode( $correct_answers );
	?>
		<div class="rks-quiz-q" data-lock-question data-correct-answers="<?php echo esc_attr( $correct_json ); ?>" data-points="<?php echo (int) $q['points']; ?>" data-qtype="<?php echo esc_attr( $q['type'] ); ?>">
			<div class="rks-quiz-q__head">
				<span class="rks-quiz-q__num">Q<?php echo (int) ( $i + 1 ); ?></span>
				<small><?php echo (int) $q['points']; ?> point<?php echo $q['points'] > 1 ? 's' : ''; ?></small>
			</div>
			<div class="rks-quiz-q__text"><?php echo wp_kses_post( $q['question_text'] ); ?></div>

			<?php if ( $q['type'] === Quiz::TYPE_SHORT ) : ?>
				<input type="text" name="answers[<?php echo $qid; ?>]" class="rks-quiz-q__short" placeholder="Type your answer and press Enter or click away to lock...">
			<?php elseif ( $q['type'] === Quiz::TYPE_TF ) : ?>
				<?php foreach ( [ 'True', 'False' ] as $idx => $label ) : ?>
					<label class="rks-quiz-q__opt">
						<input type="radio" name="answers[<?php echo $qid; ?>]" value="<?php echo (int) $idx; ?>">
						<span><?php echo esc_html( $label ); ?></span>
					</label>
				<?php endforeach; ?>
			<?php else : // mcq ?>
				<?php foreach ( (array) $q['options'] as $idx => $opt ) :
					if ( $opt === '' ) continue;
				?>
					<label class="rks-quiz-q__opt">
						<input type="radio" name="answers[<?php echo $qid; ?>]" value="<?php echo (int) $idx; ?>">
						<span><?php echo esc_html( (string) $opt ); ?></span>
					</label>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>

	<div class="rks-adm-actions" style="margin-top:30px;">
		<button type="submit" class="rks-btn rks-btn--primary rks-btn--lg" onclick="return confirm('Submit your exam? You cannot change answers after submission.');">Submit Exam</button>
	</div>
</form>

<script>
(function () {
	var form = document.getElementById('rks-quiz-form');
	if (!form) return;

	var liveScore = 0;
	var totalPoints = <?php echo (int) $quiz->total_points( (int) $qz['id'] ); ?>;
	var passingScore = <?php echo (int) $qz['passing_score']; ?>;

	function updateLiveStats() {
		var scoreEl = document.getElementById('rks-live-score');
		var pctEl = document.getElementById('rks-live-pct');
		var statusEl = document.getElementById('rks-live-status');

		if (scoreEl) scoreEl.textContent = liveScore;

		var pct = totalPoints > 0 ? ((liveScore / totalPoints) * 100) : 0;
		if (pctEl) pctEl.textContent = pct.toFixed(1) + '%';

		if (statusEl) {
			if (pct >= passingScore) {
				statusEl.textContent = 'Passing';
				statusEl.style.background = '#d1f5e0';
				statusEl.style.color = '#1b6b3a';
			} else {
				statusEl.textContent = 'Failing';
				statusEl.style.background = '#fff3cd';
				statusEl.style.color = '#8a5a00';
			}
		}
	}

	form.querySelectorAll('[data-lock-question]').forEach(function (question) {
		var qtype = question.getAttribute('data-qtype');

		if (qtype === 'short') {
			var shortInput = question.querySelector('.rks-quiz-q__short');
			if (shortInput) {
				shortInput.addEventListener('keydown', function (e) {
					if (e.key === 'Enter') {
						e.preventDefault();
						shortInput.blur();
					}
				});
				shortInput.addEventListener('change', function () {
					if (question.classList.contains('is-locked')) return;

					var val = shortInput.value.trim().toLowerCase();
					if (val === '') return;

					var correctAnswers = JSON.parse(question.getAttribute('data-correct-answers') || '[]');
					var isCorrect = correctAnswers.includes(val);

					shortInput.readOnly = true;
					shortInput.style.pointerEvents = 'none';
					question.classList.add('is-locked');

					var feedback = document.createElement('p');
					feedback.style.marginTop = '12px';
					feedback.style.fontWeight = 'bold';
					if (isCorrect) {
						feedback.style.color = '#2eb872';
						feedback.textContent = '✓ Correct Answer!';
						var points = parseInt(question.getAttribute('data-points') || '1', 10);
						liveScore += points;
					} else {
						feedback.style.color = 'var(--rks-red)';
						feedback.textContent = '✗ Wrong. Correct answer(s): ' + correctAnswers.join(' / ');
					}
					question.appendChild(feedback);

					updateLiveStats();
				});
			}
		} else {
			// MCQ or T/F
			question.addEventListener('change', function (event) {
				var input = event.target;
				if (!input || input.type !== 'radio') return;
				if (question.classList.contains('is-locked')) return;

				question.classList.add('is-locked');

				var correctAnswers = JSON.parse(question.getAttribute('data-correct-answers') || '[]');
				var selectedValue = input.value;
				var isCorrect = correctAnswers.includes(selectedValue);

				var options = question.querySelectorAll('.rks-quiz-q__opt');
				options.forEach(function (label) {
					label.style.pointerEvents = 'none';
					var optInput = label.querySelector('input[type="radio"]');
					var val = optInput ? optInput.value : '';

					if (correctAnswers.includes(val)) {
						label.classList.add('rks-quiz-q__opt--is-answer');
						if (!label.querySelector('.rks-correct-check')) {
							var check = document.createElement('span');
							check.className = 'rks-correct-check';
							check.style.color = '#2eb872';
							check.style.fontWeight = 'bold';
							check.style.marginRight = '8px';
							check.textContent = '✓ ';
							label.insertBefore(check, label.firstChild);
						}
					} else if (val === selectedValue) {
						label.classList.add('rks-quiz-q__opt--is-given-wrong');
						if (!label.querySelector('.rks-incorrect-cross')) {
							var cross = document.createElement('span');
							cross.className = 'rks-incorrect-cross';
							cross.style.color = 'var(--rks-red)';
							cross.style.fontWeight = 'bold';
							cross.style.marginRight = '8px';
							cross.textContent = '✗ ';
							label.insertBefore(cross, label.firstChild);
						}
					}

					if (val === selectedValue) {
						label.classList.add('is-selected');
					}
				});

				var points = parseInt(question.getAttribute('data-points') || '1', 10);
				if (isCorrect) {
					liveScore += points;
				}
				updateLiveStats();
			});
		}
	});
})();
</script>

<?php if ( $time_limit_sec > 0 ) : ?>
<script>
(function () {
	var el = document.getElementById('rks-quiz-timer');
	var form = document.getElementById('rks-quiz-form');
	if (!el) return;
	var remaining = parseInt(el.dataset.remaining || '0', 10);
	var t = setInterval(function () {
		remaining--;
		if (remaining <= 0) {
			clearInterval(t);
			el.textContent = '00:00';
			alert('Time is up! Your exam will be submitted now.');
			form.submit();
			return;
		}
		var m = Math.floor(remaining / 60), s = remaining % 60;
		el.textContent = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
	}, 1000);
})();
</script>
<?php endif; ?>

<!-- Instructions modal (shown on load) -->
<div class="rks-exam-modal" id="rks-exam-modal">
	<div class="rks-exam-modal__panel">
		<div class="rks-exam-modal__head">
			<div class="rks-exam-modal__icon"><?php echo Icons::svg( 'shield', 20 ); ?></div>
			<h2>Before you start the exam</h2>
		</div>
		<ul class="rks-exam-modal__list">
			<li>The exam will open in fullscreen mode. Stay on this tab until you submit.</li>
			<li>Switching tabs, minimising the window, or pressing Escape will be flagged.</li>
			<li>After 3 violations, your attempt will be auto-submitted with current answers.</li>
			<li>Right-click, copy, paste, and developer tools are disabled during the exam.</li>
			<li>Make sure you have a stable internet connection. Submit before the timer ends.</li>
		</ul>
		<div class="rks-exam-modal__actions">
			<label class="rks-exam-modal__agree">
				<input type="checkbox" id="rks-exam-agree"> I understand and agree
			</label>
			<button type="button" class="rks-btn rks-btn--primary rks-exam-modal__start" id="rks-exam-start" disabled>Start Exam</button>
		</div>
	</div>
</div>

<!-- Violation warning overlay (hidden until triggered) -->
<div class="rks-exam-violation" id="rks-exam-violation" style="display:none;">
	<div class="rks-exam-violation__panel">
		<div class="rks-exam-violation__icon"><?php echo Icons::svg( 'alert', 40 ); ?></div>
		<div class="rks-exam-violation__count">Violation <span id="rks-exam-vc">1</span> of 3</div>
		<h2>Tab switching not allowed</h2>
		<p>You left the exam window. Please stay focused on this tab until you submit. After 3 violations your attempt will be auto-submitted.</p>
		<button type="button" class="rks-exam-violation__resume" id="rks-exam-resume">Resume Exam</button>
	</div>
</div>

<script>
(function () {
	var modal      = document.getElementById('rks-exam-modal');
	var agreeBox   = document.getElementById('rks-exam-agree');
	var startBtn   = document.getElementById('rks-exam-start');
	var violation  = document.getElementById('rks-exam-violation');
	var resumeBtn  = document.getElementById('rks-exam-resume');
	var vcCounter  = document.getElementById('rks-exam-vc');
	var warnCount  = document.getElementById('rks-exam-warn-count');
	var form       = document.getElementById('rks-quiz-form');
	if (!modal || !form) return;

	var violations = 0;
	var maxViolations = 3;
	var quizStarted = false;
	var submitting = false;

	function enterFullscreen() {
		var el = document.documentElement;
		var req = el.requestFullscreen || el.webkitRequestFullscreen || el.msRequestFullscreen;
		if (req) {
			try { req.call(el); } catch (e) { /* user may deny; we still proceed */ }
		}
	}

	function exitFullscreen() {
		if (document.fullscreenElement || document.webkitFullscreenElement) {
			var ex = document.exitFullscreen || document.webkitExitFullscreen || document.msExitFullscreen;
			if (ex) { try { ex.call(document); } catch (e) {} }
		}
	}

	agreeBox.addEventListener('change', function () {
		startBtn.disabled = !agreeBox.checked;
	});

	startBtn.addEventListener('click', function () {
		if (!agreeBox.checked) return;
		modal.style.display = 'none';
		enterFullscreen();
		quizStarted = true;
	});

	function recordViolation() {
		if (!quizStarted || submitting) return;
		violations++;
		var warnSpan = warnCount.querySelector('span');
		if (warnSpan) warnSpan.textContent = violations;
		if (violations >= 2) warnCount.classList.add('is-warning');

		if (violations >= maxViolations) {
			// Auto-submit on 3rd violation
			submitting = true;
			quizStarted = false;
			try { form.submit(); } catch (e) {}
			return;
		}
		vcCounter.textContent = violations;
		violation.style.display = 'flex';
	}

	resumeBtn.addEventListener('click', function () {
		violation.style.display = 'none';
		enterFullscreen();
	});

	// Tab/window blur detection
	document.addEventListener('visibilitychange', function () {
		if (document.hidden) recordViolation();
	});
	window.addEventListener('blur', function () {
		// Fires when window loses focus (alt-tab, etc.)
		recordViolation();
	});

	// Disable right-click
	document.addEventListener('contextmenu', function (e) {
		if (quizStarted) e.preventDefault();
	});

	// Disable common shortcuts
	document.addEventListener('keydown', function (e) {
		if (!quizStarted) return;
		var k = e.key;
		var c = e.ctrlKey || e.metaKey;
		// F12, Ctrl+Shift+I/J/C, Ctrl+U, Ctrl+S, Ctrl+P
		if (k === 'F12') { e.preventDefault(); return; }
		if (c && e.shiftKey && (k === 'I' || k === 'J' || k === 'C')) { e.preventDefault(); return; }
		if (c && (k === 'u' || k === 'U' || k === 's' || k === 'S' || k === 'p' || k === 'P')) { e.preventDefault(); return; }
		// Disable copy/paste/cut (we're using copy/paste lock)
		if (c && (k === 'c' || k === 'C' || k === 'v' || k === 'V' || k === 'x' || k === 'X' || k === 'a' || k === 'A')) {
			// Allow only inside text inputs (short-answer answers)
			if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
				e.preventDefault();
			}
		}
	});

	// Disable selection outside inputs
	document.addEventListener('selectstart', function (e) {
		if (!quizStarted) return;
		if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
			e.preventDefault();
		}
	});

	// Warn if user tries to leave via back button / close
	window.addEventListener('beforeunload', function (e) {
		if (quizStarted && !submitting) {
			e.preventDefault();
			e.returnValue = '';
			return '';
		}
	});

	// Cleanup fullscreen + listeners after submit
	form.addEventListener('submit', function () {
		submitting = true;
		quizStarted = false;
		exitFullscreen();
	});
})();
</script>
