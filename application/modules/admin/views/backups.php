<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php echo page_header('Backups', 'Create, download and restore database backups.', array(array('label' => 'Create Backup', 'url' => site_url('admin/backups/create'), 'icon' => 'fa-database'))); ?>

<div class="card table-card">
	<div class="table-responsive">
		<table class="table align-middle mb-0">
			<thead><tr><th>Filename</th><th>Status</th><th>Size</th><th>Created</th><th class="text-end">Actions</th></tr></thead>
			<tbody>
				<?php if (empty($backups)): ?><tr><td colspan="5"><?php echo empty_state('No backups yet', 'Create the first database backup from this screen.', 'fa-database'); ?></td></tr><?php else: foreach ($backups as $backup): ?>
					<tr>
						<td><div class="fw-semibold"><?php echo html_escape($backup->filename); ?></div><?php if ($backup->error_message): ?><div class="small text-danger"><?php echo html_escape($backup->error_message); ?></div><?php endif; ?></td>
						<td><?php echo status_badge($backup->backup_status); ?></td>
						<td><?php echo number_format((int) $backup->size_bytes); ?> bytes</td>
						<td><?php echo format_datetime($backup->created_at); ?></td>
						<td class="text-end">
							<a class="btn btn-sm btn-outline-secondary" href="<?php echo site_url('admin/backups/download/'.$backup->id); ?>"><i class="fa-solid fa-download"></i></a>
							<form method="post" action="<?php echo site_url('admin/backups/restore/'.$backup->id); ?>" class="d-inline-flex gap-1 align-items-center">
								<input type="hidden" name="<?php echo html_escape($this->security->get_csrf_token_name()); ?>" value="<?php echo html_escape($this->security->get_csrf_hash()); ?>">
								<input class="form-control form-control-sm" name="confirm" placeholder="<?php echo html_escape($backup->filename); ?>" style="max-width: 180px;">
								<button class="btn btn-sm btn-outline-danger" type="submit" data-confirm="Restore this backup? Current database data will be overwritten."><i class="fa-solid fa-rotate-left"></i></button>
							</form>
						</td>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
</div>
