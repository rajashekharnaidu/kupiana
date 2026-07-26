<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Controller extends CI_Controller
{
	protected $data = array();

	public function __construct()
	{
		parent::__construct();

		$this->data['site_name'] = 'Kupiana';
		$this->data['current_user'] = $this->auth->user();
		$this->data['meta'] = seo_meta(array(
			'title' => 'Kupiana',
			'description' => 'Shop curated ecommerce products from Kupiana.',
			'canonical' => current_url(),
		));
	}

	protected function render($view, $data = array(), $layout = 'layouts/store')
	{
		$this->data = array_merge($this->data, $data);
		$this->data['content'] = $this->load->view($view, $this->data, TRUE);
		$this->load->view($layout, $this->data);
	}

	protected function require_login()
	{
		if (!$this->auth->check()) {
			$this->session->set_flashdata('error', 'Please sign in to continue.');
			redirect('login?redirect='.rawurlencode(uri_string()));
		}
	}

	protected function require_role($role)
	{
		$this->require_login();

		if (!$this->auth->has_role($role)) {
			show_error('You do not have permission to access this page.', 403);
		}
	}
}
