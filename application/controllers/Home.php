<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends Store_Controller
{
	public function index()
	{
		$this->load->add_package_path(APPPATH.'modules/catalog/');

		$this->render('home', array(
			'meta' => seo_meta(array(
				'title' => seo_title('Coming Soon'),
				'description' => 'Kupiana is launching soon with curated everyday pieces, gifting ideas, and beautiful essentials.',
			)),
		));
	}
}
