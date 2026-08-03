<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Report_model
 *
 * Read-only aggregate queries for the admin insight pages. All methods return a
 * normalized pack that the shared report view and CSV export can consume.
 *
 * @package Kupiana\Models
 */
class Report_model extends CI_Model
{
	public function types()
	{
		return array(
			'sales' => 'Sales',
			'revenue' => 'Revenue',
			'gst' => 'GST',
			'payments' => 'Payments',
			'shipments' => 'Shipments',
			'returns' => 'Returns',
			'customers' => 'Customers',
			'inventory' => 'Inventory',
			'products' => 'Products',
			'suppliers' => 'Suppliers',
			'coupons' => 'Coupons',
			'profit' => 'Profit & Loss',
		);
	}

	public function filters(array $input)
	{
		$from = trim((string) array_get($input, 'from'));
		$to = trim((string) array_get($input, 'to'));
		if ($from === '') { $from = date('Y-m-d', strtotime('-29 days')); }
		if ($to === '') { $to = date('Y-m-d'); }
		if (strtotime($from) === FALSE) { $from = date('Y-m-d', strtotime('-29 days')); }
		if (strtotime($to) === FALSE) { $to = date('Y-m-d'); }
		if (strtotime($from) > strtotime($to))
		{
			$tmp = $from; $from = $to; $to = $tmp;
		}
		return array('from' => $from, 'to' => $to);
	}

	public function aggregate($type, array $filters)
	{
		$type = (string) $type;
		if ( ! isset($this->types()[$type]) || ! method_exists($this, $type)) { return NULL; }
		$key = 'report_'.$type.'_'.md5(json_encode($filters));
		if (isset($this->app_cache))
		{
			return $this->app_cache->remember($key, 120, function () use ($type, $filters) {
				return $this->{$type}($filters);
			});
		}
		return $this->{$type}($filters);
	}

	public function dashboard(array $filters)
	{
		if (isset($this->app_cache))
		{
			return $this->app_cache->remember('report_dashboard_'.md5(json_encode($filters)), 120, function () use ($filters) {
				return $this->dashboard_uncached($filters);
			});
		}
		return $this->dashboard_uncached($filters);
	}

	protected function dashboard_uncached(array $filters)
	{
		$orders = $this->orders_base($filters)->select('COUNT(*) AS records, COALESCE(SUM(total_amount), 0) AS revenue, COALESCE(SUM(refunded_amount), 0) AS refunds', FALSE)->get()->row();
		$payments = $this->date_range($this->db->from('payments')->where('deleted_at IS NULL', NULL, FALSE), 'created_at', $filters)
			->select('COUNT(*) AS records, COALESCE(SUM(CASE WHEN status = "captured" THEN amount ELSE 0 END), 0) AS captured', FALSE)->get()->row();
		$shipments = $this->date_range($this->db->from('shipments')->where('deleted_at IS NULL', NULL, FALSE), 'created_at', $filters)
			->select('COUNT(*) AS records, COALESCE(SUM(CASE WHEN shipment_status = "delivered" THEN 1 ELSE 0 END), 0) AS delivered', FALSE)->get()->row();
		$returns = $this->date_range($this->db->from('return_requests')->where('deleted_at IS NULL', NULL, FALSE), 'created_at', $filters)
			->select('COUNT(*) AS records, COALESCE(SUM(refund_amount), 0) AS amount', FALSE)->get()->row();

		return array(
			'orders' => $orders ? (int) $orders->records : 0,
			'revenue' => $orders ? (float) $orders->revenue : 0.0,
			'refunds' => $orders ? (float) $orders->refunds : 0.0,
			'captured_payments' => $payments ? (float) $payments->captured : 0.0,
			'shipments' => $shipments ? (int) $shipments->records : 0,
			'delivered_shipments' => $shipments ? (int) $shipments->delivered : 0,
			'returns' => $returns ? (int) $returns->records : 0,
			'return_amount' => $returns ? (float) $returns->amount : 0.0,
		);
	}

	public function sales(array $filters)
	{
		$rows = $this->orders_base($filters)
			->select('order_status AS label, COUNT(*) AS records, COALESCE(SUM(total_amount), 0) AS amount', FALSE)
			->group_by('order_status')->order_by('records', 'DESC')->get()->result_array();
		return $this->pack('Sales Report', 'Order volume and value by lifecycle status.', $rows, 'order_status', 'money');
	}

	public function revenue(array $filters)
	{
		$rows = $this->orders_base($filters)
			->select('DATE(created_at) AS label, COUNT(*) AS records, COALESCE(SUM(total_amount - refunded_amount), 0) AS amount', FALSE)
			->where('order_status !=', 'cancelled')
			->group_by('DATE(created_at)')->order_by('label', 'ASC')->get()->result_array();
		return $this->pack('Revenue Report', 'Daily net revenue after recorded refunds.', $rows, 'day', 'money');
	}

