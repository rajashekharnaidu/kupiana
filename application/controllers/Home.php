<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends Store_Controller
{
	public function index()
	{
		$this->load->add_package_path(APPPATH.'modules/catalog/');
		$this->load->model('Store_model', 'store');

		$this->render('home', array(
			'banners' => $this->store->banners('home_slider'),
			'categories' => $this->store->categories(TRUE),
			'featured' => $this->store->featured(8),
			'trending' => $this->store->products(array('trending' => 1, 'per_page' => 8)),
			'brands' => $this->store->brands(8),
			'offers' => $this->store->offers(4),
			'testimonials' => $this->store->testimonials(6),
			'meta' => seo_meta(array(
				'title' => seo_title('Organic Spices & Oils'),
				'description' => 'Shop organic spices, fresh-ground masalas, whole spices and cold-pressed cooking oils at Kupiana.',
			)),
		));
	}
}
