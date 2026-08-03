<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php echo page_header('Manage '.$product->name, $product->sku, array(array('label' => 'Back to Products', 'url' => site_url('admin/products'), 'icon' => 'fa-arrow-left', 'class' => 'btn-outline-secondary'))); ?>

<div class="row g-3">
	<div class="col-lg-4">
		<form method="post" action="<?php echo site_url('admin/products/relations/'.$product->id); ?>" class="card h-100">
			<input type="hidden" name="<?php echo html_escape($this->security->get_csrf_token_name()); ?>" value="<?php echo html_escape($this->security->get_csrf_hash()); ?>">
			<div class="card-body">
				<h6 class="mb-3">Categories & Tags</h6>
				<label class="form-label">Categories</label>
				<select class="form-select mb-3" name="category_ids[]" multiple size="8">
					<?php foreach ($categories as $id => $label): ?><option value="<?php echo (int) $id; ?>" <?php echo in_array((int) $id, $selected_categories, TRUE) ? 'selected' : ''; ?>><?php echo html_escape($label); ?></option><?php endforeach; ?>
				</select>
				<label class="form-label">Tags</label>
				<select class="form-select" name="tag_ids[]" multiple size="6">
					<?php foreach ($tags as $id => $label): ?><option value="<?php echo (int) $id; ?>" <?php echo in_array((int) $id, $selected_tags, TRUE) ? 'selected' : ''; ?>><?php echo html_escape($label); ?></option><?php endforeach; ?>
				</select>
			</div>
			<div class="card-footer text-end"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk me-2"></i>Save Relations</button></div>
		</form>
	</div>
	<div class="col-lg-8">
		<div class="card mb-3">
			<div class="card-body">
				<h6 class="mb-3">Gallery</h6>
				<form method="post" enctype="multipart/form-data" action="<?php echo site_url('admin/products/upload-image/'.$product->id); ?>" class="row g-2 align-items-end mb-3">
					<input type="hidden" name="<?php echo html_escape($this->security->get_csrf_token_name()); ?>" value="<?php echo html_escape($this->security->get_csrf_hash()); ?>">
					<div class="col-md-4"><label class="form-label">Image</label><input class="form-control" type="file" name="image" accept="image/*" required></div>
					<div class="col-md-3"><label class="form-label">Alt Text</label><input class="form-control" type="text" name="alt_text" value="<?php echo html_escape($product->name); ?>"></div>
					<div class="col-md-2"><label class="form-label">Sort</label><input class="form-control" type="number" name="sort_order" value="0"></div>
					<div class="col-md-2"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_primary" value="1" id="is_primary"><label class="form-check-label" for="is_primary">Primary</label></div></div>
					<div class="col-md-1"><button class="btn btn-primary w-100" type="submit"><i class="fa-solid fa-upload"></i></button></div>
				</form>
				<div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Image</th><th>Alt</th><th>Sort</th><th>Primary</th><th></th></tr></thead><tbody>
					<?php if (empty($images)): ?><tr><td colspan="5"><?php echo empty_state('No images yet', 'Upload product imagery for the storefront gallery.', 'fa-images'); ?></td></tr><?php else: foreach ($images as $image): ?>
						<tr><td><?php echo html_escape($image->image_path); ?></td><td><?php echo html_escape($image->alt_text); ?></td><td><?php echo (int) $image->sort_order; ?></td><td><?php echo bool_badge($image->is_primary); ?></td><td class="text-end"><a class="btn btn-sm btn-outline-danger" data-confirm="Remove this image?" href="<?php echo site_url('admin/products/delete-image/'.$product->id.'/'.$image->id); ?>"><i class="fa-solid fa-trash"></i></a></td></tr>
					<?php endforeach; endif; ?>
				</tbody></table></div>
			</div>
		</div>

		<div class="card">
			<div class="card-body">
				<h6 class="mb-3">Variant Attributes</h6>
				<?php if (empty($variants)): ?>
					<?php echo empty_state('No variants yet', 'Create variants first, then assign their attribute values here.', 'fa-code-branch', array('label' => 'Create Variant', 'url' => site_url('admin/variants/create'), 'icon' => 'fa-plus')); ?>
				<?php else: foreach ($variants as $variant): ?>
					<form method="post" action="<?php echo site_url('admin/products/variant-attributes/'.$product->id.'/'.$variant->id); ?>" class="border rounded p-3 mb-3">
						<input type="hidden" name="<?php echo html_escape($this->security->get_csrf_token_name()); ?>" value="<?php echo html_escape($this->security->get_csrf_hash()); ?>">
						<div class="d-flex justify-content-between mb-2"><strong><?php echo html_escape($variant->sku); ?></strong><span class="text-muted"><?php echo html_escape($variant->name ?: 'Variant'); ?></span></div>
						<div class="row g-2">
							<?php foreach ($attributes as $attribute_id => $attribute): ?>
								<div class="col-md-4"><label class="form-label"><?php echo html_escape($attribute['name']); ?></label><select class="form-select" name="attributes[<?php echo (int) $attribute_id; ?>]"><option value="">Select</option><?php foreach ($attribute['values'] as $value_id => $value): ?><option value="<?php echo (int) $value_id; ?>" <?php echo isset($variant->attributes[$attribute_id]) && (int) $variant->attributes[$attribute_id] === (int) $value_id ? 'selected' : ''; ?>><?php echo html_escape($value); ?></option><?php endforeach; ?></select></div>
							<?php endforeach; ?>
						</div>
						<div class="text-end mt-3"><button class="btn btn-sm btn-primary" type="submit">Save Variant Attributes</button></div>
					</form>
				<?php endforeach; endif; ?>
			</div>
		</div>
	</div>
</div>
