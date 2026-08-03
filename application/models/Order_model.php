<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Order_model
 *
 * Creates customer orders from carts and owns order lifecycle side effects:
 * stock reservation, fulfilment, cancellation, history and shipment rows.
 *
 * @package Kupiana\Models
 */
class Order_model extends CI_Model
{
	/**
	 * Create an order from the active cart.
	 *
	 * @param  array    $identity
	 * @param  int|null $user_id
	 * @param  array    $input
	 * @return array
	 */
	public function create_from_cart(array $identity, $user_id, array $input)
	{
		$this->load->model('Store_model', 'store');
		$cart = $this->store->cart($identity, FALSE);
		if ( ! $cart) { return $this->fail('Your cart is empty.'); }

		$items = $this->cart_items($cart->id);
		if (empty($items)) { return $this->fail('Your cart is empty.'); }

		foreach ($items as $item)
		{
			if ( ! $this->has_available_stock($item->product_id, $item->variant_id, $item->quantity))
			{
				return $this->fail($item->name.' does not have enough available stock.');
			}
		}

		$now = date('Y-m-d H:i:s');
		$shipping = $this->shipping_amount($items);
		$totals = $this->totals($items, $shipping, array_get($input, 'state_code'));
		$name = trim((string) array_get($input, 'first_name').' '.(string) array_get($input, 'last_name'));
		$address = $this->address_snapshot($input);

		$this->db->trans_begin();
		$this->db->insert('orders', array(
			'order_number' => generate_code('ORD'),
			'user_id' => $user_id ?: NULL,
			'customer_name' => $name,
			'customer_email' => strtolower(trim((string) array_get($input, 'email'))),
			'customer_phone' => array_get($input, 'phone'),
			'billing_address' => json_encode($address),
			'shipping_address' => json_encode($address),
			'subtotal' => $totals['subtotal'],
			'discount_amount' => 0,
			'coupon_id' => NULL,
			'coupon_code' => NULL,
			'tax_amount' => $totals['tax'],
			'cgst_amount' => $totals['cgst'],
			'sgst_amount' => $totals['sgst'],
			'igst_amount' => $totals['igst'],
			'shipping_amount' => $shipping,
			'total_amount' => $totals['total'],
			'paid_amount' => 0,
			'currency' => 'INR',
			'payment_method' => array_get($input, 'payment_method', 'cod'),
			'payment_status' => 'pending',
			'order_status' => 'pending',
			'place_of_supply' => array_get($input, 'state_code') ?: NULL,
			'customer_note' => array_get($input, 'customer_note') ?: NULL,
			'source' => 'web',
			'ip_address' => $this->input->ip_address(),
			'placed_at' => $now,
			'status' => 'active',
			'created_at' => $now,
			'updated_at' => $now,
			'created_by' => $user_id ?: NULL,
			'updated_by' => $user_id ?: NULL,
		));
		$order_id = (int) $this->db->insert_id();

		foreach ($items as $item)
		{
			$line = $this->line_totals($item);
			$this->db->insert('order_items', array(
				'order_id' => $order_id,
				'product_id' => (int) $item->product_id,
				'variant_id' => (int) $item->variant_id ?: NULL,
				'product_name' => $item->name,
				'variant_name' => $item->variant_name,
				'sku' => $item->variant_sku ?: $item->sku,
				'image' => $item->image_path,
				'hsn_code' => $item->hsn_code,
				'quantity' => (int) $item->quantity,
				'unit_price' => (float) $item->unit_price,
				'mrp' => (float) $item->mrp,
				'discount_amount' => 0,
				'tax_rate' => $line['tax_rate'],
				'tax_amount' => $line['tax'],
				'total' => $line['total'],
				'fulfilled_quantity' => 0,
				'returned_quantity' => 0,
				'status' => 'active',
				'created_at' => $now,
				'updated_at' => $now,
				'created_by' => $user_id ?: NULL,
				'updated_by' => $user_id ?: NULL,
			));
			$this->reserve_stock($item->product_id, $item->variant_id, $item->quantity);
			$this->db->where('id', (int) $item->product_id)->set('sold_count', 'sold_count + '.(int) $item->quantity, FALSE)->update('products');
		}

		$this->history($order_id, NULL, 'pending', 'Order placed from checkout.');
		$this->db->where('cart_id', (int) $cart->id)->where('deleted_at IS NULL', NULL, FALSE)->update('cart_items', array('deleted_at' => $now, 'updated_at' => $now));

		if ($this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback();
			return $this->fail('Order could not be placed. Please try again.');
		}

		$this->db->trans_commit();
		$order = $this->find($order_id);
		return array('success' => TRUE, 'order' => $order);
	}

