<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php echo page_header('Create Purchase Entry', 'Record supplier purchase lines before receiving them into stock.', array(array('label' => 'Back to Purchases', 'url' => site_url('admin/purchases'), 'icon' => 'fa-arrow-left', 'class' => 'btn-outline-secondary'))); ?>
<?php if ($errors): ?><div class="alert alert-danger"><?php echo $errors; ?></div><?php endif; ?>

<form method="post" class="card" data-validate>
	<input type="hidden" name="<?php echo html_escape($this->security->get_csrf_token_name()); ?>" value="<?php echo html_escape($this->security->get_csrf_hash()); ?>">
	<div class="card-body">
		<div class="row g-3 mb-4">
			<div class="col-md-4"><label class="form-label">Supplier</label><select class="form-select" name="supplier_id" required><option value="">Select supplier</option><?php foreach ($suppliers as $id => $label): ?><option value="<?php echo (int) $id; ?>" <?php echo (string) $this->input->post('supplier_id', TRUE) === (string) $id ? 'selected' : ''; ?>><?php echo html_escape($label); ?></option><?php endforeach; ?></select></div>
			<div class="col-md-4"><label class="form-label">Receiving warehouse</label><select class="form-select" name="warehouse_id" required><option value="">Select warehouse</option><?php foreach ($warehouses as $id => $label): ?><option value="<?php echo (int) $id; ?>" <?php echo (string) $this->input->post('warehouse_id', TRUE) === (string) $id ? 'selected' : ''; ?>><?php echo html_escape($label); ?></option><?php endforeach; ?></select></div>
			<div class="col-md-2"><label class="form-label">Order date</label><input class="form-control" type="date" name="order_date" value="<?php echo html_escape(set_value('order_date', date('Y-m-d'))); ?>" required></div>
			<div class="col-md-2"><label class="form-label">Expected</label><input class="form-control" type="date" name="expected_date" value="<?php echo html_escape(set_value('expected_date')); ?>"></div>
			<div class="col-md-3"><label class="form-label">Shipping amount</label><input class="form-control" type="number" step="0.01" min="0" name="shipping_amount" value="<?php echo html_escape(set_value('shipping_amount', '0')); ?>"></div>
			<div class="col-md-9"><label class="form-label">Notes</label><input class="form-control" name="notes" value="<?php echo html_escape(set_value('notes')); ?>" maxlength="255"></div>
		</div>

		<h2 class="h5 mb-3">Items</h2>
		<div class="table-responsive"><table class="table align-middle" id="purchaseItems">
			<thead><tr><th style="min-width:220px;">Product</th><th style="min-width:190px;">Variant</th><th>Qty</th><th>Unit Cost</th><th>Tax %</th><th>Discount</th><th></th></tr></thead>
			<tbody>
				<?php for ($i = 0; $i < 3; $i++): ?>
					<tr>
						<td><select class="form-select" name="product_id[]"><option value="">Select product</option><?php foreach ($products as $id => $label): ?><option value="<?php echo (int) $id; ?>"><?php echo html_escape($label); ?></option><?php endforeach; ?></select></td>
						<td><select class="form-select" name="variant_id[]"><?php foreach ($variants as $id => $label): ?><option value="<?php echo (int) $id; ?>"><?php echo html_escape($label); ?></option><?php endforeach; ?></select></td>
						<td><input class="form-control" type="number" min="0" step="1" name="quantity[]" value="<?php echo $i === 0 ? '1' : ''; ?>"></td>
						<td><input class="form-control" type="number" min="0" step="0.01" name="unit_cost[]" value="0"></td>
						<td><input class="form-control" type="number" min="0" step="0.01" name="tax_rate[]" value="0"></td>
						<td><input class="form-control" type="number" min="0" step="0.01" name="discount_amount[]" value="0"></td>
						<td><button class="btn btn-sm btn-outline-danger" type="button" data-remove-row><i class="fa-solid fa-xmark"></i></button></td>
					</tr>
				<?php endfor; ?>
			</tbody>
		</table></div>
		<button class="btn btn-outline-secondary" type="button" data-add-purchase-row><i class="fa-solid fa-plus me-2"></i>Add Line</button>
	</div>
	<div class="card-footer text-end"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk me-2"></i>Create Purchase</button></div>
</form>

<script>
document.addEventListener('click', function (event) {
	if (event.target.closest('[data-remove-row]')) {
		var row = event.target.closest('tr');
		if (document.querySelectorAll('#purchaseItems tbody tr').length > 1) { row.remove(); }
	}
	if (event.target.closest('[data-add-purchase-row]')) {
		var body = document.querySelector('#purchaseItems tbody');
		var clone = body.querySelector('tr').cloneNode(true);
		clone.querySelectorAll('input').forEach(function (input) { input.value = input.name === 'quantity[]' ? '1' : '0'; });
		clone.querySelectorAll('select').forEach(function (select) { select.selectedIndex = 0; });
		body.appendChild(clone);
	}
});
</script>
