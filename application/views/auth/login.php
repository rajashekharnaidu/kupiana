<section class="auth-panel">
	<h1>Sign in</h1>

	<?php if ($this->session->flashdata('error')): ?>
		<p class="notice error"><?php echo html_escape($this->session->flashdata('error')); ?></p>
	<?php endif; ?>

	<?php echo validation_errors('<p class="notice error">', '</p>'); ?>

	<?php echo form_open('login'.($this->input->get('redirect') ? '?redirect='.rawurlencode($this->input->get('redirect', TRUE)) : '')); ?>
		<label>
			<span>Email</span>
			<input type="email" name="email" value="<?php echo set_value('email'); ?>" required autocomplete="email">
		</label>
		<label>
			<span>Password</span>
			<input type="password" name="password" required autocomplete="current-password">
		</label>
		<button type="submit">Login</button>
	<?php echo form_close(); ?>
</section>
