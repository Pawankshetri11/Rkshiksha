<?php
/** @var \WP_User $user */
/** @var \RKSiksha\Core\Modules\Lms $lms */
if ( ! defined( 'ABSPATH' ) ) exit;

use RKSiksha\Core\Modules\Courses;
use RKSiksha\Core\Modules\Subscriptions;
use RKSiksha\Core\Support\Icons;

$cid = isset( $_GET['course_id'] ) ? (int) $_GET['course_id'] : 0;
$course = get_post( $cid );

if ( ! $course ) {
	echo '<div class="rks-dash__empty"><p>Invalid course selected.</p></div>';
	return;
}

$subs        = new Subscriptions();
$sub         = $subs->get_for_user( $user->ID, $cid );

if ( ! $sub ) {
	echo '<div class="rks-dash__empty"><p>No active monthly subscription found for this course.</p></div>';
	return;
}

$pay_due     = $subs->is_payment_due( $sub );
$can_pay     = $subs->can_pay_now( $sub );
$pay_pending = $subs->has_pending_payment( (int) $sub['id'] );
$sub_done    = ( $sub['status'] === Subscriptions::STATUS_COMPLETED );

if ( $sub_done ) {
	echo '<div class="rks-dash__empty"><p>This course subscription is already fully paid.</p><p><a href="' . esc_url( add_query_arg( 'view', 'courses', home_url( '/student-dashboard/' ) ) ) . '" class="rks-btn rks-btn--primary">Back to Courses</a></p></div>';
	return;
}

if ( $pay_pending ) {
	echo '<div class="rks-dash__empty"><p>A payment for this course is already pending admin verification.</p><p><a href="' . esc_url( add_query_arg( 'view', 'courses', home_url( '/student-dashboard/' ) ) ) . '" class="rks-btn rks-btn--primary">Back to Courses</a></p></div>';
	return;
}

if ( ! $can_pay ) {
	echo '<div class="rks-dash__empty"><p>You cannot make a payment for this course right now.</p><p><a href="' . esc_url( add_query_arg( 'view', 'courses', home_url( '/student-dashboard/' ) ) ) . '" class="rks-btn rks-btn--primary">Back to Courses</a></p></div>';
	return;
}

$settings    = (array) get_option( 'rks_settings', [] );
$currency    = ! empty( $settings['currency_symbol'] ) ? (string) $settings['currency_symbol'] : '₹';
$upi_id      = (string) ( $settings['upi_id'] ?? '' );
$qr_id       = (int)    ( $settings['upi_qr_image_id'] ?? 0 );
$qr_url      = $qr_id ? wp_get_attachment_image_url( $qr_id, 'medium' ) : '';

$penalty = $subs->calculate_late_fee( $sub );
$days_overdue = 0;
if ( $penalty > 0 && ! empty( $sub['next_due_date'] ) ) {
	$due_time = strtotime( $sub['next_due_date'] );
	$today_time = strtotime( current_time( 'Y-m-d' ) );
	$days_overdue = (int) round( ( $today_time - $due_time ) / 86400 );
}

$flash_err = isset( $_GET['rks_err'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['rks_err'] ) ) : '';
?>

<header class="rks-dash__header">
	<div>
		<a href="<?php echo esc_url( add_query_arg( 'view', 'courses', home_url( '/student-dashboard/' ) ) ); ?>" class="rks-back-link">
			<?php echo Icons::svg( 'arrow-left', 14 ); ?> Back to My Courses
		</a>
		<h1 style="margin-top: 10px;">Monthly Fees Payment</h1>
		<p>Pay your installment or full balance for <strong><?php echo esc_html( $course->post_title ); ?></strong></p>
	</div>
</header>

<?php if ( $flash_err ) : ?>
	<div class="rks-flash rks-flash--error">
		<?php echo Icons::svg( 'alert', 18 ); ?>
		<?php 
		$err_msg = $flash_err;
		if ( $flash_err === 'invalid_course' ) {
			$err_msg = 'Invalid course selection.';
		} elseif ( $flash_err === 'generic' ) {
			$err_msg = 'Something went wrong. Please check your input and try again.';
		}
		echo esc_html( $err_msg ); 
		?>
	</div>
