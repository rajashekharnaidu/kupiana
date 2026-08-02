<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Auth_service
 *
 * The single entry point for everything authentication: sign-in, throttling,
 * lockout, registration, email verification, password reset, OTP and
 * remember-me.
 *
 * Controllers never touch the auth tables directly — they call this service,
 * which returns a consistent result array so the caller can render one message
 * without knowing which layer refused.
 *
 * Result shape:
 *     array('success' => bool, 'code' => string, 'message' => string, 'user' => object|null)
 *
 * @package Kupiana\Libraries
 */
class Auth_service
{
	/** @var CI_Controller */
	protected $CI;

	/** @var array Cached security settings from config/app.php. */
	protected $security = array();

	/** @var string Cookie holding the remember-me token. */
	protected $remember_cookie = 'kupiana_remember';

	/** @var string[] Roles treated as back-office staff. */
	protected $admin_roles = array('super_admin', 'admin', 'manager', 'staff');

	public function __construct()
	{
		$this->CI =& get_instance();

		$this->CI->config->load('app', TRUE);
		$this->security = (array) $this->CI->config->item('security', 'app');

		$this->CI->load->model(array(
			'User_model',
			'Password_reset_model',
			'Email_verification_model',
			'Otp_model',
			'Login_attempt_model',
			'User_session_model',
		));
	}

	// ==================================================================
	// Sign in / sign out
	// ==================================================================

	/**
	 * Attempt a password sign-in.
	 *
	 * Order of checks matters: the IP throttle runs before anything touches
	 * the database by email, so credential stuffing is cut off early.
	 *
	 * @param  string $email
	 * @param  string $password
	 * @param  bool   $remember
	 * @return array
	 */
	public function attempt($email, $password, $remember = FALSE)
	{
		$email = strtolower(trim((string) $email));

		if ($this->ip_is_throttled())
		{
			return $this->fail('ip_throttled',
				'Too many failed attempts from this device. Please try again in '
				.$this->lockout_minutes().' minutes.');
		}

		$user = $this->CI->User_model->find_for_auth($email);

		// Always record the attempt, even for unknown addresses — that is what
		// makes the IP throttle effective against username enumeration.
		if ( ! $user)
		{
			$this->CI->Login_attempt_model->record($email, FALSE);
			return $this->fail('invalid_credentials', 'Invalid email or password.');
		}

		if ($user->deleted_at !== NULL)
		{
			$this->CI->Login_attempt_model->record($email, FALSE);
			return $this->fail('invalid_credentials', 'Invalid email or password.');
		}

		if ($this->is_locked($user))
		{
			$this->CI->Login_attempt_model->record($email, FALSE);

			return $this->fail('locked',
				'This account is temporarily locked after too many failed attempts. '
				.'Try again after '.format_datetime($user->locked_until).'.');
		}

		if ($user->status !== 'active')
		{
			$this->CI->Login_attempt_model->record($email, FALSE);
			return $this->fail('inactive', 'This account is not active. Please contact support.');
		}

		if ( ! $this->verify_password($password, $user))
		{
			$this->CI->Login_attempt_model->record($email, FALSE);
			$this->CI->User_model->register_failed_attempt(
				$user->id, $this->max_attempts(), $this->lockout_minutes()
			);

			return $this->fail('invalid_credentials', 'Invalid email or password.');
		}

		if ($this->verification_required() && $user->email_verified_at === NULL)
		{
			$this->CI->Login_attempt_model->record($email, FALSE);

			return $this->fail('unverified',
				'Please verify your email address before signing in. '
				.'<a href="'.site_url('resend-verification?email='.rawurlencode($email)).'">Resend the link</a>.',
				$user);
		}

		$this->establish_session($user, $remember);

		return $this->ok('signed_in', 'Signed in successfully.', $user);
	}

	/**
	 * Sign the current user in without a password.
	 *
	 * Only for flows that have already proven identity: OTP login, and the
	 * remember-me cookie. Never expose this to request input.
	 *
	 * @param  object $user
	 * @param  bool   $remember
	 * @return array
	 */
	public function force_login($user, $remember = FALSE)
	{
		if ( ! $user || $user->status !== 'active' || $user->deleted_at !== NULL)
		{
			return $this->fail('inactive', 'This account is not active.');
		}

		$this->establish_session($user, $remember);

		return $this->ok('signed_in', 'Signed in successfully.', $user);
	}

