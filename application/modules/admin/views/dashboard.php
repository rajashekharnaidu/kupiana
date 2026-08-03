<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin dashboard.
 *
 * Live dashboard aggregates and reusable chart/table components.
 */
?>

<?php echo page_header(
	'Dashboard',
	'Overview of sales, orders and inventory.',
	array(
		array('label' => 'Export', 'url' => '#', 'icon' => 'fa-file-export', 'class' => 'btn-outline-secondary'),
		array('label' => 'Add Product', 'url' => site_url('admin/products/create'), 'icon' => 'fa-plus'),
	)
); ?>

<div class="card dashboard-brand-card mb-4">
	<div class="card-body d-flex flex-wrap align-items-center gap-3">
		<img class="dashboard-brand-logo" src="<?php echo base_url(array_get($app, 'logo', 'public/assets/images/kupiana-logo-512.png')); ?>" alt="<?php echo html_escape($site_name); ?> logo">
		<div>
			<h2 class="h5 mb-1"><?php echo html_escape($site_name); ?> Command Center</h2>
			<p class="text-muted mb-0"><?php echo html_escape(array_get($app, 'tagline', 'Curated commerce, delivered.')); ?></p>
		</div>
	</div>
</div>

<!-- KPI tiles -->
<div class="row g-3 mb-4">
	<?php
	$tiles = array(
		array('Total Revenue',  money_compact($kpis['revenue']), 'fa-indian-rupee-sign', 'primary', 0),
		array("Today's Sales",  money_compact($kpis['today']), 'fa-cart-shopping',     'success', 0),
		array('Total Orders',   number_format($kpis['orders']), 'fa-receipt',           'info',    0),
		array('Total Customers',number_format($kpis['customers']), 'fa-users',          'warning', 0),
	);

	foreach ($tiles as $tile): ?>
		<div class="col-12 col-sm-6 col-xl-3">
			<?php echo stat_card($tile[0], $tile[1], $tile[2], $tile[3], $tile[4]); ?>
		</div>
	<?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
	<?php
	$status_map = array(); foreach ($kpis['status_counts'] as $status_row) { $status_map[$status_row['status']] = $status_row['total']; }
	$secondary = array(
		array('Pending Orders',   number_format(isset($status_map['pending']) ? $status_map['pending'] : 0), 'fa-clock',         'secondary'),
		array('Delivered',        number_format(isset($status_map['delivered']) ? $status_map['delivered'] : 0), 'fa-circle-check',  'success'),
		array('Cancelled',        number_format(isset($status_map['cancelled']) ? $status_map['cancelled'] : 0), 'fa-ban',           'danger'),
		array('Low Stock Items',  number_format($kpis['low_stock']), 'fa-triangle-exclamation', 'warning'),
	);

	foreach ($secondary as $tile): ?>
		<div class="col-6 col-xl-3">
			<?php echo stat_card($tile[0], $tile[1], $tile[2], $tile[3]); ?>
		</div>
	<?php endforeach; ?>
</div>

<!-- Charts -->
<div class="row g-3 mb-4">
	<div class="col-12 col-xl-8">
		<div class="card h-100">
			<div class="card-header d-flex justify-content-between align-items-center">
				<span>Revenue Overview</span>
				<select class="form-select form-select-sm w-auto" id="revenueRange">
					<option value="7">Last 7 days</option>
					<option value="30" selected>Last 30 days</option>
					<option value="365">This year</option>
				</select>
			</div>
			<div class="card-body">
				<canvas id="revenueChart" height="110"></canvas>
			</div>
		</div>
	</div>

	<div class="col-12 col-xl-4">
		<div class="card h-100">
			<div class="card-header">Orders by Status</div>
			<div class="card-body d-flex align-items-center justify-content-center">
				<canvas id="statusChart" height="220"></canvas>
			</div>
		</div>
	</div>
</div>

