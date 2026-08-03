<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Read-only admin reports backed by live aggregates.
 */
class Reports extends Admin_Controller
{
	protected $active_menu = 'reports.sales';
	protected $required_permission = 'reports.view';

	public function __construct()
	{
		parent::__construct();
		$this->load->model('Report_model', 'report_service');
	}

	/** Render one report by slug. */
	public function index($type = 'sales')
	{
		$type = $this->type($type);
		$filters = $this->report_service->filters($this->input->get(NULL, TRUE));
		$report = $this->report_service->aggregate($type, $filters);

		$this->active_menu = 'reports.'.str_replace('_', '-', $type);
		$this->data['active_menu'] = $this->active_menu;
		$this->breadcrumb('Reports');
		$this->breadcrumb($report['title']);
		$this->render('report_view', array(
			'page_title' => $report['title'],
			'report' => $report,
			'types' => $this->report_service->types(),
			'current_type' => $type,
			'filters' => $filters,
			'dashboard' => $this->report_service->dashboard($filters),
		));
	}

	/** Download the current report as CSV. */
	public function export($type = 'sales')
	{
		$type = $this->type($type);
		$filters = $this->report_service->filters($this->input->get(NULL, TRUE));
		$report = $this->report_service->aggregate($type, $filters);
		list($headers, $rows) = $this->report_service->csv_rows($report);
		$this->export->csv('kupiana-'.$type.'-'.$filters['from'].'-to-'.$filters['to'].'.csv', $headers, $rows);
	}

	protected function type($type)
	{
		$type = preg_replace('/[^a-z_]/', '', str_replace('-', '_', (string) $type));
		$types = $this->report_service->types();
		if ( ! isset($types[$type])) { show_404(); }
		return $type;
	}
}
