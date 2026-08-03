<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php echo page_header('Payment '.$payment->payment_number, 'Gateway state, order sync and event logs.', array(array('label' => 'Back to Payments', 'url' => site_url('admin/payments'), 'icon' => 'fa-arrow-left', 'class' => 'btn-outline-secondary'))); ?>

<div class="row g-4">
	<div class="col-lg-4"><div class="card h-100"><div class="card-body">
		<h2 class="h5">Summary</h2>
		<p class="mb-1">Order: <a href="<?php echo site_url('admin/orders/view/'.$order->id); ?>"><?php echo html_escape($order->order_number); ?></a></p>
		<p class="mb-1">Gateway: <?php echo html_escape(ucwords($payment->gateway)); ?></p>
		<p class="mb-1">Method: <?php echo html_escape($payment->method ?: '—'); ?></p>
		<p class="mb-1">Gateway status: <?php echo status_badge($payment->status); ?></p>
		<p class="mb-1">Order payment: <?php echo status_badge($order->payment_status, 'payment'); ?></p>
		<p class="h5 mt-3"><?php echo money($payment->amount); ?></p>
	</div></div></div>
	<div class="col-lg-4"><div class="card h-100"><div class="card-body">
		<h2 class="h5">Gateway IDs</h2>
		<p class="small mb-1">Order ID</p><code><?php echo html_escape($payment->gateway_order_id ?: '—'); ?></code>
		<p class="small mt-3 mb-1">Payment ID</p><code><?php echo html_escape($payment->gateway_payment_id ?: '—'); ?></code>
		<p class="small mt-3 mb-1">Paid at</p><span><?php echo $payment->paid_at ? format_datetime($payment->paid_at) : '—'; ?></span>
	</div></div></div>
	<div class="col-lg-4"><div class="card h-100"><div class="card-body">
		<h2 class="h5">Actions</h2>
		<?php if ($payment->status !== 'captured'): ?><form method="post" action="<?php echo site_url('admin/payments/capture/'.$payment->id); ?>" class="mb-3"><input type="hidden" name="<?php echo html_escape($this->security->get_csrf_token_name()); ?>" value="<?php echo html_escape($this->security->get_csrf_hash()); ?>"><input class="form-control mb-2" name="method" placeholder="Method, e.g. bank_transfer"><button class="btn btn-primary w-100" type="submit">Mark Captured</button></form><?php endif; ?>
		<?php if (in_array($payment->status, array('captured', 'partially_refunded'), TRUE)): ?><form method="post" action="<?php echo site_url('admin/payments/refund/'.$payment->id); ?>"><input type="hidden" name="<?php echo html_escape($this->security->get_csrf_token_name()); ?>" value="<?php echo html_escape($this->security->get_csrf_hash()); ?>"><input class="form-control mb-2" type="number" step="0.01" min="1" max="<?php echo html_escape($payment->amount); ?>" name="amount" placeholder="Refund amount"><input class="form-control mb-2" name="reason" placeholder="Reason"><button class="btn btn-outline-danger w-100" type="submit">Record Refund</button></form><?php endif; ?>
	</div></div></div>
</div>

<div class="card mt-4"><div class="card-body">
	<h2 class="h5 mb-3">Payment Logs</h2>
	<?php if (empty($logs)): ?><p class="text-muted mb-0">No payment events yet.</p><?php else: ?><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Date</th><th>Event</th><th>IP</th><th>Response</th></tr></thead><tbody><?php foreach ($logs as $log): ?><tr><td><?php echo format_datetime($log->created_at); ?></td><td><?php echo html_escape($log->event); ?></td><td><?php echo html_escape($log->ip_address); ?></td><td><code><?php echo html_escape(mb_strimwidth((string) $log->response, 0, 120, '…')); ?></code></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</div></div>
