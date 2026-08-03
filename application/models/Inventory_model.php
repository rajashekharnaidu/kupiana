<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Inventory_model
 *
 * Single stock-writing gateway for Phase 6. All quantity changes update the
 * warehouse balance, append a signed stock ledger row, and roll the product /
 * variant stock totals inside one transaction.
 *
 * @package Kupiana\Models
 */
class Inventory_model extends CI_Model
{
	/**
	 * Dashboard statistics for stock control.
	 *
	 * @return array
	 */
	public function stats()
	{
		$row = $this->db
			->select('COUNT(*) AS records, COALESCE(SUM(quantity), 0) AS units, COALESCE(SUM(reserved_quantity), 0) AS reserved, COALESCE(SUM(quantity * COALESCE(NULLIF(product_variants.cost_price, 0), products.cost_price)), 0) AS value', FALSE)
			->from('inventory')
			->join('products', 'products.id = inventory.product_id', 'left')
			->join('product_variants', 'product_variants.id = inventory.variant_id', 'left')
			->where('inventory.deleted_at IS NULL', NULL, FALSE)
			->get()
			->row();

		return array(
			'records'   => $row ? (int) $row->records : 0,
			'units'     => $row ? (int) $row->units : 0,
			'reserved'  => $row ? (int) $row->reserved : 0,
			'available' => $row ? max(0, (int) $row->units - (int) $row->reserved) : 0,
			'value'     => $row ? (float) $row->value : 0.0,
			'low_stock' => $this->low_stock_count(),
			'out_stock' => $this->out_of_stock_count(),
		);
	}

	/**
	 * Paginated stock balances with product, variant and warehouse labels.
	 *
	 * @param  array $params
	 * @return array
	 */
	public function stock_paginate(array $params)
	{
		$page = max(1, (int) array_get($params, 'page', 1));
		$per_page = max(1, min(100, (int) array_get($params, 'per_page', 25)));
		$offset = ($page - 1) * $per_page;

		$this->stock_query($params, FALSE);
		$total = (int) $this->db->count_all_results();

		$this->stock_query($params, TRUE);
		$sort = in_array(array_get($params, 'sort'), array('product_name', 'warehouse_name', 'quantity', 'available_quantity', 'reorder_level', 'updated_at'), TRUE) ? array_get($params, 'sort') : 'updated_at';
		$order = strtolower((string) array_get($params, 'order')) === 'asc' ? 'ASC' : 'DESC';

		if ($sort === 'available_quantity')
		{
			$this->db->order_by('(inventory.quantity - inventory.reserved_quantity)', $order, FALSE);
		}
		else
		{
			$this->db->order_by($sort, $order);
		}

		$rows = $this->db->limit($per_page, $offset)->get()->result();

		return array(
			'data' => $rows,
			'total' => $total,
			'page' => $page,
			'per_page' => $per_page,
			'total_pages' => (int) ceil($total / $per_page),
			'from' => $total ? $offset + 1 : 0,
			'to' => min($total, $offset + $per_page),
		);
	}

	/**
	 * Recent signed ledger rows.
	 *
	 * @param  int $limit
	 * @return array
	 */
	public function recent_movements($limit = 20)
	{
		return $this->db
			->select('stock_movements.*, products.name AS product_name, products.sku AS product_sku, product_variants.sku AS variant_sku, warehouses.name AS warehouse_name')
			->from('stock_movements')
			->join('products', 'products.id = stock_movements.product_id', 'left')
			->join('product_variants', 'product_variants.id = stock_movements.variant_id', 'left')
			->join('warehouses', 'warehouses.id = stock_movements.warehouse_id', 'left')
			->where('stock_movements.deleted_at IS NULL', NULL, FALSE)
			->order_by('stock_movements.created_at', 'DESC')
			->limit((int) $limit)
			->get()
			->result();
	}