<?php endif; ?>

<div class="rks-dash__panel rks-pay-panel">
	<div class="rks-pay-layout">
		<!-- Left / Form Side -->
		<div class="rks-pay-form-container">
			<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="rks-pay-form">
				<?php wp_nonce_field( Subscriptions::NONCE ); ?>
				<input type="hidden" name="action" value="rks_pay_monthly">
				<input type="hidden" name="course_id" value="<?php echo (int) $cid; ?>">

				<?php if ( $penalty > 0 ) : ?>
					<div class="rks-pay-late-fee-breakdown" style="margin-bottom: 20px; padding: 14px 16px; background: rgba(230, 57, 70, 0.05); border: 1px solid rgba(230, 57, 70, 0.15); border-radius: 8px; color: var(--rks-red, #E63946);">
						<strong style="display: flex; align-items: center; gap: 6px; margin-bottom: 4px; font-weight: 700;">
							<?php echo Icons::svg( 'alert', 16 ); ?> Late Payment Penalty Applied
						</strong>
						<span style="font-size: 13px; color: #374151; display: block; line-height: 1.5;">
							Due Date: <strong><?php echo esc_html( mysql2date( get_option( 'date_format', 'F j, Y' ), $sub['next_due_date'] ) ); ?></strong> (<?php echo (int) $days_overdue; ?> days overdue)
							<br>Penalty Amount: <strong><?php echo esc_html( $currency . number_format( $penalty, 0 ) ); ?></strong> (Added to total amount)
						</span>
					</div>
				<?php endif; ?>

				<h3 class="rks-pay-form__title">1. Choose Payment Option</h3>
				<?php
				$remaining_months = (int) $sub['total_months'] - (int) $sub['months_paid'];
				$full_amount = $remaining_months * (float) $sub['monthly_fee'];
				if ( $remaining_months > 1 ) :
				?>
					<div class="rks-pay-form__options">
						<label class="rks-pay-form__option">
							<input type="radio" name="payment_type" value="installment" checked data-amount="<?php echo esc_attr( (float) $sub['monthly_fee'] + $penalty ); ?>">
							<span class="rks-pay-form__option-text">
								<strong>Pay Next Month Installment (<?php echo esc_html( $currency . number_format( (float) $sub['monthly_fee'] + $penalty, 0 ) ); ?>)</strong>
								<span class="rks-pay-form__option-desc">
									Pay for 1 month of subscription.
									<?php if ( $penalty > 0 ) : ?>
										<br><span style="color: var(--rks-red, #E63946); font-weight: 600;">(Includes late fee penalty of <?php echo esc_html( $currency . number_format( $penalty, 0 ) ); ?>)</span>
									<?php endif; ?>
								</span>
							</span>
						</label>
						<label class="rks-pay-form__option">
							<input type="radio" name="payment_type" value="full" data-amount="<?php echo esc_attr( (float) $full_amount + $penalty ); ?>">
							<span class="rks-pay-form__option-text">
								<strong>Pay All Remaining Months (<?php echo esc_html( $currency . number_format( (float) $full_amount + $penalty, 0 ) ); ?>)</strong>
								<span class="rks-pay-form__option-desc">
									Pay the complete balance of <?php echo esc_html( $remaining_months ); ?> months at once.
									<?php if ( $penalty > 0 ) : ?>
										<br><span style="color: var(--rks-red, #E63946); font-weight: 600;">(Includes late fee penalty of <?php echo esc_html( $currency . number_format( $penalty, 0 ) ); ?>)</span>
									<?php endif; ?>
								</span>
							</span>
						</label>
					</div>
				<?php else : ?>
					<input type="hidden" name="payment_type" value="installment" data-amount="<?php echo esc_attr( (float) $sub['monthly_fee'] + $penalty ); ?>">
					<div class="rks-pay-form__single-option">
						<p>You have <strong>1</strong> month remaining. Amount to pay: <strong><?php echo esc_html( $currency . number_format( (float) $sub['monthly_fee'] + $penalty, 0 ) ); ?></strong>
							<?php if ( $penalty > 0 ) : ?>
								<br><span style="color: var(--rks-red, #E63946); font-weight: 600; font-size: 13px;">Includes late fee penalty of <?php echo esc_html( $currency . number_format( $penalty, 0 ) ); ?></span>
							<?php endif; ?>
						</p>
					</div>
				<?php endif; ?>

				<h3 class="rks-pay-form__title" style="margin-top: 25px;">2. Select Payment Method</h3>
				<div class="rks-pay-form__methods">
					<label class="rks-pay-method-card">
						<input type="radio" name="method" value="upi" checked>
						<div class="rks-pay-method-info">
							<strong>UPI (Online Payment)</strong>
							<span>Scan QR & enter transaction ID</span>
						</div>
					</label>
					<label class="rks-pay-method-card">
						<input type="radio" name="method" value="offline">
						<div class="rks-pay-method-info">
							<strong>Offline Payment</strong>
							<span>Pay Cash at Institute / Branch Office</span>
						</div>
					</label>
				</div>

				<div class="rks-pay-details-fields">
					<h3 class="rks-pay-form__title" style="margin-top: 25px;">3. Enter Payment Reference & Screenshot</h3>
					
					<div class="rks-pay-form__row rks-field-utr">
						<label>
							<span>UTR / Transaction ID *</span>
							<input type="text" name="utr" required pattern="[A-Za-z0-9]{6,}" placeholder="Enter 12-digit UTR/Txn ID">
						</label>
					</div>

					<div class="rks-pay-form__row rks-field-screenshot">
						<label>
							<span>Payment Screenshot *</span>
							<input type="file" name="screenshot" accept="image/*,application/pdf" required>
							<small>JPG, PNG, WEBP or PDF. Max 5MB.</small>
						</label>
					</div>
				</div>

				<div class="rks-pay-offline-notice" style="display: none;">
					<h3 class="rks-pay-form__title" style="margin-top: 25px;">3. Confirm Offline Request</h3>
					<div class="rks-pay-form__single-option" style="background: rgba(44, 104, 123, 0.05); border-color: rgba(44, 104, 123, 0.15); color: var(--rks-teal-dark, #1f4e5e);">
						<p>Please submit this form to register your offline payment request. You must deposit <strong><?php echo esc_html( $currency ); ?><span class="rks-pay-offline-amount"><?php echo esc_html( number_format( (float) $sub['monthly_fee'] + $penalty, 0 ) ); ?></span></strong> at the institute counter to complete verification.</p>
					</div>
				</div>

				<div class="rks-pay-form__row rks-field-amount" style="margin-top: 20px;">
					<label>
						<span>Amount to Pay (<?php echo esc_html( $currency ); ?>)</span>
						<input type="number" name="amount" step="1" min="1" required readonly value="<?php echo esc_attr( (string) ( (float) $sub['monthly_fee'] + $penalty ) ); ?>" style="background:#f3f4f6; cursor:not-allowed;">
					</label>
				</div>

				<div class="rks-pay-form__actions">
					<a href="<?php echo esc_url( add_query_arg( 'view', 'courses', home_url( '/student-dashboard/' ) ) ); ?>" class="rks-btn rks-btn--ghost">Cancel</a>
					<button type="submit" class="rks-btn rks-btn--primary"><?php echo Icons::svg( 'check', 14 ); ?> Submit Payment</button>
				</div>
			</form>
		</div>

		<!-- Right / Scan QR Side -->
		<div class="rks-pay-qr-container">
			<div class="rks-pay-qr-card rks-qr-card-upi">
				<h3>3. Scan QR Code & Pay</h3>
				<?php if ( $qr_url ) : ?>
					<div class="rks-pay-form__qr">
						<img src="<?php echo esc_url( $qr_url ); ?>" alt="UPI QR">
						<?php if ( $upi_id ) : ?>
							<p class="rks-pay-upi-id"><strong>UPI ID:</strong> <code><?php echo esc_html( $upi_id ); ?></code></p>
						<?php endif; ?>
						<div class="rks-pay-amt-box">
							Amount: <span class="rks-pay-currency"><?php echo esc_html( $currency ); ?></span><strong class="rks-pay-qr-amount"><?php echo esc_html( number_format( (float) $sub['monthly_fee'] + $penalty, 0 ) ); ?></strong>
						</div>
						<p class="rks-pay-form__hint">Scan the QR code using any UPI app (PhonePe, GPay, Paytm, BHIM) and pay the exact amount shown above.</p>
					</div>
				<?php else: ?>
					<div class="rks-pay-no-qr">
						<p>No QR Code configured.</p>
						<?php if ( $upi_id ) : ?>
							<p class="rks-pay-upi-id"><strong>UPI ID:</strong> <code><?php echo esc_html( $upi_id ); ?></code></p>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>

			<div class="rks-pay-qr-card rks-qr-card-offline" style="display: none;">
				<h3>Offline Payment Mode</h3>
				<div class="rks-pay-no-qr" style="background: #fff; padding: 24px; border-color: rgba(44, 104, 123, 0.25);">
					<div style="color: var(--rks-teal, #2C687B); font-size: 40px; margin-bottom: 12px;">
						<?php echo Icons::svg( 'briefcase', 48 ); ?>
					</div>
					<h4 style="margin: 0 0 8px; color: var(--rks-teal-dark, #1f4e5e); font-size: 16px;">Pay At Branch Office</h4>
					<p style="font-size: 13px; line-height: 1.5; color: var(--rks-muted, #6b7280); margin: 0;">
						Kindly pay the amount of <strong><?php echo esc_html( $currency ); ?><span class="rks-pay-offline-amount-box"><?php echo esc_html( number_format( (float) $sub['monthly_fee'] + $penalty, 0 ) ); ?></span></strong> directly at the counter. Once paid, the branch administrator will verify and approve your request.
					</p>
				</div>
			</div>
		</div>
	</div>
