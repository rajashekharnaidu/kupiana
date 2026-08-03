<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Razorpay_gateway
 *
 * Minimal server-side Razorpay adapter. Uses direct HTTPS calls so the app does
 * not require Composer locally.
 *
 * @package Kupiana\Libraries
 */
class Razorpay_gateway
{
	protected $CI;
	protected $key_id;
	protected $key_secret;
	protected $webhook_secret;
	protected $enabled;
	protected $api_base = 'https://api.razorpay.com/v1';

	public function __construct()
	{
		$this->CI =& get_instance();
		$this->key_id = (string) $this->setting('razorpay_key_id', kupiana_env('RAZORPAY_KEY_ID', ''));
		$this->key_secret = (string) $this->setting('razorpay_key_secret', kupiana_env('RAZORPAY_KEY_SECRET', ''));
		$this->webhook_secret = (string) $this->setting('razorpay_webhook_secret', kupiana_env('RAZORPAY_WEBHOOK_SECRET', ''));
		$this->enabled = $this->setting_bool('razorpay_enabled', FALSE) && $this->key_id !== '' && $this->key_secret !== '';
	}

	/** @return bool */
	public function enabled()
	{
		return $this->enabled;
	}

	/** @return string */
	public function key_id()
	{
		return $this->key_id;
	}

	/** @return string */
	public function webhook_secret()
	{
		return $this->webhook_secret;
	}

	/**
	 * Create a Razorpay order or an offline surrogate.
	 *
	 * @param  object $order
	 * @param  object $payment
	 * @return array
	 */
	public function create_order($order, $payment)
	{
		$payload = array(
			'amount' => (int) round((float) $payment->amount * 100),
			'currency' => $payment->currency ?: 'INR',
			'receipt' => $order->order_number,
			'payment_capture' => 1,
			'notes' => array('order_id' => (string) $order->id, 'payment_id' => (string) $payment->id),
		);

		if ( ! $this->enabled())
		{
			return array('success' => TRUE, 'offline' => TRUE, 'order' => array(
				'id' => 'order_offline_'.strtolower(substr(generate_token(8), 0, 12)),
				'entity' => 'order',
				'amount' => $payload['amount'],
				'currency' => $payload['currency'],
				'receipt' => $payload['receipt'],
				'status' => 'created',
				'notes' => $payload['notes'],
			), 'request' => $payload);
		}

		return $this->post('/orders', $payload);
	}

	/**
	 * Verify Checkout signature.
	 *
	 * @param  string $gateway_order_id
	 * @param  string $gateway_payment_id
	 * @param  string $signature
	 * @return bool
	 */
	public function verify_checkout_signature($gateway_order_id, $gateway_payment_id, $signature)
	{
		if ( ! $this->enabled()) { return FALSE; }
		$expected = hash_hmac('sha256', $gateway_order_id.'|'.$gateway_payment_id, $this->key_secret);
		return hash_equals($expected, (string) $signature);
	}

	/**
	 * Verify webhook signature over the raw request body.
	 *
	 * @param  string $body
	 * @param  string $signature
	 * @return bool
	 */
	public function verify_webhook_signature($body, $signature)
	{
		if ($this->webhook_secret === '') { return FALSE; }
		return hash_equals(hash_hmac('sha256', $body, $this->webhook_secret), (string) $signature);
	}

	/**
	 * @param  string $path
	 * @param  array  $payload
	 * @return array
	 */
	protected function post($path, array $payload)
	{
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => $this->api_base.$path,
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_POST => TRUE,
			CURLOPT_POSTFIELDS => json_encode($payload),
			CURLOPT_USERPWD => $this->key_id.':'.$this->key_secret,
			CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
		));
		$response = curl_exec($curl);
		$error = curl_error($curl);
		$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);

		$decoded = json_decode((string) $response, TRUE);
		if ($error || $code < 200 || $code >= 300)
		{
			return array('success' => FALSE, 'message' => $error ?: 'Razorpay HTTP '.$code, 'request' => $payload, 'response' => is_array($decoded) ? $decoded : array('raw' => $response));
		}

		return array('success' => TRUE, 'order' => $decoded, 'request' => $payload, 'response' => $decoded);
	}

	/**
	 * @param  string $key
	 * @param  mixed  $default
	 * @return mixed
	 */
	protected function setting($key, $default = NULL)
	{
		return isset($this->CI->settings) ? $this->CI->settings->get($key, $default) : $default;
	}

	/**
	 * @param  string $key
	 * @param  bool   $default
	 * @return bool
	 */
	protected function setting_bool($key, $default = FALSE)
	{
		return isset($this->CI->settings) ? $this->CI->settings->get_bool($key, $default) : $default;
	}
}