<!-- Recent activity -->
<div class="row g-3">
	<div class="col-12 col-xl-7">
		<div class="card table-card h-100">
			<div class="card-header d-flex justify-content-between align-items-center">
				<span>Recent Orders</span>
				<a href="<?php echo site_url('admin/orders'); ?>" class="small">View all</a>
			</div>
			<div class="table-responsive">
				<table class="table align-middle">
					<thead>
						<tr>
							<th>Order</th>
							<th>Customer</th>
							<th>Status</th>
							<th class="text-end">Total</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td colspan="4" class="p-0">
								<?php echo empty_state(
									'No orders yet',
									'Orders will appear here once the storefront goes live.',
									'fa-receipt'
								); ?>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>

	<div class="col-12 col-xl-5">
		<div class="card table-card h-100">
			<div class="card-header d-flex justify-content-between align-items-center">
				<span>Top Selling Products</span>
				<a href="<?php echo site_url('admin/reports/products'); ?>" class="small">Report</a>
			</div>
			<div class="card-body p-0">
				<?php echo empty_state(
					'No sales data',
					'Best sellers are calculated from delivered orders.',
					'fa-ranking-star'
				); ?>
			</div>
		</div>
	</div>
</div>

<?php
/*
 * Chart bootstrapping.
 *
 * This block renders inside <main>, which the browser parses before the
 * layout's script tags, so the work is deferred to DOMContentLoaded — by then
 * Chart.js, jQuery and app.js have all executed.
 *
 * Phase 4 replaces the empty series with a JSON endpoint
 * (admin/dashboard/chart-data) consumed through Kupiana.ajax.
 */
?>
<script>
document.addEventListener('DOMContentLoaded', function () {
	if (typeof Chart === 'undefined') { return; }

	var styles = getComputedStyle(document.documentElement);
	var primary = styles.getPropertyValue('--k-primary').trim() || '#cc4e3a';
	var muted   = styles.getPropertyValue('--k-text-muted').trim() || '#7b6a5f';
	var border  = styles.getPropertyValue('--k-border').trim() || '#e9dfd6';

	var revenueEl = document.getElementById('revenueChart');
	var revenueChart = null;

	if (revenueEl) {
		var ctx = revenueEl.getContext('2d');
		var fill = ctx.createLinearGradient(0, 0, 0, 260);

		fill.addColorStop(0, 'rgba(204, 78, 58, .28)');
		fill.addColorStop(1, 'rgba(204, 78, 58, 0)');

		revenueChart = new Chart(ctx, {
			type: 'line',
			data: {
				labels: [],
				datasets: [{
					label: 'Revenue',
					data: [],
					borderColor: primary,
					backgroundColor: fill,
					borderWidth: 2,
					fill: true,
					tension: .35,
					pointRadius: 0,
					pointHoverRadius: 4
				}]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: {
					legend: { display: false },
					tooltip: {
						callbacks: {
							label: function (item) { return Kupiana.money(item.parsed.y); }
						}
					}
				},
				scales: {
					x: { grid: { display: false }, ticks: { color: muted } },
					y: {
						grid: { color: border },
						ticks: {
							color: muted,
							callback: function (value) { return Kupiana.money(value); }
						}
					}
				}
			}
		});
		if (typeof Kupiana !== 'undefined') {
			Kupiana.ajax({ url: <?php echo json_encode(site_url('admin/dashboard/chart_data')); ?>, method: 'GET', silent: true })
				.then(function (response) {
					if (revenueChart && response.data) {
						revenueChart.data.labels = response.data.labels;
						revenueChart.data.datasets[0].data = response.data.revenue;
						revenueChart.update();
					}
				});
		}
	}

	var statusEl = document.getElementById('statusChart');

	if (statusEl) {
		new Chart(statusEl, {
			type: 'doughnut',
			data: {
				labels: ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'],
				datasets: [{
				data: [<?php echo (int) (isset($status_map['pending']) ? $status_map['pending'] : 0); ?>, <?php echo (int) (isset($status_map['processing']) ? $status_map['processing'] : 0); ?>, <?php echo (int) (isset($status_map['shipped']) ? $status_map['shipped'] : 0); ?>, <?php echo (int) (isset($status_map['delivered']) ? $status_map['delivered'] : 0); ?>, <?php echo (int) (isset($status_map['cancelled']) ? $status_map['cancelled'] : 0); ?>],
					backgroundColor: ['#b6a79d', '#cc4e3a', '#0f7d8c', '#4b8b3b', '#a4133c'],
					borderWidth: 0
				}]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				cutout: '65%',
				plugins: { legend: { position: 'bottom', labels: { color: muted, boxWidth: 12 } } }
			}
		});
	}
});
</script>
