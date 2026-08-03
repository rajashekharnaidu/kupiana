<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Customer account dashboard.
 *
 * Phase 5 builds this out fully (profile, addresses, orders, wishlist,
 * reviews, wallet, coupons, returns, downloads, settings).
 *
 * @package Kupiana\Modules\User
 */
class Dashboard extends Store_Controller
{
	/**
	 * Account landing page.
	 *
	 * Requires a signed-in user rather than a specific role: staff accounts
	 * are customers too, and gating on a role slug broke when Phase 2 renamed
	 * the customer role from 'user' to 'customer'.
	 *
	 * @return void
	 */
	public function index()
	{
		$this->require_login();
		$user_id = (int) $this->session->userdata('user_id');

		$this->render('dashboard', array(
			'orders' => $this->orders_for($user_id, 5),
			'addresses' => $this->addresses_for($user_id),
			'wishlist_count' => (int) $this->db->from('wishlists')->where('user_id', $user_id)->where('deleted_at IS NULL', NULL, FALSE)->count_all_results(),
			'wallet' => $this->db->from('wallets')->where('user_id', $user_id)->where('deleted_at IS NULL', NULL, FALSE)->get()->row(),
			'meta' => seo_meta(array(
				'title'       => seo_title('My Account'),
				'description' => 'Manage your Kupiana account, orders, and addresses.',
				'robots'      => 'noindex,follow',
			)),
		));
	}

	public function orders()
	{
		$this->require_login();
		$this->render('orders', array('orders' => $this->orders_for((int) $this->session->userdata('user_id'), 50), 'meta' => seo_meta(array('title' => seo_title('My Orders'), 'robots' => 'noindex,follow'))));
	}

	public function order($id = NULL)
	{
		$this->require_login();
		$order = $this->db->from('orders')->where(array('id' => (int) $id, 'user_id' => (int) $this->session->userdata('user_id')))->where('deleted_at IS NULL', NULL, FALSE)->get()->row();
		if ( ! $order) { show_404(); }
		$items = $this->db->from('order_items')->where('order_id', $order->id)->where('deleted_at IS NULL', NULL, FALSE)->get()->result();
		$history = $this->db->from('order_status_history')->where('order_id', $order->id)->where('deleted_at IS NULL', NULL, FALSE)->order_by('created_at', 'DESC')->get()->result();
		$shipments = $this->db->from('shipments')->where('order_id', $order->id)->where('deleted_at IS NULL', NULL, FALSE)->order_by('created_at', 'DESC')->get()->result();
		$invoices = $this->db->from('invoices')->where('order_id', $order->id)->where('deleted_at IS NULL', NULL, FALSE)->order_by('created_at', 'DESC')->get()->result();
		$this->load->model('Tracking_model', 'tracking_service');
		$returns = $this->db->from('return_requests')->where('order_id', $order->id)->where('deleted_at IS NULL', NULL, FALSE)->order_by('created_at', 'DESC')->get()->result();
		$this->render('order_detail', array('order' => $order, 'items' => $items, 'history' => $history, 'shipments' => $shipments, 'invoices' => $invoices, 'timeline' => $this->tracking_service->order_timeline($order->id), 'returns' => $returns, 'meta' => seo_meta(array('title' => seo_title('Order '.$order->order_number), 'robots' => 'noindex,follow'))));
	}

	public function returns()
	{
		$this->require_login();
		$this->load->model('Tracking_model', 'tracking_service');
		$this->render('returns', array('returns' => $this->tracking_service->returns_for_user((int) $this->session->userdata('user_id')), 'meta' => seo_meta(array('title' => seo_title('Returns'), 'robots' => 'noindex,follow'))));
	}

	public function request_return($id = NULL)
	{
		$this->require_login();
		$user_id = (int) $this->session->userdata('user_id');
		$order = $this->db->from('orders')->where(array('id' => (int) $id, 'user_id' => $user_id))->where('deleted_at IS NULL', NULL, FALSE)->get()->row();
		if ( ! $order) { show_404(); }
		$items = $this->db->from('order_items')->where('order_id', $order->id)->where('deleted_at IS NULL', NULL, FALSE)->get()->result();
		if (strtoupper($this->input->method(TRUE)) === 'POST')
		{
			$this->form_validation->set_rules('reason', 'Reason', 'required|max_length[150]');
			$this->form_validation->set_rules('description', 'Description', 'max_length[1000]');
			$this->form_validation->set_rules('type', 'Type', 'in_list[return,exchange]');
			if ($this->form_validation->run() !== TRUE)
			{
				$this->session->set_flashdata('error', strip_tags(validation_errors()));
				redirect('account/returns/request/'.$order->id);
			}
			$this->load->model('Tracking_model', 'tracking_service');
			$result = $this->tracking_service->create_return_request($order->id, $user_id, $this->input->post(NULL, TRUE));
			$this->session->set_flashdata($result['success'] ? 'success' : 'error', $result['message']);
			redirect($result['success'] ? 'account/returns' : 'account/returns/request/'.$order->id);
		}
		$this->render('return_request', array('order' => $order, 'items' => $items, 'meta' => seo_meta(array('title' => seo_title('Request Return'), 'robots' => 'noindex,follow'))));
	}

