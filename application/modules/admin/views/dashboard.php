<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin dashboard.
 *
 * Phase 1: renders the shell and the reusable components (stat cards, chart
 * canvases, table cards, empty states) so the design system is verifiable.
 * Phase 4 swaps the placeholder values for live model data.
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

<div class="alert alert-info d-flex align-items-start gap-3" role="alert">
	<i class="fa-solid fa-circle-info mt-1"></i>
	<div>
		<strong>Phase 1 complete — architecture only.</strong>
		<div class="small">
			These tiles show placeholder values. Live figures arrive in Phase 4, once the
			Phase 2 schema and Phase 3 authentication are in place. See
			<code>PROJECT_STATUS.md</code> for the build plan.
		</div>
	</div>
</div>

<!-- KPI tiles -->
<div class="row g-3 mb-4">
	<?php
	$tiles = array(
		array('Total Revenue',  money_compact(0), 'fa-indian-rupee-sign', 'primary', 0),
		array("Today's Sales",  money_compact(0), 'fa-cart-shopping',     'success', 0),
		array('Total Orders',   '0',              'fa-receipt',           'info',    0),
		array('Total Customers','0',              'fa-users',             'warning', 0),
	);

	foreach ($tiles as $tile): ?>
		<div class="col-12 col-sm-6 col-xl-3">
			<?php echo stat_card($tile[0], $tile[1], $tile[2], $tile[3], $tile[4]); ?>
		</div>
	<?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
	<?php
	$secondary = array(
		array('Pending Orders',   '0', 'fa-clock',         'secondary'),
		array('Delivered',        '0', 'fa-circle-check',  'success'),
		array('Cancelled',        '0', 'fa-ban',           'danger'),
		array('Low Stock Items',  '0', 'fa-triangle-exclamation', 'warning'),
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
	var primary = styles.getPropertyValue('--k-primary').trim() || '#4f46e5';
	var muted   = styles.getPropertyValue('--k-text-muted').trim() || '#6b7280';
	var border  = styles.getPropertyValue('--k-border').trim() || '#e5e7eb';

	var revenueEl = document.getElementById('revenueChart');

	if (revenueEl) {
		var ctx = revenueEl.getContext('2d');
		var fill = ctx.createLinearGradient(0, 0, 0, 260);

		fill.addColorStop(0, 'rgba(79, 70, 229, .25)');
		fill.addColorStop(1, 'rgba(79, 70, 229, 0)');

		new Chart(ctx, {
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
	}

	var statusEl = document.getElementById('statusChart');

	if (statusEl) {
		new Chart(statusEl, {
			type: 'doughnut',
			data: {
				labels: ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'],
				datasets: [{
					data: [0, 0, 0, 0, 0],
					backgroundColor: ['#94a3b8', '#4f46e5', '#0ea5e9', '#16a34a', '#dc2626'],
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
