<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Acl
 *
 * Role and permission checks for the whole application.
 *
 * Permissions are named `<module>.<action>`, e.g. `products.view`,
 * `orders.refund`, `settings.manage`. The `super_admin` role bypasses every
 * check by design so the system can never lock itself out.
 *
 * The `permissions` and `role_permissions` tables arrive in Phase 2. Until they
 * exist this library falls back to role-only checks, which keeps the
 * application runnable at every point in the build.
 *
 * @package Kupiana\Libraries
 */
class Acl
{
	/** @var CI_Controller */
	protected $CI;

	/** @var string[]|null Lazily loaded permission slugs for the current user. */
	protected $permissions = NULL;

	/** @var string[] Roles treated as back-office staff. */
	protected $admin_roles = array('super_admin', 'admin', 'manager', 'staff');

	/** @var string Role that bypasses all permission checks. */
	protected $super_role = 'super_admin';

	/** @var bool|null Cached result of the permission-table probe. */
	protected $tables_ready = NULL;

	public function __construct()
	{
		$this->CI =& get_instance();
	}

	// ------------------------------------------------------------------
	// Roles
	// ------------------------------------------------------------------

	/**
	 * All role slugs held by the current user.
	 *
	 * @return string[]
	 */
	public function roles()
	{
		return (array) $this->CI->session->userdata('user_roles');
	}

	/**
	 * Does the current user hold this role?
	 *
	 * @param  string $role
	 * @return bool
	 */
	public function has_role($role)
	{
		return in_array($role, $this->roles(), TRUE);
	}

	/**
	 * Does the current user hold any of these roles?
	 *
	 * @param  string[] $roles
	 * @return bool
	 */
	public function has_any_role(array $roles)
	{
		return (bool) array_intersect($roles, $this->roles());
	}

	/**
	 * Is the current user a back-office user?
	 *
	 * @return bool
	 */
	public function is_admin()
	{
		return $this->has_any_role($this->admin_roles);
	}

	/**
	 * Is the current user the unrestricted super administrator?
	 *
	 * @return bool
	 */
	public function is_super_admin()
	{
		return $this->has_role($this->super_role);
	}

	// ------------------------------------------------------------------
	// Permissions
	// ------------------------------------------------------------------

	/**
	 * All permission slugs granted to the current user, via their roles.
	 *
	 * @return string[]
	 */
	public function permissions()
	{
		if ($this->permissions !== NULL)
		{
			return $this->permissions;
		}

		$this->permissions = array();

		$user_id = (int) $this->CI->session->userdata('user_id');

		if ( ! $user_id || ! $this->tables_ready())
		{
			return $this->permissions;
		}

		$rows = $this->CI->db
			->distinct()
			->select('permissions.slug')
			->from('permissions')
			->join('role_permissions', 'role_permissions.permission_id = permissions.id')
			->join('user_roles', 'user_roles.role_id = role_permissions.role_id')
			->where('user_roles.user_id', $user_id)
			->get()
			->result();

		foreach ($rows as $row)
		{
			$this->permissions[] = $row->slug;
		}

		return $this->permissions;
	}

	/**
	 * Can the current user perform this action?
	 *
	 * Wildcards are supported on the stored side: a granted `products.*` also
	 * satisfies a check for `products.edit`.
	 *
	 * @param  string $permission
	 * @return bool
	 */
	public function can($permission)
	{
		if ($this->is_super_admin())
		{
			return TRUE;
		}

		// Before the permission tables exist, fall back to role-based access so
		// the back office remains usable during the build.
		if ( ! $this->tables_ready())
		{
			return $this->is_admin();
		}

		$granted = $this->permissions();

		if (in_array($permission, $granted, TRUE))
		{
			return TRUE;
		}

		$module = strstr($permission, '.', TRUE);

		return $module !== FALSE && in_array($module.'.*', $granted, TRUE);
	}

	/**
	 * Can the user perform at least one of these actions?
	 *
	 * @param  string[] $permissions
	 * @return bool
	 */
	public function any(array $permissions)
	{
		foreach ($permissions as $permission)
		{
			if ($this->can($permission))
			{
				return TRUE;
			}
		}

		return FALSE;
	}

	/**
	 * Can the user perform every one of these actions?
	 *
	 * @param  string[] $permissions
	 * @return bool
	 */
	public function all(array $permissions)
	{
		foreach ($permissions as $permission)
		{
			if ( ! $this->can($permission))
			{
				return FALSE;
			}
		}

		return TRUE;
	}

	/**
	 * Halt the request unless the permission is held.
	 *
	 * @param  string $permission
	 * @return void
	 */
	public function require_permission($permission)
	{
		if ($this->can($permission))
		{
			return;
		}

		if ($this->CI->input->is_ajax_request())
		{
			$this->CI->output
				->set_status_header(403)
				->set_content_type('application/json', 'utf-8')
				->set_output(json_encode($this->CI->api_response->forbidden()));
			exit;
		}

		show_error('You do not have the "'.$permission.'" permission.', 403, 'Access Denied');
	}

	/**
	 * Drop the cached permission list, e.g. after a role change.
	 *
	 * @return void
	 */
	public function flush()
	{
		$this->permissions = NULL;
	}

	/**
	 * Have the Phase 2 permission tables been created yet?
	 *
	 * @return bool
	 */
	protected function tables_ready()
	{
		if ($this->tables_ready !== NULL)
		{
			return $this->tables_ready;
		}

		$this->tables_ready = $this->CI->db->table_exists('permissions')
			&& $this->CI->db->table_exists('role_permissions');

		return $this->tables_ready;
	}
}