	public function gst(array $filters)
	{
		$rows = $this->orders_base($filters)
			->select('COALESCE(place_of_supply, "Unassigned") AS label, COUNT(*) AS records, COALESCE(SUM(tax_amount), 0) AS amount, COALESCE(SUM(cgst_amount), 0) AS cgst, COALESCE(SUM(sgst_amount), 0) AS sgst, COALESCE(SUM(igst_amount), 0) AS igst', FALSE)
			->group_by('place_of_supply')->order_by('amount', 'DESC')->get()->result_array();
		return $this->pack('GST Report', 'Tax collected by place of supply with CGST/SGST/IGST split.', $rows, 'place_of_supply', 'money', array('cgst' => 'CGST', 'sgst' => 'SGST', 'igst' => 'IGST'));
	}

	public function payments(array $filters)
	{
		$rows = $this->date_range($this->db->from('payments')->where('deleted_at IS NULL', NULL, FALSE), 'created_at', $filters)
			->select('CONCAT(gateway, " / ", COALESCE(method, "unknown"), " / ", status) AS label, COUNT(*) AS records, COALESCE(SUM(amount), 0) AS amount', FALSE)
			->group_by(array('gateway', 'method', 'status'))->order_by('amount', 'DESC')->get()->result_array();
		return $this->pack('Payments Report', 'Payment volume grouped by gateway, method and gateway state.', $rows, 'gateway_method_status', 'money');
	}

	public function shipments(array $filters)
	{
		$rows = $this->date_range($this->db->from('shipments')->where('deleted_at IS NULL', NULL, FALSE), 'created_at', $filters)
			->select('shipment_status AS label, COUNT(*) AS records, COALESCE(SUM(shipping_cost), 0) AS amount, COALESCE(AVG(weight), 0) AS avg_weight', FALSE)
			->group_by('shipment_status')->order_by('records', 'DESC')->get()->result_array();
		return $this->pack('Shipments Report', 'Shipment count, cost and average weight by delivery state.', $rows, 'shipment_status', 'money', array('avg_weight' => 'Avg Weight'));
	}

	public function returns(array $filters)
	{
		$rows = $this->date_range($this->db->from('return_requests')->where('deleted_at IS NULL', NULL, FALSE), 'created_at', $filters)
			->select('return_status AS label, COUNT(*) AS records, COALESCE(SUM(refund_amount), 0) AS amount', FALSE)
			->group_by('return_status')->order_by('records', 'DESC')->get()->result_array();
		return $this->pack('Returns Report', 'Return and exchange requests grouped by review state.', $rows, 'return_status', 'money');
	}

	public function customers(array $filters)
	{
		$rows = $this->date_range($this->db->from('users')->where('user_type', 'customer')->where('deleted_at IS NULL', NULL, FALSE), 'created_at', $filters)
			->select('status AS label, COUNT(*) AS records, 0 AS amount', FALSE)
			->group_by('status')->order_by('records', 'DESC')->get()->result_array();
		return $this->pack('Customers Report', 'New customer accounts grouped by lifecycle status.', $rows, 'status', 'number');
	}

	public function inventory(array $filters)
	{
		$rows = $this->db->select('warehouses.name AS label, COUNT(inventory.id) AS records, COALESCE(SUM(inventory.quantity - inventory.reserved_quantity), 0) AS amount, COALESCE(SUM(inventory.quantity), 0) AS on_hand, COALESCE(SUM(inventory.reserved_quantity), 0) AS reserved', FALSE)
			->from('inventory')->join('warehouses', 'warehouses.id = inventory.warehouse_id', 'left')
			->where('inventory.deleted_at IS NULL', NULL, FALSE)->group_by('inventory.warehouse_id')->order_by('amount', 'ASC')->get()->result_array();
		return $this->pack('Inventory Report', 'Available, on-hand and reserved stock by warehouse.', $rows, 'warehouse', 'number', array('on_hand' => 'On Hand', 'reserved' => 'Reserved'));
	}

	public function products(array $filters)
	{
		$rows = $this->date_range($this->db->select('products.name AS label, COALESCE(SUM(order_items.quantity), 0) AS records, COALESCE(SUM(order_items.total), 0) AS amount', FALSE)
			->from('products')->join('order_items', 'order_items.product_id = products.id AND order_items.deleted_at IS NULL', 'left')->join('orders', 'orders.id = order_items.order_id AND orders.deleted_at IS NULL', 'left')
			->where('products.deleted_at IS NULL', NULL, FALSE), 'orders.created_at', $filters)
			->group_by('products.id')->order_by('records', 'DESC')->limit(25)->get()->result_array();
		return $this->pack('Products Report', 'Top products by ordered quantity and sales value.', $rows, 'product', 'money');
	}

	public function suppliers(array $filters)
	{
		$rows = $this->date_range($this->db->select('suppliers.name AS label, COUNT(purchase_orders.id) AS records, COALESCE(SUM(purchase_orders.total_amount), 0) AS amount', FALSE)
			->from('suppliers')->join('purchase_orders', 'purchase_orders.supplier_id = suppliers.id AND purchase_orders.deleted_at IS NULL', 'left')
			->where('suppliers.deleted_at IS NULL', NULL, FALSE), 'purchase_orders.created_at', $filters)
			->group_by('suppliers.id')->order_by('amount', 'DESC')->get()->result_array();
		return $this->pack('Suppliers Report', 'Purchase value grouped by supplier.', $rows, 'supplier', 'money');
	}

