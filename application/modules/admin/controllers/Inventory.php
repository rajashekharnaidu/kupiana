<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Inventory workflows.
 *
 * Phase 6 moves stock writes into Inventory_model so direct movements,
 * adjustments and purchase receipts share the same ledger and rollup rules.
 *
 * @package Kupiana\Modules\Admin
 */
class Inventory extends Admin_Controller
{
	protected $active_menu = 'inventory.stock';
	protected $required_permission = 'inventory.view';

	public function __construct()
	{
		parent::__construct();
		$this->load->model('Inventory_model', 'inventory_service');
	}

	/**
	 * Stock dashboard and searchable warehouse balances.
	 *
	 * @return void
	 */
	public function index()
	{
		$params = array(
			'q' => $this->input->get('q', TRUE),
			'warehouse_id' => $this->input->get('warehouse_id', TRUE),
			'state' => $this->input->get('state', TRUE),
			'sort' => $this->input->get('sort', TRUE),
			'order' => $this->input->get('order', TRUE),
			'page' => $this->input->get('page', TRUE),
			'per_page' => $this->input->get('per_page', TRUE) ?: 25,
		);

		$this->breadcrumb('Inventory');
		$this->render('inventory_overview', array(
			'page_title' => 'Stock Overview',
			'stats' => $this->inventory_service->stats(),
			'pagination' => $this->inventory_service->stock_paginate($params),
			'warehouses' => $this->inventory_service->simple_options('warehouses'),
			'recent_movements' => $this->inventory_service->recent_movements(10),
		));
	}

	/**
	 * Low-stock view using each row's reorder level.
	 *
	 * @return void
	 */
	public function low_stock()
	{
		$_GET['state'] = 'low';
		$this->active_menu = 'inventory.low_stock';
		$this->index();
	}

	/**
	 * Download current stock balances as CSV.
	 *
	 * @return void
	 */
	public function export()
	{
		$params = array(
			'q' => $this->input->get('q', TRUE),
			'warehouse_id' => $this->input->get('warehouse_id', TRUE),
			'state' => $this->input->get('state', TRUE),
			'per_page' => 5000,
		);
		$rows = array();
		foreach ($this->inventory_service->stock_paginate($params)['data'] as $row)
		{
			$rows[] = array(
				$row->product_name,
				$row->product_sku,
				$row->variant_sku ?: '',
				$row->warehouse_name,
				$row->quantity,
				$row->reserved_quantity,
				$row->available_quantity,
				$row->reorder_level,
				$row->shelf_location,
				$row->updated_at,
			);
		}
		$this->audit->log('export', 'inventory', NULL, 'Inventory balances exported.');
		$this->export->csv('inventory-balances.csv', array('Product', 'SKU', 'Variant', 'Warehouse', 'On hand', 'Reserved', 'Available', 'Reorder level', 'Shelf', 'Updated'), $rows);
	}

	/**
	 * Stock-in workflow.
	 *
	 * @return void
	 */
	public function stock_in()
	{
		$this->active_menu = 'inventory.stock_in';
		$this->transaction_form('in');
	}

	/**
	 * Stock-out workflow.
	 *
	 * @return void
	 */
	public function stock_out()
	{
		$this->active_menu = 'inventory.stock_out';
		$this->transaction_form('out');
	}

	/**
	 * Adjustment workflow.
	 *
	 * @return void
	 */
	public function adjustment()
	{
		$this->active_menu = 'inventory.adjustments';
		$this->transaction_form('adjustment');
	}

	/**
	 * Purchase entry form.
	 *
	 * @return void
	 */
	public function purchase_create()
	{
		$this->require_permission('purchases.manage');

		if ($this->input->method(TRUE) === 'POST')
		{
			$this->form_validation->set_rules('supplier_id', 'Supplier', 'required|integer');
			$this->form_validation->set_rules('warehouse_id', 'Warehouse', 'required|integer');
			$this->form_validation->set_rules('order_date', 'Order Date', 'required');

			if ($this->form_validation->run() === TRUE)
			{
				$result = $this->inventory_service->create_purchase(
					array(
						'supplier_id' => $this->input->post('supplier_id', TRUE),
						'warehouse_id' => $this->input->post('warehouse_id', TRUE),
						'order_date' => $this->input->post('order_date', TRUE),
						'expected_date' => $this->input->post('expected_date', TRUE),
						'shipping_amount' => $this->input->post('shipping_amount', TRUE),
						'notes' => $this->input->post('notes', TRUE),
					),
					array(
						'product_id' => (array) $this->input->post('product_id', TRUE),
						'variant_id' => (array) $this->input->post('variant_id', TRUE),
						'quantity' => (array) $this->input->post('quantity', TRUE),
						'unit_cost' => (array) $this->input->post('unit_cost', TRUE),
						'tax_rate' => (array) $this->input->post('tax_rate', TRUE),
						'discount_amount' => (array) $this->input->post('discount_amount', TRUE),
					)
				);

				if ($result['success'])
				{
					$this->audit->log('purchase_created', 'purchase_orders', $result['purchase_id'], 'Purchase order created.');
					$this->session->set_flashdata('success', 'Purchase entry created.');
					redirect('admin/purchases/view/'.$result['purchase_id']);
				}
				$this->session->set_flashdata('error', $result['message']);
			}
		}

		$this->active_menu = 'purchasing.orders';
		$this->breadcrumb('Purchasing', 'admin/purchases');
		$this->breadcrumb('Create');
		$this->render('purchase_form', array(
			'page_title' => 'Create Purchase Entry',
			'products' => $this->inventory_service->product_options(),
			'variants' => $this->inventory_service->variant_options(),
			'suppliers' => $this->inventory_service->simple_options('suppliers'),
			'warehouses' => $this->inventory_service->simple_options('warehouses'),
			'errors' => validation_errors('<div>', '</div>'),
		));
	}

