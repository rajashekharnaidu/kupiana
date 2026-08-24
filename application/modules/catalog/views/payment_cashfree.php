<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="py-4 border-bottom bg-light"><div class="container"><h1 class="h3 mb-1">Complete Payment</h1><p class="text-muted mb-0">Order <?php echo html_escape($order->order_number); ?> - <?php echo money($order->total_amount); ?></p></div></section>
<section class="py-5"><div class="container">
	<div class="row g-4">
		<div class="col-lg-8">
			<div class="card">
				<div class="card-body">
					<h2 class="h5 mb-4">Payment Status</h2>
					<?php if ($payment_link): ?>
						<p class="mb-3">Click the button below to proceed to Cashfree payment gateway.</p>
						<a href="<?php echo html_escape($payment_link); ?>" class="btn btn-primary btn-lg">
							<i class="fa-solid fa-lock me-2"></i>Pay with Cashfree
						</a>
						<p class="small text-muted mt-3">You will be redirected to Cashfree to complete your payment securely.</p>
					<?php else: ?>
						<div class="alert alert-danger">
							<i class="fa-solid fa-exclamation-circle me-2"></i>
							Payment link could not be generated. Please try again or contact support.
						</div>
						<a href="<?php echo site_url('account/orders/'.$order->id); ?>" class="btn btn-secondary">Back to Order</a>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<div class="col-lg-4">
			<div class="card">
				<div class="card-body">
					<h2 class="h5 mb-3">Order Summary</h2>
					<div class="d-flex justify-content-between mb-2"><span>Order Number</span><span class="fw-semibold"><?php echo html_escape($order->order_number); ?></span></div>
					<div class="d-flex justify-content-between mb-2"><span>Total Amount</span><span class="fw-semibold"><?php echo money($order->total_amount); ?></span></div>
					<div class="d-flex justify-content-between mb-2"><span>Payment Method</span><span class="fw-semibold">Cashfree</span></div>
					<hr>
					<div class="d-flex justify-content-between"><span>Payment Status</span><span class="badge bg-warning"><?php echo ucfirst(html_escape($payment->status)); ?></span></div>
				</div>
			</div>
		</div>
	</div>
</div></section>
