<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tracking_model
 *
 * Owns shipment timelines, courier metadata and return logistics without adding
 * schema beyond the Phase 2 fulfilment tables.
 *
 * @package Kupiana\Models
 */
class Tracking_model extends CI_Model
{
	public function stats()
	{
		return array(
			'shipments' => (int) $this->db->from('shipments')->where('deleted_at IS NULL', NULL, FALSE)->count_all_results(),
			'in_transit' => (int) $this->db->from('shipments')->where_in('shipment_status', array('picked_up', 'in_transit', 'out_for_delivery'))->where('deleted_at IS NULL', NULL, FALSE)->count_all_results(),
			'delivered' => (int) $this->db->from('shipments')->where('shipment_status', 'delivered')->where('deleted_at IS NULL', NULL, FALSE)->count_all_results(),
			'returns' => (int) $this->db->from('return_requests')->where_not_in('return_status', array('completed', 'cancelled', 'rejected'))->where('deleted_at IS NULL', NULL, FALSE)->count_all_results(),
		);
	}

	public function shipments_paginate(array $params)
	{
		$page = max(1, (int) array_get($params, 'page', 1));
		$per_page = max(1, min(100, (int) array_get($params, 'per_page', 25)));
		$offset = ($page - 1) * $per_page;

		$this->shipment_query($params, FALSE);
		$total = (int) $this->db->count_all_results();
		$this->shipment_query($params, TRUE);
		$rows = $this->db->order_by('shipments.updated_at', 'DESC')->limit($per_page, $offset)->get()->result();

		return array('data' => $rows, 'total' => $total, 'page' => $page, 'per_page' => $per_page, 'total_pages' => (int) ceil($total / $per_page), 'from' => $total ? $offset + 1 : 0, 'to' => min($total, $offset + $per_page));
	}

	public function shipment($id)
	{
		return $this->db
			->select('shipments.*, orders.order_number, orders.customer_name, orders.customer_email, orders.customer_phone, orders.order_status, orders.payment_status, warehouses.name AS warehouse_name')
			->from('shipments')
			->join('orders', 'orders.id = shipments.order_id')
			->join('warehouses', 'warehouses.id = shipments.warehouse_id', 'left')
			->where('shipments.id', (int) $id)
			->where('shipments.deleted_at IS NULL', NULL, FALSE)
			->get()
			->row();
	}

	public function tracking_events($shipment_id)
	{
		return $this->db->from('shipment_tracking')->where('shipment_id', (int) $shipment_id)->where('deleted_at IS NULL', NULL, FALSE)->order_by('occurred_at', 'DESC')->order_by('id', 'DESC')->get()->result();
	}

	public function order_timeline($order_id)
	{
		$timeline = array();
		$history = $this->db->from('order_status_history')->where('order_id', (int) $order_id)->where('deleted_at IS NULL', NULL, FALSE)->get()->result();
		foreach ($history as $row)
		{
			$timeline[] = (object) array('kind' => 'order', 'title' => ucwords(str_replace('_', ' ', $row->to_status)), 'status' => $row->to_status, 'location' => NULL, 'description' => $row->comment, 'occurred_at' => $row->created_at, 'shipment_id' => NULL);
		}
		$shipments = $this->db->select('id')->from('shipments')->where('order_id', (int) $order_id)->where('deleted_at IS NULL', NULL, FALSE)->get()->result();
		if ($shipments)
		{
			$ids = array_map(function ($shipment) { return (int) $shipment->id; }, $shipments);
			$events = $this->db->from('shipment_tracking')->where_in('shipment_id', $ids)->where('deleted_at IS NULL', NULL, FALSE)->get()->result();
			foreach ($events as $row)
			{
				$timeline[] = (object) array('kind' => 'shipment', 'title' => $row->status_text, 'status' => NULL, 'location' => $row->location, 'description' => $row->description, 'occurred_at' => $row->occurred_at, 'shipment_id' => $row->shipment_id);
			}
		}
		usort($timeline, function ($a, $b) {
			$time = strcmp((string) $b->occurred_at, (string) $a->occurred_at);
			return $time !== 0 ? $time : strcmp((string) $b->kind, (string) $a->kind);
		});
		return $timeline;
	}

