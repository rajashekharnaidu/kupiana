<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * MY_Model
 *
 * Base model for every table in Kupiana. Provides CRUD, soft deletes, audit
 * stamping, searching, sorting, filtering, pagination and bulk actions so that
 * concrete models stay declarative.
 *
 * Typical subclass:
 *
 *     class Product_model extends MY_Model
 *     {
 *         protected $table      = 'products';
 *         protected $fillable   = array('name', 'slug', 'price', 'status');
 *         protected $searchable = array('name', 'sku');
 *         protected $sortable   = array('name', 'price', 'created_at');
 *     }
 *
 * @package Kupiana\Core
 */
class MY_Model extends CI_Model
{
	/** @var string Table name. Must be set by the subclass. */
	protected $table = '';

	/** @var string Primary key column. */
	protected $primary_key = 'id';

	/**
	 * Columns that may be mass-assigned. An empty array means "allow all",
	 * which should only be used for internal/system tables.
	 *
	 * @var string[]
	 */
	protected $fillable = array();

	/** @var string[] Columns stripped from every write, whatever the caller sends. */
	protected $guarded = array('id');

	/** @var string[] Columns removed from read results (e.g. password hashes). */
	protected $hidden = array();

	/** @var bool Whether the table has a deleted_at column. */
	protected $soft_delete = TRUE;

	/** @var bool Whether the table has created_at / updated_at columns. */
	protected $timestamps = TRUE;

	/** @var bool Whether the table has created_by / updated_by columns. */
	protected $audit = TRUE;

	/** @var string[] Columns scanned by search(). */
	protected $searchable = array();

	/** @var string[] Whitelist of columns that may be sorted on. */
	protected $sortable = array();

	/**
	 * Filter map: request key => column (or array(column, operator)).
	 * Anything not listed here is ignored, which keeps filtering injection-safe.
	 *
	 * @var array
	 */
	protected $filterable = array();

	/** @var string Default sort column. */
	protected $default_sort = 'id';

	/** @var string Default sort direction. */
	protected $default_order = 'DESC';

	/** @var string Column holding the record status. */
	protected $status_column = 'status';

	/** @var bool When TRUE the next query includes soft-deleted rows. */
	protected $with_trashed = FALSE;

	/** @var bool When TRUE the next query returns ONLY soft-deleted rows. */
	protected $only_trashed = FALSE;

	/** @var string Result format: 'object' or 'array'. */
	protected $return_type = 'object';

	public function __construct()
	{
		parent::__construct();
	}

	// ------------------------------------------------------------------
	// Scopes
	// ------------------------------------------------------------------

	/**
	 * Include soft-deleted rows in the next query.
	 *
	 * @return $this
	 */
	public function with_trashed()
	{
		$this->with_trashed = TRUE;
		return $this;
	}

	/**
	 * Restrict the next query to soft-deleted rows only.
	 *
	 * @return $this
	 */
	public function only_trashed()
	{
		$this->only_trashed = TRUE;
		return $this;
	}

	/**
	 * Return rows as associative arrays instead of objects.
	 *
	 * @return $this
	 */
	public function as_array()
	{
		$this->return_type = 'array';
		return $this;
	}

	/**
	 * Apply the soft-delete scope to the current query builder state.
	 *
	 * @return void
	 */
	protected function apply_soft_delete_scope()
	{
		if ( ! $this->soft_delete)
		{
			return;
		}

		if ($this->only_trashed)
		{
			$this->db->where($this->table.'.deleted_at IS NOT NULL', NULL, FALSE);
		}
		elseif ( ! $this->with_trashed)
		{
			$this->db->where($this->table.'.deleted_at IS NULL', NULL, FALSE);
		}
	}

	/**
	 * Reset per-query scopes. Called after every terminal query so that scopes
	 * never leak between calls on the same shared model instance.
	 *
	 * @return void
	 */
	protected function reset_scopes()
	{
		$this->with_trashed = FALSE;
		$this->only_trashed = FALSE;
		$this->return_type  = 'object';
	}

