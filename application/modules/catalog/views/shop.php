<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="py-4 border-bottom bg-light">
	<div class="container"><h1 class="h3 mb-1"><?php echo html_escape($page_title); ?></h1><p class="text-muted mb-0">Showing <?php echo (int) $pagination['total']; ?> products.</p></div>
</section>

<section class="py-4">
	<div class="container">
		<form method="get" class="row g-3 mb-4" data-filter-form>
			<div class="col-md-4"><input class="form-control" type="search" name="q" placeholder="Search products" value="<?php echo html_escape($this->input->get('q', TRUE)); ?>"></div>
			<div class="col-md-2"><input class="form-control" type="number" name="min_price" placeholder="Min" value="<?php echo html_escape($this->input->get('min_price', TRUE)); ?>"></div>
			<div class="col-md-2"><input class="form-control" type="number" name="max_price" placeholder="Max" value="<?php echo html_escape($this->input->get('max_price', TRUE)); ?>"></div>
			<div class="col-md-2"><select class="form-select" name="sort"><option value="">Featured</option><?php foreach (array('newest' => 'Newest', 'popular' => 'Popular', 'price_asc' => 'Price low to high', 'price_desc' => 'Price high to low') as $key => $label): ?><option value="<?php echo $key; ?>" <?php echo $this->input->get('sort', TRUE) === $key ? 'selected' : ''; ?>><?php echo $label; ?></option><?php endforeach; ?></select></div>
			<div class="col-md-2"><button class="btn btn-primary w-100" type="submit">Apply</button></div>
		</form>
		<div class="row g-3">
			<?php if (empty($pagination['data'])): ?><div class="col-12"><?php echo empty_state('No products found', 'Try another search or filter.', 'fa-magnifying-glass'); ?></div><?php else: foreach ($pagination['data'] as $product): ?><div class="col-6 col-md-4 col-lg-3"><?php $this->load->view('_product_card', array('product' => $product)); ?></div><?php endforeach; endif; ?>
		</div>
		<div class="mt-4"><?php echo render_pagination($pagination); ?></div>
	</div>
</section>
