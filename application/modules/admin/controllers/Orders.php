<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Operational order screens for the back office.
 */
class Orders extends Admin_Controller
{
	protected $active_menu = 'orders.all';
	protected $required_permission = 'orders.view';

	public function __construct()
	{
		parent::__construct();
		$this->load->model('Order_model', 'orders_service');
	}

	/** Show an order with items, payment, fulfilment and history. */
	public function view($id = NULL)
	{
		$order = $this->order((int) $id);
		if ( ! $order) { show_404(); }

		$this->breadcrumb('Orders', 'admin/orders');
		$this->breadcrumb($order->order_number);
		$this->render('order_view', array(
			'page_title' => 'Order '.$order->order_number,
			'order' => $order,
			'items' => $this->children('order_items', 'order_id', $order->id),
			'payments' => $this->children('payments', 'order_id', $order->id),
			'shipments' => $this->children('shipments', 'order_id', $order->id),
			'refunds' => $this->children('refunds', 'order_id', $order->id),
			'invoices' => $this->children('invoices', 'order_id', $order->id),
			'history' => $this->children('order_status_history', 'order_id', $order->id, 'created_at', 'DESC'),
			'order_statuses' => app_config('order_statuses', array()),
			'payment_statuses' => app_config('payment_statuses', array()),
		));
	}

	/** Update lifecycle status and append an audit/status-history entry. */
	public function status($id = NULL)
	{
		$this->require_permission('orders.edit');
		$order = $this->order((int) $id);
		if ( ! $order) { show_404(); }

		$statuses = app_config('order_statuses', array());
		$to_status = (string) $this->input->post('order_status', TRUE);
		if ( ! isset($statuses[$to_status]))
		{
			$this->session->set_flashdata('error', 'Choose a valid order status.');
			redirect('admin/orders/view/'.$order->id);
		}

		$result = $this->orders_service->update_status($order->id, $to_status, trim((string) $this->input->post('comment', TRUE)));
		$this->audit->updated('orders', $order->id, (array) $order, array('order_status' => $to_status));
		$this->session->set_flashdata($result['success'] ? 'success' : 'error', $result['message']);

		redirect('admin/orders/view/'.$order->id);
	}

	/** Create an invoice row from the order totals when none exists yet. */
	public function create_invoice($id = NULL)
	{
		$this->require_permission('invoices.manage');
		$order = $this->order((int) $id);
		if ( ! $order) { show_404(); }
		$exists = $this->db->where('order_id', $order->id)->where('deleted_at IS NULL', NULL, FALSE)->count_all_results('invoices') > 0;
		if ( ! $exists)
		{
			$now = date('Y-m-d H:i:s');
			$number = 'INV-'.date('Ymd').'-'.str_pad((string) $order->id, 5, '0', STR_PAD_LEFT);
			$this->db->insert('invoices', array(
				'invoice_number' => $number,
				'order_id' => $order->id,
				'invoice_date' => date('Y-m-d'),
				'due_date' => NULL,
				'place_of_supply' => $order->place_of_supply,
				'subtotal' => $order->subtotal,
				'tax_amount' => $order->tax_amount,
				'total_amount' => $order->total_amount,
				'pdf_path' => NULL,
				'status' => 'active',
				'created_at' => $now,
				'updated_at' => $now,
				'created_by' => (int) $this->session->userdata('user_id') ?: NULL,
				'updated_by' => (int) $this->session->userdata('user_id') ?: NULL,
			));
			$this->audit->created('invoices', (int) $this->db->insert_id(), array('order_id' => $order->id, 'invoice_number' => $number));
			$this->session->set_flashdata('success', 'Invoice generated.');
		}
		else
		{
			$this->session->set_flashdata('info', 'Invoice already exists for this order.');
		}
		redirect('admin/orders/view/'.$order->id);
	}

	/** Download an invoice as printable HTML. */
	public function invoice($invoice_id = NULL)
	{
		$this->require_permission('invoices.view');
		$invoice = $this->db->from('invoices')->where('id', (int) $invoice_id)->where('deleted_at IS NULL', NULL, FALSE)->get()->row();
		if ( ! $invoice) { show_404(); }
		$order = $this->order((int) $invoice->order_id);
		$items = $this->children('order_items', 'order_id', $invoice->order_id);
		$html = $this->load->view('invoice_print', array('invoice' => $invoice, 'order' => $order, 'items' => $items), TRUE);
		$this->output->set_content_type('text/html', 'utf-8')->set_output($html);
	}

	/** Append a shipment timeline event. */
	public function tracking($shipment_id = NULL)
	{
		$this->require_permission('shipping.manage');
		$this->load->model('Tracking_model', 'tracking_service');
		$shipment = $this->db->from('shipments')->where('id', (int) $shipment_id)->where('deleted_at IS NULL', NULL, FALSE)->get()->row();
		if ( ! $shipment) { show_404(); }
		$this->form_validation->set_rules('status_text', 'Status', 'required|max_length[100]');
		$this->form_validation->set_rules('occurred_at', 'Occurred At', 'required');
		if ($this->form_validation->run() === TRUE)
		{
			$result = $this->tracking_service->add_event($shipment->id, $this->input->post(NULL, TRUE));
			$this->audit->log($result['success'] ? 'shipment_tracking_create' : 'shipment_tracking_failed', 'shipments', $shipment->id, $result['message']);
			$this->session->set_flashdata($result['success'] ? 'success' : 'error', $result['message']);
		}
		else
		{
			$this->session->set_flashdata('error', strip_tags(validation_errors()));
		}
		redirect('admin/orders/view/'.$shipment->order_id);
	}

	protected function order($id)
	{
		return $this->db->from('orders')->where('id', (int) $id)->where('deleted_at IS NULL', NULL, FALSE)->get()->row();
	}

	protected function children($table, $column, $id, $sort = 'id', $order = 'ASC')
	{
		$this->db->from($table)->where($column, (int) $id);
		if ($this->db->field_exists('deleted_at', $table)) { $this->db->where('deleted_at IS NULL', NULL, FALSE); }
		return $this->db->order_by($sort, $order)->get()->result();
	}
}
