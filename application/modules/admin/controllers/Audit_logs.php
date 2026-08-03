<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Audit_logs extends Admin_Controller
{
	protected $active_menu = 'settings.audit';
	protected $required_permission = 'audit.view';

	public function index()
	{
		$params = $this->filters();
		$page = max(1, (int) $this->input->get('page', TRUE));
		$per_page = 25;
		$total = $this->query($params)->count_all_results();
		$rows = $this->query($params)->order_by('created_at', 'DESC')->limit($per_page, ($page - 1) * $per_page)->get()->result();
		$this->breadcrumb('Audit Logs');
		$this->render('audit_logs', array(
			'page_title' => 'Audit Logs',
			'rows' => $rows,
			'filters' => $params,
			'pagination' => array('page' => $page, 'per_page' => $per_page, 'total' => $total, 'total_pages' => (int) ceil($total / $per_page), 'from' => $total ? (($page - 1) * $per_page) + 1 : 0, 'to' => min($page * $per_page, $total)),
			'entities' => $this->distinct('entity'),
			'actions' => $this->distinct('action'),
		));
	}

	public function export()
	{
		$rows = array();
		foreach ($this->query($this->filters())->order_by('created_at', 'DESC')->limit(5000)->get()->result() as $row)
		{
			$rows[] = array($row->created_at, $row->user_name, $row->action, $row->entity, $row->entity_id, $row->ip_address, $row->description);
		}
		$this->export->csv('audit-logs.csv', array('Time', 'User', 'Action', 'Entity', 'Entity ID', 'IP', 'Description'), $rows);
	}

	protected function filters()
	{
		return array(
			'entity' => trim((string) $this->input->get('entity', TRUE)),
			'action' => trim((string) $this->input->get('action', TRUE)),
			'user' => trim((string) $this->input->get('user', TRUE)),
			'from' => trim((string) $this->input->get('from', TRUE)),
			'to' => trim((string) $this->input->get('to', TRUE)),
		);
	}

	protected function query(array $filters)
	{
		$this->db->from('audit_logs')->where('deleted_at IS NULL', NULL, FALSE);
		if ($filters['entity'] !== '') { $this->db->where('entity', $filters['entity']); }
		if ($filters['action'] !== '') { $this->db->where('action', $filters['action']); }
		if ($filters['user'] !== '') { $this->db->group_start()->like('user_name', $filters['user'])->or_like('user_id', $filters['user'])->group_end(); }
		if ($filters['from'] !== '') { $this->db->where('created_at >=', $filters['from'].' 00:00:00'); }
		if ($filters['to'] !== '') { $this->db->where('created_at <=', $filters['to'].' 23:59:59'); }
		return $this->db;
	}

	protected function distinct($column)
	{
		$values = array();
		foreach ($this->db->distinct()->select($column)->from('audit_logs')->where($column.' IS NOT NULL', NULL, FALSE)->order_by($column, 'ASC')->get()->result() as $row)
		{
			$values[] = $row->{$column};
		}
		return $values;
	}
}
