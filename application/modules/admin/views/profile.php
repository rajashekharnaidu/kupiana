<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php echo page_header('My Profile', 'Manage your account details and password.'); ?>
<div class="row g-4">
	<div class="col-lg-4">
		<div class="card h-100"><div class="card-body text-center">
			<img class="rounded-circle border mb-3" src="<?php echo upload_url($user->avatar); ?>" alt="<?php echo html_escape($user->first_name); ?>" width="96" height="96" style="object-fit: cover;">
			<h2 class="h5 mb-1"><?php echo html_escape(trim($user->first_name.' '.$user->last_name)); ?></h2>
			<p class="text-muted mb-2"><?php echo html_escape($user->email); ?></p>
			<p class="mb-2"><?php echo status_badge($user->status); ?></p>
			<p class="mb-0 small text-muted">Staff since <?php echo format_date($user->created_at); ?></p>
		</div></div>
	</div>
	<div class="col-lg-8">
		<form method="post" action="<?php echo site_url('admin/profile'); ?>" class="card mb-4" enctype="multipart/form-data">
			<input type="hidden" name="<?php echo html_escape($this->security->get_csrf_token_name()); ?>" value="<?php echo html_escape($this->security->get_csrf_hash()); ?>">
			<div class="card-body">
				<h2 class="h5 mb-3">Personal Details</h2>
				<div class="row g-3">
					<div class="col-md-6"><label class="form-label">First name</label><input class="form-control" name="first_name" value="<?php echo html_escape(set_value('first_name', $user->first_name)); ?>" required></div>
					<div class="col-md-6"><label class="form-label">Last name</label><input class="form-control" name="last_name" value="<?php echo html_escape(set_value('last_name', $user->last_name)); ?>"></div>
					<div class="col-md-6"><label class="form-label">Email</label><input class="form-control" type="email" name="email" value="<?php echo html_escape(set_value('email', $user->email)); ?>" required></div>
					<div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" name="phone" value="<?php echo html_escape(set_value('phone', $user->phone)); ?>"></div>
					<div class="col-md-6"><label class="form-label">Avatar</label><input type="file" accept="image/*" class="form-control" name="avatar"></div>
				</div>
			</div>
			<div class="card-footer bg-transparent text-end"><button class="btn btn-primary" type="submit">Save Profile</button></div>
		</form>

		<form method="post" action="<?php echo site_url('admin/profile/password'); ?>" class="card">
			<input type="hidden" name="<?php echo html_escape($this->security->get_csrf_token_name()); ?>" value="<?php echo html_escape($this->security->get_csrf_hash()); ?>">
			<div class="card-body">
				<h2 class="h5 mb-3">Change Password</h2>
				<div class="row g-3">
					<div class="col-md-4"><label class="form-label">Current password</label><input class="form-control" type="password" name="current_password" autocomplete="current-password" required></div>
					<div class="col-md-4"><label class="form-label">New password</label><input class="form-control" type="password" name="new_password" autocomplete="new-password" minlength="8" required></div>
					<div class="col-md-4"><label class="form-label">Confirm password</label><input class="form-control" type="password" name="confirm_password" autocomplete="new-password" minlength="8" required></div>
				</div>
				<p class="small text-muted mt-3 mb-0">Changing your password revokes remembered devices. Your current browser session stays active.</p>
			</div>
			<div class="card-footer bg-transparent text-end"><button class="btn btn-primary" type="submit">Update Password</button></div>
		</form>
	</div>
</div>