	public function cancel_order($id = NULL)
	{
		$this->require_login();
		$order = $this->db->from('orders')->where(array('id' => (int) $id, 'user_id' => (int) $this->session->userdata('user_id')))->where('deleted_at IS NULL', NULL, FALSE)->get()->row();
		if ( ! $order) { show_404(); }
		if (in_array($order->order_status, array('packed', 'shipped', 'out_for_delivery', 'delivered', 'cancelled'), TRUE))
		{
			$this->session->set_flashdata('error', 'This order can no longer be cancelled online.');
			redirect('account/orders/'.$order->id);
		}
		$this->load->model('Order_model', 'orders_service');
		$result = $this->orders_service->update_status($order->id, 'cancelled', $this->input->post('reason', TRUE) ?: 'Cancelled by customer.');
		$this->session->set_flashdata($result['success'] ? 'success' : 'error', $result['message']);
		redirect('account/orders/'.$order->id);
	}

	public function invoice($id = NULL)
	{
		$this->require_login();
		$invoice = $this->db->select('invoices.*')->from('invoices')->join('orders', 'orders.id = invoices.order_id')->where('invoices.id', (int) $id)->where('orders.user_id', (int) $this->session->userdata('user_id'))->where('invoices.deleted_at IS NULL', NULL, FALSE)->where('orders.deleted_at IS NULL', NULL, FALSE)->get()->row();
		if ( ! $invoice) { show_404(); }
		$order = $this->db->from('orders')->where('id', $invoice->order_id)->get()->row();
		$items = $this->db->from('order_items')->where('order_id', $invoice->order_id)->where('deleted_at IS NULL', NULL, FALSE)->get()->result();
		$html = $this->load->view('admin/invoice_print', array('invoice' => $invoice, 'order' => $order, 'items' => $items), TRUE);
		$this->output->set_content_type('text/html', 'utf-8')->set_output($html);
	}

	public function addresses()
	{
		$this->require_login();
		$this->render('addresses', array('addresses' => $this->addresses_for((int) $this->session->userdata('user_id')), 'meta' => seo_meta(array('title' => seo_title('Addresses'), 'robots' => 'noindex,follow'))));
	}

	public function profile()
	{
		$this->require_login();
		$user_id = (int) $this->session->userdata('user_id');
		$user = $this->User_model->find($user_id);
		if ( ! $user) { show_404(); }

		if (strtoupper($this->input->method(TRUE)) === 'POST')
		{
			$email = strtolower(trim((string) $this->input->post('email', TRUE)));
			$existing = $this->User_model->find_by_email($email);

			$this->form_validation->set_rules('first_name', 'First Name', 'required|max_length[100]');
			$this->form_validation->set_rules('last_name', 'Last Name', 'max_length[100]');
			$this->form_validation->set_rules('email', 'Email', 'required|valid_email|max_length[191]');
			$this->form_validation->set_rules('phone', 'Phone', 'max_length[20]');
			$this->form_validation->set_rules('gender', 'Gender', 'in_list[male,female,other,prefer_not_to_say]');
			$this->form_validation->set_rules('date_of_birth', 'Date of Birth', 'max_length[10]');

			if ($existing && (int) $existing->id !== $user_id)
			{
				$this->session->set_flashdata('error', 'That email address is already in use.');
				redirect('account/profile');
			}

			if ($this->form_validation->run() !== TRUE)
			{
				$this->session->set_flashdata('error', strip_tags(validation_errors()));
				redirect('account/profile');
			}

			$this->db->where('id', $user_id)->update('users', array(
				'first_name'    => $this->input->post('first_name', TRUE),
				'last_name'     => $this->input->post('last_name', TRUE),
				'email'         => $email,
				'phone'         => $this->input->post('phone', TRUE),
				'gender'        => $this->input->post('gender', TRUE) ?: NULL,
				'date_of_birth' => $this->input->post('date_of_birth', TRUE) ?: NULL,
				'updated_at'    => date('Y-m-d H:i:s'),
				'updated_by'    => $user_id,
			));

			$this->session->set_userdata(array(
				'user_email' => $email,
				'user_name'  => trim($this->input->post('first_name', TRUE).' '.$this->input->post('last_name', TRUE)),
			));
			if (isset($this->audit)) { $this->audit->log('profile_updated', 'users', $user_id, 'Customer profile updated.'); }

			$this->session->set_flashdata('success', 'Profile updated.');
			redirect('account/profile');
		}

		$this->render('profile', array('user' => $user, 'meta' => seo_meta(array('title' => seo_title('Profile'), 'robots' => 'noindex,follow'))));
	}

