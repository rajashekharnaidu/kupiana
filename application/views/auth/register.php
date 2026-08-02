<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Customer registration form.
 *
 * @var string $site_name
 */

$min_length = (int) array_get(app_config('security', array()), 'password_min_length', 8);

$auth_title    = 'Create your account';
$auth_subtitle = 'Join '.html_escape($site_name).' — it only takes a minute.';
$auth_width    = 'col-lg-6';
$auth_footer   = 'Already have an account? <a href="'.site_url('login').'">Sign in</a>';

$this->load->view('auth/_open', get_defined_vars());
?>

<?php echo form_open('register', array('class' => 'needs-validation', 'novalidate' => 'novalidate')); ?>

	<div class="row g-3">
		<div class="col-md-6">
			<label class="form-label" for="first_name">First name <span class="required-mark">*</span></label>
			<input type="text" id="first_name" name="first_name"
			       class="<?php echo form_error_class('first_name'); ?>"
			       value="<?php echo set_value('first_name'); ?>"
			       required maxlength="100" autocomplete="given-name" autofocus>
			<div class="invalid-feedback">First name is required.</div>
			<?php echo field_error('first_name'); ?>
		</div>

		<div class="col-md-6">
			<label class="form-label" for="last_name">Last name</label>
			<input type="text" id="last_name" name="last_name"
			       class="<?php echo form_error_class('last_name'); ?>"
			       value="<?php echo set_value('last_name'); ?>"
			       maxlength="100" autocomplete="family-name">
			<?php echo field_error('last_name'); ?>
		</div>

		<div class="col-12">
			<label class="form-label" for="email">Email address <span class="required-mark">*</span></label>
			<input type="email" id="email" name="email"
			       class="<?php echo form_error_class('email'); ?>"
			       value="<?php echo set_value('email'); ?>"
			       required maxlength="191" autocomplete="email" placeholder="you@example.com">
			<div class="invalid-feedback">Enter a valid email address.</div>
			<?php echo field_error('email'); ?>
		</div>

		<div class="col-12">
			<label class="form-label" for="phone">Mobile number</label>
			<div class="input-group">
				<span class="input-group-text">+91</span>
				<input type="tel" id="phone" name="phone"
				       class="<?php echo form_error_class('phone'); ?>"
				       value="<?php echo set_value('phone'); ?>"
				       pattern="[0-9]{10}" maxlength="10" autocomplete="tel-national"
				       placeholder="10-digit number">
				<div class="invalid-feedback">Enter a valid 10-digit mobile number.</div>
			</div>
			<?php echo field_error('phone'); ?>
		</div>

		<div class="col-md-6">
			<label class="form-label" for="password">Password <span class="required-mark">*</span></label>
			<div class="input-group">
				<input type="password" id="password" name="password"
				       class="<?php echo form_error_class('password'); ?>"
				       required minlength="<?php echo $min_length; ?>" maxlength="72"
				       autocomplete="new-password">
				<button class="btn btn-outline-secondary" type="button"
				        data-toggle-password="password" aria-label="Show password">
					<i class="fa-regular fa-eye"></i>
				</button>
				<div class="invalid-feedback">
					Use at least <?php echo $min_length; ?> characters.
				</div>
			</div>
			<div class="form-text">At least <?php echo $min_length; ?> characters.</div>
			<?php echo field_error('password'); ?>
		</div>

		<div class="col-md-6">
			<label class="form-label" for="password_confirm">
				Confirm password <span class="required-mark">*</span>
			</label>
			<input type="password" id="password_confirm" name="password_confirm"
			       class="<?php echo form_error_class('password_confirm'); ?>"
			       required minlength="<?php echo $min_length; ?>" maxlength="72"
			       autocomplete="new-password">
			<div class="invalid-feedback">Please confirm your password.</div>
			<?php echo field_error('password_confirm'); ?>
		</div>

		<div class="col-12">
			<div class="form-check">
				<input class="form-check-input" type="checkbox" name="terms" value="1" id="terms"
				       required <?php echo set_checkbox('terms', '1'); ?>>
				<label class="form-check-label small" for="terms">
					I agree to the <a href="<?php echo site_url('terms'); ?>" target="_blank">Terms of Use</a>
					and <a href="<?php echo site_url('privacy-policy'); ?>" target="_blank">Privacy Policy</a>.
				</label>
				<div class="invalid-feedback">You must accept the terms to continue.</div>
			</div>
			<?php echo field_error('terms'); ?>
		</div>

		<div class="col-12">
			<button type="submit" class="btn btn-primary w-100 py-2">
				<i class="fa-solid fa-user-plus me-2"></i>Create account
			</button>
		</div>
	</div>

<?php echo form_close(); ?>

<?php $this->load->view('auth/_close', get_defined_vars()); ?>
