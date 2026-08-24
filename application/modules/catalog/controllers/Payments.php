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
		log_message('info', '========== CASHFREE PAY PAGE LOAD START ==========');
		log_message('info', 'Order ID: '.$order_id);

		$order = $this->order_for_customer((int) $order_id);
		if ( ! $order)
		{
			log_message('error', '[✗] Order not found for customer');
			log_message('info', '========== CASHFREE PAY PAGE FAILED ==========');
			show_404();
		}

		log_message('info', '[✓] Order found - Order#'.$order->order_number);
		log_message('info', 'Order Status: '.$order->order_status);
		log_message('info', 'Payment Status: '.$order->payment_status);
		log_message('info', 'Payment Method: '.$order->payment_method);

		if (in_array($order->payment_status, array('paid', 'partially_refunded', 'refunded'), TRUE))
		{
			log_message('info', '[!] Order already paid - redirecting to success page');
			$this->session->set_flashdata('info', 'This order does not require another payment.');
			redirect('checkout/success/'.$order->id);
		}

		log_message('info', '[✓] Order payment pending - proceeding with payment');

		$payment = $this->payments->pending_for_order($order, 'cashfree');
		log_message('info', 'Payment ID: '.$payment->id);
		log_message('info', 'Payment Status: '.$payment->status);
		log_message('info', 'Gateway: '.$payment->gateway);
		log_message('info', 'Gateway Order ID: '.($payment->gateway_order_id ?: 'NOT SET'));

		if (empty($payment->gateway_order_id))
		{
			log_message('info', '[!] Gateway Order ID not set - creating order in Cashfree');
			$created = $this->cashfree_gateway->create_order($order, $payment);
			log_message('info', 'Create Order Result: '.json_encode($created));

			$this->payments->log('order.create', $payment->id, $order->id, array_get($created, 'request', array()), array_get($created, 'response', array_get($created, 'order', array())), 'cashfree');

			if ( ! $created['success'])
			{
				log_message('error', '[✗] Failed to create order in Cashfree: '.$created['message']);
				log_message('info', '========== CASHFREE PAY PAGE FAILED ==========');
				$this->session->set_flashdata('error', 'Cashfree order could not be created: '.$created['message']);
				redirect('account/orders/'.$order->id);
			}

			log_message('info', '[✓] Order created successfully in Cashfree');
			log_message('info', 'Cashfree Order ID: '.$created['order']['order_id']);

			$this->payments->attach_gateway_order($payment->id, $created['order']['order_id'], $created['order']);
			log_message('info', '[✓] Gateway Order ID attached to payment record');

			$payment = $this->payments->find($payment->id);
		}
		else
		{
			log_message('info', '[✓] Gateway Order ID already set - reusing existing order');
			log_message('info', 'Existing Cashfree Order ID: '.$payment->gateway_order_id);
		}

		log_message('info', 'Retrieving payment link from gateway response...');
		$payment_link = $this->cashfree_gateway->get_payment_link($payment);

		if ($payment_link)
		{
			log_message('info', '[✓] Payment link retrieved: '.$payment_link);
		}
		else
		{
			log_message('error', '[✗] No payment link found');
		}

		log_message('info', 'Rendering payment page...');
		log_message('info', '========== CASHFREE PAY PAGE SUCCESS ==========');

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
		log_message('info', '========== CASHFREE PAYMENT VERIFICATION START ==========');
		log_message('info', 'Return URL callback received from Cashfree');

		$order_id = (string) $this->input->get('order_id', TRUE);
		$payment_id = (string) $this->input->get('cf_payment_id', TRUE);
		$signature = (string) $this->input->get('cf_signature', TRUE);

		log_message('info', 'Cashfree Order ID: '.$order_id);
		log_message('info', 'Cashfree Payment ID: '.$payment_id);
		log_message('info', 'Signature: '.$signature);
		log_message('info', 'Query Parameters: '.json_encode($this->input->get(NULL, TRUE)));

		$payment = $this->payments->find_by_gateway_order($order_id);
		if ( ! $payment)
		{
			log_message('error', '[✗] Payment record not found for gateway order: '.$order_id);
			log_message('info', '========== CASHFREE PAYMENT VERIFICATION FAILED ==========');
			show_404();
		}

		log_message('info', '[✓] Payment record found');
		log_message('info', 'Payment ID: '.$payment->id);
		log_message('info', 'Order ID: '.$payment->order_id);
		log_message('info', 'Payment Status: '.$payment->status);

		log_message('info', 'Verifying webhook signature...');
		if ( ! $this->cashfree_gateway->verify_webhook_signature($order_id, $signature))
		{
			log_message('error', '[✗] Signature verification failed');
			log_message('info', 'Marking payment as failed due to signature mismatch');
			$this->payments->mark_failed($payment, 'Signature verification failed.', $this->input->get(NULL, TRUE));
			log_message('info', '========== CASHFREE PAYMENT VERIFICATION FAILED ==========');
			$this->session->set_flashdata('error', 'Payment verification failed.');
			redirect('payments/failed/'.$payment->order_id);
		}

		log_message('info', '[✓] Signature verification passed');
		log_message('info', 'Marking payment as captured...');

		$this->payments->mark_captured($payment, array(
			'order_id' => $order_id,
			'payment_id' => $payment_id,
			'signature' => $signature,
			'method' => 'cashfree',
			'source' => 'redirect',
		));

		log_message('info', '[✓] Payment marked as captured');
		log_message('info', 'Redirecting to success page...');
		log_message('info', '========== CASHFREE PAYMENT VERIFICATION SUCCESS ==========');

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
		log_message('info', '========== CASHFREE WEBHOOK RECEIVED START ==========');

		$body = file_get_contents('php://input');
		log_message('info', 'Raw Request Body: '.$body);

		$signature = isset($_SERVER['HTTP_X_CF_SIGNATURE']) ? $_SERVER['HTTP_X_CF_SIGNATURE'] : '';
		log_message('info', 'Signature Header: '.$signature);
		log_message('info', 'All Headers: '.json_encode(getallheaders()));

		$payload = json_decode((string) $body, TRUE);
		if (json_last_error() !== JSON_ERROR_NONE)
		{
			log_message('error', '[✗] Failed to decode JSON: '.json_last_error_msg());
			$this->output->set_status_header(400)->set_output('invalid json');
			log_message('info', '========== CASHFREE WEBHOOK FAILED ==========');
			return;
		}

		log_message('info', '[✓] JSON decoded successfully');
		log_message('info', 'Payload: '.json_encode($payload));

		$this->payments->log('webhook.received', NULL, NULL, array('headers' => array('x-cf-signature' => $signature)), is_array($payload) ? $payload : array('raw' => $body), 'cashfree');

		$order_id = array_get($payload, 'data.order_id', '');
		log_message('info', 'Order ID from payload: '.$order_id);
		log_message('info', 'Event Type: '.array_get($payload, 'type', 'UNKNOWN'));

		log_message('info', 'Verifying webhook signature...');
		if ( ! $this->cashfree_gateway->verify_webhook_signature($order_id, $signature, $payload))
		{
			log_message('error', '[✗] Webhook signature verification failed');
			$this->output->set_status_header(400)->set_output('invalid signature');
			log_message('info', '========== CASHFREE WEBHOOK FAILED ==========');
			return;
		}

		log_message('info', '[✓] Webhook signature verification passed');

		$payment = $order_id ? $this->payments->find_by_gateway_order($order_id) : NULL;
		$event = array_get($payload, 'type', '');

		if ( ! $payment)
		{
			log_message('error', '[✗] No payment record found for order: '.$order_id);
			log_message('info', 'Acknowledging webhook anyway to prevent retries');
			$this->output->set_output('ok');
			log_message('info', '========== CASHFREE WEBHOOK PROCESSED (NO PAYMENT) ==========');
			return;
		}

		log_message('info', '[✓] Payment record found');
		log_message('info', 'Payment ID: '.$payment->id);
		log_message('info', 'Current Status: '.$payment->status);
		log_message('info', 'Event: '.$event);

		if ($payment && in_array($event, array('PAYMENT_SUCCESS_WEBHOOK', 'PAYMENT_AUTHORIZED_WEBHOOK'), TRUE))
		{
			log_message('info', '[✓] Payment success event received');
			log_message('info', 'Cashfree Payment ID: '.array_get($payload, 'data.payment.cf_payment_id'));
			log_message('info', 'Payment Method: '.array_get($payload, 'data.payment.payment_method'));

			$this->payments->mark_captured($payment, array(
				'order_id' => $order_id,
				'payment_id' => array_get($payload, 'data.payment.cf_payment_id'),
				'method' => array_get($payload, 'data.payment.payment_method'),
				'source' => 'webhook',
				'payload' => array_get($payload, 'data', array()),
			));

			log_message('info', '[✓] Payment marked as captured');
		}
		elseif ($payment && in_array($event, array('PAYMENT_FAILED_WEBHOOK', 'PAYMENT_DECLINED_WEBHOOK'), TRUE))
		{
			log_message('info', '[!] Payment failure event received');
			log_message('error', 'Error: '.array_get($payload, 'data.payment.error_message', 'Payment failed'));

			$this->payments->mark_failed($payment, array_get($payload, 'data.payment.error_message', 'Payment failed.'), array_get($payload, 'data', array()));

			log_message('info', '[✓] Payment marked as failed');
		}
		else
		{
			log_message('info', '[!] Webhook received but no action taken (event: '.$event.')');
			log_message('info', 'Possible events: PAYMENT_SUCCESS_WEBHOOK, PAYMENT_AUTHORIZED_WEBHOOK, PAYMENT_FAILED_WEBHOOK, PAYMENT_DECLINED_WEBHOOK');
		}

		log_message('info', 'Acknowledging webhook receipt...');
		$this->output->set_output('ok');
		log_message('info', '========== CASHFREE WEBHOOK PROCESSED SUCCESSFULLY ==========');
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
