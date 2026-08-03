<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $primary = ! empty($product->images) ? $product->images[0]->image_path : (isset($product->image_path) ? $product->image_path : NULL); ?>
<section class="py-5">
	<div class="container">
		<div class="row g-5">
			<div class="col-lg-6">
				<img class="img-fluid rounded-3 border bg-light w-100" src="<?php echo upload_url($primary); ?>" alt="<?php echo html_escape($product->name); ?>">
				<?php if (count($product->images) > 1): ?><div class="row g-2 mt-2"><?php foreach ($product->images as $image): ?><div class="col-3"><img class="img-fluid rounded border" src="<?php echo upload_url($image->image_path); ?>" alt="<?php echo html_escape($image->alt_text); ?>"></div><?php endforeach; ?></div><?php endif; ?>
			</div>
			<div class="col-lg-6">
				<div class="product-brand mb-2"><?php echo html_escape($product->brand_name ?: 'Kupiana'); ?></div>
				<h1 class="h2 mb-3"><?php echo html_escape($product->name); ?></h1>
				<div class="product-rating mb-3"><i class="fa-solid fa-star"></i> <?php echo number_format((float) $product->rating_average, 1); ?> <span class="text-muted">(<?php echo (int) $product->rating_count; ?> reviews)</span></div>
				<div class="d-flex align-items-baseline gap-3 mb-3"><span class="display-6 fw-bold"><?php echo money($product->price); ?></span><?php if ((float) $product->mrp > (float) $product->price): ?><span class="h5 text-muted text-decoration-line-through"><?php echo money($product->mrp); ?></span><span class="badge badge-soft badge-soft-success"><?php echo discount_percent($product->mrp, $product->price); ?>% off</span><?php endif; ?></div>
				<p class="text-muted"><?php echo html_escape($product->short_description); ?></p>
				<?php if ($product->stock_quantity > 0): ?><span class="badge badge-soft badge-soft-success mb-3">In stock: <?php echo (int) $product->stock_quantity; ?></span><?php else: ?><span class="badge badge-soft badge-soft-danger mb-3">Out of stock</span><?php endif; ?>
				<form method="post" action="<?php echo site_url('cart/add'); ?>" class="d-flex flex-wrap align-items-center gap-2 mb-4">
					<input type="hidden" name="<?php echo html_escape($this->security->get_csrf_token_name()); ?>" value="<?php echo html_escape($this->security->get_csrf_hash()); ?>">
					<input type="hidden" name="product_id" value="<?php echo (int) $product->id; ?>">
					<?php if ( ! empty($product->variants)): ?><select class="form-select" name="variant_id" style="max-width: 260px;"><option value="">Select variant</option><?php foreach ($product->variants as $variant): ?><option value="<?php echo (int) $variant->id; ?>"><?php echo html_escape(($variant->name ?: $variant->sku).' - '.money($variant->price)); ?></option><?php endforeach; ?></select><?php endif; ?>
					<div class="qty-stepper input-group" style="max-width: 140px;"><button class="btn btn-outline-secondary" type="button" data-qty-step="-1">-</button><input class="form-control text-center" type="number" name="quantity" value="1" min="1" max="<?php echo max(1, (int) $product->stock_quantity); ?>"><button class="btn btn-outline-secondary" type="button" data-qty-step="1">+</button></div>
					<button class="btn btn-primary" type="submit" <?php echo $product->stock_quantity <= 0 ? 'disabled' : ''; ?>><i class="fa-solid fa-cart-plus me-2"></i>Add to Cart</button>
					<a class="btn btn-outline-secondary" href="<?php echo site_url('wishlist/add/'.$product->id); ?>"><i class="fa-regular fa-heart me-2"></i>Wishlist</a>
				</form>
				<div class="border-top pt-3"><?php echo $product->description ?: '<p class="text-muted">No detailed description yet.</p>'; ?></div>
			</div>
		</div>
	</div>
</section>

<section class="py-5 bg-light">
	<div class="container">
		<div class="section-heading"><h2>Related Products</h2><?php if ($product->category_slug): ?><a href="<?php echo site_url('category/'.$product->category_slug); ?>" class="small">Explore category</a><?php endif; ?></div>
		<div class="row g-3"><?php foreach ($related as $item): if ((int) $item->id === (int) $product->id) { continue; } ?><div class="col-6 col-md-4 col-lg-3"><?php $this->load->view('_product_card', array('product' => $item)); ?></div><?php endforeach; ?></div>
	</div>
</section>
