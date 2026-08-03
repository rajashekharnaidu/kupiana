<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin dashboard.
 *
 * Provides live sales, customer and inventory aggregates for the back office.
 *
 * @package Kupiana\Modules\Admin
 */
class Dashboard extends Admin_Controller
{
	/** @var string Highlights the sidebar entry. */
	protected $active_menu = 'dashboard';

	/** @var string */
	protected $required_permission = 'dashboard.view';

	/**
	 * Dashboard landing page.
	 *
	 * @return void
	 */
	public function index()
	{
		$this->breadcrumb('Dashboard');
		$kpis = isset($this->app_cache) ? $this->app_cache->remember('admin_dashboard_kpis_'.date('YmdHi'), 60, array($this, 'kpis')) : $this->kpis();
		$this->render('dashboard', array('page_title' => 'Dashboard', 'kpis' => $kpis));
	}

	public function kpis()
	{
		$order_count = (int) $this->db->from('orders')->where('deleted_at IS NULL', NULL, FALSE)->count_all_results();
		$revenue_row = $this->db->select_sum('total_amount')->from('orders')->where('order_status', 'delivered')->where('deleted_at IS NULL', NULL, FALSE)->get()->row();
		$today_row = $this->db->select_sum('total_amount')->from('orders')->where('order_status !=', 'cancelled')->where('DATE(created_at)', date('Y-m-d'), FALSE)->where('deleted_at IS NULL', NULL, FALSE)->get()->row();
		$customers = (int) $this->db->from('users')->where('user_type', 'customer')->where('deleted_at IS NULL', NULL, FALSE)->count_all_results();
		$low_stock = (int) $this->db->from('inventory')->where('quantity <=', (int) app_config('inventory.low_stock_threshold', 10))->where('deleted_at IS NULL', NULL, FALSE)->count_all_results();
		$status_counts = $this->db->select('order_status AS status, COUNT(*) AS total')->from('orders')->where('deleted_at IS NULL', NULL, FALSE)->group_by('order_status')->get()->result_array();

		return array(
			'revenue' => $revenue_row ? (float) $revenue_row->total_amount : 0,
			'today' => $today_row ? (float) $today_row->total_amount : 0,
			'orders' => $order_count, 'customers' => $customers, 'low_stock' => $low_stock,
			'status_counts' => $status_counts,
		);
	}

	/** Return revenue chart data through the standard JSON envelope. */
	public function chart_data()
	{
		$days = min(365, max(7, (int) $this->input->get('days', TRUE)));
		if (isset($this->app_cache))
		{
			$this->json($this->api_response->success($this->app_cache->remember('admin_dashboard_chart_'.$days.'_'.date('YmdHi'), 60, function () use ($days) {
				return $this->chart_payload($days);
			}), 'Dashboard chart data loaded.'));
			return;
		}
		$this->json($this->api_response->success($this->chart_payload($days), 'Dashboard chart data loaded.'));
	}

	protected function chart_payload($days)
	{
		$rows = $this->db->select('DATE(created_at) AS day, SUM(total_amount) AS total')->from('orders')->where('order_status !=', 'cancelled')->where('created_at >=', date('Y-m-d 00:00:00', strtotime('-'.($days - 1).' days')))->where('deleted_at IS NULL', NULL, FALSE)->group_by('DATE(created_at)')->order_by('day', 'ASC')->get()->result_array();
		$totals = array();
		foreach ($rows as $row) { $totals[$row['day']] = (float) $row['total']; }
		$labels = array(); $values = array();
		for ($i = $days - 1; $i >= 0; $i--)
		{
			$day = date('Y-m-d', strtotime('-'.$i.' days'));
			$labels[] = date('d M', strtotime($day));
			$values[] = isset($totals[$day]) ? $totals[$day] : 0;
		}
		return array('labels' => $labels, 'revenue' => $values);
	}
}
