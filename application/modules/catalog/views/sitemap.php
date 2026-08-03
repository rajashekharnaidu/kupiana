<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
	xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
<?php foreach ($urls as $url): ?>
	<url>
		<loc><?php echo html_escape($url['loc']); ?></loc>
		<?php if (!empty($url['lastmod'])): ?>
			<lastmod><?php echo html_escape($url['lastmod']); ?></lastmod>
		<?php endif; ?>
		<?php if (!empty($url['changefreq'])): ?>
			<changefreq><?php echo html_escape($url['changefreq']); ?></changefreq>
		<?php endif; ?>
		<priority><?php echo html_escape($url['priority']); ?></priority>
		<?php if (!empty($url['image'])): ?>
			<image:image>
				<image:loc><?php echo html_escape($url['image']); ?></image:loc>
				<?php if (!empty($url['image_title'])): ?>
					<image:title><?php echo html_escape($url['image_title']); ?></image:title>
				<?php endif; ?>
			</image:image>
		<?php endif; ?>
	</url>
<?php endforeach; ?>
</urlset>
