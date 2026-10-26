<?php
/**
 * Admissions module — handles admission applications + approval workflow.
 *
 * @package RKSiksha\Core
 */

declare( strict_types=1 );

namespace RKSiksha\Core\Modules;

use RKSiksha\Core\Roles;
use RKSiksha\Core\Support\DB;
use RKSiksha\Core\Modules\Gateways\Gateway;
use RKSiksha\Core\Modules\Gateways\Razorpay;
use RKSiksha\Core\Modules\Gateways\Cashfree;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Admissions {

	public const NONCE         = 'rks_admission_submit';
	public const NONCE_PG      = 'rks_pg_create_order';
	public const STATUS_PAYMENT_REVIEW = 'payment_review';
	public const STATUS_APPROVED       = 'approved';
	public const STATUS_REJECTED       = 'rejected';

	private const ADMISSION_FEE    = 500.0;
	private const DOC_ALLOWED_MIME = [ 'image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'application/pdf' ];
	private const PHOTO_ALLOWED_MIME = [ 'image/jpeg', 'image/jpg', 'image/png', 'image/webp' ];
	private const DOC_MAX_BYTES    = 5242880; // 5MB

	public function register(): void {
		add_action( 'init',              [ $this, 'register_shortcode' ] );
		add_action( 'init',              [ $this, 'reset_existing_student_passwords_to_dob' ], 30 );
		add_action( 'template_redirect', [ $this, 'handle_submission' ], 5 );
		add_action( 'admin_post_rks_admission_document', [ $this, 'handle_admin_view_document' ] );
		$this->register_gateway_endpoints();
	}

	/**
	 * One-time migration: replace legacy random student passwords with the
	 * student's latest admission DOB in ddmmyyyy format.
	 */
	public function reset_existing_student_passwords_to_dob(): void {
		if ( get_option( 'rks_existing_student_dob_password_reset_v1', false ) ) {
			return;
		}

		global $wpdb;
		$user_ids = get_users( [
			'role'   => Roles::STUDENT,
			'fields' => 'ids',
		] );
		$reset_ids = [];
		$skipped_ids = [];

		foreach ( array_map( 'intval', $user_ids ) as $user_id ) {
			$dob = (string) $wpdb->get_var( $wpdb->prepare(
				'SELECT dob FROM ' . DB::table( 'admissions' ) . ' WHERE user_id = %d AND dob IS NOT NULL ORDER BY id DESC LIMIT 1',
				$user_id
			) );
			$password = self::password_from_dob( $dob );

			if ( '' === $password ) {
				$skipped_ids[] = $user_id;
				continue;
			}

			wp_set_password( $password, $user_id );
			update_user_meta( $user_id, 'rks_initial_password_format', 'dob_ddmmyyyy' );
			$reset_ids[] = $user_id;
		}

		update_option( 'rks_existing_student_dob_password_reset_v1', [
			'reset_ids'   => $reset_ids,
			'skipped_ids' => $skipped_ids,
			'completed_at'=> current_time( 'mysql' ),
		], false );
	}

	public function register_gateway_endpoints(): void {
		add_action( 'admin_post_rks_pg_create_order',        [ $this, 'handle_pg_create_order' ] );
		add_action( 'admin_post_nopriv_rks_pg_create_order', [ $this, 'handle_pg_create_order' ] );
	}

	/**
	 * Resolve a gateway instance by id. Returns null if unknown or disabled.
	 */
	private function gateway( string $id ): ?Gateway {
		$settings = (array) get_option( 'rks_settings', [] );
		$enabled  = (array) ( $settings['payment_gateways_enabled'] ?? [ 'upi' ] );
		if ( ! in_array( $id, $enabled, true ) ) {
			return null;
		}
		switch ( $id ) {
			case 'razorpay': return new Razorpay();
			case 'cashfree': return new Cashfree();
			default:         return null;
		}
	}

	private function enabled_gateways(): array {
		$settings = (array) get_option( 'rks_settings', [] );
		$enabled  = (array) ( $settings['payment_gateways_enabled'] ?? [ 'upi' ] );
		return array_values( array_intersect( [ 'razorpay', 'cashfree', 'upi', 'offline' ], $enabled ) );
	}

	public function register_shortcode(): void {
		add_shortcode( 'rks_admission_form', [ $this, 'shortcode_form' ] );
	}

	/* ================================================================== */
	/* SHORTCODE                                                           */
	/* ================================================================== */

	public function shortcode_form( $atts = [] ): string {
		global $wpdb;

		$settings  = (array) get_option( 'rks_settings', [] );
		$currency  = (string) ( $settings['currency_symbol'] ?? '₹' );
		$upi_id    = (string) ( $settings['upi_id'] ?? '' );
		$qr_id     = (int)    ( $settings['upi_qr_image_id'] ?? 0 );
		$qr_url    = $qr_id ? wp_get_attachment_image_url( $qr_id, 'medium' ) : '';
		$admission_fee = self::ADMISSION_FEE;

		if ( isset( $_GET['rks_print'] ) ) {
			return $this->render_printable_admission( sanitize_text_field( wp_unslash( (string) $_GET['rks_print'] ) ) );
		}

		$preselect = isset( $_GET['course_id'] ) ? (int) $_GET['course_id'] : 0;

		$courses = get_posts( [
			'post_type'      => Courses::CPT_COURSE,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		] );

		$branches = $wpdb->get_results( "SELECT id, name, type, city FROM " . DB::table( 'branches' ) . " WHERE status = 'active' ORDER BY type ASC, name ASC", ARRAY_A );

		$course_categories = [
			Courses::CAT_CERTIFICATE => 'Certificate Courses',
			Courses::CAT_DIPLOMA     => 'Diploma Courses',
		];
		$preselect_category = $preselect > 0 ? Courses::classify_admission_category( $preselect ) : '';

		$error      = isset( $_GET['rks_err'] )   ? sanitize_key( wp_unslash( (string) $_GET['rks_err'] ) )            : '';
		$msg        = isset( $_GET['rks_msg'] )   ? sanitize_key( wp_unslash( (string) $_GET['rks_msg'] ) )            : '';
		$ref        = isset( $_GET['rks_ref'] )   ? sanitize_text_field( wp_unslash( (string) $_GET['rks_ref'] ) )     : '';
		$creds_tok  = isset( $_GET['rks_creds'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['rks_creds'] ) )   : '';

		ob_start();
		?>
		<?php if ( 'submitted' === $msg && $ref ) :
			$creds = $creds_tok ? (array) get_transient( 'rks_adm_creds_' . $creds_tok ) : [];
			if ( $creds_tok && $creds ) {
				delete_transient( 'rks_adm_creds_' . $creds_tok );
			}
			$has_password   = ! empty( $creds['username'] ) && ! empty( $creds['password'] );
			$is_existing    = ( ( $creds['account_status'] ?? '' ) === 'existing' );
			$student_name   = (string) ( $creds['name']   ?? '' );
			$course_title   = (string) ( $creds['course'] ?? '' );
			$brand          = (string) ( $settings['site_brand'] ?? 'Rishikul Shiksha' );
			$print_url      = add_query_arg( 'rks_print', $ref, home_url( '/admission/' ) );
		?>
			<div class="rks-adm-success">
				<div class="rks-adm-card" id="rks-adm-card" data-brand="<?php echo esc_attr( $brand ); ?>">
					<div class="rks-adm-card__head">
						<strong><?php echo esc_html( $brand ); ?></strong>
						<span>Admission Confirmation</span>
					</div>
					<h2>Application Submitted!</h2>
					<dl class="rks-adm-card__meta">
						<dt>Reference</dt><dd><?php echo esc_html( $ref ); ?></dd>
						<?php if ( $student_name ) : ?>
							<dt>Name</dt><dd><?php echo esc_html( $student_name ); ?></dd>
						<?php endif; ?>
						<?php if ( $course_title ) : ?>
							<dt>Course</dt><dd><?php echo esc_html( $course_title ); ?></dd>
						<?php endif; ?>
						<?php if ( $has_password ) : ?>
							<dt>Username</dt><dd><code><?php echo esc_html( $creds['username'] ); ?></code></dd>
							<dt>Password</dt><dd><code><?php echo esc_html( $creds['password'] ); ?></code></dd>
						<?php elseif ( $is_existing && ! empty( $creds['username'] ) ) : ?>
							<dt>Username</dt><dd><code><?php echo esc_html( $creds['username'] ); ?></code></dd>
						<?php endif; ?>
					</dl>
					<p class="rks-adm-card__note">
						<?php if ( $has_password ) : ?>
							Save this image — these are your login credentials. Course access will activate after admin approval.
						<?php elseif ( $is_existing ) : ?>
							This email is already registered. Use your existing password to log in. Course access will activate after admin approval.
						<?php else : ?>
							Save this reference number. Course access will activate after admin approval.
						<?php endif; ?>
					</p>
				</div>
				<div class="rks-adm-success__actions">
					<button type="button" class="rks-btn rks-btn--primary" id="rks-adm-download">Download as Image</button>
					<a href="<?php echo esc_url( $print_url ); ?>" target="_blank" class="rks-btn rks-btn--primary">Print Admission Form</a>
					<?php if ( $is_existing && ! $has_password ) : ?>
						<a href="<?php echo esc_url( home_url( '/forgot-password/' ) ); ?>" class="rks-btn rks-btn--ghost">Forgot Password?</a>
					<?php endif; ?>
					<a href="<?php echo esc_url( home_url( '/student-dashboard/' ) ); ?>" class="rks-btn rks-btn--ghost">Go to Dashboard →</a>
				</div>
			</div>
			<script>
			(function () {
				var btn  = document.getElementById('rks-adm-download');
				var card = document.getElementById('rks-adm-card');
				if (!btn || !card) return;
				btn.addEventListener('click', function () {
					var W = 460, scale = 2;
					var pad = 18, labelCol = 95, rowGap = 6, rowH = 16;
					var headerH = 50;
					var noteFontSize = 11, noteLineH = 14, notePadX = 12, notePadY = 10;

					var rows = [];
					card.querySelectorAll('.rks-adm-card__meta dt').forEach(function (dt) {
						var dd = dt.nextElementSibling;
						if (dd) rows.push({ label: dt.textContent.trim(), value: dd.textContent.trim() });
					});

					var noteEl = card.querySelector('.rks-adm-card__note');
					var note   = noteEl ? noteEl.textContent.trim() : '';

					// Measurement pass — pre-compute wrapped note height
					var measureCv = document.createElement('canvas');
					var mctx = measureCv.getContext('2d');
					mctx.font = noteFontSize + 'px Poppins, Arial, sans-serif';
					var noteMaxW = W - pad * 2 - notePadX * 2;
					function wrapLines(text, maxW) {
						var words = text.split(' '), line = '', out = [];
						for (var i = 0; i < words.length; i++) {
							var test = line + words[i] + ' ';
							if (mctx.measureText(test).width > maxW && line.length) {
								out.push(line.trim()); line = words[i] + ' ';
							} else { line = test; }
						}
						if (line.trim()) out.push(line.trim());
						return out;
					}
					var noteLines = note ? wrapLines(note, noteMaxW) : [];
					var noteH     = note ? (noteLines.length * noteLineH + notePadY * 2) : 0;

					// Wrap value strings for tall rows (Course title can be long)
					mctx.font = 'bold 13px Poppins, Arial, sans-serif';
					var valMaxW = W - pad - labelCol - pad;
					rows.forEach(function (r) { r.lines = wrapLines(r.value, valMaxW); });
					var rowsH = 0;
					rows.forEach(function (r) {
						var lineCount = Math.max(1, r.lines.length);
						r.height = lineCount * rowH + rowGap;
						rowsH += r.height;
					});

					var titleBlockH = 34;
					var noteGap     = note ? 14 : 0;
					var bottomPad   = 18;
					var topAfterHdr = 18;
					var H = headerH + topAfterHdr + titleBlockH + rowsH + noteGap + noteH + bottomPad;

					var cv = document.createElement('canvas');
					cv.width = W * scale; cv.height = H * scale;
					var ctx = cv.getContext('2d');
					ctx.scale(scale, scale);

					// Cream background, white card area
					ctx.fillStyle = '#FBF3E9';
					ctx.fillRect(0, 0, W, H);
					ctx.fillStyle = '#ffffff';
					ctx.fillRect(0, headerH, W, H - headerH);

					// Teal header band
					ctx.fillStyle = '#2C687B';
					ctx.fillRect(0, 0, W, headerH);

					// Mint accent line under header
					ctx.fillStyle = '#8EC9C4';
					ctx.fillRect(0, headerH, W, 3);

					ctx.fillStyle = '#ffffff';
					ctx.textBaseline = 'middle';
					ctx.font = 'bold 16px Poppins, Arial, sans-serif';
					ctx.fillText(card.dataset.brand || 'Rishikul Shiksha', pad, headerH / 2 - 6);
					ctx.font = '10px Poppins, Arial, sans-serif';
					ctx.globalAlpha = 0.85;
					ctx.fillText('ADMISSION CONFIRMATION', pad, headerH / 2 + 10);
					ctx.globalAlpha = 1;
					ctx.textBaseline = 'alphabetic';

					// Title with dashed underline
					var titleY = headerH + topAfterHdr + 4;
					ctx.fillStyle = '#E63946';
					ctx.font = 'bold 16px Poppins, Arial, sans-serif';
					ctx.fillText('Application Submitted!', pad, titleY);
					ctx.strokeStyle = 'rgba(230, 57, 70, 0.3)';
					ctx.lineWidth = 1;
					ctx.setLineDash([3, 3]);
					ctx.beginPath();
					ctx.moveTo(pad, titleY + 10);
					ctx.lineTo(W - pad, titleY + 10);
					ctx.stroke();
					ctx.setLineDash([]);

					// Meta rows
					var y = headerH + topAfterHdr + titleBlockH + 4;
					rows.forEach(function (r) {
						ctx.fillStyle = '#6b7280';
						ctx.font = '600 9.5px Poppins, Arial, sans-serif';
						ctx.fillText(r.label.toUpperCase(), pad, y);

						ctx.fillStyle = '#111827';
						ctx.font = 'bold 13px Poppins, Arial, sans-serif';
						var ly = y;
						r.lines.forEach(function (ln) {
							ctx.fillText(ln, pad + labelCol, ly);
							ly += rowH;
						});
						y += r.height;
					});

					// Note box with red left border
					if (note) {
						y += noteGap - 4;
						ctx.fillStyle = '#FBF3E9';
						ctx.fillRect(pad, y, W - pad * 2, noteH);
						ctx.fillStyle = '#E63946';
						ctx.fillRect(pad, y, 3, noteH);
						ctx.font = noteFontSize + 'px Poppins, Arial, sans-serif';
						var ny = y + notePadY + noteFontSize;
						noteLines.forEach(function (ln) {
							ctx.fillText(ln, pad + notePadX, ny);
							ny += noteLineH;
						});
					}

					var link = document.createElement('a');
					link.download = 'admission-<?php echo esc_js( $ref ); ?>.png';
					link.href = cv.toDataURL('image/png');
					link.click();
				});
			})();
			</script>
			<?php return ob_get_clean(); ?>
		<?php endif; ?>

		<?php if ( $error ) : ?>
			<div class="rks-auth-alert rks-auth-alert--error">
				<?php echo esc_html( $this->error_message( $error ) ); ?>
			</div>
		<?php endif; ?>

		<form method="post" enctype="multipart/form-data" class="rks-adm-form" action="<?php echo esc_url( home_url( '/admission/' ) ); ?>" id="rks-admission-form">
			<?php wp_nonce_field( self::NONCE, '_rks_adm_nonce' ); ?>
			<input type="hidden" name="rks_action" value="admission_submit">

			<!-- Step indicator -->
			<ol class="rks-adm-steps">
				<li data-step="1" class="is-active"><span>1</span> Personal Details</li>
				<li data-step="2"><span>2</span> Course & Branch</li>
				<li data-step="3"><span>3</span> Payment</li>
			</ol>

			<!-- Step 1 -->
			<fieldset class="rks-adm-step is-active" data-step="1">
				<legend>Personal Details</legend>
				<div class="rks-form-grid">
					<div class="rks-form-row">
						<label>Full Name *</label>
						<input type="text" name="full_name" required>
					</div>
					<div class="rks-form-row">
						<label>Father's Name *</label>
						<input type="text" name="father_name" required>
					</div>
				</div>
				<div class="rks-form-row">
					<label>Parent's Mobile No. *</label>
					<input type="tel" name="parent_mobile" required pattern="[0-9+ ]{8,}" placeholder="+91 9999999999">
				</div>
				<div class="rks-form-row">
					<label>Student Mobile No. *</label>
					<input type="tel" name="phone" required pattern="[0-9+ ]{8,}" placeholder="+91 9999999999">
				</div>
				<div class="rks-form-row">
					<label>Email *</label>
					<input type="email" name="email" required>
				</div>
				<div class="rks-form-grid">
					<div class="rks-form-row">
						<label>Date of Birth *</label>
						<input type="date" name="dob" required>
					</div>
					<div class="rks-form-row">
						<label>Gender *</label>
						<select name="gender" required>
							<option value="">— Select —</option>
							<option value="male">Male</option>
							<option value="female">Female</option>
							<option value="other">Other</option>
						</select>
					</div>
				</div>
				<div class="rks-form-grid">
					<div class="rks-form-row">
						<label>Nationality *</label>
						<input type="text" name="nationality" required placeholder="Indian">
					</div>
					<div class="rks-form-row">
						<label>Religion *</label>
						<select name="religion" required>
							<option value="">— Select —</option>
							<option value="Hindu">Hindu</option>
							<option value="Muslim">Muslim</option>
							<option value="Sikh">Sikh</option>
							<option value="Christian">Christian</option>
							<option value="Buddhist">Buddhist</option>
							<option value="Jain">Jain</option>
							<option value="Other">Other</option>
						</select>
					</div>
				</div>
				<div class="rks-form-row">
					<label>Permanent Address *</label>
					<textarea name="address" rows="2" required></textarea>
				</div>
				<div class="rks-form-grid">
					<div class="rks-form-row">
						<label>Permanent District *</label>
						<input type="text" name="permanent_district" required>
					</div>
					<div class="rks-form-row">
						<label>Permanent State *</label>
						<input type="text" name="permanent_state" required>
					</div>
					<div class="rks-form-row">
						<label>Permanent Nation *</label>
						<input type="text" name="permanent_nation" required value="India">
					</div>
				</div>
				<div class="rks-form-row">
					<label class="rks-form-check">
						<input type="checkbox" id="rks-same-address" name="same_address" value="1">
						Present Address same as Permanent Address
					</label>
				</div>
				<div class="rks-form-row">
					<label>Present Address *</label>
					<textarea name="present_address" rows="2" required></textarea>
				</div>
				<div class="rks-form-grid">
					<div class="rks-form-row">
						<label>Present District *</label>
						<input type="text" name="present_district" required>
					</div>
					<div class="rks-form-row">
						<label>Present State *</label>
						<input type="text" name="present_state" required>
					</div>
					<div class="rks-form-row">
						<label>Present Nation *</label>
						<input type="text" name="present_nation" required value="India">
					</div>
				</div>
				<div class="rks-form-row">
					<label>Last Qualification *</label>
					<input type="text" name="qualification" required placeholder="e.g. 10th, 12th, B.Sc, B.Tech...">
				</div>
				<div class="rks-form-row">
					<label>Student Photo *</label>
					<input type="file" name="student_photo" accept="image/*" required>
					<small>Passport-size photo. JPG, PNG or WEBP. Max 5MB.</small>
				</div>
				<div class="rks-adm-actions">
					<button type="button" class="rks-btn rks-btn--primary" data-rks-next>Next: Course →</button>
				</div>
			</fieldset>

			<!-- Step 2 -->
			<fieldset class="rks-adm-step" data-step="2">
				<legend>Choose Course & Branch</legend>
				<div class="rks-form-row">
					<label>Branch *</label>
					<select name="branch_id" required id="rks-branch-select">
						<?php foreach ( $branches as $b ) : ?>
							<option value="<?php echo (int) $b['id']; ?>" <?php selected( ( $b['type'] === 'main' ), true ); ?>>
								<?php echo esc_html( $b['name'] . ( $b['city'] ? ' — ' . $b['city'] : '' ) ); ?>
								<?php echo $b['type'] === 'main' ? ' (HQ)' : ''; ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="rks-form-row">
					<label>Course Category *</label>
					<select name="course_category" required id="rks-course-category-select">
						<option value="">Select Category</option>
						<?php foreach ( $course_categories as $cat_slug => $cat_label ) : ?>
							<option value="<?php echo esc_attr( $cat_slug ); ?>" <?php selected( $preselect_category, $cat_slug ); ?>>
								<?php echo esc_html( $cat_label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<small>Select category first so required documents can be shown correctly.</small>
				</div>
				<div class="rks-form-row">
					<label>Course *</label>
					<select name="course_id" required id="rks-course-select">
						<option value="">— Select Course —</option>
						<?php foreach ( $courses as $c ) :
							$course_category = Courses::classify_admission_category( (int) $c->ID );
							$is_paid      = (bool) get_post_meta( $c->ID, Courses::M_IS_PAID, true );
							$price        = (float) get_post_meta( $c->ID, Courses::M_PRICE, true );
							$pay_mode     = (string) get_post_meta( $c->ID, Courses::M_PAYMENT_MODE, true ) ?: 'one_time';
							$monthly_fee  = (float) get_post_meta( $c->ID, Courses::M_MONTHLY_FEE, true );
							$total_months = (int) get_post_meta( $c->ID, Courses::M_TOTAL_MONTHS, true );
							$seat_limit   = (int) get_post_meta( $c->ID, Courses::M_SEAT_LIMIT, true );
							$enrolled     = (int) get_post_meta( $c->ID, Courses::M_ENROLLED_COUNT, true );
							$is_full      = ( $seat_limit > 0 && $enrolled >= $seat_limit );

							if ( ! $is_paid ) {
								$label_price = 'Free';
							} elseif ( $pay_mode === 'monthly' && $monthly_fee > 0 && $total_months > 0 ) {
								$label_price = $currency . number_format( $monthly_fee, 0 ) . '/month × ' . $total_months . ' months';
							} else {
								$label_price = $currency . number_format( $price, 0 );
							}
							if ( $is_full ) $label_price .= ' — Seats Full';

							// Server price is authoritative; for monthly, charge first month at submission.
							$client_price = $is_paid
								? ( $pay_mode === 'monthly' ? $monthly_fee : $price )
								: 0;
						?>
							<option value="<?php echo (int) $c->ID; ?>"
									data-price="<?php echo esc_attr( (string) $client_price ); ?>"
									data-paid="<?php echo $is_paid ? '1' : '0'; ?>"
									data-pay-mode="<?php echo esc_attr( $pay_mode ); ?>"
									data-monthly-fee="<?php echo esc_attr( (string) $monthly_fee ); ?>"
									data-total-months="<?php echo esc_attr( (string) $total_months ); ?>"
									data-total-price="<?php echo esc_attr( (string) $price ); ?>"
									data-course-category="<?php echo esc_attr( $course_category ); ?>"
									data-full="<?php echo $is_full ? '1' : '0'; ?>"
									<?php disabled( $is_full ); ?>
									<?php selected( $preselect, $c->ID ); ?>>
								<?php echo esc_html( $c->post_title . ' — ' . $label_price ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="rks-form-hint" id="rks-pay-plan-hint" style="display:none;"></p>
				</div>
				<div class="rks-adm-docs" id="rks-adm-docs" style="display:none;">
					<div class="rks-adm-docs__head">
						<strong>Required Documents</strong>
						<small id="rks-adm-docs-hint">Select course category to see document requirements.</small>
					</div>
					<div class="rks-form-row rks-adm-doc-field" data-doc-field="aadhar">
						<label>Aadhaar Card Front *</label>
						<input type="file" name="aadhar_front" accept="image/*,application/pdf">
						<small>Upload Aadhaar card front image or PDF. Max 5MB.</small>
					</div>
					<div class="rks-form-row rks-adm-doc-field" data-doc-field="aadhar_back">
						<label>Aadhaar Card Back *</label>
						<input type="file" name="aadhar_back" accept="image/*,application/pdf">
						<small>Upload Aadhaar card back image or PDF. Max 5MB.</small>
					</div>
					<div class="rks-form-row rks-adm-doc-field" data-doc-field="marksheet">
						<label>10th / 12th Marksheet *</label>
						<input type="file" name="marksheet" accept="image/*,application/pdf">
						<small>Required for diploma courses only. Upload 10th or 12th marksheet image/PDF.</small>
					</div>
				</div>
				<div class="rks-adm-actions">
					<button type="button" class="rks-btn rks-btn--ghost" data-rks-prev>← Back</button>
					<button type="button" class="rks-btn rks-btn--primary" data-rks-next>Next: Payment →</button>
				</div>
			</fieldset>

			<!-- Step 3 -->
			<fieldset class="rks-adm-step" data-step="3">
				<legend>Payment</legend>

				<div class="rks-adm-fee">
					<div class="rks-adm-fee__row">
						<span>Course Fee</span>
						<strong id="rks-course-fee-display"><?php echo esc_html( $currency ); ?>0</strong>
					</div>
					<div class="rks-adm-fee__row">
						<span>Admission Fee</span>
						<strong id="rks-admission-fee-display" data-amount="<?php echo esc_attr( (string) $admission_fee ); ?>"><?php echo esc_html( $currency . number_format( $admission_fee, 0 ) ); ?></strong>
					</div>
					<div class="rks-adm-fee__row rks-adm-fee__row--total">
						<span>Total Payable</span>
						<strong id="rks-fee-display"><?php echo esc_html( $currency . number_format( $admission_fee, 0 ) ); ?></strong>
					</div>
				</div>

				<?php
				$enabled_gw = $this->enabled_gateways();
				if ( empty( $enabled_gw ) ) { $enabled_gw = [ 'upi' ]; }
				$gw_default = in_array( 'razorpay', $enabled_gw, true )
					? 'razorpay'
					: ( in_array( 'cashfree', $enabled_gw, true ) ? 'cashfree' : 'upi' );
				$gw_labels  = [
					'razorpay' => 'Razorpay',
					'cashfree' => 'Cashfree',
					'upi'      => 'UPI QR (verify manually)',
					'offline'  => 'Offline Payment',
				];
				?>
				<input type="hidden" name="_gateway" id="rks-gateway-input" value="<?php echo esc_attr( $gw_default ); ?>">
				<input type="hidden" name="razorpay_payment_id" id="rks-rzp-payment-id" value="">
				<input type="hidden" name="razorpay_order_id"   id="rks-rzp-order-id"   value="">
				<input type="hidden" name="razorpay_signature"  id="rks-rzp-signature"  value="">
				<input type="hidden" name="cf_order_id"         id="rks-cf-order-id"    value="">

				<?php if ( count( $enabled_gw ) > 1 ) : ?>
					<div id="rks-paid-block-gw" style="display:none;" class="rks-adm-gw-tabs">
						<label style="display:block;font-weight:600;margin-bottom:6px;">Payment Method</label>
						<?php foreach ( $enabled_gw as $gw_id ) : ?>
							<label class="rks-adm-gw-tab" style="display:inline-block;margin-right:14px;">
								<input type="radio" name="_gateway_choice" value="<?php echo esc_attr( $gw_id ); ?>" <?php checked( $gw_id, $gw_default ); ?>>
								<?php echo esc_html( $gw_labels[ $gw_id ] ?? $gw_id ); ?>
							</label>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<div id="rks-paid-block" style="display:none;">
					<div class="rks-adm-pay-grid" id="rks-gw-upi-block" <?php echo in_array( 'upi', $enabled_gw, true ) ? '' : 'style="display:none;"'; ?>>
						<div class="rks-adm-qr">
							<?php if ( $qr_url ) : ?>
								<div class="rks-adm-qr__amount">
									<small>Pay exactly</small>
									<strong id="rks-qr-amount-display"><?php echo esc_html( $currency ); ?>0</strong>
								</div>
								<img src="<?php echo esc_url( $qr_url ); ?>" alt="UPI QR">
								<?php if ( $upi_id ) : ?>
									<p><strong>UPI ID:</strong> <code><?php echo esc_html( $upi_id ); ?></code></p>
								<?php endif; ?>
								<p>Scan QR with any UPI app, send the exact amount, then upload screenshot below.</p>
							<?php else : ?>
								<div class="rks-adm-qr-placeholder">
									<p><strong>UPI QR not configured.</strong></p>
									<p>Admin: please upload a UPI QR in <em>Settings → UPI Payment</em>.</p>
								</div>
							<?php endif; ?>
						</div>
						<div class="rks-adm-pay-form">
							<div class="rks-form-row">
								<label>UTR / Transaction ID *</label>
								<input type="text" name="utr" pattern="[A-Za-z0-9]{6,}" placeholder="123456789012">
								<small>12-digit reference number from your UPI app.</small>
							</div>
							<div class="rks-form-row">
								<label>Amount Paid (<?php echo esc_html( $currency ); ?>) *</label>
								<input type="number" name="amount_paid" id="rks-amount-paid" step="1" min="0" readonly required style="background:#f9fafb;cursor:not-allowed;">
								<small class="rks-form-hint">Amount is set automatically based on the course you selected. Pay the exact amount shown.</small>
							</div>
							<div class="rks-form-row">
								<label>Payment Screenshot *</label>
								<input type="file" name="screenshot" accept="image/*,application/pdf">
								<small>JPG, PNG, WEBP or PDF. Max 5MB.</small>
							</div>
						</div>
					</div>

					<div id="rks-gw-online-block" style="display:none;padding:18px;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc;">
						<p style="margin-top:0;">You'll be redirected to a secure payment page. Payment is auto-verified.</p>
						<div id="rks-gw-error" style="display:none;color:#b91c1c;margin:8px 0;"></div>
						<button type="button" class="rks-btn rks-btn--primary" id="rks-gw-pay-btn">Pay Now</button>
					</div>

					<div id="rks-gw-offline-block" style="display:none;padding:18px;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc;">
						<p style="margin-top:0;">You have selected offline payment. Please submit your application. You can pay the admission and course fees directly at the study centre or branch, and the administrator will activate your account after verifying your payment.</p>
					</div>
				</div>

				<div id="rks-free-block" style="display:none;">
					<p>No payment is due. Click submit to enroll.</p>
				</div>

				<div class="rks-form-row">
					<label class="rks-form-check">
						<input type="checkbox" name="agree" value="1" required>
						I confirm the details are correct and accept the program terms.
					</label>
				</div>

				<div class="rks-adm-terms">
					<label class="rks-adm-terms__label">
						<input type="checkbox" name="rks_terms_accepted" value="1" required id="rks-terms-accepted">
						<span class="rks-adm-terms__text">
							I have read and agree to the
							<a href="<?php echo esc_url( home_url( '/terms/' ) ); ?>" data-rks-terms-open aria-haspopup="dialog">Student Terms &amp; Conditions</a>
							of Rishikul Shiksha.
						</span>
					</label>
					<p class="rks-adm-terms__hint">Please review our policies on admission, fees, conduct and certification before submitting your application.</p>
				</div>

				<div class="rks-adm-actions">
					<button type="button" class="rks-btn rks-btn--ghost" data-rks-prev>← Back</button>
					<button type="submit" class="rks-btn rks-btn--primary">Submit Application</button>
				</div>
			</fieldset>
		</form>

		<div class="rks-adm-modal" id="rks-adm-terms-modal" aria-hidden="true">
			<div class="rks-adm-modal__overlay" data-rks-terms-close></div>
			<div class="rks-adm-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="rks-adm-terms-title">
				<button type="button" class="rks-adm-modal__close" data-rks-terms-close aria-label="Close terms popup">&times;</button>
				<header class="rks-adm-modal__head">
					<span>Student Agreement</span>
					<h2 id="rks-adm-terms-title">Student Terms &amp; Conditions</h2>
					<p>Please read these terms before submitting your admission application.</p>
				</header>
				<div class="rks-adm-modal__body" data-rks-terms-content data-terms-url="<?php echo esc_url( home_url( '/terms/' ) ); ?>">
					<p class="rks-adm-modal__loading">Loading terms...</p>
				</div>
				<footer class="rks-adm-modal__foot">
					<button type="button" class="rks-btn rks-btn--primary" data-rks-terms-close>I Understand</button>
				</footer>
			</div>
		</div>

		<script>
		(function () {
			var form = document.getElementById('rks-admission-form');
			if (!form) return;

			// Address syncing
			var sameAddressChk = form.querySelector('#rks-same-address');
			var permAddress    = form.querySelector('textarea[name="address"]');
			var permDistrict   = form.querySelector('input[name="permanent_district"]');
			var permState      = form.querySelector('input[name="permanent_state"]');
			var permNation     = form.querySelector('input[name="permanent_nation"]');

			var presAddress    = form.querySelector('textarea[name="present_address"]');
			var presDistrict   = form.querySelector('input[name="present_district"]');
			var presState      = form.querySelector('input[name="present_state"]');
			var presNation     = form.querySelector('input[name="present_nation"]');

			function syncAddresses() {
				if (sameAddressChk.checked) {
					presAddress.value  = permAddress.value;
					presDistrict.value = permDistrict.value;
					presState.value    = permState.value;
					presNation.value   = permNation.value;
				}
			}

			if (sameAddressChk) {
				sameAddressChk.addEventListener('change', syncAddresses);
				[permAddress, permDistrict, permState, permNation].forEach(function(el) {
					if (el) el.addEventListener('input', syncAddresses);
				});
			}

			var steps = form.querySelectorAll('.rks-adm-step');
			var indicators = form.querySelectorAll('.rks-adm-steps li');
			var current = 1;
			var termsModal = document.getElementById('rks-adm-terms-modal');
			var termsOpen = form.querySelector('[data-rks-terms-open]');
			var termsContent = termsModal ? termsModal.querySelector('[data-rks-terms-content]') : null;
			var termsLoaded = false;
			var lastFocused = null;

			function loadTermsContent() {
				if (!termsContent || termsLoaded) return;
				var termsUrl = termsContent.getAttribute('data-terms-url');
				if (!termsUrl || !window.fetch || !window.DOMParser) {
					termsLoaded = true;
					termsContent.innerHTML = '<p>Terms content could not be loaded here. Please contact the office before submitting if you need clarification.</p>';
					return;
				}

				fetch(termsUrl, { credentials: 'same-origin' })
					.then(function (response) {
						if (!response.ok) throw new Error('Could not load terms');
						return response.text();
					})
					.then(function (html) {
						var doc = new DOMParser().parseFromString(html, 'text/html');
						var source = doc.querySelector('.rks-policy__content') || doc.querySelector('main') || doc.body;
						if (!source) throw new Error('Terms content missing');

						source.querySelectorAll('script, style, iframe, object, embed').forEach(function (node) {
							node.remove();
						});

						termsContent.innerHTML = source.innerHTML;
						termsLoaded = true;
					})
					.catch(function () {
						termsLoaded = true;
						termsContent.innerHTML = '<p>Terms content could not be loaded in the popup right now. Please refresh this page or contact the office before submitting if you need clarification.</p>';
					});
			}

			function openTermsModal() {
				if (!termsModal) return;
				lastFocused = document.activeElement;
				termsModal.setAttribute('aria-hidden', 'false');
				document.body.classList.add('rks-adm-modal-open');
				loadTermsContent();
				var closeBtn = termsModal.querySelector('.rks-adm-modal__close');
				if (closeBtn) closeBtn.focus();
			}

			function closeTermsModal() {
				if (!termsModal) return;
				termsModal.setAttribute('aria-hidden', 'true');
				document.body.classList.remove('rks-adm-modal-open');
				if (lastFocused && typeof lastFocused.focus === 'function') {
					lastFocused.focus();
				}
			}

			if (termsOpen && termsModal) {
				termsOpen.addEventListener('click', function (event) {
					event.preventDefault();
					event.stopPropagation();
					openTermsModal();
				});

				termsModal.querySelectorAll('[data-rks-terms-close]').forEach(function (btn) {
					btn.addEventListener('click', closeTermsModal);
				});

				document.addEventListener('keydown', function (event) {
					if (event.key === 'Escape' && termsModal.getAttribute('aria-hidden') === 'false') {
						closeTermsModal();
					}
				});
			}

			function show(n) {
				current = n;
				steps.forEach(function (s) {
					s.classList.toggle('is-active', parseInt(s.dataset.step) === n);
				});
				indicators.forEach(function (i) {
					var x = parseInt(i.dataset.step);
					i.classList.toggle('is-active', x === n);
					i.classList.toggle('is-done',  x <  n);
				});
				window.scrollTo({ top: form.offsetTop - 80, behavior: 'smooth' });
			}

			form.querySelectorAll('[data-rks-next]').forEach(function (btn) {
				btn.addEventListener('click', function () {
					var step = btn.closest('.rks-adm-step');
					var inputs = step.querySelectorAll('input[required], select[required], textarea[required]');
					for (var j = 0; j < inputs.length; j++) {
						if (!inputs[j].checkValidity()) { inputs[j].reportValidity(); return; }
					}
					show(current + 1);
				});
			});
			form.querySelectorAll('[data-rks-prev]').forEach(function (btn) {
				btn.addEventListener('click', function () { show(current - 1); });
			});

			var categorySel = form.querySelector('#rks-course-category-select');
			var courseSel = form.querySelector('#rks-course-select');
			var allCourseOptions = courseSel ? Array.prototype.slice.call(courseSel.options) : [];
			var feeDisp   = form.querySelector('#rks-fee-display');
			var courseFeeDisp = form.querySelector('#rks-course-fee-display');
			var admissionFeeDisp = form.querySelector('#rks-admission-fee-display');
			var admissionFee = admissionFeeDisp ? parseFloat(admissionFeeDisp.dataset.amount || '0') : 0;
			var paidBlk   = form.querySelector('#rks-paid-block');
			var freeBlk   = form.querySelector('#rks-free-block');
			var sym       = "<?php echo esc_js( $currency ); ?>";

			var gwInput   = form.querySelector('#rks-gateway-input');
			var gwTabsWrap= form.querySelector('#rks-paid-block-gw');
			var gwTabs    = form.querySelectorAll('input[name="_gateway_choice"]');
			var upiBlk    = form.querySelector('#rks-gw-upi-block');
			var onlineBlk = form.querySelector('#rks-gw-online-block');
			var offlineBlk = form.querySelector('#rks-gw-offline-block');
			var gwError   = form.querySelector('#rks-gw-error');
			var payBtn    = form.querySelector('#rks-gw-pay-btn');
			var enabledGw = <?php echo wp_json_encode( $enabled_gw ); ?>;
			var pgNonce   = "<?php echo esc_js( wp_create_nonce( self::NONCE_PG ) ); ?>";
			var ajaxUrl   = "<?php echo esc_js( admin_url( 'admin-post.php' ) ); ?>";

			function setRequiredManualFields(on) {
				form.querySelector('input[name="utr"]').required = on;
				form.querySelector('input[name="amount_paid"]').required = on;
				form.querySelector('input[name="screenshot"]').required = on;
			}

			function currentGateway() {
				return gwInput.value || 'upi';
			}

			function updateGatewayView(paid) {
				if (!paid) {
					if (upiBlk)    upiBlk.style.display    = 'none';
					if (onlineBlk) onlineBlk.style.display = 'none';
					if (offlineBlk) offlineBlk.style.display = 'none';
					if (gwTabsWrap) gwTabsWrap.style.display = 'none';
					setRequiredManualFields(false);
					return;
				}
				if (gwTabsWrap) gwTabsWrap.style.display = 'block';
				var gw = currentGateway();
				if (gw === 'upi') {
					if (upiBlk)    upiBlk.style.display    = '';
					if (onlineBlk) onlineBlk.style.display = 'none';
					if (offlineBlk) offlineBlk.style.display = 'none';
					setRequiredManualFields(true);
				} else if (gw === 'offline') {
					if (upiBlk)    upiBlk.style.display    = 'none';
					if (onlineBlk) onlineBlk.style.display = 'none';
					if (offlineBlk) offlineBlk.style.display = '';
					setRequiredManualFields(false);
				} else {
					if (upiBlk)    upiBlk.style.display    = 'none';
					if (onlineBlk) onlineBlk.style.display = '';
					if (offlineBlk) offlineBlk.style.display = 'none';
					setRequiredManualFields(false);
				}
			}

			function updateDocumentRequirements() {
				var category = categorySel ? categorySel.value : '';
				var docsBlock = form.querySelector('#rks-adm-docs');
				var docsHint = form.querySelector('#rks-adm-docs-hint');
				var aadharFrontInput = form.querySelector('input[name="aadhar_front"]');
				var aadharBackInput = form.querySelector('input[name="aadhar_back"]');
				var marksheetInput = form.querySelector('input[name="marksheet"]');
				var marksheetField = form.querySelector('[data-doc-field="marksheet"]');

				if (!docsBlock || !aadharFrontInput || !aadharBackInput || !marksheetInput) return;

				var needsDocs = category === '<?php echo esc_js( Courses::CAT_CERTIFICATE ); ?>' || category === '<?php echo esc_js( Courses::CAT_DIPLOMA ); ?>';
				docsBlock.style.display = needsDocs ? 'block' : 'none';
				aadharFrontInput.required = needsDocs;
				aadharBackInput.required = needsDocs;
				marksheetInput.required = category === '<?php echo esc_js( Courses::CAT_DIPLOMA ); ?>';

				if (marksheetField) {
					marksheetField.style.display = marksheetInput.required ? 'block' : 'none';
				}
				if (!needsDocs) {
					aadharFrontInput.value = '';
					aadharBackInput.value = '';
					marksheetInput.value = '';
				} else if (!marksheetInput.required) {
					marksheetInput.value = '';
				}
				if (docsHint) {
					docsHint.textContent = category === '<?php echo esc_js( Courses::CAT_DIPLOMA ); ?>'
						? 'Diploma courses require Aadhaar card (front & back) and 10th/12th marksheet.'
						: (needsDocs ? 'Certificate courses require Aadhaar card (front & back) only.' : 'Select course category to see document requirements.');
				}
			}

			function syncCourseOptions(resetSelection) {
				if (!categorySel || !courseSel) return;
				var category = categorySel.value;
				var selected = courseSel.options[courseSel.selectedIndex];

				allCourseOptions.forEach(function (opt) {
					if (!opt.value) {
						opt.hidden = false;
						opt.disabled = false;
						return;
					}

					var matches = category && opt.dataset.courseCategory === category;
					opt.hidden = !matches;
					opt.disabled = !matches || opt.dataset.full === '1';
				});

				if (resetSelection || (selected && selected.value && selected.dataset.courseCategory !== category)) {
					courseSel.value = '';
				}
			}

			var planHint = document.getElementById('rks-pay-plan-hint');
			function updatePriceLegacy() {
				if (!courseSel.value) { if (planHint) planHint.style.display = 'none'; return; }
				var opt      = courseSel.options[courseSel.selectedIndex];
				var price    = parseFloat(opt.dataset.price || '0'); // first-installment OR full price
				var paid     = opt.dataset.paid === '1';
				var payMode  = opt.dataset.payMode || 'one_time';
				var monthly  = parseFloat(opt.dataset.monthlyFee || '0');
				var months   = parseInt(opt.dataset.totalMonths || '0', 10);
				var total    = parseFloat(opt.dataset.totalPrice || '0');

				if (!paid) {
					feeDisp.textContent = sym + '0 (Free)';
				} else if (payMode === 'monthly' && monthly > 0 && months > 0) {
					feeDisp.textContent = sym + monthly.toLocaleString('en-IN') + ' (first month)';
				} else {
					feeDisp.textContent = sym + price.toLocaleString('en-IN');
				}

				var amtField = form.querySelector('#rks-amount-paid');
				if (amtField) {
					if (!paid) {
						amtField.value = '';
						amtField.required = false;
					} else if (payMode === 'monthly' && monthly > 0 && months > 0) {
						amtField.value = monthly;
						amtField.required = true;
					} else {
						amtField.value = price;
						amtField.required = true;
					}
				}

				var qrAmtDisplay = form.querySelector('#rks-qr-amount-display');
				if (qrAmtDisplay) {
					var amt = (!paid) ? 0 : (payMode === 'monthly' && monthly > 0 ? monthly : price);
					qrAmtDisplay.textContent = sym + amt.toLocaleString('en-IN');
				}

				if (planHint) {
					if (paid && payMode === 'monthly' && monthly > 0 && months > 0) {
						planHint.innerHTML = 'Monthly plan: <strong>' + sym + monthly.toLocaleString('en-IN') + '</strong> × ' + months + ' months = <strong>' + sym + (monthly * months).toLocaleString('en-IN') + '</strong> total. Pay first month now, rest from your dashboard.';
						planHint.style.display = 'block';
					} else if (paid && total > 0) {
						planHint.innerHTML = 'One-time payment: <strong>' + sym + total.toLocaleString('en-IN') + '</strong>';
						planHint.style.display = 'block';
					} else {
						planHint.style.display = 'none';
					}
				}

				paidBlk.style.display = paid ? 'block' : 'none';
				freeBlk.style.display = paid ? 'none'  : 'block';
				updateGatewayView(paid);
			}
			function updatePrice() {
				if (!courseSel.value) {
					if (planHint) planHint.style.display = 'none';
					if (courseFeeDisp) courseFeeDisp.textContent = sym + '0';
					if (feeDisp) feeDisp.textContent = sym + admissionFee.toLocaleString('en-IN');
					paidBlk.style.display = admissionFee > 0 ? 'block' : 'none';
					freeBlk.style.display = admissionFee > 0 ? 'none' : 'block';
					updateGatewayView(admissionFee > 0);
					return;
				}

				var opt        = courseSel.options[courseSel.selectedIndex];
				var price      = parseFloat(opt.dataset.price || '0');
				var coursePaid = opt.dataset.paid === '1';
				var payMode    = opt.dataset.payMode || 'one_time';
				var monthly    = parseFloat(opt.dataset.monthlyFee || '0');
				var months     = parseInt(opt.dataset.totalMonths || '0', 10);
				var total      = parseFloat(opt.dataset.totalPrice || '0');
				var courseDue  = 0;

				if (!coursePaid) {
					courseDue = 0;
					if (courseFeeDisp) courseFeeDisp.textContent = sym + '0 (Free)';
				} else if (payMode === 'monthly' && monthly > 0 && months > 0) {
					courseDue = monthly;
					if (courseFeeDisp) courseFeeDisp.textContent = sym + monthly.toLocaleString('en-IN') + ' (first month)';
				} else {
					courseDue = price;
					if (courseFeeDisp) courseFeeDisp.textContent = sym + price.toLocaleString('en-IN');
				}

				var payable = courseDue + admissionFee;
				if (feeDisp) feeDisp.textContent = sym + payable.toLocaleString('en-IN');

				var amtField = form.querySelector('#rks-amount-paid');
				if (amtField) {
					if (payable <= 0) {
						amtField.value = '';
						amtField.required = false;
					} else {
						amtField.value = payable;
						amtField.required = true;
					}
				}

				var qrAmtDisplay = form.querySelector('#rks-qr-amount-display');
				if (qrAmtDisplay) {
					qrAmtDisplay.textContent = sym + payable.toLocaleString('en-IN');
				}

				if (planHint) {
					if (coursePaid && payMode === 'monthly' && monthly > 0 && months > 0) {
						planHint.innerHTML = 'Monthly plan: course first month <strong>' + sym + monthly.toLocaleString('en-IN') + '</strong> + admission fee <strong>' + sym + admissionFee.toLocaleString('en-IN') + '</strong> = <strong>' + sym + payable.toLocaleString('en-IN') + '</strong> now. Course total: <strong>' + sym + (monthly * months).toLocaleString('en-IN') + '</strong>.';
						planHint.style.display = 'block';
					} else if (coursePaid && total > 0) {
						planHint.innerHTML = 'One-time course fee: <strong>' + sym + total.toLocaleString('en-IN') + '</strong> + admission fee <strong>' + sym + admissionFee.toLocaleString('en-IN') + '</strong>.';
						planHint.style.display = 'block';
					} else if (admissionFee > 0) {
						planHint.innerHTML = 'Course fee is free. Admission fee payable now: <strong>' + sym + admissionFee.toLocaleString('en-IN') + '</strong>.';
						planHint.style.display = 'block';
					} else {
						planHint.style.display = 'none';
					}
				}

				paidBlk.style.display = payable > 0 ? 'block' : 'none';
				freeBlk.style.display = payable > 0 ? 'none' : 'block';
				updateGatewayView(payable > 0);
			}

			if (categorySel) {
				categorySel.addEventListener('change', function () {
					syncCourseOptions(true);
					updateDocumentRequirements();
					updatePrice();
				});
			}
			courseSel.addEventListener('change', updatePrice);

			gwTabs.forEach(function (r) {
				r.addEventListener('change', function () {
					if (r.checked) {
						gwInput.value = r.value;
						updateGatewayView(true);
					}
				});
			});

			function currentAmount() {
				if (!courseSel.value) return admissionFee;
				var opt = courseSel.options[courseSel.selectedIndex];
				var courseDue = opt.dataset.paid === '1' ? parseFloat(opt.dataset.price || '0') : 0;
				return courseDue + admissionFee;
			}

			function showError(msg) {
				if (!gwError) return;
				gwError.textContent = msg || 'Something went wrong.';
				gwError.style.display = 'block';
			}
			function clearError() { if (gwError) { gwError.style.display = 'none'; gwError.textContent = ''; } }

			function loadScript(src) {
				return new Promise(function (resolve, reject) {
					if (document.querySelector('script[data-rks-src="' + src + '"]')) return resolve();
					var s = document.createElement('script');
					s.src = src; s.async = true;
					s.setAttribute('data-rks-src', src);
					s.onload = function () { resolve(); };
					s.onerror = function () { reject(new Error('Failed to load ' + src)); };
					document.head.appendChild(s);
				});
			}

			function createGatewayOrder() {
				var fd = new FormData();
				fd.append('action', 'rks_pg_create_order');
				fd.append('_nonce', pgNonce);
				fd.append('gateway', currentGateway());
				fd.append('amount', String(currentAmount()));
				fd.append('course_id', form.querySelector('select[name="course_id"]').value || '');
				fd.append('branch_id', form.querySelector('select[name="branch_id"]').value || '');
				fd.append('full_name', form.querySelector('input[name="full_name"]').value || '');
				fd.append('email',     form.querySelector('input[name="email"]').value || '');
				fd.append('phone',     form.querySelector('input[name="phone"]').value || '');
				return fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd })
					.then(function (r) { return r.json(); });
			}

			function submitFormNow() {
				// Bypass our front-end gateway-pay button: submit the form normally.
				var marker = document.createElement('input');
				marker.type = 'hidden'; marker.name = '_rks_pg_submitted'; marker.value = '1';
				form.appendChild(marker);
				form.submit();
			}

			function payRazorpay(amount) {
				return createGatewayOrder().then(function (resp) {
					if (!resp || !resp.ok) { throw new Error((resp && resp.error) || 'Could not create order'); }
					return loadScript('https://checkout.razorpay.com/v1/checkout.js').then(function () {
						var gd = resp.gateway_data || {};
						var opts = {
							key:      gd.key_id,
							amount:   gd.amount,
							currency: gd.currency || 'INR',
							name:     <?php echo wp_json_encode( (string) ( $settings['site_brand'] ?? 'Rishikul Shiksha' ) ); ?>,
							description: 'Course Admission',
							order_id: resp.order_id,
							prefill: {
								name:    form.querySelector('input[name="full_name"]').value || '',
								email:   form.querySelector('input[name="email"]').value || '',
								contact: form.querySelector('input[name="phone"]').value || ''
							},
							handler: function (rsp) {
								form.querySelector('#rks-rzp-payment-id').value = rsp.razorpay_payment_id || '';
								form.querySelector('#rks-rzp-order-id').value   = rsp.razorpay_order_id   || '';
								form.querySelector('#rks-rzp-signature').value  = rsp.razorpay_signature  || '';
								submitFormNow();
							},
							modal: {
								ondismiss: function () { showError('Payment cancelled.'); payBtn.disabled = false; }
							},
							theme: { color: '#0ea5e9' }
						};
						var rzp = new window.Razorpay(opts);
						rzp.on('payment.failed', function (e) { showError((e && e.error && e.error.description) || 'Payment failed.'); payBtn.disabled = false; });
						rzp.open();
					});
				});
			}

			function payCashfree(amount) {
				return createGatewayOrder().then(function (resp) {
					if (!resp || !resp.ok) { throw new Error((resp && resp.error) || 'Could not create order'); }
					var gd = resp.gateway_data || {};
					form.querySelector('#rks-cf-order-id').value = resp.order_id || '';
					return loadScript('https://sdk.cashfree.com/js/v3/cashfree.js').then(function () {
						var mode = (gd.env === 'production') ? 'production' : 'sandbox';
						var cashfree = window.Cashfree({ mode: mode });
						cashfree.checkout({
							paymentSessionId: gd.payment_session_id,
							redirectTarget: '_self'
						});
					});
				});
			}

			if (payBtn) {
				payBtn.addEventListener('click', function () {
					clearError();
					var amount = currentAmount();
					if (amount <= 0) { showError('Select a paid course first.'); return; }
					// Required field check on visible steps
					var inputs = form.querySelectorAll('input[required]:not([type="file"]), select[required]');
					for (var k = 0; k < inputs.length; k++) {
						if (!inputs[k].offsetParent) continue; // skip hidden
						if (!inputs[k].checkValidity()) { inputs[k].reportValidity(); return; }
					}
					var requiredFiles = form.querySelectorAll('input[type="file"][required]');
					for (var f = 0; f < requiredFiles.length; f++) {
						if (!requiredFiles[f].offsetParent) continue;
						if (!requiredFiles[f].files || requiredFiles[f].files.length === 0) {
							requiredFiles[f].reportValidity();
							return;
						}
					}
					var agree = form.querySelector('input[name="agree"]');
					if (agree && !agree.checked) { showError('Please accept the terms.'); return; }
					var tnc = form.querySelector('input[name="rks_terms_accepted"]');
					if (tnc && !tnc.checked) { showError('Please accept the Student Terms & Conditions to continue.'); tnc.reportValidity(); return; }
					payBtn.disabled = true;
					var gw = currentGateway();
					var p  = (gw === 'razorpay') ? payRazorpay(amount)
						   : (gw === 'cashfree') ? payCashfree(amount)
						   : Promise.reject(new Error('Unknown gateway'));
					p.catch(function (e) { showError(e.message || 'Payment error.'); payBtn.disabled = false; });
				});
			}

			syncCourseOptions(false);
			updateDocumentRequirements();
			updatePrice();
		})();
		</script>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Validate an admission document upload without moving the temporary file.
	 *
	 * @param array<string, mixed> $file $_FILES entry.
	 * @return array{ok:bool, mime?:string, error?:string}
	 */
	public function validate_document_upload( array $file, ?array $allowed_mime = null ): array {
		if ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
			return [ 'ok' => false, 'error' => 'No file uploaded.' ];
		}
		if ( ! empty( $file['error'] ) && UPLOAD_ERR_OK !== (int) $file['error'] ) {
			return [ 'ok' => false, 'error' => 'Upload error code ' . (int) $file['error'] ];
		}
		if ( (int) ( $file['size'] ?? 0 ) > self::DOC_MAX_BYTES ) {
			return [ 'ok' => false, 'error' => 'File too large (max 5MB).' ];
		}

		$mime = function_exists( 'mime_content_type' ) ? (string) mime_content_type( $file['tmp_name'] ) : (string) ( $file['type'] ?? '' );
		$allowed_mime = $allowed_mime ?: self::DOC_ALLOWED_MIME;
		if ( ! in_array( $mime, $allowed_mime, true ) ) {
			return [ 'ok' => false, 'error' => 'Unsupported file type. Use JPG / PNG / WEBP / PDF.' ];
		}

		return [ 'ok' => true, 'mime' => $mime ];
	}

	/**
	 * Save an admission document to the private uploads area.
	 *
	 * @param array<string, mixed> $file $_FILES entry.
	 * @return array{ok:bool, path?:string, original_name?:string, mime?:string, error?:string}
	 */
	public function store_document( array $file, string $prefix, ?array $allowed_mime = null ): array {
		$valid = $this->validate_document_upload( $file, $allowed_mime );
		if ( empty( $valid['ok'] ) ) {
			return $valid;
		}

		$mime = (string) ( $valid['mime'] ?? '' );
		$ext  = match ( $mime ) {
			'image/jpeg', 'image/jpg' => 'jpg',
			'image/png'               => 'png',
			'image/webp'              => 'webp',
			'application/pdf'         => 'pdf',
			default                   => 'bin',
		};

		$uploads = wp_upload_dir();
		$dir     = trailingslashit( $uploads['basedir'] ) . 'rksiksha-private/admissions/' . date( 'Y/m' );
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		$safe_prefix = sanitize_key( $prefix ) ?: 'document';
		$basename    = $safe_prefix . '-' . wp_generate_uuid4() . '.' . $ext;
		$dest        = $dir . '/' . $basename;

		if ( ! move_uploaded_file( $file['tmp_name'], $dest ) ) {
			return [ 'ok' => false, 'error' => 'Could not save file.' ];
		}

		$rel = ltrim( str_replace( $uploads['basedir'], '', $dest ), DIRECTORY_SEPARATOR . '/' );

		return [
			'ok'            => true,
			'path'          => $rel,
			'original_name' => sanitize_file_name( (string) ( $file['name'] ?? $basename ) ),
			'mime'          => $mime,
		];
	}

	/**
	 * Authenticated proxy to view private admission documents.
	 */
	public function handle_admin_view_document(): void {
		if ( ! is_user_logged_in() ) wp_die( 'Forbidden', 'Forbidden', [ 'response' => 403 ] );

		$admission_id = isset( $_GET['admission_id'] ) ? (int) $_GET['admission_id'] : 0;
		$doc_key      = isset( $_GET['doc'] ) ? sanitize_key( wp_unslash( (string) $_GET['doc'] ) ) : '';
		$nonce        = isset( $_GET['_n'] ) ? (string) $_GET['_n'] : '';
		if ( ! $admission_id || ! $doc_key || ! wp_verify_nonce( $nonce, 'rks_view_admission_doc_' . $admission_id . '_' . $doc_key ) ) {
			wp_die( 'Invalid token', 'Forbidden', [ 'response' => 403 ] );
		}

		$adm = $this->get( $admission_id );
		if ( ! $adm ) {
			wp_die( 'Not found', 'Not found', [ 'response' => 404 ] );
		}

		$user      = wp_get_current_user();
		$is_super  = current_user_can( 'rks_view_all_admissions' );
		$is_branch = current_user_can( 'rks_approve_admission' ) && (int) get_user_meta( $user->ID, 'rks_branch_id', true ) === (int) $adm['branch_id'];
		$is_student = ! empty( $adm['user_id'] ) && (int) $adm['user_id'] === (int) $user->ID;
		if ( ! $is_super && ! $is_branch && ! $is_student ) {
			wp_die( 'Forbidden', 'Forbidden', [ 'response' => 403 ] );
		}

		$meta      = DB::decode_meta( (string) ( $adm['meta'] ?? '' ) );
		$documents = isset( $meta['documents'] ) && is_array( $meta['documents'] ) ? $meta['documents'] : [];
		$doc       = isset( $documents[ $doc_key ] ) && is_array( $documents[ $doc_key ] ) ? $documents[ $doc_key ] : [];
		$rel_path  = (string) ( $doc['path'] ?? '' );
		if ( '' === $rel_path ) {
			wp_die( 'Not found', 'Not found', [ 'response' => 404 ] );
		}

		$uploads = wp_upload_dir();
		$base    = realpath( $uploads['basedir'] );
		$file    = realpath( trailingslashit( $uploads['basedir'] ) . ltrim( $rel_path, '/\\' ) );
		if ( ! $base || ! $file || ! str_starts_with( $file, $base ) || ! is_readable( $file ) ) {
			wp_die( 'File missing', 'Not found', [ 'response' => 404 ] );
		}

		$mime = function_exists( 'mime_content_type' ) ? (string) mime_content_type( $file ) : (string) ( $doc['mime'] ?? 'application/octet-stream' );
		$name = sanitize_file_name( (string) ( $doc['original_name'] ?? basename( $file ) ) );
		header( 'Content-Type: ' . $mime );
		header( 'Content-Length: ' . filesize( $file ) );
		header( 'Content-Disposition: inline; filename="' . $name . '"' );
		header( 'Cache-Control: private, max-age=300' );
		readfile( $file );
		exit;
	}

	public static function document_view_url( int $admission_id, string $doc_key ): string {
		$doc_key = sanitize_key( $doc_key );
		return add_query_arg( [
			'action'       => 'rks_admission_document',
			'admission_id' => $admission_id,
			'doc'          => $doc_key,
			'_n'           => wp_create_nonce( 'rks_view_admission_doc_' . $admission_id . '_' . $doc_key ),
		], admin_url( 'admin-post.php' ) );
	}

	private function admission_id_from_reference( string $reference ): int {
		if ( preg_match( '/^ADM-\d{4}-(\d+)$/', strtoupper( trim( $reference ) ), $m ) ) {
			return (int) ltrim( $m[1], '0' );
		}
		return 0;
	}

	private function can_view_admission_record( array $adm ): bool {
		if ( ! is_user_logged_in() ) {
			return false;
		}
		$user_id = get_current_user_id();
		if ( ! empty( $adm['user_id'] ) && (int) $adm['user_id'] === $user_id ) {
			return true;
		}
		if ( current_user_can( 'rks_view_all_admissions' ) ) {
			return true;
		}
		return current_user_can( 'rks_approve_admission' )
			&& (int) get_user_meta( $user_id, 'rks_branch_id', true ) === (int) $adm['branch_id'];
	}

	private function printable_boxes( string $value, int $cells = 32 ): string {
		$value = strtoupper( $value );
		$value = function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $cells ) : substr( $value, 0, $cells );
		$out   = '';
		for ( $i = 0; $i < $cells; $i++ ) {
			$out .= '<span>' . esc_html( $value[ $i ] ?? '' ) . '</span>';
		}
		return $out;
	}

	private function render_printable_admission( string $reference ): string {
		$admission_id = $this->admission_id_from_reference( $reference );
		$adm          = $admission_id > 0 ? $this->get( $admission_id ) : null;
		if ( ! $adm || ! $this->can_view_admission_record( $adm ) ) {
			return '<div class="rks-auth-alert rks-auth-alert--error">Printable admission form is not available for this record.</div>';
		}

		global $wpdb;
		$settings = (array) get_option( 'rks_settings', [] );
		$currency = (string) ( $settings['currency_symbol'] ?? '₹' );
		$brand    = (string) ( $settings['site_brand'] ?? 'Rishikul Shiksha' );
		$branch   = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . DB::table( 'branches' ) . ' WHERE id = %d', (int) $adm['branch_id'] ), ARRAY_A );
		$meta     = DB::decode_meta( (string) ( $adm['meta'] ?? '' ) );
		$fees     = isset( $meta['fees'] ) && is_array( $meta['fees'] ) ? $meta['fees'] : [];
		$documents = isset( $meta['documents'] ) && is_array( $meta['documents'] ) ? $meta['documents'] : [];
		$student_details = isset( $meta['student_details'] ) && is_array( $meta['student_details'] ) ? $meta['student_details'] : [];

		$has_fee_meta  = ! empty( $fees );
		$admission_fee = $has_fee_meta ? (float) ( $fees['admission_fee'] ?? self::ADMISSION_FEE ) : 0.0;
		$course_fee    = $has_fee_meta ? (float) ( $fees['course_fee'] ?? 0 ) : (float) $adm['fees_amount'];
		$payable_now   = (float) ( $fees['payable_now'] ?? (float) $adm['fees_amount'] );
		$course_total  = (float) ( $fees['course_total'] ?? $course_fee );
		$category      = (string) ( $meta['course_category_label'] ?? '' );
		$created       = ! empty( $adm['created_at'] ) ? mysql2date( 'd M Y', (string) $adm['created_at'] ) : date_i18n( 'd M Y' );
		$branch_name   = $branch ? (string) $branch['name'] : 'Branch #' . (int) $adm['branch_id'];
		$branch_city   = $branch && ! empty( $branch['city'] ) ? (string) $branch['city'] : '';
		$print_url     = add_query_arg( 'rks_print', $reference, home_url( '/admission/' ) );
		$photo_url     = ! empty( $documents['student_photo']['path'] ) ? self::document_view_url( $admission_id, 'student_photo' ) : '';
		$back_url      = current_user_can( 'rks_approve_admission' ) ? admin_url( 'admin.php?page=rks-admissions' ) : home_url( '/student-dashboard/' );
		$back_label    = current_user_can( 'rks_approve_admission' ) ? 'Back to Admissions' : 'Back to Dashboard';

		$terms_sections = [
			[
				'title' => 'Admission Policy',
				'items' => [
					'Admission is confirmed only after completion of registration formalities and fee payment.',
					'Students must submit valid identity proof and required documents at the time of admission.',
					'Providing false information may result in cancellation of admission.',
				],
			],
			[
				'title' => 'Fee & Payment Policy',
				'items' => [
					'All fees must be paid according to the selected payment plan.',
					'Fees once deposited are strictly non-refundable and non-transferable under any circumstances.',
					'Delayed payments may attract late charges or temporary suspension from classes.',
					'The institute reserves the right to revise course fees or charges when required.',
				],
			],
			[
				'title' => 'Attendance Policy',
				'items' => [
					'Students are expected to attend classes regularly and maintain punctuality.',
					'Minimum attendance may be required for examinations, practicals, and certificate eligibility.',
					'Repeated absenteeism without valid reason may affect course progress.',
				],
			],
			[
				'title' => 'Code of Conduct',
				'items' => [
					'Students must maintain respectful and professional behavior within institute premises.',
					'Use of abusive language, fighting, bullying, or inappropriate conduct is strictly prohibited.',
					'Any activity disturbing the learning environment may lead to disciplinary action.',
				],
			],
			[
				'title' => 'Classroom & Lab Rules',
				'items' => [
					'Students must handle computers, accessories, and institute equipment carefully.',
					'Installing unauthorized software, changing system settings, or accessing restricted content is prohibited.',
					'Food, drinks, and unnecessary noise inside labs or classrooms are not allowed.',
					'Students are responsible for any intentional damage caused to institute property.',
				],
			],
			[
				'title' => 'Course & Schedule Management',
				'items' => [
					'The institute may modify class schedules, batches, faculty members, or course structure when necessary.',
					'Extra classes, workshops, or revision sessions may be arranged for academic improvement.',
					'Recorded sessions or backup classes may be provided only for selected courses.',
				],
			],
			[
				'title' => 'Examination & Certification',
				'items' => [
					'Students must complete assignments, projects, and assessments on time.',
					'Certificates will be awarded only after successful completion of the course and clearance of all dues.',
					'The institute reserves the right to withhold certificates in case of misconduct or pending fees.',
				],
			],
			[
				'title' => 'Placement & Career Support',
				'items' => [
					'Placement assistance, interview preparation, and career guidance may be provided for eligible students.',
					'Job placement depends on student performance, skills, attendance, and company requirements.',
					'The institute does not guarantee employment.',
				],
			],
			[
				'title' => 'Study Material Policy',
				'items' => [
					'Notes, videos, PDFs, software, and learning materials provided by the institute are for personal educational use only.',
					'Sharing, reselling, recording, or reproducing institute content without permission is prohibited.',
				],
			],
			[
				'title' => 'Safety & Security',
				'items' => [
					'Students should take care of their personal belongings.',
					'The institute will not be responsible for loss, theft, or damage of personal items.',
					'CCTV surveillance may be used on campus for security and safety purposes.',
				],
			],
			[
				'title' => 'Digital & Online Learning Policy',
				'items' => [
					'Students attending online classes must maintain proper online discipline.',
					'Sharing login credentials or unauthorized access to online classes is prohibited.',
					'Online sessions may be recorded for quality and training purposes.',
				],
			],
			[
				'title' => 'Privacy Policy',
				'items' => [
					'Student data and personal information will be kept secure and confidential.',
					'Information may be used for academic communication, certification, and administrative purposes only.',
				],
			],
			[
				'title' => 'Management Rights',
				'items' => [
					'The institute reserves the right to update policies, course content, fees, and regulations without prior notice.',
					'Management decisions regarding academic and disciplinary matters shall be final and binding.',
				],
			],
			[
				'title' => 'Declaration',
				'items' => [
					'I hereby confirm that I have read and understood all institute rules, policies, and terms & conditions. I agree to follow them sincerely during my enrollment period.',
				],
			],
		];

		ob_start();
		?>
		<div class="rks-adm-print-toolbar no-print">
			<a class="rks-btn rks-btn--ghost" href="<?php echo esc_url( $back_url ); ?>"><?php echo esc_html( $back_label ); ?></a>
			<button class="rks-btn rks-btn--primary" type="button" onclick="window.print()">Download / Save PDF</button>
		</div>

		<div class="rks-adm-print" data-print-url="<?php echo esc_url( $print_url ); ?>">
			<section class="rks-adm-print-sheet">
				<header class="rks-adm-print-head">
					<div>
						<small>Application form of admission</small>
						<h1>Admission Form for Students</h1>
						<strong><?php echo esc_html( $brand ); ?></strong>
					</div>
					<div class="rks-adm-print-ref">
						<span>Form No.</span>
						<strong><?php echo esc_html( $reference ); ?></strong>
						<span>Date: <?php echo esc_html( $created ); ?></span>
					</div>
				</header>

				<div class="rks-adm-print-photo">
					<?php if ( $photo_url ) : ?>
						<img src="<?php echo esc_url( $photo_url ); ?>" alt="Student photo">
					<?php else : ?>
						Passport Photo
					<?php endif; ?>
				</div>

				<div class="rks-adm-print-field">
					<label>Name of student</label>
					<div class="rks-adm-print-boxes"><?php echo $this->printable_boxes( (string) $adm['full_name'], 34 ); ?></div>
				</div>
				<div class="rks-adm-print-field">
					<label>Father's Name</label>
					<div class="rks-adm-print-boxes"><?php echo $this->printable_boxes( (string) $adm['father_name'], 34 ); ?></div>
				</div>
				<div class="rks-adm-print-grid rks-adm-print-grid--narrow">
					<div class="rks-adm-print-field">
						<label>Parent's Mobile No.</label>
						<div class="rks-adm-print-line"><?php echo esc_html( (string) ( $student_details['parent_mobile'] ?? '' ) ); ?></div>
					</div>
					<div class="rks-adm-print-field">
						<label>Mobile No.</label>
						<div class="rks-adm-print-line"><?php echo esc_html( (string) $adm['phone'] ); ?></div>
					</div>
					<div class="rks-adm-print-field">
						<label>Email</label>
						<div class="rks-adm-print-line"><?php echo esc_html( (string) $adm['email'] ); ?></div>
					</div>
				</div>
				<div class="rks-adm-print-grid rks-adm-print-grid--narrow">
					<div class="rks-adm-print-field">
						<label>D.O.B.</label>
						<div class="rks-adm-print-line"><?php echo esc_html( (string) $adm['dob'] ); ?></div>
					</div>
					<div class="rks-adm-print-field">
						<label>Gender</label>
						<div class="rks-adm-print-line"><?php echo esc_html( ucfirst( (string) $adm['gender'] ) ); ?></div>
					</div>
					<div class="rks-adm-print-field">
						<label>Nationality</label>
						<div class="rks-adm-print-line"><?php echo esc_html( (string) ( $student_details['nationality'] ?? '' ) ); ?></div>
					</div>
				</div>
				<div class="rks-adm-print-grid">
					<div class="rks-adm-print-field">
						<label>Religion</label>
						<div class="rks-adm-print-line"><?php echo esc_html( (string) ( $student_details['religion'] ?? '' ) ); ?></div>
					</div>
					<div class="rks-adm-print-field">
						<label>Qualification</label>
						<div class="rks-adm-print-line"><?php echo esc_html( (string) $adm['qualification'] ); ?></div>
					</div>
				</div>
				<div class="rks-adm-print-field">
					<label>Permanent Address</label>
					<div class="rks-adm-print-line rks-adm-print-line--tall"><?php echo esc_html( (string) $adm['address'] ); ?></div>
				</div>
				<div class="rks-adm-print-grid">
					<div class="rks-adm-print-field">
						<label>District</label>
						<div class="rks-adm-print-line"><?php echo esc_html( (string) ( $student_details['permanent_district'] ?? '' ) ); ?></div>
					</div>
					<div class="rks-adm-print-field">
						<label>State</label>
						<div class="rks-adm-print-line"><?php echo esc_html( (string) ( $student_details['permanent_state'] ?? '' ) ); ?></div>
					</div>
					<div class="rks-adm-print-field">
						<label>Nation</label>
						<div class="rks-adm-print-line"><?php echo esc_html( (string) ( $student_details['permanent_nation'] ?? '' ) ); ?></div>
					</div>
				</div>
				<div class="rks-adm-print-field">
					<label>Present Address</label>
					<div class="rks-adm-print-line rks-adm-print-line--tall"><?php echo esc_html( (string) ( $student_details['present_address'] ?? '' ) ); ?></div>
				</div>
				<div class="rks-adm-print-grid">
					<div class="rks-adm-print-field">
						<label>District</label>
						<div class="rks-adm-print-line"><?php echo esc_html( (string) ( $student_details['present_district'] ?? '' ) ); ?></div>
					</div>
					<div class="rks-adm-print-field">
						<label>State</label>
						<div class="rks-adm-print-line"><?php echo esc_html( (string) ( $student_details['present_state'] ?? '' ) ); ?></div>
					</div>
					<div class="rks-adm-print-field">
						<label>Nation</label>
						<div class="rks-adm-print-line"><?php echo esc_html( (string) ( $student_details['present_nation'] ?? '' ) ); ?></div>
					</div>
				</div>
				<div class="rks-adm-print-grid">
					<div class="rks-adm-print-field">
						<label>Course Applied For</label>
						<div class="rks-adm-print-line"><?php echo esc_html( (string) $adm['course_title'] ); ?></div>
					</div>
					<div class="rks-adm-print-field">
						<label>Course Category</label>
						<div class="rks-adm-print-line"><?php echo esc_html( $category ); ?></div>
					</div>
				</div>
				<div class="rks-adm-print-grid">
					<div class="rks-adm-print-field">
						<label>Branch / Study Centre</label>
						<div class="rks-adm-print-line"><?php echo esc_html( trim( $branch_name . ( $branch_city ? ', ' . $branch_city : '' ) ) ); ?></div>
					</div>
					<div class="rks-adm-print-field">
						<label>Documents Submitted</label>
						<div class="rks-adm-print-line"><?php echo esc_html( implode( ', ', array_map( static fn( $d ) => is_array( $d ) ? (string) ( $d['label'] ?? 'Document' ) : 'Document', $documents ) ) ); ?></div>
					</div>
				</div>

				<div class="rks-adm-print-fees">
					<div><span>Admission Fee</span><strong><?php echo esc_html( $currency . number_format( $admission_fee, 0 ) ); ?></strong></div>
					<div><span>Course Fee Payable Now</span><strong><?php echo esc_html( $currency . number_format( $course_fee, 0 ) ); ?></strong></div>
					<div><span>Course Total</span><strong><?php echo esc_html( $currency . number_format( $course_total, 0 ) ); ?></strong></div>
					<div><span>Total Paid / Payable</span><strong><?php echo esc_html( $currency . number_format( $payable_now, 0 ) ); ?></strong></div>
				</div>

				<p class="rks-adm-print-declaration">
					I agree to abide by the rules and regulations of the institute. I certify that the information provided above is true and the submitted documents are valid.
				</p>
				<div class="rks-adm-print-sign">
					<span>Date</span>
					<span>Student Signature</span>
					<span>Parent / Guardian Signature</span>
				</div>
			</section>

			<section class="rks-adm-print-sheet rks-adm-print-sheet--terms">
				<header class="rks-adm-print-terms-head">
					<h2>Terms &amp; Conditions Apply</h2>
					<p><?php echo esc_html( $brand ); ?> student admission declaration</p>
				</header>
				<div class="rks-adm-print-terms">
					<?php foreach ( $terms_sections as $index => $section ) : ?>
						<section class="rks-adm-print-term-section">
							<h3><span><?php echo esc_html( (string) ( $index + 1 ) ); ?></span><?php echo esc_html( $section['title'] ); ?></h3>
							<ul>
								<?php foreach ( $section['items'] as $term ) : ?>
									<li><?php echo esc_html( $term ); ?></li>
								<?php endforeach; ?>
							</ul>
						</section>
					<?php endforeach; ?>
				</div>
				<div class="rks-adm-print-sign rks-adm-print-sign--terms">
					<span>Student Signature</span>
					<span>Parent's Signature</span>
				</div>
			</section>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/* ================================================================== */
	/* SUBMISSION HANDLER                                                  */
	/* ================================================================== */

	public function handle_submission(): void {
		if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) return;
		if ( empty( $_POST['rks_action'] ) || $_POST['rks_action'] !== 'admission_submit' ) return;

		if ( ! isset( $_POST['_rks_adm_nonce'] ) || ! wp_verify_nonce( wp_unslash( (string) $_POST['_rks_adm_nonce'] ), self::NONCE ) ) {
			$this->redirect_back( 'nonce' );
		}

		// Sanitize
		$full_name      = sanitize_text_field( wp_unslash( (string) ( $_POST['full_name']     ?? '' ) ) );
		$father_name    = sanitize_text_field( wp_unslash( (string) ( $_POST['father_name']   ?? '' ) ) );
		$email          = sanitize_email(      wp_unslash( (string) ( $_POST['email']         ?? '' ) ) );
		$phone          = sanitize_text_field( wp_unslash( (string) ( $_POST['phone']         ?? '' ) ) );
		$dob            = sanitize_text_field( wp_unslash( (string) ( $_POST['dob']           ?? '' ) ) );
		$gender         = sanitize_text_field( wp_unslash( (string) ( $_POST['gender']        ?? '' ) ) );
		$address        = sanitize_textarea_field( wp_unslash( (string) ( $_POST['address']    ?? '' ) ) );
		$qualification  = sanitize_text_field( wp_unslash( (string) ( $_POST['qualification'] ?? '' ) ) );
		$father_occupation = '';
		$parent_mobile      = sanitize_text_field( wp_unslash( (string) ( $_POST['parent_mobile'] ?? '' ) ) );
		$nationality        = sanitize_text_field( wp_unslash( (string) ( $_POST['nationality'] ?? '' ) ) );
		$religion           = sanitize_text_field( wp_unslash( (string) ( $_POST['religion'] ?? '' ) ) );
		$permanent_district = sanitize_text_field( wp_unslash( (string) ( $_POST['permanent_district'] ?? '' ) ) );
		$permanent_state    = sanitize_text_field( wp_unslash( (string) ( $_POST['permanent_state'] ?? '' ) ) );
		$permanent_nation   = sanitize_text_field( wp_unslash( (string) ( $_POST['permanent_nation'] ?? '' ) ) );
		$present_address    = sanitize_textarea_field( wp_unslash( (string) ( $_POST['present_address'] ?? '' ) ) );
		$present_district   = sanitize_text_field( wp_unslash( (string) ( $_POST['present_district'] ?? '' ) ) );
		$present_state      = sanitize_text_field( wp_unslash( (string) ( $_POST['present_state'] ?? '' ) ) );
		$present_nation     = sanitize_text_field( wp_unslash( (string) ( $_POST['present_nation'] ?? '' ) ) );
		$branch_id      = (int) ( $_POST['branch_id'] ?? 0 );
		$course_id      = (int) ( $_POST['course_id'] ?? 0 );
		$course_category = sanitize_key( wp_unslash( (string) ( $_POST['course_category'] ?? '' ) ) );
		$utr            = sanitize_text_field( wp_unslash( (string) ( $_POST['utr']          ?? '' ) ) );
		$amount_paid    = (float) ( $_POST['amount_paid'] ?? 0 );
		$agree          = ! empty( $_POST['agree'] );
		$terms_accepted = ! empty( $_POST['rks_terms_accepted'] );

		$course_category_labels = [
			Courses::CAT_CERTIFICATE => 'Certificate Courses',
			Courses::CAT_DIPLOMA     => 'Diploma Courses',
		];

		if ( ! $full_name || ! $father_name || ! $email || ! $phone ) $this->redirect_back( 'missing' );
		if ( ! is_email( $email ) )                            $this->redirect_back( 'invalid_email' );
		if ( ! $dob || ! $gender || ! $qualification || ! $address ) $this->redirect_back( 'missing' );
		if ( ! $parent_mobile || ! $nationality || ! $religion ) $this->redirect_back( 'missing' );
		if ( ! $permanent_district || ! $permanent_state || ! $permanent_nation || ! $present_address || ! $present_district || ! $present_state || ! $present_nation ) $this->redirect_back( 'missing' );
		if ( $branch_id <= 0 || $course_id <= 0 )              $this->redirect_back( 'missing' );
		if ( ! isset( $course_category_labels[ $course_category ] ) ) $this->redirect_back( 'course_category' );
		if ( ! $agree )                                        $this->redirect_back( 'terms' );
		if ( ! $terms_accepted )                               $this->redirect_back( 'student_terms' );

		$course = get_post( $course_id );
		if ( ! $course || $course->post_type !== Courses::CPT_COURSE ) $this->redirect_back( 'invalid_course' );
		if ( Courses::classify_admission_category( $course_id ) !== $course_category ) {
			$this->redirect_back( 'course_category' );
		}

		// Seat limit guard — 0 means unlimited
		$seat_limit = (int) get_post_meta( $course_id, Courses::M_SEAT_LIMIT, true );
		if ( $seat_limit > 0 ) {
			$current_count = (int) get_post_meta( $course_id, Courses::M_ENROLLED_COUNT, true );
			if ( $current_count >= $seat_limit ) {
				$this->redirect_back( 'seats_full' );
			}
		}

		$is_paid       = (bool) get_post_meta( $course_id, Courses::M_IS_PAID, true );
		$pay_mode      = (string) get_post_meta( $course_id, Courses::M_PAYMENT_MODE, true ) ?: 'one_time';
		$monthly_fee   = (float) get_post_meta( $course_id, Courses::M_MONTHLY_FEE, true );
		$total_months  = (int) get_post_meta( $course_id, Courses::M_TOTAL_MONTHS, true );
		$total_price   = (float) get_post_meta( $course_id, Courses::M_PRICE, true );

		// Authoritative course fee: first installment for monthly, full price for one-time.
		if ( $is_paid && $pay_mode === 'monthly' && $monthly_fee > 0 && $total_months > 0 ) {
			$course_fee_due = $monthly_fee;
		} elseif ( $is_paid ) {
			$course_fee_due = $total_price;
		} else {
			$course_fee_due = 0.0;
		}
		$admission_fee    = self::ADMISSION_FEE;
		$fees             = $course_fee_due + $admission_fee;
		$requires_payment = $fees > 0;

		$requires_marksheet = Courses::CAT_DIPLOMA === $course_category;
		$photo_check        = $this->validate_document_upload( (array) ( $_FILES['student_photo'] ?? [] ), self::PHOTO_ALLOWED_MIME );
		if ( empty( $photo_check['ok'] ) ) {
			$this->redirect_back( 'photo_required' );
		}
		$aadhar_front_check = $this->validate_document_upload( (array) ( $_FILES['aadhar_front'] ?? [] ) );
		$aadhar_back_check  = $this->validate_document_upload( (array) ( $_FILES['aadhar_back'] ?? [] ) );
		if ( empty( $aadhar_front_check['ok'] ) || empty( $aadhar_back_check['ok'] ) ) {
			$this->redirect_back( 'document_required' );
		}
		if ( $requires_marksheet ) {
			$marksheet_check = $this->validate_document_upload( (array) ( $_FILES['marksheet'] ?? [] ) );
			if ( empty( $marksheet_check['ok'] ) ) {
				$this->redirect_back( 'document_required' );
			}
		}

		$gateway_choice = sanitize_key( wp_unslash( (string) ( $_POST['_gateway'] ?? 'upi' ) ) );
		if ( ! in_array( $gateway_choice, [ 'upi', 'razorpay', 'cashfree', 'offline' ], true ) ) {
			$gateway_choice = 'upi';
		}

		$payment_id     = 0;
		$admission_status_paid = self::STATUS_PAYMENT_REVIEW;

		if ( $requires_payment ) {
			$payments = new Payments();

			if ( $gateway_choice === 'razorpay' ) {
				$gw  = $this->gateway( 'razorpay' );
				if ( ! $gw ) $this->redirect_back( 'payment_required' );
				$res = $gw->verify_payment( [
					'razorpay_order_id'   => (string) ( $_POST['razorpay_order_id']   ?? '' ),
					'razorpay_payment_id' => (string) ( $_POST['razorpay_payment_id'] ?? '' ),
					'razorpay_signature'  => (string) ( $_POST['razorpay_signature']  ?? '' ),
				] );
				if ( empty( $res['ok'] ) ) $this->redirect_back( 'payment_required' );

				$payment_id = $payments->create( [
					'branch_id' => $branch_id,
					'amount'    => (float) ( $res['amount'] ?? $fees ),
					'method'    => 'razorpay',
					'utr'       => (string) ( $res['utr'] ?? '' ),
					'status'    => Payments::STATUS_APPROVED,
					'meta'      => (array) ( $res['meta'] ?? [] ),
				] );
				$admission_status_paid = self::STATUS_APPROVED;

			} elseif ( $gateway_choice === 'cashfree' ) {
				$gw = $this->gateway( 'cashfree' );
				if ( ! $gw ) $this->redirect_back( 'payment_required' );
				$cf_order_id = sanitize_text_field( wp_unslash( (string) ( $_POST['cf_order_id'] ?? $_GET['cf_order_id'] ?? $_GET['order_id'] ?? '' ) ) );
				if ( ! $cf_order_id ) $this->redirect_back( 'payment_required' );
				$res = $gw->verify_payment( [ 'order_id' => $cf_order_id ] );
				if ( empty( $res['ok'] ) ) $this->redirect_back( 'payment_required' );

				$payment_id = $payments->create( [
					'branch_id' => $branch_id,
					'amount'    => (float) ( $res['amount'] ?? $fees ),
					'method'    => 'cashfree',
					'utr'       => (string) ( $res['utr'] ?? '' ),
					'status'    => Payments::STATUS_APPROVED,
					'meta'      => (array) ( $res['meta'] ?? [] ),
				] );
				$admission_status_paid = self::STATUS_APPROVED;

			} elseif ( $gateway_choice === 'offline' ) {
				$payment_id = $payments->create( [
					'branch_id'       => $branch_id,
					'amount'          => $fees,
					'method'          => 'offline',
					'utr'             => 'OFFLINE-' . strtoupper( wp_generate_password( 8, false, false ) ),
					'status'          => Payments::STATUS_PENDING,
				] );
				$admission_status_paid = self::STATUS_PAYMENT_REVIEW;
			} else {
				// Manual UPI flow — amount is server-authoritative (derived from course meta).
				if ( $fees <= 0 || ! $utr || empty( $_FILES['screenshot']['tmp_name'] ?? null ) ) {
					$this->redirect_back( 'payment_required' );
				}
				$store = $payments->store_screenshot( (array) $_FILES['screenshot'] );
				if ( ! $store['ok'] ) $this->redirect_back( 'upload_failed' );

				$payment_id = $payments->create( [
					'branch_id'       => $branch_id,
					'amount'          => $fees,
					'method'          => 'upi',
					'utr'             => $utr,
					'screenshot_path' => $store['path'],
					'status'          => Payments::STATUS_PENDING,
				] );
			}
		}

		$documents = [];
		$photo_store = $this->store_document( (array) $_FILES['student_photo'], 'student-photo', self::PHOTO_ALLOWED_MIME );
		if ( empty( $photo_store['ok'] ) ) {
			$this->redirect_back( 'upload_failed' );
		}
		$documents['student_photo'] = [
			'label'         => 'Student Photo',
			'path'          => (string) $photo_store['path'],
			'original_name' => (string) ( $photo_store['original_name'] ?? '' ),
			'mime'          => (string) ( $photo_store['mime'] ?? '' ),
		];
		$aadhar_front_store = $this->store_document( (array) $_FILES['aadhar_front'], 'aadhar-front' );
		if ( empty( $aadhar_front_store['ok'] ) ) {
			$this->redirect_back( 'upload_failed' );
		}
		$documents['aadhar_front'] = [
			'label'         => 'Aadhaar Card Front',
			'path'          => (string) $aadhar_front_store['path'],
			'original_name' => (string) ( $aadhar_front_store['original_name'] ?? '' ),
			'mime'          => (string) ( $aadhar_front_store['mime'] ?? '' ),
		];

		$aadhar_back_store = $this->store_document( (array) $_FILES['aadhar_back'], 'aadhar-back' );
		if ( empty( $aadhar_back_store['ok'] ) ) {
			$this->redirect_back( 'upload_failed' );
		}
		$documents['aadhar_back'] = [
			'label'         => 'Aadhaar Card Back',
			'path'          => (string) $aadhar_back_store['path'],
			'original_name' => (string) ( $aadhar_back_store['original_name'] ?? '' ),
			'mime'          => (string) ( $aadhar_back_store['mime'] ?? '' ),
		];
		if ( $requires_marksheet ) {
			$marksheet_store = $this->store_document( (array) $_FILES['marksheet'], 'marksheet' );
			if ( empty( $marksheet_store['ok'] ) ) {
				$this->redirect_back( 'upload_failed' );
			}
			$documents['marksheet'] = [
				'label'         => '10th / 12th Marksheet',
				'path'          => (string) $marksheet_store['path'],
				'original_name' => (string) ( $marksheet_store['original_name'] ?? '' ),
				'mime'          => (string) ( $marksheet_store['mime'] ?? '' ),
			];
		}

		$admission_meta = [
			'course_category'       => $course_category,
			'course_category_label' => $course_category_labels[ $course_category ],
			'student_details'       => [
				'father_occupation' => $father_occupation,
				'parent_mobile'      => $parent_mobile,
				'nationality'        => $nationality,
				'religion'           => $religion,
				'permanent_address'  => $address,
				'permanent_district' => $permanent_district,
				'permanent_state'    => $permanent_state,
				'permanent_nation'   => $permanent_nation,
				'present_address'    => $present_address,
				'present_district'   => $present_district,
				'present_state'      => $present_state,
				'present_nation'     => $present_nation,
			],
			'fees'                  => [
				'admission_fee' => $admission_fee,
				'course_fee'    => $course_fee_due,
				'payable_now'   => $fees,
				'course_total'   => $total_price,
				'payment_mode'   => $pay_mode,
				'total_months'   => $total_months,
			],
			'documents'             => $documents,
		];

		// Insert admission row
		global $wpdb;
		$wpdb->insert(
			DB::table( 'admissions' ),
			[
				'branch_id'     => $branch_id,
				'full_name'     => $full_name,
				'father_name'   => $father_name ?: null,
				'email'         => $email,
				'phone'         => $phone,
				'dob'           => $dob ?: null,
				'gender'        => $gender ?: null,
				'address'       => $address ?: null,
				'qualification' => $qualification ?: null,
				'course_id'     => $course_id,
				'course_title'  => $course->post_title,
				'fees_amount'   => $fees,
				'payment_id'    => $payment_id ?: null,
				'status'        => $requires_payment ? $admission_status_paid : 'pending',
				'meta'          => DB::encode_meta( $admission_meta ),
			],
			[ '%d','%s','%s','%s','%s','%s','%s','%s','%s','%d','%s','%f','%d','%s','%s' ]
		);
		$adm_id = (int) $wpdb->insert_id;

		if ( $adm_id <= 0 ) $this->redirect_back( 'generic' );

		// Link payment back to admission
		if ( $payment_id > 0 ) {
			$wpdb->update( DB::table( 'payments' ), [ 'admission_id' => $adm_id ], [ 'id' => $payment_id ], [ '%d' ], [ '%d' ] );
		}

		$reference = $this->build_reference( $adm_id );

		// Create user account immediately so student can track status from dashboard.
		// Enrollment is created in 'pending' state — admin approval flips it to 'active'.
		$account = $this->ensure_student_account( $email, $full_name, $phone, $branch_id, $dob );
		$wpdb->update(
			DB::table( 'admissions' ),
			[ 'user_id' => $account['user_id'] ],
			[ 'id' => $adm_id ],
			[ '%d' ],
			[ '%d' ]
		);
		// Backfill user_id on the payment row so Payment History (queried by user_id)
		// shows this payment for the student. The payment is created before the account
		// exists in this flow, so we tag it once the user_id is known.
		if ( $payment_id > 0 && ! empty( $account['user_id'] ) ) {
			$wpdb->update(
				DB::table( 'payments' ),
				[ 'user_id' => (int) $account['user_id'] ],
				[ 'id' => $payment_id ],
				[ '%d' ],
				[ '%d' ]
			);
		}
		( new Lms() )->enroll_user( $account['user_id'], $course_id, $branch_id, 'pending' );
		wp_set_auth_cookie( $account['user_id'], true );
		wp_set_current_user( $account['user_id'] );

		// Send notifications
		$this->notify_student_submitted( $email, $full_name, $reference, $course->post_title, $requires_payment );
		$this->notify_branch_admin( $branch_id, $full_name, $reference, $course->post_title );

		$redirect_args = [ 'rks_msg' => 'submitted', 'rks_ref' => $reference ];
		// Always store metadata so success card can show Name + Course.
		// `password` is empty for existing accounts — UI handles both cases.
		$token = wp_generate_password( 20, false, false );
		set_transient( 'rks_adm_creds_' . $token, [
			'username'       => $account['username'],
			'password'       => $account['password'],
			'name'           => $full_name,
			'course'         => $course->post_title,
			'ref'            => $reference,
			'account_status' => ! empty( $account['credentials_shown'] ) ? 'new' : 'existing',
		], 30 * MINUTE_IN_SECONDS );
		$redirect_args['rks_creds'] = $token;

		wp_safe_redirect( add_query_arg( $redirect_args, home_url( '/admission/' ) ) );
		exit;
	}

	/**
	 * Create a student user if one doesn't already exist for this email.
	 * Returns ['user_id', 'username', 'password', 'credentials_shown'].
	 * `credentials_shown` is true only when a fresh account was created in this call —
	 * existing users keep their old password; we never re-display it.
	 */
	public static function password_from_dob( string $dob ): string {
		$dob  = trim( $dob );
		$date = \DateTimeImmutable::createFromFormat( '!Y-m-d', $dob );
		$errors = \DateTimeImmutable::getLastErrors();

		if ( ! $date || ( is_array( $errors ) && ( $errors['warning_count'] > 0 || $errors['error_count'] > 0 ) ) ) {
			return '';
		}

		return $date->format( 'dmY' );
	}

	private function ensure_student_account( string $email, string $full_name, string $phone, int $branch_id, string $dob ): array {
		$existing = get_user_by( 'email', $email );
		if ( $existing ) {
			return [
				'user_id'           => (int) $existing->ID,
				'username'          => $existing->user_login,
				'password'          => '',
				'credentials_shown' => false,
			];
		}

		$base    = sanitize_user( strtolower( str_replace( ' ', '', explode( '@', $email )[0] ) ), true ) ?: 'student';
		$login   = $this->unique_login( $base );
		$pass    = self::password_from_dob( $dob );
		if ( '' === $pass ) {
			return [
				'user_id'           => 0,
				'username'          => '',
				'password'          => '',
				'credentials_shown' => false,
			];
		}
		$user_id = wp_create_user( $login, $pass, $email );
		if ( is_wp_error( $user_id ) ) {
			// Fall back: caller continues without account creation.
			return [
				'user_id'           => 0,
				'username'          => '',
				'password'          => '',
				'credentials_shown' => false,
			];
		}
		wp_update_user( [
			'ID'           => $user_id,
			'display_name' => $full_name,
			'first_name'   => explode( ' ', $full_name )[0],
		] );
		( get_user_by( 'id', $user_id ) )->set_role( Roles::STUDENT );
		update_user_meta( $user_id, 'rks_branch_id', $branch_id );
		update_user_meta( $user_id, 'rks_phone', $phone );

		return [
			'user_id'           => (int) $user_id,
			'username'          => $login,
			'password'          => $pass,
			'credentials_shown' => true,
		];
	}

	/* ================================================================== */
	/* GATEWAY ORDER CREATION (AJAX)                                       */
	/* ================================================================== */

	public function handle_pg_create_order(): void {
		$nonce = isset( $_POST['_nonce'] ) ? (string) wp_unslash( $_POST['_nonce'] ) : '';
		if ( ! wp_verify_nonce( $nonce, self::NONCE_PG ) ) {
			wp_send_json( [ 'ok' => false, 'error' => 'Invalid security token.' ], 403 );
		}

		$gateway_id = sanitize_key( (string) ( $_POST['gateway']   ?? '' ) );
		$amount     = (float)        ( $_POST['amount']    ?? 0 );
		$course_id  = (int)          ( $_POST['course_id'] ?? 0 );
		$branch_id  = (int)          ( $_POST['branch_id'] ?? 0 );

		if ( $amount <= 0 || $course_id <= 0 || $branch_id <= 0 ) {
			wp_send_json( [ 'ok' => false, 'error' => 'Invalid amount, course, or branch.' ], 400 );
		}

		$course = get_post( $course_id );
		if ( ! $course || $course->post_type !== Courses::CPT_COURSE ) {
			wp_send_json( [ 'ok' => false, 'error' => 'Invalid course.' ], 400 );
		}

		// Seat limit guard
		$seat_limit = (int) get_post_meta( $course_id, Courses::M_SEAT_LIMIT, true );
		if ( $seat_limit > 0 ) {
			$current_count = (int) get_post_meta( $course_id, Courses::M_ENROLLED_COUNT, true );
			if ( $current_count >= $seat_limit ) {
				wp_send_json( [ 'ok' => false, 'error' => 'This course is full.' ], 400 );
			}
		}

		$is_paid     = (bool) get_post_meta( $course_id, Courses::M_IS_PAID, true );
		$pay_mode    = (string) get_post_meta( $course_id, Courses::M_PAYMENT_MODE, true ) ?: 'one_time';
		$monthly_fee = (float) get_post_meta( $course_id, Courses::M_MONTHLY_FEE, true );
		$course_fee  = (float) get_post_meta( $course_id, Courses::M_PRICE, true );

		// For monthly: charge first installment only. Admission fee is always separate.
		$course_due    = $is_paid ? ( ( $pay_mode === 'monthly' && $monthly_fee > 0 ) ? $monthly_fee : $course_fee ) : 0.0;
		$server_amount = $course_due + self::ADMISSION_FEE;
		if ( $server_amount <= 0 ) {
			wp_send_json( [ 'ok' => false, 'error' => 'No payment is due.' ], 400 );
		}
		// Trust server-side amount.
		$amount = $server_amount;

		$gateway = $this->gateway( $gateway_id );
		if ( ! $gateway || ! $gateway->is_configured() ) {
			wp_send_json( [ 'ok' => false, 'error' => 'Gateway not available.' ], 400 );
		}

		$context = [
			'id'             => 'C' . $course_id . 'B' . $branch_id . 'T' . time(),
			'course_id'      => $course_id,
			'branch_id'      => $branch_id,
			'customer_id'    => 'cust_' . wp_rand( 1000, 999999 ),
			'customer_name'  => sanitize_text_field( wp_unslash( (string) ( $_POST['full_name'] ?? '' ) ) ),
			'customer_email' => sanitize_email(      wp_unslash( (string) ( $_POST['email']     ?? '' ) ) ),
			'customer_phone' => sanitize_text_field( wp_unslash( (string) ( $_POST['phone']     ?? '' ) ) ),
			'return_url'     => home_url( '/admission/?_gateway=cashfree&cf_order_id={order_id}' ),
		];

		$result = $gateway->create_order( $amount, 'INR', $context );

		if ( isset( $result['error'] ) ) {
			wp_send_json( [ 'ok' => false, 'error' => (string) $result['error'] ], 400 );
		}

		wp_send_json( [
			'ok'           => true,
			'gateway'      => $gateway->id(),
			'order_id'     => (string) ( $result['order_id'] ?? '' ),
			'gateway_data' => (array)  ( $result['gateway_data'] ?? [] ),
		] );
	}

	private function build_reference( int $admission_id ): string {
		return 'ADM-' . date( 'Y' ) . '-' . str_pad( (string) $admission_id, 5, '0', STR_PAD_LEFT );
	}

	/* ================================================================== */
	/* APPROVAL / REJECTION (called from admin page in Phase 5)            */
	/* ================================================================== */

	public function approve( int $admission_id, int $reviewer_id ): array {
		global $wpdb;
		$adm = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . DB::table( 'admissions' ) . ' WHERE id = %d', $admission_id ), ARRAY_A );
		if ( ! $adm ) return [ 'ok' => false, 'error' => 'Admission not found.' ];
		if ( $adm['status'] === self::STATUS_APPROVED ) return [ 'ok' => false, 'error' => 'Already approved.' ];

		// Approve linked payment first (if any)
		if ( ! empty( $adm['payment_id'] ) ) {
			( new Payments() )->approve( (int) $adm['payment_id'], $reviewer_id );
		}

		// Create or get user
		$user_id = (int) $adm['user_id'];
		if ( $user_id <= 0 ) {
			$existing = get_user_by( 'email', $adm['email'] );
			if ( $existing ) {
				$user_id = (int) $existing->ID;
			} else {
				$base    = sanitize_user( strtolower( str_replace( ' ', '', explode( '@', $adm['email'] )[0] ) ), true ) ?: ( 'student' . $admission_id );
				$login   = $this->unique_login( $base );
				$pass    = self::password_from_dob( (string) $adm['dob'] );
				if ( '' === $pass ) {
					return [ 'ok' => false, 'error' => 'Student date of birth is missing or invalid.' ];
				}
				$user_id = wp_create_user( $login, $pass, $adm['email'] );
				if ( is_wp_error( $user_id ) ) return [ 'ok' => false, 'error' => 'User creation failed: ' . $user_id->get_error_message() ];
				wp_update_user( [ 'ID' => $user_id, 'display_name' => $adm['full_name'], 'first_name' => explode( ' ', $adm['full_name'] )[0] ] );
				( get_user_by( 'id', $user_id ) )->set_role( Roles::STUDENT );
				update_user_meta( $user_id, 'rks_branch_id', (int) $adm['branch_id'] );
				update_user_meta( $user_id, 'rks_phone', $adm['phone'] );

				$this->notify_student_approved( $adm['email'], $adm['full_name'], $login, $pass, $adm['course_title'] );
			}
		}

		// Set student profile/ID card photo from admission documents
		$meta      = DB::decode_meta( (string) ( $adm['meta'] ?? '' ) );
		$documents = isset( $meta['documents'] ) && is_array( $meta['documents'] ) ? $meta['documents'] : [];
		$photo_doc = isset( $documents['student_photo'] ) && is_array( $documents['student_photo'] ) ? $documents['student_photo'] : null;
		if ( $photo_doc && ! empty( $photo_doc['path'] ) ) {
			$this->import_photo_to_media(
				(string) $photo_doc['path'],
				(string) ( $photo_doc['original_name'] ?? 'student-photo.jpg' ),
				(string) ( $photo_doc['mime'] ?? 'image/jpeg' ),
				$user_id
			);
		}

		// Activate enrollment (created in 'pending' at submission time) or create fresh.
		$lms = new Lms();
		if ( ! $lms->activate_enrollment( $user_id, (int) $adm['course_id'] ) ) {
			$lms->enroll_user( $user_id, (int) $adm['course_id'], (int) $adm['branch_id'], 'active' );
		}

		// Update admission status
		$wpdb->update(
			DB::table( 'admissions' ),
			[
				'status'      => self::STATUS_APPROVED,
				'user_id'     => $user_id,
				'reviewed_by' => $reviewer_id,
				'reviewed_at' => current_time( 'mysql' ),
			],
			[ 'id' => $admission_id ],
			[ '%s', '%d', '%d', '%s' ],
			[ '%d' ]
		);

		do_action( 'rks_admission_approved', $admission_id, $user_id, (int) $adm['branch_id'] );

		return [ 'ok' => true, 'user_id' => $user_id ];
	}

	public function reject( int $admission_id, int $reviewer_id, string $note = '' ): array {
		global $wpdb;
		$adm = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . DB::table( 'admissions' ) . ' WHERE id = %d', $admission_id ), ARRAY_A );
		if ( ! $adm ) return [ 'ok' => false, 'error' => 'Admission not found.' ];

		if ( ! empty( $adm['payment_id'] ) ) {
			( new Payments() )->reject( (int) $adm['payment_id'], $reviewer_id, $note );
		}

		$wpdb->update(
			DB::table( 'admissions' ),
			[
				'status'         => self::STATUS_REJECTED,
				'rejection_note' => $note,
				'reviewed_by'    => $reviewer_id,
				'reviewed_at'    => current_time( 'mysql' ),
			],
			[ 'id' => $admission_id ],
			[ '%s', '%s', '%d', '%s' ],
			[ '%d' ]
		);

		$this->notify_student_rejected( $adm['email'], $adm['full_name'], $note );

		return [ 'ok' => true ];
	}

	private function unique_login( string $base ): string {
		$try = $base;
		$i   = 1;
		while ( username_exists( $try ) ) {
			$try = $base . $i;
			$i++;
			if ( $i > 999 ) {
				$try = $base . wp_rand( 1000, 9999 );
				break;
			}
		}
		return $try;
	}

	/* ================================================================== */
	/* QUERIES                                                             */
	/* ================================================================== */

	public function get( int $id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . DB::table( 'admissions' ) . ' WHERE id = %d', $id ), ARRAY_A );
		return $row ?: null;
	}

	public function list( array $args = [] ): array {
		global $wpdb;
		$where  = [ '1=1' ];
		$params = [];
		if ( ! empty( $args['branch_id'] ) ) { $where[] = 'branch_id = %d'; $params[] = (int) $args['branch_id']; }
		if ( ! empty( $args['status'] ) )    { $where[] = 'status = %s';    $params[] = (string) $args['status']; }

		$limit  = max( 1, min( 200, (int) ( $args['limit']  ?? 50 ) ) );
		$offset = max( 0,           (int) ( $args['offset'] ?? 0  ) );
		$sql = 'SELECT * FROM ' . DB::table( 'admissions' ) . ' WHERE ' . implode( ' AND ', $where ) . ' ORDER BY id DESC LIMIT %d OFFSET %d';
		$params[] = $limit;
		$params[] = $offset;
		return $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A ) ?: [];
	}

	/* ================================================================== */
	/* EMAIL HELPERS                                                       */
	/* ================================================================== */

	private function notify_student_submitted( string $email, string $name, string $ref, string $course, bool $is_paid ): void {
		$mailer = new Emailer();
		$mailer->send_template(
			$email,
			'Your admission has been received — ' . $ref,
			'Hi ' . $name . ', we received your application',
			'<p>Thanks for applying to Rishikul Shiksha! Our team is reviewing your application' . ( $is_paid ? ' and verifying your payment' : '' ) . '. You\'ll hear from us within 24 hours.</p>',
			[
				'Reference'   => $ref,
				'Course'      => $course,
				'Status'      => $is_paid ? 'Awaiting payment verification' : 'Awaiting review',
			]
		);
	}

	private function notify_branch_admin( int $branch_id, string $student_name, string $ref, string $course ): void {
		global $wpdb;
		$admin_id = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT owner_user_id FROM ' . DB::table( 'branches' ) . ' WHERE id = %d', $branch_id ) );
		if ( $admin_id <= 0 ) return;
		$user = get_userdata( $admin_id );
		if ( ! $user ) return;

		$mailer = new Emailer();
		$mailer->send_template(
			$user->user_email,
			'New admission application — ' . $ref,
			'New admission ready for review',
			'<p>A new admission has been submitted to your branch.</p>',
			[
				'Reference' => $ref,
				'Student'   => $student_name,
				'Course'    => $course,
			],
			[ 'label' => 'Open Admin Dashboard', 'url' => admin_url( 'admin.php?page=rks-admissions' ) ]
		);
	}

	private function notify_student_approved( string $email, string $name, string $login, string $password, string $course ): void {
		$mailer = new Emailer();
		$mailer->send_template(
			$email,
			'Your Rishikul Shiksha admission is approved',
			'Welcome, ' . $name . '!',
			'<p>Your admission has been approved and your student account is ready. You\'re enrolled in <strong>' . esc_html( $course ) . '</strong>.</p><p>Login credentials are below — please change your password after first login.</p>',
			[
				'Username' => $login,
				'Password' => $password,
				'Course'   => $course,
			],
			[ 'label' => 'Login & Start Learning', 'url' => home_url( '/login/' ) ]
		);
	}

	private function notify_student_rejected( string $email, string $name, string $note ): void {
		$mailer = new Emailer();
		$mailer->send_template(
			$email,
			'Your Rishikul Shiksha admission application',
			'Hi ' . $name . ', we couldn\'t approve your application',
			'<p>We were unable to approve your admission at this time.</p>' . ( $note ? '<p><strong>Reason:</strong> ' . esc_html( $note ) . '</p>' : '' ) . '<p>Please contact us at info@rishikulshiksha.com for clarification or to resubmit.</p>'
		);
	}

	/* ================================================================== */
	/* INTERNAL                                                            */
	/* ================================================================== */

	private function error_message( string $code ): string {
		$map = [
			'nonce'           => 'Security check failed. Refresh and try again.',
			'missing'         => 'Please fill all required fields.',
			'invalid_email'   => 'Please enter a valid email address.',
			'invalid_course'  => 'Selected course is not valid.',
			'terms'           => 'You must accept the terms to submit.',
			'student_terms'   => 'Please tick the Student Terms & Conditions checkbox to submit your admission.',
			'payment_required'=> 'Please complete the payment section (UTR, amount, screenshot).',
			'photo_required'  => 'Please upload a valid student photo.',
			'document_required' => 'Please upload all required admission documents.',
			'upload_failed'   => 'Could not save your uploaded file. Try again.',
			'seats_full'      => 'Sorry, this course is currently full. Please choose another course or contact us to join the waitlist.',
			'generic'         => 'Something went wrong. Try again or contact support.',
		];
		return $map[ $code ] ?? $map['generic'];
	}

	private function redirect_back( string $code ): void {
		wp_safe_redirect( add_query_arg( 'rks_err', $code, home_url( '/admission/' ) ) );
		exit;
	}

	/**
	 * Import a private admission student photo into WordPress Media Library as a public attachment
	 * and link it to the student user as 'rks_photo_id'.
	 *
	 * @param string $rel_path Private uploads relative path.
	 * @param string $original_name Original file name.
	 * @param string $mime MIME type.
	 * @param int $user_id Student user ID.
	 * @return int|null Attachment ID or null on failure.
	 */
	public function import_photo_to_media( string $rel_path, string $original_name, string $mime, int $user_id ): ?int {
		$uploads = wp_upload_dir();
		$src_file = trailingslashit( $uploads['basedir'] ) . ltrim( $rel_path, '/\\' );
		if ( ! file_exists( $src_file ) || ! is_readable( $src_file ) ) {
			return null;
		}

		$filename = basename( $src_file );
		$dir      = trailingslashit( $uploads['path'] );
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		$dest_file = wp_unique_filename( $dir, $filename );
		$dest_path = $dir . $dest_file;

		if ( ! copy( $src_file, $dest_path ) ) {
			return null;
		}

		$attachment = [
			'guid'           => trailingslashit( $uploads['url'] ) . $dest_file,
			'post_mime_type' => $mime,
			'post_title'     => preg_replace( '/\.[^.]+$/', '', $original_name ),
			'post_content'   => '',
			'post_status'    => 'inherit',
			'post_author'    => $user_id,
		];

		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$attach_id = wp_insert_attachment( $attachment, $dest_path, 0 );
		if ( is_wp_error( $attach_id ) || ! $attach_id ) {
			@unlink( $dest_path );
			return null;
		}

		$attach_data = wp_generate_attachment_metadata( (int) $attach_id, $dest_path );
		wp_update_attachment_metadata( (int) $attach_id, $attach_data );

		update_user_meta( $user_id, 'rks_photo_id', (int) $attach_id );

		return (int) $attach_id;
	}
}
