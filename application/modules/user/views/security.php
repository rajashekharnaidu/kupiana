<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="py-4 border-bottom bg-light"><div class="container"><h1 class="h3 mb-1">Security</h1><p class="text-muted mb-0">Change your password and review remembered devices.</p></div></section>
<section class="py-5"><div class="container">
	<div class="row g-4">
		<div class="col-lg-5">
			<form method="post" action="<?php echo site_url('account/security'); ?>" class="card h-100">
				<input type="hidden" name="<?php echo html_escape($this->security->get_csrf_token_name()); ?>" value="<?php echo html_escape($this->security->get_csrf_hash()); ?>">
				<div class="card-body">
					<h2 class="h5 mb-3">Change Password</h2>
					<label class="form-label">Current password</label><input class="form-control mb-3" type="password" name="current_password" autocomplete="current-password" required>
					<label class="form-label">New password</label><input class="form-control mb-3" type="password" name="new_password" autocomplete="new-password" minlength="8" required>
					<label class="form-label">Confirm password</label><input class="form-control" type="password" name="confirm_password" autocomplete="new-password" minlength="8" required>
					<p class="small text-muted mt-3 mb-0">Changing your password revokes remembered devices. Your current browser session stays active.</p>
				</div>
				<div class="card-footer bg-transparent text-end"><button class="btn btn-primary" type="submit">Update Password</button></div>
			</form>
		</div>
		<div class="col-lg-7">
			<div class="card h-100"><div class="card-body">
				<h2 class="h5 mb-3">Remembered Devices</h2>
				<?php if (empty($sessions)): ?>
					<p class="text-muted mb-0">No remembered devices are active right now.</p>
				<?php else: ?>
					<div class="list-group list-group-flush">
						<?php foreach ($sessions as $session): ?>
							<div class="list-group-item px-0 d-flex justify-content-between gap-3">
								<div>
									<div class="fw-semibold"><?php echo html_escape($session->user_agent ?: 'Unknown device'); ?></div>
									<div class="small text-muted">IP <?php echo html_escape($session->ip_address ?: 'n/a'); ?> · Last used <?php echo format_date($session->last_used_at); ?> · Expires <?php echo format_date($session->expires_at); ?></div>
								</div>
								<form method="post" action="<?php echo site_url('account/sessions/revoke/'.$session->id); ?>">
									<input type="hidden" name="<?php echo html_escape($this->security->get_csrf_token_name()); ?>" value="<?php echo html_escape($this->security->get_csrf_hash()); ?>">
									<button class="btn btn-sm btn-outline-danger" type="submit">Revoke</button>
								</form>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div></div>
		</div>
	</div>
</div></section>
