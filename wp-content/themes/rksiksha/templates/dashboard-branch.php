<?php
/**
 * Dashboard — Branch Profile editor
 *
 * @var array|null $branch
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! $branch ) {
	echo '<div class="rks-dash__panel"><div class="rks-dash__empty">No branch linked to your account. Contact super admin.</div></div>';
	return;
}
?>

<header class="rks-dash__header">
	<div>
		<h1>Branch Profile</h1>
		<p>Update your branch's public contact information.</p>
	</div>
</header>

<div class="rks-dash__panel">
	<form method="post" class="rks-dash__form">
		<?php wp_nonce_field( 'rks_dash_branch' ); ?>
		<input type="hidden" name="rks_action" value="branch_save">

		<div class="rks-form-row">
			<label>Branch Name</label>
			<input type="text" name="name" required value="<?php echo esc_attr( (string) $branch['name'] ); ?>">
		</div>

		<div class="rks-form-grid">
			<div class="rks-form-row">
				<label>City</label>
				<input type="text" name="city" value="<?php echo esc_attr( (string) $branch['city'] ); ?>">
			</div>
			<div class="rks-form-row">
				<label>State</label>
				<input type="text" name="state" value="<?php echo esc_attr( (string) $branch['state'] ); ?>">
			</div>
		</div>

		<div class="rks-form-grid">
			<div class="rks-form-row">
				<label>Pincode</label>
				<input type="text" name="pincode" value="<?php echo esc_attr( (string) $branch['pincode'] ); ?>">
			</div>
			<div class="rks-form-row">
				<label>Phone</label>
				<input type="tel" name="phone" value="<?php echo esc_attr( (string) $branch['phone'] ); ?>">
			</div>
		</div>

		<div class="rks-form-row">
			<label>Email</label>
			<input type="email" name="email" value="<?php echo esc_attr( (string) $branch['email'] ); ?>">
		</div>

		<div class="rks-form-row">
			<label>Address</label>
			<textarea name="address" rows="3"><?php echo esc_textarea( (string) $branch['address'] ); ?></textarea>
		</div>

		<button type="submit" class="rks-btn rks-btn--primary">Save Changes</button>
	</form>

	<dl class="rks-dash__meta">
		<dt>Branch ID</dt><dd>#<?php echo (int) $branch['id']; ?></dd>
		<dt>Type</dt><dd><?php echo esc_html( ucfirst( (string) $branch['type'] ) ); ?></dd>
		<dt>Status</dt><dd><span class="rks-status rks-status--<?php echo esc_attr( (string) $branch['status'] ); ?>"><?php echo esc_html( (string) $branch['status'] ); ?></span></dd>
		<dt>Created</dt><dd><?php echo esc_html( (string) $branch['created_at'] ); ?></dd>
	</dl>
</div>
