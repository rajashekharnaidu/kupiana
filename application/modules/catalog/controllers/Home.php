<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends Store_Controller
{
	public function index()
	{
		$this->render('home', array(
			'meta' => seo_meta(array(
				'title' => seo_title('Organic Spices & Oils'),
				'description' => 'Discover organic spices, fresh-ground masalas, whole spices and cold-pressed cooking oils at Kupiana.',
				'canonical' => site_url(),
			)),
			'json_ld' => seo_json_ld_graph(array(
				seo_organization_schema(),
				seo_website_schema(),
				seo_breadcrumb_schema(array('Home' => site_url())),
			)),
		));
	}
}
