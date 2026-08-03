<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin payment operations.
 *
 * @package Kupiana\Modules\Admin
 */
class Payments extends Admin_Controller
{
	protected $active_menu = 'payments.all';
	protected $required_permission = 'payments.view';

	public function __construct()
	{
		parent::__construct();
		$this->load->model('Payment_model', 'payments');
	}

	/**
	 * Payment detail.
	 *
	 * @param  int|null $id
	 * @return void
	 */
	public function view($id = NULL)
	{
		$payment = $this->payments->find((int) $id);
		if ( ! $payment) { show_404(); }
		$order = $this->db->from('orders')->where('id', (int) $payment->order_id)->get()->row();
		$logs = $this->db->from('payment_logs')->where('payment_id', (int) $payment->id)->where('deleted_at IS NULL', NULL, FALSE)->order_by('created_at', 'DESC')->get()->result();
		$this->breadcrumb('Payments', 'admin/payments');
		$this->breadcrumb($payment->payment_number);
		$this->render('payment_view', array('page_title' => 'Payment '.$payment->payment_number, 'payment' => $payment, 'order' => $order, 'logs' => $logs));
	}

	/**
	 * Mark a pending/manual payment as captured.
	 *
	 * @param  int|null $id
	 * @return void
	 */
	public function capture($id = NULL)
	{
		$this->require_permission('payments.manage');
		$payment = $this->payments->find((int) $id);
		if ( ! $payment) { show_404(); }
		$this->payments->mark_captured($payment, array('payment_id' => $payment->gateway_payment_id ?: 'manual_'.strtolower(substr(generate_token(8), 0, 12)), 'order_id' => $payment->gateway_order_id, 'method' => $this->input->post('method', TRUE) ?: 'manual', 'source' => 'admin'));
		$this->audit->log('payment_captured', 'payments', $payment->id, 'Payment marked captured by admin.');
		$this->session->set_flashdata('success', 'Payment marked captured.');
		redirect('admin/payments/view/'.$payment->id);
	}

	/**
	 * Create a refund record and sync payment/order refund status.
	 *
	 * @param  int|null $id
	 * @return void
	 */
	public function refund($id = NULL)
	{
		$this->require_permission('payments.manage');
		$payment = $this->payments->find((int) $id);
		if ( ! $payment) { show_404(); }
		$amount = (float) $this->input->post('amount', TRUE);
		if ($amount <= 0 || $amount > (float) $payment->amount)
		{
			$this->session->set_flashdata('error', 'Enter a valid refund amount.');
			redirect('admin/payments/view/'.$payment->id);
		}
		$now = date('Y-m-d H:i:s');
		$this->db->trans_begin();
		$this->db->insert('refunds', array(
			'order_id' => (int) $payment->order_id,
			'payment_id' => (int) $payment->id,
			'refund_number' => generate_code('REF'),
			'amount' => $amount,
			'reason' => $this->input->post('reason', TRUE) ?: NULL,
			'refund_status' => 'completed',
			'gateway_refund_id' => 'manual_'.strtolower(substr(generate_token(8), 0, 12)),
			'gateway_response' => json_encode(array('source' => 'admin')),
			'processed_at' => $now,
			'processed_by' => (int) $this->session->userdata('user_id') ?: NULL,
			'status' => 'active',
			'created_at' => $now,
			'updated_at' => $now,
			'created_by' => (int) $this->session->userdata('user_id') ?: NULL,
			'updated_by' => (int) $this->session->userdata('user_id') ?: NULL,
		));
		$refund_sum = (float) $this->db->select_sum('amount')->from('refunds')->where('payment_id', (int) $payment->id)->where('refund_status', 'completed')->where('deleted_at IS NULL', NULL, FALSE)->get()->row()->amount;
		$status = $refund_sum >= (float) $payment->amount ? 'refunded' : 'partially_refunded';
		$this->db->where('id', (int) $payment->id)->update('payments', array('status' => $status, 'updated_at' => $now, 'updated_by' => (int) $this->session->userdata('user_id') ?: NULL));
		$this->db->where('id', (int) $payment->order_id)->update('orders', array('payment_status' => $status, 'refunded_amount' => $refund_sum, 'updated_at' => $now, 'updated_by' => (int) $this->session->userdata('user_id') ?: NULL));
		$this->payments->log('refund.completed', $payment->id, $payment->order_id, $this->input->post(NULL, TRUE), array('amount' => $amount, 'status' => $status));
		if ($this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback();
			$this->session->set_flashdata('error', 'Refund could not be recorded.');
		}
		else
		{
			$this->db->trans_commit();
			$this->audit->log('refund_recorded', 'payments', $payment->id, 'Refund recorded by admin.');
			$this->session->set_flashdata('success', 'Refund recorded.');
		}
		redirect('admin/payments/view/'.$payment->id);
	}
}