</div>

<style>
.rks-back-link {
	display: inline-flex;
	align-items: center;
	gap: 6px;
	color: var(--rks-teal, #2C687B);
	font-weight: 500;
	text-decoration: none;
	font-size: 14px;
	transition: transform 0.2s ease;
}
.rks-back-link:hover {
	transform: translateX(-3px);
	color: var(--rks-teal-dark, #1f4e5e);
}
.rks-pay-panel {
	background: #fff;
	border-radius: 12px;
	box-shadow: 0 4px 15px rgba(0,0,0,0.05);
	padding: 24px;
}
.rks-pay-layout {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 40px;
}
@media (max-width: 768px) {
	.rks-pay-layout {
		grid-template-columns: 1fr;
		gap: 24px;
	}
	.rks-pay-qr-container {
		order: -1; /* show QR at the top on mobile */
	}
}
.rks-pay-form__title {
	font-family: var(--rks-font-heading, 'Lora', serif);
	color: var(--rks-teal, #2C687B);
	font-size: 18px;
	margin-bottom: 15px;
	border-bottom: 2px solid var(--rks-cream, #FBF3E9);
	padding-bottom: 8px;
}
.rks-pay-form__options {
	display: flex;
	flex-direction: column;
	gap: 12px;
}
.rks-pay-form__option {
	display: flex;
	align-items: flex-start;
	padding: 14px 16px;
	border: 1px solid #d1d5db;
	border-radius: 8px;
	background: #fff;
	cursor: pointer;
	transition: all 0.2s ease;
}
.rks-pay-form__option:hover {
	border-color: var(--rks-teal, #2C687B);
	background: rgba(44, 104, 123, 0.02);
}
.rks-pay-form__option input[type="radio"] {
	margin-right: 12px;
	margin-top: 3px;
	accent-color: var(--rks-teal, #2C687B);
	cursor: pointer;
	width: 18px;
	height: 18px;
}
.rks-pay-form__option-text {
	display: flex;
	flex-direction: column;
	font-size: 14px;
	color: #374151;
	line-height: 1.4;
}
.rks-pay-form__option-text strong {
	color: var(--rks-teal-dark, #1f4e5e);
}
.rks-pay-form__option-desc {
	font-size: 12px;
	color: var(--rks-muted, #6b7280);
	margin-top: 4px;
}
.rks-pay-form__option:has(input[type="radio"]:checked) {
	border-color: var(--rks-teal, #2C687B);
	background: rgba(44, 104, 123, 0.04);
	box-shadow: 0 0 0 1px var(--rks-teal, #2C687B);
}
.rks-pay-form__single-option {
	background: rgba(44, 104, 123, 0.05);
	padding: 12px 16px;
	border-radius: 8px;
	color: var(--rks-teal-dark, #1f4e5e);
	font-size: 14px;
	border: 1px solid rgba(44, 104, 123, 0.15);
}

/* Payment Methods Styles */
.rks-pay-form__methods {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 12px;
	margin-bottom: 20px;
}
.rks-pay-method-card {
	display: flex;
	align-items: center;
	padding: 12px 14px;
	border: 1px solid #d1d5db;
	border-radius: 8px;
	background: #fff;
	cursor: pointer;
	transition: all 0.2s ease;
}
.rks-pay-method-card:hover {
	border-color: var(--rks-teal, #2C687B);
	background: rgba(44, 104, 123, 0.02);
}
.rks-pay-method-card input[type="radio"] {
	margin-right: 10px;
	accent-color: var(--rks-teal, #2C687B);
	cursor: pointer;
}
.rks-pay-method-info {
	display: flex;
	flex-direction: column;
	font-size: 13px;
	color: #374151;
	line-height: 1.3;
}
.rks-pay-method-info strong {
	color: var(--rks-teal-dark, #1f4e5e);
}
.rks-pay-method-info span {
	font-size: 11px;
	color: var(--rks-muted, #6b7280);
}
.rks-pay-method-card:has(input[type="radio"]:checked) {
	border-color: var(--rks-teal, #2C687B);
	background: rgba(44, 104, 123, 0.04);
	box-shadow: 0 0 0 1px var(--rks-teal, #2C687B);
}

.rks-pay-form__row {
	margin-bottom: 16px;
}
.rks-pay-form__row label {
	display: block;
}
.rks-pay-form__row span {
	display: block;
	font-size: 13px;
	font-weight: 600;
	color: #374151;
	margin-bottom: 6px;
}
.rks-pay-form__row input[type="text"],
.rks-pay-form__row input[type="number"],
.rks-pay-form__row input[type="file"] {
	width: 100%;
	padding: 10px 12px;
	border: 1px solid #d1d5db;
	border-radius: 6px;
	font-size: 14px;
	box-sizing: border-box;
	transition: border-color 0.2s ease;
}
.rks-pay-form__row input[type="text"]:focus,
.rks-pay-form__row input[type="number"]:focus {
	border-color: var(--rks-teal, #2C687B);
	outline: none;
}
.rks-pay-form__row small {
	display: block;
	margin-top: 5px;
	font-size: 11px;
	color: var(--rks-muted, #6b7280);
}
.rks-pay-form__actions {
	display: flex;
	gap: 12px;
	justify-content: flex-end;
	margin-top: 24px;
}
.rks-pay-qr-card {
	background: var(--rks-cream, #FBF3E9);
	border: 1px solid rgba(44, 104, 123, 0.15);
	border-radius: 12px;
	padding: 24px;
	text-align: center;
}
.rks-pay-qr-card h3 {
	font-family: var(--rks-font-heading, 'Lora', serif);
	color: var(--rks-teal, #2C687B);
	margin-top: 0;
	margin-bottom: 20px;
	font-size: 16px;
}
.rks-pay-form__qr img {
	max-width: 220px;
	height: auto;
	border: 4px solid #fff;
	border-radius: 8px;
	box-shadow: 0 4px 10px rgba(0,0,0,0.05);
	margin-bottom: 12px;
}
.rks-pay-upi-id {
	margin: 8px 0;
	font-size: 13px;
}
.rks-pay-upi-id code {
	background: #fff;
	padding: 4px 8px;
	border: 1px solid #e5e7eb;
	border-radius: 4px;
	font-family: monospace;
	font-size: 13px;
	color: var(--rks-teal, #2C687B);
}
.rks-pay-amt-box {
	background: var(--rks-red, #E63946);
	color: #fff;
	padding: 8px 16px;
	border-radius: 6px;
	font-size: 16px;
	font-weight: 500;
	display: inline-block;
	margin: 10px auto;
}
.rks-pay-amt-box strong {
	font-size: 20px;
	font-weight: 700;
	margin-left: 2px;
}
.rks-pay-form__hint {
	font-size: 12px;
	color: var(--rks-muted, #6b7280);
	line-height: 1.5;
	margin-top: 15px;
	font-style: italic;
}
.rks-pay-no-qr {
	background: #fff;
	padding: 20px;
	border-radius: 8px;
	border: 1px dashed #d1d5db;
	color: var(--rks-muted, #6b7280);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
	var form = document.querySelector('.rks-pay-form');
	if ( ! form ) return;

	var qrAmtDisplay = document.querySelector('.rks-pay-qr-amount');
	var amtInput = form.querySelector('input[name="amount"]');
	var radios = form.querySelectorAll('input[name="payment_type"]');
	var methodRadios = form.querySelectorAll('input[name="method"]');
	
	// Sections to toggle
	var upiDetailsSection = form.querySelector('.rks-pay-details-fields');
	var offlineNoticeSection = form.querySelector('.rks-pay-offline-notice');
	var upiQRCard = document.querySelector('.rks-qr-card-upi');
	var offlineQRCard = document.querySelector('.rks-qr-card-offline');
	
	// Inputs to toggle requirement & disabled state
	var utrInput = form.querySelector('input[name="utr"]');
	var screenshotInput = form.querySelector('input[name="screenshot"]');
	
	var offlineAmountLabels = document.querySelectorAll('.rks-pay-offline-amount');
	var offlineAmountBoxLabel = document.querySelector('.rks-pay-offline-amount-box');

	function updatePaymentState() {
		// Get currently selected amount based on payment type radio button or hidden input
		var currentAmount = amtInput ? parseFloat(amtInput.value) : 0;
		var activeRadio = form.querySelector('input[name="payment_type"]:checked');
		if (!activeRadio) {
			activeRadio = form.querySelector('input[name="payment_type"][type="hidden"]');
		}
		if (activeRadio && activeRadio.dataset.amount) {
			currentAmount = parseFloat(activeRadio.dataset.amount);
		}

		var formattedAmount = Math.round(currentAmount).toLocaleString('en-IN');
		
		// Update amount inputs (keep enabled & readonly so it submits in POST request)
		if (amtInput) {
			amtInput.value = Math.round(currentAmount);
		}

		// Update displays
		if (qrAmtDisplay) {
			qrAmtDisplay.textContent = formattedAmount;
		}
		offlineAmountLabels.forEach(function(el) {
			el.textContent = formattedAmount;
		});
		if (offlineAmountBoxLabel) {
			offlineAmountBoxLabel.textContent = formattedAmount;
		}
	}

	radios.forEach(function (radio) {
		radio.addEventListener('change', updatePaymentState);
	});

	methodRadios.forEach(function (radio) {
		radio.addEventListener('change', function () {
			if (radio.checked) {
				if (radio.value === 'offline') {
					// Hide UPI fields, show offline notice
					if (upiDetailsSection) upiDetailsSection.style.display = 'none';
					if (offlineNoticeSection) offlineNoticeSection.style.display = 'block';
					if (upiQRCard) upiQRCard.style.display = 'none';
					if (offlineQRCard) offlineQRCard.style.display = 'block';
					
					// Disable UPI inputs and remove required
					if (utrInput) {
						utrInput.required = false;
						utrInput.disabled = true;
					}
					if (screenshotInput) {
						screenshotInput.required = false;
						screenshotInput.disabled = true;
					}
				} else {
					// Show UPI fields, hide offline notice
					if (upiDetailsSection) upiDetailsSection.style.display = 'block';
					if (offlineNoticeSection) offlineNoticeSection.style.display = 'none';
					if (upiQRCard) upiQRCard.style.display = 'block';
					if (offlineQRCard) offlineQRCard.style.display = 'none';
					
					// Enable UPI inputs and set required
					if (utrInput) {
						utrInput.required = true;
						utrInput.disabled = false;
					}
					if (screenshotInput) {
						screenshotInput.required = true;
						screenshotInput.disabled = false;
					}
				}
				updatePaymentState();
			}
		});
	});

	// Trigger initial setup
	updatePaymentState();
});
</script>
