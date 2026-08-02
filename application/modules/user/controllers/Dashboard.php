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

		$this->render('dashboard', array(
			'meta' => seo_meta(array(
				'title'       => seo_title('My Account'),
				'description' => 'Manage your Kupiana account, orders, and addresses.',
				'robots'      => 'noindex,follow',
			)),
		));
	}
}
