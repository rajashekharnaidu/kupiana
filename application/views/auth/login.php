<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sign-in form.
 *
 * @var string $site_name
 */

$redirect = $this->input->get('redirect', TRUE);
$action   = 'login'.($redirect ? '?redirect='.rawurlencode($redirect) : '');

$auth_title    = 'Welcome back';
$auth_subtitle = 'Sign in to continue to '.html_escape($site_name).'.';
$auth_footer   = 'New to '.html_escape($site_name).'? '
	.'<a href="'.site_url('register').'">Create an account</a>';

$this->load->view('auth/_open', get_defined_vars());
?>

<?php echo form_open($action, array('class' => 'needs-validation', 'novalidate' => 'novalidate')); ?>

	<div class="mb-3">
		<label class="form-label" for="email">Email address <span class="required-mark">*</span></label>
		<input type="email" id="email" name="email"
		       class="<?php echo form_error_class('email'); ?>"
		       value="<?php echo set_value('email'); ?>"
		       required autocomplete="email" autofocus placeholder="you@example.com">
		<div class="invalid-feedback">Enter a valid email address.</div>
		<?php echo field_error('email'); ?>
	</div>

	<div class="mb-3">
		<div class="d-flex justify-content-between align-items-center">
			<label class="form-label" for="password">Password <span class="required-mark">*</span></label>
			<a href="<?php echo site_url('forgot-password'); ?>" class="small">Forgot?</a>
		</div>
		<div class="input-group">
			<input type="password" id="password" name="password"
			       class="<?php echo form_error_class('password'); ?>"
			       required autocomplete="current-password" placeholder="Enter your password">
			<button class="btn btn-outline-secondary" type="button"
			        data-toggle-password="password" aria-label="Show password">
				<i class="fa-regular fa-eye"></i>
			</button>
			<div class="invalid-feedback">Password is required.</div>
		</div>
		<?php echo field_error('password'); ?>
	</div>

	<div class="form-check mb-4">
		<input class="form-check-input" type="checkbox" name="remember" value="1" id="remember"
		       <?php echo set_checkbox('remember', '1'); ?>>
		<label class="form-check-label" for="remember">Keep me signed in for 30 days</label>
	</div>

	<button type="submit" class="btn btn-primary w-100 py-2">
		<i class="fa-solid fa-right-to-bracket me-2"></i>Sign in
	</button>

<?php echo form_close(); ?>

<div class="position-relative text-center my-4">
	<hr class="m-0">
	<span class="position-absolute top-50 start-50 translate-middle px-3 small text-muted"
	      style="background: var(--k-surface);">or</span>
</div>

<a href="<?php echo site_url('login/otp'); ?>" class="btn btn-outline-secondary w-100">
	<i class="fa-solid fa-shield-halved me-2"></i>Sign in with a one-time code
</a>

<?php $this->load->view('auth/_close', get_defined_vars()); ?>
