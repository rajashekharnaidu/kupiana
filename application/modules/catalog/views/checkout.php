<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$address = $default_address;
$first = $current_user ? $current_user->first_name : ($address ? $address->first_name : '');
$last = $current_user ? $current_user->last_name : ($address ? $address->last_name : '');
$email = $current_user ? $current_user->email : '';
$phone = $current_user && $current_user->phone ? $current_user->phone : ($address ? $address->phone : '');
?>
<section class="py-4 border-bottom bg-light"><div class="container"><h1 class="h3 mb-1">Checkout</h1><p class="text-muted mb-0">Place your order. Razorpay is added in Phase 8; COD is available now.</p></div></section>
<section class="py-5"><div class="container">
	<form method="post" action="<?php echo site_url('checkout'); ?>" class="row g-4" data-validate>
		<input type="hidden" name="<?php echo html_escape($this->security->get_csrf_token_name()); ?>" value="<?php echo html_escape($this->security->get_csrf_hash()); ?>">
		<div class="col-lg-8">
			<div class="card mb-4"><div class="card-body">
				<h2 class="h5 mb-3">Shipping Details</h2>
				<div class="row g-3">
					<div class="col-md-6"><label class="form-label">First name</label><input class="form-control" name="first_name" value="<?php echo html_escape(set_value('first_name', $first)); ?>" required></div>
					<div class="col-md-6"><label class="form-label">Last name</label><input class="form-control" name="last_name" value="<?php echo html_escape(set_value('last_name', $last)); ?>"></div>
					<div class="col-md-6"><label class="form-label">Email</label><input class="form-control" type="email" name="email" value="<?php echo html_escape(set_value('email', $email)); ?>" required></div>
					<div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" name="phone" value="<?php echo html_escape(set_value('phone', $phone)); ?>" required></div>
					<div class="col-12"><label class="form-label">Address line 1</label><input class="form-control" name="address_line1" value="<?php echo html_escape(set_value('address_line1', $address ? $address->address_line1 : '')); ?>" required></div>
					<div class="col-12"><label class="form-label">Address line 2</label><input class="form-control" name="address_line2" value="<?php echo html_escape(set_value('address_line2', $address ? $address->address_line2 : '')); ?>"></div>
					<div class="col-md-4"><label class="form-label">City</label><input class="form-control" name="city" value="<?php echo html_escape(set_value('city', $address ? $address->city : '')); ?>" required></div>
					<div class="col-md-4"><label class="form-label">State</label><input class="form-control" name="state" value="<?php echo html_escape(set_value('state', $address ? $address->state : '')); ?>" required></div>
					<div class="col-md-2"><label class="form-label">State code</label><input class="form-control" name="state_code" value="<?php echo html_escape(set_value('state_code', $address ? $address->state_code : '29')); ?>"></div>
					<div class="col-md-2"><label class="form-label">PIN</label><input class="form-control" name="postal_code" value="<?php echo html_escape(set_value('postal_code', $address ? $address->postal_code : '')); ?>" required></div>
					<div class="col-12"><label class="form-label">Order note</label><textarea class="form-control" name="customer_note" rows="3"><?php echo html_escape(set_value('customer_note')); ?></textarea></div>
				</div>
			</div></div>
			<div class="card"><div class="card-body">
				<h2 class="h5 mb-3">Payment</h2>
				<div class="form-check border rounded p-3 ps-5 mb-2"><input class="form-check-input" type="radio" name="payment_method" value="cod" id="pay_cod" checked><label class="form-check-label fw-semibold" for="pay_cod">Cash on Delivery</label><div class="small text-muted">Pay when the order arrives.</div></div>
				<div class="form-check border rounded p-3 ps-5"><input class="form-check-input" type="radio" name="payment_method" value="razorpay" id="pay_razorpay"><label class="form-check-label fw-semibold" for="pay_razorpay">Razorpay Online Payment</label><div class="small text-muted"><?php echo $razorpay_available ? 'Pay securely with card, UPI, wallet or netbanking.' : 'Keys are not configured locally; the Phase 8 offline simulator will be used.'; ?></div></div>
			</div></div>
		</div>
		<div class="col-lg-4">
			<div class="card sticky-top" style="top: 1rem;"><div class="card-body">
				<h2 class="h5 mb-3">Order Summary</h2>
				<?php foreach ($items as $item): ?><div class="d-flex justify-content-between gap-3 border-bottom py-2"><div><div class="fw-semibold"><?php echo html_escape($item->name); ?></div><div class="small text-muted">Qty <?php echo (int) $item->quantity; ?></div></div><span><?php echo money($item->unit_price * $item->quantity); ?></span></div><?php endforeach; ?>
				<div class="d-flex justify-content-between mt-3 mb-2"><span>Subtotal</span><span><?php echo money($totals['subtotal']); ?></span></div>
				<div class="d-flex justify-content-between mb-2"><span>Shipping</span><span><?php echo money($totals['shipping']); ?></span></div>
				<hr><div class="d-flex justify-content-between h5"><span>Payable</span><span><?php echo money($totals['total']); ?></span></div>
				<button class="btn btn-primary w-100 mt-3" type="submit"><i class="fa-solid fa-bag-shopping me-2"></i>Place Order</button>
			</div></div>
		</div>
	</form>
</div></section>
