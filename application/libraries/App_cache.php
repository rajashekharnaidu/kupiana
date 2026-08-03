<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * App_cache
 *
 * Tiny cache facade for optimization work. It uses CodeIgniter's file cache when
 * available and silently falls back to a per-request in-memory array, so callers
 * can cache safely without making the cache directory a runtime dependency.
 */
class App_cache
{
	protected $CI;
	protected $ready = FALSE;
	protected $memory = array();

	public function __construct()
	{
		$this->CI =& get_instance();
		if (is_dir(APPPATH.'cache') && is_writable(APPPATH.'cache'))
		{
			$this->CI->load->driver('cache', array('adapter' => 'file'));
			$this->ready = isset($this->CI->cache) && $this->CI->cache->file->is_supported();
		}
	}

	public function get($key)
	{
		$key = $this->key($key);
		if (array_key_exists($key, $this->memory)) { return $this->memory[$key]; }
		if ( ! $this->ready) { return FALSE; }
		$value = $this->CI->cache->file->get($key);
		if ($value !== FALSE) { $this->memory[$key] = $value; }
		return $value;
	}

	public function save($key, $value, $ttl = 300)
	{
		$key = $this->key($key);
		$this->memory[$key] = $value;
		if ($this->ready) { $this->CI->cache->file->save($key, $value, (int) $ttl); }
		return $value;
	}

	public function remember($key, $ttl, callable $callback)
	{
		$value = $this->get($key);
		if ($value !== FALSE) { return $value; }
		return $this->save($key, call_user_func($callback), $ttl);
	}

	public function delete($key)
	{
		$key = $this->key($key);
		unset($this->memory[$key]);
		if ($this->ready) { $this->CI->cache->file->delete($key); }
	}

	protected function key($key)
	{
		return 'kupiana_'.md5((string) $key);
	}
}
