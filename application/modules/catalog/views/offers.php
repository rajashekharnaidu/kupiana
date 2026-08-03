<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="py-4 border-bottom bg-light"><div class="container"><h1 class="h3 mb-1">Offers</h1><p class="text-muted mb-0">Current promotions and campaign offers.</p></div></section>
<section class="py-5"><div class="container"><div class="row g-3">
	<?php if (empty($offers)): ?><div class="col-12"><?php echo empty_state('No active offers', 'Check back for new promotions.', 'fa-tags'); ?></div><?php else: foreach ($offers as $offer): ?>
		<div class="col-md-6 col-lg-4"><div class="card h-100"><div class="card-body"><span class="badge badge-soft badge-soft-primary mb-2"><?php echo html_escape(ucwords($offer->discount_type)); ?></span><h2 class="h5"><?php echo html_escape($offer->title); ?></h2><p class="text-muted"><?php echo html_escape($offer->description); ?></p><div class="h4 mb-0"><?php echo $offer->discount_type === 'percentage' ? (float) $offer->discount_value.'%' : money($offer->discount_value); ?> off</div></div></div></div>
	<?php endforeach; endif; ?>
</div></div></section>
