<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<article>
	<section class="py-4 border-bottom bg-light">
		<div class="container">
			<nav class="small mb-3" aria-label="Breadcrumb"><a href="<?php echo site_url(); ?>">Home</a> / <a href="<?php echo site_url('blog'); ?>">Blog</a></nav>
			<h1 class="h2 mb-2"><?php echo html_escape($post->title); ?></h1>
			<p class="text-muted mb-0"><?php echo format_date($post->published_at ?: $post->created_at); ?></p>
		</div>
	</section>
	<section class="py-5">
		<div class="container">
			<?php if ($post->featured_image): ?>
				<img class="img-fluid rounded-3 border mb-4" src="<?php echo upload_url($post->featured_image); ?>" alt="<?php echo html_escape($post->title); ?>">
			<?php endif; ?>
			<div class="card"><div class="card-body">
				<?php echo $post->content; ?>
			</div></div>
		</div>
	</section>
</article>
