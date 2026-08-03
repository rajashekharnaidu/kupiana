<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Storefront layout.
 *
 * Expects: $meta, $content, $site_name, $app, $flash, $cart_count,
 *          $wishlist_count, $current_user.
 * Optional: $page_scripts, $inline_script, $json_ld, $body_class.
 */

$assets = $this->config->item('assets', 'app');
?>
<!doctype html>
<html lang="en" data-bs-theme="light">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo html_escape($meta['title']); ?></title>

	<meta name="description" content="<?php echo html_escape($meta['description']); ?>">
	<?php if ( ! empty($meta['keywords'])): ?>
	<meta name="keywords" content="<?php echo html_escape($meta['keywords']); ?>">
	<?php endif; ?>
	<meta name="robots" content="<?php echo html_escape($meta['robots']); ?>">
	<link rel="canonical" href="<?php echo html_escape($meta['canonical']); ?>">
	<?php if ( ! empty($meta['rel_prev'])): ?>
	<link rel="prev" href="<?php echo html_escape($meta['rel_prev']); ?>">
	<?php endif; ?>
	<?php if ( ! empty($meta['rel_next'])): ?>
	<link rel="next" href="<?php echo html_escape($meta['rel_next']); ?>">
	<?php endif; ?>
	<link rel="alternate" hreflang="en-IN" href="<?php echo html_escape($meta['canonical']); ?>">
	<link rel="alternate" hreflang="x-default" href="<?php echo html_escape($meta['canonical']); ?>">

	<meta property="og:site_name" content="<?php echo html_escape($site_name); ?>">
	<meta property="og:locale" content="en_IN">
	<meta property="og:title" content="<?php echo html_escape($meta['og_title']); ?>">
	<meta property="og:description" content="<?php echo html_escape($meta['og_description']); ?>">
	<meta property="og:type" content="<?php echo html_escape($meta['og_type']); ?>">
	<meta property="og:url" content="<?php echo html_escape($meta['canonical']); ?>">
	<meta property="og:image" content="<?php echo html_escape($meta['og_image']); ?>">

	<meta name="twitter:card" content="<?php echo html_escape($meta['twitter_card']); ?>">
	<meta name="twitter:title" content="<?php echo html_escape($meta['og_title']); ?>">
	<meta name="twitter:description" content="<?php echo html_escape($meta['og_description']); ?>">
	<meta name="twitter:image" content="<?php echo html_escape($meta['og_image']); ?>">
	<meta name="twitter:url" content="<?php echo html_escape($meta['canonical']); ?>">

	<link rel="icon" href="<?php echo base_url(array_get($app, 'favicon', 'public/assets/images/favicon.png')); ?>">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link rel="stylesheet" href="<?php echo $assets['google_fonts']; ?>">
	<link rel="stylesheet" href="<?php echo $assets['bootstrap_css']; ?>">
	<link rel="stylesheet" href="<?php echo $assets['fontawesome']; ?>">
	<link rel="stylesheet" href="<?php echo base_url('public/assets/css/app.css'); ?>">

	<?php if ( ! empty($json_ld)): ?>
	<script type="application/ld+json"><?php echo is_array($json_ld) ? json_encode($json_ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : $json_ld; ?></script>
	<?php endif; ?>
</head>
<body class="store-body <?php echo isset($body_class) ? html_escape($body_class) : ''; ?>">

	<div class="loading-overlay" id="loadingOverlay" aria-hidden="true">
		<div class="spinner-border text-primary" role="status">
			<span class="visually-hidden">Loading…</span>
		</div>
	</div>

	<?php $this->load->view('partials/store_header'); ?>

	<main class="store-main" id="mainContent">
		<?php echo $content; ?>
	</main>

	<?php $this->load->view('partials/store_footer'); ?>

	<button type="button" class="btn btn-primary btn-back-to-top" id="backToTop" aria-label="Back to top">
		<i class="fa-solid fa-arrow-up"></i>
	</button>

	<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer"></div>

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
			currency: <?php echo json_encode($this->config->item('currency', 'app')); ?>,
			loggedIn: <?php echo $current_user ? 'true' : 'false'; ?>
		};
	</script>
	<script src="<?php echo $assets['jquery']; ?>"></script>
	<script src="<?php echo $assets['bootstrap_js']; ?>"></script>
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
