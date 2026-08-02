<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Mailer
 *
 * Transactional email through the ZeptoMail (Zoho) HTTP API.
 *
 * Bodies are rendered from the `email_templates` table so an administrator can
 * edit copy in Phase 4 without a deploy. Placeholders use {{double_braces}}.
 *
 * Usage:
 *     $this->mailer->send_template('password_reset', $email, $name, array(
 *         'first_name'     => 'Asha',
 *         'reset_url'      => site_url('reset-password?token=...'),
 *         'expiry_minutes' => 60,
 *     ));
 *
 * Credentials come from .env (ZEPTOMAIL_API_KEY), never from source control.
 * When no key is configured, or MAIL_ENABLED is false, the message is written
 * to application/logs instead of being sent, so local development works
 * without credentials.
 *
 * @package Kupiana\Libraries
 */
class Mailer
{
	/** @var CI_Controller */
	protected $CI;

	/** @var string */
	protected $api_key;

	/** @var string */
	protected $api_url;

	/** @var string */
	protected $from_address;

	/** @var string */
	protected $from_name;

	/** @var bool When FALSE, messages are logged rather than sent. */
	protected $enabled;

	/** @var string|null Populated when a send fails. */
	protected $last_error = NULL;

	public function __construct()
	{
		$this->CI =& get_instance();
		$this->CI->config->load('app', TRUE);

		$mail = (array) $this->CI->config->item('mail', 'app');

		$this->api_key      = (string) $this->setting('zeptomail_api_key', $this->value($mail, 'api_key', ''));
		$this->api_url      = (string) $this->value($mail, 'api_url', 'https://api.zeptomail.in/v1.1/email');
		$this->from_address = (string) $this->setting('mail_from_address', $this->value($mail, 'from_address', ''));
		$this->from_name    = (string) $this->setting('mail_from_name', $this->value($mail, 'from_name', 'Kupiana'));
		$this->enabled      = (bool) $this->value($mail, 'enabled', FALSE);
	}

	// ------------------------------------------------------------------
	// Public API
	// ------------------------------------------------------------------

	/**
	 * Render a template from `email_templates` and send it.
	 *
	 * @param  string $code       Template code, e.g. 'password_reset'.
	 * @param  string $to_email
	 * @param  string $to_name
	 * @param  array  $vars       Placeholder replacements.
	 * @return bool
	 */
	public function send_template($code, $to_email, $to_name = '', array $vars = array())
	{
		$template = $this->CI->db
			->where('code', $code)
			->where('status', 'active')
			->where('deleted_at IS NULL', NULL, FALSE)
			->limit(1)
			->get('email_templates')
			->row();

		if ( ! $template)
		{
			$this->last_error = 'Email template "'.$code.'" was not found.';
			log_message('error', 'Mailer: '.$this->last_error);
			return FALSE;
		}

		// Values every template may reference.
		$vars = array_merge($this->global_vars(), $vars);

		$subject = $this->replace($template->subject, $vars);
		$body    = $this->wrap($this->replace($template->body, $vars), $subject);

		return $this->send($to_email, $to_name, $subject, $body);
	}

	/**
	 * Send an already-rendered message.
	 *
	 * @param  string $to_email
	 * @param  string $to_name
	 * @param  string $subject
	 * @param  string $html_body
	 * @return bool
	 */
	public function send($to_email, $to_name, $subject, $html_body)
	{
		$this->last_error = NULL;

		$to_email = trim((string) $to_email);

		if ($to_email === '' || ! filter_var($to_email, FILTER_VALIDATE_EMAIL))
		{
			$this->last_error = 'A valid recipient address is required.';
			log_message('error', 'Mailer: '.$this->last_error);
			return FALSE;
		}

		// No credentials, or sending switched off: log and report success so
		// that registration and password-reset flows still complete locally.
		if ( ! $this->enabled || $this->api_key === '')
		{
			log_message('info', sprintf(
				"Mailer (not sent — MAIL_ENABLED=%s, api_key=%s)\nTo: %s <%s>\nSubject: %s\n%s",
				$this->enabled ? 'true' : 'false',
				$this->api_key === '' ? 'missing' : 'set',
				$to_name, $to_email, $subject, $html_body
			));

			return TRUE;
		}

		$payload = array(
			'from' => array(
				'address' => $this->from_address,
				'name'    => $this->from_name,
			),
			'to' => array(
				array(
					'email_address' => array(
						'address' => $to_email,
						'name'    => $to_name !== '' ? $to_name : $to_email,
					),
				),
			),
			'subject'  => $subject,
			'htmlbody' => $html_body,
		);

		return $this->post($payload, $to_email);
	}

