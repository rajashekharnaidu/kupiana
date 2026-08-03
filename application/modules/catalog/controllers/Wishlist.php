<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Wishlist extends Store_Controller
{
	public function index()
	{
		$products = $this->wishlist_products();
		$this->render('wishlist', array('products' => $products, 'meta' => seo_meta(array('title' => seo_title('Wishlist'), 'canonical' => site_url('wishlist'), 'robots' => 'noindex,follow'))));
	}

	public function add($product_id = NULL)
	{
		$product_id = (int) $product_id;
		$product = $this->db->from('products')->where('id', $product_id)->where('status', 'active')->where('deleted_at IS NULL', NULL, FALSE)->get()->row();
		if ( ! $product) { show_404(); }
		if ($this->auth->check())
		{
			$user_id = (int) $this->session->userdata('user_id');
			$exists = $this->db->where(array('user_id' => $user_id, 'product_id' => $product_id, 'variant_id' => NULL))->count_all_results('wishlists') > 0;
			$now = date('Y-m-d H:i:s');
			if ($exists) { $this->db->where(array('user_id' => $user_id, 'product_id' => $product_id))->update('wishlists', array('deleted_at' => NULL, 'status' => 'active', 'updated_at' => $now)); }
			else { $this->db->insert('wishlists', array('user_id' => $user_id, 'product_id' => $product_id, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now)); }
		}
		else
		{
			$wishlist = (array) $this->session->userdata('wishlist');
			$wishlist[$product_id] = $product_id;
			$this->session->set_userdata('wishlist', $wishlist);
		}
		$this->session->set_flashdata('success', 'Added to wishlist.');
		redirect('wishlist');
	}

	public function remove($product_id = NULL)
	{
		if ($this->auth->check())
		{
			$this->db->where(array('user_id' => (int) $this->session->userdata('user_id'), 'product_id' => (int) $product_id))->update('wishlists', array('deleted_at' => date('Y-m-d H:i:s')));
		}
		else
		{
			$wishlist = (array) $this->session->userdata('wishlist');
			unset($wishlist[(int) $product_id]);
			$this->session->set_userdata('wishlist', $wishlist);
		}
		$this->session->set_flashdata('success', 'Removed from wishlist.');
		redirect('wishlist');
	}

	protected function wishlist_products()
	{
		$this->load->model('Store_model', 'store');
		if ($this->auth->check())
		{
			$ids = array();
			foreach ($this->db->from('wishlists')->where('user_id', (int) $this->session->userdata('user_id'))->where('deleted_at IS NULL', NULL, FALSE)->get()->result() as $row) { $ids[] = (int) $row->product_id; }
		}
		else { $ids = array_values((array) $this->session->userdata('wishlist')); }
		if (empty($ids)) { return array(); }
		$products = $this->db->select('products.*, brands.name AS brand_name')->from('products')->join('brands', 'brands.id = products.brand_id', 'left')->where_in('products.id', $ids)->where('products.deleted_at IS NULL', NULL, FALSE)->get()->result();
		return $this->store->attach_images($products);
	}
}
