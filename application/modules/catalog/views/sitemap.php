<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($urls as $url): ?>
	<url>
		<loc><?php echo html_escape($url['loc']); ?></loc>
		<?php if (!empty($url['lastmod'])): ?>
			<lastmod><?php echo html_escape($url['lastmod']); ?></lastmod>
		<?php endif; ?>
		<priority><?php echo html_escape($url['priority']); ?></priority>
	</url>
<?php endforeach; ?>
</urlset>
