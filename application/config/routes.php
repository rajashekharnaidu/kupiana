<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/userguide3/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
$route['default_controller'] = 'home';

/* Authentication (Phase 3) --------------------------------------------- */
$route['login']               = 'auth/login';
$route['login/otp']           = 'auth/otp';
$route['logout']              = 'auth/logout';
$route['register']            = 'auth/register';
$route['verify-email']        = 'auth/verify_email';
$route['resend-verification'] = 'auth/resend_verification';
$route['forgot-password']     = 'auth/forgot_password';
$route['reset-password']      = 'auth/reset_password';
$route['admin'] = 'admin/dashboard';
$route['admin/dashboard/chart_data'] = 'admin/dashboard/chart_data';
$route['admin/products/manage/(:num)'] = 'admin/products/manage/$1';
$route['admin/products/relations/(:num)'] = 'admin/products/save_relations/$1';
$route['admin/products/upload-image/(:num)'] = 'admin/products/upload_image/$1';
$route['admin/products/delete-image/(:num)/(:num)'] = 'admin/products/delete_image/$1/$2';
$route['admin/products/variant-attributes/(:num)/(:num)'] = 'admin/products/save_variant_attributes/$1/$2';
$route['admin/orders/view/(:num)'] = 'admin/orders/view/$1';
$route['admin/orders/status/(:num)'] = 'admin/orders/status/$1';
$route['admin/orders/invoice/create/(:num)'] = 'admin/orders/create_invoice/$1';
$route['admin/invoices/download/(:num)'] = 'admin/orders/invoice/$1';
$route['admin/shipments/tracking/(:num)'] = 'admin/orders/tracking/$1';
$route['admin/tracking'] = 'admin/tracking/index';
$route['admin/tracking/view/(:num)'] = 'admin/tracking/view/$1';
$route['admin/tracking/update/(:num)'] = 'admin/tracking/update/$1';
$route['admin/tracking/event/(:num)'] = 'admin/tracking/event/$1';
$route['admin/returns/view/(:num)'] = 'admin/returns/view/$1';
$route['admin/returns/status/(:num)'] = 'admin/returns/status/$1';
$route['admin/backups'] = 'admin/backups/index';
$route['admin/backups/create'] = 'admin/backups/create';
$route['admin/backups/download/(:num)'] = 'admin/backups/download/$1';
$route['admin/backups/restore/(:num)'] = 'admin/backups/restore/$1';
$route['admin/audit-logs'] = 'admin/audit_logs/index';
$route['admin/audit-logs/export'] = 'admin/audit_logs/export';
$route['admin/payments/logs'] = 'admin/crud/index/payment-logs';
$route['admin/payments/razorpay'] = 'admin/crud/index/payments';
$route['admin/payments/view/(:num)'] = 'admin/payments/view/$1';
$route['admin/payments/capture/(:num)'] = 'admin/payments/capture/$1';
$route['admin/payments/refund/(:num)'] = 'admin/payments/refund/$1';
$route['admin/inventory'] = 'admin/inventory/index';
$route['admin/inventory/export'] = 'admin/inventory/export';
$route['admin/inventory/stock-in'] = 'admin/inventory/stock_in';
$route['admin/inventory/stock-out'] = 'admin/inventory/stock_out';
$route['admin/inventory/adjustments'] = 'admin/inventory/adjustment';
$route['admin/inventory/low-stock'] = 'admin/inventory/low_stock';
$route['admin/purchases/create'] = 'admin/inventory/purchase_create';
$route['admin/purchases/view/(:num)'] = 'admin/inventory/purchase_view/$1';
$route['admin/purchases/receive/(:num)'] = 'admin/inventory/purchase_receive/$1';
$route['admin/(:any)/create'] = 'admin/crud/create/$1';
$route['admin/(:any)/edit/(:num)'] = 'admin/crud/edit/$1/$2';
$route['admin/(:any)/delete/(:num)'] = 'admin/crud/delete/$1/$2';
$route['admin/(:any)/bulk'] = 'admin/crud/bulk/$1';
$route['admin/(:any)/export'] = 'admin/crud/export/$1';
$route['admin/reports'] = 'admin/reports/index/sales';
$route['admin/reports/export/(:any)'] = 'admin/reports/export/$1';
$route['admin/reports/(:any)'] = 'admin/reports/index/$1';
$route['admin/settings'] = 'admin/admin_settings/index/general';
$route['admin/settings/shipping'] = 'admin/admin_settings/index/shipping';
$route['admin/settings/payment'] = 'admin/admin_settings/index/payment';
$route['admin/settings/tax'] = 'admin/admin_settings/index/tax';
$route['admin/settings/inventory'] = 'admin/admin_settings/index/inventory';
$route['admin/settings/catalog'] = 'admin/admin_settings/index/catalog';
$route['admin/settings/seo'] = 'admin/admin_settings/index/seo';
$route['admin/settings/mail'] = 'admin/admin_settings/index/mail';
$route['admin/settings/security'] = 'admin/admin_settings/index/security';
$route['admin/templates/email'] = 'admin/crud/index/email-templates';
$route['admin/templates/sms'] = 'admin/crud/index/sms-templates';
$route['admin/settings/backup'] = 'admin/backups/index';
$route['admin/settings/audit'] = 'admin/audit_logs/index';
$route['admin/(:any)/(:any)'] = 'admin/crud/index/$1';
$route['admin/(:any)'] = 'admin/crud/index/$1';
$route['account'] = 'user/dashboard';
$route['account/profile'] = 'user/dashboard/profile';
$route['account/security'] = 'user/dashboard/security';
$route['account/sessions/revoke/(:num)'] = 'user/dashboard/revoke_session/$1';
$route['account/orders'] = 'user/dashboard/orders';
$route['account/orders/(:num)'] = 'user/dashboard/order/$1';
$route['account/orders/cancel/(:num)'] = 'user/dashboard/cancel_order/$1';
$route['account/returns'] = 'user/dashboard/returns';
$route['account/returns/request/(:num)'] = 'user/dashboard/request_return/$1';
$route['account/invoices/(:num)'] = 'user/dashboard/invoice/$1';
$route['account/addresses'] = 'user/dashboard/addresses';
$route['account/addresses/save'] = 'user/dashboard/save_address';
$route['account/addresses/save/(:num)'] = 'user/dashboard/save_address/$1';
$route['account/addresses/delete/(:num)'] = 'user/dashboard/delete_address/$1';
$route['shop'] = 'catalog/products/index';
$route['search'] = 'catalog/products/search';
$route['deals'] = 'catalog/products/deals';
$route['offers'] = 'catalog/products/offers';
$route['brands'] = 'catalog/products/brands';
$route['brand/(:any)'] = 'catalog/products/brand/$1';
$route['cart'] = 'catalog/cart/index';
$route['cart/add'] = 'catalog/cart/add';
$route['cart/update'] = 'catalog/cart/update';
$route['cart/remove/(:num)'] = 'catalog/cart/remove/$1';
$route['wishlist'] = 'catalog/wishlist/index';
$route['wishlist/add/(:num)'] = 'catalog/wishlist/add/$1';
$route['wishlist/remove/(:num)'] = 'catalog/wishlist/remove/$1';
$route['api/search/suggest'] = 'catalog/search/suggest';
$route['contact'] = 'catalog/pages/contact';
$route['track-order'] = 'catalog/pages/track_order';
$route['blog'] = 'catalog/pages/blog';
$route['blog/(:any)'] = 'catalog/pages/blog_post/$1';
$route['checkout'] = 'catalog/checkout/index';
$route['checkout/success/(:num)'] = 'catalog/checkout/success/$1';
$route['payments/razorpay/pay/(:num)'] = 'catalog/payments/pay/$1';
$route['payments/razorpay/verify'] = 'catalog/payments/verify';
$route['payments/razorpay/simulate/(:num)'] = 'catalog/payments/simulate/$1';
$route['payments/failed/(:num)'] = 'catalog/payments/failed/$1';
$route['payments/razorpay/webhook'] = 'catalog/payments/webhook';
$route['tracking/webhook'] = 'catalog/tracking/webhook';
$route['page/(:any)'] = 'catalog/pages/show/$1';
$route['products/(:any)'] = 'catalog/products/detail/$1';
$route['category/(:any)'] = 'catalog/products/category/$1';
$route['robots.txt'] = 'catalog/seo/robots';
$route['sitemap.xml'] = 'catalog/seo/sitemap';
$route['404_override'] = '';
$route['translate_uri_dashes'] = TRUE;
