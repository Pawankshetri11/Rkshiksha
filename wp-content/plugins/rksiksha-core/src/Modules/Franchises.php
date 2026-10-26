<?php
/**
 * Franchises module — application form handler + approval workflow.
 *
 * @package RKSiksha\Core
 */

declare( strict_types=1 );

namespace RKSiksha\Core\Modules;

use RKSiksha\Core\Roles;
use RKSiksha\Core\Support\DB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Franchises {

	public const NONCE          = 'rks_franchise_apply';
	public const STATUS_PENDING   = 'pending';
	public const STATUS_APPROVED  = 'approved';
	public const STATUS_REJECTED  = 'rejected';
	public const STATUS_ONBOARDED = 'onboarded';

	public function register(): void {
		add_action( 'init', [ $this, 'register_shortcode' ] );
		add_action( 'template_redirect', [ $this, 'handle_submission' ], 5 );
	}

	public function register_shortcode(): void {
		add_shortcode( 'rks_franchise_form', [ $this, 'shortcode_form' ] );
		add_shortcode( 'rks_franchise_onboarding_form', [ $this, 'shortcode_onboarding_form' ] );
	}

	public function shortcode_form( $atts = [] ): string {
		$error = isset( $_GET['rks_franchise_err'] ) ? sanitize_key( wp_unslash( (string) $_GET['rks_franchise_err'] ) ) : '';
		$msg   = isset( $_GET['rks_franchise_msg'] ) ? sanitize_key( wp_unslash( (string) $_GET['rks_franchise_msg'] ) ) : '';

		ob_start();
		?>
		<?php if ( 'inquiry_submitted' === $msg || 'submitted' === $msg ) : ?>
			<div class="rks-frn-success">
				<div class="rks-frn-success__icon">OK</div>
				<h3>Enquiry Received</h3>
				<p>Thanks for your interest in Rishikul Shiksha franchise partnership. Our team will contact you and share the onboarding steps.</p>
			</div>
			<?php return ob_get_clean(); ?>
		<?php endif; ?>

		<?php if ( $error ) : ?>
			<div class="rks-auth-alert rks-auth-alert--error">
				<?php echo esc_html( $this->error_message( $error ) ); ?>
			</div>
		<?php endif; ?>

		<form method="post" class="rks-coaching__franchise-form rks-frn-form" action="<?php echo esc_url( get_permalink() ); ?>">
			<?php wp_nonce_field( self::NONCE, '_rks_frn_nonce' ); ?>
			<input type="hidden" name="rks_action" value="franchise_apply">
			<input type="hidden" name="rks_franchise_mode" value="inquiry">

			<div class="rks-form-grid">
				<div class="rks-form-row">
					<label>Full Name *</label>
					<input type="text" name="applicant_name" required>
				</div>
				<div class="rks-form-row">
					<label>Phone *</label>
					<input type="tel" name="phone" required>
				</div>
			</div>
			<div class="rks-form-grid">
				<div class="rks-form-row">
					<label>Email *</label>
					<input type="email" name="email" required>
				</div>
				<div class="rks-form-row">
					<label>City *</label>
					<input type="text" name="city" required>
				</div>
			</div>
			<div class="rks-form-row">
				<label>Message</label>
				<textarea name="message" rows="4" placeholder="Share your city, current business/background, and what you want to know about the franchise model."></textarea>
			</div>
			<div class="rks-form-row">
				<label class="rks-form-check">
					<input type="checkbox" name="agree" value="1" required>
					I confirm the information is accurate and consent to be contacted by Rishikul Shiksha.
				</label>
			</div>
			<button type="submit" class="rks-btn rks-btn--primary rks-btn--lg">Send Enquiry</button>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	public function shortcode_onboarding_form( $atts = [] ): string {
		if ( isset( $_GET['rks_frn_agreement'] ) ) {
			$id    = absint( $_GET['rks_frn_agreement'] );
			$token = isset( $_GET['rks_frn_token'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['rks_frn_token'] ) ) : '';
			return $this->render_printable_agreement( $id, $token );
		}

		$error = isset( $_GET['rks_franchise_err'] ) ? sanitize_key( wp_unslash( (string) $_GET['rks_franchise_err'] ) ) : '';
		$msg   = isset( $_GET['rks_franchise_msg'] ) ? sanitize_key( wp_unslash( (string) $_GET['rks_franchise_msg'] ) ) : '';
		$ref   = isset( $_GET['rks_frn_ref'] ) ? absint( $_GET['rks_frn_ref'] ) : 0;
		$token = isset( $_GET['rks_frn_token'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['rks_frn_token'] ) ) : '';
		$agreement_url = ( $ref > 0 && '' !== $token ) ? self::agreement_print_url( $ref, $token ) : '';

		ob_start();
		?>
		<?php if ( 'onboarding_submitted' === $msg ) : ?>
			<div class="rks-frn-success">
				<div class="rks-frn-success__icon">OK</div>
				<h3>Onboarding Form Submitted</h3>
				<p>Your franchise registration details and Aadhaar document have been received. Our admin team will verify and contact you for approval.</p>
				<?php if ( $agreement_url ) : ?>
					<div class="rks-frn-success__actions">
						<a href="<?php echo esc_url( $agreement_url ); ?>" target="_blank" rel="noopener" class="rks-btn rks-btn--primary">Download / Print Agreement PDF</a>
					</div>
				<?php endif; ?>
			</div>
			<?php return ob_get_clean(); ?>
		<?php endif; ?>

		<?php if ( $error ) : ?>
			<div class="rks-auth-alert rks-auth-alert--error">
				<?php echo esc_html( $this->error_message( $error ) ); ?>
			</div>
		<?php endif; ?>

		<form method="post" enctype="multipart/form-data" class="rks-coaching__franchise-form rks-frn-form rks-frn-form--onboarding" action="<?php echo esc_url( get_permalink() ); ?>">
			<?php wp_nonce_field( self::NONCE, '_rks_frn_nonce' ); ?>
			<input type="hidden" name="rks_action" value="franchise_apply">
			<input type="hidden" name="rks_franchise_mode" value="onboarding">

			<section class="rks-frn-section">
				<h3>Basic Details</h3>
				<div class="rks-form-grid">
					<div class="rks-form-row">
						<label>Applicant / Owner Name *</label>
						<input type="text" name="applicant_name" required>
					</div>
					<div class="rks-form-row">
						<label>Phone *</label>
						<input type="tel" name="phone" required>
					</div>
				</div>
				<div class="rks-form-grid">
					<div class="rks-form-row">
						<label>Email *</label>
						<input type="email" name="email" required>
					</div>
					<div class="rks-form-row">
						<label>Proposed Centre Name *</label>
						<input type="text" name="branch_name" placeholder="Rishikul Shiksha - City" required>
					</div>
				</div>
				<div class="rks-form-grid">
					<div class="rks-form-row">
						<label>Aadhaar Number *</label>
						<input type="text" name="aadhar_number" inputmode="numeric" maxlength="14" placeholder="12 digit Aadhaar number" required>
					</div>
					<div class="rks-form-row">
						<label>Aadhaar Card Upload *</label>
						<input type="file" name="aadhar_card" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/*" required>
					</div>
				</div>
			</section>

			<section class="rks-frn-section">
				<h3>Detailed Location</h3>
				<div class="rks-form-row">
					<label>Full Centre Address *</label>
					<input type="text" name="address_line1" placeholder="Building, road, area" required>
				</div>
				<div class="rks-form-row">
					<label>Nearby Landmark</label>
					<input type="text" name="landmark" placeholder="Near bus stand, market, school, etc.">
				</div>
				<div class="rks-form-grid">
					<div class="rks-form-row">
						<label>City *</label>
						<input type="text" name="city" required>
					</div>
					<div class="rks-form-row">
						<label>District *</label>
						<input type="text" name="district" required>
					</div>
				</div>
				<div class="rks-form-grid">
					<div class="rks-form-row">
						<label>State *</label>
						<input type="text" name="state" required>
					</div>
					<div class="rks-form-row">
						<label>Pincode *</label>
						<input type="text" name="pincode" inputmode="numeric" maxlength="6" required>
					</div>
				</div>
				<div class="rks-form-row">
					<label>Google Map Link</label>
					<input type="url" name="map_link" placeholder="https://maps.google.com/...">
				</div>
			</section>

			<section class="rks-frn-section">
				<h3>Centre Readiness</h3>
				<div class="rks-form-grid">
					<div class="rks-form-row">
						<label>Premises Type *</label>
						<select name="premises_type" required>
							<option value="">Select</option>
							<option value="owned">Owned</option>
							<option value="rented">Rented</option>
							<option value="planned">Not finalized yet</option>
						</select>
					</div>
					<div class="rks-form-row">
						<label>Approx. Area (sq ft) *</label>
						<input type="number" name="area_sqft" min="100" step="1" required>
					</div>
				</div>
				<div class="rks-form-grid">
					<div class="rks-form-row">
						<label>Investment Budget</label>
						<input type="text" name="budget" placeholder="Example: 2 lakh - 5 lakh">
					</div>
					<div class="rks-form-row">
						<label>Business / Education Experience</label>
						<input type="text" name="experience" placeholder="Example: coaching, retail, teaching">
					</div>
				</div>
				<div class="rks-form-row">
					<label>Additional Notes</label>
					<textarea name="message" rows="4" placeholder="Any setup details, preferred launch date, or questions for the onboarding team."></textarea>
				</div>
			</section>

			<section class="rks-frn-section">
				<h3>Agreement</h3>
				<div class="rks-form-grid">
					<div class="rks-form-row">
						<label>Agreement Accepted By *</label>
						<input type="text" name="agreement_name" required>
					</div>
					<div class="rks-form-row">
						<label>Place *</label>
						<input type="text" name="agreement_place" required>
					</div>
				</div>
				<label class="rks-form-check">
					<input type="checkbox" name="agree" value="1" required>
					<span>
						I confirm all submitted details are correct, the Aadhaar document belongs to me, and I have read and agree to the
						<a href="#rks-frn-agreement-modal" data-rks-frn-agreement-open aria-haspopup="dialog">Franchise Agreement</a>.
					</span>
				</label>
			</section>

			<button type="submit" class="rks-btn rks-btn--primary rks-btn--lg">Submit Onboarding Form</button>
		</form>

		<div class="rks-frn-modal" id="rks-frn-agreement-modal" aria-hidden="true">
			<div class="rks-frn-modal__overlay" data-rks-frn-agreement-close></div>
			<div class="rks-frn-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="rks-frn-agreement-title">
				<button type="button" class="rks-frn-modal__close" data-rks-frn-agreement-close aria-label="Close agreement popup">&times;</button>
				<header class="rks-frn-modal__head">
					<span>Partner Agreement</span>
					<h2 id="rks-frn-agreement-title">Franchise Agreement</h2>
					<p>Please read these terms before submitting your franchise onboarding form.</p>
				</header>
				<div class="rks-frn-modal__body">
					<?php echo $this->render_agreement_sections_html( 'modal' ); ?>
				</div>
				<footer class="rks-frn-modal__foot">
					<button type="button" class="rks-btn rks-btn--primary" data-rks-frn-agreement-close>I Understand</button>
				</footer>
			</div>
		</div>

		<script>
		(function () {
			var modal = document.getElementById('rks-frn-agreement-modal');
			var open = document.querySelector('[data-rks-frn-agreement-open]');
			if (!modal || !open) return;
			var lastFocused = null;

			function openModal(event) {
				if (event) event.preventDefault();
				lastFocused = document.activeElement;
				modal.setAttribute('aria-hidden', 'false');
				document.body.classList.add('rks-frn-modal-open');
				var closeBtn = modal.querySelector('.rks-frn-modal__close');
				if (closeBtn) closeBtn.focus();
			}

			function closeModal() {
				modal.setAttribute('aria-hidden', 'true');
				document.body.classList.remove('rks-frn-modal-open');
				if (lastFocused && typeof lastFocused.focus === 'function') {
					lastFocused.focus();
				}
			}

			open.addEventListener('click', openModal);
			modal.querySelectorAll('[data-rks-frn-agreement-close]').forEach(function (btn) {
				btn.addEventListener('click', closeModal);
			});
			document.addEventListener('keydown', function (event) {
				if (event.key === 'Escape' && modal.getAttribute('aria-hidden') === 'false') {
					closeModal();
				}
			});
		})();
		</script>
		<?php
		return (string) ob_get_clean();
	}

	public function handle_submission(): void {
		if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) return;
		if ( empty( $_POST['rks_action'] ) || $_POST['rks_action'] !== 'franchise_apply' ) return;

		$mode   = sanitize_key( wp_unslash( (string) ( $_POST['rks_franchise_mode'] ?? 'inquiry' ) ) );
		$anchor = 'onboarding' === $mode ? '#onboarding' : '#apply';

		if ( ! isset( $_POST['_rks_frn_nonce'] ) || ! wp_verify_nonce( wp_unslash( (string) $_POST['_rks_frn_nonce'] ), self::NONCE ) ) {
			$this->redirect_back( 'nonce', $anchor );
		}

		if ( 'onboarding' === $mode ) {
			$this->handle_onboarding_submission();
			return;
		}

		$this->handle_inquiry_submission();
		return;
	}

	private function handle_inquiry_submission(): void {
		$name    = sanitize_text_field( wp_unslash( (string) ( $_POST['applicant_name'] ?? '' ) ) );
		$phone   = sanitize_text_field( wp_unslash( (string) ( $_POST['phone']          ?? '' ) ) );
		$email   = sanitize_email(      wp_unslash( (string) ( $_POST['email']          ?? '' ) ) );
		$city    = sanitize_text_field( wp_unslash( (string) ( $_POST['city']           ?? '' ) ) );
		$message = sanitize_textarea_field( wp_unslash( (string) ( $_POST['message']     ?? '' ) ) );
		$agree   = ! empty( $_POST['agree'] );

		if ( ! $name || ! $phone || ! $email || ! $city ) $this->redirect_back( 'missing', '#apply' );
		if ( ! is_email( $email ) )                       $this->redirect_back( 'invalid_email', '#apply' );
		if ( ! $agree )                                   $this->redirect_back( 'terms', '#apply' );

		global $wpdb;
		$wpdb->insert(
			DB::table( 'franchises' ),
			[
				'applicant_name' => $name,
				'phone'          => $phone,
				'email'          => $email,
				'city'           => $city,
				'message'        => $message,
				'status'         => self::STATUS_PENDING,
				'meta'           => wp_json_encode( [
					'form_type' => 'inquiry',
					'source'    => 'franchise_page',
				] ),
			],
			[ '%s','%s','%s','%s','%s','%s','%s' ]
		);

		$id = (int) $wpdb->insert_id;
		if ( $id <= 0 ) $this->redirect_back( 'generic', '#apply' );

		$mailer = new Emailer();
		$mailer->send_template(
			get_option( 'admin_email' ),
			'New franchise enquiry - ' . $city,
			'New Franchise Enquiry',
			'<p>A new franchise enquiry has been submitted.</p>',
			[
				'Applicant' => $name,
				'Email'     => $email,
				'Phone'     => $phone,
				'City'      => $city,
			],
			[ 'label' => 'Review Franchise Leads', 'url' => admin_url( 'admin.php?page=rks-franchise-inquiries&view=' . $id ) ]
		);
		$mailer->send_template(
			$email,
			'Your Rishikul Shiksha franchise enquiry',
			'Hi ' . $name . ', we received your enquiry',
			'<p>Thanks for your interest in becoming a Rishikul Shiksha franchise partner. Our team will reach out with the onboarding steps.</p>'
		);

		$this->redirect_success( 'inquiry_submitted', '#apply' );
	}

	private function handle_onboarding_submission(): void {
		$name            = sanitize_text_field( wp_unslash( (string) ( $_POST['applicant_name']  ?? '' ) ) );
		$phone           = sanitize_text_field( wp_unslash( (string) ( $_POST['phone']           ?? '' ) ) );
		$email           = sanitize_email(      wp_unslash( (string) ( $_POST['email']           ?? '' ) ) );
		$branch_name     = sanitize_text_field( wp_unslash( (string) ( $_POST['branch_name']     ?? '' ) ) );
		$aadhar_number   = preg_replace( '/\D+/', '', wp_unslash( (string) ( $_POST['aadhar_number'] ?? '' ) ) );
		$address_line1   = sanitize_text_field( wp_unslash( (string) ( $_POST['address_line1']   ?? '' ) ) );
		$landmark        = sanitize_text_field( wp_unslash( (string) ( $_POST['landmark']        ?? '' ) ) );
		$city            = sanitize_text_field( wp_unslash( (string) ( $_POST['city']            ?? '' ) ) );
		$district        = sanitize_text_field( wp_unslash( (string) ( $_POST['district']        ?? '' ) ) );
		$state           = sanitize_text_field( wp_unslash( (string) ( $_POST['state']           ?? '' ) ) );
		$pincode         = preg_replace( '/\D+/', '', wp_unslash( (string) ( $_POST['pincode']   ?? '' ) ) );
		$map_link        = esc_url_raw( wp_unslash( (string) ( $_POST['map_link']                ?? '' ) ) );
		$premises_type   = sanitize_key( wp_unslash( (string) ( $_POST['premises_type']          ?? '' ) ) );
		$area_sqft       = max( 0, (int) ( $_POST['area_sqft'] ?? 0 ) );
		$budget          = sanitize_text_field( wp_unslash( (string) ( $_POST['budget']          ?? '' ) ) );
		$experience      = sanitize_text_field( wp_unslash( (string) ( $_POST['experience']      ?? '' ) ) );
		$message         = sanitize_textarea_field( wp_unslash( (string) ( $_POST['message']     ?? '' ) ) );
		$agreement_name  = sanitize_text_field( wp_unslash( (string) ( $_POST['agreement_name']  ?? '' ) ) );
		$agreement_place = sanitize_text_field( wp_unslash( (string) ( $_POST['agreement_place'] ?? '' ) ) );
		$agree           = ! empty( $_POST['agree'] );
		$print_token     = wp_generate_password( 32, false, false );

		if ( ! $name || ! $phone || ! $email || ! $branch_name || ! $address_line1 || ! $city || ! $district || ! $state || ! $pincode || ! $premises_type || ! $area_sqft || ! $agreement_name || ! $agreement_place ) {
			$this->redirect_back( 'missing', '#onboarding' );
		}
		if ( ! is_email( $email ) )                       $this->redirect_back( 'invalid_email', '#onboarding' );
		if ( strlen( (string) $aadhar_number ) !== 12 )   $this->redirect_back( 'aadhar', '#onboarding' );
		if ( strlen( (string) $pincode ) !== 6 )          $this->redirect_back( 'pincode', '#onboarding' );
		if ( ! in_array( $premises_type, [ 'owned', 'rented', 'planned' ], true ) ) $this->redirect_back( 'missing', '#onboarding' );
		if ( ! $agree )                                   $this->redirect_back( 'terms', '#onboarding' );

		$file = $this->store_uploaded_document( 'aadhar_card' );
		if ( empty( $file['ok'] ) ) {
			$this->redirect_back( (string) ( $file['error'] ?? 'upload' ), '#onboarding' );
		}

		$meta = [
			'form_type'     => 'onboarding',
			'source'        => 'franchise_register',
			'branch_name'   => $branch_name,
			'aadhar_number' => $aadhar_number,
			'location'      => [
				'address_line1' => $address_line1,
				'landmark'      => $landmark,
				'city'          => $city,
				'district'      => $district,
				'state'         => $state,
				'pincode'       => $pincode,
				'map_link'      => $map_link,
			],
			'premises'      => [
				'type'       => $premises_type,
				'area_sqft'  => $area_sqft,
				'experience' => $experience,
			],
			'documents'     => [
				'aadhar_card' => $file['document'],
			],
			'agreement'     => [
				'accepted'    => true,
				'name'        => $agreement_name,
				'place'       => $agreement_place,
				'accepted_at' => current_time( 'mysql' ),
				'ip'          => $this->request_ip(),
				'user_agent'  => substr( sanitize_text_field( wp_unslash( (string) ( $_SERVER['HTTP_USER_AGENT'] ?? '' ) ) ), 0, 190 ),
				'version'     => 'franchise-agreement-2026-06',
				'print_token' => $print_token,
			],
		];

		global $wpdb;
		$wpdb->insert(
			DB::table( 'franchises' ),
			[
				'applicant_name' => $name,
				'phone'          => $phone,
				'email'          => $email,
				'city'           => $city,
				'budget'         => $budget,
				'message'        => $message,
				'status'         => self::STATUS_PENDING,
				'territory'      => trim( $district . ', ' . $state, ', ' ),
				'agreement_date' => current_time( 'Y-m-d' ),
				'meta'           => wp_json_encode( $meta ),
			],
			[ '%s','%s','%s','%s','%s','%s','%s','%s','%s','%s' ]
		);

		$id = (int) $wpdb->insert_id;
		if ( $id <= 0 ) {
			$this->redirect_back( 'generic', '#onboarding' );
		}

		$mailer = new Emailer();
		$mailer->send_template(
			get_option( 'admin_email' ),
			'New franchise onboarding - ' . $city,
			'New Franchise Onboarding',
			'<p>A new franchise onboarding form has been submitted.</p>',
			[
				'Applicant' => $name,
				'Email'     => $email,
				'Phone'     => $phone,
				'Centre'    => $branch_name,
				'City'      => $city,
				'District'  => $district,
				'State'     => $state,
			],
			[ 'label' => 'Review Onboarding', 'url' => admin_url( 'admin.php?page=rks-franchise-onboarding&view=' . $id ) ]
		);
		$mailer->send_template(
			$email,
			'Your Rishikul Shiksha franchise onboarding form',
			'Hi ' . $name . ', we received your onboarding form',
			'<p>Your franchise onboarding details have been submitted successfully. Our team will verify the details and contact you for the next step.</p>'
		);

		$this->redirect_success( 'onboarding_submitted', '#onboarding', [
			'rks_frn_ref'   => $id,
			'rks_frn_token' => $print_token,
		] );
	}

	public function approve( int $id, int $reviewer_id ): array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . DB::table( 'franchises' ) . ' WHERE id = %d', $id ), ARRAY_A );
		if ( ! $row ) return [ 'ok' => false, 'error' => 'Application not found.' ];
		if ( $row['status'] === self::STATUS_ONBOARDED ) return [ 'ok' => false, 'error' => 'Already onboarded.' ];

		$meta = self::decode_meta( $row['meta'] ?? '' );
		if ( 'inquiry' === (string) ( $meta['form_type'] ?? '' ) ) {
			return [ 'ok' => false, 'error' => 'This is an enquiry. Ask the applicant to submit the Franchise Register form before onboarding.' ];
		}
		$location    = is_array( $meta['location'] ?? null ) ? $meta['location'] : [];
		$branch_name = trim( (string) ( $meta['branch_name'] ?? '' ) );
		$branch_name = $branch_name ?: 'Rishikul Shiksha - ' . $row['city'];

		// Create user for franchise admin
		$user_id = email_exists( $row['email'] );
		if ( ! $user_id ) {
			$base    = sanitize_user( strtolower( str_replace( ' ', '', explode( '@', $row['email'] )[0] ) ), true );
			$base    = $base ?: 'franchise' . $id;
			$login   = $this->unique_login( $base . '_' . strtolower( str_replace( ' ', '', $row['city'] ) ) );
			$pass    = wp_generate_password( 14, true, false );
			$user_id = wp_create_user( $login, $pass, $row['email'] );
			if ( is_wp_error( $user_id ) ) return [ 'ok' => false, 'error' => 'User create failed: ' . $user_id->get_error_message() ];

			wp_update_user( [ 'ID' => $user_id, 'display_name' => $row['applicant_name'], 'first_name' => explode( ' ', $row['applicant_name'] )[0] ] );
			( get_user_by( 'id', $user_id ) )->set_role( Roles::BRANCH_ADMIN );

			$initial_password = $pass;
		} else {
			( get_user_by( 'id', $user_id ) )->add_role( Roles::BRANCH_ADMIN );
			$initial_password = null;
		}

		// Create franchise branch
		$branch_id = ( new Branches() )->create( [
			'type'          => Branches::TYPE_FRANCHISE,
			'name'          => $branch_name,
			'address'       => (string) ( $location['address_line1'] ?? '' ),
			'city'          => $row['city'],
			'state'         => (string) ( $location['state'] ?? '' ),
			'pincode'       => (string) ( $location['pincode'] ?? '' ),
			'phone'         => $row['phone'],
			'email'         => $row['email'],
			'owner_user_id' => $user_id,
			'franchise_id'  => $id,
			'status'        => 'active',
		] );

		update_user_meta( $user_id, 'rks_branch_id', $branch_id );

		// Update franchise record
		$wpdb->update(
			DB::table( 'franchises' ),
			[
				'status'        => self::STATUS_ONBOARDED,
				'reviewed_by'   => $reviewer_id,
				'reviewed_at'   => current_time( 'mysql' ),
				'branch_id'     => $branch_id,
				'agreement_date'=> current_time( 'Y-m-d' ),
			],
			[ 'id' => $id ],
			[ '%s', '%d', '%s', '%d', '%s' ],
			[ '%d' ]
		);

		// Email credentials
		$mailer = new Emailer();
		$rows = [
			'Username'  => get_userdata( $user_id )->user_login,
			'Branch'    => $branch_name,
			'Branch ID' => '#' . $branch_id,
		];
		if ( $initial_password ) $rows['Initial Password'] = $initial_password;

		$mailer->send_template(
			$row['email'],
			'Welcome to Rishikul Shiksha — your franchise is approved!',
			'Congratulations, ' . $row['applicant_name'] . '!',
			'<p>Your franchise application has been approved. You now have access to your branch dashboard. Login with the credentials below and change your password after first login.</p>',
			$rows,
			[ 'label' => 'Login to Branch Dashboard', 'url' => home_url( '/login/' ) ]
		);

		// Store one-shot credentials card data (24h TTL) so admin can also download as image.
		if ( $initial_password ) {
			set_transient( 'rks_franchise_card_' . $id, [
				'applicant_name' => (string) $row['applicant_name'],
				'branch_name'    => $branch_name,
				'city'           => (string) $row['city'],
				'email'          => (string) $row['email'],
				'username'       => (string) get_userdata( $user_id )->user_login,
				'password'       => $initial_password, // The plain password we just created
				'login_url'      => home_url( '/login/' ),
				'created_at'     => current_time( 'mysql' ),
			], DAY_IN_SECONDS );
		}

		return [ 'ok' => true, 'branch_id' => $branch_id, 'user_id' => $user_id ];
	}

	public function reject( int $id, int $reviewer_id, string $note = '' ): array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . DB::table( 'franchises' ) . ' WHERE id = %d', $id ), ARRAY_A );
		if ( ! $row ) return [ 'ok' => false, 'error' => 'Not found.' ];

		$wpdb->update(
			DB::table( 'franchises' ),
			[
				'status'        => self::STATUS_REJECTED,
				'rejection_note'=> $note,
				'reviewed_by'   => $reviewer_id,
				'reviewed_at'   => current_time( 'mysql' ),
			],
			[ 'id' => $id ],
			[ '%s', '%s', '%d', '%s' ],
			[ '%d' ]
		);

		( new Emailer() )->send_template(
			$row['email'],
			'Your Rishikul Shiksha franchise application',
			'Hi ' . $row['applicant_name'],
			'<p>Thank you for your interest. After review, we are unable to approve your application at this time.</p>' . ( $note ? '<p><strong>Note:</strong> ' . esc_html( $note ) . '</p>' : '' )
		);

		return [ 'ok' => true ];
	}

	public function list( array $args = [] ): array {
		global $wpdb;
		$where  = [ '1=1' ];
		$params = [];
		if ( ! empty( $args['status'] ) ) {
			$where[]  = 'status = %s';
			$params[] = (string) $args['status'];
		}
		if ( ! empty( $args['form_type'] ) && in_array( (string) $args['form_type'], [ 'inquiry', 'onboarding' ], true ) ) {
			$inquiry_like = '%' . $wpdb->esc_like( '"form_type":"inquiry"' ) . '%';
			if ( 'inquiry' === (string) $args['form_type'] ) {
				$where[]  = 'meta LIKE %s';
				$params[] = $inquiry_like;
			} else {
				$where[]  = '(meta IS NULL OR meta = "" OR meta NOT LIKE %s)';
				$params[] = $inquiry_like;
			}
		}
		$limit  = max( 1, min( 200, (int) ( $args['limit']  ?? 50 ) ) );
		$offset = max( 0,           (int) ( $args['offset'] ?? 0  ) );
		$sql = 'SELECT * FROM ' . DB::table( 'franchises' ) . ' WHERE ' . implode( ' AND ', $where ) . ' ORDER BY id DESC LIMIT %d OFFSET %d';
		$params[] = $limit;
		$params[] = $offset;
		return $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A ) ?: [];
	}

	public function get( int $id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . DB::table( 'franchises' ) . ' WHERE id = %d', $id ), ARRAY_A );
		return $row ?: null;
	}

	public function update( int $id, array $data ): bool {
		global $wpdb;
		$allowed = [ 'applicant_name', 'phone', 'email', 'city', 'message', 'status' ];
		$update  = [];
		$format  = [];
		foreach ( $allowed as $k ) {
			if ( ! array_key_exists( $k, $data ) ) {
				continue;
			}
			$update[ $k ] = sanitize_text_field( (string) $data[ $k ] );
			$format[]    = '%s';
		}
		if ( empty( $update ) ) {
			return false;
		}
		return false !== $wpdb->update( DB::table( 'franchises' ), $update, [ 'id' => $id ], $format, [ '%d' ] );
	}

	public function delete( int $id ): bool {
		global $wpdb;
		return false !== $wpdb->delete( DB::table( 'franchises' ), [ 'id' => $id ], [ '%d' ] );
	}

	public static function decode_meta( $value ): array {
		if ( is_array( $value ) ) {
			return $value;
		}
		if ( ! is_string( $value ) || '' === trim( $value ) ) {
			return [];
		}
		$decoded = json_decode( $value, true );
		return is_array( $decoded ) ? $decoded : [];
	}

	public static function agreement_print_url( int $id, string $token = '' ): string {
		$args = [ 'rks_frn_agreement' => max( 0, $id ) ];
		if ( '' !== $token ) {
			$args['rks_frn_token'] = $token;
		}
		return add_query_arg( $args, home_url( '/franchise-register/' ) );
	}

	private function agreement_sections(): array {
		return [
			[
				'title' => 'Parties & Purpose',
				'items' => [
					'This agreement records the applicant intent to operate a Rishikul Shiksha franchise centre after verification and approval by the head office.',
					'Final franchise rights are granted only after admin approval, required fee clearance, site verification, and branch creation in the official dashboard.',
				],
			],
			[
				'title' => 'Submitted Information',
				'items' => [
					'The applicant confirms that all personal, Aadhaar, location, premises, contact, and business details submitted in this onboarding form are true and valid.',
					'False, incomplete, forged, or misleading information may lead to rejection, suspension, or cancellation of franchise onboarding.',
				],
			],
			[
				'title' => 'Aadhaar & Document Consent',
				'items' => [
					'The applicant confirms that the Aadhaar document uploaded belongs to the applicant or authorized owner signing this form.',
					'Rishikul Shiksha may use submitted documents only for identity verification, franchise due diligence, agreement records, and administrative compliance.',
				],
			],
			[
				'title' => 'Brand & Centre Usage',
				'items' => [
					'Use of the Rishikul Shiksha name, logo, certificates, course material, software, and dashboard is permitted only after formal approval.',
					'The franchise partner must follow brand guidelines, academic standards, pricing policies, certificate rules, and operational instructions issued by the head office.',
				],
			],
			[
				'title' => 'Fees & Refund Policy',
				'items' => [
					'Franchise processing, approval, setup, brand licensing, training, software, and related service fees are non-refundable once paid.',
					'Any pending dues, renewal charges, software charges, certificate charges, or operational charges must be paid within the timeline communicated by the head office.',
				],
			],
			[
				'title' => 'Centre Operations',
				'items' => [
					'The partner is responsible for local premises, staff, utilities, computers, internet, student counselling, admission support, and daily centre discipline.',
					'Student records, fees, attendance, courses, exams, certificates, leads, and branch operations must be managed through the official dashboard where applicable.',
				],
			],
			[
				'title' => 'Quality, Exams & Certificates',
				'items' => [
					'The partner must not issue unauthorized certificates, alter certificate formats, manipulate exam attempts, or misrepresent student results.',
					'Rishikul Shiksha may audit branch records, student documents, exam records, payments, certificates, and operational quality at any time.',
				],
			],
			[
				'title' => 'Data & Confidentiality',
				'items' => [
					'Course content, dashboard access, student data, pricing, training material, internal documents, and business processes must be kept confidential.',
					'The partner must not share login credentials, export student data for unauthorized use, copy proprietary material, or misuse brand assets.',
				],
			],
			[
				'title' => 'Approval, Suspension & Termination',
				'items' => [
					'Rishikul Shiksha may reject onboarding if the location, documents, applicant profile, payment, or compliance checks are not satisfactory.',
					'The head office may suspend or terminate franchise access for misconduct, non-payment, misuse of brand, false promises to students, unauthorized certification, or policy violation.',
				],
			],
			[
				'title' => 'Declaration',
				'items' => [
					'I confirm that I have read and understood this Franchise Agreement. I agree to follow all rules, policies, operational instructions, and non-refundable fee terms of Rishikul Shiksha.',
				],
			],
		];
	}

	private function render_agreement_sections_html( string $context = 'modal' ): string {
		ob_start();
		?>
		<div class="rks-frn-agreement rks-frn-agreement--<?php echo esc_attr( $context ); ?>">
			<?php foreach ( $this->agreement_sections() as $index => $section ) : ?>
				<section class="rks-frn-agreement__section">
					<h3><span><?php echo esc_html( str_pad( (string) ( $index + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span><?php echo esc_html( (string) $section['title'] ); ?></h3>
					<ul>
						<?php foreach ( (array) $section['items'] as $item ) : ?>
							<li><?php echo esc_html( (string) $item ); ?></li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endforeach; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	private function render_printable_agreement( int $id, string $token = '' ): string {
		global $wpdb;
		$row = $id > 0 ? $this->get( $id ) : null;
		if ( ! $row ) {
			return '<div class="rks-auth-alert rks-auth-alert--error">Agreement PDF is not available for this franchise record.</div>';
		}

		$meta      = self::decode_meta( $row['meta'] ?? '' );
		$agreement = is_array( $meta['agreement'] ?? null ) ? $meta['agreement'] : [];
		$stored_token = (string) ( $agreement['print_token'] ?? '' );
		$can_view = current_user_can( 'rks_approve_franchise' ) || ( '' !== $token && '' !== $stored_token && hash_equals( $stored_token, $token ) );
		if ( ! $can_view || 'onboarding' !== (string) ( $meta['form_type'] ?? '' ) ) {
			return '<div class="rks-auth-alert rks-auth-alert--error">Agreement PDF is not available for this franchise record.</div>';
		}

		$settings = (array) get_option( 'rks_settings', [] );
		$brand    = (string) ( $settings['site_brand'] ?? 'Rishikul Shiksha' );
		$location = is_array( $meta['location'] ?? null ) ? $meta['location'] : [];
		$premises = is_array( $meta['premises'] ?? null ) ? $meta['premises'] : [];
		$created  = ! empty( $row['created_at'] ) ? mysql2date( 'd M Y', (string) $row['created_at'] ) : date_i18n( 'd M Y' );
		$print_url = self::agreement_print_url( $id, $token );
		$back_url = current_user_can( 'rks_approve_franchise' )
			? admin_url( 'admin.php?page=rks-franchises&view=' . $id )
			: home_url( '/franchise-register/' );
		$back_label = current_user_can( 'rks_approve_franchise' ) ? 'Back to Franchise' : 'Back to Register';
		$centre_address = trim( (string) ( $location['address_line1'] ?? '' ) . ( ! empty( $location['landmark'] ) ? ', ' . (string) $location['landmark'] : '' ), ', ' );
		$district_state = trim( (string) ( $location['district'] ?? '' ) . ', ' . (string) ( $location['state'] ?? '' ), ', ' );
		$accepted_at    = (string) ( $agreement['accepted_at'] ?? $row['created_at'] );
		$accepted_date  = $accepted_at ? mysql2date( 'd M Y', $accepted_at ) : $created;
		$agreement_no   = 'RKS-FRN-' . str_pad( (string) $id, 5, '0', STR_PAD_LEFT );

		ob_start();
		?>
		<div class="rks-frn-print-toolbar no-print">
			<a class="rks-btn rks-btn--ghost" href="<?php echo esc_url( $back_url ); ?>"><?php echo esc_html( $back_label ); ?></a>
			<button class="rks-btn rks-btn--primary" type="button" onclick="window.print()">Download / Save PDF</button>
		</div>

		<div class="rks-frn-print rks-frn-print--legal" data-print-url="<?php echo esc_url( $print_url ); ?>">
			<section class="rks-frn-legal-sheet">
				<header class="rks-frn-legal-head">
					<div class="rks-frn-legal-brand"><?php echo esc_html( $brand ); ?></div>
					<h1>Franchise Agreement</h1>
					<p>Agreement No: <strong><?php echo esc_html( $agreement_no ); ?></strong> | Application ID: <strong>#<?php echo (int) $row['id']; ?></strong> | Date: <strong><?php echo esc_html( $accepted_date ); ?></strong></p>
				</header>

				<p class="rks-frn-legal-intro">
					This Franchise Agreement ("Agreement") is made and accepted on <strong><?php echo esc_html( $accepted_date ); ?></strong> between the parties mentioned below for the proposed operation of a Rishikul Shiksha franchise centre.
				</p>

				<div class="rks-frn-legal-parties">
					<div>
						<h2>First Party</h2>
						<p><strong><?php echo esc_html( $brand ); ?></strong>, referred to as the Head Office / Brand Owner.</p>
					</div>
					<div>
						<h2>Second Party</h2>
						<p><strong><?php echo esc_html( (string) $row['applicant_name'] ); ?></strong>, referred to as the Franchise Applicant / Proposed Partner.</p>
					</div>
				</div>

				<table class="rks-frn-legal-table">
					<tbody>
						<tr><th>Applicant Name</th><td><?php echo esc_html( (string) $row['applicant_name'] ); ?></td><th>Mobile</th><td><?php echo esc_html( (string) $row['phone'] ); ?></td></tr>
						<tr><th>Email</th><td><?php echo esc_html( (string) $row['email'] ); ?></td><th>City</th><td><?php echo esc_html( (string) $row['city'] ); ?></td></tr>
						<tr><th>Proposed Centre</th><td colspan="3"><?php echo esc_html( (string) ( $meta['branch_name'] ?? '' ) ); ?></td></tr>
						<tr><th>Centre Address</th><td colspan="3"><?php echo esc_html( $centre_address ); ?></td></tr>
						<tr><th>District / State</th><td><?php echo esc_html( $district_state ); ?></td><th>Pincode</th><td><?php echo esc_html( (string) ( $location['pincode'] ?? '' ) ); ?></td></tr>
						<tr><th>Premises</th><td><?php echo esc_html( ucfirst( (string) ( $premises['type'] ?? '' ) ) ); ?></td><th>Area</th><td><?php echo ! empty( $premises['area_sqft'] ) ? esc_html( (string) ( (int) $premises['area_sqft'] . ' sq ft' ) ) : ''; ?></td></tr>
					</tbody>
				</table>

				<div class="rks-frn-legal-recital">
					<h2>Recitals</h2>
					<p>Whereas the First Party operates education, training, certification, counselling and technology-enabled learning services under the Rishikul Shiksha brand; and whereas the Second Party has submitted details for consideration as a franchise partner; both parties agree that final franchise rights shall be subject to verification, approval, payment clearance and compliance with the following clauses.</p>
				</div>

				<div class="rks-frn-legal-clauses">
					<?php foreach ( $this->agreement_sections() as $index => $section ) : ?>
						<section>
							<h2><?php echo esc_html( ( $index + 1 ) . '. ' . (string) $section['title'] ); ?></h2>
							<?php foreach ( (array) $section['items'] as $item_index => $item ) : ?>
								<p><span><?php echo esc_html( ( $index + 1 ) . '.' . ( $item_index + 1 ) ); ?></span><?php echo esc_html( (string) $item ); ?></p>
							<?php endforeach; ?>
						</section>
					<?php endforeach; ?>
				</div>

				<div class="rks-frn-legal-declaration">
					<p>I, <strong><?php echo esc_html( (string) ( $agreement['name'] ?? $row['applicant_name'] ) ); ?></strong>, accept this Franchise Agreement at <strong><?php echo esc_html( (string) ( $agreement['place'] ?? '' ) ); ?></strong>. I confirm that the submitted information is true and that I agree to the non-refundable fee policy, brand rules, verification process and operational terms of <?php echo esc_html( $brand ); ?>.</p>
				</div>

				<div class="rks-frn-legal-signatures">
					<div>
						<span></span>
						<strong>Second Party Signature</strong>
						<small><?php echo esc_html( (string) $row['applicant_name'] ); ?></small>
					</div>
					<div>
						<span></span>
						<strong>First Party Authorized Signatory</strong>
						<small><?php echo esc_html( $brand ); ?></small>
					</div>
				</div>
			</section>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	private function store_uploaded_document( string $field ): array {
		if ( empty( $_FILES[ $field ] ) || ! is_array( $_FILES[ $field ] ) ) {
			return [ 'ok' => false, 'error' => 'upload_missing' ];
		}

		$file = $_FILES[ $field ];
		if ( (int) ( $file['error'] ?? UPLOAD_ERR_NO_FILE ) === UPLOAD_ERR_NO_FILE ) {
			return [ 'ok' => false, 'error' => 'upload_missing' ];
		}
		if ( (int) ( $file['error'] ?? UPLOAD_ERR_OK ) !== UPLOAD_ERR_OK ) {
			return [ 'ok' => false, 'error' => 'upload' ];
		}
		if ( (int) ( $file['size'] ?? 0 ) > 5 * 1024 * 1024 ) {
			return [ 'ok' => false, 'error' => 'upload_size' ];
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';

		$allowed = [
			'pdf'  => 'application/pdf',
			'jpg'  => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'png'  => 'image/png',
			'webp' => 'image/webp',
		];
		$check = wp_check_filetype_and_ext( (string) $file['tmp_name'], (string) $file['name'], $allowed );
		if ( empty( $check['ext'] ) || empty( $check['type'] ) ) {
			return [ 'ok' => false, 'error' => 'upload_type' ];
		}

		$uploads = wp_upload_dir();
		if ( ! empty( $uploads['error'] ) ) {
			return [ 'ok' => false, 'error' => 'upload' ];
		}

		$private_base = trailingslashit( (string) $uploads['basedir'] ) . 'rksiksha-private';
		if ( ! wp_mkdir_p( $private_base ) ) {
			return [ 'ok' => false, 'error' => 'upload' ];
		}
		if ( ! file_exists( trailingslashit( $private_base ) . '.htaccess' ) ) {
			@file_put_contents( trailingslashit( $private_base ) . '.htaccess', "Deny from all\n" );
		}
		if ( ! file_exists( trailingslashit( $private_base ) . 'index.php' ) ) {
			@file_put_contents( trailingslashit( $private_base ) . 'index.php', "<?php\n// Silence is golden.\n" );
		}

		$target_dir = trailingslashit( $private_base ) . 'franchise/' . gmdate( 'Y/m' );
		if ( ! wp_mkdir_p( $target_dir ) ) {
			return [ 'ok' => false, 'error' => 'upload' ];
		}

		$filename = 'aadhar-card-' . wp_generate_uuid4() . '.' . strtolower( (string) $check['ext'] );
		$target   = trailingslashit( $target_dir ) . $filename;

		if ( ! move_uploaded_file( (string) $file['tmp_name'], $target ) ) {
			return [ 'ok' => false, 'error' => 'upload' ];
		}

		@chmod( $target, 0640 );

		return [
			'ok'       => true,
			'document' => [
				'path'          => wp_normalize_path( $target ),
				'original_name' => sanitize_file_name( (string) $file['name'] ),
				'mime'          => (string) $check['type'],
				'size'          => (int) $file['size'],
				'uploaded_at'   => current_time( 'mysql' ),
			],
		];
	}

	private function request_ip(): string {
		$keys = [ 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' ];
		foreach ( $keys as $key ) {
			$value = sanitize_text_field( wp_unslash( (string) ( $_SERVER[ $key ] ?? '' ) ) );
			if ( '' === $value ) {
				continue;
			}
			$parts = array_map( 'trim', explode( ',', $value ) );
			return substr( (string) $parts[0], 0, 45 );
		}
		return '';
	}

	private function redirect_success( string $message, string $anchor, array $extra_args = [] ): void {
		$url = wp_get_referer() ?: get_permalink();
		$url = remove_query_arg( [ 'rks_franchise_err', 'rks_franchise_msg', 'rks_frn_ref', 'rks_frn_token' ], $url );
		$args = array_merge( [ 'rks_franchise_msg' => $message ], $extra_args );
		wp_safe_redirect( add_query_arg( $args, $url ) . $anchor );
		exit;
	}

	private function unique_login( string $base ): string {
		$try = $base; $i = 1;
		while ( username_exists( $try ) ) { $try = $base . $i++; if ( $i > 99 ) { $try = $base . wp_rand( 100, 999 ); break; } }
		return $try;
	}

	private function error_message( string $code ): string {
		return [
			'nonce'         => 'Security check failed.',
			'missing'       => 'Please fill required fields.',
			'invalid_email' => 'Enter a valid email.',
			'terms'         => 'Please accept the terms.',
			'aadhar'        => 'Enter a valid 12 digit Aadhaar number.',
			'pincode'       => 'Enter a valid 6 digit pincode.',
			'upload_missing'=> 'Please upload the Aadhaar card document.',
			'upload_size'   => 'Uploaded document must be below 5MB.',
			'upload_type'   => 'Upload Aadhaar as PDF, JPG, PNG, or WebP only.',
			'upload'        => 'Document upload failed. Please try again.',
			'generic'       => 'Something went wrong.',
		][ $code ] ?? 'Something went wrong.';
	}

	private function redirect_back( string $code, string $anchor = '#apply' ): void {
		$url = wp_get_referer() ?: home_url( '/coaching/' );
		$url = remove_query_arg( [ 'rks_franchise_err', 'rks_franchise_msg' ], $url );
		wp_safe_redirect( add_query_arg( 'rks_franchise_err', $code, $url ) . $anchor );
		exit;
	}
}
