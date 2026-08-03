<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php echo page_header('Purchase '.$purchase->po_number, 'Receive supplier stock and review purchase movement history.', array(array('label' => 'Back to Purchases', 'url' => site_url('admin/purchases'), 'icon' => 'fa-arrow-left', 'class' => 'btn-outline-secondary'))); ?>

<div class="row g-4">
	<div class="col-lg-4">
		<div class="card h-100"><div class="card-body">
			<h2 class="h5">Summary</h2>
			<p class="mb-1">Supplier: <strong><?php echo html_escape($purchase->supplier_name); ?></strong></p>
			<p class="mb-1">Warehouse: <strong><?php echo html_escape($purchase->warehouse_name); ?></strong></p>
			<p class="mb-1">Ordered: <?php echo format_date($purchase->order_date); ?></p>
			<p class="mb-1">Expected: <?php echo $purchase->expected_date ? format_date($purchase->expected_date) : '—'; ?></p>
			<p class="mb-1">Receive: <?php echo status_badge($purchase->receive_status); ?></p>
			<p class="mb-0">Total: <strong><?php echo money($purchase->total_amount); ?></strong></p>
		</div></div>
	</div>
	<div class="col-lg-8">
		<form method="post" action="<?php echo site_url('admin/purchases/receive/'.$purchase->id); ?>" class="card">
			<input type="hidden" name="<?php echo html_escape($this->security->get_csrf_token_name()); ?>" value="<?php echo html_escape($this->security->get_csrf_hash()); ?>">
			<div class="card-body">
				<h2 class="h5 mb-3">Receive Items</h2>
				<div class="table-responsive"><table class="table align-middle mb-0">
					<thead><tr><th>Product</th><th class="text-end">Ordered</th><th class="text-end">Received</th><th class="text-end">Pending</th><th>Receive now</th><th>Batch</th></tr></thead>
					<tbody><?php foreach ($items as $item): $pending = max(0, (int) $item->quantity - (int) $item->received_quantity); ?>
						<tr>
							<td><div class="fw-semibold"><?php echo html_escape($item->product_name); ?></div><div class="small text-muted"><?php echo html_escape($item->product_sku); ?><?php if ($item->variant_sku): ?> · <?php echo html_escape($item->variant_sku); ?><?php endif; ?></div></td>
							<td class="text-end"><?php echo (int) $item->quantity; ?></td>
							<td class="text-end"><?php echo (int) $item->received_quantity; ?></td>
							<td class="text-end"><?php echo $pending; ?></td>
							<td><input class="form-control form-control-sm" type="number" min="0" max="<?php echo $pending; ?>" name="received_quantity[<?php echo (int) $item->id; ?>]" value="<?php echo $pending > 0 ? $pending : 0; ?>" <?php echo $pending <= 0 ? 'readonly' : ''; ?>></td>
							<td><input class="form-control form-control-sm" name="batch_number[<?php echo (int) $item->id; ?>]" placeholder="Optional batch" <?php echo $pending <= 0 ? 'readonly' : ''; ?>></td>
						</tr>
					<?php endforeach; ?></tbody>
				</table></div>
			</div>
			<div class="card-footer text-end"><button class="btn btn-primary" type="submit" <?php echo $purchase->receive_status === 'received' ? 'disabled' : ''; ?>><i class="fa-solid fa-box-open me-2"></i>Receive Stock</button></div>
		</form>
	</div>
</div>

<div class="card mt-4"><div class="card-body">
	<h2 class="h5 mb-3">Receipt Ledger</h2>
	<?php if (empty($movements)): ?><p class="text-muted mb-0">No stock has been received for this purchase yet.</p><?php else: ?>
		<div class="table-responsive"><table class="table mb-0"><thead><tr><th>Date</th><th>Type</th><th class="text-end">Quantity</th><th class="text-end">Balance After</th><th>Notes</th></tr></thead><tbody>
			<?php foreach ($movements as $movement): ?><tr><td><?php echo format_datetime($movement->created_at); ?></td><td><?php echo html_escape(ucwords(str_replace('_', ' ', $movement->type))); ?></td><td class="text-end"><?php echo (int) $movement->quantity; ?></td><td class="text-end"><?php echo (int) $movement->balance_after; ?></td><td><?php echo html_escape($movement->notes); ?></td></tr><?php endforeach; ?>
		</tbody></table></div>
	<?php endif; ?>
</div></div>
