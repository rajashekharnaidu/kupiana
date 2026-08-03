<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="py-4 border-bottom bg-light"><div class="container"><h1 class="h3 mb-1">Pay for <?php echo html_escape($order->order_number); ?></h1><p class="text-muted mb-0">Amount due <?php echo money($payment->amount); ?></p></div></section>
<section class="py-5"><div class="container"><div class="row justify-content-center"><div class="col-lg-7"><div class="card"><div class="card-body text-center">
	<?php if ($razorpay_enabled): ?>
		<p class="text-muted">Click below to open Razorpay Checkout.</p>
		<form method="post" action="<?php echo site_url('payments/razorpay/verify'); ?>" id="razorpayForm">
			<input type="hidden" name="<?php echo html_escape($this->security->get_csrf_token_name()); ?>" value="<?php echo html_escape($this->security->get_csrf_hash()); ?>">
			<input type="hidden" name="razorpay_order_id" value="<?php echo html_escape($payment->gateway_order_id); ?>">
			<input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
			<input type="hidden" name="razorpay_signature" id="razorpay_signature">
			<button type="button" class="btn btn-primary btn-lg" id="payButton">Pay <?php echo money($payment->amount); ?></button>
		</form>
		<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
		<script>
		document.getElementById('payButton').addEventListener('click', function () {
			var rzp = new Razorpay({
				key: <?php echo json_encode($razorpay_key_id); ?>,
				amount: <?php echo (int) round((float) $payment->amount * 100); ?>,
				currency: <?php echo json_encode($payment->currency); ?>,
				name: <?php echo json_encode($site_name); ?>,
				description: <?php echo json_encode('Order '.$order->order_number); ?>,
				order_id: <?php echo json_encode($payment->gateway_order_id); ?>,
				prefill: { name: <?php echo json_encode($order->customer_name); ?>, email: <?php echo json_encode($order->customer_email); ?>, contact: <?php echo json_encode($order->customer_phone); ?> },
				handler: function (response) {
					document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id;
					document.getElementById('razorpay_signature').value = response.razorpay_signature;
					document.getElementById('razorpayForm').submit();
				}
			});
			rzp.open();
		});
		</script>
	<?php else: ?>
		<div class="alert alert-warning text-start">Razorpay keys are not configured locally. Use the simulator to verify the paid-order path without external charges.</div>
		<a class="btn btn-primary btn-lg" href="<?php echo site_url('payments/razorpay/simulate/'.$payment->id); ?>">Simulate Successful Payment</a>
	<?php endif; ?>
</div></div></div></div></div></section>
