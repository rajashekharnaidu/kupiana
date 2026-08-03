<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="py-4 border-bottom bg-light"><div class="container"><h1 class="h3 mb-1">Request Return</h1><p class="text-muted mb-0">Order <?php echo html_escape($order->order_number); ?></p></div></section>
<section class="py-5"><div class="container"><div class="row justify-content-center"><div class="col-lg-8"><div class="card"><div class="card-body">
	<?php if ($order->order_status !== 'delivered'): ?>
		<?php echo empty_state('Return not available yet', 'Returns and exchanges can be requested after delivery.', 'fa-truck-fast', array('label' => 'Back to Order', 'url' => site_url('account/orders/'.$order->id), 'icon' => 'fa-arrow-left')); ?>
	<?php else: ?>
		<form method="post" data-validate>
			<input type="hidden" name="<?php echo html_escape($this->security->get_csrf_token_name()); ?>" value="<?php echo html_escape($this->security->get_csrf_hash()); ?>">
			<div class="row g-3">
				<div class="col-md-6"><label class="form-label">Request Type</label><select class="form-select" name="type"><option value="return">Return</option><option value="exchange">Exchange</option></select></div>
				<div class="col-md-6"><label class="form-label">Reason</label><input class="form-control" name="reason" maxlength="150" required placeholder="Damaged, wrong item, size issue..."></div>
				<div class="col-12"><label class="form-label">Items</label><?php foreach ($items as $item): $available = max(0, (int) $item->fulfilled_quantity - (int) $item->returned_quantity); ?><div class="d-flex align-items-center justify-content-between border rounded p-2 mb-2"><div><strong><?php echo html_escape($item->product_name); ?></strong><div class="small text-muted"><?php echo html_escape($item->sku ?: '—'); ?> · Delivered <?php echo (int) $available; ?></div></div><input class="form-control form-control-sm" style="max-width:110px" type="number" min="0" max="<?php echo (int) $available; ?>" name="items[<?php echo (int) $item->id; ?>]" value="<?php echo $available > 0 ? 1 : 0; ?>"></div><?php endforeach; ?></div>
				<div class="col-12"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="4" maxlength="1000" placeholder="Tell us what happened."></textarea></div>
				<div class="col-12 d-flex gap-2"><button class="btn btn-primary" type="submit">Submit Request</button><a class="btn btn-outline-secondary" href="<?php echo site_url('account/orders/'.$order->id); ?>">Cancel</a></div>
			</div>
		</form>
	<?php endif; ?>
</div></div></div></div></div></section>
