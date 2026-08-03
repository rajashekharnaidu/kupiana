<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php echo page_header($title, 'Record a stock movement and update the product inventory balance.', array(array('label' => 'Stock Overview', 'url' => site_url('admin/inventory'), 'icon' => 'fa-warehouse', 'class' => 'btn-outline-secondary'))); ?>
<?php if ($errors): ?><div class="alert alert-danger"><?php echo $errors; ?></div><?php endif; ?>

<form method="post" class="card" data-validate>
	<input type="hidden" name="<?php echo html_escape($this->security->get_csrf_token_name()); ?>" value="<?php echo html_escape($this->security->get_csrf_hash()); ?>">
	<div class="card-body">
		<div class="row g-3">
			<div class="col-md-6">
				<label class="form-label" for="product_id">Product</label>
				<select class="form-select" name="product_id" id="product_id" required>
					<option value="">Select product</option>
					<?php foreach ($products as $id => $label): ?><option value="<?php echo (int) $id; ?>" <?php echo (string) $this->input->post('product_id', TRUE) === (string) $id ? 'selected' : ''; ?>><?php echo html_escape($label); ?></option><?php endforeach; ?>
				</select>
			</div>
			<div class="col-md-6">
				<label class="form-label" for="variant_id">Variant</label>
				<select class="form-select" name="variant_id" id="variant_id">
					<option value="0">No variant</option>
					<?php foreach ($variants as $id => $label): ?><option value="<?php echo (int) $id; ?>" <?php echo (string) $this->input->post('variant_id', TRUE) === (string) $id ? 'selected' : ''; ?>><?php echo html_escape($label); ?></option><?php endforeach; ?>
				</select>
			</div>
			<div class="col-md-6">
				<label class="form-label" for="warehouse_id">Warehouse</label>
				<select class="form-select" name="warehouse_id" id="warehouse_id" required>
					<option value="">Select warehouse</option>
					<?php foreach ($warehouses as $id => $label): ?><option value="<?php echo (int) $id; ?>" <?php echo (string) $this->input->post('warehouse_id', TRUE) === (string) $id ? 'selected' : ''; ?>><?php echo html_escape($label); ?></option><?php endforeach; ?>
				</select>
			</div>
			<div class="col-md-6">
				<label class="form-label" for="quantity">Quantity</label>
				<input type="number" min="1" step="1" class="form-control" name="quantity" id="quantity" value="<?php echo html_escape($this->input->post('quantity', TRUE)); ?>" required>
			</div>
			<?php if ($mode === 'adjustment'): ?>
				<div class="col-md-6">
					<label class="form-label" for="direction">Direction</label>
					<select class="form-select" name="direction" id="direction">
						<option value="increase" <?php echo $this->input->post('direction', TRUE) !== 'decrease' ? 'selected' : ''; ?>>Increase stock</option>
						<option value="decrease" <?php echo $this->input->post('direction', TRUE) === 'decrease' ? 'selected' : ''; ?>>Decrease stock</option>
					</select>
				</div>
				<div class="col-md-6">
					<label class="form-label" for="reason">Reason</label>
					<input type="text" class="form-control" name="reason" id="reason" maxlength="150" value="<?php echo html_escape($this->input->post('reason', TRUE)); ?>" required>
				</div>
			<?php endif; ?>
			<div class="col-md-6">
				<label class="form-label" for="unit_cost">Unit Cost</label>
				<input type="number" min="0" step="0.01" class="form-control" name="unit_cost" id="unit_cost" value="<?php echo html_escape($this->input->post('unit_cost', TRUE)); ?>">
			</div>
			<div class="col-md-<?php echo $mode === 'adjustment' ? '6' : '12'; ?>">
				<label class="form-label" for="notes">Notes</label>
				<input type="text" class="form-control" name="notes" id="notes" maxlength="255" value="<?php echo html_escape($this->input->post('notes', TRUE)); ?>">
			</div>
		</div>
	</div>
	<div class="card-footer d-flex justify-content-end gap-2">
		<a class="btn btn-light" href="<?php echo site_url('admin/inventory'); ?>">Cancel</a>
		<button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-2"></i>Record Movement</button>
	</div>
</form>