	// ------------------------------------------------------------------
	// Reads
	// ------------------------------------------------------------------

	/**
	 * Fetch a single row by primary key.
	 *
	 * @param  int   $id
	 * @param  array $select Optional column list.
	 * @return object|array|null
	 */
	public function find($id, $select = array())
	{
		if ( ! empty($select))
		{
			$this->db->select($select);
		}

		$this->db->from($this->table)->where($this->table.'.'.$this->primary_key, $id);
		$this->apply_soft_delete_scope();

		$row = $this->db->get()->row();
		$as_array = ($this->return_type === 'array');
		$this->reset_scopes();

		if ($row === NULL)
		{
			return NULL;
		}

		$row = $this->strip_hidden($row);

		return $as_array ? (array) $row : $row;
	}

	/**
	 * Fetch the first row matching a set of conditions.
	 *
	 * @param  array $where
	 * @return object|array|null
	 */
	public function find_by(array $where)
	{
		$this->db->from($this->table)->where($where)->limit(1);
		$this->apply_soft_delete_scope();

		$row = $this->db->get()->row();
		$as_array = ($this->return_type === 'array');
		$this->reset_scopes();

		if ($row === NULL)
		{
			return NULL;
		}

		$row = $this->strip_hidden($row);

		return $as_array ? (array) $row : $row;
	}

	/**
	 * Fetch every matching row.
	 *
	 * @param  array $params Supports: where, search, filters, sort, order, limit, offset, select.
	 * @return array
	 */
	public function all(array $params = array())
	{
		$this->build_query($params);

		$rows = $this->db->get()->result();
		$as_array = ($this->return_type === 'array');
		$this->reset_scopes();

		$rows = array_map(array($this, 'strip_hidden'), $rows);

		return $as_array ? array_map(function ($row) { return (array) $row; }, $rows) : $rows;
	}

	/**
	 * Count rows matching the given params (ignores limit/offset).
	 *
	 * @param  array $params
	 * @return int
	 */
	public function count_all(array $params = array())
	{
		unset($params['limit'], $params['offset'], $params['sort'], $params['select']);

		$this->build_query($params, FALSE);

		$count = $this->db->count_all_results();
		$this->reset_scopes();

		return (int) $count;
	}

	/**
	 * Paginated listing for admin tables and storefront grids.
	 *
	 * @param  array $params Adds: page, per_page. Everything all() supports also works.
	 * @return array {data, total, page, per_page, total_pages, from, to}
	 */
	public function paginate(array $params = array())
	{
		$page     = max(1, (int) $this->value($params, 'page', 1));
		$per_page = (int) $this->value($params, 'per_page', 15);
		$per_page = ($per_page > 0 && $per_page <= 200) ? $per_page : 15;

		// count_all() consumes the scopes, so remember them for the data query.
		$with_trashed = $this->with_trashed;
		$only_trashed = $this->only_trashed;
		$return_type  = $this->return_type;

		$total = $this->count_all($params);

		$this->with_trashed = $with_trashed;
		$this->only_trashed = $only_trashed;
		$this->return_type  = $return_type;

		$params['limit']  = $per_page;
		$params['offset'] = ($page - 1) * $per_page;

		$rows        = $this->all($params);
		$total_pages = $per_page > 0 ? (int) ceil($total / $per_page) : 0;

		return array(
			'data'        => $rows,
			'total'       => $total,
			'page'        => $page,
			'per_page'    => $per_page,
			'total_pages' => $total_pages,
			'from'        => $total ? (($page - 1) * $per_page) + 1 : 0,
			'to'          => min($page * $per_page, $total),
		);
	}

	/**
	 * Key/value list, handy for <select> dropdowns.
	 *
	 * @param  string $value_column
	 * @param  string $key_column
	 * @param  array  $params
	 * @return array
	 */
	public function dropdown($value_column, $key_column = NULL, array $params = array())
	{
		$key_column      = $key_column ?: $this->primary_key;
		$params['select'] = array($key_column, $value_column);
		$params['sort']   = in_array($value_column, $this->sortable, TRUE) ? $value_column : NULL;

		$options = array();

		foreach ($this->all($params) as $row)
		{
			$options[$row->{$key_column}] = $row->{$value_column};
		}

		return $options;
	}

