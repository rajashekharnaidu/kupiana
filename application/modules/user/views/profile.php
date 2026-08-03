<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="py-4 border-bottom bg-light"><div class="container"><h1 class="h3 mb-1">Profile</h1><p class="text-muted mb-0">Keep your account details current for orders and support.</p></div></section>
<section class="py-5"><div class="container">
	<div class="row g-4">
		<div class="col-lg-4">
			<div class="card h-100"><div class="card-body">
				<h2 class="h5">Account</h2>
				<p class="text-muted mb-2"><?php echo html_escape($user->email); ?></p>
				<p class="mb-2">Status: <?php echo status_badge($user->status); ?></p>
				<p class="mb-0 small text-muted">Member since <?php echo format_date($user->created_at); ?></p>
			</div></div>
		</div>
		<div class="col-lg-8">
			<form method="post" action="<?php echo site_url('account/profile'); ?>" class="card">
				<input type="hidden" name="<?php echo html_escape($this->security->get_csrf_token_name()); ?>" value="<?php echo html_escape($this->security->get_csrf_hash()); ?>">
				<div class="card-body">
					<h2 class="h5 mb-3">Personal Details</h2>
					<div class="row g-3">
						<div class="col-md-6"><label class="form-label">First name</label><input class="form-control" name="first_name" value="<?php echo html_escape(set_value('first_name', $user->first_name)); ?>" required></div>
						<div class="col-md-6"><label class="form-label">Last name</label><input class="form-control" name="last_name" value="<?php echo html_escape(set_value('last_name', $user->last_name)); ?>"></div>
						<div class="col-md-6"><label class="form-label">Email</label><input class="form-control" type="email" name="email" value="<?php echo html_escape(set_value('email', $user->email)); ?>" required></div>
						<div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" name="phone" value="<?php echo html_escape(set_value('phone', $user->phone)); ?>"></div>
						<div class="col-md-6"><label class="form-label">Gender</label><select class="form-select" name="gender">
							<?php foreach (array('' => 'Prefer not to say', 'male' => 'Male', 'female' => 'Female', 'other' => 'Other', 'prefer_not_to_say' => 'Prefer not to say') as $value => $label): ?>
								<option value="<?php echo html_escape($value); ?>" <?php echo set_select('gender', $value, (string) $user->gender === (string) $value); ?>><?php echo html_escape($label); ?></option>
							<?php endforeach; ?>
						</select></div>
						<div class="col-md-6"><label class="form-label">Date of birth</label><input class="form-control" type="date" name="date_of_birth" value="<?php echo html_escape(set_value('date_of_birth', $user->date_of_birth)); ?>"></div>
					</div>
				</div>
				<div class="card-footer bg-transparent text-end"><button class="btn btn-primary" type="submit">Save Profile</button></div>
			</form>
		</div>
	</div>
</div></section>
