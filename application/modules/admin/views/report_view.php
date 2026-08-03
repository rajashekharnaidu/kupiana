<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$query = http_build_query(array('from' => $filters['from'], 'to' => $filters['to']));
$export_url = site_url('admin/reports/export/'.$current_type).($query ? '?'.$query : '');
$value = function ($amount) use ($report) { return $report['value_format'] === 'money' ? money($amount) : number_format((float) $amount, 2); };
?>
<?php echo page_header($report['title'], $report['description'], array(array('label' => 'Export CSV', 'url' => $export_url, 'icon' => 'fa-file-csv', 'class' => 'btn-outline-secondary'), array('label' => 'Dashboard', 'url' => site_url('admin'), 'icon' => 'fa-gauge-high', 'class' => 'btn-outline-secondary'))); ?>

<div class="card mb-3"><div class="card-body">
	<form class="row g-2 align-items-end" method="get">
		<div class="col-md-3"><label class="form-label">From</label><input class="form-control" type="date" name="from" value="<?php echo html_escape($filters['from']); ?>"></div>
		<div class="col-md-3"><label class="form-label">To</label><input class="form-control" type="date" name="to" value="<?php echo html_escape($filters['to']); ?>"></div>
		<div class="col-md-3"><button class="btn btn-primary w-100" type="submit">Apply Range</button></div>
		<div class="col-md-3"><a class="btn btn-outline-secondary w-100" href="<?php echo site_url('admin/reports/'.$current_type); ?>">Last 30 Days</a></div>
	</form>
</div></div>

<div class="card mb-3"><div class="card-body">
	<div class="d-flex flex-wrap gap-2">
		<?php foreach ($types as $key => $label): ?><a class="btn btn-sm <?php echo $current_type === $key ? 'btn-primary' : 'btn-outline-secondary'; ?>" href="<?php echo site_url('admin/reports/'.$key).($query ? '?'.$query : ''); ?>"><?php echo html_escape($label); ?></a><?php endforeach; ?>
	</div>
</div></div>

<div class="row g-3 mb-3">
	<div class="col-md-3"><div class="stat-card"><div class="stat-icon text-bg-primary"><i class="fa-solid fa-receipt"></i></div><div><div class="stat-label">Orders</div><div class="stat-value"><?php echo number_format($dashboard['orders']); ?></div></div></div></div>
	<div class="col-md-3"><div class="stat-card"><div class="stat-icon text-bg-success"><i class="fa-solid fa-indian-rupee-sign"></i></div><div><div class="stat-label">Revenue</div><div class="stat-value"><?php echo money($dashboard['revenue']); ?></div></div></div></div>
	<div class="col-md-3"><div class="stat-card"><div class="stat-icon text-bg-info"><i class="fa-solid fa-truck-fast"></i></div><div><div class="stat-label">Delivered</div><div class="stat-value"><?php echo number_format($dashboard['delivered_shipments']); ?>/<?php echo number_format($dashboard['shipments']); ?></div></div></div></div>
	<div class="col-md-3"><div class="stat-card"><div class="stat-icon text-bg-warning"><i class="fa-solid fa-rotate-left"></i></div><div><div class="stat-label">Returns</div><div class="stat-value"><?php echo number_format($dashboard['returns']); ?></div></div></div></div>
</div>

<div class="row g-3 mb-3">
	<div class="col-md-4"><div class="stat-card"><div class="stat-icon text-bg-primary"><i class="fa-solid fa-list-check"></i></div><div><div class="stat-label">Report Records</div><div class="stat-value"><?php echo number_format($report['totals']['records']); ?></div></div></div></div>
	<div class="col-md-4"><div class="stat-card"><div class="stat-icon text-bg-success"><i class="fa-solid fa-chart-line"></i></div><div><div class="stat-label"><?php echo html_escape($report['value_label']); ?></div><div class="stat-value"><?php echo $value($report['totals']['amount']); ?></div></div></div></div>
	<div class="col-md-4"><div class="stat-card"><div class="stat-icon text-bg-info"><i class="fa-solid fa-layer-group"></i></div><div><div class="stat-label">Groups</div><div class="stat-value"><?php echo number_format(count($report['rows'])); ?></div></div></div></div>
</div>

<div class="card table-card">
	<div class="table-responsive">
		<table class="table align-middle mb-0">
			<thead><tr><th><?php echo html_escape(ucwords(str_replace('_', ' ', $report['dimension']))); ?></th><th class="text-end">Records</th><th class="text-end"><?php echo html_escape($report['value_label']); ?></th><?php foreach ($report['extra_columns'] as $label): ?><th class="text-end"><?php echo html_escape($label); ?></th><?php endforeach; ?></tr></thead>
			<tbody>
				<?php if (empty($report['rows'])): ?>
					<tr><td colspan="<?php echo 3 + count($report['extra_columns']); ?>"><?php echo empty_state('No report data yet', 'Activity will appear here as orders, payments, shipments and inventory records are created.', 'fa-chart-simple'); ?></td></tr>
				<?php else: foreach ($report['rows'] as $row): ?>
					<tr>
						<td><?php echo html_escape($row['label']); ?></td>
						<td class="text-end"><?php echo number_format($row['records']); ?></td>
						<td class="text-end"><?php echo $value($row['amount']); ?></td>
						<?php foreach (array_keys($report['extra_columns']) as $key): ?><td class="text-end"><?php echo number_format((float) $row[$key], 2); ?></td><?php endforeach; ?>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
			<?php if ( ! empty($report['rows'])): ?><tfoot><tr><th>Total</th><th class="text-end"><?php echo number_format($report['totals']['records']); ?></th><th class="text-end"><?php echo $value($report['totals']['amount']); ?></th><?php foreach (array_keys($report['extra_columns']) as $key): ?><th class="text-end"><?php echo number_format((float) $report['totals']['extra'][$key], 2); ?></th><?php endforeach; ?></tr></tfoot><?php endif; ?>
		</table>
	</div>
</div>
