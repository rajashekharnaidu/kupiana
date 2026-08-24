<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="py-5 bg-light border-bottom">
	<div class="container">
		<div class="row align-items-center g-4">
			<div class="col-lg-7">
				<span class="badge badge-soft badge-soft-primary mb-3">Organic spices &amp; cold-pressed oils</span>
				<h1 class="display-5 fw-bold mb-3"><?php echo html_escape($page->title); ?></h1>
				<p class="lead text-muted mb-4">
					Kupiana brings together fresh-ground spices, whole masalas and cold-pressed cooking oils for
					everyday Indian kitchens that want cleaner ingredients and fuller aroma.
				</p>
				<div class="d-flex flex-wrap gap-2">
					<a href="<?php echo site_url('shop'); ?>" class="btn btn-primary btn-lg">
						<i class="fa-solid fa-seedling me-2"></i>Shop Organic Pantry
					</a>
					<a href="<?php echo site_url('contact'); ?>" class="btn btn-outline-secondary btn-lg">Talk to Support</a>
				</div>
			</div>
			<div class="col-lg-5">
				<div class="card shadow-sm border-0 overflow-hidden">
					<img src="<?php echo base_url('public/assets/images/store-hero.jpg'); ?>"
					     alt="<?php echo html_escape($site_name); ?> organic spices and cold-pressed oils"
					     class="img-fluid" loading="eager" fetchpriority="high" width="1400" height="1050">
				</div>
			</div>
		</div>
	</div>
</section>

<section class="py-5">
	<div class="container">
		<div class="row g-4">
			<div class="col-lg-8">
				<div class="card h-100">
					<div class="card-body p-4 p-lg-5">
						<h2 class="h4 mb-3">Our story</h2>
						<div class="page-content">
							<?php echo $page->content ?: '<p class="text-muted mb-0">Content coming soon.</p>'; ?>
						</div>
					</div>
				</div>
			</div>
			<div class="col-lg-4">
				<div class="card h-100 bg-light border-0">
					<div class="card-body p-4 p-lg-5">
						<h2 class="h5 mb-3">Why Kupiana</h2>
						<ul class="list-unstyled mb-0">
							<li class="d-flex gap-3 mb-3">
								<i class="fa-solid fa-seedling text-primary mt-1"></i>
								<div>
									<strong>Trusted growers</strong>
									<div class="text-muted small">We source with care so the ingredients feel clean and reliable.</div>
								</div>
							</li>
							<li class="d-flex gap-3 mb-3">
								<i class="fa-solid fa-mortar-pestle text-primary mt-1"></i>
								<div>
									<strong>Small-batch freshness</strong>
									<div class="text-muted small">Fresh-ground masalas and carefully pressed oils keep the aroma intact.</div>
								</div>
							</li>
							<li class="d-flex gap-3 mb-0">
								<i class="fa-solid fa-receipt text-primary mt-1"></i>
								<div>
									<strong>Clear checkout</strong>
									<div class="text-muted small">Fast dispatch, secure packing and GST-ready invoicing on every order.</div>
								</div>
							</li>
						</ul>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>

<section class="py-5 bg-light">
	<div class="container">
		<div class="section-heading">
			<h2>What you'll find here</h2>
			<a href="<?php echo site_url('shop'); ?>" class="small">Browse products</a>
		</div>
		<div class="row g-3">
			<div class="col-md-4">
				<div class="card h-100">
					<div class="card-body">
						<i class="fa-solid fa-pepper-hot fa-2x text-primary mb-3"></i>
						<h3 class="h5">Whole spices</h3>
						<p class="text-muted mb-0">Pepper, cardamom, cumin and other kitchen essentials selected for aroma and freshness.</p>
					</div>
				</div>
			</div>
			<div class="col-md-4">
				<div class="card h-100">
					<div class="card-body">
						<i class="fa-solid fa-mortar-pestle fa-2x text-primary mb-3"></i>
						<h3 class="h5">Fresh-ground masalas</h3>
						<p class="text-muted mb-0">Turmeric, chilli and garam masala blends made in small batches for everyday cooking.</p>
					</div>
				</div>
			</div>
			<div class="col-md-4">
				<div class="card h-100">
					<div class="card-body">
						<i class="fa-solid fa-bottle-droplet fa-2x text-primary mb-3"></i>
						<h3 class="h5">Cold-pressed oils</h3>
						<p class="text-muted mb-0">Groundnut, sesame and coconut oils pressed slowly for a cleaner, fuller pantry staple.</p>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>

<section class="py-5">
	<div class="container">
		<div class="row align-items-center g-4">
			<div class="col-lg-8">
				<h2 class="h4 mb-3">Built for a cleaner pantry</h2>
				<p class="text-muted mb-0">
					Our promise is simple: ingredients that taste honest, packing that protects freshness, and a checkout
					experience that makes reordering easy. If you are browsing for pantry staples, trying a new spice blend
					or stocking up on oils, Kupiana is here to keep the experience straightforward.
				</p>
			</div>
			<div class="col-lg-4 text-lg-end">
				<a href="<?php echo site_url('contact'); ?>" class="btn btn-outline-primary btn-lg">Contact the team</a>
			</div>
		</div>
	</div>
</section>