	/**
	 * Check whether a value already exists, optionally ignoring one row.
	 * Used for unique validation on slugs, SKUs and emails.
	 *
	 * @param  string   $column
	 * @param  mixed    $value
	 * @param  int|null $ignore_id
	 * @return bool
	 */
	public function exists($column, $value, $ignore_id = NULL)
	{
		$this->db->from($this->table)->where($column, $value);

		if ($ignore_id !== NULL)
		{
			$this->db->where($this->primary_key.' !=', $ignore_id);
		}

		$this->apply_soft_delete_scope();

		$count = $this->db->count_all_results();
		$this->reset_scopes();

		return $count > 0;
	}

	// ------------------------------------------------------------------
	// Writes
	// ------------------------------------------------------------------

	/**
	 * Insert a row and return its new id.
	 *
	 * @param  array $data
	 * @return int|false
	 */
	public function insert(array $data)
	{
		$data = $this->filter_columns($data);

		if ($this->timestamps)
		{
			$now = date('Y-m-d H:i:s');
			$data['created_at'] = $now;
			$data['updated_at'] = $now;
		}

		if ($this->audit)
		{
			$actor = $this->current_user_id();
			$data['created_by'] = $actor;
			$data['updated_by'] = $actor;
		}

		if ( ! $this->db->insert($this->table, $data))
		{
			return FALSE;
		}

		return (int) $this->db->insert_id();
	}

	/**
	 * Insert many rows in one statement. Timestamps/audit are applied to each.
	 *
	 * @param  array $rows
	 * @return bool
	 */
	public function insert_many(array $rows)
	{
		if (empty($rows))
		{
			return TRUE;
		}

		$now   = date('Y-m-d H:i:s');
		$actor = $this->current_user_id();

		$prepared = array();

		foreach ($rows as $row)
		{
			$row = $this->filter_columns($row);

			if ($this->timestamps)
			{
				$row['created_at'] = $now;
				$row['updated_at'] = $now;
			}

			if ($this->audit)
			{
				$row['created_by'] = $actor;
				$row['updated_by'] = $actor;
			}

			$prepared[] = $row;
		}

		return (bool) $this->db->insert_batch($this->table, $prepared);
	}

	/**
	 * Update a row by primary key.
	 *
	 * @param  int   $id
	 * @param  array $data
	 * @return bool
	 */
	public function update($id, array $data)
	{
		$data = $this->filter_columns($data);

		if (empty($data))
		{
			return TRUE;
		}

		if ($this->timestamps)
		{
			$data['updated_at'] = date('Y-m-d H:i:s');
		}

		if ($this->audit)
		{
			$data['updated_by'] = $this->current_user_id();
		}

		return (bool) $this->db
			->where($this->primary_key, $id)
			->update($this->table, $data);
	}

	/**
	 * Update every row matching a condition set.
	 *
	 * @param  array $where
	 * @param  array $data
	 * @return bool
	 */
	public function update_where(array $where, array $data)
	{
		$data = $this->filter_columns($data);

		if (empty($data))
		{
			return TRUE;
		}

		if ($this->timestamps)
		{
			$data['updated_at'] = date('Y-m-d H:i:s');
		}

		if ($this->audit)
		{
			$data['updated_by'] = $this->current_user_id();
		}

		return (bool) $this->db->where($where)->update($this->table, $data);
	}

	/**
	 * Soft delete when the table supports it, hard delete otherwise.
	 *
	 * @param  int $id
	 * @return bool
	 */
	public function delete($id)
	{
		if ( ! $this->soft_delete)
		{
			return $this->force_delete($id);
		}

		$data = array('deleted_at' => date('Y-m-d H:i:s'));

		if ($this->audit)
		{
			$data['updated_by'] = $this->current_user_id();
		}

		return (bool) $this->db
			->where($this->primary_key, $id)
			->update($this->table, $data);
	}

