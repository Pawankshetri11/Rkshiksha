<?php
/** @var \WP_User $user */
if ( ! defined( 'ABSPATH' ) ) exit;

$first    = (string) get_user_meta( $user->ID, 'first_name', true );
$last     = (string) get_user_meta( $user->ID, 'last_name', true );
$phone    = (string) get_user_meta( $user->ID, 'rks_phone', true );
$photo_id = (int)    get_user_meta( $user->ID, 'rks_photo_id', true );
$photo_url = $photo_id ? wp_get_attachment_image_url( $photo_id, 'medium' ) : '';
$initial  = strtoupper( substr( trim( $user->display_name ?: $user->user_login ), 0, 1 ) );

$msg = isset( $_GET['rks_msg'] ) ? sanitize_key( wp_unslash( (string) $_GET['rks_msg'] ) ) : '';
$msg_text = [
	'saved'           => [ 'success', 'Profile updated successfully.' ],
	'photo_saved'     => [ 'success', 'Photo uploaded — it will appear on your ID card.' ],
	'photo_removed'   => [ 'success', 'Photo removed.' ],
	'password_changed'=> [ 'success', 'Password changed successfully.' ],
	'wrong_current'   => [ 'error',   'Current password is incorrect.' ],
	'weak_password'   => [ 'error',   'New password must be at least 8 characters.' ],
	'mismatch'        => [ 'error',   'New passwords do not match.' ],
	'no_file'         => [ 'error',   'Please choose an image to upload.' ],
	'bad_type'        => [ 'error',   'Only JPG, PNG or WEBP images are allowed.' ],
	'too_large'       => [ 'error',   'Photo must be under 3 MB.' ],
	'upload_failed'   => [ 'error',   'Upload failed. Please try again.' ],
][ $msg ] ?? null;
?>

<header class="rks-dash__header">
	<div>
		<h1>My Profile</h1>
		<p>Update your personal info, profile photo, or change your password.</p>
	</div>
</header>

<?php if ( $msg_text ) : ?>
	<div class="rks-dash__notice rks-dash__notice--<?php echo esc_attr( $msg_text[0] ); ?>"><?php echo esc_html( $msg_text[1] ); ?></div>
<?php endif; ?>

<div class="rks-dash__panel">
	<h2 style="margin-top:0;color:var(--rks-teal);font-size:18px;">Profile Photo</h2>
	<p class="rks-photo-help">This photo appears on your ID card and certificate. Use a clear front-facing photo (passport style). JPG / PNG / WEBP — max 3 MB.</p>

	<div class="rks-photo-upload">
		<div class="rks-photo-upload__preview" style="width:120px;height:140px;flex:0 0 120px;overflow:hidden;">
			<?php if ( $photo_url ) : ?>
				<img src="<?php echo esc_url( $photo_url ); ?>" alt="Profile photo" style="width:100%;height:100%;object-fit:cover;display:block;">
			<?php else : ?>
				<span class="rks-photo-upload__initial"><?php echo esc_html( $initial ?: 'U' ); ?></span>
			<?php endif; ?>
		</div>

		<div class="rks-photo-upload__forms">
			<form method="post" enctype="multipart/form-data" class="rks-photo-upload__form">
				<?php wp_nonce_field( 'rks_photo_upload' ); ?>
				<input type="hidden" name="rks_action" value="photo_upload">
				<label for="rks-photo-input" class="rks-photo-upload__label">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
					<span id="rks-photo-label-text">Choose Photo</span>
				</label>
				<input type="file" id="rks-photo-input" name="photo" accept="image/jpeg,image/png,image/webp" required onchange="var t=document.getElementById('rks-photo-label-text'); if(t&&this.files[0]) t.textContent = this.files[0].name.length>22 ? this.files[0].name.substr(0,20)+'…' : this.files[0].name;">
				<button type="submit" class="rks-btn rks-btn--primary"><?php echo $photo_url ? 'Replace Photo' : 'Upload Photo'; ?></button>
			</form>

			<?php if ( $photo_url ) : ?>
				<form method="post" class="rks-photo-upload__remove">
					<?php wp_nonce_field( 'rks_photo_remove' ); ?>
					<input type="hidden" name="rks_action" value="photo_remove">
					<button type="submit" class="rks-btn rks-btn--ghost" onclick="return confirm('Remove your profile photo? The ID card will show your initial again.');">Remove Photo</button>
				</form>
			<?php endif; ?>
		</div>
	</div>
</div>

<div class="rks-dash__panel">
	<form method="post" class="rks-dash__form">
		<?php wp_nonce_field( 'rks_profile_save' ); ?>
		<input type="hidden" name="rks_action" value="profile_save">

		<h2 style="margin-top:0;color:var(--rks-teal);font-size:18px;">Personal Information</h2>

		<div class="rks-form-grid">
			<div class="rks-form-row">
				<label>First Name</label>
				<input type="text" name="first_name" value="<?php echo esc_attr( $first ); ?>" required>
			</div>
			<div class="rks-form-row">
				<label>Last Name</label>
				<input type="text" name="last_name" value="<?php echo esc_attr( $last ); ?>">
			</div>
		</div>
		<div class="rks-form-row">
			<label>Email <small>(read-only)</small></label>
			<input type="email" value="<?php echo esc_attr( $user->user_email ); ?>" readonly>
		</div>
		<div class="rks-form-row">
			<label>Phone</label>
			<input type="tel" name="phone" value="<?php echo esc_attr( $phone ); ?>">
		</div>

		<button type="submit" class="rks-btn rks-btn--primary">Save Profile</button>
	</form>
</div>

<div class="rks-dash__panel">
	<form method="post" class="rks-dash__form">
		<?php wp_nonce_field( 'rks_password_change' ); ?>
		<input type="hidden" name="rks_action" value="password_change">

		<h2 style="margin-top:0;color:var(--rks-teal);font-size:18px;">Change Password</h2>

		<div class="rks-form-row">
			<label>Current Password</label>
			<input type="password" name="current_password" required autocomplete="current-password">
		</div>
		<div class="rks-form-grid">
			<div class="rks-form-row">
				<label>New Password</label>
				<input type="password" name="new_password" required minlength="8" autocomplete="new-password">
			</div>
			<div class="rks-form-row">
				<label>Confirm New Password</label>
				<input type="password" name="new_password2" required minlength="8" autocomplete="new-password">
			</div>
		</div>

		<button type="submit" class="rks-btn rks-btn--primary">Change Password</button>
	</form>
</div>