	/**
	 * Update an order status and run lifecycle side effects.
	 *
	 * @param  int    $order_id
	 * @param  string $to_status
	 * @param  string $comment
	 * @return array
	 */
	public function update_status($order_id, $to_status, $comment = '')
	{
		$order = $this->find($order_id);
		if ( ! $order) { return $this->fail('Order not found.'); }
		if ($order->order_status === $to_status) { return array('success' => TRUE, 'message' => 'Order status already set.'); }

		$now = date('Y-m-d H:i:s');
		$user_id = $this->current_user_id();
		$data = array('order_status' => $to_status, 'updated_at' => $now, 'updated_by' => $user_id);
		if ($to_status === 'confirmed' && empty($order->confirmed_at)) { $data['confirmed_at'] = $now; }
		if (in_array($to_status, array('shipped', 'out_for_delivery'), TRUE) && empty($order->shipped_at)) { $data['shipped_at'] = $now; }
		if ($to_status === 'delivered' && empty($order->delivered_at)) { $data['delivered_at'] = $now; }
		if ($to_status === 'cancelled' && empty($order->cancelled_at)) { $data['cancelled_at'] = $now; }
		if ($to_status === 'cancelled' && $comment !== '') { $data['cancel_reason'] = $comment; }

		$this->db->trans_begin();
		if ($to_status === 'cancelled')
		{
			$this->release_order_stock($order->id);
		}
		if (in_array($to_status, array('packed', 'shipped', 'out_for_delivery', 'delivered'), TRUE))
		{
			$this->fulfil_order($order->id);
			$this->ensure_shipment($order, $to_status);
		}

		$this->db->where('id', $order->id)->update('orders', $data);
		$this->history($order->id, $order->order_status, $to_status, $comment);

		if ($this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback();
			return $this->fail('Order status could not be updated.');
		}

		$this->db->trans_commit();
		return array('success' => TRUE, 'message' => 'Order status updated.');
	}

	/**
	 * Fetch one active order.
	 *
	 * @param  int $id
	 * @return object|null
	 */
	public function find($id)
	{
		return $this->db->from('orders')->where('id', (int) $id)->where('deleted_at IS NULL', NULL, FALSE)->get()->row();
	}

	/** @param int $cart_id @return array */
	protected function cart_items($cart_id)
	{
		$rows = $this->db
			->select('cart_items.*, products.name, products.sku, products.mrp, products.hsn_code, products.tax_rate_id, products.manage_stock, products.allow_backorder, product_variants.sku AS variant_sku, product_variants.name AS variant_name, product_variants.mrp AS variant_mrp, tax_rates.rate AS tax_rate')
			->from('cart_items')
			->join('products', 'products.id = cart_items.product_id')
			->join('product_variants', 'product_variants.id = cart_items.variant_id', 'left')
			->join('tax_rates', 'tax_rates.id = products.tax_rate_id', 'left')
			->where('cart_items.cart_id', (int) $cart_id)
			->where('cart_items.deleted_at IS NULL', NULL, FALSE)
			->where('products.deleted_at IS NULL', NULL, FALSE)
			->get()
			->result();
		$this->load->model('Store_model', 'store');
		return $this->store->attach_images($rows);
	}

