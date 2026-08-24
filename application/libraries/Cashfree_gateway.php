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
	// protected $base_url = 'https://api.cashfree.com/pg';
	protected $base_url = 'https://sandbox.cashfree.com/pg';
	protected $sandbox_url = 'https://sandbox.cashfree.com/pg';
	protected $is_sandbox = FALSE;

	public function __construct()
	{
		$this->ci = &get_instance();
		$this->app_id = kupiana_env('CASHFREE_APP_ID');
		$this->secret_key = kupiana_env('CASHFREE_SECRET_KEY');

		$this->is_sandbox = kupiana_env('CASHFREE_SANDBOX', FALSE);
		if (ENVIRONMENT !== 'production')
		{
			$this->is_sandbox = TRUE;
		}

		log_message('info', '========== CASHFREE PAYMENT GATEWAY INITIALIZED ==========');
		log_message('info', 'Environment: '.ENVIRONMENT);
		log_message('info', 'Cashfree Mode: '.($this->is_sandbox ? 'SANDBOX' : 'PRODUCTION'));
		log_message('info', 'API URL: '.($this->is_sandbox ? $this->sandbox_url : $this->base_url));

		if ($this->app_id && $this->secret_key)
		{
			log_message('info', '[✓] Cashfree credentials loaded (APP_ID: '.substr($this->app_id, 0, 10).'..., SECRET_KEY: '.substr($this->secret_key, 0, 10).'...)');
		}
		else
		{
			log_message('error', '[✗] Cashfree credentials NOT found in .env file');
		}
		log_message('info', '=========================================================');
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
		log_message('info', '---------- CREATE CASHFREE ORDER START ----------');
		log_message('info', 'Order ID: '.$order->id);
		log_message('info', 'Payment ID: '.$payment->id);
		log_message('info', 'Order Number: '.$order->order_number);
		log_message('info', 'Total Amount: '.$order->total_amount.' '.$order->currency ?? 'INR');
		log_message('info', 'Customer: '.$order->first_name.' ('.$order->email.')');

		if ( ! $this->enabled())
		{
			log_message('error', '[✗] Cashfree is NOT configured - credentials missing');
			log_message('info', '---------- CREATE CASHFREE ORDER FAILED ----------');
			return array('success' => FALSE, 'message' => 'Cashfree is not configured.');
		}

		log_message('info', '[✓] Cashfree credentials are present');

		// Build request body matching working Postman request
		$request_body = array(
			'order_amount' => (float) $order->total_amount,
			'order_currency' => 'INR',
			'customer_details' => array(
				'customer_id' => 'customer_'.$order->user_id,
				'customer_name' => $order->first_name.($order->last_name ? ' '.$order->last_name : ''),
				'customer_email' => $order->email,
				'customer_phone' => $order->phone,
			),
		);

		log_message('info', 'Request Body: '.json_encode($request_body));

		try
		{
			log_message('info', 'Calling Cashfree API: POST /orders');
			$response = $this->make_request('POST', '/orders', $request_body);

			if ($response && (isset($response['cf_order_id']) || isset($response['order_id'])))
			{
				log_message('info', '[✓] Order created successfully');
				$order_id_field = isset($response['cf_order_id']) ? 'cf_order_id' : 'order_id';
				log_message('info', 'Cashfree Order ID: '.$response[$order_id_field]);
				if (isset($response['order_status']))
				{
					log_message('info', 'Order Status: '.$response['order_status']);
				}
				if (isset($response['payment_links']) && count($response['payment_links']) > 0)
				{
					log_message('info', 'Payment Link: '.$response['payment_links'][0]['url']);
				}
				if (isset($response['payments_links']) && count($response['payments_links']) > 0)
				{
					log_message('info', 'Payment Link: '.$response['payments_links'][0]['url']);
				}
				log_message('info', 'Full Response: '.json_encode($response));
				log_message('info', '---------- CREATE CASHFREE ORDER SUCCESS ----------');
				return array(
					'success' => TRUE,
					'order' => $response,
					'request' => $request_body,
					'response' => $response,
				);
			}

			if ($response && isset($response['message']) && strpos($response['message'], 'authentication') !== FALSE)
			{
				log_message('error', '[✗] Authentication Failed - Invalid or expired credentials');
				log_message('error', 'API Response: '.json_encode($response));
				log_message('error', 'Please verify CASHFREE_APP_ID and CASHFREE_SECRET_KEY in .env file');
				log_message('info', '---------- CREATE CASHFREE ORDER FAILED ----------');
				return array(
					'success' => FALSE,
					'message' => 'Payment gateway credentials are invalid. Please check your Cashfree configuration.',
					'request' => $request_body,
					'response' => $response,
				);
			}

			log_message('error', '[✗] Order creation failed');
			log_message('error', 'API Response: '.json_encode($response));
			log_message('info', '---------- CREATE CASHFREE ORDER FAILED ----------');
			return array(
				'success' => FALSE,
				'message' => isset($response['message']) ? $response['message'] : 'Failed to create order.',
				'request' => $request_body,
				'response' => $response,
			);
		}
		catch (Exception $e)
		{
			log_message('error', '[✗] Exception caught: '.$e->getMessage());
			log_message('error', 'Stack Trace: '.$e->getTraceAsString());
			log_message('info', '---------- CREATE CASHFREE ORDER FAILED ----------');
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
		log_message('info', '---------- GET PAYMENT LINK START ----------');
		log_message('info', 'Payment ID: '.$payment->id);

		if (is_object($payment) && isset($payment->gateway_response))
		{
			log_message('info', '[✓] Payment has gateway_response');
			$response = json_decode($payment->gateway_response, TRUE);
			log_message('info', 'Gateway Response: '.json_encode($response));

			// Try both payment_links and payments_links formats
			$links = isset($response['payment_links']) ? $response['payment_links'] :
					 (isset($response['payments_links']) ? $response['payments_links'] : NULL);

			if ($links && is_array($links))
			{
				log_message('info', 'Found '.count($links).' payment link(s)');
				foreach ($links as $link)
				{
					if (isset($link['url']))
					{
						log_message('info', '[✓] Payment link found: '.$link['url']);
						log_message('info', '---------- GET PAYMENT LINK SUCCESS ----------');
						return $link['url'];
					}
				}
			}
			log_message('error', '[✗] No payment links in gateway_response');
		}
		else
		{
			log_message('error', '[✗] Payment object missing or no gateway_response');
		}

		log_message('info', '---------- GET PAYMENT LINK FAILED ----------');
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
		log_message('info', '---------- VERIFY WEBHOOK SIGNATURE START ----------');
		log_message('info', 'Order ID: '.$order_id);
		log_message('info', 'Provided Signature: '.$signature);

		if (empty($this->secret_key))
		{
			log_message('error', '[✗] Secret key is empty');
			log_message('info', '---------- VERIFY WEBHOOK SIGNATURE FAILED ----------');
			return FALSE;
		}

		$msg = $order_id.$this->secret_key;
		$computed_signature = hash('sha256', $msg);
		log_message('info', 'Computed Signature: '.$computed_signature);

		$is_valid = hash_equals($computed_signature, $signature);
		log_message('info', '['.($is_valid ? '✓' : '✗').'] Signature '.($is_valid ? 'VALID' : 'INVALID'));
		log_message('info', '---------- VERIFY WEBHOOK SIGNATURE '.($is_valid ? 'SUCCESS' : 'FAILED').' ----------');

		return $is_valid;
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
		log_message('info', '---------- CASHFREE API REQUEST START ----------');
		log_message('info', 'Method: '.$method);
		log_message('info', 'Endpoint: '.$endpoint);

		if ( ! function_exists('curl_init'))
		{
			log_message('error', '[✗] cURL is NOT installed on this server');
			log_message('info', '---------- CASHFREE API REQUEST FAILED ----------');
			return NULL;
		}

		log_message('info', '[✓] cURL is available');

		$base_url = $this->is_sandbox ? $this->sandbox_url : $this->base_url;
		$url = $base_url.$endpoint;
		log_message('info', 'Using URL: '.($this->is_sandbox ? 'SANDBOX' : 'PRODUCTION'));
		log_message('info', 'Full URL: '.$url);

		$headers = array(
			'Content-Type: application/json',
			'X-Client-Id: '.$this->app_id,
			'X-Client-Secret: '.substr($this->secret_key, 0, 10).'...',
			'x-api-version: 2025-01-01',
			'Accept: application/json',
		);
		log_message('info', 'Headers (with masked secret): '.json_encode($headers));
		log_message('info', 'Request Body: '.json_encode($body));

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
			CURLOPT_HTTPHEADER => array(
				'Content-Type: application/json',
				'X-Client-Id: '.$this->app_id,
				'X-Client-Secret: '.$this->secret_key,
				'x-api-version: 2025-01-01',
				'Accept: application/json',
			),
			CURLOPT_SSL_VERIFYPEER => FALSE,
		));

		log_message('info', 'Executing cURL request...');
		$response = curl_exec($curl);
		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$error = curl_error($curl);
		$curl_info = curl_getinfo($curl);
		curl_close($curl);

		log_message('info', 'HTTP Status Code: '.$http_code);
		log_message('info', 'Response Time: '.round($curl_info['total_time'], 3).'s');
		log_message('info', 'Connect Time: '.round($curl_info['connect_time'], 3).'s');

		if ($error)
		{
			log_message('error', '[✗] cURL Error: '.$error);
			log_message('info', '---------- CASHFREE API REQUEST FAILED ----------');
			return NULL;
		}

		log_message('info', 'Raw Response: '.$response);

		$decoded = json_decode($response, TRUE);
		if (json_last_error() !== JSON_ERROR_NONE)
		{
			log_message('error', '[✗] JSON Decode Error: '.json_last_error_msg());
		}
		else
		{
			log_message('info', '[✓] Response decoded successfully');
		}

		log_message('info', 'Decoded Response: '.json_encode($decoded));

		if ($http_code >= 200 && $http_code < 300)
		{
			log_message('info', '[✓] API call successful (HTTP '.$http_code.')');
		}
		else
		{
			log_message('error', '[✗] API call failed (HTTP '.$http_code.')');
		}

		log_message('info', '---------- CASHFREE API REQUEST END ----------');

		return is_array($decoded) ? $decoded : NULL;
	}
}
