<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Storefront header: announcement bar, brand, search, account, wishlist, cart
 * and the primary navigation with a mega-menu mount point.
 *
 * Phase 5 populates $mega_menu from the categories table; until then the
 * navigation falls back to static links so the storefront stays usable.
 *
 * @var int         $cart_count
 * @var int         $wishlist_count
 * @var object|null $current_user
 */

$mega_menu = isset($mega_menu) ? $mega_menu : array();
?>
<div class="announcement-bar">
	<div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
		<span><i class="fa-solid fa-truck-fast me-2"></i>Free shipping on orders above <?php echo money(999); ?></span>
		<span class="d-none d-md-inline">
			<i class="fa-solid fa-headset me-2"></i>
			<?php echo html_escape(array_get($app, 'support_phone', '')); ?>
		</span>
	</div>
</div>

<header class="store-header" id="storeHeader">
	<div class="container">
		<div class="header-main d-flex align-items-center gap-3">

			<button type="button" class="btn btn-icon d-lg-none" data-mobile-nav-toggle aria-label="Menu">
				<i class="fa-solid fa-bars"></i>
			</button>

			<a class="store-brand" href="<?php echo site_url(); ?>">
				<span class="brand-mark"><i class="fa-solid fa-bag-shopping"></i></span>
				<span class="brand-text"><?php echo html_escape($site_name); ?></span>
			</a>

			<form class="header-search flex-grow-1 d-none d-md-block" action="<?php echo site_url('search'); ?>" method="get" role="search">
				<div class="search-wrap">
					<i class="fa-solid fa-magnifying-glass"></i>
					<input type="search" name="q" class="form-control" placeholder="Search for products, brands and more…"
					       value="<?php echo html_escape($this->input->get('q', TRUE)); ?>"
					       autocomplete="off" data-live-search="<?php echo site_url('api/search/suggest'); ?>">
					<button type="submit" class="btn btn-primary">Search</button>
					<div class="search-suggestions" id="searchSuggestions" hidden></div>
				</div>
			</form>

			<div class="header-actions d-flex align-items-center gap-1 ms-auto">

				<?php if ($current_user): ?>
					<div class="dropdown">
						<button class="btn btn-icon-label" data-bs-toggle="dropdown" aria-expanded="false">
							<i class="fa-regular fa-user"></i>
							<span class="d-none d-xl-inline"><?php echo html_escape($current_user->first_name); ?></span>
						</button>
						<ul class="dropdown-menu dropdown-menu-end">
							<li><a class="dropdown-item" href="<?php echo site_url('account'); ?>">
								<i class="fa-solid fa-gauge me-2"></i>My Account</a></li>
							<li><a class="dropdown-item" href="<?php echo site_url('account/orders'); ?>">
								<i class="fa-solid fa-box me-2"></i>My Orders</a></li>
							<li><a class="dropdown-item" href="<?php echo site_url('account/wishlist'); ?>">
								<i class="fa-regular fa-heart me-2"></i>Wishlist</a></li>
							<?php if ($this->acl->is_admin()): ?>
								<li><hr class="dropdown-divider"></li>
								<li><a class="dropdown-item" href="<?php echo site_url('admin'); ?>">
									<i class="fa-solid fa-shield-halved me-2"></i>Admin Panel</a></li>
							<?php endif; ?>
							<li><hr class="dropdown-divider"></li>
							<li><a class="dropdown-item text-danger" href="<?php echo site_url('logout'); ?>">
								<i class="fa-solid fa-right-from-bracket me-2"></i>Sign out</a></li>
						</ul>
					</div>
				<?php else: ?>
					<a class="btn btn-icon-label" href="<?php echo site_url('login'); ?>">
						<i class="fa-regular fa-user"></i>
						<span class="d-none d-xl-inline">Sign in</span>
					</a>
				<?php endif; ?>

				<a class="btn btn-icon-label position-relative" href="<?php echo site_url('wishlist'); ?>">
					<i class="fa-regular fa-heart"></i>
					<span class="d-none d-xl-inline">Wishlist</span>
					<span class="count-badge" data-wishlist-count><?php echo (int) $wishlist_count; ?></span>
				</a>

				<a class="btn btn-icon-label position-relative" href="<?php echo site_url('cart'); ?>">
					<i class="fa-solid fa-cart-shopping"></i>
					<span class="d-none d-xl-inline">Cart</span>
					<span class="count-badge" data-cart-count><?php echo (int) $cart_count; ?></span>
				</a>
			</div>
		</div>

		<form class="header-search d-md-none pb-3" action="<?php echo site_url('search'); ?>" method="get" role="search">
			<div class="search-wrap">
				<i class="fa-solid fa-magnifying-glass"></i>
				<input type="search" name="q" class="form-control" placeholder="Search products…"
				       value="<?php echo html_escape($this->input->get('q', TRUE)); ?>">
			</div>
		</form>
	</div>

	<nav class="store-nav" id="storeNav">
		<div class="container">
			<ul class="nav-list">
				<li><a href="<?php echo site_url(); ?>">Home</a></li>

				<li class="has-mega">
					<a href="<?php echo site_url('shop'); ?>">Shop <i class="fa-solid fa-chevron-down small"></i></a>

					<?php if ( ! empty($mega_menu)): ?>
					<div class="mega-menu">
						<div class="container d-flex flex-wrap gap-4">
							<?php foreach ($mega_menu as $column): ?>
								<div class="mega-col">
									<h6><a href="<?php echo site_url('category/'.$column->slug); ?>">
										<?php echo html_escape($column->name); ?></a></h6>
									<ul>
										<?php foreach ((array) array_get($column, 'children', array()) as $child): ?>
											<li><a href="<?php echo site_url('category/'.$child->slug); ?>">
												<?php echo html_escape($child->name); ?></a></li>
										<?php endforeach; ?>
									</ul>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
					<?php endif; ?>
				</li>

				<li><a href="<?php echo site_url('deals'); ?>">Today's Deals</a></li>
				<li><a href="<?php echo site_url('brands'); ?>">Brands</a></li>
				<li><a href="<?php echo site_url('offers'); ?>">Offers</a></li>
				<li><a href="<?php echo site_url('blog'); ?>">Blog</a></li>
				<li><a href="<?php echo site_url('track-order'); ?>">Track Order</a></li>
				<li><a href="<?php echo site_url('contact'); ?>">Contact</a></li>
			</ul>
		</div>
	</nav>
</header>
