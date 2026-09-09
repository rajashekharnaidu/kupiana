<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php if (empty($banners)): ?>
<section class="py-5 bg-light">
	<div class="container">
		<div class="row align-items-center g-4">
			<div class="col-lg-6">
				<span class="badge badge-soft badge-soft-primary mb-3">Spices &amp; cold-pressed oils</span>
				<h1 class="display-5 fw-bold mb-3"><?php echo html_escape($site_name); ?></h1>
				<p class="lead text-muted mb-4">Shop farm-sourced whole spices, fresh-ground masalas and cold-pressed cooking oils with careful packing, fast dispatch and clear GST-ready invoicing.</p>
				<div class="d-flex flex-wrap gap-2"><a href="<?php echo site_url('shop'); ?>" class="btn btn-primary btn-lg"><i class="fa-solid fa-seedling me-2"></i>Shop Pantry</a><a href="<?php echo site_url('deals'); ?>" class="btn btn-outline-secondary btn-lg">View Pantry Deals</a></div>
			</div>
			<div class="col-lg-6"><img src="<?php echo base_url('public/assets/images/store-hero.jpg'); ?>" alt="<?php echo html_escape($site_name); ?> spices and cold-pressed oils" class="img-fluid rounded-3 shadow-sm" width="1400" height="1050" loading="eager" fetchpriority="high"></div>
		</div>
	</div>
</section>
<?php else: ?>
<section class="hero-slider">
	<div id="homeBannerCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-touch="true">
		<?php if (count($banners) > 1): ?>
		<div class="carousel-indicators">
			<?php foreach ($banners as $i => $banner): ?><button type="button" data-bs-target="#homeBannerCarousel" data-bs-slide-to="<?php echo $i; ?>" <?php echo $i === 0 ? 'class="active" aria-current="true"' : ''; ?> aria-label="Slide <?php echo $i + 1; ?>"></button><?php endforeach; ?>
		</div>
		<?php endif; ?>
		<div class="carousel-inner">
			<?php foreach ($banners as $i => $banner): ?>
			<div class="carousel-item<?php echo $i === 0 ? ' active' : ''; ?>">
				<div class="hero-slide">
					<picture>
						<?php if (trim((string) $banner->mobile_image) !== ''): ?><source media="(max-width: 767.98px)" srcset="<?php echo upload_url($banner->mobile_image); ?>"><?php endif; ?>
						<img src="<?php echo upload_url($banner->image); ?>" alt="<?php echo html_escape($banner->title); ?>" loading="<?php echo $i === 0 ? 'eager' : 'lazy'; ?>"<?php echo $i === 0 ? ' fetchpriority="high"' : ''; ?>>
					</picture>
					<div class="hero-slide-content">
						<?php if (trim((string) $banner->title) !== ''): ?><h1 class="fw-bold mb-2"><?php echo html_escape($banner->title); ?></h1><?php endif; ?>
						<?php if (trim((string) $banner->subtitle) !== ''): ?><p class="lead mb-3"><?php echo html_escape($banner->subtitle); ?></p><?php endif; ?>
						<?php if (trim((string) $banner->link_url) !== ''): ?><a href="<?php echo site_url($banner->link_url); ?>" class="btn btn-primary btn-lg"><?php echo html_escape(trim((string) $banner->button_text) !== '' ? $banner->button_text : 'Shop Now'); ?></a><?php endif; ?>
					</div>
				</div>
			</div>
			<?php endforeach; ?>
		</div>
		<?php if (count($banners) > 1): ?>
		<button class="carousel-control-prev" type="button" data-bs-target="#homeBannerCarousel" data-bs-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Previous</span></button>
		<button class="carousel-control-next" type="button" data-bs-target="#homeBannerCarousel" data-bs-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">Next</span></button>
		<?php endif; ?>
	</div>
</section>
<?php endif; ?>

<section class="py-5 bg-light">
	<div class="container">
		<div class="section-heading"><h2>Featured Picks</h2><a href="<?php echo site_url('shop'); ?>" class="small">View all <i class="fa-solid fa-arrow-right ms-1"></i></a></div>
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
		<?php foreach (array('fa-seedling' => array('Farm-sourced', 'Spices selected from trusted growers'), 'fa-bottle-droplet' => array('Cold-pressed oils', 'Small-batch oils pressed for flavour and aroma'), 'fa-receipt' => array('GST invoices', 'Order records ready for accounting')) as $icon => $item): ?>
			<div class="col-md-4"><div class="trust-item"><i class="fa-solid <?php echo $icon; ?>"></i><div><strong><?php echo html_escape($item[0]); ?></strong><span><?php echo html_escape($item[1]); ?></span></div></div></div>
		<?php endforeach; ?>
	</div></div>
</section>
