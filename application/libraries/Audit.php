<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Audit
 *
 * Writes an immutable trail of who changed what, when and from where.
 *
 * Usage:
 *     $this->audit->created('products', $id, $data);
 *     $this->audit->updated('products', $id, $before, $after);
 *     $this->audit->deleted('orders', $id, $order);
 *     $this->audit->log('login', 'users', $user_id, 'Signed in');
 *
 * Writes are silently skipped until the `audit_logs` table exists (Phase 2), so
 * calls are always safe to make.
 *
 * @package Kupiana\Libraries
 */
class Audit
{
	/** @var CI_Controller */
	protected $CI;

	/** @var string */
	protected $table = 'audit_logs';

	/** @var bool|null */
	protected $table_ready = NULL;

	/**
	 * Keys never written to the trail, whatever the caller passes.
	 *
	 * @var string[]
	 */
	protected $redacted = array(
		'password', 'password_hash', 'password_confirm', 'confirm_password',
		'remember_token', 'reset_token', 'otp', 'otp_code', 'api_key',
		'razorpay_key_secret', 'webhook_secret', 'csrf_test_name',
	);

	public function __construct()
	{
		$this->CI =& get_instance();
	}

	/**
	 * Record a create.
	 *
	 * @param  string $entity Table or module name.
	 * @param  int    $entity_id
	 * @param  array  $values
	 * @return bool
	 */
	public function created($entity, $entity_id, array $values = array())
	{
		return $this->write('create', $entity, $entity_id, NULL, $values,
			ucfirst($this->humanise($entity)).' record created.');
	}

	/**
	 * Record an update, storing only the columns that actually changed.
	 *
	 * @param  string $entity
	 * @param  int    $entity_id
	 * @param  array  $before
	 * @param  array  $after
	 * @return bool
	 */
	public function updated($entity, $entity_id, $before = array(), $after = array())
	{
		$before = (array) $before;
		$after  = (array) $after;

		$changed_before = array();
		$changed_after  = array();

		foreach ($after as $key => $value)
		{
			$old = isset($before[$key]) ? $before[$key] : NULL;

			if ((string) $old !== (string) $value)
			{
				$changed_before[$key] = $old;
				$changed_after[$key]  = $value;
			}
		}

		if (empty($changed_after))
		{
			return TRUE;
		}

		return $this->write('update', $entity, $entity_id, $changed_before, $changed_after,
			ucfirst($this->humanise($entity)).' record updated.');
	}

	/**
	 * Record a delete.
	 *
	 * @param  string $entity
	 * @param  int    $entity_id
	 * @param  array  $values
	 * @return bool
	 */
	public function deleted($entity, $entity_id, $values = array())
	{
		return $this->write('delete', $entity, $entity_id, (array) $values, NULL,
			ucfirst($this->humanise($entity)).' record deleted.');
	}

	/**
	 * Record any other event: login, logout, export, refund, status change.
	 *
	 * @param  string   $action
	 * @param  string   $entity
	 * @param  int|null $entity_id
	 * @param  string   $description
	 * @param  array    $context
	 * @return bool
	 */
	public function log($action, $entity = 'system', $entity_id = NULL, $description = '', array $context = array())
	{
		return $this->write($action, $entity, $entity_id, NULL, $context, $description);
	}

	// ------------------------------------------------------------------
	// Internals
	// ------------------------------------------------------------------

	/**
	 * Insert one audit row.
	 *
	 * @param  string      $action
	 * @param  string      $entity
	 * @param  int|null    $entity_id
	 * @param  array|null  $old_values
	 * @param  array|null  $new_values
	 * @param  string      $description
	 * @return bool
	 */
	protected function write($action, $entity, $entity_id, $old_values, $new_values, $description = '')
	{
		if ( ! $this->table_ready())
		{
			return FALSE;
		}

		$user_id = (int) $this->CI->session->userdata('user_id') ?: NULL;
		$now     = date('Y-m-d H:i:s');

		$row = array(
			'user_id'     => $user_id,
			'user_name'   => $this->CI->session->userdata('user_name'),
			'action'      => $action,
			'entity'      => $entity,
			'entity_id'   => $entity_id !== NULL ? (int) $entity_id : NULL,
			'description' => $description,
			'old_values'  => $this->encode($old_values),
			'new_values'  => $this->encode($new_values),
			'ip_address'  => $this->CI->input->ip_address(),
			'user_agent'  => substr((string) $this->CI->input->user_agent(), 0, 255),
			'url'         => substr(current_url(), 0, 255),
			'method'      => $this->CI->input->method(TRUE),
			'status'      => 'active',
			'created_at'  => $now,
			'updated_at'  => $now,
			'created_by'  => $user_id,
			'updated_by'  => $user_id,
		);

		return (bool) $this->CI->db->insert($this->table, $row);
	}

	/**
	 * Redact sensitive keys and JSON-encode a payload.
	 *
	 * @param  array|null $values
	 * @return string|null
	 */
	protected function encode($values)
	{
		if (empty($values))
		{
			return NULL;
		}

		foreach ($this->redacted as $key)
		{
			if (array_key_exists($key, $values))
			{
				$values[$key] = '[redacted]';
			}
		}

		return json_encode($values);
	}

	/**
	 * `product_images` -> `product images`
	 *
	 * @param  string $entity
	 * @return string
	 */
	protected function humanise($entity)
	{
		return str_replace('_', ' ', $entity);
	}

	/**
	 * Does the audit table exist yet?
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
