<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Cashfree payment gateway integration.
 *
 * @package Kupiana\Libraries
 */
class Cashfree_gateway
{
	protected $ci;
	protected $app_id;
	protected $secret_key;
	protected $base_url = 'https://api.cashfree.com/pg';
	protected $test_base_url = 'https://sandbox.cashfree.com/pg';

	public function __construct()
	{
		$this->ci = &get_instance();
		$this->app_id = kupiana_env('CASHFREE_APP_ID');
		$this->secret_key = kupiana_env('CASHFREE_SECRET_KEY');
	}

	/**
	 * Check if Cashfree is enabled.
	 *
	 * @return bool
	 */
	public function enabled()
	{
		return ! empty($this->app_id) && ! empty($this->secret_key);
	}

	/**
	 * Get app ID.
	 *
	 * @return string
	 */
	public function app_id()
	{
		return $this->app_id;
	}

	/**
	 * Create an order in Cashfree.
	 *
	 * @param  object $order
	 * @param  object $payment
	 * @return array
	 */
	public function create_order($order, $payment)
	{
		if ( ! $this->enabled())
		{
			return array('success' => FALSE, 'message' => 'Cashfree is not configured.');
		}

		$request_body = array(
			'order_id' => 'order_'.$order->id.'_'.time(),
			'order_amount' => (float) $order->total_amount,
			'order_currency' => 'INR',
			'order_note' => 'Order for '.$order->order_number,
			'customer_details' => array(
				'customer_id' => 'customer_'.$order->user_id,
				'customer_name' => $order->first_name.($order->last_name ? ' '.$order->last_name : ''),
				'customer_email' => $order->email,
				'customer_phone' => $order->phone,
			),
			'order_meta' => array(
				'notify_url' => site_url('payments/cashfree/webhook'),
				'return_url' => site_url('payments/cashfree/verify'),
			),
		);

		try
		{
			$response = $this->make_request('POST', '/orders', $request_body);
			if ($response && isset($response['order_id']))
			{
				return array(
					'success' => TRUE,
					'order' => $response,
					'request' => $request_body,
					'response' => $response,
				);
			}
			return array(
				'success' => FALSE,
				'message' => isset($response['message']) ? $response['message'] : 'Failed to create order.',
				'request' => $request_body,
				'response' => $response,
			);
		}
		catch (Exception $e)
		{
			return array(
				'success' => FALSE,
				'message' => $e->getMessage(),
				'request' => $request_body,
			);
		}
	}

	/**
	 * Get the payment link for an order.
	 *
	 * @param  object $payment Cashfree order response
	 * @return string|null
	 */
	public function get_payment_link($payment)
	{
		if (is_object($payment) && isset($payment->gateway_response))
		{
			$response = json_decode($payment->gateway_response, TRUE);
			if (isset($response['payments_links']) && is_array($response['payments_links']))
			{
				foreach ($response['payments_links'] as $link)
				{
					if (isset($link['url']))
					{
						return $link['url'];
					}
				}
			}
		}
		return NULL;
	}

	/**
	 * Verify the payment signature from Cashfree webhook.
	 *
	 * @param  string $order_id
	 * @param  string $signature
	 * @param  array $data
	 * @return bool
	 */
	public function verify_webhook_signature($order_id, $signature, array $data = array())
	{
		if (empty($this->secret_key))
		{
			return FALSE;
		}

		$msg = $order_id.$this->secret_key;
		$computed_signature = hash('sha256', $msg);
		return hash_equals($computed_signature, $signature);
	}

	/**
	 * Make an HTTP request to Cashfree API.
	 *
	 * @param  string $method
	 * @param  string $endpoint
	 * @param  array $body
	 * @return array|null
	 */
	protected function make_request($method, $endpoint, array $body = array())
	{
		if ( ! function_exists('curl_init'))
		{
			log_message('error', 'cURL is not installed on this server.');
			return NULL;
		}

		$url = $this->base_url.$endpoint;
		$headers = array(
			'Content-Type: application/json',
			'X-Client-Id: '.$this->app_id,
			'X-Client-Secret: '.$this->secret_key,
			'x-api-version: 2023-08-01',
		);

		$curl = curl_init();

		curl_setopt_array($curl, array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_FOLLOWLOCATION => TRUE,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => $method,
			CURLOPT_POSTFIELDS => json_encode($body),
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_SSL_VERIFYPEER => FALSE,
		));

		$response = curl_exec($curl);
		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$error = curl_error($curl);
		curl_close($curl);

		if ($error)
		{
			log_message('error', 'Cashfree cURL error: '.$error);
			return NULL;
		}

		$decoded = json_decode($response, TRUE);
		log_message('info', 'Cashfree API response ('.($http_code ?? 'unknown').'): '.substr($response, 0, 500));

		return is_array($decoded) ? $decoded : NULL;
	}
}