	/** @param array $items @param float $shipping @param string|null $state_code @return array */
	protected function totals(array $items, $shipping, $state_code = NULL)
	{
		$subtotal = 0; $tax = 0;
		foreach ($items as $item)
		{
			$line = $this->line_totals($item);
			$subtotal += $line['subtotal'];
			$tax += $line['tax'];
		}
		$origin = (string) array_get(app_config('tax', array()), 'origin_state_code', '29');
		$same_state = (string) $state_code === $origin || $state_code === NULL || $state_code === '';
		return array(
			'subtotal' => $subtotal,
			'tax' => $tax,
			'cgst' => $same_state ? round($tax / 2, 2) : 0,
			'sgst' => $same_state ? round($tax / 2, 2) : 0,
			'igst' => $same_state ? 0 : $tax,
			'total' => $subtotal + $tax + (float) $shipping,
		);
	}

	/** @param object $item @return array */
	protected function line_totals($item)
	{
		$subtotal = round((float) $item->unit_price * (int) $item->quantity, 2);
		$rate = $item->tax_rate !== NULL ? (float) $item->tax_rate : (float) array_get(app_config('tax', array()), 'default_rate', 0);
		$tax = round($subtotal * $rate / 100, 2);
		return array('subtotal' => $subtotal, 'tax_rate' => $rate, 'tax' => $tax, 'total' => $subtotal + $tax);
	}

	/** @param array $items @return float */
	protected function shipping_amount(array $items)
	{
		$subtotal = 0;
		foreach ($items as $item) { $subtotal += (float) $item->unit_price * (int) $item->quantity; }
		return $subtotal >= 999 ? 0.0 : 99.0;
	}

	/** @param array $input @return array */
	protected function address_snapshot(array $input)
	{
		return array(
			'name' => trim((string) array_get($input, 'first_name').' '.(string) array_get($input, 'last_name')),
			'phone' => array_get($input, 'phone'),
			'email' => array_get($input, 'email'),
			'address_line1' => array_get($input, 'address_line1'),
			'address_line2' => array_get($input, 'address_line2'),
			'city' => array_get($input, 'city'),
			'state' => array_get($input, 'state'),
			'state_code' => array_get($input, 'state_code'),
			'postal_code' => array_get($input, 'postal_code'),
			'country' => array_get($input, 'country', 'India'),
		);
	}

	/** @param int $product_id @param int $variant_id @param int $quantity @return bool */
	protected function has_available_stock($product_id, $variant_id, $quantity)
	{
		$row = $this->db->select('COALESCE(SUM(quantity - reserved_quantity), 0) AS available', FALSE)->from('inventory')->where('product_id', (int) $product_id)->where('variant_id', (int) $variant_id)->where('deleted_at IS NULL', NULL, FALSE)->get()->row();
		return $row && (int) $row->available >= (int) $quantity;
	}

	/** @param int $product_id @param int $variant_id @param int $quantity @return void */
	protected function reserve_stock($product_id, $variant_id, $quantity)
	{
		$row = $this->stock_row($product_id, $variant_id, $quantity);
		if ($row) { $this->db->where('id', $row->id)->set('reserved_quantity', 'reserved_quantity + '.(int) $quantity, FALSE)->update('inventory'); }
	}

	/** @param int $order_id @return void */
	protected function release_order_stock($order_id)
	{
		foreach ($this->db->from('order_items')->where('order_id', (int) $order_id)->where('deleted_at IS NULL', NULL, FALSE)->get()->result() as $item)
		{
			$unfulfilled = max(0, (int) $item->quantity - (int) $item->fulfilled_quantity);
			if ($unfulfilled <= 0) { continue; }
			$row = $this->stock_row($item->product_id, (int) $item->variant_id, $unfulfilled, FALSE);
			if ($row) { $this->db->where('id', $row->id)->set('reserved_quantity', 'GREATEST(reserved_quantity - '.$unfulfilled.', 0)', FALSE)->update('inventory'); }
		}
	}

