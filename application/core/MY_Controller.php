<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Controller hierarchy for Kupiana.
 *
 *   MY_Controller
 *     +-- Admin_Controller   back office, permission gated, admin layout
 *     +-- Store_Controller   public storefront, store layout
 *     +-- Api_Controller     JSON only, no layout
 *
 * All three subclasses live in this file because CodeIgniter only autoloads a
 * single MY_Controller from application/core.
 *
 * @package Kupiana\Core
 */
class MY_Controller extends CI_Controller
{
	/** @var array Data shared with every view rendered by this controller. */
	protected $data = array();

	/** @var string Layout used by render() unless overridden. */
	protected $layout = 'layouts/store';

	public function __construct()
	{
		parent::__construct();

		$this->config->load('app', TRUE);

		// Restore a session from the remember-me cookie before anything reads
		// the current user. Done here rather than through a CI hook so the
		// hooks subsystem can stay disabled — this constructor already runs on
		// every request that renders anything.
		$this->auth->attempt_remembered_login();

		// app.php is loaded as a section, so item() needs the section name as
		// its second argument; item('app') alone would return the whole file.
		$this->data['app']       = (array) $this->config->item('app', 'app');
		$this->data['site_name'] = $this->setting(
			'site_name',
			array_get($this->data['app'], 'name', 'Kupiana')
		);
		$this->data['current_user'] = $this->auth->check() ? $this->auth->user() : NULL;
		$this->data['flash']        = $this->collect_flash();
		$this->data['meta']         = seo_meta(array(
			'title'     => $this->data['site_name'],
			'canonical' => current_url(),
		));
	}

	/**
	 * Render a view inside a layout.
	 *
	 * @param  string       $view
	 * @param  array        $data
	 * @param  string|false $layout Pass FALSE to render without a layout.
	 * @return void
	 */
	protected function render($view, $data = array(), $layout = NULL)
	{
		$layout = ($layout === NULL) ? $this->layout : $layout;

		$this->data = array_merge($this->data, $data);

		if ($layout === FALSE)
		{
			$this->load->view($view, $this->data);
			return;
		}

		$this->data['content'] = $this->load->view($view, $this->data, TRUE);

		$this->load->view($layout, $this->data);
	}

	/**
	 * Set or extend the page meta block.
	 *
	 * @param  array $meta
	 * @return void
	 */
	protected function meta(array $meta)
	{
		$this->data['meta'] = array_merge($this->data['meta'], $meta);
	}

	/**
	 * Read an application setting, falling back to a default.
	 *
	 * @param  string $key
	 * @param  mixed  $default
	 * @return mixed
	 */
	protected function setting($key, $default = NULL)
	{
		if (isset($this->settings))
		{
			return $this->settings->get($key, $default);
		}

		return $default;
	}

	/**
	 * Queue a flash message for the next request.
	 *
	 * @param  string $type One of: success, error, warning, info.
	 * @param  string $message
	 * @return void
	 */
	protected function flash($type, $message)
	{
		$this->session->set_flashdata('flash_'.$type, $message);
	}

	/**
	 * Pull all flash messages into a normalised list for the layout.
	 *
	 * @return array
	 */
	protected function collect_flash()
	{
		$messages = array();

		foreach (array('success', 'error', 'warning', 'info') as $type)
		{
			$message = $this->session->flashdata('flash_'.$type);

			if ( ! empty($message))
			{
				$messages[] = array('type' => $type, 'message' => $message);
			}
		}

		return $messages;
	}

	/**
	 * TRUE when the current request was made with XMLHttpRequest.
	 *
	 * @return bool
	 */
	protected function is_ajax()
	{
		return $this->input->is_ajax_request();
	}

	/**
	 * Send a JSON envelope.
	 *
	 * The current CSRF hash is always attached to `meta`. CodeIgniter is
	 * configured with `csrf_regenerate = TRUE`, so without this the second
	 * AJAX request on a page would be rejected — app.js reads the value back
	 * out via Kupiana.refreshCsrf().
	 *
	 * @param  array $payload
	 * @param  int   $http_code
	 * @return void
	 */
	protected function json(array $payload, $http_code = 200)
	{
		$meta = isset($payload['meta']) ? (array) $payload['meta'] : array();
		$meta['csrf_hash'] = $this->security->get_csrf_hash();

		$payload['meta'] = $meta;

		$this->output
			->set_status_header($http_code)
			->set_content_type('application/json', 'utf-8')
			->set_output(json_encode($payload));
	}

