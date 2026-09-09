<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * The signed-in staff member's own profile: personal details, avatar and
 * password. Linked from the admin topbar's "My Profile" menu item.
 */
class Profile extends Admin_Controller
{
	/**
	 * Personal details form.
	 *
	 * @return void
	 */
	public function index()
	{
		$user_id = (int) $this->auth->id();
		$this->load->model('User_model');
		$user = $this->User_model->find($user_id);
		if ( ! $user) { show_404(); }

		if ($this->input->method(TRUE) === 'POST')
		{
			$email = strtolower(trim((string) $this->input->post('email', TRUE)));
			$existing = $this->User_model->find_by_email($email);

			$this->form_validation->set_rules('first_name', 'First Name', 'required|max_length[100]');
			$this->form_validation->set_rules('last_name', 'Last Name', 'max_length[100]');
			$this->form_validation->set_rules('email', 'Email', 'required|valid_email|max_length[191]');
			$this->form_validation->set_rules('phone', 'Phone', 'max_length[20]');

			if ($existing && (int) $existing->id !== $user_id)
			{
				$this->session->set_flashdata('error', 'That email address is already in use.');
				redirect('admin/profile');
			}

			if ($this->form_validation->run() === TRUE)
			{
				$data = array(
					'first_name' => $this->input->post('first_name', TRUE),
					'last_name' => $this->input->post('last_name', TRUE),
					'email' => $email,
					'phone' => $this->input->post('phone', TRUE),
				);

				include_once APPPATH.'libraries/Upload.php';
				$uploader = new Upload();
				$upload = $uploader->image('avatar', 'users');
				if ($upload !== FALSE) { $data['avatar'] = 'users/'.$upload['name']; }

				$this->User_model->update($user_id, $data);
				$this->session->set_userdata(array('user_email' => $email, 'user_name' => trim($data['first_name'].' '.$data['last_name'])));
				$this->audit->log('profile_updated', 'users', $user_id, 'Staff profile updated.');
				$this->session->set_flashdata('success', 'Profile updated.');
			}
			else
			{
				$this->session->set_flashdata('error', strip_tags(validation_errors()));
			}

			redirect('admin/profile');
		}

		$this->breadcrumb('My Profile');
		$this->render('profile', array(
			'page_title' => 'My Profile',
			'user' => $user,
		));
	}

	/**
	 * Change password.
	 *
	 * @return void
	 */
	public function password()
	{
		$user_id = (int) $this->auth->id();

		$this->form_validation->set_rules('current_password', 'Current Password', 'required');
		$this->form_validation->set_rules('new_password', 'New Password', 'required|min_length[8]');
		$this->form_validation->set_rules('confirm_password', 'Confirm Password', 'required|matches[new_password]');

		if ($this->form_validation->run() !== TRUE)
		{
			$this->session->set_flashdata('error', strip_tags(validation_errors()));
			redirect('admin/profile');
		}

		$result = $this->auth->change_password(
			$user_id,
			$this->input->post('current_password', TRUE),
			$this->input->post('new_password', TRUE)
		);

		$this->session->set_flashdata($result['success'] ? 'success' : 'error', $result['message']);
		redirect('admin/profile');
	}
}
