<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * User_session_model
 *
 * Persistent "remember me" tokens, and the list of active devices a customer
 * can review and revoke from account settings.
 *
 * The cookie carries "<id>:<plaintext selector>". Only a SHA-256 digest of the
 * plaintext is stored, and lookup is by primary key followed by a
 * hash_equals() comparison — so the check is not vulnerable to timing
 * analysis and a leaked table cannot be replayed.
 *
 * @package Kupiana\Models
 */
class User_session_model extends MY_Model
{
	protected $table = 'user_sessions';

	protected $fillable = array(
		'user_id', 'token_hash', 'ip_address', 'user_agent',
		'last_used_at', 'expires_at', 'revoked_at', 'status',
	);

	protected $audit = FALSE;

	/**
	 * Issue a remember-me token.
	 *
	 * @param  int $user_id
	 * @param  int $days
	 * @return string Cookie value: "<id>:<plaintext>".
	 */
	public function issue($user_id, $days = 30)
	{
		$plain = generate_token(32);

		$id = $this->insert(array(
			'user_id'      => (int) $user_id,
			'token_hash'   => hash('sha256', $plain),
			'ip_address'   => $this->input->ip_address(),
			'user_agent'   => substr((string) $this->input->user_agent(), 0, 255),
			'last_used_at' => date('Y-m-d H:i:s'),
			'expires_at'   => date('Y-m-d H:i:s', time() + ((int) $days * 86400)),
			'status'       => 'active',
		));

		return $id ? $id.':'.$plain : '';
	}

	/**
	 * Resolve a cookie value to its live session row.
	 *
	 * @param  string $cookie
	 * @return object|null
	 */
	public function find_valid($cookie)
	{
		if (strpos((string) $cookie, ':') === FALSE)
		{
			return NULL;
		}

		list($id, $plain) = explode(':', $cookie, 2);

		$row = $this->db
			->from($this->table)
			->where('id', (int) $id)
			->where('revoked_at IS NULL', NULL, FALSE)
			->where('expires_at >', date('Y-m-d H:i:s'))
			->where('deleted_at IS NULL', NULL, FALSE)
			->limit(1)
			->get()
			->row();

		if ( ! $row)
		{
			return NULL;
		}

		if ( ! hash_equals($row->token_hash, hash('sha256', $plain)))
		{
			// The id exists but the secret is wrong: treat as a stolen or
			// tampered cookie and revoke the whole session.
			$this->revoke($row->id);
			return NULL;
		}

		return $row;
	}

	/**
	 * Rotate a session's secret. Called on every remember-me sign-in so a
	 * stolen cookie stops working as soon as the real user returns.
	 *
	 * @param  int $id
	 * @return string New cookie value.
	 */
	public function rotate($id)
	{
		$plain = generate_token(32);

		$this->update($id, array(
			'token_hash'   => hash('sha256', $plain),
			'last_used_at' => date('Y-m-d H:i:s'),
			'ip_address'   => $this->input->ip_address(),
		));

		return $id.':'.$plain;
	}

	/**
	 * Revoke one session.
	 *
	 * @param  int $id
	 * @return bool
	 */
	public function revoke($id)
	{
		return $this->update($id, array(
			'revoked_at' => date('Y-m-d H:i:s'),
			'status'     => 'revoked',
		));
	}

	/**
	 * Revoke every session for a user, e.g. after a password change.
	 *
	 * @param  int      $user_id
	 * @param  int|null $except_id
	 * @return void
	 */
	public function revoke_all($user_id, $except_id = NULL)
	{
		$this->db->where('user_id', (int) $user_id)->where('revoked_at IS NULL', NULL, FALSE);

		if ($except_id !== NULL)
		{
			$this->db->where('id !=', (int) $except_id);
		}

		$this->db->update($this->table, array(
			'revoked_at' => date('Y-m-d H:i:s'),
			'status'     => 'revoked',
			'updated_at' => date('Y-m-d H:i:s'),
		));
	}

	/**
	 * Live sessions for a user, for the "signed-in devices" screen.
	 *
	 * @param  int $user_id
	 * @return array
	 */
	public function active_for_user($user_id)
	{
		return $this->db
			->from($this->table)
			->where('user_id', (int) $user_id)
			->where('revoked_at IS NULL', NULL, FALSE)
			->where('expires_at >', date('Y-m-d H:i:s'))
			->order_by('last_used_at', 'DESC')
			->get()
			->result();
	}
}
