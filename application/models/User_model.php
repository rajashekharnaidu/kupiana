<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * User_model
 *
 * Users, their roles, and the counters used by login throttling.
 * Replaces the Phase 1 Auth_model.
 *
 * @package Kupiana\Models
 */
class User_model extends MY_Model
{
	protected $table = 'users';

	protected $fillable = array(
		'uuid', 'first_name', 'last_name', 'email', 'phone', 'password', 'avatar',
		'user_type', 'gender', 'date_of_birth', 'email_verified_at', 'phone_verified_at',
		'last_login_at', 'last_login_ip', 'failed_login_attempts', 'locked_until',
		'remember_token', 'status',
	);

	/** Never leaked to a view or an API response. */
	protected $hidden = array('password', 'remember_token');

	protected $searchable = array('first_name', 'last_name', 'email', 'phone');

	protected $sortable = array('first_name', 'email', 'user_type', 'status', 'last_login_at', 'created_at');

	protected $filterable = array(
		'user_type' => 'users.user_type',
		'status'    => 'users.status',
		'verified'  => 'users.email_verified_at',
		'from'      => array('users.created_at >=', '>='),
		'to'        => array('users.created_at <=', '<='),
	);

	protected $default_sort = 'created_at';

	/**
	 * Find a user by email, including the password hash.
	 *
	 * Deliberately bypasses $hidden: authentication needs the hash. Never pass
	 * the result of this method to a view.
	 *
	 * @param  string $email
	 * @return object|null
	 */
	public function find_for_auth($email)
	{
		return $this->db
			->from($this->table)
			->where('email', strtolower(trim($email)))
			->limit(1)
			->get()
			->row();
	}

	/**
	 * Find a user by id, including the password hash.
	 *
	 * Same warning as find_for_auth(): never hand the result to a view.
	 *
	 * @param  int $id
	 * @return object|null
	 */
	public function find_for_auth_by_id($id)
	{
		return $this->db
			->from($this->table)
			->where('id', (int) $id)
			->limit(1)
			->get()
			->row();
	}

	/**
	 * Find a user by email, safe for display.
	 *
	 * @param  string $email
	 * @return object|null
	 */
	public function find_by_email($email)
	{
		return $this->find_by(array('email' => strtolower(trim($email))));
	}

	/**
	 * Role slugs held by a user.
	 *
	 * @param  int $user_id
	 * @return string[]
	 */
	public function roles_for_user($user_id)
	{
		$rows = $this->db
			->select('roles.slug')
			->from('roles')
			->join('user_roles', 'user_roles.role_id = roles.id')
			->where('user_roles.user_id', (int) $user_id)
			->where('roles.deleted_at IS NULL', NULL, FALSE)
			->get()
			->result();

		return array_map(function ($row) {
			return $row->slug;
		}, $rows);
	}

	/**
	 * Replace a user's roles.
	 *
	 * @param  int   $user_id
	 * @param  int[] $role_ids
	 * @return void
	 */
	public function sync_roles($user_id, array $role_ids)
	{
		$this->db->delete('user_roles', array('user_id' => (int) $user_id));

		$now  = date('Y-m-d H:i:s');
		$rows = array();

		foreach (array_unique(array_map('intval', $role_ids)) as $role_id)
		{
			$rows[] = array(
				'user_id'    => (int) $user_id,
				'role_id'    => $role_id,
				'status'     => 'active',
				'created_at' => $now,
				'updated_at' => $now,
				'created_by' => $this->current_user_id(),
				'updated_by' => $this->current_user_id(),
			);
		}

		if ( ! empty($rows))
		{
			$this->db->insert_batch('user_roles', $rows);
		}
	}

	/**
	 * Assign a role by slug. Used at registration.
	 *
	 * @param  int    $user_id
	 * @param  string $slug
	 * @return bool
	 */
	public function assign_role_slug($user_id, $slug)
	{
		$role = $this->db->select('id')->where('slug', $slug)->limit(1)->get('roles')->row();

		if ( ! $role)
		{
			return FALSE;
		}

		$now = date('Y-m-d H:i:s');

		return (bool) $this->db->insert('user_roles', array(
			'user_id'    => (int) $user_id,
			'role_id'    => (int) $role->id,
			'status'     => 'active',
			'created_at' => $now,
			'updated_at' => $now,
		));
	}

	/**
	 * Record a successful sign-in and clear the throttle counters.
	 *
	 * @param  int $user_id
	 * @return void
	 */
	public function touch_login($user_id)
	{
		$this->db->where('id', (int) $user_id)->update($this->table, array(
			'last_login_at'         => date('Y-m-d H:i:s'),
			'last_login_ip'         => $this->input->ip_address(),
			'failed_login_attempts' => 0,
			'locked_until'          => NULL,
		));
	}

	/**
	 * Increment the failed-attempt counter, locking the account once it passes
	 * the configured threshold.
	 *
	 * @param  int $user_id
	 * @param  int $max_attempts
	 * @param  int $lockout_minutes
	 * @return void
	 */
	public function register_failed_attempt($user_id, $max_attempts, $lockout_minutes)
	{
		$this->db->set('failed_login_attempts', 'failed_login_attempts + 1', FALSE)
			->where('id', (int) $user_id)
			->update($this->table);

		$user = $this->db->select('failed_login_attempts')
			->where('id', (int) $user_id)
			->get($this->table)
			->row();

		if ($user && (int) $user->failed_login_attempts >= (int) $max_attempts)
		{
			$this->db->where('id', (int) $user_id)->update($this->table, array(
				'locked_until' => date('Y-m-d H:i:s', time() + ((int) $lockout_minutes * 60)),
			));
		}
	}

	/**
	 * Store a new password hash and clear any lockout.
	 *
	 * @param  int    $user_id
	 * @param  string $hash
	 * @return bool
	 */
	public function update_password($user_id, $hash)
	{
		return (bool) $this->db->where('id', (int) $user_id)->update($this->table, array(
			'password'              => $hash,
			'failed_login_attempts' => 0,
			'locked_until'          => NULL,
			'updated_at'            => date('Y-m-d H:i:s'),
		));
	}

	/**
	 * Mark an email address as verified.
	 *
	 * @param  int $user_id
	 * @return bool
	 */
	public function mark_email_verified($user_id)
	{
		return (bool) $this->db->where('id', (int) $user_id)->update($this->table, array(
			'email_verified_at' => date('Y-m-d H:i:s'),
			'updated_at'        => date('Y-m-d H:i:s'),
		));
	}

	/**
	 * Create a customer account. Returns the new user id.
	 *
	 * @param  array $data
	 * @return int|false
	 */
	public function create_customer(array $data)
	{
		$data['uuid']      = $this->uuid();
		$data['user_type'] = 'customer';
		$data['status']    = 'active';
		$data['email']     = strtolower(trim($data['email']));

		$user_id = $this->insert($data);

		if ($user_id)
		{
			$this->assign_role_slug($user_id, 'customer');

			// Every customer gets a wallet so Phase 5 can credit refunds.
			$now = date('Y-m-d H:i:s');
			$this->db->insert('wallets', array(
				'user_id'    => $user_id,
				'balance'    => 0.00,
				'status'     => 'active',
				'created_at' => $now,
				'updated_at' => $now,
			));
		}

		return $user_id;
	}

	/**
	 * RFC 4122 version 4 UUID.
	 *
	 * @return string
	 */
	public function uuid()
	{
		$data = random_bytes(16);

		$data[6] = chr(ord($data[6]) & 0x0f | 0x40);
		$data[8] = chr(ord($data[8]) & 0x3f | 0x80);

		return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
	}
}
