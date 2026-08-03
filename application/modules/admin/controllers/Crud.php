<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Shared, permission-gated CRUD controller for admin resources.
 */
class Crud extends Admin_Controller
{
	/** @var array Current resource definition. */
	protected $resource = array();
	/** @var Admin_resource_model */
	protected $model;
	/** @var string */
	protected $resource_key = '';

	public function __construct()
	{
		$parts = explode('/', trim((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));
		$this->resource_key = strtolower(isset($parts[1]) ? $parts[1] : '');
		$nested = array(
			'payments/logs' => 'payment-logs',
			'payments/razorpay' => 'payments',
			'inventory/stock-in' => 'stock-movements',
			'inventory/stock-out' => 'stock-movements',
			'inventory/adjustments' => 'stock-adjustments',
			'inventory/low-stock' => 'low-stock',
			'settings/tax' => 'tax-rates',
			'templates/email' => 'email-templates',
			'templates/sms' => 'sms-templates'
		);
		$pair = (isset($parts[1]) ? $parts[1] : '').'/'.(isset($parts[2]) ? $parts[2] : '');
		if (isset($nested[$pair])) { $this->resource_key = $nested[$pair]; }
		$config = array();
		include APPPATH.'config/admin_resources.php';
		$resources = isset($config['admin_resources']) ? (array) $config['admin_resources'] : array();
		$this->resource = isset($resources[$this->resource_key]) ? $resources[$this->resource_key] : array();
		$this->required_permission = isset($this->resource['permission']) ? $this->resource['permission'] : NULL;
		$this->active_menu = $this->menu_key($this->resource_key);
		parent::__construct();

		if (empty($this->resource)) { show_404(); }
		$this->load->model('Admin_resource_model', 'resource_model');
		$this->model = $this->resource_model->configure($this->resource['table']);
	}

	/** List, search, sort and filter records. */
	public function index()
	{
		$params = $this->list_params(array('status'));
		$params['where'] = isset($this->resource['fixed_filters']) ? $this->resource['fixed_filters'] : array();
		$pagination = $this->model->paginate($params);
		$this->breadcrumb($this->resource['label']);
		$this->render('resource_list', array(
			'page_title' => $this->resource['label'],
			'resource' => $this->resource,
			'resource_key' => $this->resource_key,
			'columns' => $this->display_columns(),
			'display_maps' => $this->display_maps($this->display_columns()),
			'pagination' => $pagination,
		));
	}

	/** Create form and persist a new record. */
	public function create()
	{
		$this->save();
	}

	/** Edit form and persist an existing record. */
	public function edit($id = NULL)
	{
		$row = $this->model->find((int) $id);
		if ( ! $row) { show_404(); }
		$this->save($row, (int) $id);
	}

	/** Soft-delete one record. */
	public function delete($id = NULL)
	{
		$this->require_manage();
		$id = (int) $id;
		$row = $this->model->find($id);
		if ($row && $this->model->delete($id))
		{
			$this->audit->deleted($this->resource['table'], $id, (array) $row);
			$this->session->set_flashdata('success', $this->resource['label'].' record deleted.');
		}
		redirect('admin/'.$this->resource_key);
	}

	/** Apply a bulk status or soft-delete action. */
	public function bulk()
	{
		$this->require_manage();
		$ids = (array) $this->input->post('ids', TRUE);
		$action = (string) $this->input->post('bulk_action', TRUE);
		$count = 0;
		if ($action === 'delete') { $count = $this->model->delete_many($ids); }
		elseif ($action !== '') { $count = $this->model->set_status_many($ids, $action); }
		$this->audit->log('bulk_'.$action, $this->resource['table'], NULL, 'Bulk action applied.', array('ids' => $ids));
		$this->session->set_flashdata('success', $count.' record(s) updated.');
		redirect('admin/'.$this->resource_key);
	}

	/** Export the current filtered result set as CSV. */
	public function export()
	{
		$params = $this->list_params(array('status'));
		$params['where'] = isset($this->resource['fixed_filters']) ? $this->resource['fixed_filters'] : array();
		$params['limit'] = 5000;
		$columns = $this->display_columns();
		$rows = array();
		foreach ($this->model->all($params) as $record)
		{
			$line = array();
			foreach ($columns as $column) { $line[] = isset($record->{$column->name}) ? $record->{$column->name} : ''; }
			$rows[] = $line;
		}
		$headers = array_map(array($this, 'column_label'), $columns);
		$this->audit->log('export', $this->resource['table'], NULL, 'Admin resource exported.');
		$this->export->csv($this->resource_key.'.csv', $headers, $rows);
	}

	/** @return void */
	protected function save($existing = NULL, $id = NULL)
	{
		$this->require_manage();
		$is_update = $id !== NULL;
		if ($this->input->method(TRUE) === 'POST')
		{
			$input = (array) $this->input->post(NULL, TRUE);
			$data = array();
			foreach ($this->model->fillable_columns() as $field)
			{
				if (array_key_exists($field, $input)) { $data[$field] = is_array($input[$field]) ? json_encode($input[$field]) : $input[$field]; }
			}
			$this->merge_uploads($data);
			$this->normalise_data($data);
			$this->generated_defaults($data);
			$rules = $this->validation_rules($data, $existing);
			$this->form_validation->set_rules($rules);
			if ($this->form_validation->run() === TRUE)
			{
				$written = $is_update ? $this->model->update($id, $data) : $this->model->insert($data);
				if ($written !== FALSE)
				{
					$record_id = $is_update ? $id : $written;
					if ($is_update) { $this->audit->updated($this->resource['table'], $record_id, (array) $existing, $data); }
					else { $this->audit->created($this->resource['table'], $record_id, $data); }
					$this->session->set_flashdata('success', $this->resource['label'].' saved successfully.');
					redirect('admin/'.$this->resource_key);
				}
				$this->session->set_flashdata('error', 'The record could not be saved. Check related IDs and required fields.');
			}
		}
		$this->breadcrumb($this->resource['label'], 'admin/'.$this->resource_key);
		$this->breadcrumb($is_update ? 'Edit' : 'Create');
		$this->render('resource_form', array(
			'page_title' => ($is_update ? 'Edit ' : 'Create ').$this->resource['label'],
			'resource' => $this->resource,
			'resource_key' => $this->resource_key,
			'columns' => $this->form_columns(),
			'record' => $existing,
			'field_options' => $this->field_options($this->form_columns()),
			'errors' => validation_errors('<div>', '</div>'),
		));
	}

	/** @return void */
	protected function require_manage()
	{
		$permission = str_replace('.view', '.manage', $this->resource['permission']);
		if ($permission === $this->resource['permission']) { $permission = $this->resource['permission']; }
		$this->require_permission($permission);
	}

	/** @return array */
	protected function display_columns()
	{
		$columns = array();
		foreach ($this->model->columns() as $column)
		{
			if (in_array($column->name, array('deleted_at', 'created_by', 'updated_by', 'password'), TRUE)) { continue; }
			$columns[] = $column;
			if (count($columns) >= 6) { break; }
		}
		return $columns;
	}

	/** @return array */
	protected function form_columns()
	{
		$columns = array();
		foreach ($this->model->columns() as $column)
		{
			if (in_array($column->name, array('id', 'uuid', 'created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by', 'password', 'remember_token', 'last_login_ip'), TRUE)) { continue; }
			$columns[] = $column;
		}
		return $columns;
	}

	/** @return array */
	protected function validation_rules(array $data, $existing = NULL)
	{
		$rules = array();
		foreach ($this->form_columns() as $column)
		{
			if ($this->is_upload_field($column->name) && (isset($data[$column->name]) || ($existing && ! empty($existing->{$column->name})))) { continue; }
			if ( ! empty($column->name) && $column->name !== 'status' && $column->null === 'NO' && $column->default === NULL && ! in_array($column->name, array('description', 'content', 'body', 'notes', 'message'), TRUE))
			{
				$rules[] = array('field' => $column->name, 'label' => $this->column_label($column), 'rules' => 'required');
			}
		}
		return $rules;
	}

	/** @param object $column @return string */
	public function column_label($column)
	{
		return ucwords(str_replace('_', ' ', $column->name));
	}

	/** @return string */
	protected function menu_key($resource_key)
	{
		$map = array(
			'products' => 'catalog.products', 'categories' => 'catalog.categories', 'brands' => 'catalog.brands',
			'attributes' => 'catalog.attributes', 'variants' => 'catalog.variants', 'tags' => 'catalog.tags', 'reviews' => 'catalog.reviews',
			'orders' => 'orders.all', 'shipments' => 'orders.shipping', 'tracking' => 'orders.tracking', 'returns' => 'orders.returns',
			'refunds' => 'orders.refunds', 'cancellations' => 'orders.cancellations', 'invoices' => 'orders.invoices',
			'payments' => 'payments.all', 'payment-logs' => 'payments.logs', 'coupons' => 'promotions.coupons', 'offers' => 'promotions.offers',
			'inventory' => 'inventory.stock', 'stock-movements' => 'inventory.stock_in', 'stock-adjustments' => 'inventory.adjustments', 'low-stock' => 'inventory.low_stock',
			'warehouses' => 'inventory.warehouses', 'purchases' => 'purchasing.orders', 'suppliers' => 'purchasing.suppliers',
			'customers' => 'customers', 'users' => 'access.users', 'roles' => 'access.roles', 'permissions' => 'access.permissions',
			'pages' => 'cms.pages', 'banners' => 'cms.banners', 'blog' => 'cms.blog', 'testimonials' => 'cms.testimonials',
			'faqs' => 'cms.faqs', 'contacts' => 'cms.contacts', 'newsletter' => 'cms.newsletter', 'seo' => 'seo.meta',
			'notifications' => 'notifications.all', 'email-templates' => 'notifications.email', 'sms-templates' => 'notifications.sms',
			'settings' => 'settings.general', 'tax-rates' => 'settings.tax', 'backups' => 'settings.backup', 'audit-logs' => 'settings.audit',
		);
		return isset($map[$resource_key]) ? $map[$resource_key] : $resource_key;
	}

	/** @return array */
	protected function field_options(array $columns)
	{
		$options = array();
		foreach ($columns as $column)
		{
			if ($this->is_boolean_field($column))
			{
				$options[$column->name] = array('type' => 'boolean');
			}
			elseif ($this->is_upload_field($column->name))
			{
				$options[$column->name] = array('type' => 'image');
			}
			elseif ($this->relation_for($column->name))
			{
				$options[$column->name] = array('type' => 'relation', 'options' => $this->relation_options($column->name));
			}
			elseif ($enum = $this->enum_options($column->type))
			{
				$options[$column->name] = array('type' => 'select', 'options' => $enum);
			}
		}
		return $options;
	}

	/** @return array */
	protected function display_maps(array $columns)
	{
		$maps = array();
		foreach ($columns as $column)
		{
			if ($this->relation_for($column->name))
			{
				$maps[$column->name] = $this->relation_options($column->name);
			}
		}
		return $maps;
	}

	/** @return array|null */
	protected function relation_for($field)
	{
		$relations = isset($this->resource['relations']) ? (array) $this->resource['relations'] : array();
		return isset($relations[$field]) ? (array) $relations[$field] : NULL;
	}

	/** @return array */
	protected function relation_options($field)
	{
		$relation = $this->relation_for($field);
		if (empty($relation['table']) || empty($relation['label']) || ! $this->db->table_exists($relation['table'])) { return array(); }
		$key = isset($relation['key']) ? $relation['key'] : 'id';
		$label = $relation['label'];
		if ( ! $this->db->field_exists($key, $relation['table']) || ! $this->db->field_exists($label, $relation['table'])) { return array(); }
		$select = $key.', '.$label;
		$this->db->select($select)->from($relation['table'])->limit(250);
		if ($this->db->field_exists('deleted_at', $relation['table'])) { $this->db->where('deleted_at IS NULL', NULL, FALSE); }
		if ($this->db->field_exists('status', $relation['table'])) { $this->db->where_in('status', array('active', 'draft')); }
		$this->db->order_by($label, 'ASC');
		$options = array();
		foreach ($this->db->get()->result() as $row)
		{
			$options[$row->{$key}] = $row->{$label};
		}
		return $options;
	}

	/** @return array */
	protected function enum_options($type)
	{
		if (strpos($type, 'enum(') !== 0) { return array(); }
		preg_match_all("/'([^']+)'/", $type, $matches);
		$options = array();
		foreach ($matches[1] as $value) { $options[$value] = ucwords(str_replace('_', ' ', $value)); }
		return $options;
	}

	/** @return bool */
	protected function is_boolean_field($column)
	{
		return strpos(strtolower($column->type), 'tinyint') !== FALSE && (int) $column->max_length === 1;
	}

	/** @return bool */
	protected function is_upload_field($field)
	{
		return in_array($field, array('image', 'banner', 'logo', 'avatar', 'featured_image', 'mobile_image', 'og_image'), TRUE);
	}

	/** @return void */
	protected function merge_uploads(array &$data)
	{
		include_once APPPATH.'libraries/Upload.php';
		$directory = isset($this->resource['upload_dir']) ? $this->resource['upload_dir'] : 'imports';
		$uploader = new Upload();
		foreach ($this->form_columns() as $column)
		{
			if ( ! $this->is_upload_field($column->name)) { continue; }
			$upload = $uploader->image($column->name, $directory);
			if ($upload !== FALSE) { $data[$column->name] = $upload['name']; }
		}
	}

	/** @return void */
	protected function normalise_data(array &$data)
	{
		foreach ($this->model->columns() as $column)
		{
			if ( ! array_key_exists($column->name, $data) || $data[$column->name] !== '') { continue; }
			if ($column->null === 'YES') { $data[$column->name] = NULL; }
		}
	}

	/** @return void */
	protected function generated_defaults(array &$data)
	{
		foreach ($this->model->columns() as $column)
		{
			if ($column->name === 'uuid' && empty($data['uuid'])) { $data['uuid'] = $this->uuid(); }
		}
	}

	/** @return string */
	protected function uuid()
	{
		$data = random_bytes(16);
		$data[6] = chr(ord($data[6]) & 0x0f | 0x40);
		$data[8] = chr(ord($data[8]) & 0x3f | 0x80);
		return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
	}
}