	/**
	 * Purchase detail and receive form.
	 *
	 * @param  int|null $id
	 * @return void
	 */
	public function purchase_view($id = NULL)
	{
		$this->require_permission('purchases.view');
		$purchase = $this->inventory_service->purchase((int) $id);
		if ( ! $purchase) { show_404(); }

		$this->active_menu = 'purchasing.orders';
		$this->breadcrumb('Purchasing', 'admin/purchases');
		$this->breadcrumb($purchase->po_number);
		$this->render('purchase_view', array(
			'page_title' => 'Purchase '.$purchase->po_number,
			'purchase' => $purchase,
			'items' => $this->inventory_service->purchase_items($purchase->id),
			'movements' => $this->db->from('stock_movements')->where(array('reference_type' => 'purchase_orders', 'reference_id' => (int) $purchase->id))->where('deleted_at IS NULL', NULL, FALSE)->order_by('created_at', 'DESC')->get()->result(),
		));
	}

	/**
	 * Receive purchase quantities into inventory.
	 *
	 * @param  int|null $id
	 * @return void
	 */
	public function purchase_receive($id = NULL)
	{
		$this->require_permission('purchases.manage');
		$result = $this->inventory_service->receive_purchase(
			(int) $id,
			(array) $this->input->post('received_quantity', TRUE),
			(array) $this->input->post('batch_number', TRUE)
		);
		$this->audit->log($result['success'] ? 'purchase_received' : 'purchase_receive_failed', 'purchase_orders', (int) $id, $result['success'] ? 'Purchase received into stock.' : $result['message']);
		$this->session->set_flashdata($result['success'] ? 'success' : 'error', $result['success'] ? 'Purchase stock received.' : $result['message']);
		redirect('admin/purchases/view/'.(int) $id);
	}

	/**
	 * Shared direct movement form.
	 *
	 * @param  string $mode
	 * @return void
	 */
	protected function transaction_form($mode)
	{
		$this->require_permission('inventory.manage');
		$this->data['active_menu'] = $this->active_menu;
		$title = $mode === 'in' ? 'Stock In' : ($mode === 'out' ? 'Stock Out' : 'Stock Adjustment');
		if ($this->input->method(TRUE) === 'POST')
		{
			$this->form_validation->set_rules(array(
				array('field' => 'product_id', 'label' => 'Product', 'rules' => 'required|integer'),
				array('field' => 'warehouse_id', 'label' => 'Warehouse', 'rules' => 'required|integer'),
				array('field' => 'quantity', 'label' => 'Quantity', 'rules' => 'required|integer|greater_than[0]'),
			));
			if ($mode === 'adjustment') { $this->form_validation->set_rules('reason', 'Reason', 'required|max_length[150]'); }

			if ($this->form_validation->run() === TRUE)
			{
				$signed = (int) $this->input->post('quantity', TRUE);
				if ($mode === 'out' || ($mode === 'adjustment' && (string) $this->input->post('direction', TRUE) === 'decrease'))
				{
					$signed *= -1;
				}

				$result = $this->inventory_service->record_movement(array(
					'product_id' => $this->input->post('product_id', TRUE),
					'variant_id' => $this->input->post('variant_id', TRUE),
					'warehouse_id' => $this->input->post('warehouse_id', TRUE),
					'quantity' => $signed,
					'type' => $mode === 'in' ? 'transfer_in' : ($mode === 'out' ? 'transfer_out' : 'adjustment'),
					'unit_cost' => $this->input->post('unit_cost', TRUE),
					'adjustment_reason' => $this->input->post('reason', TRUE),
					'notes' => $this->movement_notes($mode),
				));

				if ($result['success'])
				{
					$this->audit->log('stock_'.$mode, 'inventory', $result['inventory_id'], 'Inventory transaction recorded.', array('quantity' => $signed, 'balance_after' => $result['balance']));
					$this->session->set_flashdata('success', $title.' recorded successfully.');
					redirect($mode === 'adjustment' ? 'admin/inventory/adjustments' : 'admin/inventory/stock-'.$mode);
				}
				$this->session->set_flashdata('error', $result['message']);
			}
		}

		$this->breadcrumb('Inventory', 'admin/inventory');
		$this->breadcrumb($title);
		$this->render('inventory_transaction', array(
			'page_title' => $title,
			'mode' => $mode,
			'title' => $title,
			'products' => $this->inventory_service->product_options(),
			'variants' => $this->inventory_service->variant_options(),
			'warehouses' => $this->inventory_service->simple_options('warehouses'),
			'errors' => validation_errors('<div>', '</div>'),
		));
	}

	/**
	 * @param  string $mode
	 * @return string
	 */
	protected function movement_notes($mode)
	{
		$notes = trim((string) $this->input->post('notes', TRUE));
		if ($mode === 'adjustment')
		{
			$reason = trim((string) $this->input->post('reason', TRUE));
			return trim($reason.($notes !== '' ? ' — '.$notes : ''));
		}
		return $notes;
	}
}
