<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php echo page_header($page_title, 'Manage runtime configuration stored in the settings table.'); ?>

<ul class="nav nav-pills mb-3">
	<?php foreach ($tabs as $key => $label): ?>
		<li class="nav-item"><a class="nav-link<?php echo $group === $key ? ' active' : ''; ?>" href="<?php echo site_url($key === 'general' ? 'admin/settings' : 'admin/settings/'.$key); ?>"><?php echo html_escape($label); ?></a></li>
	<?php endforeach; ?>
</ul>

<form method="post" class="card" data-validate>
	<input type="hidden" name="<?php echo html_escape($this->security->get_csrf_token_name()); ?>" value="<?php echo html_escape($this->security->get_csrf_hash()); ?>">
	<div class="card-body">
		<?php if (empty($rows)): ?>
			<?php echo empty_state('No settings in this group', 'Add settings rows through the generic settings resource or seed file.', 'fa-gear'); ?>
		<?php else: ?>
			<div class="row g-3">
				<?php foreach ($rows as $row): ?>
					<?php $key = $row->setting_key; $type = $row->setting_type; $value = set_value($key, $row->setting_value); ?>
					<div class="col-md-<?php echo $type === 'textarea' ? '12' : '6'; ?>">
						<label class="form-label" for="setting_<?php echo html_escape($key); ?>"><?php echo html_escape($row->label ?: ucwords(str_replace('_', ' ', $key))); ?></label>
						<?php if ($type === 'bool'): ?>
							<input type="hidden" name="<?php echo html_escape($key); ?>" value="0">
							<div class="form-check form-switch pt-2">
								<input class="form-check-input" type="checkbox" role="switch" name="<?php echo html_escape($key); ?>" id="setting_<?php echo html_escape($key); ?>" value="1" <?php echo (string) $value === '1' ? 'checked' : ''; ?>>
								<label class="form-check-label" for="setting_<?php echo html_escape($key); ?>">Enabled</label>
							</div>
						<?php elseif ($type === 'textarea'): ?>
							<textarea class="form-control" name="<?php echo html_escape($key); ?>" id="setting_<?php echo html_escape($key); ?>" rows="4"><?php echo html_escape($value); ?></textarea>
						<?php elseif ($type === 'number'): ?>
							<input type="number" step="0.01" class="form-control" name="<?php echo html_escape($key); ?>" id="setting_<?php echo html_escape($key); ?>" value="<?php echo html_escape($value); ?>">
						<?php else: ?>
							<input type="text" class="form-control" name="<?php echo html_escape($key); ?>" id="setting_<?php echo html_escape($key); ?>" value="<?php echo html_escape($value); ?>">
						<?php endif; ?>
						<?php if ( ! empty($row->description)): ?><div class="form-text"><?php echo html_escape($row->description); ?></div><?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
	<?php if ( ! empty($rows)): ?>
		<div class="card-footer d-flex justify-content-end gap-2">
			<a class="btn btn-light" href="<?php echo site_url('admin/settings'); ?>">Cancel</a>
			<button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-2"></i>Save Settings</button>
		</div>
	<?php endif; ?>
</form>
