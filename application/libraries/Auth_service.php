<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth_service
{
	protected $CI;

	public function __construct()
	{
		$this->CI =& get_instance();
	}

	public function attempt($email, $password)
	{
		$user = $this->CI->Auth_model->find_by_email($email);

		if (!$user || $user->password !== md5($password) || (int) $user->is_active !== 1) {
			return FALSE;
		}

		$roles = $this->CI->Auth_model->roles_for_user($user->id);

		$this->CI->session->sess_regenerate(TRUE);
		$this->CI->session->set_userdata(array(
			'user_id' => (int) $user->id,
			'user_email' => $user->email,
			'user_name' => trim($user->first_name.' '.$user->last_name),
			'user_roles' => $roles,
		));

		$this->CI->Auth_model->touch_login($user->id);

		return TRUE;
	}

	public function check()
	{
		return (bool) $this->CI->session->userdata('user_id');
	}

	public function user()
	{
		$user_id = $this->CI->session->userdata('user_id');
		return $user_id ? $this->CI->Auth_model->find($user_id) : NULL;
	}

	public function roles()
	{
		return (array) $this->CI->session->userdata('user_roles');
	}

	public function has_role($role)
	{
		return in_array($role, $this->roles(), TRUE);
	}

	public function logout()
	{
		$this->CI->session->sess_destroy();
	}

	public function redirect_path()
	{
		return $this->has_role('admin') ? 'admin' : 'account';
	}
}
