<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cart extends Store_Controller
{
	public function index()
	{
		$this->load->model('Store_model', 'store');
		$items = $this->store->cart_items($this->cart_identity());
		$this->render('cart', array('items' => $items, 'totals' => $this->totals($items), 'meta' => seo_meta(array('title' => seo_title('Cart'), 'canonical' => site_url('cart'), 'robots' => 'noindex,follow'))));
	}

	public function add()
	{
		$product_id = (int) $this->input->post('product_id', TRUE);
		$variant_id = (int) $this->input->post('variant_id', TRUE) ?: NULL;
		$quantity = max(1, (int) $this->input->post('quantity', TRUE));
		$product = $this->db->from('products')->where('id', $product_id)->where('status', 'active')->where('deleted_at IS NULL', NULL, FALSE)->get()->row();
		if ( ! $product) { show_404(); }
		$this->load->model('Store_model', 'store');
		$cart = $this->store->cart($this->cart_identity(), TRUE);
		$existing = $this->db->from('cart_items')->where(array('cart_id' => $cart->id, 'product_id' => $product_id, 'variant_id' => $variant_id))->where('deleted_at IS NULL', NULL, FALSE)->get()->row();
		$now = date('Y-m-d H:i:s');
		if ($existing)
		{
			$this->db->where('id', $existing->id)->update('cart_items', array('quantity' => min(999, (int) $existing->quantity + $quantity), 'updated_at' => $now));
		}
		else
		{
			$this->db->insert('cart_items', array('cart_id' => $cart->id, 'product_id' => $product_id, 'variant_id' => $variant_id, 'quantity' => $quantity, 'unit_price' => $variant_id ? $this->variant_price($variant_id, $product->price) : $product->price, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now));
		}
		$this->session->set_flashdata('success', 'Added to cart.');
		redirect('cart');
	}

	public function update()
	{
		$this->load->model('Store_model', 'store');
		$cart = $this->store->cart($this->cart_identity(), FALSE);
		if ($cart)
		{
			foreach ((array) $this->input->post('quantities', TRUE) as $item_id => $qty)
			{
				$this->db->where(array('id' => (int) $item_id, 'cart_id' => $cart->id))->update('cart_items', array('quantity' => max(1, (int) $qty), 'updated_at' => date('Y-m-d H:i:s')));
			}
		}
		$this->session->set_flashdata('success', 'Cart updated.');
		redirect('cart');
	}

	public function remove($id = NULL)
	{
		$this->load->model('Store_model', 'store');
		$cart = $this->store->cart($this->cart_identity(), FALSE);
		if ($cart) { $this->db->where(array('id' => (int) $id, 'cart_id' => $cart->id))->update('cart_items', array('deleted_at' => date('Y-m-d H:i:s'))); }
		$this->session->set_flashdata('success', 'Item removed.');
		redirect('cart');
	}

	public function apply_coupon()
	{
		$code = trim((string) $this->input->post('code', TRUE));
		$this->load->model('Store_model', 'store');
		$this->load->model('Coupon_model', 'coupons');
		$identity = $this->cart_identity();
		$cart = $this->store->cart($identity, FALSE);
		$items = $this->store->cart_items($identity);

		if ($code === '' || ! $cart || empty($items))
		{
			$this->session->set_flashdata('error', 'Add items to your cart before applying a coupon.');
			redirect('cart');
		}

		$coupon = $this->coupons->find_by_code($code);
		if ( ! $coupon)
		{
			$this->session->set_flashdata('error', 'Invalid coupon code.');
			redirect('cart');
		}

		$subtotal = 0; foreach ($items as $item) { $subtotal += (float) $item->unit_price * (int) $item->quantity; }
		$result = $this->coupons->evaluate($coupon, array(
			'user_id' => $this->auth->check() ? (int) $this->auth->id() : NULL,
			'items' => $items,
			'subtotal' => $subtotal,
		));

		if ( ! $result['success'])
		{
			$this->session->set_flashdata('error', $result['message']);
			redirect('cart');
		}

		$this->db->where('id', $cart->id)->update('carts', array('coupon_id' => $coupon->id, 'updated_at' => date('Y-m-d H:i:s')));
		$this->session->set_flashdata('success', $result['free_shipping'] ? 'Coupon applied: free shipping.' : 'Coupon applied! You saved '.money($result['discount']).'.');
		redirect('cart');
	}

	public function remove_coupon()
	{
		$this->load->model('Store_model', 'store');
		$cart = $this->store->cart($this->cart_identity(), FALSE);
		if ($cart) { $this->db->where('id', $cart->id)->update('carts', array('coupon_id' => NULL, 'updated_at' => date('Y-m-d H:i:s'))); }
		$this->session->set_flashdata('success', 'Coupon removed.');
		redirect('cart');
	}

	protected function totals(array $items)
	{
		return $this->cart_totals($items);
	}

	protected function variant_price($variant_id, $fallback)
	{
		$row = $this->db->from('product_variants')->where('id', (int) $variant_id)->where('deleted_at IS NULL', NULL, FALSE)->get()->row();
		return $row ? (float) $row->price : (float) $fallback;
	}
}