	public function coupons(array $filters)
	{
		$rows = $this->date_range($this->db->select('COALESCE(coupon_code, "No Coupon") AS label, COUNT(*) AS records, COALESCE(SUM(discount_amount), 0) AS amount, COALESCE(SUM(total_amount), 0) AS gross_sales', FALSE)
			->from('orders')->where('deleted_at IS NULL', NULL, FALSE), 'created_at', $filters)
			->group_by('coupon_code')->order_by('amount', 'DESC')->get()->result_array();
		return $this->pack('Coupons Report', 'Discount usage and gross sales by coupon code.', $rows, 'coupon', 'money', array('gross_sales' => 'Gross Sales'));
	}

	public function profit(array $filters)
	{
		$revenue = $this->orders_base($filters)->select('COALESCE(SUM(total_amount), 0) AS value', FALSE)->where('order_status !=', 'cancelled')->get()->row();
		$refunds = $this->orders_base($filters)->select('COALESCE(SUM(refunded_amount), 0) AS value', FALSE)->get()->row();
		$cogs = $this->date_range($this->db->select('COALESCE(SUM(order_items.quantity * COALESCE(NULLIF(product_variants.cost_price, 0), products.cost_price, 0)), 0) AS value', FALSE)
			->from('order_items')->join('orders', 'orders.id = order_items.order_id')->join('products', 'products.id = order_items.product_id', 'left')->join('product_variants', 'product_variants.id = order_items.variant_id', 'left')
			->where('order_items.deleted_at IS NULL', NULL, FALSE)->where('orders.deleted_at IS NULL', NULL, FALSE)->where('orders.order_status !=', 'cancelled'), 'orders.created_at', $filters)->get()->row();
		$shipping = $this->date_range($this->db->select('COALESCE(SUM(shipping_cost), 0) AS value', FALSE)->from('shipments')->where('deleted_at IS NULL', NULL, FALSE), 'created_at', $filters)->get()->row();

		$gross = (float) ($revenue ? $revenue->value : 0);
		$refund = (float) ($refunds ? $refunds->value : 0);
		$cost = (float) ($cogs ? $cogs->value : 0);
		$ship = (float) ($shipping ? $shipping->value : 0);
		$rows = array(
			array('label' => 'Gross Revenue', 'records' => 1, 'amount' => $gross),
			array('label' => 'Refunds', 'records' => 1, 'amount' => -$refund),
			array('label' => 'Estimated COGS', 'records' => 1, 'amount' => -$cost),
			array('label' => 'Shipment Cost', 'records' => 1, 'amount' => -$ship),
			array('label' => 'Estimated Gross Profit', 'records' => 1, 'amount' => $gross - $refund - $cost - $ship),
		);
		return $this->pack('Profit & Loss Report', 'Estimated gross profit from sales, refunds, product cost and shipment cost.', $rows, 'metric', 'money');
	}

	public function csv_rows(array $report)
	{
		$headers = array_merge(array(ucwords(str_replace('_', ' ', $report['dimension'])), 'Records', $report['value_label']), array_values($report['extra_columns']));
		$rows = array();
		foreach ($report['rows'] as $row)
		{
			$line = array($row['label'], $row['records'], $row['amount']);
			foreach (array_keys($report['extra_columns']) as $key) { $line[] = isset($row[$key]) ? $row[$key] : 0; }
			$rows[] = $line;
		}
		return array($headers, $rows);
	}

	protected function orders_base(array $filters)
	{
		return $this->date_range($this->db->from('orders')->where('deleted_at IS NULL', NULL, FALSE), 'created_at', $filters);
	}

	protected function date_range($builder, $column, array $filters)
	{
		$builder->where($column.' >=', $filters['from'].' 00:00:00');
		$builder->where($column.' <=', $filters['to'].' 23:59:59');
		return $builder;
	}

	protected function pack($title, $description, array $rows, $dimension, $value_format = 'money', array $extra_columns = array())
	{
		$total_records = 0; $total_amount = 0; $extra_totals = array();
		foreach ($extra_columns as $key => $label) { $extra_totals[$key] = 0; }
		foreach ($rows as &$row)
		{
			$row['label'] = $row['label'] !== NULL && $row['label'] !== '' ? $row['label'] : 'Unassigned';
			$row['records'] = (int) $row['records'];
			$row['amount'] = (float) $row['amount'];
			$total_records += $row['records'];
			$total_amount += $row['amount'];
			foreach ($extra_columns as $key => $label)
			{
				$row[$key] = isset($row[$key]) ? (float) $row[$key] : 0.0;
				$extra_totals[$key] += $row[$key];
			}
		}
		return array('title' => $title, 'description' => $description, 'dimension' => $dimension, 'rows' => $rows, 'totals' => array('records' => $total_records, 'amount' => $total_amount, 'extra' => $extra_totals), 'value_format' => $value_format, 'value_label' => $value_format === 'money' ? 'Amount' : 'Units', 'extra_columns' => $extra_columns);
	}
}