	/**
	 * Write the session, reset throttles and optionally set remember-me.
	 *
	 * @param  object $user
	 * @param  bool   $remember
	 * @return void
	 */
	protected function establish_session($user, $remember = FALSE)
	{
		$roles = $this->CI->User_model->roles_for_user($user->id);

		// Regenerate the session id on privilege change to defeat fixation.
		$this->CI->session->sess_regenerate(TRUE);

		$this->CI->session->set_userdata(array(
			'user_id'    => (int) $user->id,
			'user_email' => $user->email,
			'user_name'  => trim($user->first_name.' '.$user->last_name),
			'user_roles' => $roles,
			'logged_in_at' => date('Y-m-d H:i:s'),
		));

		$this->CI->User_model->touch_login($user->id);
		$this->CI->Login_attempt_model->record($user->email, TRUE);
		$this->CI->Login_attempt_model->clear_for_email($user->email);

		if (isset($this->CI->acl))
		{
			$this->CI->acl->flush();
		}

		if ($remember)
		{
			$this->set_remember_cookie($user->id);
		}

		$this->CI->audit->log('login', 'users', $user->id, 'User signed in.');
	}

	/**
	 * Sign out, revoking the remember-me token for this device.
	 *
	 * @return void
	 */
	public function logout()
	{
		$user_id = (int) $this->CI->session->userdata('user_id');

		if ($user_id)
		{
			$this->CI->audit->log('logout', 'users', $user_id, 'User signed out.');
		}

		$cookie = $this->CI->input->cookie($this->remember_cookie, TRUE);

		if ($cookie)
		{
			$session = $this->CI->User_session_model->find_valid($cookie);

			if ($session)
			{
				$this->CI->User_session_model->revoke($session->id);
			}

			$this->clear_remember_cookie();
		}

		$this->CI->session->sess_destroy();
	}

	// ==================================================================
	// Remember me
	// ==================================================================

	/**
	 * Restore a session from the remember-me cookie.
	 *
	 * Invoked by the auth_autologin hook on every request, so it must be cheap
	 * and must fail silently.
	 *
	 * @return bool TRUE if a session was restored.
	 */
	public function attempt_remembered_login()
	{
		if ($this->check())
		{
			return FALSE;
		}

		$cookie = $this->CI->input->cookie($this->remember_cookie, TRUE);

		if ( ! $cookie)
		{
			return FALSE;
		}

		$session = $this->CI->User_session_model->find_valid($cookie);

		if ( ! $session)
		{
			$this->clear_remember_cookie();
			return FALSE;
		}

		$user = $this->CI->User_model->find_for_auth_by_id($session->user_id);

		if ( ! $user || $user->status !== 'active' || $user->deleted_at !== NULL)
		{
			$this->CI->User_session_model->revoke($session->id);
			$this->clear_remember_cookie();
			return FALSE;
		}

		// Rotate the secret so a copied cookie dies the moment the real
		// browser presents it again.
		$this->write_remember_cookie($this->CI->User_session_model->rotate($session->id));

		$roles = $this->CI->User_model->roles_for_user($user->id);

		$this->CI->session->set_userdata(array(
			'user_id'    => (int) $user->id,
			'user_email' => $user->email,
			'user_name'  => trim($user->first_name.' '.$user->last_name),
			'user_roles' => $roles,
			'logged_in_at' => date('Y-m-d H:i:s'),
			'via_remember' => TRUE,
		));

		return TRUE;
	}

	/**
	 * Create and store a remember-me cookie.
	 *
	 * @param  int $user_id
	 * @return void
	 */
	protected function set_remember_cookie($user_id)
	{
		$days   = (int) $this->config_value('remember_me_days', 30);
		$cookie = $this->CI->User_session_model->issue($user_id, $days);

		if ($cookie !== '')
		{
			$this->write_remember_cookie($cookie, $days);
		}
	}

	/**
	 * Write the cookie with hardened flags.
	 *
	 * @param  string $value
	 * @param  int    $days
	 * @return void
	 */
	protected function write_remember_cookie($value, $days = NULL)
	{
		$days = $days ?: (int) $this->config_value('remember_me_days', 30);

		$this->CI->input->set_cookie(array(
			'name'     => $this->remember_cookie,
			'value'    => $value,
			'expire'   => $days * 86400,
			'httponly' => TRUE,
			'secure'   => (bool) $this->CI->config->item('cookie_secure'),
			'samesite' => 'Lax',
		));
	}

