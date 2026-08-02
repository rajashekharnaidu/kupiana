<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Configurable model used by the admin resource screens.
 *
 * Resource metadata stays in the controller; all persistence still goes
 * through MY_Model so soft deletes, whitelists and audit stamps are shared.
 */
class Admin_resource_model extends MY_Model
{
	/** @var array Database column metadata. */
	protected $columns = array();

	/**
	 * Configure this instance for one schema table.
	 *
	 * @param string $table
	 * @return $this
	 */
	public function configure($table)
	{
		$this->table = preg_replace('/[^a-z0-9_]/i', '', (string) $table);
		$this->columns = $this->db->field_data($this->table);
		$names = array();

		foreach ($this->columns as $column)
		{
			$name = $column->name;
			$names[] = $name;
		}

		$system = array('id', 'created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by');
		$this->fillable = array_values(array_diff($names, $system));
		$this->searchable = array_values(array_intersect($names, array(
			'name', 'title', 'label', 'email', 'sku', 'code', 'slug', 'number',
			'order_number', 'phone', 'subject', 'question', 'message', 'description'
		)));
		$this->sortable = array_values(array_diff($names, array('deleted_at')));
		$this->filterable = in_array('status', $names, TRUE) ? array('status' => 'status') : array();
		$this->hidden = in_array('password', $names, TRUE) ? array('password') : array();
		return $this;
	}

	/** @return array */
	public function columns()
	{
		return $this->columns;
	}

	/** @return string[] */
	public function fillable_columns()
	{
		return $this->fillable;
	}
}
