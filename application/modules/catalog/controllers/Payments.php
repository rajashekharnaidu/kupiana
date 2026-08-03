<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Customer payment endpoints.
 *
 * @package Kupiana\Modules\Catalog
 */
class Payments extends Store_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Payment_model', 'payments');
		$this->load->library('razorpay_gateway');
	}

	/**
	 * Razorpay payment page for an order.
	 *
	 * @param  int|null $order_id
	 * @return void
	 */
	public function pay($order_id = NULL)
	{
		$order = $this->order_for_customer((int) $order_id);
		if ( ! $order) { show_404(); }
		if (in_array($order->payment_status, array('paid', 'partially_refunded', 'refunded'), TRUE))
		{
			$this->session->set_flashdata('info', 'This order does not require another payment.');
			redirect('checkout/success/'.$order->id);
		}

		$payment = $this->payments->pending_for_order($order, 'razorpay');
		if (empty($payment->gateway_order_id))
		{
			$created = $this->razorpay_gateway->create_order($order, $payment);
			$this->payments->log('order.create', $payment->id, $order->id, array_get($created, 'request', array()), array_get($created, 'response', array_get($created, 'order', array())));
			if ( ! $created['success'])
			{
				$this->session->set_flashdata('error', 'Razorpay order could not be created: '.$created['message']);
				redirect('account/orders/'.$order->id);
			}
			$this->payments->attach_gateway_order($payment->id, $created['order']['id'], $created['order']);
			$payment = $this->payments->find($payment->id);
		}

		$this->render('payment_pay', array(
			'order' => $order,
			'payment' => $payment,
			'razorpay_enabled' => $this->razorpay_gateway->enabled(),
			'razorpay_key_id' => $this->razorpay_gateway->key_id(),
			'meta' => seo_meta(array('title' => seo_title('Pay '.$order->order_number), 'robots' => 'noindex,follow')),
		));
	}

	/**
	 * Razorpay Checkout return/callback.
	 *
	 * @return void
	 */
	public function verify()
	{
		$gateway_order_id = (string) $this->input->post('razorpay_order_id', TRUE);
		$gateway_payment_id = (string) $this->input->post('razorpay_payment_id', TRUE);
		$signature = (string) $this->input->post('razorpay_signature', TRUE);
		$payment = $this->payments->find_by_gateway_order($gateway_order_id);
		if ( ! $payment) { show_404(); }

		if ( ! $this->razorpay_gateway->verify_checkout_signature($gateway_order_id, $gateway_payment_id, $signature))
		{
			$this->payments->mark_failed($payment, 'Signature verification failed.', $this->input->post(NULL, TRUE));
			$this->session->set_flashdata('error', 'Payment verification failed.');
			redirect('payments/failed/'.$payment->order_id);
		}

		$this->payments->mark_captured($payment, array(
			'order_id' => $gateway_order_id,
			'payment_id' => $gateway_payment_id,
			'signature' => $signature,
			'method' => 'razorpay',
			'source' => 'checkout',
		));
		$this->session->set_flashdata('success', 'Payment captured successfully.');
		redirect('checkout/success/'.$payment->order_id);
	}

	/**
	 * Local-only offline capture for development without Razorpay keys.
	 *
	 * @param  int|null $payment_id
	 * @return void
	 */
	public function simulate($payment_id = NULL)
	{
		$payment = $this->payments->find((int) $payment_id);
		if ( ! $payment) { show_404(); }
		$order = $this->order_for_customer($payment->order_id);
		if ( ! $order) { show_404(); }
		if ($this->razorpay_gateway->enabled()) { show_404(); }

		$this->payments->mark_captured($payment, array(
			'order_id' => $payment->gateway_order_id,
			'payment_id' => 'pay_offline_'.strtolower(substr(generate_token(8), 0, 12)),
			'signature' => 'offline',
			'method' => 'offline-simulator',
			'source' => 'local',
		));
		$this->session->set_flashdata('success', 'Offline payment simulation captured.');
		redirect('checkout/success/'.$payment->order_id);
	}

	/**
	 * Payment failed page.
	 *
	 * @param  int|null $order_id
	 * @return void
	 */
	public function failed($order_id = NULL)
	{
		$order = $this->order_for_customer((int) $order_id);
		if ( ! $order) { show_404(); }
		$this->render('payment_failed', array('order' => $order, 'meta' => seo_meta(array('title' => seo_title('Payment Failed'), 'robots' => 'noindex,follow'))));
	}

	/**
	 * Razorpay webhook receiver.
	 *
	 * @return void
	 */
	public function webhook()
	{
		$body = file_get_contents('php://input');
		$signature = isset($_SERVER['HTTP_X_RAZORPAY_SIGNATURE']) ? $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] : '';
		$payload = json_decode((string) $body, TRUE);
		$this->payments->log('webhook.received', NULL, NULL, array('headers' => array('x-razorpay-signature' => $signature)), is_array($payload) ? $payload : array('raw' => $body));

		if ( ! $this->razorpay_gateway->verify_webhook_signature($body, $signature))
		{
			$this->output->set_status_header(400)->set_output('invalid signature');
			return;
		}

		$event = array_get($payload, 'event', '');
		$entity = array_get(array_get(array_get($payload, 'payload', array()), 'payment', array()), 'entity', array());
		$gateway_order_id = array_get($entity, 'order_id');
		$payment = $gateway_order_id ? $this->payments->find_by_gateway_order($gateway_order_id) : NULL;
		if ($payment && $event === 'payment.captured')
		{
			$this->payments->mark_captured($payment, array('order_id' => $gateway_order_id, 'payment_id' => array_get($entity, 'id'), 'method' => array_get($entity, 'method'), 'source' => 'webhook', 'payload' => $entity));
		}
		elseif ($payment && $event === 'payment.failed')
		{
			$this->payments->mark_failed($payment, array_get($entity, 'error_description', 'Payment failed.'), $entity);
		}

		$this->output->set_output('ok');
	}

	/**
	 * @param  int $order_id
	 * @return object|null
	 */
	protected function order_for_customer($order_id)
	{
		$this->db->from('orders')->where('id', (int) $order_id)->where('deleted_at IS NULL', NULL, FALSE);
		if ($this->auth->check())
		{
			$this->db->where('user_id', (int) $this->auth->id());
		}
		return $this->db->get()->row();
	}
}