	/**
	 * Record a direct inventory movement.
	 *
	 * @param  array $data
	 * @return array
	 */
	public function record_movement(array $data)
	{
		$product_id = (int) array_get($data, 'product_id');
		$variant_id = (int) array_get($data, 'variant_id', 0);
		$warehouse_id = (int) array_get($data, 'warehouse_id');
		$quantity = (int) array_get($data, 'quantity');
		$type = (string) array_get($data, 'type', 'adjustment');
		$unit_cost = (float) array_get($data, 'unit_cost', 0);
		$notes = trim((string) array_get($data, 'notes'));
		$adjustment_reason = trim((string) array_get($data, 'adjustment_reason'));
		$reference_type = array_get($data, 'reference_type');
		$reference_id = array_get($data, 'reference_id');
		$batch_id = array_get($data, 'batch_id');
		$user_id = $this->current_user_id();

		if ($quantity === 0)
		{
			return $this->fail('Quantity must be non-zero.');
		}

		if ( ! $this->active_product($product_id) || ! $this->active_warehouse($warehouse_id))
		{
			return $this->fail('Choose an active product and warehouse.');
		}

		if ($variant_id > 0 && ! $this->variant_belongs_to_product($variant_id, $product_id))
		{
			return $this->fail('Choose a variant that belongs to the selected product.');
		}

		$this->db->trans_begin();
		$stock = $this->stock_row($product_id, $variant_id, $warehouse_id);
		$current = $stock ? (int) $stock->quantity : 0;
		$balance = $current + $quantity;

		if ($balance < 0)
		{
			$this->db->trans_rollback();
			return $this->fail('Stock cannot go below zero. Current balance is '.$current.'.');
		}

		$now = date('Y-m-d H:i:s');
		if ($type === 'adjustment' && ! $reference_id && $adjustment_reason !== '')
		{
			$this->db->insert('stock_adjustments', array(
				'reference_no' => 'ADJ-'.date('Ymd').'-'.strtoupper(substr(generate_token(8), 0, 6)),
				'warehouse_id' => $warehouse_id,
				'adjust_date' => date('Y-m-d'),
				'reason' => $adjustment_reason,
				'total_items' => 1,
				'notes' => $notes !== '' ? $notes : NULL,
				'status' => 'active',
				'created_at' => $now,
				'updated_at' => $now,
				'created_by' => $user_id,
				'updated_by' => $user_id,
			));
			$reference_type = 'stock_adjustments';
			$reference_id = (int) $this->db->insert_id();
		}

		if ($stock)
		{
			$this->db->where('id', $stock->id)->update('inventory', array(
				'quantity' => $balance,
				'updated_at' => $now,
				'updated_by' => $user_id,
			));
			$inventory_id = (int) $stock->id;
		}
		else
		{
			$this->db->insert('inventory', array(
				'product_id' => $product_id,
				'variant_id' => $variant_id,
				'warehouse_id' => $warehouse_id,
				'quantity' => $balance,
				'reserved_quantity' => 0,
				'reorder_level' => (int) app_config('inventory.low_stock_threshold', 10),
				'status' => 'active',
				'created_at' => $now,
				'updated_at' => $now,
				'created_by' => $user_id,
				'updated_by' => $user_id,
			));
			$inventory_id = (int) $this->db->insert_id();
		}

		$this->db->insert('stock_movements', array(
			'product_id' => $product_id,
			'variant_id' => $variant_id,
			'warehouse_id' => $warehouse_id,
			'batch_id' => $batch_id ? (int) $batch_id : NULL,
			'type' => $type,
			'quantity' => $quantity,
			'balance_after' => $balance,
			'unit_cost' => $unit_cost,
			'reference_type' => $reference_type,
			'reference_id' => $reference_id ? (int) $reference_id : NULL,
			'notes' => $notes !== '' ? $notes : NULL,
			'status' => 'active',
			'created_at' => $now,
			'updated_at' => $now,
			'created_by' => $user_id,
			'updated_by' => $user_id,
		));

		$this->rollup_stock($product_id, $variant_id);

		if ($this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback();
			return $this->fail('Inventory transaction could not be saved.');
		}

		$this->db->trans_commit();
		return array('success' => TRUE, 'inventory_id' => $inventory_id, 'balance' => $balance);
	}