	public function security()
	{
		$this->require_login();
		$user_id = (int) $this->session->userdata('user_id');

		if (strtoupper($this->input->method(TRUE)) === 'POST')
		{
			$this->form_validation->set_rules('current_password', 'Current Password', 'required');
			$this->form_validation->set_rules('new_password', 'New Password', 'required|min_length[8]');
			$this->form_validation->set_rules('confirm_password', 'Confirm Password', 'required|matches[new_password]');

			if ($this->form_validation->run() !== TRUE)
			{
				$this->session->set_flashdata('error', strip_tags(validation_errors()));
				redirect('account/security');
			}

			$result = $this->auth->change_password(
				$user_id,
				$this->input->post('current_password', TRUE),
				$this->input->post('new_password', TRUE)
			);

			$this->session->set_flashdata($result['success'] ? 'success' : 'error', $result['message']);
			redirect('account/security');
		}

		$this->load->model('User_session_model');
		$this->render('security', array(
			'sessions' => $this->User_session_model->active_for_user($user_id),
			'meta' => seo_meta(array('title' => seo_title('Security'), 'robots' => 'noindex,follow')),
		));
	}

	public function revoke_session($id = NULL)
	{
		$this->require_login();
		$user_id = (int) $this->session->userdata('user_id');
		$this->load->model('User_session_model');

		$session = $this->db->from('user_sessions')->where(array('id' => (int) $id, 'user_id' => $user_id))->where('revoked_at IS NULL', NULL, FALSE)->get()->row();
		if ($session)
		{
			$this->User_session_model->revoke($session->id);
			if (isset($this->audit)) { $this->audit->log('session_revoked', 'user_sessions', $session->id, 'Customer revoked a remembered device.'); }
			$this->session->set_flashdata('success', 'Device session revoked.');
		}
		else
		{
			$this->session->set_flashdata('error', 'Device session was not found.');
		}

		redirect('account/security');
	}

	public function save_address($id = NULL)
	{
		$this->require_login();
		$this->form_validation->set_rules('first_name', 'First Name', 'required|max_length[100]');
		$this->form_validation->set_rules('phone', 'Phone', 'required|max_length[20]');
		$this->form_validation->set_rules('address_line1', 'Address', 'required|max_length[255]');
		$this->form_validation->set_rules('city', 'City', 'required|max_length[100]');
		$this->form_validation->set_rules('state', 'State', 'required|max_length[100]');
		$this->form_validation->set_rules('postal_code', 'Postal Code', 'required|max_length[20]');
		if ($this->form_validation->run() !== TRUE)
		{
			$this->session->set_flashdata('error', strip_tags(validation_errors()));
			redirect('account/addresses');
		}
		$user_id = (int) $this->session->userdata('user_id');
		$now = date('Y-m-d H:i:s');
		$data = array('user_id' => $user_id, 'type' => $this->input->post('type', TRUE) ?: 'both', 'label' => $this->input->post('label', TRUE), 'first_name' => $this->input->post('first_name', TRUE), 'last_name' => $this->input->post('last_name', TRUE), 'phone' => $this->input->post('phone', TRUE), 'address_line1' => $this->input->post('address_line1', TRUE), 'address_line2' => $this->input->post('address_line2', TRUE), 'city' => $this->input->post('city', TRUE), 'state' => $this->input->post('state', TRUE), 'postal_code' => $this->input->post('postal_code', TRUE), 'country' => 'India', 'is_default' => $this->input->post('is_default', TRUE) ? 1 : 0, 'status' => 'active', 'updated_at' => $now, 'updated_by' => $user_id);
		if ($data['is_default']) { $this->db->where('user_id', $user_id)->update('addresses', array('is_default' => 0)); }
		if ($id) { $this->db->where(array('id' => (int) $id, 'user_id' => $user_id))->update('addresses', $data); }
		else { $data['created_at'] = $now; $data['created_by'] = $user_id; $this->db->insert('addresses', $data); }
		$this->session->set_flashdata('success', 'Address saved.');
		redirect('account/addresses');
	}

	public function delete_address($id = NULL)
	{
		$this->require_login();
		$this->db->where(array('id' => (int) $id, 'user_id' => (int) $this->session->userdata('user_id')))->update('addresses', array('deleted_at' => date('Y-m-d H:i:s')));
		$this->session->set_flashdata('success', 'Address removed.');
		redirect('account/addresses');
	}

	protected function orders_for($user_id, $limit)
	{
		return $this->db->from('orders')->where('user_id', (int) $user_id)->where('deleted_at IS NULL', NULL, FALSE)->order_by('created_at', 'DESC')->limit((int) $limit)->get()->result();
	}

	protected function addresses_for($user_id)
	{
		return $this->db->from('addresses')->where('user_id', (int) $user_id)->where('deleted_at IS NULL', NULL, FALSE)->order_by('is_default', 'DESC')->order_by('id', 'DESC')->get()->result();
	}
}
