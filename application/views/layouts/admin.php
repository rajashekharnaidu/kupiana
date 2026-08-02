<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin layout.
 *
 * Expects: $meta, $content, $page_title, $breadcrumbs, $active_menu,
 *          $current_user, $flash, $app, $site_name.
 * Optional: $page_scripts (array of asset paths), $inline_script (raw HTML).
 */

$assets = $this->config->item('assets', 'app');
?>
<!doctype html>
<html lang="en" data-bs-theme="light">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="<?php echo html_escape($meta['robots']); ?>">
	<title><?php echo html_escape($meta['title']); ?></title>

	<link rel="icon" href="<?php echo base_url(array_get($app, 'favicon', 'public/assets/images/favicon.png')); ?>">
	<link rel="stylesheet" href="<?php echo $assets['google_fonts']; ?>">
	<link rel="stylesheet" href="<?php echo $assets['bootstrap_css']; ?>">
	<link rel="stylesheet" href="<?php echo $assets['fontawesome']; ?>">
	<link rel="stylesheet" href="<?php echo base_url('public/assets/css/app.css'); ?>">
</head>
<body class="admin-body">

	<div class="loading-overlay" id="loadingOverlay" aria-hidden="true">
		<div class="spinner-border text-primary" role="status">
			<span class="visually-hidden">Loading…</span>
		</div>
	</div>

	<div class="admin-layout" id="adminLayout">

		<?php $this->load->view('partials/admin_sidebar'); ?>

		<div class="admin-main">

			<?php $this->load->view('partials/admin_topbar'); ?>

			<main class="admin-content">
				<?php if ( ! empty($breadcrumbs)): ?>
					<div class="mb-3"><?php echo breadcrumbs($breadcrumbs); ?></div>
				<?php endif; ?>

				<?php echo $content; ?>
			</main>

			<footer class="admin-footer">
				<span>&copy; <?php echo date('Y'); ?> <?php echo html_escape($site_name); ?>. All rights reserved.</span>
				<span class="text-muted small">v<?php echo html_escape(array_get($app, 'version', '1.0.0')); ?></span>
			</footer>
		</div>
	</div>

	<div class="toast-container position-fixed top-0 end-0 p-3" id="toastContainer"></div>

	<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered modal-sm">
			<div class="modal-content">
				<div class="modal-body text-center p-4">
					<div class="confirm-icon mb-3"><i class="fa-solid fa-triangle-exclamation"></i></div>
					<h5 class="mb-2">Are you sure?</h5>
					<p class="text-muted mb-4" id="confirmMessage">This action cannot be undone.</p>
					<div class="d-flex gap-2 justify-content-center">
						<button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
						<button type="button" class="btn btn-danger" id="confirmAccept">Yes, continue</button>
					</div>
				</div>
			</div>
		</div>
	</div>

	<script>
		window.KUPIANA = {
			baseUrl:  <?php echo json_encode(base_url()); ?>,
			siteUrl:  <?php echo json_encode(site_url()); ?>,
			csrfName: <?php echo json_encode($this->security->get_csrf_token_name()); ?>,
			csrfHash: <?php echo json_encode($this->security->get_csrf_hash()); ?>,
			currency: <?php echo json_encode($this->config->item('currency', 'app')); ?>
		};
	</script>
	<script src="<?php echo $assets['jquery']; ?>"></script>
	<script src="<?php echo $assets['bootstrap_js']; ?>"></script>
	<script src="<?php echo $assets['chartjs']; ?>"></script>
	<script src="<?php echo base_url('public/assets/js/app.js'); ?>"></script>

	<?php if ( ! empty($flash)): ?>
	<script>
		<?php foreach ($flash as $message): ?>
		Kupiana.toast(<?php echo json_encode($message['type']); ?>, <?php echo json_encode($message['message']); ?>);
		<?php endforeach; ?>
	</script>
	<?php endif; ?>

	<?php if ( ! empty($page_scripts)): ?>
		<?php foreach ((array) $page_scripts as $script): ?>
			<script src="<?php echo base_url($script); ?>"></script>
		<?php endforeach; ?>
	<?php endif; ?>

	<?php echo isset($inline_script) ? $inline_script : ''; ?>
</body>
</html>
