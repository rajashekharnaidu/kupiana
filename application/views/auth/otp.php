<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Passwordless sign-in.
 *
 * Two stages in one view:
 *   'request' — ask for the email address
 *   'verify'  — enter the 6-digit code
 *
 * @var string $stage
 * @var string $email
 */

$ttl = (int) array_get(app_config('security', array()), 'otp_ttl_min', 10);

$auth_title    = $stage === 'verify' ? 'Enter your code' : 'Sign in with a code';
$auth_subtitle = $stage === 'verify'
	? 'We sent a 6-digit code to <strong>'.html_escape($email).'</strong>.'
	: 'We will email you a one-time code — no password needed.';
$auth_footer   = 'Prefer a password? <a href="'.site_url('login').'">Sign in normally</a>';

$this->load->view('auth/_open', get_defined_vars());
?>

<?php if ($stage === 'verify'): ?>

	<?php echo form_open('login/otp', array('class' => 'needs-validation', 'novalidate' => 'novalidate')); ?>
		<input type="hidden" name="action" value="verify">
		<input type="hidden" name="email" value="<?php echo html_escape($email); ?>">

		<div class="mb-3">
			<label class="form-label" for="otp">6-digit code <span class="required-mark">*</span></label>
			<input type="text" id="otp" name="otp"
			       class="<?php echo form_error_class('otp'); ?> text-center"
			       style="font-size:1.5rem;letter-spacing:.5em;font-weight:600;"
			       required inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
			       autocomplete="one-time-code" autofocus placeholder="000000">
			<div class="invalid-feedback">Enter the 6-digit code from your email.</div>
			<?php echo field_error('otp'); ?>
		</div>

		<div class="form-check mb-4">
			<input class="form-check-input" type="checkbox" name="remember" value="1" id="remember">
			<label class="form-check-label" for="remember">Keep me signed in for 30 days</label>
		</div>

		<button type="submit" class="btn btn-primary w-100 py-2">
			<i class="fa-solid fa-right-to-bracket me-2"></i>Verify and sign in
		</button>
	<?php echo form_close(); ?>

	<?php echo form_open('login/otp', array('class' => 'mt-3')); ?>
		<input type="hidden" name="email" value="<?php echo html_escape($email); ?>">
		<button type="submit" class="btn btn-link w-100 p-0 small">Didn't get it? Send a new code</button>
	<?php echo form_close(); ?>

	<div class="alert alert-light border mt-4 mb-0 small text-muted">
		<i class="fa-solid fa-clock me-1"></i>
		The code expires in <?php echo $ttl; ?> minutes. After 5 wrong attempts you will need a new one.
	</div>

<?php else: ?>

	<?php echo form_open('login/otp', array('class' => 'needs-validation', 'novalidate' => 'novalidate')); ?>
		<div class="mb-4">
			<label class="form-label" for="email">Email address <span class="required-mark">*</span></label>
			<input type="email" id="email" name="email"
			       class="<?php echo form_error_class('email'); ?>"
			       value="<?php echo set_value('email', $email); ?>"
			       required autocomplete="email" autofocus placeholder="you@example.com">
			<div class="invalid-feedback">Enter a valid email address.</div>
			<?php echo field_error('email'); ?>
		</div>

		<button type="submit" class="btn btn-primary w-100 py-2">
			<i class="fa-solid fa-paper-plane me-2"></i>Email me a code
		</button>
	<?php echo form_close(); ?>

<?php endif; ?>

<?php $this->load->view('auth/_close', get_defined_vars()); ?>
