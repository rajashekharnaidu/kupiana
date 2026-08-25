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

		// Enrich column metadata with NULL constraint info from INFORMATION_SCHEMA
		$db_config = config_item('database') ? config_item('database')[0] : array();
		$db_name = isset($db_config['database']) ? $db_config['database'] : 'kupiana';

		$schema_info = $this->db
			->select('COLUMN_NAME, IS_NULLABLE, COLUMN_TYPE')
			->from('INFORMATION_SCHEMA.COLUMNS')
			->where('TABLE_SCHEMA', $db_name)
			->where('TABLE_NAME', $this->table)
			->get()
			->result_array();

		$schema_map = array();
		foreach ($schema_info as $col) {
			$schema_map[$col['COLUMN_NAME']] = array(
				'null' => $col['IS_NULLABLE'],
				'column_type' => $col['COLUMN_TYPE']
			);
		}

		foreach ($this->columns as $column)
		{
			$name = $column->name;
			$names[] = $name;

			// Add null and column_type from schema
			if (isset($schema_map[$name])) {
				$column->null = $schema_map[$name]['null'];
				$column->column_type = $schema_map[$name]['column_type'];
			}
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
		$this->status_column = ($this->table === 'payments' && in_array('status_flag', $names, TRUE)) ? 'status_flag' : 'status';
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