	/** @param int $order_id @return void */
	protected function fulfil_order($order_id)
	{
		$this->load->model('Inventory_model', 'inventory_service');
		foreach ($this->db->from('order_items')->where('order_id', (int) $order_id)->where('deleted_at IS NULL', NULL, FALSE)->get()->result() as $item)
		{
			$pending = max(0, (int) $item->quantity - (int) $item->fulfilled_quantity);
			if ($pending <= 0) { continue; }
			$variant_id = (int) $item->variant_id;
			$row = $this->stock_row($item->product_id, $variant_id, $pending, FALSE);
			if ( ! $row) { continue; }
			$balance = max(0, (int) $row->quantity - $pending);
			$this->db->where('id', $row->id)->update('inventory', array(
				'quantity' => $balance,
				'reserved_quantity' => max(0, (int) $row->reserved_quantity - $pending),
				'updated_at' => date('Y-m-d H:i:s'),
				'updated_by' => $this->current_user_id(),
			));
			$this->db->insert('stock_movements', array(
				'product_id' => (int) $item->product_id,
				'variant_id' => $variant_id,
				'warehouse_id' => (int) $row->warehouse_id,
				'type' => 'sale',
				'quantity' => -$pending,
				'balance_after' => $balance,
				'unit_cost' => 0,
				'reference_type' => 'orders',
				'reference_id' => (int) $order_id,
				'notes' => 'Order fulfilment',
				'status' => 'active',
				'created_at' => date('Y-m-d H:i:s'),
				'updated_at' => date('Y-m-d H:i:s'),
				'created_by' => $this->current_user_id(),
				'updated_by' => $this->current_user_id(),
			));
			$this->db->where('id', (int) $item->id)->set('fulfilled_quantity', 'fulfilled_quantity + '.$pending, FALSE)->update('order_items');
			$this->inventory_service->rollup_stock($item->product_id, $variant_id);
		}
	}

	/** @param object $order @param string $status @return void */
	protected function ensure_shipment($order, $status)
	{
		$exists = $this->db->where('order_id', (int) $order->id)->where('deleted_at IS NULL', NULL, FALSE)->count_all_results('shipments');
		if ($exists) { return; }
		$warehouse = $this->db->select('id')->from('warehouses')->where('deleted_at IS NULL', NULL, FALSE)->order_by('is_default', 'DESC')->limit(1)->get()->row();
		$now = date('Y-m-d H:i:s');
		$this->db->insert('shipments', array(
			'shipment_number' => generate_code('SHP'),
			'order_id' => (int) $order->id,
			'warehouse_id' => $warehouse ? (int) $warehouse->id : NULL,
			'shipment_status' => $status === 'packed' ? 'packed' : 'in_transit',
			'shipped_at' => in_array($status, array('shipped', 'out_for_delivery', 'delivered'), TRUE) ? $now : NULL,
			'status' => 'active',
			'created_at' => $now,
			'updated_at' => $now,
			'created_by' => $this->current_user_id(),
			'updated_by' => $this->current_user_id(),
		));
	}

	/** @param int $product_id @param int $variant_id @param int $quantity @param bool $require_available @return object|null */
	protected function stock_row($product_id, $variant_id, $quantity, $require_available = TRUE)
	{
		$this->db->from('inventory')->where('product_id', (int) $product_id)->where('variant_id', (int) $variant_id)->where('deleted_at IS NULL', NULL, FALSE);
		if ($require_available) { $this->db->where('(quantity - reserved_quantity) >=', (int) $quantity, FALSE); }
		return $this->db->order_by('quantity - reserved_quantity', 'DESC', FALSE)->limit(1)->get()->row();
	}

	/** @param int $order_id @param string|null $from @param string $to @param string $comment @return void */
	protected function history($order_id, $from, $to, $comment = '')
	{
		$now = date('Y-m-d H:i:s');
		$this->db->insert('order_status_history', array('order_id' => (int) $order_id, 'from_status' => $from, 'to_status' => $to, 'comment' => $comment ?: NULL, 'notified' => 0, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now, 'created_by' => $this->current_user_id(), 'updated_by' => $this->current_user_id()));
	}

	/** @return int|null */
	protected function current_user_id()
	{
		$id = $this->session->userdata('user_id');
		return $id ? (int) $id : NULL;
	}

	/** @param string $message @return array */
	protected function fail($message)
	{
		return array('success' => FALSE, 'message' => $message);
	}
}
