<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php echo page_header('Stock Overview', 'Live warehouse balances, reorder alerts and recent stock movement ledger.', array(
	array('label' => 'Export CSV', 'url' => site_url('admin/inventory/export').'?'.http_build_query($this->input->get(NULL, TRUE) ?: array()), 'icon' => 'fa-file-csv', 'class' => 'btn-outline-secondary'),
	array('label' => 'Stock In', 'url' => site_url('admin/inventory/stock-in'), 'icon' => 'fa-arrow-down'),
	array('label' => 'Purchase Entry', 'url' => site_url('admin/purchases/create'), 'icon' => 'fa-truck-ramp-box'),
)); ?>

<div class="row g-3 mb-4">
	<div class="col-md-3"><?php echo stat_card('On Hand', number_format($stats['units']), 'fa-boxes-stacked', 'primary'); ?></div>
	<div class="col-md-3"><?php echo stat_card('Available', number_format($stats['available']), 'fa-circle-check', 'success'); ?></div>
	<div class="col-md-3"><?php echo stat_card('Low Stock', number_format($stats['low_stock']), 'fa-triangle-exclamation', 'warning'); ?></div>
	<div class="col-md-3"><?php echo stat_card('Stock Value', money($stats['value']), 'fa-indian-rupee-sign', 'info'); ?></div>
</div>

<form method="get" class="card mb-3" data-filter-form>
	<div class="card-body"><div class="row g-2 align-items-end">
		<div class="col-md-4"><label class="form-label">Search</label><input class="form-control" name="q" value="<?php echo html_escape($this->input->get('q', TRUE)); ?>" placeholder="Product, SKU, variant or warehouse"></div>
		<div class="col-md-3"><label class="form-label">Warehouse</label><select name="warehouse_id" class="form-select"><option value="">All warehouses</option><?php foreach ($warehouses as $id => $label): ?><option value="<?php echo (int) $id; ?>" <?php echo (string) $this->input->get('warehouse_id', TRUE) === (string) $id ? 'selected' : ''; ?>><?php echo html_escape($label); ?></option><?php endforeach; ?></select></div>
		<div class="col-md-3"><label class="form-label">State</label><select name="state" class="form-select"><option value="">All stock</option><option value="low" <?php echo $this->input->get('state', TRUE) === 'low' ? 'selected' : ''; ?>>Low stock</option><option value="out" <?php echo $this->input->get('state', TRUE) === 'out' ? 'selected' : ''; ?>>Out of stock</option></select></div>
		<div class="col-md-2"><label class="form-label">Per page</label><?php echo per_page_selector($pagination['per_page']); ?></div>
	</div></div>
</form>

<div class="row g-4">
	<div class="col-xl-8">
		<div class="card table-card">
			<div class="table-responsive"><table class="table align-middle mb-0">
				<thead><tr><th><?php echo sort_link('Product', 'product_name'); ?></th><th>Warehouse</th><th class="text-end"><?php echo sort_link('On hand', 'quantity'); ?></th><th class="text-end">Reserved</th><th class="text-end"><?php echo sort_link('Available', 'available_quantity'); ?></th><th class="text-end">Reorder</th><th>State</th></tr></thead>
				<tbody>
					<?php if (empty($pagination['data'])): ?><tr><td colspan="7"><?php echo empty_state('No stock rows found', 'Stock appears here after opening balances, purchases or adjustments are recorded.', 'fa-warehouse', array('label' => 'Record Stock In', 'url' => site_url('admin/inventory/stock-in'), 'icon' => 'fa-arrow-down')); ?></td></tr>
					<?php else: foreach ($pagination['data'] as $row): ?>
						<tr>
							<td><div class="fw-semibold"><?php echo html_escape($row->product_name); ?></div><div class="small text-muted"><?php echo html_escape($row->product_sku); ?><?php if ($row->variant_sku): ?> · <?php echo html_escape($row->variant_sku); ?><?php endif; ?></div></td>
							<td><?php echo html_escape($row->warehouse_name); ?><?php if ($row->shelf_location): ?><div class="small text-muted"><?php echo html_escape($row->shelf_location); ?></div><?php endif; ?></td>
							<td class="text-end"><?php echo number_format((int) $row->quantity); ?></td>
							<td class="text-end"><?php echo number_format((int) $row->reserved_quantity); ?></td>
							<td class="text-end"><?php echo number_format((int) $row->available_quantity); ?></td>
							<td class="text-end"><?php echo number_format((int) $row->reorder_level); ?></td>
							<td><?php if ((int) $row->quantity <= 0): ?><span class="badge badge-soft badge-soft-danger">Out</span><?php elseif ((int) $row->quantity <= (int) $row->reorder_level): ?><span class="badge badge-soft badge-soft-warning">Low</span><?php else: ?><span class="badge badge-soft badge-soft-success">Healthy</span><?php endif; ?></td>
						</tr>
					<?php endforeach; endif; ?>
				</tbody>
			</table></div>
			<div class="card-footer"><?php echo render_pagination($pagination); ?></div>
		</div>
	</div>
	<div class="col-xl-4">
		<div class="card h-100"><div class="card-body">
			<h2 class="h5 mb-3">Recent Movements</h2>
			<?php if (empty($recent_movements)): ?><p class="text-muted mb-0">No stock movement has been recorded yet.</p><?php else: ?>
				<div class="list-group list-group-flush">
					<?php foreach ($recent_movements as $movement): ?>
						<div class="list-group-item px-0">
							<div class="d-flex justify-content-between gap-3"><strong><?php echo html_escape($movement->product_name); ?></strong><span class="<?php echo (int) $movement->quantity < 0 ? 'text-danger' : 'text-success'; ?>"><?php echo (int) $movement->quantity > 0 ? '+' : ''; ?><?php echo (int) $movement->quantity; ?></span></div>
							<div class="small text-muted"><?php echo html_escape(ucwords(str_replace('_', ' ', $movement->type))); ?> · <?php echo html_escape($movement->warehouse_name); ?> · <?php echo format_datetime($movement->created_at); ?></div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div></div>
	</div>
</div>
