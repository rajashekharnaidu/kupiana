<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="py-4 border-bottom bg-light"><div class="container"><h1 class="h3 mb-1">Brands</h1><p class="text-muted mb-0">Explore curated brands available on Kupiana.</p></div></section>
<section class="py-5"><div class="container"><div class="row g-3">
	<?php if (empty($brands)): ?><div class="col-12"><?php echo empty_state('No brands yet', 'Brands will appear here once they are active.', 'fa-copyright'); ?></div><?php else: foreach ($brands as $brand): ?>
		<div class="col-6 col-md-4 col-lg-3"><a class="card h-100 p-3 text-center" href="<?php echo site_url('brand/'.$brand->slug); ?>"><i class="fa-solid fa-award fa-2x text-primary mb-2"></i><strong><?php echo html_escape($brand->name); ?></strong><span class="small text-muted"><?php echo html_escape($brand->website ?: 'View products'); ?></span></a></div>
	<?php endforeach; endif; ?>
</div></div></section>