	/** @return void */
	protected function clear_remember_cookie()
	{
		delete_cookie($this->remember_cookie);
	}

	// ==================================================================
	// Registration & email verification
	// ==================================================================

	/**
	 * Create a customer account and send the verification email.
	 *
	 * @param  array $data first_name, last_name, email, phone, password
	 * @return array
	 */
	public function register(array $data)
	{
		$email = strtolower(trim($data['email']));

		if ($this->CI->User_model->find_by_email($email))
		{
			return $this->fail('email_taken', 'An account with this email already exists.');
		}

		$user_id = $this->CI->User_model->create_customer(array(
			'first_name' => $data['first_name'],
			'last_name'  => isset($data['last_name']) ? $data['last_name'] : NULL,
			'email'      => $email,
			'phone'      => isset($data['phone']) ? $data['phone'] : NULL,
			'password'   => $this->hash_password($data['password']),
		));

		if ( ! $user_id)
		{
			return $this->fail('create_failed', 'We could not create your account. Please try again.');
		}

		$user = $this->CI->User_model->find($user_id);

		$this->CI->audit->log('register', 'users', $user_id, 'Customer account created.');

		$this->send_verification_email($user);

		return $this->ok('registered',
			'Your account has been created. Check your inbox to verify your email address.',
			$user);
	}

	/**
	 * Issue a verification token and email it.
	 *
	 * @param  object $user
	 * @return bool
	 */
	public function send_verification_email($user)
	{
		$token = $this->CI->Email_verification_model->issue($user, 48);

		return $this->CI->mailer->send_template(
			'email_verification',
			$user->email,
			trim($user->first_name.' '.$user->last_name),
			array(
				'first_name'     => $user->first_name,
				'verify_url'     => site_url('verify-email?token='.rawurlencode($token)),
				'expiry_minutes' => 48 * 60,
			)
		);
	}

	/**
	 * Consume a verification token.
	 *
	 * @param  string $token
	 * @return array
	 */
	public function verify_email($token)
	{
		$row = $this->CI->Email_verification_model->find_valid($token);

		if ( ! $row)
		{
			return $this->fail('invalid_token',
				'This verification link is invalid or has expired. Please request a new one.');
		}

		$this->CI->Email_verification_model->consume($row->id);
		$this->CI->User_model->mark_email_verified($row->user_id);
		$this->CI->audit->log('verify_email', 'users', $row->user_id, 'Email address verified.');

		$user = $this->CI->User_model->find($row->user_id);

		if ($user)
		{
			$this->CI->mailer->send_template('welcome', $user->email,
				trim($user->first_name.' '.$user->last_name),
				array('first_name' => $user->first_name));
		}

		return $this->ok('verified', 'Your email address has been verified. You can now sign in.', $user);
	}

	// ==================================================================
	// Password reset
	// ==================================================================

	/**
	 * Start a password reset.
	 *
	 * Always reports success, whatever the address — otherwise this form
	 * becomes an oracle for which emails have accounts.
	 *
	 * @param  string $email
	 * @return array
	 */
	public function send_password_reset($email)
	{
		$email   = strtolower(trim($email));
		$generic = 'If an account exists for that address, a reset link is on its way.';

		if ($this->CI->Password_reset_model->recent_request_count($email, 15) >= 3)
		{
			return $this->fail('throttled',
				'Too many reset requests. Please wait 15 minutes before trying again.');
		}

		$user = $this->CI->User_model->find_by_email($email);

		if ( ! $user || $user->status !== 'active')
		{
			return $this->ok('sent', $generic);
		}

		$ttl   = (int) $this->config_value('reset_token_ttl_min', 60);
		$token = $this->CI->Password_reset_model->issue($user, $ttl);

		$this->CI->mailer->send_template(
			'password_reset',
			$user->email,
			trim($user->first_name.' '.$user->last_name),
			array(
				'first_name'     => $user->first_name,
				'reset_url'      => site_url('reset-password?token='.rawurlencode($token)),
				'expiry_minutes' => $ttl,
			)
		);

		$this->CI->audit->log('password_reset_requested', 'users', $user->id,
			'Password reset link requested.');

		return $this->ok('sent', $generic);
	}

