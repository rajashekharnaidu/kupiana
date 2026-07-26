<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends MY_Controller
{
	public function index()
	{
		$this->render('home', array(
			'meta' => seo_meta(array(
				'title' => seo_title('Online Store'),
				'description' => 'Discover featured products, collections, and new arrivals at Kupiana.',
			)),
		));
	}
}
