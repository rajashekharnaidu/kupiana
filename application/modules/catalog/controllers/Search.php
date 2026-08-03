<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Search extends Api_Controller
{
	protected $require_auth = FALSE;
	protected $allowed_methods = array('GET');

	public function suggest()
	{
		$q = trim((string) $this->input->get('q', TRUE));
		if (strlen($q) < 2) { $this->respond($this->api_response->success(array())); }
		$this->load->model('Store_model', 'store');
		$result = $this->store->products(array('q' => $q, 'per_page' => 6));
		$data = array();
		foreach ($result['data'] as $product)
		{
			$data[] = array('name' => $product->name, 'price' => (float) $product->price, 'url' => site_url('products/'.$product->slug), 'image' => upload_url($product->image_path));
		}
		$this->respond($this->api_response->success($data));
	}
}
