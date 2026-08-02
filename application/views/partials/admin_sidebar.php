<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin sidebar.
 *
 * Renders application/config/admin_menu.php, hiding entries the current user
 * has no permission for and expanding the group containing $active_menu.
 *
 * @var string $active_menu
 */

$this->config->load('admin_menu', TRUE);

$menu   = (array) $this->config->item('admin_menu', 'admin_menu');
$active = isset($active_menu) ? $active_menu : '';
?>
<aside class="admin-sidebar" id="adminSidebar">

	<div class="sidebar-brand">
		<a href="<?php echo site_url('admin'); ?>" class="d-flex align-items-center gap-2">
			<span class="brand-mark"><i class="fa-solid fa-bag-shopping"></i></span>
			<span class="brand-text"><?php echo html_escape($site_name); ?></span>
		</a>
		<button type="button" class="btn btn-sm btn-icon sidebar-close d-lg-none" data-sidebar-toggle>
			<i class="fa-solid fa-xmark"></i>
		</button>
	</div>

	<nav class="sidebar-nav" role="navigation">
		<ul class="nav flex-column">
			<?php foreach ($menu as $item): ?>

				<?php if (isset($item['heading'])): ?>
					<li class="sidebar-heading"><?php echo html_escape($item['heading']); ?></li>
					<?php continue; ?>
				<?php endif; ?>

				<?php if ( ! empty($item['permission']) && ! can($item['permission'])): ?>
					<?php continue; ?>
				<?php endif; ?>

				<?php if (empty($item['children'])): ?>

					<li class="nav-item">
						<a class="nav-link<?php echo ($active === $item['key']) ? ' active' : ''; ?>"
						   href="<?php echo site_url($item['uri']); ?>">
							<i class="fa-solid <?php echo html_escape($item['icon']); ?> nav-icon"></i>
							<span class="nav-text"><?php echo html_escape($item['label']); ?></span>
						</a>
					</li>

				<?php else: ?>

					<?php
					// Only render the group if at least one child is permitted.
					$children = array();

					foreach ($item['children'] as $child)
					{
						if (empty($child['permission']) || can($child['permission']))
						{
							$children[] = $child;
						}
					}

					if (empty($children))
					{
						continue;
					}

					$is_open   = (strpos($active, $item['key'].'.') === 0) || ($active === $item['key']);
					$collapse  = 'menu-'.str_replace('.', '-', $item['key']);
					?>

					<li class="nav-item">
						<a class="nav-link nav-toggle<?php echo $is_open ? '' : ' collapsed'; ?>"
						   data-bs-toggle="collapse" href="#<?php echo $collapse; ?>"
						   role="button" aria-expanded="<?php echo $is_open ? 'true' : 'false'; ?>">
							<i class="fa-solid <?php echo html_escape($item['icon']); ?> nav-icon"></i>
							<span class="nav-text"><?php echo html_escape($item['label']); ?></span>
							<i class="fa-solid fa-chevron-right nav-caret"></i>
						</a>

						<div class="collapse<?php echo $is_open ? ' show' : ''; ?>" id="<?php echo $collapse; ?>">
							<ul class="nav flex-column sidebar-submenu">
								<?php foreach ($children as $child): ?>
									<li class="nav-item">
										<a class="nav-link<?php echo ($active === $child['key']) ? ' active' : ''; ?>"
										   href="<?php echo site_url($child['uri']); ?>">
											<span class="nav-dot"></span>
											<span class="nav-text"><?php echo html_escape($child['label']); ?></span>
										</a>
									</li>
								<?php endforeach; ?>
							</ul>
						</div>
					</li>

				<?php endif; ?>

			<?php endforeach; ?>
		</ul>
	</nav>

	<div class="sidebar-footer">
		<a href="<?php echo site_url(); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-light w-100">
			<i class="fa-solid fa-arrow-up-right-from-square me-2"></i>View Storefront
		</a>
	</div>
</aside>

<div class="sidebar-backdrop" data-sidebar-toggle></div>
