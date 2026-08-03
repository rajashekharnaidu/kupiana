<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<article class="product-card">
	<a class="product-thumb" href="<?php echo site_url('products/'.$product->slug); ?>">
		<img src="<?php echo upload_url(isset($product->image_path) ? $product->image_path : NULL); ?>" alt="<?php echo html_escape($product->name); ?>" loading="lazy">
		<?php if (discount_percent($product->mrp, $product->price)): ?><span class="product-badge"><?php echo discount_percent($product->mrp, $product->price); ?>% off</span><?php endif; ?>
	</a>
	<div class="product-body">
		<div class="product-brand"><?php echo html_escape($product->brand_name ?: 'Kupiana'); ?></div>
		<a class="product-name" href="<?php echo site_url('products/'.$product->slug); ?>"><?php echo html_escape($product->name); ?></a>
		<div class="product-rating"><i class="fa-solid fa-star"></i> <?php echo number_format((float) $product->rating_average, 1); ?> <span class="text-muted">(<?php echo (int) $product->rating_count; ?>)</span></div>
		<div class="product-price"><span class="price-now"><?php echo money($product->price); ?></span><?php if ((float) $product->mrp > (float) $product->price): ?><span class="price-mrp"><?php echo money($product->mrp); ?></span><?php endif; ?></div>
		<form method="post" action="<?php echo site_url('cart/add'); ?>" class="d-flex gap-2 mt-2">
			<input type="hidden" name="<?php echo html_escape($this->security->get_csrf_token_name()); ?>" value="<?php echo html_escape($this->security->get_csrf_hash()); ?>">
			<input type="hidden" name="product_id" value="<?php echo (int) $product->id; ?>">
			<input type="hidden" name="quantity" value="1">
			<button class="btn btn-sm btn-primary flex-grow-1" type="submit"><i class="fa-solid fa-cart-plus me-1"></i>Add</button>
			<a class="btn btn-sm btn-outline-secondary" href="<?php echo site_url('wishlist/add/'.$product->id); ?>" title="Wishlist"><i class="fa-regular fa-heart"></i></a>
		</form>
	</div>
</article>