	/**
	 * Require an authenticated session.
	 *
	 * @param  string $redirect_to
	 * @return void
	 */
	protected function require_login($redirect_to = 'login')
	{
		if ($this->auth->check())
		{
			return;
		}

		if ($this->is_ajax())
		{
			$this->json($this->api_response->unauthorized('Your session has expired.'), 401);
			exit;
		}

		$this->flash('error', 'Please sign in to continue.');
		redirect($redirect_to.'?redirect='.rawurlencode(uri_string()));
	}

	/**
	 * Require a role, 403 otherwise.
	 *
	 * @param  string $role
	 * @return void
	 */
	protected function require_role($role)
	{
		$this->require_login();

		if ( ! $this->auth->has_role($role))
		{
			$this->deny('You do not have permission to access this page.');
		}
	}

	/**
	 * Require a permission, 403 otherwise.
	 *
	 * @param  string $permission
	 * @return void
	 */
	protected function require_permission($permission)
	{
		$this->require_login();

		if ( ! $this->acl->can($permission))
		{
			$this->deny('You do not have the "'.$permission.'" permission.');
		}
	}

	/**
	 * Reject the request with 403, respecting AJAX callers.
	 *
	 * @param  string $message
	 * @return void
	 */
	protected function deny($message = 'Access denied.')
	{
		if ($this->is_ajax())
		{
			$this->json($this->api_response->forbidden($message), 403);
			exit;
		}

		show_error($message, 403, 'Access Denied');
	}

	/**
	 * Collect standard listing parameters (search / sort / filter / paginate)
	 * from the query string. Shared by every admin index action.
	 *
	 * @param  array $filter_keys Query keys to treat as filters.
	 * @return array
	 */
	protected function list_params(array $filter_keys = array())
	{
		$per_page_options = $this->config->item('per_page_options', 'app');
		$per_page_options = is_array($per_page_options) ? $per_page_options : array(15, 25, 50, 100);

		$per_page = (int) $this->input->get('per_page', TRUE);
		$per_page = in_array($per_page, $per_page_options, TRUE)
			? $per_page
			: (int) $this->config->item('per_page', 'app');

		$filters = array();

		foreach ($filter_keys as $key)
		{
			$value = $this->input->get($key, TRUE);

			if ($value !== NULL && $value !== '')
			{
				$filters[$key] = $value;
			}
		}

		return array(
			'page'     => max(1, (int) $this->input->get('page', TRUE)),
			'per_page' => $per_page ?: 15,
			'search'   => (string) $this->input->get('q', TRUE),
			'sort'     => $this->input->get('sort', TRUE),
			'order'    => $this->input->get('order', TRUE),
			'filters'  => $filters,
		);
	}
}

/**
 * Base controller for every back-office screen.
 *
 * Subclasses set $required_permission so access control stays declarative:
 *
 *     class Products extends Admin_Controller
 *     {
 *         protected $required_permission = 'products.view';
 *         protected $active_menu         = 'catalog.products';
 *     }
 */
class Admin_Controller extends MY_Controller
{
	/** @var string */
	protected $layout = 'layouts/admin';

	/** @var string|null Permission checked for every action in the controller. */
	protected $required_permission = NULL;

	/** @var string Dot-path used to highlight the sidebar entry. */
	protected $active_menu = '';

	/** @var array Breadcrumb trail. */
	protected $breadcrumbs = array();

	public function __construct()
	{
		parent::__construct();

		$this->require_login('login');

		if ( ! $this->acl->is_admin())
		{
			$this->deny('This area is restricted to staff accounts.');
		}

		if ($this->required_permission !== NULL)
		{
			$this->require_permission($this->required_permission);
		}

		$this->data['active_menu'] = $this->active_menu;
		$this->data['is_admin']    = TRUE;

		$this->meta(array('robots' => 'noindex,nofollow'));
	}

