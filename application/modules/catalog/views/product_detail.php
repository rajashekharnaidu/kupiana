<section class="page-heading">
	<h1><?php echo html_escape(ucwords(str_replace('-', ' ', $slug))); ?></h1>
	<p>Explore product details, availability, and delivery options.</p>
</section>

<script type="application/ld+json">
<?php
echo json_encode(array(
	'@context' => 'https://schema.org',
	'@type' => 'Product',
	'name' => ucwords(str_replace('-', ' ', $slug)),
	'url' => site_url('products/'.$slug),
), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
?>
</script>
