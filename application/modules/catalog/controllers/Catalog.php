<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Catalog extends MY_Controller
{
	public function index()
	{
		$this->render('home', array(
			'meta' => seo_meta(array(
				'title' => seo_title('Coming Soon'),
				'description' => 'Kupiana is launching soon with curated everyday pieces, gifting ideas, and beautiful essentials.',
			)),
		));
	}
}
