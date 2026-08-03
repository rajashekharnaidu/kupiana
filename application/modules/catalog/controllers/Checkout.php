<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Checkout flow.
 *
 * Phase 7 supports COD order placement. Razorpay attaches to the same order
 * records in Phase 8.
 *
 * @package Kupiana\Modules\Catalog
 */
class Checkout extends Store_Controller
{
	/**
	 * Show checkout and place orders.
	 *
	 * @return void
	 */
	public function index()
	{
		$this->load->model('Order_model', 'orders');
		$this->load->model('Store_model', 'store');
		$identity = $this->cart_identity();
		$items = $this->store->cart_items($identity);
		if (empty($items))
		{
			$this->session->set_flashdata('error', 'Your cart is empty.');
			redirect('cart');
		}

		if ($this->input->method(TRUE) === 'POST')
		{
			$this->form_validation->set_rules('first_name', 'First Name', 'required|max_length[100]');
			$this->form_validation->set_rules('email', 'Email', 'required|valid_email|max_length[191]');
			$this->form_validation->set_rules('phone', 'Phone', 'required|max_length[20]');
			$this->form_validation->set_rules('address_line1', 'Address', 'required|max_length[255]');
			$this->form_validation->set_rules('city', 'City', 'required|max_length[100]');
			$this->form_validation->set_rules('state', 'State', 'required|max_length[100]');
			$this->form_validation->set_rules('postal_code', 'PIN Code', 'required|max_length[20]');
			$this->form_validation->set_rules('payment_method', 'Payment Method', 'required|in_list[cod,razorpay]');

			if ($this->form_validation->run() === TRUE)
			{
				$result = $this->orders->create_from_cart($identity, $this->auth->id(), $this->input->post(NULL, TRUE));
				if ($result['success'])
				{
					$this->audit->log('order_placed', 'orders', $result['order']->id, 'Customer placed an order.');
					$this->session->set_flashdata('success', 'Order placed successfully.');
					if ($result['order']->payment_method === 'razorpay')
					{
						redirect('payments/razorpay/pay/'.$result['order']->id);
					}
					redirect('checkout/success/'.$result['order']->id);
				}
				$this->session->set_flashdata('error', $result['message']);
				redirect('checkout');
			}
		}

		$this->render('checkout', array(
			'items' => $items,
			'totals' => $this->totals($items),
			'default_address' => $this->default_address(),
			'razorpay_available' => (bool) $this->settings->get_bool('razorpay_enabled', FALSE),
			'meta' => seo_meta(array('title' => seo_title('Checkout'), 'canonical' => site_url('checkout'), 'robots' => 'noindex,follow')),
		));
	}

	/**
	 * Thank-you page.
	 *
	 * @param  int|null $id
	 * @return void
	 */
	public function success($id = NULL)
	{
		$order = $this->db->from('orders')->where('id', (int) $id)->where('deleted_at IS NULL', NULL, FALSE)->get()->row();
		if ( ! $order) { show_404(); }
		if ($this->auth->check() && (int) $order->user_id !== (int) $this->auth->id()) { show_404(); }

		$this->render('order_success', array(
			'order' => $order,
			'meta' => seo_meta(array('title' => seo_title('Order '.$order->order_number), 'canonical' => site_url('checkout/success/'.$order->id), 'robots' => 'noindex,follow')),
		));
	}

	/**
	 * @param  array $items
	 * @return array
	 */
	protected function totals(array $items)
	{
		$subtotal = 0;
		foreach ($items as $item) { $subtotal += (float) $item->unit_price * (int) $item->quantity; }
		$shipping = $subtotal >= 999 ? 0 : 99;
		return array('subtotal' => $subtotal, 'shipping' => $shipping, 'total' => $subtotal + $shipping);
	}

	/**
	 * @return object|null
	 */
	protected function default_address()
	{
		if ( ! $this->auth->check()) { return NULL; }
		return $this->db->from('addresses')->where('user_id', (int) $this->auth->id())->where('deleted_at IS NULL', NULL, FALSE)->order_by('is_default', 'DESC')->order_by('id', 'DESC')->limit(1)->get()->row();
	}
}
