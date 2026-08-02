<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Settings
 *
 * Runtime application settings backed by the `settings` table, with the
 * `app` config file as the fallback so the site works before Phase 2 creates
 * the table and before an administrator has saved anything.
 *
 * Resolution order:  settings table  ->  app config  ->  supplied default.
 *
 * The whole table is loaded once per request and cached in memory.
 *
 * @package Kupiana\Libraries
 */
class Settings
{
	/** @var CI_Controller */
	protected $CI;

	/** @var array|null Cached key => value map. */
	protected $store = NULL;

	/** @var string */
	protected $table = 'settings';

	/** @var bool|null */
	protected $table_ready = NULL;

	public function __construct()
	{
		$this->CI =& get_instance();
		$this->CI->config->load('app', TRUE);
	}

	/**
	 * Read a setting.
	 *
	 * Dot notation reaches into the app config, e.g. `currency.symbol`.
	 *
	 * @param  string $key
	 * @param  mixed  $default
	 * @return mixed
	 */
	public function get($key, $default = NULL)
	{
		$store = $this->load();

		if (array_key_exists($key, $store))
		{
			return $store[$key];
		}

		$from_config = $this->from_config($key);

		return ($from_config !== NULL) ? $from_config : $default;
	}

	/**
	 * Read a setting cast to boolean.
	 *
	 * @param  string $key
	 * @param  bool   $default
	 * @return bool
	 */
	public function get_bool($key, $default = FALSE)
	{
		$value = $this->get($key, $default);

		if (is_bool($value))
		{
			return $value;
		}

		return in_array(strtolower((string) $value), array('1', 'true', 'yes', 'on'), TRUE);
	}

	/**
	 * Read a setting cast to integer.
	 *
	 * @param  string $key
	 * @param  int    $default
	 * @return int
	 */
	public function get_int($key, $default = 0)
	{
		return (int) $this->get($key, $default);
	}

	/**
	 * Every stored setting.
	 *
	 * @return array
	 */
	public function all()
	{
		return $this->load();
	}

	/**
	 * Settings belonging to one group, e.g. `payment` or `seo`.
	 *
	 * @param  string $group
	 * @return array
	 */
	public function group($group)
	{
		if ( ! $this->table_ready())
		{
			return array();
		}

		$rows = $this->CI->db
			->select('setting_key, setting_value')
			->where('setting_group', $group)
			->get($this->table)
			->result();

		$values = array();

		foreach ($rows as $row)
		{
			$values[$row->setting_key] = $this->cast($row->setting_value);
		}

		return $values;
	}

	/**
	 * Write a single setting.
	 *
	 * @param  string $key
	 * @param  mixed  $value
	 * @param  string $group
	 * @return bool
	 */
	public function set($key, $value, $group = 'general')
	{
		if ( ! $this->table_ready())
		{
			return FALSE;
		}

		$value = is_array($value) ? json_encode($value) : $value;
		$now   = date('Y-m-d H:i:s');
		$actor = (int) $this->CI->session->userdata('user_id') ?: NULL;

		$exists = $this->CI->db
			->where('setting_key', $key)
			->count_all_results($this->table) > 0;

		if ($exists)
		{
			$saved = $this->CI->db
				->where('setting_key', $key)
				->update($this->table, array(
					'setting_value' => $value,
					'updated_at'    => $now,
					'updated_by'    => $actor,
				));
		}
		else
		{
			$saved = $this->CI->db->insert($this->table, array(
				'setting_key'   => $key,
				'setting_value' => $value,
				'setting_group' => $group,
				'status'        => 'active',
				'created_at'    => $now,
				'updated_at'    => $now,
				'created_by'    => $actor,
				'updated_by'    => $actor,
			));
		}

		$this->flush();

		return (bool) $saved;
	}

	/**
	 * Write many settings at once, e.g. from a settings form.
	 *
	 * @param  array  $values key => value
	 * @param  string $group
	 * @return bool
	 */
	public function set_many(array $values, $group = 'general')
	{
		$ok = TRUE;

		foreach ($values as $key => $value)
		{
			$ok = $this->set($key, $value, $group) && $ok;
		}

		return $ok;
	}

	/**
	 * Discard the in-memory cache.
	 *
	 * @return void
	 */
	public function flush()
	{
		$this->store = NULL;
	}

	// ------------------------------------------------------------------
	// Internals
	// ------------------------------------------------------------------

	/**
	 * Load and cache every setting row.
	 *
	 * @return array
	 */
	protected function load()
	{
		if ($this->store !== NULL)
		{
			return $this->store;
		}

		$this->store = array();

		if ( ! $this->table_ready())
		{
			return $this->store;
		}

		$rows = $this->CI->db
			->select('setting_key, setting_value')
			->where('deleted_at IS NULL', NULL, FALSE)
			->get($this->table)
			->result();

		foreach ($rows as $row)
		{
			$this->store[$row->setting_key] = $this->cast($row->setting_value);
		}

		return $this->store;
	}

	/**
	 * Resolve a key against the app config file, supporting dot notation.
	 *
	 * @param  string $key
	 * @return mixed|null
	 */
	protected function from_config($key)
	{
		$segments = explode('.', $key);
		$root     = array_shift($segments);
		$value    = $this->CI->config->item($root, 'app');

		if ($value === NULL)
		{
			// Also allow flat lookups such as `name` against the app array.
			$app = $this->CI->config->item('app', 'app');

			return (is_array($app) && isset($app[$key])) ? $app[$key] : NULL;
		}

		foreach ($segments as $segment)
		{
			if ( ! is_array($value) || ! array_key_exists($segment, $value))
			{
				return NULL;
			}

			$value = $value[$segment];
		}

		return $value;
	}

	/**
	 * Decode JSON-looking values so array settings round-trip.
	 *
	 * @param  string $value
	 * @return mixed
	 */
	protected function cast($value)
	{
		if ( ! is_string($value) || $value === '')
		{
			return $value;
		}

		$first = $value[0];

		if ($first !== '{' && $first !== '[')
		{
			return $value;
		}

		$decoded = json_decode($value, TRUE);

		return (json_last_error() === JSON_ERROR_NONE) ? $decoded : $value;
	}

	/**
	 * Does the settings table exist yet?
	 *
	 * @return bool
	 */
	protected function table_ready()
	{
		if ($this->table_ready === NULL)
		{
			$this->table_ready = $this->CI->db->table_exists($this->table);
		}

		return $this->table_ready;
	}
}
