<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="py-5 bg-light">
	<div class="container">
		<div class="row align-items-center g-4">
			<div class="col-lg-6">
				<span class="badge badge-soft badge-soft-primary mb-3">Organic spices &amp; cold-pressed oils</span>
				<h1 class="display-5 fw-bold mb-3"><?php echo html_escape($site_name); ?></h1>
				<p class="lead text-muted mb-4">Shop farm-sourced whole spices, fresh-ground masalas and cold-pressed cooking oils with careful packing, fast dispatch and clear GST-ready invoicing.</p>
				<div class="d-flex flex-wrap gap-2"><a href="<?php echo site_url('shop'); ?>" class="btn btn-primary btn-lg"><i class="fa-solid fa-seedling me-2"></i>Shop Organic Pantry</a><a href="<?php echo site_url('deals'); ?>" class="btn btn-outline-secondary btn-lg">View Pantry Deals</a></div>
			</div>
			<div class="col-lg-6"><img src="<?php echo base_url('public/assets/images/store-hero.jpg'); ?>" alt="<?php echo html_escape($site_name); ?> organic spices and cold-pressed oils" class="img-fluid rounded-3 shadow-sm" width="1400" height="1050" loading="eager" fetchpriority="high"></div>
		</div>
	</div>
</section>

<section class="py-5">
	<div class="container">
		<div class="section-heading"><h2>Shop Categories</h2><a href="<?php echo site_url('shop'); ?>" class="small">View all</a></div>
		<div class="row g-3">
			<?php foreach (array_slice($categories, 0, 8) as $category): ?>
				<div class="col-6 col-md-3"><a class="card h-100 text-center p-3" href="<?php echo site_url('category/'.$category->slug); ?>"><i class="fa-solid fa-layer-group fa-2x text-primary mb-2"></i><strong><?php echo html_escape($category->name); ?></strong><span class="small text-muted"><?php echo (int) $category->product_count; ?> products</span></a></div>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<section class="py-5 bg-light">
	<div class="container">
		<div class="section-heading"><h2>Featured Organic Picks</h2><a href="<?php echo site_url('shop'); ?>" class="small">View all <i class="fa-solid fa-arrow-right ms-1"></i></a></div>
		<div class="row g-3">
			<?php if (empty($featured)): ?><div class="col-12"><?php echo empty_state('No featured products yet', 'Feature products from the admin catalog.', 'fa-box-open'); ?></div><?php else: foreach ($featured as $product): ?><div class="col-6 col-md-4 col-lg-3"><?php $this->load->view('_product_card', array('product' => $product)); ?></div><?php endforeach; endif; ?>
		</div>
	</div>
</section>

<section class="py-5">
	<div class="container">
		<div class="section-heading"><h2>Trending in the Pantry</h2><a href="<?php echo site_url('deals'); ?>" class="small">Explore</a></div>
		<div class="row g-3">
			<?php foreach ($trending['data'] as $product): ?><div class="col-6 col-md-4 col-lg-3"><?php $this->load->view('_product_card', array('product' => $product)); ?></div><?php endforeach; ?>
		</div>
	</div>
</section>

<section class="trust-strip">
	<div class="container"><div class="row g-3">
		<?php foreach (array('fa-seedling' => array('Farm-sourced', 'Organic spices selected from trusted growers'), 'fa-bottle-droplet' => array('Cold-pressed oils', 'Small-batch oils pressed for flavour and aroma'), 'fa-receipt' => array('GST invoices', 'Order records ready for accounting')) as $icon => $item): ?>
			<div class="col-md-4"><div class="trust-item"><i class="fa-solid <?php echo $icon; ?>"></i><div><strong><?php echo html_escape($item[0]); ?></strong><span><?php echo html_escape($item[1]); ?></span></div></div></div>
		<?php endforeach; ?>
	</div></div>
</section>
