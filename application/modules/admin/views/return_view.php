<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php echo page_header('Return '.$return->return_number, 'Order '.$return->order_number.' · '.$return->customer_name, array(array('label' => 'Back to Returns', 'url' => site_url('admin/returns'), 'icon' => 'fa-arrow-left', 'class' => 'btn-outline-secondary'))); ?>

<div class="row g-3">
	<div class="col-lg-8">
		<div class="card table-card mb-3"><div class="table-responsive"><table class="table mb-0 align-middle">
			<thead><tr><th>Item</th><th>SKU</th><th class="text-end">Qty</th><th class="text-end">Refund</th><th>Restocked</th></tr></thead>
			<tbody><?php foreach ($items as $item): ?><tr><td><?php echo html_escape($item->product_name); ?><div class="small text-muted"><?php echo html_escape($item->variant_name ?: 'Standard'); ?></div></td><td><?php echo html_escape($item->sku ?: '—'); ?></td><td class="text-end"><?php echo (int) $item->quantity; ?></td><td class="text-end"><?php echo money($item->refund_amount); ?></td><td><?php echo bool_badge($item->restocked); ?></td></tr><?php endforeach; ?></tbody>
		</table></div></div>
		<div class="card"><div class="card-body"><h2 class="h5">Request Details</h2><p class="mb-1"><strong>Reason:</strong> <?php echo html_escape($return->reason); ?></p><p class="mb-1"><strong>Description:</strong> <?php echo html_escape($return->description ?: '—'); ?></p><pre class="small text-muted mb-0 text-wrap"><?php echo html_escape($return->pickup_address ?: 'Pickup address not captured'); ?></pre></div></div>
	</div>
	<div class="col-lg-4">
		<div class="card"><div class="card-body">
			<h2 class="h5">Status</h2>
			<p><?php echo status_badge($return->return_status, 'return'); ?></p>
			<div class="d-flex justify-content-between mb-2"><span>Type</span><strong><?php echo html_escape(ucwords($return->type)); ?></strong></div>
			<div class="d-flex justify-content-between mb-3"><span>Refund Estimate</span><strong><?php echo money($return->refund_amount); ?></strong></div>
			<form method="post" action="<?php echo site_url('admin/returns/status/'.$return->id); ?>" data-validate>
				<input type="hidden" name="<?php echo html_escape($this->security->get_csrf_token_name()); ?>" value="<?php echo html_escape($this->security->get_csrf_hash()); ?>">
				<div class="mb-2"><label class="form-label">New Status</label><select class="form-select" name="return_status" required><?php foreach ($statuses as $key => $meta): ?><option value="<?php echo html_escape($key); ?>" <?php echo $return->return_status === $key ? 'selected' : ''; ?>><?php echo html_escape($meta['label']); ?></option><?php endforeach; ?></select></div>
				<div class="mb-2"><label class="form-label">Note / Rejection Reason</label><textarea class="form-control" name="note" rows="3"></textarea></div>
				<div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="restock" value="1" id="restock"><label class="form-check-label" for="restock">Restock returned items</label></div>
				<button class="btn btn-primary w-100" type="submit">Update Return</button>
			</form>
		</div></div>
	</div>
</div>
