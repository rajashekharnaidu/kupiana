<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php echo page_header('Order '.$order->order_number, $order->customer_name.' · '.$order->customer_email, array(array('label' => 'Generate Invoice', 'url' => site_url('admin/orders/invoice/create/'.$order->id), 'icon' => 'fa-file-invoice'), array('label' => 'Back to Orders', 'url' => site_url('admin/orders'), 'icon' => 'fa-arrow-left', 'class' => 'btn-outline-secondary'))); ?>

<div class="row g-3 mb-3">
	<div class="col-lg-8">
		<div class="card h-100">
			<div class="card-body">
				<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
					<div>
						<div class="text-muted small">Placed</div>
						<div class="fw-semibold"><?php echo format_datetime($order->placed_at ?: $order->created_at); ?></div>
					</div>
					<div class="d-flex gap-2">
						<?php echo status_badge($order->order_status, 'order'); ?>
						<?php echo status_badge($order->payment_status, 'payment'); ?>
					</div>
				</div>
				<div class="row g-3">
					<div class="col-sm-6"><div class="text-muted small">Customer</div><div><?php echo html_escape($order->customer_name); ?></div><div class="small text-muted"><?php echo html_escape($order->customer_phone); ?></div></div>
					<div class="col-sm-6"><div class="text-muted small">Source</div><div><?php echo html_escape(ucwords($order->source)); ?></div><div class="small text-muted"><?php echo html_escape($order->ip_address ?: 'No IP captured'); ?></div></div>
					<div class="col-sm-6"><div class="text-muted small">Billing Address</div><pre class="small mb-0 text-wrap"><?php echo html_escape($order->billing_address ?: 'Not captured'); ?></pre></div>
					<div class="col-sm-6"><div class="text-muted small">Shipping Address</div><pre class="small mb-0 text-wrap"><?php echo html_escape($order->shipping_address ?: 'Not captured'); ?></pre></div>
				</div>
			</div>
		</div>
	</div>
	<div class="col-lg-4">
		<div class="card h-100">
			<div class="card-body">
				<h6 class="mb-3">Update Status</h6>
				<form method="post" action="<?php echo site_url('admin/orders/status/'.$order->id); ?>" data-validate>
					<input type="hidden" name="<?php echo html_escape($this->security->get_csrf_token_name()); ?>" value="<?php echo html_escape($this->security->get_csrf_hash()); ?>">
					<div class="mb-3">
						<label class="form-label" for="order_status">Order Status</label>
						<select class="form-select" name="order_status" id="order_status" required>
							<?php foreach ($order_statuses as $key => $status): ?><option value="<?php echo html_escape($key); ?>" <?php echo $order->order_status === $key ? 'selected' : ''; ?>><?php echo html_escape($status['label']); ?></option><?php endforeach; ?>
						</select>
					</div>
					<div class="mb-3">
						<label class="form-label" for="status_comment">Comment</label>
						<textarea class="form-control" name="comment" id="status_comment" rows="3" maxlength="500"></textarea>
					</div>
					<button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-circle-check me-2"></i>Save Status</button>
				</form>
			</div>
		</div>
	</div>
</div>

<div class="card table-card mb-3">
	<div class="card-header bg-transparent"><h6 class="mb-0">Items</h6></div>
	<div class="table-responsive">
		<table class="table align-middle mb-0">
			<thead><tr><th>Product</th><th>SKU</th><th class="text-end">Qty</th><th class="text-end">Unit</th><th class="text-end">Tax</th><th class="text-end">Total</th></tr></thead>
			<tbody>
				<?php if (empty($items)): ?><tr><td colspan="6"><?php echo empty_state('No items found', 'This order has no item rows.', 'fa-box-open'); ?></td></tr><?php else: foreach ($items as $item): ?>
					<tr>
						<td><div class="fw-semibold"><?php echo html_escape($item->product_name); ?></div><div class="small text-muted"><?php echo html_escape($item->variant_name ?: 'Standard'); ?></div></td>
						<td><?php echo html_escape($item->sku ?: '—'); ?></td>
						<td class="text-end"><?php echo number_format((int) $item->quantity); ?></td>
						<td class="text-end"><?php echo money($item->unit_price); ?></td>
						<td class="text-end"><?php echo money($item->tax_amount); ?></td>
						<td class="text-end fw-semibold"><?php echo money($item->total); ?></td>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
</div>