	/**
	 * Permanently remove a row. Reserved for pivot/system tables.
	 *
	 * @param  int $id
	 * @return bool
	 */
	public function force_delete($id)
	{
		return (bool) $this->db->delete($this->table, array($this->primary_key => $id));
	}

	/**
	 * Undo a soft delete.
	 *
	 * @param  int $id
	 * @return bool
	 */
	public function restore($id)
	{
		if ( ! $this->soft_delete)
		{
			return FALSE;
		}

		return (bool) $this->db
			->where($this->primary_key, $id)
			->update($this->table, array('deleted_at' => NULL));
	}

	/**
	 * Bulk soft delete, used by admin table bulk actions.
	 *
	 * @param  int[] $ids
	 * @return int Number of affected rows.
	 */
	public function delete_many(array $ids)
	{
		$ids = array_filter(array_map('intval', $ids));

		if (empty($ids))
		{
			return 0;
		}

		if ( ! $this->soft_delete)
		{
			$this->db->where_in($this->primary_key, $ids)->delete($this->table);
			return (int) $this->db->affected_rows();
		}

		$this->db
			->where_in($this->primary_key, $ids)
			->update($this->table, array('deleted_at' => date('Y-m-d H:i:s')));

		return (int) $this->db->affected_rows();
	}

	/**
	 * Bulk status change, used by admin table bulk actions.
	 *
	 * @param  int[]  $ids
	 * @param  string $status
	 * @return int Number of affected rows.
	 */
	public function set_status_many(array $ids, $status)
	{
		$ids = array_filter(array_map('intval', $ids));

		if (empty($ids))
		{
			return 0;
		}

		$data = array($this->status_column => $status);

		if ($this->timestamps)
		{
			$data['updated_at'] = date('Y-m-d H:i:s');
		}

		if ($this->audit)
		{
			$data['updated_by'] = $this->current_user_id();
		}

		$this->db->where_in($this->primary_key, $ids)->update($this->table, $data);

		return (int) $this->db->affected_rows();
	}

	// ------------------------------------------------------------------
	// Transactions
	// ------------------------------------------------------------------

	/** @return void */
	public function begin()
	{
		$this->db->trans_begin();
	}

	/** @return bool TRUE when committed, FALSE when rolled back. */
	public function commit()
	{
		if ($this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback();
			return FALSE;
		}

		$this->db->trans_commit();
		return TRUE;
	}

	/** @return void */
	public function rollback()
	{
		$this->db->trans_rollback();
	}

	// ------------------------------------------------------------------
	// Query building
	// ------------------------------------------------------------------

	/**
	 * Translate a params array into query-builder state.
	 *
	 * @param  array $params
	 * @param  bool  $apply_select Skip select/limit when counting.
	 * @return void
	 */
	protected function build_query(array $params, $apply_select = TRUE)
	{
		if ($apply_select && ! empty($params['select']))
		{
			$this->db->select($params['select']);
		}

		$this->db->from($this->table);

		// Extension point for joins in subclasses.
		$this->apply_joins($params);

		if ( ! empty($params['where']) && is_array($params['where']))
		{
			$this->db->where($params['where']);
		}

		if ( ! empty($params['where_in']) && is_array($params['where_in']))
		{
			foreach ($params['where_in'] as $column => $values)
			{
				$this->db->where_in($column, (array) $values);
			}
		}

		$this->apply_filters($this->value($params, 'filters', array()));
		$this->apply_search($this->value($params, 'search', ''));
		$this->apply_soft_delete_scope();

		if ($apply_select)
		{
			$this->apply_sort(
				$this->value($params, 'sort'),
				$this->value($params, 'order')
			);

			if (isset($params['limit']))
			{
				$this->db->limit((int) $params['limit'], (int) $this->value($params, 'offset', 0));
			}
		}
	}

	/**
	 * Hook for subclasses that need joins in listings. Default is a no-op.
	 *
	 * @param  array $params
	 * @return void
	 */
	protected function apply_joins(array $params)
	{
		// Intentionally empty. Override in subclasses.
	}