	/**
	 * Create a purchase order with item lines.
	 *
	 * @param  array $header
	 * @param  array $items
	 * @return array
	 */
	public function create_purchase(array $header, array $items)
	{
		$user_id = $this->current_user_id();
		$now = date('Y-m-d H:i:s');
		$clean = $this->normalise_purchase_items($items);

		if (empty($clean))
		{
			return $this->fail('Add at least one product line.');
		}

		$subtotal = 0;
		$tax = 0;
		$total = 0;
		foreach ($clean as $item)
		{
			$subtotal += $item['quantity'] * $item['unit_cost'];
			$tax += $item['tax_amount'];
			$total += $item['total'];
		}

		$this->db->trans_begin();
		$this->db->insert('purchase_orders', array(
			'po_number' => $this->purchase_number(),
			'supplier_id' => (int) array_get($header, 'supplier_id'),
			'warehouse_id' => (int) array_get($header, 'warehouse_id'),
			'order_date' => array_get($header, 'order_date') ?: date('Y-m-d'),
			'expected_date' => array_get($header, 'expected_date') ?: NULL,
			'subtotal' => $subtotal,
			'tax_amount' => $tax,
			'discount_amount' => 0,
			'shipping_amount' => (float) array_get($header, 'shipping_amount', 0),
			'total_amount' => $total + (float) array_get($header, 'shipping_amount', 0),
			'paid_amount' => 0,
			'payment_status' => 'pending',
			'receive_status' => 'pending',
			'notes' => array_get($header, 'notes') ?: NULL,
			'status' => 'active',
			'created_at' => $now,
			'updated_at' => $now,
			'created_by' => $user_id,
			'updated_by' => $user_id,
		));
		$purchase_id = (int) $this->db->insert_id();

		foreach ($clean as $item)
		{
			$item['purchase_order_id'] = $purchase_id;
			$item['status'] = 'active';
			$item['created_at'] = $now;
			$item['updated_at'] = $now;
			$item['created_by'] = $user_id;
			$item['updated_by'] = $user_id;
			$this->db->insert('purchase_order_items', $item);
		}

		if ($this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback();
			return $this->fail('Purchase order could not be saved.');
		}

		$this->db->trans_commit();
		return array('success' => TRUE, 'purchase_id' => $purchase_id);
	}

