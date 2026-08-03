<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Application configuration.
 *
 * Values here are build-time defaults. Anything an administrator can change at
 * runtime belongs in the `settings` table (Phase 2) and is read through the
 * Settings library, which falls back to these values.
 *
 * @package Kupiana\Config
 */

/* Brand ---------------------------------------------------------------- */
$config['app'] = array(
	'name'        => 'Kupiana',
	'tagline'     => 'Organic spices and oils, delivered.',
	'version'     => '1.0.0',
	'logo'        => 'public/assets/images/kupiana-logo-512.png',
	'favicon'     => 'public/assets/images/favicon.png',
	'support_email' => 'support@kupiana.test',
	'support_phone' => '+91 00000 00000',
);

/* Currency & locale ---------------------------------------------------- */
$config['currency'] = array(
	'code'      => 'INR',
	'symbol'    => '₹',
	'position'  => 'before',
	'decimals'  => 2,
	'thousand_separator' => ',',
	'decimal_separator'  => '.',
	'locale'    => 'en_IN',
);

// Defined and applied in config/constants.php, which loads early enough to
// affect every date() call in the request.
$config['timezone']      = APP_TIMEZONE;
$config['date_format']   = 'd M Y';
$config['time_format']   = 'h:i A';
$config['datetime_format'] = 'd M Y, h:i A';

/* Pagination ----------------------------------------------------------- */
$config['per_page']          = 15;
$config['per_page_options']  = array(15, 25, 50, 100);
$config['store_per_page']    = 12;

/* Uploads -------------------------------------------------------------- */
$config['upload'] = array(
	'base_path'     => FCPATH.'public/uploads/',
	'base_url'      => 'public/uploads/',
	'max_size_kb'   => 4096,
	'image_types'   => 'jpg|jpeg|png|webp|gif|svg',
	'document_types' => 'pdf|doc|docx|xls|xlsx|csv',
	'paths'         => array(
		'products'    => 'products/',
		'categories'  => 'categories/',
		'brands'      => 'brands/',
		'banners'     => 'banners/',
		'users'       => 'users/',
		'blog'        => 'blog/',
		'testimonials' => 'testimonials/',
		'invoices'    => 'invoices/',
		'imports'     => 'imports/',
		'backups'     => 'backups/',
	),
	'thumbnails'    => array(
		'thumb'  => array('width' => 150, 'height' => 150),
		'small'  => array('width' => 300, 'height' => 300),
		'medium' => array('width' => 600, 'height' => 600),
		'large'  => array('width' => 1200, 'height' => 1200),
	),
);

/* Order lifecycle ------------------------------------------------------ */
$config['order_statuses'] = array(
	'pending'    => array('label' => 'Pending',    'badge' => 'secondary', 'icon' => 'fa-clock'),
	'confirmed'  => array('label' => 'Confirmed',  'badge' => 'info',      'icon' => 'fa-circle-check'),
	'processing' => array('label' => 'Processing', 'badge' => 'primary',   'icon' => 'fa-gears'),
	'packed'     => array('label' => 'Packed',     'badge' => 'primary',   'icon' => 'fa-box'),
	'shipped'    => array('label' => 'Shipped',    'badge' => 'info',      'icon' => 'fa-truck-fast'),
	'out_for_delivery' => array('label' => 'Out for Delivery', 'badge' => 'info', 'icon' => 'fa-truck-ramp-box'),
	'delivered'  => array('label' => 'Delivered',  'badge' => 'success',   'icon' => 'fa-circle-check'),
	'cancelled'  => array('label' => 'Cancelled',  'badge' => 'danger',    'icon' => 'fa-ban'),
	'returned'   => array('label' => 'Returned',   'badge' => 'warning',   'icon' => 'fa-rotate-left'),
	'refunded'   => array('label' => 'Refunded',   'badge' => 'dark',      'icon' => 'fa-indian-rupee-sign'),
);

$config['payment_statuses'] = array(
	'pending'  => array('label' => 'Pending',  'badge' => 'secondary'),
	'paid'     => array('label' => 'Paid',     'badge' => 'success'),
	'failed'   => array('label' => 'Failed',   'badge' => 'danger'),
	'refunded' => array('label' => 'Refunded', 'badge' => 'dark'),
	'partially_refunded' => array('label' => 'Partially Refunded', 'badge' => 'warning'),
);

