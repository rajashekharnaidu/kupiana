<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Payment_model
 *
 * Payment persistence and gateway state transitions. Important: `payments.status`
 * is the gateway state; MY_Model lifecycle writes must target `status_flag`.
 *
 * @package Kupiana\Models
 */
class Payment_model extends MY_Model
{
	protected $table = 'payments';
	protected $status_column = 'status_flag';
	protected $fillable = array(
		'order_id', 'payment_number', 'gateway', 'method', 'amount', 'currency',
		'status', 'gateway_order_id', 'gateway_payment_id', 'gateway_signature',
		'gateway_response', 'failure_reason', 'paid_at', 'status_flag',
	);
	protected $searchable = array('payment_number', 'gateway_order_id', 'gateway_payment_id');
	protected $sortable = array('payment_number', 'amount', 'status', 'paid_at', 'created_at');

	/**
	 * Find the latest active payment for an order.
	 *
	 * @param  int $order_id
	 * @return object|null
	 */
	public function latest_for_order($order_id)
	{
		return $this->db->from($this->table)
			->where('order_id', (int) $order_id)
			->where('status_flag', 'active')
			->where('deleted_at IS NULL', NULL, FALSE)
			->order_by('id', 'DESC')
			->limit(1)
			->get()
			->row();
	}

	/**
	 * Find a payment by gateway order id.
	 *
	 * @param  string $gateway_order_id
	 * @return object|null
	 */
	public function find_by_gateway_order($gateway_order_id)
	{
		return $this->db->from($this->table)
			->where('gateway_order_id', (string) $gateway_order_id)
			->where('status_flag', 'active')
			->where('deleted_at IS NULL', NULL, FALSE)
			->limit(1)
			->get()
			->row();
	}

	/**
	 * Create or reuse a pending payment row for an order.
	 *
	 * @param  object $order
	 * @param  string $gateway
	 * @return object
	 */
	public function pending_for_order($order, $gateway = 'razorpay')
	{
		$existing = $this->latest_for_order($order->id);
		if ($existing && $existing->status === 'pending' && $existing->gateway === $gateway)
		{
			return $existing;
		}

		$now = date('Y-m-d H:i:s');
		$id = $this->insert(array(
			'order_id' => (int) $order->id,
			'payment_number' => generate_code('PAY'),
			'gateway' => $gateway,
			'method' => $order->payment_method,
			'amount' => (float) $order->total_amount,
			'currency' => $order->currency ?: 'INR',
			'status' => 'pending',
			'status_flag' => 'active',
			'created_at' => $now,
			'updated_at' => $now,
		));

		return $this->find($id);
	}

	/**
	 * Attach a Razorpay order id to a payment.
	 *
	 * @param  int    $payment_id
	 * @param  string $gateway_order_id
	 * @param  array  $response
	 * @return bool
	 */
	public function attach_gateway_order($payment_id, $gateway_order_id, array $response = array())
	{
		return (bool) $this->db->where('id', (int) $payment_id)->update($this->table, array(
			'gateway_order_id' => $gateway_order_id,
			'gateway_response' => json_encode($response),
			'updated_at' => date('Y-m-d H:i:s'),
			'updated_by' => $this->current_user_id(),
		));
	}

	/**
	 * Mark a payment captured and sync the parent order.
	 *
	 * @param  object $payment
	 * @param  array  $gateway
	 * @return bool
	 */
	public function mark_captured($payment, array $gateway)
	{
		$now = date('Y-m-d H:i:s');
		$this->db->trans_begin();
		$this->db->where('id', (int) $payment->id)->update($this->table, array(
			'status' => 'captured',
			'method' => array_get($gateway, 'method', $payment->method),
			'gateway_payment_id' => array_get($gateway, 'payment_id', $payment->gateway_payment_id),
			'gateway_signature' => array_get($gateway, 'signature', $payment->gateway_signature),
			'gateway_response' => json_encode($gateway),
			'paid_at' => $now,
			'updated_at' => $now,
			'updated_by' => $this->current_user_id(),
		));
		$this->db->where('id', (int) $payment->order_id)->update('orders', array(
			'payment_status' => 'paid',
			'paid_amount' => (float) $payment->amount,
			'updated_at' => $now,
			'updated_by' => $this->current_user_id(),
		));
		$this->log('payment.captured', $payment->id, $payment->order_id, array(), $gateway);

		if ($this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback();
			return FALSE;
		}
		$this->db->trans_commit();
		return TRUE;
	}

	/**
	 * Mark a payment failed.
	 *
	 * @param  object $payment
	 * @param  string $reason
	 * @param  array  $response
	 * @return bool
	 */
	public function mark_failed($payment, $reason, array $response = array())
	{
		$this->log('payment.failed', $payment ? $payment->id : NULL, $payment ? $payment->order_id : NULL, array(), $response);
		if ( ! $payment) { return FALSE; }
		$now = date('Y-m-d H:i:s');
		$this->db->where('id', (int) $payment->id)->update($this->table, array(
			'status' => 'failed',
			'failure_reason' => $reason,
			'gateway_response' => json_encode($response),
			'updated_at' => $now,
			'updated_by' => $this->current_user_id(),
		));
		$this->db->where('id', (int) $payment->order_id)->update('orders', array(
			'payment_status' => 'failed',
			'updated_at' => $now,
			'updated_by' => $this->current_user_id(),
		));
		return TRUE;
	}

	/**
	 * Payment event log.
	 *
	 * @param  string   $event
	 * @param  int|null $payment_id
	 * @param  int|null $order_id
	 * @param  array    $request
	 * @param  array    $response
	 * @return void
	 */
	public function log($event, $payment_id = NULL, $order_id = NULL, array $request = array(), array $response = array())
	{
		$now = date('Y-m-d H:i:s');
		$this->db->insert('payment_logs', array(
			'payment_id' => $payment_id ? (int) $payment_id : NULL,
			'order_id' => $order_id ? (int) $order_id : NULL,
			'gateway' => 'razorpay',
			'event' => $event,
			'request' => json_encode($request),
			'response' => json_encode($response),
			'ip_address' => $this->input->ip_address(),
			'status' => 'active',
			'created_at' => $now,
			'updated_at' => $now,
			'created_by' => $this->current_user_id(),
			'updated_by' => $this->current_user_id(),
		));
	}

	/**
	 * @return int|null
	 */
	protected function current_user_id()
	{
		$id = $this->session->userdata('user_id');
		return $id ? (int) $id : NULL;
	}
}