	public function assign_shipment($shipment_id, array $data)
	{
		$shipment = $this->shipment($shipment_id);
		if ( ! $shipment) { return $this->fail('Shipment not found.'); }
		$now = date('Y-m-d H:i:s');
		$this->db->where('id', (int) $shipment_id)->update('shipments', array(
			'courier_name' => trim((string) array_get($data, 'courier_name')) ?: NULL,
			'courier_code' => trim((string) array_get($data, 'courier_code')) ?: NULL,
			'tracking_number' => trim((string) array_get($data, 'tracking_number')) ?: NULL,
			'tracking_url' => trim((string) array_get($data, 'tracking_url')) ?: NULL,
			'weight' => (float) array_get($data, 'weight', 0),
			'shipping_cost' => (float) array_get($data, 'shipping_cost', 0),
			'estimated_delivery' => array_get($data, 'estimated_delivery') ?: NULL,
			'updated_at' => $now,
			'updated_by' => $this->current_user_id(),
		));
		return array('success' => TRUE, 'message' => 'Shipment details updated.');
	}

	public function add_event($shipment_id, array $data)
	{
		$shipment = $this->shipment($shipment_id);
		if ( ! $shipment) { return $this->fail('Shipment not found.'); }

		$status_text = trim((string) array_get($data, 'status_text'));
		if ($status_text === '') { return $this->fail('Tracking status is required.'); }
		$shipment_status = trim((string) array_get($data, 'shipment_status'));
		if ($shipment_status !== '' && ! isset($this->shipment_statuses()[$shipment_status])) { return $this->fail('Choose a valid shipment status.'); }

		$now = date('Y-m-d H:i:s');
		$occurred = array_get($data, 'occurred_at') ? date('Y-m-d H:i:s', strtotime((string) array_get($data, 'occurred_at'))) : $now;

		$this->db->trans_begin();
		$this->db->insert('shipment_tracking', array(
			'shipment_id' => (int) $shipment_id,
			'status_text' => $status_text,
			'location' => trim((string) array_get($data, 'location')) ?: NULL,
			'description' => trim((string) array_get($data, 'description')) ?: NULL,
			'occurred_at' => $occurred,
			'status' => 'active',
			'created_at' => $now,
			'updated_at' => $now,
			'created_by' => $this->current_user_id(),
			'updated_by' => $this->current_user_id(),
		));

		if ($shipment_status !== '')
		{
			$this->apply_shipment_status($shipment, $shipment_status, $status_text, $occurred);
		}

		if ($this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback();
			return $this->fail('Tracking event could not be saved.');
		}

		$this->db->trans_commit();
		return array('success' => TRUE, 'message' => 'Tracking event added.');
	}

	public function create_return_request($order_id, $user_id, array $input)
	{
		$order = $this->db->from('orders')->where(array('id' => (int) $order_id, 'user_id' => (int) $user_id))->where('deleted_at IS NULL', NULL, FALSE)->get()->row();
		if ( ! $order) { return $this->fail('Order not found.'); }
		if ($order->order_status !== 'delivered') { return $this->fail('Returns can be requested after delivery.'); }
		$existing = (int) $this->db->from('return_requests')->where('order_id', (int) $order_id)->where_not_in('return_status', array('cancelled', 'rejected'))->where('deleted_at IS NULL', NULL, FALSE)->count_all_results();
		if ($existing > 0) { return $this->fail('A return or exchange request already exists for this order.'); }

		$selected = (array) array_get($input, 'items', array());
		$items = $this->db->from('order_items')->where('order_id', (int) $order_id)->where('deleted_at IS NULL', NULL, FALSE)->get()->result();
		$clean = array();
		$total = 0;
		foreach ($items as $item)
		{
			$qty = max(0, (int) array_get($selected, $item->id, 0));
			$available = max(0, (int) $item->fulfilled_quantity - (int) $item->returned_quantity);
			if ($qty <= 0) { continue; }
			if ($qty > $available) { return $this->fail('Return quantity cannot exceed delivered quantity.'); }
			$amount = round(((float) $item->total / max(1, (int) $item->quantity)) * $qty, 2);
			$total += $amount;
			$clean[] = array('item' => $item, 'quantity' => $qty, 'refund_amount' => $amount);
		}
		if (empty($clean)) { return $this->fail('Choose at least one delivered item to return.'); }

		$now = date('Y-m-d H:i:s');
		$this->db->trans_begin();
		$this->db->insert('return_requests', array(
			'return_number' => generate_code('RET'),
			'order_id' => (int) $order_id,
			'user_id' => (int) $user_id,
			'type' => array_get($input, 'type') === 'exchange' ? 'exchange' : 'return',
			'reason' => trim((string) array_get($input, 'reason')),
			'description' => trim((string) array_get($input, 'description')) ?: NULL,
			'pickup_address' => $order->shipping_address,
			'refund_amount' => $total,
			'return_status' => 'requested',
			'status' => 'active',
			'created_at' => $now,
			'updated_at' => $now,
			'created_by' => (int) $user_id,
			'updated_by' => (int) $user_id,
		));
		$return_id = (int) $this->db->insert_id();
		foreach ($clean as $row)
		{
			$this->db->insert('return_items', array(
				'return_request_id' => $return_id,
				'order_item_id' => (int) $row['item']->id,
				'quantity' => (int) $row['quantity'],
				'reason' => trim((string) array_get($input, 'reason')),
				'refund_amount' => $row['refund_amount'],
				'status' => 'active',
				'created_at' => $now,
				'updated_at' => $now,
				'created_by' => (int) $user_id,
				'updated_by' => (int) $user_id,
			));
		}

		if ($this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback();
			return $this->fail('Return request could not be created.');
		}
		$this->db->trans_commit();
		return array('success' => TRUE, 'message' => 'Return request created.', 'return_id' => $return_id);
	}