$config['payment_methods'] = array(
	'razorpay' => 'Razorpay',
	'cod'      => 'Cash on Delivery',
	'wallet'   => 'Wallet',
	'bank_transfer' => 'Bank Transfer',
);

$config['shipment_statuses'] = array(
	'pending' => array('label' => 'Pending', 'badge' => 'secondary'),
	'packed' => array('label' => 'Packed', 'badge' => 'primary'),
	'picked_up' => array('label' => 'Picked Up', 'badge' => 'info'),
	'in_transit' => array('label' => 'In Transit', 'badge' => 'info'),
	'out_for_delivery' => array('label' => 'Out for Delivery', 'badge' => 'info'),
	'delivered' => array('label' => 'Delivered', 'badge' => 'success'),
	'failed' => array('label' => 'Failed Attempt', 'badge' => 'danger'),
	'returned' => array('label' => 'Returned', 'badge' => 'warning'),
);

$config['return_statuses'] = array(
	'requested' => array('label' => 'Requested', 'badge' => 'secondary'),
	'approved' => array('label' => 'Approved', 'badge' => 'info'),
	'rejected' => array('label' => 'Rejected', 'badge' => 'danger'),
	'picked_up' => array('label' => 'Picked Up', 'badge' => 'info'),
	'received' => array('label' => 'Received', 'badge' => 'primary'),
	'completed' => array('label' => 'Completed', 'badge' => 'success'),
	'cancelled' => array('label' => 'Cancelled', 'badge' => 'dark'),
);

/* Generic record statuses ---------------------------------------------- */
$config['record_statuses'] = array(
	'active'   => array('label' => 'Active',   'badge' => 'success'),
	'inactive' => array('label' => 'Inactive', 'badge' => 'secondary'),
	'draft'    => array('label' => 'Draft',    'badge' => 'warning'),
	'archived' => array('label' => 'Archived', 'badge' => 'dark'),
);

/* Inventory ------------------------------------------------------------ */
$config['inventory'] = array(
	'low_stock_threshold' => 10,
	'allow_backorder'     => FALSE,
	'reserve_on_order'    => TRUE,
);

/* Tax ------------------------------------------------------------------ */
$config['tax'] = array(
	'enabled'        => TRUE,
	'prices_include_tax' => FALSE,
	'default_rate'   => 18.00,
	'gst_enabled'    => TRUE,
	'origin_state_code' => '29',
);

/* Mail ----------------------------------------------------------------- *
| Transactional email goes through the ZeptoMail (Zoho) HTTP API.
|
| The API key is read from .env and must NEVER be committed. When the key is
| empty or `enabled` is FALSE, Mailer writes the message to application/logs
| instead of sending, so local development needs no credentials.
|
| An administrator can override the key and sender from Admin > Settings; the
| `settings` table wins over these values.
*/
$config['mail'] = array(
	'driver'       => 'zeptomail',
	'api_url'      => kupiana_env('ZEPTOMAIL_API_URL', 'https://api.zeptomail.in/v1.1/email'),
	'api_key'      => kupiana_env('ZEPTOMAIL_API_KEY', ''),
	'from_address' => kupiana_env('MAIL_FROM_ADDRESS', 'noreply@kupiana.test'),
	'from_name'    => kupiana_env('MAIL_FROM_NAME', 'Kupiana'),
	'enabled'      => in_array(strtolower((string) kupiana_env('MAIL_ENABLED', 'false')), array('1', 'true', 'yes', 'on'), TRUE),
);

/* Security ------------------------------------------------------------- */
$config['security'] = array(
	'password_min_length'   => 8,
	'password_algo'         => PASSWORD_BCRYPT,
	'max_login_attempts'    => 5,
	'lockout_minutes'       => 15,
	'reset_token_ttl_min'   => 60,
	'otp_length'            => 6,
	'otp_ttl_min'           => 10,
	'remember_me_days'      => 30,
	'session_idle_minutes'  => 120,
);

/* Assets --------------------------------------------------------------- */
$config['assets'] = array(
	'bootstrap_css' => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
	'bootstrap_js'  => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
	'fontawesome'   => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css',
	'jquery'        => 'https://code.jquery.com/jquery-3.7.1.min.js',
	'chartjs'       => 'https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js',
	'google_fonts'  => 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
);
