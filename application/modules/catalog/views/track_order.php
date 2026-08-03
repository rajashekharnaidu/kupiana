<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="py-4 border-bottom bg-light"><div class="container"><h1 class="h3 mb-1">Track Order</h1><p class="text-muted mb-0">Enter your order number and email or phone to check status.</p></div></section>
<section class="py-5"><div class="container">
	<div class="card mb-4"><div class="card-body"><form method="get" class="row g-2 align-items-end">
		<div class="col-md-5"><label class="form-label">Order number</label><input class="form-control" name="order" placeholder="KP-10001" value="<?php echo html_escape($this->input->get('order', TRUE)); ?>" required></div>
		<div class="col-md-5"><label class="form-label">Email or phone</label><input class="form-control" name="identity" placeholder="Email or phone used at checkout" value="<?php echo html_escape($this->input->get('identity', TRUE)); ?>" required></div>
		<div class="col-md-2"><button class="btn btn-primary w-100" type="submit">Track</button></div>
	</form></div></div>
	<?php if ($searched && ! $order): ?>
		<?php echo empty_state('Order not found', 'Check the order number and the email or phone used at checkout.', 'fa-magnifying-glass'); ?>
	<?php elseif ($order): ?>
		<div class="row g-4">
			<div class="col-lg-4">
				<div class="card h-100"><div class="card-body">
					<h2 class="h5">Order <?php echo html_escape($order->order_number); ?></h2>
					<p class="mb-1">Status: <?php echo status_badge($order->order_status, 'order'); ?></p>
					<p class="mb-1">Payment: <?php echo status_badge($order->payment_status); ?></p>
					<p class="mb-1">Total: <strong><?php echo money($order->total_amount); ?></strong></p>
					<p class="text-muted mb-0">Placed <?php echo format_date($order->placed_at ?: $order->created_at); ?></p>
				</div></div>
			</div>
			<div class="col-lg-8">
				<div class="card mb-4"><div class="card-body">
					<h2 class="h5">Shipment</h2>
					<?php if (empty($shipments)): ?><p class="text-muted mb-0">Shipment details will appear once the order is packed.</p><?php else: foreach ($shipments as $shipment): ?>
						<div class="border rounded p-3 mb-2">
							<div class="d-flex justify-content-between gap-3"><strong><?php echo html_escape($shipment->shipment_number); ?></strong><?php echo status_badge($shipment->shipment_status, 'shipment'); ?></div>
							<div class="text-muted small"><?php echo html_escape($shipment->courier_name ?: 'Courier pending'); ?><?php if ($shipment->tracking_number): ?> · <?php echo html_escape($shipment->tracking_number); ?><?php endif; ?></div>
							<?php if ($shipment->tracking_url): ?><a href="<?php echo html_escape($shipment->tracking_url); ?>" target="_blank" rel="noopener">Open courier tracking</a><?php endif; ?>
						</div>
					<?php endforeach; endif; ?>
				</div></div>
				<div class="card"><div class="card-body">
					<h2 class="h5">Timeline</h2>
					<?php if (empty($history) && empty($tracking)): ?><p class="text-muted mb-0">Timeline updates are not available yet.</p><?php else: ?>
						<ul class="list-unstyled mb-0">
							<?php foreach ($tracking as $row): ?><li class="border-bottom py-2"><strong><?php echo html_escape($row->status_text); ?></strong><div class="small text-muted"><?php echo html_escape($row->location ?: 'Courier update'); ?> · <?php echo format_datetime($row->occurred_at); ?></div><?php if ($row->description): ?><div><?php echo html_escape($row->description); ?></div><?php endif; ?></li><?php endforeach; ?>
							<?php foreach ($history as $row): ?><li class="border-bottom py-2"><strong><?php echo html_escape(ucwords(str_replace('_', ' ', $row->to_status))); ?></strong><div class="small text-muted"><?php echo format_datetime($row->created_at); ?></div><?php if ($row->comment): ?><div><?php echo html_escape($row->comment); ?></div><?php endif; ?></li><?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div></div>
			</div>
		</div>
	<?php else: ?>
		<p class="text-muted mb-0">Signed-in customers can also review orders from <a href="<?php echo site_url('account/orders'); ?>">My Account</a>.</p>
	<?php endif; ?>
</div></section>