	/**
	 * Add a breadcrumb segment.
	 *
	 * @param  string      $label
	 * @param  string|null $uri
	 * @return $this
	 */
	protected function breadcrumb($label, $uri = NULL)
	{
		$this->breadcrumbs[] = array('label' => $label, 'uri' => $uri);
		return $this;
	}

	/**
	 * Render an admin page, wiring in the page title and breadcrumbs.
	 *
	 * @param  string       $view
	 * @param  array        $data
	 * @param  string|false $layout
	 * @return void
	 */
	protected function render($view, $data = array(), $layout = NULL)
	{
		$title = isset($data['page_title']) ? $data['page_title'] : 'Dashboard';

		$this->data['page_title']  = $title;
		$this->data['breadcrumbs'] = $this->breadcrumbs;

		$this->meta(array('title' => $title.' | '.$this->data['site_name'].' Admin'));

		parent::render($view, $data, $layout);
	}
}

/**
 * Base controller for public storefront pages.
 */
class Store_Controller extends MY_Controller
{
	/** @var string */
	protected $layout = 'layouts/store';

	public function __construct()
	{
		parent::__construct();

		$this->data['is_admin']       = FALSE;
		$this->data['cart_count']     = $this->cart_count();
		$this->data['wishlist_count'] = $this->wishlist_count();
	}

	/**
	 * Number of items in the visitor's cart.
	 * Phase 5 replaces this session stub with the carts table.
	 *
	 * @return int
	 */
	protected function cart_count()
	{
		$cart = $this->session->userdata('cart');

		return is_array($cart) ? count($cart) : 0;
	}

	/**
	 * Number of items in the visitor's wishlist.
	 * Phase 5 replaces this session stub with the wishlists table.
	 *
	 * @return int
	 */
	protected function wishlist_count()
	{
		$wishlist = $this->session->userdata('wishlist');

		return is_array($wishlist) ? count($wishlist) : 0;
	}
}

/**
 * Base controller for JSON endpoints. Never renders a layout and always
 * answers with the Api_response envelope.
 */
class Api_Controller extends MY_Controller
{
	/** @var bool Set FALSE on public endpoints such as search suggestions. */
	protected $require_auth = TRUE;

	/** @var string[] HTTP methods this controller accepts. */
	protected $allowed_methods = array('GET', 'POST');

	public function __construct()
	{
		parent::__construct();

		$this->output->set_content_type('application/json', 'utf-8');

		$method = strtoupper($this->input->method());

		if ( ! in_array($method, $this->allowed_methods, TRUE))
		{
			$this->respond($this->api_response->error('Method not allowed.'), 405);
		}

		if ($this->require_auth)
		{
			$this->require_login();
		}
	}

	/**
	 * Emit a JSON envelope and terminate the request.
	 *
	 * @param  array $payload
	 * @param  int   $http_code
	 * @return void
	 */
	protected function respond(array $payload, $http_code = 200)
	{
		$this->json($payload, $http_code);
		exit;
	}

	/**
	 * Read and decode a JSON request body, falling back to POST fields.
	 *
	 * @return array
	 */
	protected function body()
	{
		$raw = $this->input->raw_input_stream;

		if (empty($raw))
		{
			return (array) $this->input->post(NULL, TRUE);
		}

		$decoded = json_decode($raw, TRUE);

		return is_array($decoded) ? $decoded : array();
	}

	/**
	 * Run form_validation against supplied rules and respond 422 on failure.
	 *
	 * @param  array $rules
	 * @param  array $data
	 * @return bool
	 */
	protected function validate(array $rules, array $data = array())
	{
		if ( ! empty($data))
		{
			$this->form_validation->set_data($data);
		}

		$this->form_validation->set_rules($rules);

		if ($this->form_validation->run() === TRUE)
		{
			return TRUE;
		}

		$this->respond(
			$this->api_response->validation_error($this->form_validation->error_array()),
			422
		);

		return FALSE;
	}
}