	/**
	 * Receive quantities from a purchase order into stock.
	 *
	 * @param  int   $purchase_id
	 * @param  array $received
	 * @param  array $batch_numbers
	 * @return array
	 */
	public function receive_purchase($purchase_id, array $received, array $batch_numbers = array())
	{
		$purchase = $this->purchase($purchase_id);
		if ( ! $purchase || $purchase->receive_status === 'cancelled')
		{
			return $this->fail('Purchase order is not receivable.');
		}

		$items = $this->purchase_items($purchase_id);
		$now = date('Y-m-d H:i:s');
		$user_id = $this->current_user_id();
		$received_any = FALSE;

		$this->db->trans_begin();
		foreach ($items as $item)
		{
			$qty = max(0, (int) array_get($received, $item->id, 0));
			$remaining = max(0, (int) $item->quantity - (int) $item->received_quantity);
			if ($qty <= 0) { continue; }
			if ($qty > $remaining)
			{
				$this->db->trans_rollback();
				return $this->fail('Receive quantity cannot exceed the pending quantity.');
			}

			$variant_id = (int) $item->variant_id;
			$batch_id = NULL;
			$batch_number = trim((string) array_get($batch_numbers, $item->id));
			if ($batch_number !== '')
			{
				$this->db->insert('batches', array(
					'product_id' => (int) $item->product_id,
					'variant_id' => $variant_id,
					'warehouse_id' => (int) $purchase->warehouse_id,
					'batch_number' => $batch_number,
					'quantity' => $qty,
					'cost_price' => (float) $item->unit_cost,
					'status' => 'active',
					'created_at' => $now,
					'updated_at' => $now,
					'created_by' => $user_id,
					'updated_by' => $user_id,
				));
				$batch_id = (int) $this->db->insert_id();
			}

			$this->db->where('id', $item->id)->update('purchase_order_items', array(
				'received_quantity' => (int) $item->received_quantity + $qty,
				'updated_at' => $now,
				'updated_by' => $user_id,
			));

			$result = $this->record_movement(array(
				'product_id' => (int) $item->product_id,
				'variant_id' => $variant_id,
				'warehouse_id' => (int) $purchase->warehouse_id,
				'quantity' => $qty,
				'type' => 'purchase',
				'unit_cost' => (float) $item->unit_cost,
				'reference_type' => 'purchase_orders',
				'reference_id' => (int) $purchase_id,
				'batch_id' => $batch_id,
				'notes' => 'Purchase receipt '.$purchase->po_number,
			));

			if ( ! $result['success'])
			{
				$this->db->trans_rollback();
				return $result;
			}

			$received_any = TRUE;
		}

		if ( ! $received_any)
		{
			$this->db->trans_rollback();
			return $this->fail('Enter at least one quantity to receive.');
		}

		$status = $this->purchase_receive_status($purchase_id);
		$this->db->where('id', (int) $purchase_id)->update('purchase_orders', array(
			'receive_status' => $status,
			'received_date' => $status === 'received' ? date('Y-m-d') : NULL,
			'updated_at' => $now,
			'updated_by' => $user_id,
		));

		if ($this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback();
			return $this->fail('Purchase receipt could not be saved.');
		}

		$this->db->trans_commit();
		return array('success' => TRUE, 'message' => 'Purchase stock received.');
	}

	/**
	 * Purchase header by id.
	 *
	 * @param  int $id
	 * @return object|null
	 */
	public function purchase($id)
	{
		return $this->db
			->select('purchase_orders.*, suppliers.name AS supplier_name, warehouses.name AS warehouse_name')
			->from('purchase_orders')
			->join('suppliers', 'suppliers.id = purchase_orders.supplier_id', 'left')
			->join('warehouses', 'warehouses.id = purchase_orders.warehouse_id', 'left')
			->where('purchase_orders.id', (int) $id)
			->where('purchase_orders.deleted_at IS NULL', NULL, FALSE)
			->limit(1)
			->get()
			->row();
	}

	/**
	 * Purchase item rows.
	 *
	 * @param  int $purchase_id
	 * @return array
	 */
	public function purchase_items($purchase_id)
	{
		return $this->db
			->select('purchase_order_items.*, products.name AS product_name, products.sku AS product_sku, product_variants.sku AS variant_sku')
			->from('purchase_order_items')
			->join('products', 'products.id = purchase_order_items.product_id', 'left')
			->join('product_variants', 'product_variants.id = purchase_order_items.variant_id', 'left')
			->where('purchase_order_items.purchase_order_id', (int) $purchase_id)
			->where('purchase_order_items.deleted_at IS NULL', NULL, FALSE)
			->order_by('purchase_order_items.id', 'ASC')
			->get()
			->result();
	}

	/**
	 * Dropdown map for active products.
	 *
	 * @return array
	 */
	public function product_options()
	{
		$options = array();
		foreach ($this->db->select('id, name, sku')->from('products')->where('deleted_at IS NULL', NULL, FALSE)->where_in('status', array('active', 'draft'))->order_by('name', 'ASC')->get()->result() as $row)
		{
			$options[$row->id] = $row->name.' ('.$row->sku.')';
		}
		return $options;
	}

