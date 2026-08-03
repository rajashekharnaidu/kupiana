<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="py-4 border-bottom bg-light"><div class="container"><h1 class="h3 mb-1"><?php echo html_escape($page->title); ?></h1></div></section>
<section class="py-5"><div class="container"><div class="card"><div class="card-body"><?php echo $page->content ?: '<p class="text-muted mb-0">Content coming soon.</p>'; ?></div></div></div></section>
