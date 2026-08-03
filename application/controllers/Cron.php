<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CLI-only maintenance entry points for production schedulers.
 */
class Cron extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();

		if (!is_cli()) {
			show_404();
		}
	}

	/**
	 * Prune old login-attempt audit rows.
	 *
	 * Usage:
	 *   php index.php cron prune_login_attempts 90
	 *
	 * @param int $days Retention window in days.
	 * @return void
	 */
	public function prune_login_attempts($days = 90)
	{
		$days = max(1, (int) $days);

		$this->load->model('Login_attempt_model');
		$removed = $this->Login_attempt_model->prune($days);

		$this->line('Pruned '.$removed.' login attempt row(s) older than '.$days.' day(s).');
	}

	protected function line($message)
	{
		echo $message.PHP_EOL;
	}
}
