<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Grouped runtime settings editor.
 */
class Admin_settings extends Admin_Controller
{
	protected $active_menu = 'settings.general';
	protected $required_permission = 'settings.view';

	public function index($group = 'general')
	{
		$group = preg_replace('/[^a-z_]/', '', (string) $group);
		$allowed = array('general', 'shipping', 'payment', 'tax', 'inventory', 'catalog', 'seo', 'mail', 'security');
		if ( ! in_array($group, $allowed, TRUE)) { show_404(); }

		$this->active_menu = $this->menu_key($group);
		$this->data['active_menu'] = $this->active_menu;
		$rows = $this->setting_rows($group);

		if ($this->input->method(TRUE) === 'POST')
		{
			$this->require_permission('settings.manage');
			$values = array();
			foreach ($rows as $row)
			{
				$value = $this->input->post($row->setting_key, TRUE);
				if ($row->setting_type === 'bool') { $value = $value ? '1' : '0'; }
				$values[$row->setting_key] = $value;
			}

			if ($this->settings->set_many($values, $group))
			{
				$this->audit->log('settings_update', 'settings', NULL, 'Settings group updated.', array('group' => $group, 'keys' => array_keys($values)));
				$this->session->set_flashdata('success', ucwords($group).' settings saved.');
				redirect($this->group_uri($group));
			}

			$this->session->set_flashdata('error', 'Settings could not be saved.');
		}

		$this->breadcrumb('Settings');
		$this->breadcrumb(ucwords(str_replace('_', ' ', $group)));
		$this->render('settings_form', array(
			'page_title' => ucwords(str_replace('_', ' ', $group)).' Settings',
			'group' => $group,
			'rows' => $rows,
			'tabs' => array(
				'general' => 'General',
				'shipping' => 'Shipping',
				'payment' => 'Payment',
				'tax' => 'Tax & GST',
				'inventory' => 'Inventory',
				'catalog' => 'Catalog',
				'seo' => 'SEO',
				'mail' => 'Mail',
				'security' => 'Security',
			),
		));
	}

	protected function setting_rows($group)
	{
		return $this->db->from('settings')
			->where('setting_group', $group)
			->where('deleted_at IS NULL', NULL, FALSE)
			->order_by('id', 'ASC')
			->get()
			->result();
	}

	protected function menu_key($group)
	{
		$map = array('general' => 'settings.general', 'shipping' => 'settings.shipping', 'payment' => 'settings.payment', 'tax' => 'settings.tax');
		return isset($map[$group]) ? $map[$group] : 'settings.general';
	}

	protected function group_uri($group)
	{
		return $group === 'general' ? 'admin/settings' : 'admin/settings/'.$group;
	}
}
