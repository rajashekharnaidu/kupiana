<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin return logistics screens.
 */
class Returns extends Admin_Controller
{
	protected $active_menu = 'orders.returns';
	protected $required_permission = 'returns.view';

	public function __construct()
	{
		parent::__construct();
		$this->load->model('Tracking_model', 'tracking_service');
	}

	public function view($id = NULL)
	{
		$return = $this->tracking_service->return_request((int) $id);
		if ( ! $return) { show_404(); }
		$this->breadcrumb('Return Requests', 'admin/returns');
		$this->breadcrumb($return->return_number);
		$this->render('return_view', array(
			'page_title' => 'Return '.$return->return_number,
			'return' => $return,
			'items' => $this->tracking_service->return_items($return->id),
			'statuses' => $this->tracking_service->return_statuses(),
		));
	}

	public function status($id = NULL)
	{
		$this->require_permission('returns.manage');
		$result = $this->tracking_service->update_return_status((int) $id, (string) $this->input->post('return_status', TRUE), trim((string) $this->input->post('note', TRUE)), (bool) $this->input->post('restock', TRUE));
		if (isset($this->audit)) { $this->audit->log($result['success'] ? 'return_status_updated' : 'return_status_failed', 'return_requests', (int) $id, $result['message']); }
		$this->session->set_flashdata($result['success'] ? 'success' : 'error', $result['message']);
		redirect('admin/returns/view/'.(int) $id);
	}
}
