<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="py-4 border-bottom bg-light">
	<div class="container">
		<h1 class="h3 mb-1">Contact</h1>
		<p class="text-muted mb-0">We are here to help with orders, returns and product questions.</p>
	</div>
</section>
<section class="py-5">
	<div class="container">
		<div class="row g-4">
			<div class="col-lg-5">
				<div class="card h-100">
					<div class="card-body">
						<h2 class="h5">Support</h2>
						<p class="text-muted">Tell us what is going on and the Kupiana team will follow up from the admin inbox.</p>
						<p><i class="fa-solid fa-envelope me-2 text-primary"></i><?php echo html_escape(array_get($app, 'support_email', 'support@kupiana.test')); ?></p>
						<p><i class="fa-solid fa-phone me-2 text-primary"></i><?php echo html_escape(array_get($app, 'support_phone', '+91 90000 00000')); ?></p>
						<div class="alert alert-light border mb-0"><i class="fa-regular fa-clock me-2"></i>Typical response time: 1 business day.</div>
					</div>
				</div>
			</div>
			<div class="col-lg-7">
				<form method="post" action="<?php echo site_url('contact'); ?>" class="card">
					<input type="hidden" name="<?php echo html_escape($this->security->get_csrf_token_name()); ?>" value="<?php echo html_escape($this->security->get_csrf_hash()); ?>">
					<div class="card-body">
						<h2 class="h5 mb-3">Send a Message</h2>
						<div class="row g-3">
							<div class="col-md-6"><label class="form-label">Name</label><input class="form-control" name="name" value="<?php echo html_escape(set_value('name', $current_user ? trim($current_user->first_name.' '.$current_user->last_name) : '')); ?>" required></div>
							<div class="col-md-6"><label class="form-label">Email</label><input class="form-control" type="email" name="email" value="<?php echo html_escape(set_value('email', $current_user ? $current_user->email : '')); ?>" required></div>
							<div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" name="phone" value="<?php echo html_escape(set_value('phone', $current_user ? $current_user->phone : '')); ?>"></div>
							<div class="col-md-6"><label class="form-label">Subject</label><input class="form-control" name="subject" value="<?php echo html_escape(set_value('subject')); ?>" placeholder="Order help, product question, return..."></div>
							<div class="col-12"><label class="form-label">Message</label><textarea class="form-control" name="message" rows="5" required><?php echo html_escape(set_value('message')); ?></textarea></div>
						</div>
					</div>
					<div class="card-footer bg-transparent text-end"><button class="btn btn-primary" type="submit">Submit Message</button></div>
				</form>
			</div>
		</div>
	</div>
</section>
