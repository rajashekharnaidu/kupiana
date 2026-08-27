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
		$this->load->model('Order_model', 'orders');
		$this->load->library('cashfree_gateway');
	}


	/**
	 * Cashfree payment page for an order.
	 *
	 * @param  string|null $token Order's opaque public_token
	 * @return void
	 */
	public function cashfree_pay($token = NULL)
	{
		log_message('info', '========== CASHFREE PAY PAGE LOAD START ==========');

		$order = $this->order_for_customer($token);
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
			redirect('checkout/success/'.$order->public_token);
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

			$order_response = $created['order'];
			// Use the merchant order_id (not cf_order_id) - Cashfree's return_url
			// placeholder and webhook payloads both key off this value.
			$gateway_order_id = isset($order_response['order_id']) ? $order_response['order_id'] :
								(isset($order_response['cf_order_id']) ? $order_response['cf_order_id'] : NULL);

			log_message('info', 'Cashfree Order ID: '.$gateway_order_id);

			$this->payments->attach_gateway_order($payment->id, $gateway_order_id, $order_response);
			log_message('info', '[✓] Gateway Order ID attached to payment record');

			$payment = $this->payments->find($payment->id);
		}
		else
		{
			log_message('info', '[✓] Gateway Order ID already set - reusing existing order');
			log_message('info', 'Existing Cashfree Order ID: '.$payment->gateway_order_id);
		}

		log_message('info', 'Retrieving payment_session_id from gateway response...');
		$payment_session_id = $this->cashfree_gateway->get_payment_session_id($payment);

		if ($payment_session_id)
		{
			log_message('info', '[✓] payment_session_id retrieved');
		}
		else
		{
			log_message('error', '[✗] No payment_session_id found');
		}

		log_message('info', 'Rendering payment page...');
		log_message('info', '========== CASHFREE PAY PAGE SUCCESS ==========');

		$this->render('payment_cashfree', array(
			'order' => $order,
			'payment' => $payment,
			'payment_session_id' => $payment_session_id,
			'is_sandbox' => $this->cashfree_gateway->is_sandbox(),
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

		// The return_url redirect is unauthenticated (any query string can be
		// forged by the browser), so it is only used to look up the payment;
		// the actual order/payment status is fetched from Cashfree directly.
		$order_id = (string) $this->input->get('order_id', TRUE);
		log_message('info', 'Cashfree Order ID: '.$order_id);

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

		$order = $this->orders->find($payment->order_id);
		if ( ! $order)
		{
			log_message('error', '[✗] Order record not found for payment: '.$payment->order_id);
			log_message('info', '========== CASHFREE PAYMENT VERIFICATION FAILED ==========');
			show_404();
		}

		if (in_array($payment->status, array('captured'), TRUE))
		{
			log_message('info', '[!] Payment already captured (likely via webhook) - redirecting to success page');
			log_message('info', '========== CASHFREE PAYMENT VERIFICATION SUCCESS ==========');
			$this->session->set_flashdata('success', 'Payment captured successfully.');
			redirect('checkout/success/'.$order->public_token);
		}

		log_message('info', 'Fetching authoritative order status from Cashfree...');
		$order_response = $this->cashfree_gateway->get_order($order_id);
		$order_status = $order_response ? array_get($order_response, 'order_status') : NULL;
		log_message('info', 'Order Status: '.$order_status);

		if ($order_status === 'PAID')
		{
			log_message('info', '[✓] Order is PAID - marking payment as captured');

			$order_payments = $this->cashfree_gateway->get_order_payments($order_id);
			$successful_payment = array();
			foreach ($order_payments as $order_payment)
			{
				if (array_get($order_payment, 'payment_status') === 'SUCCESS')
				{
					$successful_payment = $order_payment;
					break;
				}
			}

			$this->payments->mark_captured($payment, array(
				'order_id' => $order_id,
				'payment_id' => array_get($successful_payment, 'cf_payment_id'),
				'method' => array_get($successful_payment, 'payment_group', 'cashfree'),
				'source' => 'redirect',
				'order' => $order_response,
				'payment' => $successful_payment,
			));

			log_message('info', '[✓] Payment marked as captured');
			log_message('info', 'Redirecting to success page...');
			log_message('info', '========== CASHFREE PAYMENT VERIFICATION SUCCESS ==========');

			$this->session->set_flashdata('success', 'Payment captured successfully.');
			redirect('checkout/success/'.$order->public_token);
		}

		log_message('error', '[✗] Order not paid (status: '.$order_status.')');
		log_message('info', 'Marking payment as failed');
		$this->payments->mark_failed($payment, 'Cashfree order status: '.($order_status ?: 'unknown'), (array) $order_response);
		log_message('info', '========== CASHFREE PAYMENT VERIFICATION FAILED ==========');
		$this->session->set_flashdata('error', 'Payment could not be verified.');
		redirect('payments/failed/'.$order->public_token);
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

		$signature = isset($_SERVER['HTTP_X_WEBHOOK_SIGNATURE']) ? $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] : '';
		$timestamp = isset($_SERVER['HTTP_X_WEBHOOK_TIMESTAMP']) ? $_SERVER['HTTP_X_WEBHOOK_TIMESTAMP'] : '';
		log_message('info', 'Signature Header: '.$signature);
		log_message('info', 'Timestamp Header: '.$timestamp);
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

		$this->payments->log('webhook.received', NULL, NULL, array('headers' => array('x-webhook-signature' => $signature, 'x-webhook-timestamp' => $timestamp)), is_array($payload) ? $payload : array('raw' => $body), 'cashfree');

		$order_id = array_get($payload, 'data.order.order_id', '');
		log_message('info', 'Order ID from payload: '.$order_id);
		log_message('info', 'Event Type: '.array_get($payload, 'type', 'UNKNOWN'));

		log_message('info', 'Verifying webhook signature...');
		if ( ! $this->cashfree_gateway->verify_webhook_signature($signature, $body, $timestamp))
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
	 * @param  string|null $token Order's opaque public_token
	 * @return void
	 */
	public function failed($token = NULL)
	{
		$order = $this->order_for_customer($token);
		if ( ! $order) { show_404(); }
		$this->render('payment_failed', array('order' => $order, 'meta' => seo_meta(array('title' => seo_title('Payment Failed'), 'robots' => 'noindex,follow'))));
	}


	/**
	 * Look up an order by its opaque public_token (never by sequential id, to
	 * prevent enumeration). If the visitor is logged in, the order must also
	 * belong to them; guest orders remain reachable to anyone holding the token.
	 *
	 * @param  string $token
	 * @return object|null
	 */
	protected function order_for_customer($token)
	{
		if (empty($token)) { return NULL; }

		$this->db->from('orders')->where('public_token', (string) $token)->where('deleted_at IS NULL', NULL, FALSE);
		if ($this->auth->check())
		{
			$this->db->where('(user_id IS NULL OR user_id = '.(int) $this->auth->id().')', NULL, FALSE);
		}
		return $this->db->get()->row();
	}
}
