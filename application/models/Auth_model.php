<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth_model extends CI_Model
{
	public function find($id)
	{
		return $this->db
			->select('id, email, first_name, last_name, phone, is_active, created_at, updated_at')
			->where('id', (int) $id)
			->get('users')
			->row();
	}

	public function find_by_email($email)
	{
		return $this->db
			->where('email', strtolower(trim($email)))
			->limit(1)
			->get('users')
			->row();
	}

	public function roles_for_user($user_id)
	{
		$rows = $this->db
			->select('roles.slug')
			->from('roles')
			->join('user_roles', 'user_roles.role_id = roles.id')
			->where('user_roles.user_id', (int) $user_id)
			->get()
			->result();

		return array_map(function ($row) {
			return $row->slug;
		}, $rows);
	}

	public function touch_login($user_id)
	{
		$this->db
			->where('id', (int) $user_id)
			->update('users', array('last_login_at' => date('Y-m-d H:i:s')));
	}
}
