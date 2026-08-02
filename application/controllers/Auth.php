<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Auth
 *
 * Every public authentication flow: sign in, sign out, register, verify email,
 * forgot/reset password and OTP sign-in.
 *
 * The controller stays thin — validation and redirects only. All the rules
 * live in Auth_service so the same logic can back a REST API later.
 *
 * @package Kupiana\Controllers
 */
class Auth extends Store_Controller
{
	public function __construct()
	{
		parent::__construct();

		$this->meta(array('robots' => 'noindex,follow'));
	}

	// ==================================================================
	// Sign in / out
	// ==================================================================

	/**
	 * Password sign-in.
	 *
	 * @return void
	 */
	public function login()
	{
		if ($this->auth->check())
		{
			redirect($this->auth->redirect_path());
		}

		if ($this->input->method() === 'post')
		{
			$this->form_validation->set_rules('email', 'Email', 'required|valid_email|trim');
			$this->form_validation->set_rules('password', 'Password', 'required');

			if ($this->form_validation->run())
			{
				$result = $this->auth->attempt(
					$this->input->post('email', TRUE),
					$this->input->post('password'),
					(bool) $this->input->post('remember')
				);

				if ($result['success'])
				{
					$this->flash('success', 'Welcome back, '.$result['user']->first_name.'.');
					redirect($this->safe_redirect($this->auth->redirect_path()));
				}

				$this->flash('error', $result['message']);
				redirect('login'.$this->redirect_query());
			}
		}

		$this->render('auth/login', array(
			'meta' => seo_meta(array(
				'title'       => seo_title('Sign In'),
				'description' => 'Sign in to your Kupiana account.',
				'robots'      => 'noindex,follow',
			)),
		));
	}

	/**
	 * Sign out.
	 *
	 * @return void
	 */
	public function logout()
	{
		$this->auth->logout();
		$this->flash('success', 'You have been signed out.');
		redirect('login');
	}

	// ==================================================================
	// Registration
	// ==================================================================

	/**
	 * Create a customer account.
	 *
	 * @return void
	 */
	public function register()
	{
		if ($this->auth->check())
		{
			redirect($this->auth->redirect_path());
		}

		if ($this->input->method() === 'post')
		{
			$min = (int) array_get(app_config('security', array()), 'password_min_length', 8);

			$this->form_validation->set_rules('first_name', 'First name', 'required|trim|max_length[100]');
			$this->form_validation->set_rules('last_name', 'Last name', 'trim|max_length[100]');
			$this->form_validation->set_rules('email', 'Email', 'required|valid_email|trim|max_length[191]|is_unique[users.email]');
			$this->form_validation->set_rules('phone', 'Phone', 'trim|numeric|exact_length[10]');
			$this->form_validation->set_rules('password', 'Password', 'required|min_length['.$min.']|max_length[72]');
			$this->form_validation->set_rules('password_confirm', 'Confirm password', 'required|matches[password]');
			$this->form_validation->set_rules('terms', 'Terms', 'required');

			$this->form_validation->set_message('is_unique', 'An account with this email already exists.');
			$this->form_validation->set_message('required', 'The {field} field is required.');

			if ($this->form_validation->run())
			{
				$result = $this->auth->register(array(
					'first_name' => $this->input->post('first_name', TRUE),
					'last_name'  => $this->input->post('last_name', TRUE),
					'email'      => $this->input->post('email', TRUE),
					'phone'      => $this->input->post('phone', TRUE),
					'password'   => $this->input->post('password'),
				));

				if ($result['success'])
				{
					$this->flash('success', $result['message']);
					redirect('login');
				}

				$this->flash('error', $result['message']);
			}
		}

		$this->render('auth/register', array(
			'meta' => seo_meta(array(
				'title'       => seo_title('Create Account'),
				'description' => 'Create your Kupiana account.',
				'robots'      => 'noindex,follow',
			)),
		));
	}

	// ==================================================================
	// Email verification
	// ==================================================================

	/**
	 * Consume a verification link.
	 *
	 * @return void
	 */
	public function verify_email()
	{
		$result = $this->auth->verify_email($this->input->get('token', TRUE));

		$this->flash($result['success'] ? 'success' : 'error', $result['message']);

		redirect('login');
	}

	/**
	 * Re-send a verification link.
	 *
	 * @return void
	 */
	public function resend_verification()
	{
		$email = $this->input->method() === 'post'
			? $this->input->post('email', TRUE)
			: $this->input->get('email', TRUE);

		$user = $this->User_model->find_by_email((string) $email);

		// Same generic answer whether or not the account exists, so this
		// endpoint cannot be used to discover registered addresses.
		if ($user && $user->email_verified_at === NULL && $user->status === 'active')
		{
			$this->auth->send_verification_email($user);
		}

		$this->flash('success', 'If that account needs verifying, a new link is on its way.');

		redirect('login');
	}

	// ==================================================================
	// Password reset
	// ==================================================================

