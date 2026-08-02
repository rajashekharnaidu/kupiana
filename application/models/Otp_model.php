<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Otp_model
 *
 * One-time passwords for passwordless login, checkout confirmation and
 * phone/email verification.
 *
 * The OTP is stored as a bcrypt hash and compared with password_verify(), so
 * a database leak does not expose live codes. Each row carries its own attempt
 * counter, which caps brute-force guessing at a handful of tries even within
 * the validity window.
 *
 * @package Kupiana\Models
 */
class Otp_model extends MY_Model
{
	protected $table = 'otp_codes';

	protected $fillable = array(
		'user_id', 'identifier', 'channel', 'purpose', 'otp_hash',
		'attempts', 'expires_at', 'verified_at', 'status',
	);

	protected $audit = FALSE;

	/** @var int Wrong guesses allowed before a code is burned. */
	protected $max_attempts = 5;

	/**
	 * Generate and store an OTP, returning the PLAINTEXT to send.
	 *
	 * @param  string   $identifier Email address or phone number.
	 * @param  string   $purpose    login|verify|reset|checkout
	 * @param  string   $channel    email|sms
	 * @param  int|null $user_id
	 * @param  int      $ttl_minutes
	 * @return string
	 */
	public function issue($identifier, $purpose = 'login', $channel = 'email', $user_id = NULL, $ttl_minutes = 10)
	{
		$this->invalidate($identifier, $purpose);

		$otp = generate_otp();

		$this->insert(array(
			'user_id'    => $user_id ? (int) $user_id : NULL,
			'identifier' => strtolower(trim($identifier)),
			'channel'    => $channel,
			'purpose'    => $purpose,
			'otp_hash'   => password_hash($otp, PASSWORD_BCRYPT),
			'attempts'   => 0,
			'expires_at' => date('Y-m-d H:i:s', time() + ((int) $ttl_minutes * 60)),
			'status'     => 'active',
		));

		return $otp;
	}

	/**
	 * Check a submitted code.
	 *
	 * Returns the row on success, or a string error key on failure:
	 * 'not_found', 'expired', 'too_many_attempts', 'mismatch'.
	 *
	 * @param  string $identifier
	 * @param  string $otp
	 * @param  string $purpose
	 * @return object|string
	 */
	public function verify($identifier, $otp, $purpose = 'login')
	{
		$row = $this->db
			->from($this->table)
			->where('identifier', strtolower(trim($identifier)))
			->where('purpose', $purpose)
			->where('verified_at IS NULL', NULL, FALSE)
			->where('status', 'active')
			->where('deleted_at IS NULL', NULL, FALSE)
			->order_by('id', 'DESC')
			->limit(1)
			->get()
			->row();

		if ( ! $row)
		{
			return 'not_found';
		}

		if (strtotime($row->expires_at) < time())
		{
			$this->update($row->id, array('status' => 'expired'));
			return 'expired';
		}

		if ((int) $row->attempts >= $this->max_attempts)
		{
			$this->update($row->id, array('status' => 'blocked'));
			return 'too_many_attempts';
		}

		if ( ! password_verify((string) $otp, $row->otp_hash))
		{
			$this->update($row->id, array('attempts' => (int) $row->attempts + 1));
			return 'mismatch';
		}

		$this->update($row->id, array(
			'verified_at' => date('Y-m-d H:i:s'),
			'status'      => 'verified',
		));

		return $row;
	}

	/**
	 * Retire any outstanding codes for an identifier and purpose.
	 *
	 * @param  string $identifier
	 * @param  string $purpose
	 * @return void
	 */
	public function invalidate($identifier, $purpose)
	{
		$this->db
			->where('identifier', strtolower(trim($identifier)))
			->where('purpose', $purpose)
			->where('verified_at IS NULL', NULL, FALSE)
			->update($this->table, array(
				'status'     => 'superseded',
				'updated_at' => date('Y-m-d H:i:s'),
			));
	}

	/**
	 * How many codes this identifier has requested recently. Throttles resends.
	 *
	 * @param  string $identifier
	 * @param  string $purpose
	 * @param  int    $minutes
	 * @return int
	 */
	public function recent_request_count($identifier, $purpose, $minutes = 15)
	{
		return (int) $this->db
			->from($this->table)
			->where('identifier', strtolower(trim($identifier)))
			->where('purpose', $purpose)
			->where('created_at >', date('Y-m-d H:i:s', time() - ($minutes * 60)))
			->count_all_results();
	}
}