	public function returns_for_user($user_id)
	{
		return $this->db->select('return_requests.*, orders.order_number')->from('return_requests')->join('orders', 'orders.id = return_requests.order_id')->where('return_requests.user_id', (int) $user_id)->where('return_requests.deleted_at IS NULL', NULL, FALSE)->order_by('return_requests.created_at', 'DESC')->get()->result();
	}

	public function return_request($id)
	{
		return $this->db->select('return_requests.*, orders.order_number, orders.customer_name, orders.customer_email, orders.customer_phone')->from('return_requests')->join('orders', 'orders.id = return_requests.order_id')->where('return_requests.id', (int) $id)->where('return_requests.deleted_at IS NULL', NULL, FALSE)->get()->row();
	}

	public function return_items($return_id)
	{
		return $this->db->select('return_items.*, order_items.product_name, order_items.variant_name, order_items.sku, order_items.quantity AS ordered_quantity')->from('return_items')->join('order_items', 'order_items.id = return_items.order_item_id')->where('return_items.return_request_id', (int) $return_id)->where('return_items.deleted_at IS NULL', NULL, FALSE)->get()->result();
	}

	public function update_return_status($return_id, $to_status, $note = '', $restock = FALSE)
	{
		$return = $this->return_request($return_id);
		if ( ! $return) { return $this->fail('Return request not found.'); }
		if ( ! isset($this->return_statuses()[$to_status])) { return $this->fail('Choose a valid return status.'); }
		$now = date('Y-m-d H:i:s');
		$data = array('return_status' => $to_status, 'updated_at' => $now, 'updated_by' => $this->current_user_id());
		if ($to_status === 'approved' && empty($return->approved_at)) { $data['approved_at'] = $now; }
		if ($to_status === 'rejected') { $data['rejected_reason'] = $note ?: NULL; }
		if ($to_status === 'completed' && empty($return->completed_at)) { $data['completed_at'] = $now; }

		$this->db->trans_begin();
		$this->db->where('id', (int) $return_id)->update('return_requests', $data);
		if (in_array($to_status, array('received', 'completed'), TRUE) && $restock)
		{
			$this->restock_return_items($return);
		}
		if ($to_status === 'completed')
		{
			$this->db->where('id', (int) $return->order_id)->update('orders', array('order_status' => 'returned', 'updated_at' => $now, 'updated_by' => $this->current_user_id()));
			$this->history($return->order_id, NULL, 'returned', $note ?: 'Return completed.');
		}

		if ($this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback();
			return $this->fail('Return status could not be updated.');
		}
		$this->db->trans_commit();
		return array('success' => TRUE, 'message' => 'Return status updated.');
	}

	public function shipment_statuses()
	{
		return app_config('shipment_statuses', array());
	}

	public function return_statuses()
	{
		return app_config('return_statuses', array());
	}

	protected function shipment_query(array $params, $select)
	{
		if ($select)
		{
			$this->db->select('shipments.*, orders.order_number, orders.customer_name, orders.customer_phone, warehouses.name AS warehouse_name');
		}
		$this->db->from('shipments')->join('orders', 'orders.id = shipments.order_id')->join('warehouses', 'warehouses.id = shipments.warehouse_id', 'left')->where('shipments.deleted_at IS NULL', NULL, FALSE);
		$q = trim((string) array_get($params, 'q'));
		if ($q !== '')
		{
			$this->db->group_start()->like('shipments.shipment_number', $q)->or_like('shipments.tracking_number', $q)->or_like('shipments.courier_name', $q)->or_like('orders.order_number', $q)->or_like('orders.customer_name', $q)->group_end();
		}
		$status = trim((string) array_get($params, 'status'));
		if ($status !== '') { $this->db->where('shipments.shipment_status', $status); }
	}