	/**
	 * Request a reset link.
	 *
	 * @return void
	 */
	public function forgot_password()
	{
		if ($this->auth->check())
		{
			redirect($this->auth->redirect_path());
		}

		if ($this->input->method() === 'post')
		{
			$this->form_validation->set_rules('email', 'Email', 'required|valid_email|trim');

			if ($this->form_validation->run())
			{
				$result = $this->auth->send_password_reset($this->input->post('email', TRUE));

				$this->flash($result['success'] ? 'success' : 'error', $result['message']);

				if ($result['success'])
				{
					redirect('login');
				}
			}
		}

		$this->render('auth/forgot_password', array(
			'meta' => seo_meta(array(
				'title'       => seo_title('Forgot Password'),
				'description' => 'Reset your Kupiana password.',
				'robots'      => 'noindex,nofollow',
			)),
		));
	}

	/**
	 * Set a new password from a reset link.
	 *
	 * @return void
	 */
	public function reset_password()
	{
		if ($this->auth->check())
		{
			redirect($this->auth->redirect_path());
		}

		// The token travels in the query string on GET and in a hidden field
		// on POST, so it never lands in the browser history after submission.
		$token = $this->input->method() === 'post'
			? $this->input->post('token', TRUE)
			: $this->input->get('token', TRUE);

		if ( ! $this->auth->find_reset($token))
		{
			$this->flash('error', 'This reset link is invalid or has expired. Please request a new one.');
			redirect('forgot-password');
		}

		if ($this->input->method() === 'post')
		{
			$min = (int) array_get(app_config('security', array()), 'password_min_length', 8);

			$this->form_validation->set_rules('password', 'Password', 'required|min_length['.$min.']|max_length[72]');
			$this->form_validation->set_rules('password_confirm', 'Confirm password', 'required|matches[password]');

			if ($this->form_validation->run())
			{
				$result = $this->auth->reset_password($token, $this->input->post('password'));

				$this->flash($result['success'] ? 'success' : 'error', $result['message']);

				redirect($result['success'] ? 'login' : 'forgot-password');
			}
		}

		$this->render('auth/reset_password', array(
			'token' => $token,
			'meta'  => seo_meta(array(
				'title'       => seo_title('Reset Password'),
				'description' => 'Choose a new password.',
				'robots'      => 'noindex,nofollow',
			)),
		));
	}

	// ==================================================================
	// OTP sign-in
	// ==================================================================

	/**
	 * Passwordless sign-in: request a code, then submit it.
	 *
	 * @return void
	 */
	public function otp()
	{
		if ($this->auth->check())
		{
			redirect($this->auth->redirect_path());
		}

		$stage = 'request';
		$email = (string) $this->input->post('email', TRUE);

		if ($this->input->method() === 'post')
		{
			$action = $this->input->post('action', TRUE);

			if ($action === 'verify')
			{
				$this->form_validation->set_rules('email', 'Email', 'required|valid_email|trim');
				$this->form_validation->set_rules('otp', 'Code', 'required|trim|numeric|exact_length[6]');

				if ($this->form_validation->run())
				{
					$result = $this->auth->verify_login_otp(
						$email,
						$this->input->post('otp', TRUE),
						(bool) $this->input->post('remember')
					);

					if ($result['success'])
					{
						$this->flash('success', 'Signed in successfully.');
						redirect($this->safe_redirect($this->auth->redirect_path()));
					}

					$this->flash('error', $result['message']);
				}

				$stage = 'verify';
			}
			else
			{
				$this->form_validation->set_rules('email', 'Email', 'required|valid_email|trim');

				if ($this->form_validation->run())
				{
					$result = $this->auth->send_login_otp($email);

					$this->flash($result['success'] ? 'success' : 'error', $result['message']);

					$stage = $result['success'] ? 'verify' : 'request';
				}
			}
		}

		$this->render('auth/otp', array(
			'stage' => $stage,
			'email' => $email,
			'meta'  => seo_meta(array(
				'title'       => seo_title('Sign in with a code'),
				'description' => 'Sign in to Kupiana with a one-time code.',
				'robots'      => 'noindex,nofollow',
			)),
		));
	}

	// ==================================================================
	// Helpers
	// ==================================================================

	/**
	 * Resolve the post-login destination.
	 *
	 * Only relative, single-segment-rooted paths are honoured. Without this,
	 * `?redirect=https://evil.test` would turn the login page into an open
	 * redirect that phishers could point at their own site.
	 *
	 * @param  string $fallback
	 * @return string
	 */
	protected function safe_redirect($fallback)
	{
		$target = (string) $this->input->get('redirect', TRUE);

		if ($target === '')
		{
			return $fallback;
		}

		$target = ltrim(rawurldecode($target), '/');

		// Allow-list rather than deny-list: the destination must look like an
		// internal path, optionally with a simple query string. This rejects
		// absolute URLs, protocol-relative "//evil.test", traversal, CRLF
		// injection and anything CI's XSS filter has already mangled — without
		// needing to enumerate every hostile shape.
		if ( ! preg_match('~^[A-Za-z0-9][A-Za-z0-9/_\-]*(\?[A-Za-z0-9_\-=&%.+]*)?$~', $target))
		{
			return $fallback;
		}

		if (strpos($target, '..') !== FALSE)
		{
			return $fallback;
		}

		return $target;
	}

	/**
	 * Preserve ?redirect= across a failed login POST.
	 *
	 * @return string
	 */
	protected function redirect_query()
	{
		$target = (string) $this->input->get('redirect', TRUE);

		return $target === '' ? '' : '?redirect='.rawurlencode($target);
	}
}
