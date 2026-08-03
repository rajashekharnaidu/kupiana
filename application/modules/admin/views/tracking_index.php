<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php echo page_header('Delivery Tracking', 'Monitor shipments, courier assignments and delivery exceptions.'); ?>

<div class="row g-3 mb-3">
	<?php foreach (array(array('Total Shipments', $stats['shipments'], 'fa-truck-fast', 'primary'), array('In Transit', $stats['in_transit'], 'fa-route', 'info'), array('Delivered', $stats['delivered'], 'fa-circle-check', 'success'), array('Open Returns', $stats['returns'], 'fa-rotate-left', 'warning')) as $card): ?>
		<div class="col-md-3"><div class="stat-card"><div class="stat-icon text-bg-<?php echo html_escape($card[3]); ?>"><i class="fa-solid <?php echo html_escape($card[2]); ?>"></i></div><div><div class="stat-label"><?php echo html_escape($card[0]); ?></div><div class="stat-value"><?php echo number_format((int) $card[1]); ?></div></div></div></div>
	<?php endforeach; ?>
</div>

<div class="card table-card">
	<div class="card-header bg-transparent"><form class="row g-2 align-items-end">
		<div class="col-md-5"><label class="form-label">Search</label><input class="form-control" name="q" value="<?php echo html_escape($filters['q']); ?>" placeholder="Shipment, tracking, order, customer"></div>
		<div class="col-md-3"><label class="form-label">Status</label><select class="form-select" name="status"><option value="">All statuses</option><?php foreach ($statuses as $key => $meta): ?><option value="<?php echo html_escape($key); ?>" <?php echo $filters['status'] === $key ? 'selected' : ''; ?>><?php echo html_escape($meta['label']); ?></option><?php endforeach; ?></select></div>
		<div class="col-md-2"><button class="btn btn-primary w-100" type="submit">Filter</button></div>
		<div class="col-md-2"><a class="btn btn-outline-secondary w-100" href="<?php echo site_url('admin/tracking'); ?>">Reset</a></div>
	</form></div>
	<div class="table-responsive"><table class="table align-middle mb-0">
		<thead><tr><th>Shipment</th><th>Order</th><th>Customer</th><th>Courier</th><th>Tracking</th><th>Status</th><th>ETA</th><th class="text-end">Action</th></tr></thead>
		<tbody><?php if (empty($pagination['data'])): ?><tr><td colspan="8"><?php echo empty_state('No shipments found', 'Packed orders will create shipment rows automatically.', 'fa-truck'); ?></td></tr><?php else: foreach ($pagination['data'] as $shipment): ?><tr>
			<td><a href="<?php echo site_url('admin/tracking/view/'.$shipment->id); ?>"><?php echo html_escape($shipment->shipment_number); ?></a><div class="small text-muted"><?php echo html_escape($shipment->warehouse_name ?: 'No warehouse'); ?></div></td>
			<td><a href="<?php echo site_url('admin/orders/view/'.$shipment->order_id); ?>"><?php echo html_escape($shipment->order_number); ?></a></td>
			<td><?php echo html_escape($shipment->customer_name); ?><div class="small text-muted"><?php echo html_escape($shipment->customer_phone); ?></div></td>
			<td><?php echo html_escape($shipment->courier_name ?: 'Pending'); ?></td>
			<td><?php echo html_escape($shipment->tracking_number ?: '—'); ?></td>
			<td><?php echo status_badge($shipment->shipment_status, 'shipment'); ?></td>
			<td><?php echo $shipment->estimated_delivery ? format_date($shipment->estimated_delivery) : '—'; ?></td>
			<td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?php echo site_url('admin/tracking/view/'.$shipment->id); ?>"><i class="fa-solid fa-eye"></i></a></td>
		</tr><?php endforeach; endif; ?></tbody>
	</table></div>
	<div class="card-footer bg-transparent d-flex justify-content-end"><?php echo render_pagination($pagination); ?></div>
</div>
