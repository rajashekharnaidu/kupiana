<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo html_escape($meta['title']); ?></title>
	<meta name="robots" content="<?php echo html_escape($meta['robots']); ?>">
	<link rel="stylesheet" href="<?php echo base_url('public/assets/css/app.css'); ?>">
</head>
<body class="admin-shell">
	<aside>
		<a class="brand" href="<?php echo site_url('admin'); ?>">Kupiana Admin</a>
		<a href="<?php echo site_url(); ?>">Storefront</a>
		<a href="<?php echo site_url('logout'); ?>">Logout</a>
	</aside>
	<main>
		<?php echo $content; ?>
	</main>
</body>
</html>