	/**
	 * Validate a reset token without consuming it, so the form can be shown.
	 *
	 * @param  string $token
	 * @return object|null
	 */
	public function find_reset($token)
	{
		return $this->CI->Password_reset_model->find_valid($token);
	}

	/**
	 * Complete a password reset.
	 *
	 * @param  string $token
	 * @param  string $password
	 * @return array
	 */
	public function reset_password($token, $password)
	{
		$row = $this->CI->Password_reset_model->find_valid($token);

		if ( ! $row)
		{
			return $this->fail('invalid_token',
				'This reset link is invalid or has expired. Please request a new one.');
		}

		$this->CI->User_model->update_password($row->user_id, $this->hash_password($password));
		$this->CI->Password_reset_model->consume($row->id);

		// A password change invalidates every remembered device.
		$this->CI->User_session_model->revoke_all($row->user_id);
		$this->CI->Login_attempt_model->clear_for_email($row->email);

		$this->CI->audit->log('password_reset', 'users', $row->user_id, 'Password reset completed.');

		return $this->ok('reset', 'Your password has been updated. You can now sign in.');
	}

	/**
	 * Change the signed-in user's password after confirming the current one.
	 *
	 * @param  int    $user_id
	 * @param  string $current
	 * @param  string $new
	 * @return array
	 */
	public function change_password($user_id, $current, $new)
	{
		$user = $this->CI->User_model->find_for_auth_by_id($user_id);

		if ( ! $user)
		{
			return $this->fail('not_found', 'Account not found.');
		}

		if ( ! $this->verify_password($current, $user))
		{
			return $this->fail('wrong_password', 'Your current password is incorrect.');
		}

		$this->CI->User_model->update_password($user_id, $this->hash_password($new));
		$this->CI->User_session_model->revoke_all($user_id);
		$this->CI->audit->log('password_changed', 'users', $user_id, 'Password changed from account settings.');

		return $this->ok('changed', 'Your password has been updated.');
	}

	// ==================================================================
	// OTP
	// ==================================================================

	/**
	 * Email a one-time login code.
	 *
	 * Like the reset flow, the response does not reveal whether the account
	 * exists.
	 *
	 * @param  string $email
	 * @param  string $purpose
	 * @return array
	 */
	public function send_login_otp($email, $purpose = 'login')
	{
		$email   = strtolower(trim($email));
		$generic = 'If an account exists for that address, a code is on its way.';

		if ($this->CI->Otp_model->recent_request_count($email, $purpose, 15) >= 3)
		{
			return $this->fail('throttled',
				'Too many codes requested. Please wait 15 minutes before trying again.');
		}

		$user = $this->CI->User_model->find_by_email($email);

		if ( ! $user || $user->status !== 'active')
		{
			return $this->ok('sent', $generic);
		}

		$ttl = (int) $this->config_value('otp_ttl_min', 10);
		$otp = $this->CI->Otp_model->issue($email, $purpose, 'email', $user->id, $ttl);

		$this->CI->mailer->send_template(
			'otp_login',
			$user->email,
			trim($user->first_name.' '.$user->last_name),
			array('otp' => $otp, 'expiry_minutes' => $ttl)
		);

		return $this->ok('sent', $generic);
	}

	/**
	 * Verify a login OTP and sign the user in.
	 *
	 * @param  string $email
	 * @param  string $otp
	 * @param  bool   $remember
	 * @return array
	 */
	public function verify_login_otp($email, $otp, $remember = FALSE)
	{
		$result = $this->CI->Otp_model->verify($email, $otp, 'login');

		if (is_string($result))
		{
			$messages = array(
				'not_found'         => 'No active code found. Please request a new one.',
				'expired'           => 'That code has expired. Please request a new one.',
				'too_many_attempts' => 'Too many incorrect attempts. Please request a new code.',
				'mismatch'          => 'That code is not correct.',
			);

			return $this->fail($result,
				isset($messages[$result]) ? $messages[$result] : 'Verification failed.');
		}

		$user = $this->CI->User_model->find_for_auth($email);

		if ( ! $user)
		{
			return $this->fail('not_found', 'Account not found.');
		}

		// Signing in by emailed code proves control of the address.
		if ($user->email_verified_at === NULL)
		{
			$this->CI->User_model->mark_email_verified($user->id);
		}

		return $this->force_login($user, $remember);
	}

	// ==================================================================
	// Session state
	// ==================================================================

