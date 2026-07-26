<section class="page-heading">
	<h1>My Account</h1>
	<p>Welcome <?php echo html_escape($current_user ? $current_user->first_name : ''); ?>.</p>
</section>

<section class="metric-grid">
	<article>
		<strong>Orders</strong>
		<span>Track order history and order status.</span>
	</article>
	<article>
		<strong>Addresses</strong>
		<span>Manage shipping and billing addresses.</span>
	</article>
	<article>
		<strong>Profile</strong>
		<span>Keep contact details up to date.</span>
	</article>
</section>