	/**
	 * The error from the most recent failed send, if any.
	 *
	 * @return string|null
	 */
	public function last_error()
	{
		return $this->last_error;
	}

	// ------------------------------------------------------------------
	// Transport
	// ------------------------------------------------------------------

	/**
	 * POST a payload to the ZeptoMail API.
	 *
	 * @param  array  $payload
	 * @param  string $recipient For log context only.
	 * @return bool
	 */
	protected function post(array $payload, $recipient)
	{
		$curl = curl_init();

		curl_setopt_array($curl, array(
			CURLOPT_URL            => $this->api_url,
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_ENCODING       => '',
			CURLOPT_MAXREDIRS      => 10,
			CURLOPT_TIMEOUT        => 30,
			CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST  => 'POST',
			CURLOPT_POSTFIELDS     => json_encode($payload),
			CURLOPT_HTTPHEADER     => array(
				'accept: application/json',
				'authorization: Zoho-enczapikey '.$this->api_key,
				'cache-control: no-cache',
				'content-type: application/json',
			),
		));

		$response  = curl_exec($curl);
		$err       = curl_error($curl);
		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		curl_close($curl);

		if ($err)
		{
			$this->last_error = 'cURL error: '.$err;
			log_message('error', 'Mailer cURL error for '.$recipient.': '.$err);
			return FALSE;
		}

		if ($http_code < 200 || $http_code >= 300)
		{
			$this->last_error = 'ZeptoMail responded with HTTP '.$http_code;
			log_message('error', 'Mailer failed with HTTP '.$http_code.' for '.$recipient.'. Response: '.$response);
			return FALSE;
		}

		log_message('info', 'Mailer sent to '.$recipient.' (HTTP '.$http_code.')');

		return TRUE;
	}

	// ------------------------------------------------------------------
	// Rendering
	// ------------------------------------------------------------------

	/**
	 * Substitute {{placeholders}}. Values are HTML-escaped unless the key ends
	 * in `_html`, so template copy cannot be used to inject markup.
	 *
	 * @param  string $subject
	 * @param  array  $vars
	 * @return string
	 */
	protected function replace($subject, array $vars)
	{
		$search  = array();
		$replace = array();

		foreach ($vars as $key => $value)
		{
			if (is_array($value) || is_object($value))
			{
				continue;
			}

			$search[]  = '{{'.$key.'}}';
			$replace[] = (substr($key, -5) === '_html') ? (string) $value : html_escape((string) $value);
		}

		return str_replace($search, $replace, (string) $subject);
	}

	/**
	 * Wrap rendered body copy in the branded HTML email shell.
	 *
	 * @param  string $body_html
	 * @param  string $subject
	 * @return string
	 */
	protected function wrap($body_html, $subject)
	{
		return $this->CI->load->view('email/layout', array(
			'subject'   => $subject,
			'body_html' => $body_html,
			'site_name' => $this->setting('site_name', 'Kupiana'),
			'site_url'  => site_url(),
			'support_email' => $this->setting('support_email', $this->from_address),
			'year'      => date('Y'),
		), TRUE);
	}

	/**
	 * Placeholders available to every template.
	 *
	 * @return array
	 */
	protected function global_vars()
	{
		return array(
			'site_name'     => $this->setting('site_name', 'Kupiana'),
			'site_url'      => site_url(),
			'login_url'     => site_url('login'),
			'support_email' => $this->setting('support_email', $this->from_address),
			'year'          => date('Y'),
		);
	}

	// ------------------------------------------------------------------
	// Internals
	// ------------------------------------------------------------------

	/**
	 * Read a runtime setting, falling back to a default.
	 *
	 * @param  string $key
	 * @param  mixed  $default
	 * @return mixed
	 */
	protected function setting($key, $default = NULL)
	{
		if ( ! isset($this->CI->settings))
		{
			return $default;
		}

		$value = $this->CI->settings->get($key, $default);

		return ($value === NULL || $value === '') ? $default : $value;
	}

	/**
	 * Null-safe array read.
	 *
	 * @param  array  $array
	 * @param  string $key
	 * @param  mixed  $default
	 * @return mixed
	 */
	protected function value($array, $key, $default = NULL)
	{
		return (is_array($array) && isset($array[$key])) ? $array[$key] : $default;
	}
}
