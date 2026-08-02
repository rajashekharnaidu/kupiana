<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Email_verification_model
 *
 * Expiring email-confirmation tokens. As with password resets, only a
 * SHA-256 digest is stored.
 *
 * @package Kupiana\Models
 */
class Email_verification_model extends MY_Model
{
	protected $table = 'email_verifications';

	protected $fillable = array('user_id', 'email', 'token', 'expires_at', 'verified_at', 'status');

	protected $audit = FALSE;

	/**
	 * Issue a verification token and return the PLAINTEXT for the email link.
	 *
	 * @param  object $user
	 * @param  int    $ttl_hours
	 * @return string
	 */
	public function issue($user, $ttl_hours = 48)
	{
		$this->db
			->where('user_id', (int) $user->id)
			->where('verified_at IS NULL', NULL, FALSE)
			->update($this->table, array(
				'status'     => 'superseded',
				'updated_at' => date('Y-m-d H:i:s'),
			));

		$plain = generate_token(32);

		$this->insert(array(
			'user_id'    => (int) $user->id,
			'email'      => $user->email,
			'token'      => hash('sha256', $plain),
			'expires_at' => date('Y-m-d H:i:s', time() + ((int) $ttl_hours * 3600)),
			'status'     => 'active',
		));

		return $plain;
	}

	/**
	 * Resolve a plaintext token to its unverified, unexpired row.
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
			->where('verified_at IS NULL', NULL, FALSE)
			->where('status', 'active')
			->where('expires_at >', date('Y-m-d H:i:s'))
			->where('deleted_at IS NULL', NULL, FALSE)
			->limit(1)
			->get()
			->row();
	}

	/**
	 * Mark a token as used.
	 *
	 * @param  int $id
	 * @return bool
	 */
	public function consume($id)
	{
		return $this->update($id, array(
			'verified_at' => date('Y-m-d H:i:s'),
			'status'      => 'verified',
		));
	}
}
