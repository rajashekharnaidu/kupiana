<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends MY_Controller
{
	public function index()
	{
		$this->require_role('admin');

		$this->render('dashboard', array(
			'meta' => seo_meta(array(
				'title' => seo_title('Admin Dashboard'),
				'description' => 'Kupiana admin dashboard.',
				'robots' => 'noindex,nofollow',
			)),
		), 'layouts/admin');
	}
}
