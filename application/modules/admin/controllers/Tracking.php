<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin delivery tracking dashboard.
 */
class Tracking extends Admin_Controller
{
	protected $active_menu = 'orders.tracking';
	protected $required_permission = 'shipping.view';

	public function __construct()
	{
		parent::__construct();
		$this->load->model('Tracking_model', 'tracking_service');
	}

	public function index()
	{
		$params = array(
			'q' => $this->input->get('q', TRUE),
			'status' => $this->input->get('status', TRUE),
			'page' => $this->input->get('page', TRUE),
			'per_page' => $this->input->get('per_page', TRUE),
		);
		$this->breadcrumb('Delivery Tracking');
		$this->render('tracking_index', array(
			'page_title' => 'Delivery Tracking',
			'stats' => $this->tracking_service->stats(),
			'pagination' => $this->tracking_service->shipments_paginate($params),
			'statuses' => $this->tracking_service->shipment_statuses(),
			'filters' => $params,
		));
	}

	public function view($id = NULL)
	{
		$shipment = $this->tracking_service->shipment((int) $id);
		if ( ! $shipment) { show_404(); }
		$this->breadcrumb('Delivery Tracking', 'admin/tracking');
		$this->breadcrumb($shipment->shipment_number);
		$this->render('tracking_view', array(
			'page_title' => 'Shipment '.$shipment->shipment_number,
			'shipment' => $shipment,
			'events' => $this->tracking_service->tracking_events($shipment->id),
			'timeline' => $this->tracking_service->order_timeline($shipment->order_id),
			'statuses' => $this->tracking_service->shipment_statuses(),
		));
	}

	public function update($id = NULL)
	{
		$this->require_permission('shipping.manage');
		$result = $this->tracking_service->assign_shipment((int) $id, $this->input->post(NULL, TRUE));
		if (isset($this->audit)) { $this->audit->log($result['success'] ? 'shipment_updated' : 'shipment_update_failed', 'shipments', (int) $id, $result['message']); }
		$this->session->set_flashdata($result['success'] ? 'success' : 'error', $result['message']);
		redirect('admin/tracking/view/'.(int) $id);
	}

	public function event($id = NULL)
	{
		$this->require_permission('shipping.manage');
		$result = $this->tracking_service->add_event((int) $id, $this->input->post(NULL, TRUE));
		if (isset($this->audit)) { $this->audit->log($result['success'] ? 'shipment_tracking_create' : 'shipment_tracking_failed', 'shipments', (int) $id, $result['message']); }
		$this->session->set_flashdata($result['success'] ? 'success' : 'error', $result['message']);
		redirect('admin/tracking/view/'.(int) $id);
	}
}
