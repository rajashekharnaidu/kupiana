<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin topbar: sidebar toggle, global search, theme switch, notifications,
 * profile menu.
 *
 * @var object|null $current_user
 * @var string      $page_title
 */

$display_name = $current_user
	? trim($current_user->first_name.' '.$current_user->last_name)
	: 'Account';

$initials = strtoupper(substr($display_name, 0, 1));
?>
<header class="admin-topbar">

	<button type="button" class="btn btn-icon sidebar-trigger" data-sidebar-toggle aria-label="Toggle menu">
		<i class="fa-solid fa-bars"></i>
	</button>

	<h2 class="topbar-title d-none d-md-block"><?php echo html_escape(isset($page_title) ? $page_title : 'Dashboard'); ?></h2>

	<form class="topbar-search d-none d-lg-flex" action="<?php echo site_url('admin/search'); ?>" method="get" role="search">
		<i class="fa-solid fa-magnifying-glass"></i>
		<input type="search" name="q" class="form-control" placeholder="Search orders, products, customers…"
		       value="<?php echo html_escape($this->input->get('q', TRUE)); ?>" autocomplete="off">
	</form>

	<div class="topbar-actions ms-auto">

		<button type="button" class="btn btn-icon" data-theme-toggle aria-label="Toggle theme">
			<i class="fa-solid fa-moon theme-icon-dark"></i>
			<i class="fa-solid fa-sun theme-icon-light"></i>
		</button>

		<div class="dropdown">
			<button class="btn btn-icon position-relative" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
				<i class="fa-solid fa-bell"></i>
				<span class="notification-dot" id="notificationDot" hidden></span>
			</button>
			<div class="dropdown-menu dropdown-menu-end notification-menu">
				<div class="dropdown-header d-flex justify-content-between align-items-center">
					<span>Notifications</span>
					<a href="<?php echo site_url('admin/notifications'); ?>" class="small">View all</a>
				</div>
				<div id="notificationList">
					<div class="dropdown-item-text text-muted small text-center py-4">
						<i class="fa-regular fa-bell-slash d-block mb-2 fs-4"></i>
						No new notifications
					</div>
				</div>
			</div>
		</div>

		<div class="dropdown">
			<button class="btn profile-trigger" data-bs-toggle="dropdown" aria-expanded="false">
				<span class="avatar"><?php echo html_escape($initials); ?></span>
				<span class="d-none d-md-inline text-start">
					<span class="profile-name"><?php echo html_escape($display_name); ?></span>
					<span class="profile-role"><?php echo html_escape(ucwords(str_replace('_', ' ', implode(', ', $this->acl->roles())))); ?></span>
				</span>
				<i class="fa-solid fa-chevron-down small ms-1"></i>
			</button>
			<ul class="dropdown-menu dropdown-menu-end">
				<li><a class="dropdown-item" href="<?php echo site_url('admin/profile'); ?>">
					<i class="fa-solid fa-user me-2"></i>My Profile</a></li>
				<li><a class="dropdown-item" href="<?php echo site_url('admin/settings'); ?>">
					<i class="fa-solid fa-gear me-2"></i>Settings</a></li>
				<li><hr class="dropdown-divider"></li>
				<li><a class="dropdown-item text-danger" href="<?php echo site_url('logout'); ?>">
					<i class="fa-solid fa-right-from-bracket me-2"></i>Sign out</a></li>
			</ul>
		</div>
	</div>
</header>
