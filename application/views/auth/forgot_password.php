<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Request a password reset link.
 */

$auth_title    = 'Forgot your password?';
$auth_subtitle = 'Enter your email address and we will send you a link to choose a new one.';
$auth_footer   = 'Remembered it? <a href="'.site_url('login').'">Back to sign in</a>';

$this->load->view('auth/_open', get_defined_vars());
?>

<?php echo form_open('forgot-password', array('class' => 'needs-validation', 'novalidate' => 'novalidate')); ?>

	<div class="mb-4">
		<label class="form-label" for="email">Email address <span class="required-mark">*</span></label>
		<input type="email" id="email" name="email"
		       class="<?php echo form_error_class('email'); ?>"
		       value="<?php echo set_value('email'); ?>"
		       required autocomplete="email" autofocus placeholder="you@example.com">
		<div class="invalid-feedback">Enter a valid email address.</div>
		<?php echo field_error('email'); ?>
	</div>

	<button type="submit" class="btn btn-primary w-100 py-2">
		<i class="fa-solid fa-paper-plane me-2"></i>Send reset link
	</button>

<?php echo form_close(); ?>

<div class="alert alert-light border mt-4 mb-0 small text-muted">
	<i class="fa-solid fa-circle-info me-1"></i>
	For your security, the link expires in
	<?php echo (int) array_get(app_config('security', array()), 'reset_token_ttl_min', 60); ?> minutes
	and can only be used once.
</div>

<?php $this->load->view('auth/_close', get_defined_vars()); ?>