	/** @return bool */
	public function check()
	{
		return (bool) $this->CI->session->userdata('user_id');
	}

	/** @return object|null */
	public function user()
	{
		$user_id = $this->CI->session->userdata('user_id');

		return $user_id ? $this->CI->User_model->find($user_id) : NULL;
	}

	/** @return int|null */
	public function id()
	{
		$id = $this->CI->session->userdata('user_id');

		return $id ? (int) $id : NULL;
	}

	/** @return string[] */
	public function roles()
	{
		return (array) $this->CI->session->userdata('user_roles');
	}

	/**
	 * @param  string $role
	 * @return bool
	 */
	public function has_role($role)
	{
		return in_array($role, $this->roles(), TRUE);
	}

	/**
	 * Where to send a user straight after signing in.
	 *
	 * Delegates to Acl so the set of back-office roles lives in one place.
	 *
	 * @return string
	 */
	public function redirect_path()
	{
		return $this->CI->acl->is_admin() ? 'admin' : 'account';
	}

	// ==================================================================
	// Passwords
	// ==================================================================

	/**
	 * Hash a plaintext password for storage.
	 *
	 * @param  string $password
	 * @return string
	 */
	public function hash_password($password)
	{
		return password_hash($password, PASSWORD_BCRYPT);
	}

	/**
	 * Verify a plaintext password against a stored hash.
	 *
	 * Accepts bcrypt, and accepts a legacy 32-character MD5 hash from the
	 * original scaffold exactly once — rehashing it to bcrypt on the spot so
	 * no account is stranded by the migration.
	 *
	 * @param  string $password
	 * @param  object $user
	 * @return bool
	 */
	public function verify_password($password, $user)
	{
		$hash = (string) $user->password;

		if (strlen($hash) === 32 && ctype_xdigit($hash))
		{
			if ( ! hash_equals($hash, md5((string) $password)))
			{
				return FALSE;
			}

			$this->CI->User_model->update_password($user->id, $this->hash_password($password));

			return TRUE;
		}

		if ( ! password_verify((string) $password, $hash))
		{
			return FALSE;
		}

		if (password_needs_rehash($hash, PASSWORD_BCRYPT))
		{
			$this->CI->User_model->update_password($user->id, $this->hash_password($password));
		}

		return TRUE;
	}

	// ==================================================================
	// Throttling
	// ==================================================================

	/**
	 * Is this account inside a lockout window?
	 *
	 * @param  object $user
	 * @return bool
	 */
	public function is_locked($user)
	{
		return ! empty($user->locked_until) && strtotime($user->locked_until) > time();
	}

	/**
	 * Has this IP failed too many times recently?
	 *
	 * The IP allowance is deliberately looser than the per-account one, so a
	 * shared office address is not blocked by one careless colleague.
	 *
	 * @return bool
	 */
	public function ip_is_throttled()
	{
		$limit = $this->max_attempts() * 3;

		return $this->CI->Login_attempt_model->recent_failures_for_ip($this->lockout_minutes()) >= $limit;
	}

	/** @return int */
	protected function max_attempts()
	{
		return (int) $this->config_value('max_login_attempts', 5);
	}

	/** @return int */
	protected function lockout_minutes()
	{
		return (int) $this->config_value('lockout_minutes', 15);
	}

	/** @return bool */
	protected function verification_required()
	{
		if (isset($this->CI->settings))
		{
			return $this->CI->settings->get_bool('require_email_verification', FALSE);
		}

		return FALSE;
	}

	// ==================================================================
	// Internals
	// ==================================================================

	/**
	 * Read a key from the security config block.
	 *
	 * @param  string $key
	 * @param  mixed  $default
	 * @return mixed
	 */
	protected function config_value($key, $default = NULL)
	{
		return isset($this->security[$key]) ? $this->security[$key] : $default;
	}

	/**
	 * @param  string      $code
	 * @param  string      $message
	 * @param  object|null $user
	 * @return array
	 */
	protected function ok($code, $message, $user = NULL)
	{
		return array('success' => TRUE, 'code' => $code, 'message' => $message, 'user' => $user);
	}

	/**
	 * @param  string      $code
	 * @param  string      $message
	 * @param  object|null $user
	 * @return array
	 */
	protected function fail($code, $message, $user = NULL)
	{
		return array('success' => FALSE, 'code' => $code, 'message' => $message, 'user' => $user);
	}
}