<div class="row g-3">
	<div class="col-lg-4">
		<div class="card h-100">
			<div class="card-body">
				<h6 class="mb-3">Totals</h6>
				<div class="d-flex justify-content-between mb-2"><span>Subtotal</span><span><?php echo money($order->subtotal); ?></span></div>
				<div class="d-flex justify-content-between mb-2"><span>Discount</span><span>-<?php echo money($order->discount_amount); ?></span></div>
				<div class="d-flex justify-content-between mb-2"><span>Tax</span><span><?php echo money($order->tax_amount); ?></span></div>
				<div class="d-flex justify-content-between mb-2"><span>Shipping</span><span><?php echo money($order->shipping_amount); ?></span></div>
				<hr>
				<div class="d-flex justify-content-between fw-semibold"><span>Total</span><span><?php echo money($order->total_amount); ?></span></div>
				<div class="d-flex justify-content-between small text-muted mt-2"><span>Paid</span><span><?php echo money($order->paid_amount); ?></span></div>
				<div class="d-flex justify-content-between small text-muted"><span>Refunded</span><span><?php echo money($order->refunded_amount); ?></span></div>
			</div>
		</div>
	</div>
	<div class="col-lg-4">
		<div class="card h-100">
			<div class="card-body">
				<h6 class="mb-3">Payments & Documents</h6>
				<?php foreach ($payments as $payment): ?><div class="d-flex justify-content-between border-bottom py-2"><span><?php echo html_escape($payment->payment_number); ?></span><span><?php echo status_badge($payment->status, 'payment'); ?></span></div><?php endforeach; ?>
				<?php foreach ($invoices as $invoice): ?><div class="d-flex justify-content-between border-bottom py-2"><span><a href="<?php echo site_url('admin/invoices/download/'.$invoice->id); ?>" target="_blank"><?php echo html_escape($invoice->invoice_number); ?></a></span><span><?php echo money($invoice->total_amount); ?></span></div><?php endforeach; ?>
				<?php foreach ($refunds as $refund): ?><div class="d-flex justify-content-between border-bottom py-2"><span><?php echo html_escape($refund->refund_number); ?></span><span><?php echo money($refund->amount); ?></span></div><?php endforeach; ?>
				<?php if (empty($payments) && empty($invoices) && empty($refunds)): ?><p class="text-muted mb-0">No payment, invoice or refund rows yet.</p><?php endif; ?>
			</div>
		</div>
	</div>
	<div class="col-lg-4">
		<div class="card h-100">
			<div class="card-body">
				<h6 class="mb-3">Shipments</h6>
				<?php foreach ($shipments as $shipment): ?><div class="border-bottom py-2"><div class="fw-semibold"><a href="<?php echo site_url('admin/tracking/view/'.$shipment->id); ?>"><?php echo html_escape($shipment->shipment_number); ?></a></div><div class="small text-muted"><?php echo html_escape($shipment->courier_name ?: 'Courier pending'); ?> · <?php echo html_escape($shipment->tracking_number ?: 'No tracking'); ?></div><div class="mb-2"><?php echo status_badge($shipment->shipment_status, 'shipment'); ?></div><form method="post" action="<?php echo site_url('admin/shipments/tracking/'.$shipment->id); ?>" class="row g-2"><input type="hidden" name="<?php echo html_escape($this->security->get_csrf_token_name()); ?>" value="<?php echo html_escape($this->security->get_csrf_hash()); ?>"><div class="col-12"><input class="form-control form-control-sm" name="status_text" placeholder="Tracking status" required></div><div class="col-12"><select class="form-select form-select-sm" name="shipment_status"><option value="">Keep shipment status</option><?php foreach (app_config('shipment_statuses', array()) as $key => $meta): ?><option value="<?php echo html_escape($key); ?>"><?php echo html_escape($meta['label']); ?></option><?php endforeach; ?></select></div><div class="col-12"><input class="form-control form-control-sm" name="location" placeholder="Location"></div><div class="col-12"><input class="form-control form-control-sm" type="datetime-local" name="occurred_at" value="<?php echo date('Y-m-d\\TH:i'); ?>" required></div><div class="col-12"><button class="btn btn-sm btn-outline-primary w-100" type="submit">Add Tracking</button></div></form></div><?php endforeach; ?>
				<?php if (empty($shipments)): ?><p class="text-muted mb-0">No shipment rows yet.</p><?php endif; ?>
			</div>
		</div>
	</div>
</div>

<div class="card mt-3">
	<div class="card-body">
		<h6 class="mb-3">Status Timeline</h6>
		<?php if (empty($history)): ?><p class="text-muted mb-0">No status history has been recorded yet.</p><?php else: foreach ($history as $entry): ?>
			<div class="d-flex gap-3 border-bottom py-2">
				<div class="text-muted small" style="width: 150px;"><?php echo format_datetime($entry->created_at); ?></div>
				<div><div><?php echo html_escape(ucwords(str_replace('_', ' ', $entry->from_status ?: 'new'))); ?> → <?php echo status_badge($entry->to_status, 'order'); ?></div><?php if ($entry->comment): ?><div class="small text-muted"><?php echo html_escape($entry->comment); ?></div><?php endif; ?></div>
			</div>
		<?php endforeach; endif; ?>
	</div>
</div>
