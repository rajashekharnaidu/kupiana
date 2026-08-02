<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Storefront footer: trust badges, newsletter, link columns, payment methods.
 */
?>
<section class="trust-strip">
	<div class="container">
		<div class="row g-4">
			<?php
			$badges = array(
				array('icon' => 'fa-truck-fast',     'title' => 'Free Shipping',   'text' => 'On all orders above '.money(999)),
				array('icon' => 'fa-rotate-left',    'title' => 'Easy Returns',    'text' => '7-day hassle-free returns'),
				array('icon' => 'fa-shield-halved',  'title' => 'Secure Payments', 'text' => '100% protected checkout'),
				array('icon' => 'fa-headset',        'title' => 'Support',         'text' => 'Dedicated help, 7 days a week'),
			);

			foreach ($badges as $badge): ?>
				<div class="col-6 col-lg-3">
					<div class="trust-item">
						<i class="fa-solid <?php echo $badge['icon']; ?>"></i>
						<div>
							<strong><?php echo html_escape($badge['title']); ?></strong>
							<span><?php echo html_escape($badge['text']); ?></span>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<section class="newsletter-band">
	<div class="container">
		<div class="row align-items-center g-4">
			<div class="col-lg-6">
				<h3 class="mb-1">Get 10% off your first order</h3>
				<p class="mb-0 opacity-75">Join our newsletter for early access to launches, deals and offers.</p>
			</div>
			<div class="col-lg-6">
				<form class="newsletter-form" method="post" action="<?php echo site_url('newsletter/subscribe'); ?>"
				      data-ajax-form data-reset-on-success>
					<?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
					<div class="input-group input-group-lg">
						<input type="email" name="email" class="form-control" placeholder="Enter your email address"
						       required aria-label="Email address">
						<button class="btn btn-dark" type="submit">
							Subscribe <i class="fa-solid fa-paper-plane ms-2"></i>
						</button>
					</div>
					<div class="form-feedback small mt-2"></div>
				</form>
			</div>
		</div>
	</div>
</section>

<footer class="store-footer">
	<div class="container">
		<div class="row g-4">

			<div class="col-lg-4">
				<a class="store-brand mb-3 d-inline-flex" href="<?php echo site_url(); ?>">
					<span class="brand-mark"><i class="fa-solid fa-bag-shopping"></i></span>
					<span class="brand-text"><?php echo html_escape($site_name); ?></span>
				</a>
				<p class="text-muted"><?php echo html_escape(array_get($app, 'tagline', '')); ?></p>
				<ul class="list-unstyled footer-contact">
					<li><i class="fa-solid fa-envelope"></i>
						<a href="mailto:<?php echo html_escape(array_get($app, 'support_email', '')); ?>">
							<?php echo html_escape(array_get($app, 'support_email', '')); ?></a></li>
					<li><i class="fa-solid fa-phone"></i>
						<?php echo html_escape(array_get($app, 'support_phone', '')); ?></li>
				</ul>
				<div class="social-links">
					<a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
					<a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
					<a href="#" aria-label="X"><i class="fa-brands fa-x-twitter"></i></a>
					<a href="#" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a>
				</div>
			</div>

			<?php
			$columns = array(
				'Shop' => array(
					'All Products' => 'shop',
					"Today's Deals" => 'deals',
					'Brands'        => 'brands',
					'Offers'        => 'offers',
					'New Arrivals'  => 'shop?sort=created_at',
				),
				'My Account' => array(
					'Sign In'      => 'login',
					'My Orders'    => 'account/orders',
					'Wishlist'     => 'wishlist',
					'Track Order'  => 'track-order',
					'Returns'      => 'account/returns',
				),
				'Company' => array(
					'About Us'  => 'about',
					'Blog'      => 'blog',
					'Contact'   => 'contact',
					'FAQ'       => 'faq',
				),
				'Policies' => array(
					'Privacy Policy'  => 'privacy-policy',
					'Terms of Use'    => 'terms',
					'Return Policy'   => 'return-policy',
					'Shipping Policy' => 'shipping-policy',
				),
			);

			foreach ($columns as $heading => $links): ?>
				<div class="col-6 col-lg-2">
					<h6 class="footer-heading"><?php echo html_escape($heading); ?></h6>
					<ul class="list-unstyled footer-links">
						<?php foreach ($links as $label => $uri): ?>
							<li><a href="<?php echo site_url($uri); ?>"><?php echo html_escape($label); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endforeach; ?>
		</div>

		<hr class="footer-divider">

		<div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
			<span class="text-muted small">
				&copy; <?php echo date('Y'); ?> <?php echo html_escape($site_name); ?>. All rights reserved.
			</span>
			<div class="payment-icons">
				<i class="fa-brands fa-cc-visa"></i>
				<i class="fa-brands fa-cc-mastercard"></i>
				<i class="fa-brands fa-cc-amex"></i>
				<i class="fa-brands fa-google-pay"></i>
				<i class="fa-solid fa-money-bill-wave" title="Cash on Delivery"></i>
			</div>
		</div>
	</div>
</footer>