	/**
	 * LIKE search across the $searchable columns.
	 *
	 * @param  string $term
	 * @return void
	 */
	protected function apply_search($term)
	{
		$term = trim((string) $term);

		if ($term === '' || empty($this->searchable))
		{
			return;
		}

		$this->db->group_start();

		foreach ($this->searchable as $index => $column)
		{
			$column = (strpos($column, '.') === FALSE) ? $this->table.'.'.$column : $column;

			if ($index === 0)
			{
				$this->db->like($column, $term);
			}
			else
			{
				$this->db->or_like($column, $term);
			}
		}

		$this->db->group_end();
	}

	/**
	 * Apply whitelisted filters. Keys absent from $filterable are ignored.
	 *
	 * @param  array $filters
	 * @return void
	 */
	protected function apply_filters($filters)
	{
		if ( ! is_array($filters))
		{
			return;
		}

		foreach ($filters as $key => $value)
		{
			if ( ! isset($this->filterable[$key]))
			{
				continue;
			}

			if ($value === '' || $value === NULL)
			{
				continue;
			}

			$definition = $this->filterable[$key];
			$column     = is_array($definition) ? $definition[0] : $definition;
			$operator   = is_array($definition) && isset($definition[1]) ? $definition[1] : '=';

			switch ($operator)
			{
				case 'like':
					$this->db->like($column, $value);
					break;

				case 'in':
					$this->db->where_in($column, (array) $value);
					break;

				case 'date':
					$this->db->where('DATE('.$column.')', $value);
					break;

				default:
					$this->db->where($column.' '.$operator, $value);
			}
		}
	}

	/**
	 * Apply sorting, restricted to the $sortable whitelist.
	 *
	 * @param  string|null $sort
	 * @param  string|null $order
	 * @return void
	 */
	protected function apply_sort($sort = NULL, $order = NULL)
	{
		$sort  = in_array($sort, $this->sortable, TRUE) ? $sort : $this->default_sort;
		$order = strtoupper((string) $order) === 'ASC' ? 'ASC' : 'DESC';

		if ($sort === NULL || $sort === '')
		{
			return;
		}

		$sort = (strpos($sort, '.') === FALSE) ? $this->table.'.'.$sort : $sort;

		$this->db->order_by($sort, $order);
	}

	// ------------------------------------------------------------------
	// Internals
	// ------------------------------------------------------------------

	/**
	 * Strip $fillable-violating and guarded keys from a write payload.
	 *
	 * @param  array $data
	 * @return array
	 */
	protected function filter_columns(array $data)
	{
		if ( ! empty($this->fillable))
		{
			$data = array_intersect_key($data, array_flip($this->fillable));
		}

		foreach ($this->guarded as $column)
		{
			unset($data[$column]);
		}

		return $data;
	}

	/**
	 * Remove hidden columns from a result row.
	 *
	 * @param  object $row
	 * @return object
	 */
	protected function strip_hidden($row)
	{
		if (empty($this->hidden) || ! is_object($row))
		{
			return $row;
		}

		foreach ($this->hidden as $column)
		{
			unset($row->{$column});
		}

		return $row;
	}

	/**
	 * Id of the acting user, or NULL for guests and system jobs.
	 *
	 * @return int|null
	 */
	protected function current_user_id()
	{
		if ( ! isset($this->session))
		{
			return NULL;
		}

		$user_id = $this->session->userdata('user_id');

		return $user_id ? (int) $user_id : NULL;
	}

	/**
	 * Null-safe array read.
	 *
	 * @param  array  $array
	 * @param  string $key
	 * @param  mixed  $default
	 * @return mixed
	 */
	protected function value($array, $key, $default = NULL)
	{
		return (is_array($array) && isset($array[$key])) ? $array[$key] : $default;
	}

	/**
	 * Expose the table name to services that need it.
	 *
	 * @return string
	 */
	public function table()
	{
		return $this->table;
	}
}
