<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php echo page_header('Audit Logs', 'Filter back-office activity by action, entity, actor and date.', array(array('label' => 'Export CSV', 'url' => site_url('admin/audit-logs/export').'?'.http_build_query($filters), 'icon' => 'fa-file-csv', 'class' => 'btn-outline-secondary'))); ?>

<form method="get" class="card mb-3">
	<div class="card-body"><div class="row g-2 align-items-end">
		<div class="col-md-2"><label class="form-label">Entity</label><select class="form-select" name="entity"><option value="">All</option><?php foreach ($entities as $entity): ?><option value="<?php echo html_escape($entity); ?>" <?php echo $filters['entity'] === $entity ? 'selected' : ''; ?>><?php echo html_escape($entity); ?></option><?php endforeach; ?></select></div>
		<div class="col-md-2"><label class="form-label">Action</label><select class="form-select" name="action"><option value="">All</option><?php foreach ($actions as $action): ?><option value="<?php echo html_escape($action); ?>" <?php echo $filters['action'] === $action ? 'selected' : ''; ?>><?php echo html_escape($action); ?></option><?php endforeach; ?></select></div>
		<div class="col-md-2"><label class="form-label">User</label><input class="form-control" name="user" value="<?php echo html_escape($filters['user']); ?>"></div>
		<div class="col-md-2"><label class="form-label">From</label><input class="form-control" type="date" name="from" value="<?php echo html_escape($filters['from']); ?>"></div>
		<div class="col-md-2"><label class="form-label">To</label><input class="form-control" type="date" name="to" value="<?php echo html_escape($filters['to']); ?>"></div>
		<div class="col-md-2"><button class="btn btn-primary w-100" type="submit">Filter</button></div>
	</div></div>
</form>

<div class="card table-card">
	<div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Time</th><th>User</th><th>Action</th><th>Entity</th><th>IP</th><th>Description</th></tr></thead><tbody>
		<?php if (empty($rows)): ?><tr><td colspan="6"><?php echo empty_state('No audit records', 'Try changing the filters.', 'fa-shield-halved'); ?></td></tr><?php else: foreach ($rows as $row): ?>
			<tr><td><?php echo format_datetime($row->created_at); ?></td><td><?php echo html_escape($row->user_name ?: ('#'.$row->user_id)); ?></td><td><span class="badge text-bg-light"><?php echo html_escape($row->action); ?></span></td><td><?php echo html_escape($row->entity); ?><?php if ($row->entity_id): ?><span class="text-muted small"> #<?php echo (int) $row->entity_id; ?></span><?php endif; ?></td><td><?php echo html_escape($row->ip_address); ?></td><td><?php echo html_escape($row->description); ?></td></tr>
		<?php endforeach; endif; ?>
	</tbody></table></div>
	<div class="card-footer"><?php echo render_pagination($pagination); ?></div>
</div>
