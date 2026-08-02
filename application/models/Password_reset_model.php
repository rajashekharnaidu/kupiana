<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Password_reset_model
 *
 * Single-use, expiring password-reset tokens.
 *
 * Only a SHA-256 digest of the token is stored. The plaintext exists solely
 * inside the emailed link, so a database leak cannot be replayed to seize
 * accounts.
 *
 * @package Kupiana\Models
 */
class Password_reset_model extends MY_Model
{
	protected $table = 'password_resets';

	protected $fillable = array('user_id', 'email', 'token', 'ip_address', 'expires_at', 'used_at', 'status');

	protected $audit = FALSE; // Created by anonymous visitors.

	/**
	 * Issue a token for a user and return the PLAINTEXT to embed in the email.
	 *
	 * Any earlier unused token for the same address is invalidated first, so a
	 * user cannot hold several live reset links at once.
	 *
	 * @param  object $user
	 * @param  int    $ttl_minutes
	 * @return string
	 */
	public function issue($user, $ttl_minutes = 60)
	{
		$this->invalidate_for_email($user->email);

		$plain = generate_token(32);

		$this->insert(array(
			'user_id'    => (int) $user->id,
			'email'      => $user->email,
			'token'      => hash('sha256', $plain),
			'ip_address' => $this->input->ip_address(),
			'expires_at' => date('Y-m-d H:i:s', time() + ((int) $ttl_minutes * 60)),
			'status'     => 'active',
		));

		return $plain;
	}

	/**
	 * Resolve a plaintext token to its unused, unexpired row.
	 *
	 * @param  string $plain
	 * @return object|null
	 */
	public function find_valid($plain)
	{
		if (trim((string) $plain) === '')
		{
			return NULL;
		}

		return $this->db
			->from($this->table)
			->where('token', hash('sha256', $plain))
			->where('used_at IS NULL', NULL, FALSE)
			->where('expires_at >', date('Y-m-d H:i:s'))
			->where('deleted_at IS NULL', NULL, FALSE)
			->limit(1)
			->get()
			->row();
	}

	/**
	 * Burn a token after a successful reset.
	 *
	 * @param  int $id
	 * @return bool
	 */
	public function consume($id)
	{
		return $this->update($id, array('used_at' => date('Y-m-d H:i:s'), 'status' => 'used'));
	}

	/**
	 * Invalidate every outstanding token for an address.
	 *
	 * @param  string $email
	 * @return void
	 */
	public function invalidate_for_email($email)
	{
		$this->db
			->where('email', strtolower(trim($email)))
			->where('used_at IS NULL', NULL, FALSE)
			->update($this->table, array(
				'used_at'    => date('Y-m-d H:i:s'),
				'status'     => 'superseded',
				'updated_at' => date('Y-m-d H:i:s'),
			));
	}

	/**
	 * How many reset requests this address has made recently.
	 * Used to throttle the forgot-password form.
	 *
	 * @param  string $email
	 * @param  int    $minutes
	 * @return int
	 */
	public function recent_request_count($email, $minutes = 15)
	{
		return (int) $this->db
			->from($this->table)
			->where('email', strtolower(trim($email)))
			->where('created_at >', date('Y-m-d H:i:s', time() - ($minutes * 60)))
			->count_all_results();
	}
}
