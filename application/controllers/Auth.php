<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends MY_Controller
{
	public function login()
	{
		if ($this->auth->check()) {
			redirect($this->auth->redirect_path());
		}

		$this->form_validation->set_rules('email', 'Email', 'required|valid_email|trim');
		$this->form_validation->set_rules('password', 'Password', 'required');

		if ($this->form_validation->run()) {
			if ($this->auth->attempt($this->input->post('email', TRUE), $this->input->post('password'))) {
				$redirect = $this->input->get('redirect', TRUE);
				redirect($redirect ? $redirect : $this->auth->redirect_path());
			}

			$this->session->set_flashdata('error', 'Invalid email or password.');
		}

		$this->render('auth/login', array(
			'meta' => seo_meta(array(
				'title' => seo_title('Login'),
				'description' => 'Sign in to your Kupiana account.',
				'robots' => 'noindex,follow',
			)),
		));
	}

	public function logout()
	{
		$this->auth->logout();
		redirect('login');
	}
}