	/**
	 * Dropdown map for active variants.
	 *
	 * @return array
	 */
	public function variant_options()
	{
		$options = array(0 => 'No variant');
		foreach ($this->db->select('product_variants.id, product_variants.sku, product_variants.name, products.name AS product_name')->from('product_variants')->join('products', 'products.id = product_variants.product_id', 'left')->where('product_variants.deleted_at IS NULL', NULL, FALSE)->where_in('product_variants.status', array('active', 'draft'))->order_by('products.name', 'ASC')->get()->result() as $row)
		{
			$options[$row->id] = $row->product_name.' — '.$row->sku.($row->name ? ' ('.$row->name.')' : '');
		}
		return $options;
	}

	/**
	 * Active warehouse/supplier options.
	 *
	 * @param  string $table
	 * @return array
	 */
	public function simple_options($table)
	{
		$options = array();
		foreach ($this->db->select('id, name')->from($table)->where('deleted_at IS NULL', NULL, FALSE)->where('status', 'active')->order_by('name', 'ASC')->get()->result() as $row)
		{
			$options[$row->id] = $row->name;
		}
		return $options;
	}

	/**
	 * Roll product and variant stock totals from warehouse balances.
	 *
	 * @param  int $product_id
	 * @param  int $variant_id
	 * @return void
	 */
	public function rollup_stock($product_id, $variant_id = 0)
	{
		$product = $this->db->select_sum('quantity')->from('inventory')->where('product_id', (int) $product_id)->where('deleted_at IS NULL', NULL, FALSE)->get()->row();
		$this->db->where('id', (int) $product_id)->update('products', array(
			'stock_quantity' => $product ? (int) $product->quantity : 0,
			'updated_at' => date('Y-m-d H:i:s'),
		));

		if ((int) $variant_id > 0)
		{
			$variant = $this->db->select_sum('quantity')->from('inventory')->where(array('product_id' => (int) $product_id, 'variant_id' => (int) $variant_id))->where('deleted_at IS NULL', NULL, FALSE)->get()->row();
			$this->db->where('id', (int) $variant_id)->update('product_variants', array(
				'stock_quantity' => $variant ? (int) $variant->quantity : 0,
				'updated_at' => date('Y-m-d H:i:s'),
			));
		}
	}

	/**
	 * Build the stock query.
	 *
	 * @param  array $params
	 * @param  bool  $select
	 * @return void
	 */
	protected function stock_query(array $params, $select)
	{
		if ($select)
		{
			$this->db->select('inventory.*, (inventory.quantity - inventory.reserved_quantity) AS available_quantity, products.name AS product_name, products.sku AS product_sku, product_variants.sku AS variant_sku, warehouses.name AS warehouse_name', FALSE);
		}
		$this->db->from('inventory')
			->join('products', 'products.id = inventory.product_id', 'left')
			->join('product_variants', 'product_variants.id = inventory.variant_id', 'left')
			->join('warehouses', 'warehouses.id = inventory.warehouse_id', 'left')
			->where('inventory.deleted_at IS NULL', NULL, FALSE);

		if (array_get($params, 'warehouse_id'))
		{
			$this->db->where('inventory.warehouse_id', (int) array_get($params, 'warehouse_id'));
		}

		if (array_get($params, 'state') === 'low')
		{
			$this->db->where('inventory.quantity <= inventory.reorder_level', NULL, FALSE);
		}
		elseif (array_get($params, 'state') === 'out')
		{
			$this->db->where('inventory.quantity <=', 0);
		}

		$q = trim((string) array_get($params, 'q'));
		if ($q !== '')
		{
			$this->db->group_start()
				->like('products.name', $q)
				->or_like('products.sku', $q)
				->or_like('product_variants.sku', $q)
				->or_like('warehouses.name', $q)
				->group_end();
		}
	}

	/**
	 * Current balance row.
	 *
	 * @param  int $product_id
	 * @param  int $variant_id
	 * @param  int $warehouse_id
	 * @return object|null
	 */
	protected function stock_row($product_id, $variant_id, $warehouse_id)
	{
		return $this->db->from('inventory')->where(array(
			'product_id' => (int) $product_id,
			'variant_id' => (int) $variant_id,
			'warehouse_id' => (int) $warehouse_id,
		))->where('deleted_at IS NULL', NULL, FALSE)->limit(1)->get()->row();
	}

