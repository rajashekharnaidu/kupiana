<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Choose a new password from a valid reset link.
 *
 * @var string $token
 */

$min_length = (int) array_get(app_config('security', array()), 'password_min_length', 8);

$auth_title    = 'Choose a new password';
$auth_subtitle = 'Pick something you have not used here before.';
$auth_footer   = '<a href="'.site_url('login').'">Back to sign in</a>';

$this->load->view('auth/_open', get_defined_vars());
?>

<?php echo form_open('reset-password', array('class' => 'needs-validation', 'novalidate' => 'novalidate')); ?>

	<?php // Carried in the body so the token leaves the URL bar after submit. ?>
	<input type="hidden" name="token" value="<?php echo html_escape($token); ?>">

	<div class="mb-3">
		<label class="form-label" for="password">New password <span class="required-mark">*</span></label>
		<div class="input-group">
			<input type="password" id="password" name="password"
			       class="<?php echo form_error_class('password'); ?>"
			       required minlength="<?php echo $min_length; ?>" maxlength="72"
			       autocomplete="new-password" autofocus>
			<button class="btn btn-outline-secondary" type="button"
			        data-toggle-password="password" aria-label="Show password">
				<i class="fa-regular fa-eye"></i>
			</button>
			<div class="invalid-feedback">Use at least <?php echo $min_length; ?> characters.</div>
		</div>
		<div class="form-text">At least <?php echo $min_length; ?> characters.</div>
		<?php echo field_error('password'); ?>
	</div>

	<div class="mb-4">
		<label class="form-label" for="password_confirm">
			Confirm new password <span class="required-mark">*</span>
		</label>
		<input type="password" id="password_confirm" name="password_confirm"
		       class="<?php echo form_error_class('password_confirm'); ?>"
		       required minlength="<?php echo $min_length; ?>" maxlength="72"
		       autocomplete="new-password">
		<div class="invalid-feedback">Please confirm your new password.</div>
		<?php echo field_error('password_confirm'); ?>
	</div>

	<button type="submit" class="btn btn-primary w-100 py-2">
		<i class="fa-solid fa-key me-2"></i>Update password
	</button>

<?php echo form_close(); ?>

<div class="alert alert-light border mt-4 mb-0 small text-muted">
	<i class="fa-solid fa-shield-halved me-1"></i>
	Changing your password signs you out of every remembered device.
</div>

<?php $this->load->view('auth/_close', get_defined_vars()); ?>
