<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Checkout flow.
 *
 * Supports COD and online payment methods (Cashfree).
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
		log_message('info', '========== CHECKOUT PAGE START ==========');

		$this->load->model('Order_model', 'orders');
		$this->load->model('Store_model', 'store');
		$this->load->library('cashfree_gateway');

		$identity = $this->cart_identity();
		log_message('info', 'Cart Identity: '.$identity);

		$items = $this->store->cart_items($identity);
		log_message('info', 'Cart Items Count: '.count($items));

		if (empty($items))
		{
			log_message('info', '[!] Cart is empty - redirecting to cart page');
			$this->session->set_flashdata('error', 'Your cart is empty.');
			redirect('cart');
		}

		log_message('info', '[✓] Cart has items');
		log_message('info', 'Cashfree Gateway Available: '.($this->cashfree_gateway->enabled() ? 'YES' : 'NO'));

		if ($this->input->method(TRUE) === 'POST')
		{
			log_message('info', '========== CHECKOUT FORM SUBMISSION ==========');
			log_message('info', 'POST Data: '.json_encode($this->input->post(NULL, TRUE)));

			$this->form_validation->set_rules('first_name', 'First Name', 'required|max_length[100]');
			$this->form_validation->set_rules('email', 'Email', 'required|valid_email|max_length[191]');
			$this->form_validation->set_rules('phone', 'Phone', 'required|max_length[20]');
			$this->form_validation->set_rules('address_line1', 'Address', 'required|max_length[255]');
			$this->form_validation->set_rules('city', 'City', 'required|max_length[100]');
			$this->form_validation->set_rules('state', 'State', 'required|max_length[100]');
			$this->form_validation->set_rules('postal_code', 'PIN Code', 'required|max_length[20]');
			$this->form_validation->set_rules('payment_method', 'Payment Method', 'required|in_list[cod,cashfree]');

			if ($this->form_validation->run() === TRUE)
			{
				log_message('info', '[✓] Form validation passed');
				log_message('info', 'Payment Method: '.$this->input->post('payment_method'));

				$result = $this->orders->create_from_cart($identity, $this->auth->id(), $this->input->post(NULL, TRUE));

				if ($result['success'])
				{
					log_message('info', '[✓] Order created successfully');
					log_message('info', 'Order ID: '.$result['order']->id);
					log_message('info', 'Order Number: '.$result['order']->order_number);
					log_message('info', 'Payment Method: '.$result['order']->payment_method);
					log_message('info', 'Total Amount: '.$result['order']->total_amount);

					$this->audit->log('order_placed', 'orders', $result['order']->id, 'Customer placed an order.');

					$this->session->set_flashdata('success', 'Order placed successfully.');

					if ($result['order']->payment_method === 'cashfree')
					{
						log_message('info', 'Cashfree payment selected - redirecting to payment page');
						log_message('info', '========== CHECKOUT COMPLETED - CASHFREE PAYMENT ==========');
						redirect('payments/cashfree/pay/'.$result['order']->public_token);
					}

					log_message('info', 'COD payment selected - redirecting to success page');
					log_message('info', '========== CHECKOUT COMPLETED - COD PAYMENT ==========');
					redirect('checkout/success/'.$result['order']->public_token);
				}

				log_message('error', '[✗] Order creation failed: '.$result['message']);
				$this->session->set_flashdata('error', $result['message']);
				redirect('checkout');
			}

			log_message('error', '[✗] Form validation failed');
			log_message('error', 'Validation Errors: '.strip_tags(validation_errors()));
		}

		log_message('info', '========== CHECKOUT PAGE RENDERED ==========');

		$this->render('checkout', array(
			'items' => $items,
			'totals' => $this->totals($items),
			'default_address' => $this->default_address(),
			'saved_addresses' => $this->saved_addresses(),
			'cashfree_available' => $this->cashfree_gateway->enabled(),
			'meta' => seo_meta(array('title' => seo_title('Checkout'), 'canonical' => site_url('checkout'), 'robots' => 'noindex,follow')),
		));
	}

	/**
	 * Thank-you page.
	 *
	 * @param  string|null $token Order's opaque public_token
	 * @return void
	 */
	public function success($token = NULL)
	{
		$order = $this->db->from('orders')->where('public_token', (string) $token)->where('deleted_at IS NULL', NULL, FALSE)->get()->row();
		if ( ! $order) { show_404(); }
		if ($this->auth->check() && $order->user_id !== NULL && (int) $order->user_id !== (int) $this->auth->id()) { show_404(); }

		$this->render('order_success', array(
			'order' => $order,
			'meta' => seo_meta(array('title' => seo_title('Order '.$order->order_number), 'canonical' => site_url('checkout/success/'.$order->public_token), 'robots' => 'noindex,follow')),
		));
	}

	/**
	 * @param  array $items
	 * @return array
	 */
	protected function totals(array $items)
	{
		return $this->cart_totals($items);
	}

	/**
	 * @return object|null
	 */
	protected function default_address()
	{
		if ( ! $this->auth->check()) { return NULL; }
		return $this->db->from('addresses')->where('user_id', (int) $this->auth->id())->where('deleted_at IS NULL', NULL, FALSE)->order_by('is_default', 'DESC')->order_by('id', 'DESC')->limit(1)->get()->row();
	}

	/**
	 * @return array
	 */
	protected function saved_addresses()
	{
		if ( ! $this->auth->check()) { return array(); }
		return $this->db->from('addresses')->where('user_id', (int) $this->auth->id())->where('deleted_at IS NULL', NULL, FALSE)->order_by('is_default', 'DESC')->order_by('id', 'DESC')->get()->result();
	}
}
