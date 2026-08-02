<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin dashboard.
 *
 * Phase 1 renders the shell with placeholder tiles so the layout is verifiable.
 * Phase 4 replaces the static figures with live aggregates from a
 * Dashboard_model (revenue, orders, customers, low stock, charts).
 *
 * @package Kupiana\Modules\Admin
 */
class Dashboard extends Admin_Controller
{
	/** @var string Highlights the sidebar entry. */
	protected $active_menu = 'dashboard';

	/** @var string */
	protected $required_permission = 'dashboard.view';

	/**
	 * Dashboard landing page.
	 *
	 * @return void
	 */
	public function index()
	{
		$this->breadcrumb('Dashboard');

		$this->render('dashboard', array(
			'page_title' => 'Dashboard',
		));
	}
}
