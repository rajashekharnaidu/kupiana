<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Login_attempt_model
 *
 * Append-only record of sign-in attempts, used for IP-level throttling and as
 * a security audit trail.
 *
 * Two layers of defence work together:
 *   - per ACCOUNT: users.failed_login_attempts + users.locked_until
 *     (see User_model::register_failed_attempt)
 *   - per IP: this table, which also catches attackers spraying many
 *     different usernames from one host.
 *
 * @package Kupiana\Models
 */
class Login_attempt_model extends MY_Model
{
	protected $table = 'login_attempts';

	protected $fillable = array('email', 'ip_address', 'user_agent', 'successful', 'status');

	protected $audit = FALSE;

	protected $searchable = array('email', 'ip_address');

	protected $sortable = array('email', 'ip_address', 'successful', 'created_at');

	protected $filterable = array(
		'successful' => 'login_attempts.successful',
		'email'      => array('login_attempts.email', 'like'),
		'ip'         => 'login_attempts.ip_address',
	);

	protected $default_sort = 'created_at';

	/**
	 * Record an attempt.
	 *
	 * @param  string $email
	 * @param  bool   $successful
	 * @return void
	 */
	public function record($email, $successful)
	{
		$this->insert(array(
			'email'      => strtolower(trim($email)),
			'ip_address' => $this->input->ip_address(),
			'user_agent' => substr((string) $this->input->user_agent(), 0, 255),
			'successful' => $successful ? 1 : 0,
			'status'     => 'active',
		));
	}

	/**
	 * Failed attempts from the current IP within the window.
	 *
	 * @param  int $minutes
	 * @return int
	 */
	public function recent_failures_for_ip($minutes = 15)
	{
		return (int) $this->db
			->from($this->table)
			->where('ip_address', $this->input->ip_address())
			->where('successful', 0)
			->where('created_at >', date('Y-m-d H:i:s', time() - ($minutes * 60)))
			->count_all_results();
	}

	/**
	 * Failed attempts against one address within the window.
	 *
	 * @param  string $email
	 * @param  int    $minutes
	 * @return int
	 */
	public function recent_failures_for_email($email, $minutes = 15)
	{
		return (int) $this->db
			->from($this->table)
			->where('email', strtolower(trim($email)))
			->where('successful', 0)
			->where('created_at >', date('Y-m-d H:i:s', time() - ($minutes * 60)))
			->count_all_results();
	}

	/**
	 * Clear the IP throttle after a successful sign-in, so one good login
	 * releases the block for that host.
	 *
	 * @param  string $email
	 * @return void
	 */
	public function clear_for_email($email)
	{
		$this->db
			->where('email', strtolower(trim($email)))
			->where('successful', 0)
			->delete($this->table);
	}

	/**
	 * Prune old rows. Called from a Phase 13 scheduled task.
	 *
	 * @param  int $days
	 * @return int Rows removed.
	 */
	public function prune($days = 90)
	{
		$this->db
			->where('created_at <', date('Y-m-d H:i:s', time() - ($days * 86400)))
			->delete($this->table);

		return (int) $this->db->affected_rows();
	}
}
