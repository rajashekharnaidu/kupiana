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
		$this->load->library('cashfree_gateway');
	}


	/**
	 * Cashfree payment page for an order.
	 *
	 * @param  int|null $order_id
	 * @return void
	 */
	public function cashfree_pay($order_id = NULL)
	{
		$order = $this->order_for_customer((int) $order_id);
		if ( ! $order) { show_404(); }
		if (in_array($order->payment_status, array('paid', 'partially_refunded', 'refunded'), TRUE))
		{
			$this->session->set_flashdata('info', 'This order does not require another payment.');
			redirect('checkout/success/'.$order->id);
		}

		$payment = $this->payments->pending_for_order($order, 'cashfree');
		if (empty($payment->gateway_order_id))
		{
			$created = $this->cashfree_gateway->create_order($order, $payment);
			$this->payments->log('order.create', $payment->id, $order->id, array_get($created, 'request', array()), array_get($created, 'response', array_get($created, 'order', array())), 'cashfree');
			if ( ! $created['success'])
			{
				$this->session->set_flashdata('error', 'Cashfree order could not be created: '.$created['message']);
				redirect('account/orders/'.$order->id);
			}
			$this->payments->attach_gateway_order($payment->id, $created['order']['order_id'], json_encode($created['order']));
			$payment = $this->payments->find($payment->id);
		}

		$payment_link = $this->cashfree_gateway->get_payment_link($payment);
		$this->render('payment_cashfree', array(
			'order' => $order,
			'payment' => $payment,
			'payment_link' => $payment_link,
			'cashfree_enabled' => $this->cashfree_gateway->enabled(),
			'meta' => seo_meta(array('title' => seo_title('Pay '.$order->order_number), 'robots' => 'noindex,follow')),
		));
	}

	/**
	 * Cashfree payment verification callback.
	 *
	 * @return void
	 */
	public function cashfree_verify()
	{
		$order_id = (string) $this->input->get('order_id', TRUE);
		$payment = $this->payments->find_by_gateway_order($order_id);
		if ( ! $payment) { show_404(); }

		$signature = (string) $this->input->get('cf_signature', TRUE);
		if ( ! $this->cashfree_gateway->verify_webhook_signature($order_id, $signature))
		{
			$this->payments->mark_failed($payment, 'Signature verification failed.', $this->input->get(NULL, TRUE));
			$this->session->set_flashdata('error', 'Payment verification failed.');
			redirect('payments/failed/'.$payment->order_id);
		}

		$this->payments->mark_captured($payment, array(
			'order_id' => $order_id,
			'payment_id' => (string) $this->input->get('cf_payment_id', TRUE),
			'signature' => $signature,
			'method' => 'cashfree',
			'source' => 'redirect',
		));
		$this->session->set_flashdata('success', 'Payment captured successfully.');
		redirect('checkout/success/'.$payment->order_id);
	}

	/**
	 * Cashfree webhook receiver.
	 *
	 * @return void
	 */
	public function cashfree_webhook()
	{
		$body = file_get_contents('php://input');
		$signature = isset($_SERVER['HTTP_X_CF_SIGNATURE']) ? $_SERVER['HTTP_X_CF_SIGNATURE'] : '';
		$payload = json_decode((string) $body, TRUE);
		$this->payments->log('webhook.received', NULL, NULL, array('headers' => array('x-cf-signature' => $signature)), is_array($payload) ? $payload : array('raw' => $body), 'cashfree');

		if ( ! $this->cashfree_gateway->verify_webhook_signature(array_get($payload, 'data.order_id', ''), $signature, $payload))
		{
			$this->output->set_status_header(400)->set_output('invalid signature');
			return;
		}

		$order_id = array_get($payload, 'data.order_id');
		$payment = $order_id ? $this->payments->find_by_gateway_order($order_id) : NULL;
		$event = array_get($payload, 'type', '');

		if ($payment && in_array($event, array('PAYMENT_SUCCESS_WEBHOOK', 'PAYMENT_AUTHORIZED_WEBHOOK'), TRUE))
		{
			$this->payments->mark_captured($payment, array(
				'order_id' => $order_id,
				'payment_id' => array_get($payload, 'data.payment.cf_payment_id'),
				'method' => array_get($payload, 'data.payment.payment_method'),
				'source' => 'webhook',
				'payload' => array_get($payload, 'data', array()),
			));
		}
		elseif ($payment && in_array($event, array('PAYMENT_FAILED_WEBHOOK', 'PAYMENT_DECLINED_WEBHOOK'), TRUE))
		{
			$this->payments->mark_failed($payment, array_get($payload, 'data.payment.error_message', 'Payment failed.'), array_get($payload, 'data', array()));
		}

		$this->output->set_output('ok');
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