	protected function apply_shipment_status($shipment, $shipment_status, $status_text, $occurred_at)
	{
		$now = date('Y-m-d H:i:s');
		$data = array('shipment_status' => $shipment_status, 'updated_at' => $now, 'updated_by' => $this->current_user_id());
		if (in_array($shipment_status, array('picked_up', 'in_transit', 'out_for_delivery', 'delivered'), TRUE) && empty($shipment->shipped_at)) { $data['shipped_at'] = $occurred_at; }
		if ($shipment_status === 'delivered') { $data['delivered_at'] = $occurred_at; }
		$this->db->where('id', (int) $shipment->id)->update('shipments', $data);

		$order_status = NULL;
		if (in_array($shipment_status, array('picked_up', 'in_transit'), TRUE)) { $order_status = 'shipped'; }
		if ($shipment_status === 'out_for_delivery') { $order_status = 'out_for_delivery'; }
		if ($shipment_status === 'delivered') { $order_status = 'delivered'; }
		if ($shipment_status === 'returned') { $order_status = 'returned'; }
		if ($order_status && $shipment->order_status !== $order_status)
		{
			$order_data = array('order_status' => $order_status, 'updated_at' => $now, 'updated_by' => $this->current_user_id());
			if (in_array($order_status, array('shipped', 'out_for_delivery'), TRUE)) { $order_data['shipped_at'] = $occurred_at; }
			if ($order_status === 'delivered') { $order_data['delivered_at'] = $occurred_at; }
			$this->db->where('id', (int) $shipment->order_id)->update('orders', $order_data);
			$this->history($shipment->order_id, $shipment->order_status, $order_status, $status_text);
		}
	}

	protected function restock_return_items($return)
	{
		$this->load->model('Inventory_model', 'inventory_service');
		$warehouse = $this->db->select('warehouse_id')->from('shipments')->where('order_id', (int) $return->order_id)->where('deleted_at IS NULL', NULL, FALSE)->order_by('id', 'ASC')->limit(1)->get()->row();
		if ( ! $warehouse || ! $warehouse->warehouse_id)
		{
			$warehouse = $this->db->select('id AS warehouse_id')->from('warehouses')->where('deleted_at IS NULL', NULL, FALSE)->order_by('is_default', 'DESC')->limit(1)->get()->row();
		}
		if ( ! $warehouse) { return; }
		$items = $this->db->select('return_items.*, order_items.product_id, order_items.variant_id')->from('return_items')->join('order_items', 'order_items.id = return_items.order_item_id')->where('return_items.return_request_id', (int) $return->id)->where('return_items.restocked', 0)->where('return_items.deleted_at IS NULL', NULL, FALSE)->get()->result();
		foreach ($items as $item)
		{
			$this->inventory_service->record_movement(array('product_id' => $item->product_id, 'variant_id' => (int) $item->variant_id, 'warehouse_id' => (int) $warehouse->warehouse_id, 'quantity' => (int) $item->quantity, 'type' => 'return', 'reference_type' => 'return_requests', 'reference_id' => (int) $return->id, 'notes' => 'Return restock'));
			$this->db->where('id', (int) $item->id)->update('return_items', array('restocked' => 1, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => $this->current_user_id()));
			$this->db->where('id', (int) $item->order_item_id)->set('returned_quantity', 'returned_quantity + '.(int) $item->quantity, FALSE)->update('order_items');
		}
	}

	protected function history($order_id, $from, $to, $comment = '')
	{
		$now = date('Y-m-d H:i:s');
		$this->db->insert('order_status_history', array('order_id' => (int) $order_id, 'from_status' => $from, 'to_status' => $to, 'comment' => $comment ?: NULL, 'notified' => 0, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now, 'created_by' => $this->current_user_id(), 'updated_by' => $this->current_user_id()));
	}

	protected function current_user_id()
	{
		$id = $this->session->userdata('user_id');
		return $id ? (int) $id : NULL;
	}

	protected function fail($message)
	{
		return array('success' => FALSE, 'message' => $message);
	}
}