	/**
	 * @param int $product_id
	 * @return bool
	 */
	protected function active_product($product_id)
	{
		return (bool) $this->db->from('products')->where('id', (int) $product_id)->where('deleted_at IS NULL', NULL, FALSE)->count_all_results();
	}

	/**
	 * @param int $warehouse_id
	 * @return bool
	 */
	protected function active_warehouse($warehouse_id)
	{
		return (bool) $this->db->from('warehouses')->where('id', (int) $warehouse_id)->where('deleted_at IS NULL', NULL, FALSE)->count_all_results();
	}

	/**
	 * @param int $variant_id
	 * @param int $product_id
	 * @return bool
	 */
	protected function variant_belongs_to_product($variant_id, $product_id)
	{
		return (bool) $this->db->from('product_variants')->where(array('id' => (int) $variant_id, 'product_id' => (int) $product_id))->where('deleted_at IS NULL', NULL, FALSE)->count_all_results();
	}

	/**
	 * @param array $items
	 * @return array
	 */
	protected function normalise_purchase_items(array $items)
	{
		$clean = array();
		$product_ids = (array) array_get($items, 'product_id', array());
		foreach ($product_ids as $i => $product_id)
		{
			$product_id = (int) $product_id;
			$variant_id = (int) array_get(array_get($items, 'variant_id', array()), $i, 0);
			$quantity = (int) array_get(array_get($items, 'quantity', array()), $i, 0);
			$unit_cost = (float) array_get(array_get($items, 'unit_cost', array()), $i, 0);
			$tax_rate = (float) array_get(array_get($items, 'tax_rate', array()), $i, 0);
			$discount = (float) array_get(array_get($items, 'discount_amount', array()), $i, 0);
			if ($product_id <= 0 || $quantity <= 0) { continue; }
			$line = max(0, ($quantity * $unit_cost) - $discount);
			$tax = round($line * $tax_rate / 100, 2);
			$clean[] = array(
				'product_id' => $product_id,
				'variant_id' => $variant_id,
				'quantity' => $quantity,
				'received_quantity' => 0,
				'unit_cost' => $unit_cost,
				'tax_rate' => $tax_rate,
				'tax_amount' => $tax,
				'discount_amount' => $discount,
				'total' => $line + $tax,
			);
		}
		return $clean;
	}

	/**
	 * @param int $purchase_id
	 * @return string
	 */
	protected function purchase_receive_status($purchase_id)
	{
		$rows = $this->db->select('quantity, received_quantity')->from('purchase_order_items')->where('purchase_order_id', (int) $purchase_id)->where('deleted_at IS NULL', NULL, FALSE)->get()->result();
		$total = 0;
		$received = 0;
		foreach ($rows as $row)
		{
			$total += (int) $row->quantity;
			$received += (int) $row->received_quantity;
		}
		if ($received <= 0) { return 'pending'; }
		return $received >= $total ? 'received' : 'partial';
	}

	/**
	 * @return string
	 */
	protected function purchase_number()
	{
		return 'PO-'.date('Ymd').'-'.strtoupper(substr(generate_token(8), 0, 6));
	}

	/**
	 * @return int|null
	 */
	protected function current_user_id()
	{
		$id = $this->session->userdata('user_id');
		return $id ? (int) $id : NULL;
	}

	/**
	 * @return int
	 */
	protected function low_stock_count()
	{
		return (int) $this->db->from('inventory')->where('quantity <= reorder_level', NULL, FALSE)->where('deleted_at IS NULL', NULL, FALSE)->count_all_results();
	}

	/**
	 * @return int
	 */
	protected function out_of_stock_count()
	{
		return (int) $this->db->from('inventory')->where('quantity <=', 0)->where('deleted_at IS NULL', NULL, FALSE)->count_all_results();
	}

	/**
	 * @param string $message
	 * @return array
	 */
	protected function fail($message)
	{
		return array('success' => FALSE, 'message' => $message);
	}
}
