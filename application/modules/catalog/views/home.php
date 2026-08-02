<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Storefront home.
 *
 * Phase 1: restyled onto the new design system so the landing page matches the
 * rest of the application. Phase 5 replaces this with the full homepage —
 * hero slider, categories, featured/trending/best sellers, flash sale,
 * brands, reviews and Instagram feed.
 */
?>
<section class="py-5" style="background: linear-gradient(135deg, var(--k-surface) 0%, var(--k-surface-alt) 100%);">
	<div class="container">
		<div class="row align-items-center g-5">

			<div class="col-lg-6 fade-in-up">
				<span class="badge badge-soft badge-soft-primary mb-3">Launching soon</span>
				<h1 class="display-5 fw-bold mb-3"><?php echo html_escape($site_name); ?></h1>
				<p class="lead text-muted mb-4">
					A considered ecommerce destination for refined everyday pieces,
					thoughtful gifting and beautiful essentials.
				</p>

				<div class="d-flex flex-wrap gap-2 mb-4">
					<a href="<?php echo site_url('shop'); ?>" class="btn btn-primary btn-lg">
						<i class="fa-solid fa-bag-shopping me-2"></i>Shop Now
					</a>
					<a href="<?php echo site_url('deals'); ?>" class="btn btn-outline-secondary btn-lg">
						View Deals
					</a>
				</div>

				<div class="d-flex flex-wrap gap-4 text-muted small">
					<?php foreach (array(
						'fa-layer-group'   => 'Curated collections',
						'fa-shield-halved' => 'Secure checkout',
						'fa-user'          => 'Member accounts',
					) as $icon => $label): ?>
						<span><i class="fa-solid <?php echo $icon; ?> me-2 text-primary"></i>
							<?php echo html_escape($label); ?></span>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="col-lg-6">
				<img src="<?php echo base_url('public/assets/images/store-hero.png'); ?>"
				     alt="<?php echo html_escape($site_name); ?> storefront preview"
				     class="img-fluid rounded-3 shadow-sm" loading="lazy">
			</div>
		</div>
	</div>
</section>

<section class="py-5">
	<div class="container">
		<div class="section-heading">
			<h2>Featured Products</h2>
			<a href="<?php echo site_url('shop'); ?>" class="small">
				View all <i class="fa-solid fa-arrow-right ms-1"></i>
			</a>
		</div>

		<div class="card">
			<div class="card-body p-0">
				<?php echo empty_state(
					'Catalog coming soon',
					'Products appear here once the catalog is populated in Phase 5.',
					'fa-box-open'
				); ?>
			</div>
		</div>
	</div>
</section>
