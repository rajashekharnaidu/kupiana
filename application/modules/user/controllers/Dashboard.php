<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends MY_Controller
{
	public function index()
	{
		$this->require_role('user');

		$this->render('dashboard', array(
			'meta' => seo_meta(array(
				'title' => seo_title('My Account'),
				'description' => 'Manage your Kupiana account, orders, and addresses.',
				'robots' => 'noindex,follow',
			)),
		));
	}
}
