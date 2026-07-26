<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo html_escape($meta['title']); ?></title>
	<meta name="description" content="<?php echo html_escape($meta['description']); ?>">
	<meta name="robots" content="<?php echo html_escape($meta['robots']); ?>">
	<link rel="canonical" href="<?php echo html_escape($meta['canonical']); ?>">
	<meta property="og:title" content="<?php echo html_escape($meta['title']); ?>">
	<meta property="og:description" content="<?php echo html_escape($meta['description']); ?>">
	<meta property="og:type" content="<?php echo html_escape($meta['og_type']); ?>">
	<meta property="og:url" content="<?php echo html_escape($meta['canonical']); ?>">
	<meta property="og:image" content="<?php echo html_escape($meta['og_image']); ?>">
	<link rel="stylesheet" href="<?php echo base_url('public/assets/css/app.css'); ?>">
</head>
<body>
	<header class="site-header">
		<a class="brand" href="<?php echo site_url(); ?>">Kupiana</a>
		
	</header>

	<main class="store-main">
		<?php echo $content; ?>
	</main>
</body>
</html>
