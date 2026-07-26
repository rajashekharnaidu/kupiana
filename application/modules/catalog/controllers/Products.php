<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Products extends MY_Controller
{
	public function detail($slug)
	{
		$this->render('product_detail', array(
			'slug' => $slug,
			'meta' => seo_meta(array(
				'title' => seo_title(ucwords(str_replace('-', ' ', $slug))),
				'description' => 'View product details, pricing, availability, and structured ecommerce data.',
				'canonical' => site_url('products/'.$slug),
			)),
		));
	}

	public function category($slug)
	{
		$this->render('category', array(
			'slug' => $slug,
			'meta' => seo_meta(array(
				'title' => seo_title(ucwords(str_replace('-', ' ', $slug))),
				'description' => 'Browse Kupiana products by category.',
				'canonical' => site_url('category/'.$slug),
			)),
		));
	}
}
