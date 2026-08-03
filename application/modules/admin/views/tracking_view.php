<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php echo page_header('Shipment '.$shipment->shipment_number, 'Order '.$shipment->order_number.' · '.$shipment->customer_name, array(array('label' => 'Back to Tracking', 'url' => site_url('admin/tracking'), 'icon' => 'fa-arrow-left', 'class' => 'btn-outline-secondary'))); ?>

<div class="row g-3">
	<div class="col-lg-5">
		<div class="card mb-3"><div class="card-body">
			<h2 class="h5">Courier Assignment</h2>
			<form method="post" action="<?php echo site_url('admin/tracking/update/'.$shipment->id); ?>" data-validate>
				<input type="hidden" name="<?php echo html_escape($this->security->get_csrf_token_name()); ?>" value="<?php echo html_escape($this->security->get_csrf_hash()); ?>">
				<div class="row g-2">
					<div class="col-md-6"><label class="form-label">Courier</label><input class="form-control" name="courier_name" value="<?php echo html_escape($shipment->courier_name); ?>"></div>
					<div class="col-md-6"><label class="form-label">Courier Code</label><input class="form-control" name="courier_code" value="<?php echo html_escape($shipment->courier_code); ?>"></div>
					<div class="col-12"><label class="form-label">Tracking Number</label><input class="form-control" name="tracking_number" value="<?php echo html_escape($shipment->tracking_number); ?>"></div>
					<div class="col-12"><label class="form-label">Tracking URL</label><input class="form-control" name="tracking_url" value="<?php echo html_escape($shipment->tracking_url); ?>"></div>
					<div class="col-md-4"><label class="form-label">Weight kg</label><input class="form-control" type="number" step="0.001" min="0" name="weight" value="<?php echo html_escape($shipment->weight); ?>"></div>
					<div class="col-md-4"><label class="form-label">Ship Cost</label><input class="form-control" type="number" step="0.01" min="0" name="shipping_cost" value="<?php echo html_escape($shipment->shipping_cost); ?>"></div>
					<div class="col-md-4"><label class="form-label">ETA</label><input class="form-control" type="date" name="estimated_delivery" value="<?php echo html_escape($shipment->estimated_delivery); ?>"></div>
					<div class="col-12"><button class="btn btn-primary w-100" type="submit">Save Shipment</button></div>
				</div>
			</form>
		</div></div>
		<div class="card"><div class="card-body">
			<h2 class="h5">Add Tracking Event</h2>
			<form method="post" action="<?php echo site_url('admin/tracking/event/'.$shipment->id); ?>" data-validate>
				<input type="hidden" name="<?php echo html_escape($this->security->get_csrf_token_name()); ?>" value="<?php echo html_escape($this->security->get_csrf_hash()); ?>">
				<div class="mb-2"><label class="form-label">Status Text</label><input class="form-control" name="status_text" required placeholder="Package reached Bengaluru hub"></div>
				<div class="mb-2"><label class="form-label">Shipment Status</label><select class="form-select" name="shipment_status"><option value="">Keep current</option><?php foreach ($statuses as $key => $meta): ?><option value="<?php echo html_escape($key); ?>" <?php echo $shipment->shipment_status === $key ? 'selected' : ''; ?>><?php echo html_escape($meta['label']); ?></option><?php endforeach; ?></select></div>
				<div class="mb-2"><label class="form-label">Location</label><input class="form-control" name="location"></div>
				<div class="mb-2"><label class="form-label">Occurred At</label><input class="form-control" type="datetime-local" name="occurred_at" value="<?php echo date('Y-m-d\\TH:i'); ?>"></div>
				<div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="3" maxlength="500"></textarea></div>
				<button class="btn btn-outline-primary w-100" type="submit">Add Event</button>
			</form>
		</div></div>
	</div>
	<div class="col-lg-7">
		<div class="card mb-3"><div class="card-body">
			<div class="d-flex justify-content-between gap-3 mb-2"><div><h2 class="h5 mb-1">Shipment Status</h2><div class="text-muted small"><?php echo html_escape($shipment->courier_name ?: 'Courier pending'); ?> · <?php echo html_escape($shipment->tracking_number ?: 'No tracking number'); ?></div></div><?php echo status_badge($shipment->shipment_status, 'shipment'); ?></div>
			<div class="row g-2 small text-muted"><div class="col-md-4">Shipped: <?php echo $shipment->shipped_at ? format_datetime($shipment->shipped_at) : '—'; ?></div><div class="col-md-4">ETA: <?php echo $shipment->estimated_delivery ? format_date($shipment->estimated_delivery) : '—'; ?></div><div class="col-md-4">Delivered: <?php echo $shipment->delivered_at ? format_datetime($shipment->delivered_at) : '—'; ?></div></div>
			<?php if ($shipment->tracking_url): ?><a href="<?php echo html_escape($shipment->tracking_url); ?>" target="_blank" rel="noopener">Open courier tracking</a><?php endif; ?>
		</div></div>
		<div class="card"><div class="card-body">
			<h2 class="h5">Unified Timeline</h2>
			<?php if (empty($timeline)): ?><p class="text-muted mb-0">No timeline events yet.</p><?php else: foreach ($timeline as $entry): ?><div class="border-bottom py-2">
				<div class="d-flex justify-content-between gap-2"><strong><?php echo html_escape($entry->title); ?></strong><span class="small text-muted"><?php echo format_datetime($entry->occurred_at); ?></span></div>
				<div class="small text-muted"><?php echo html_escape($entry->kind === 'shipment' ? 'Shipment update' : 'Order update'); ?><?php if ($entry->location): ?> · <?php echo html_escape($entry->location); ?><?php endif; ?></div>
				<?php if ($entry->description): ?><div><?php echo html_escape($entry->description); ?></div><?php endif; ?>
			</div><?php endforeach; endif; ?>
		</div></div>
	</div>
</div>
