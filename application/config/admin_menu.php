<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin sidebar navigation.
 *
 * The sidebar partial renders this tree and hides any entry the signed-in user
 * lacks permission for, so adding a back-office module is a one-line change
 * here plus the controller itself.
 *
 * Entry shape:
 *   key        unique dot-path, matched against $active_menu for highlighting
 *   label      display text
 *   icon       Font Awesome class
 *   uri        route relative to site_url() (omit for a parent with children)
 *   permission permission slug required to see the entry
 *   children   nested entries
 *
 * @package Kupiana\Config
 */
$config['admin_menu'] = array(

	array(
		'key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'fa-gauge-high',
		'uri' => 'admin', 'permission' => 'dashboard.view',
	),

	array('heading' => 'Catalog'),

	array(
		'key' => 'catalog', 'label' => 'Catalog', 'icon' => 'fa-box-open',
		'permission' => 'products.view',
		'children' => array(
			array('key' => 'catalog.products',   'label' => 'Products',        'uri' => 'admin/products',           'permission' => 'products.view'),
			array('key' => 'catalog.categories', 'label' => 'Categories',      'uri' => 'admin/categories',         'permission' => 'categories.view'),
			array('key' => 'catalog.brands',     'label' => 'Brands',          'uri' => 'admin/brands',             'permission' => 'brands.view'),
			array('key' => 'catalog.attributes', 'label' => 'Attributes',      'uri' => 'admin/attributes',         'permission' => 'attributes.view'),
			array('key' => 'catalog.variants',   'label' => 'Variants & SKUs', 'uri' => 'admin/variants',           'permission' => 'variants.view'),
			array('key' => 'catalog.tags',       'label' => 'Tags',            'uri' => 'admin/tags',               'permission' => 'tags.view'),
			array('key' => 'catalog.reviews',    'label' => 'Reviews',         'uri' => 'admin/reviews',            'permission' => 'reviews.view'),
		),
	),

	array('heading' => 'Sales'),

	array(
		'key' => 'orders', 'label' => 'Orders', 'icon' => 'fa-cart-shopping',
		'permission' => 'orders.view',
		'children' => array(
			array('key' => 'orders.all',        'label' => 'All Orders',    'uri' => 'admin/orders',              'permission' => 'orders.view'),
			array('key' => 'orders.pending',    'label' => 'Pending',       'uri' => 'admin/orders?status=pending','permission' => 'orders.view'),
			array('key' => 'orders.shipping',   'label' => 'Shipping',      'uri' => 'admin/shipments',           'permission' => 'shipping.view'),
			array('key' => 'orders.tracking',   'label' => 'Delivery Tracking','uri' => 'admin/tracking',         'permission' => 'shipping.view'),
			array('key' => 'orders.returns',    'label' => 'Return Requests','uri' => 'admin/returns',            'permission' => 'returns.view'),
			array('key' => 'orders.refunds',    'label' => 'Refunds',       'uri' => 'admin/refunds',             'permission' => 'refunds.view'),
			array('key' => 'orders.cancellations','label' => 'Cancellations','uri' => 'admin/cancellations',      'permission' => 'orders.view'),
			array('key' => 'orders.invoices',   'label' => 'Invoices',      'uri' => 'admin/invoices',            'permission' => 'invoices.view'),
		),
	),

	array(
		'key' => 'payments', 'label' => 'Payments', 'icon' => 'fa-credit-card',
		'permission' => 'payments.view',
		'children' => array(
			array('key' => 'payments.all',      'label' => 'Transactions',  'uri' => 'admin/payments',            'permission' => 'payments.view'),
			array('key' => 'payments.razorpay', 'label' => 'Razorpay',      'uri' => 'admin/payments/razorpay',    'permission' => 'payments.manage'),
			array('key' => 'payments.logs',     'label' => 'Payment Logs',  'uri' => 'admin/payments/logs',        'permission' => 'payments.view'),
		),
	),

	array(
		'key' => 'promotions', 'label' => 'Promotions', 'icon' => 'fa-tags',
		'permission' => 'coupons.view',
		'children' => array(
			array('key' => 'promotions.coupons', 'label' => 'Coupons', 'uri' => 'admin/coupons', 'permission' => 'coupons.view'),
			array('key' => 'promotions.offers',  'label' => 'Offers',  'uri' => 'admin/offers',  'permission' => 'offers.view'),
		),
	),

	array('heading' => 'Inventory'),

	array(
		'key' => 'inventory', 'label' => 'Inventory', 'icon' => 'fa-warehouse',
		'permission' => 'inventory.view',
		'children' => array(
			array('key' => 'inventory.stock',      'label' => 'Stock Overview', 'uri' => 'admin/inventory',            'permission' => 'inventory.view'),
			array('key' => 'inventory.warehouses', 'label' => 'Warehouses',     'uri' => 'admin/warehouses',           'permission' => 'warehouses.view'),
			array('key' => 'inventory.stock_in',   'label' => 'Stock In',       'uri' => 'admin/inventory/stock-in',   'permission' => 'inventory.manage'),
			array('key' => 'inventory.stock_out',  'label' => 'Stock Out',      'uri' => 'admin/inventory/stock-out',  'permission' => 'inventory.manage'),
			array('key' => 'inventory.adjustments','label' => 'Adjustments',    'uri' => 'admin/inventory/adjustments','permission' => 'inventory.manage'),
			array('key' => 'inventory.low_stock',  'label' => 'Low Stock',      'uri' => 'admin/inventory/low-stock',  'permission' => 'inventory.view'),
		),
	),

	array(
		'key' => 'purchasing', 'label' => 'Purchasing', 'icon' => 'fa-truck-ramp-box',
		'permission' => 'purchases.view',
		'children' => array(
			array('key' => 'purchasing.orders',    'label' => 'Purchase Entries', 'uri' => 'admin/purchases', 'permission' => 'purchases.view'),
			array('key' => 'purchasing.suppliers', 'label' => 'Suppliers',        'uri' => 'admin/suppliers', 'permission' => 'suppliers.view'),
		),
	),

	array('heading' => 'People'),

	array(
		'key' => 'customers', 'label' => 'Customers', 'icon' => 'fa-users',
		'uri' => 'admin/customers', 'permission' => 'customers.view',
	),

	array(
		'key' => 'access', 'label' => 'Access Control', 'icon' => 'fa-user-shield',
		'permission' => 'users.view',
		'children' => array(
			array('key' => 'access.users',       'label' => 'Staff Users',  'uri' => 'admin/users',       'permission' => 'users.view'),
			array('key' => 'access.roles',       'label' => 'Roles',        'uri' => 'admin/roles',       'permission' => 'roles.view'),
			array('key' => 'access.permissions', 'label' => 'Permissions',  'uri' => 'admin/permissions', 'permission' => 'permissions.view'),
		),
	),

	array('heading' => 'Content'),

	array(
		'key' => 'cms', 'label' => 'Website CMS', 'icon' => 'fa-newspaper',
		'permission' => 'cms.view',
		'children' => array(
			array('key' => 'cms.pages',        'label' => 'Pages',            'uri' => 'admin/pages',        'permission' => 'cms.view'),
			array('key' => 'cms.banners',      'label' => 'Banners',          'uri' => 'admin/banners',      'permission' => 'banners.view'),
			array('key' => 'cms.blog',         'label' => 'Blog',             'uri' => 'admin/blog',         'permission' => 'blog.view'),
			array('key' => 'cms.testimonials', 'label' => 'Testimonials',     'uri' => 'admin/testimonials', 'permission' => 'testimonials.view'),
			array('key' => 'cms.faqs',         'label' => 'FAQ',              'uri' => 'admin/faqs',         'permission' => 'faqs.view'),
			array('key' => 'cms.contacts',     'label' => 'Contact Messages', 'uri' => 'admin/contacts',     'permission' => 'contacts.view'),
			array('key' => 'cms.newsletter',   'label' => 'Newsletter',       'uri' => 'admin/newsletter',   'permission' => 'newsletter.view'),
		),
	),

	array(
		'key' => 'seo', 'label' => 'SEO', 'icon' => 'fa-magnifying-glass-chart',
		'permission' => 'seo.view',
		'children' => array(
			array('key' => 'seo.meta',    'label' => 'Meta Manager', 'uri' => 'admin/seo',         'permission' => 'seo.view'),
			array('key' => 'seo.sitemap', 'label' => 'Sitemap',      'uri' => 'admin/seo/sitemap', 'permission' => 'seo.manage'),
		),
	),

	array('heading' => 'Insights'),

	array(
		'key' => 'reports', 'label' => 'Reports', 'icon' => 'fa-chart-column',
		'permission' => 'reports.view',
		'children' => array(
			array('key' => 'reports.sales',     'label' => 'Sales',        'uri' => 'admin/reports/sales',     'permission' => 'reports.view'),
			array('key' => 'reports.revenue',   'label' => 'Revenue',      'uri' => 'admin/reports/revenue',   'permission' => 'reports.view'),
			array('key' => 'reports.gst',       'label' => 'GST',          'uri' => 'admin/reports/gst',       'permission' => 'reports.view'),
			array('key' => 'reports.payments',  'label' => 'Payments',     'uri' => 'admin/reports/payments',  'permission' => 'reports.view'),
			array('key' => 'reports.shipments', 'label' => 'Shipments',    'uri' => 'admin/reports/shipments', 'permission' => 'reports.view'),
			array('key' => 'reports.returns',   'label' => 'Returns',      'uri' => 'admin/reports/returns',   'permission' => 'reports.view'),
			array('key' => 'reports.customers', 'label' => 'Customers',    'uri' => 'admin/reports/customers', 'permission' => 'reports.view'),
			array('key' => 'reports.inventory', 'label' => 'Inventory',    'uri' => 'admin/reports/inventory', 'permission' => 'reports.view'),
			array('key' => 'reports.products',  'label' => 'Products',     'uri' => 'admin/reports/products',  'permission' => 'reports.view'),
			array('key' => 'reports.suppliers', 'label' => 'Suppliers',    'uri' => 'admin/reports/suppliers', 'permission' => 'reports.view'),
			array('key' => 'reports.coupons',   'label' => 'Coupons',      'uri' => 'admin/reports/coupons',   'permission' => 'reports.view'),
			array('key' => 'reports.profit',    'label' => 'Profit & Loss','uri' => 'admin/reports/profit',    'permission' => 'reports.view'),
		),
	),

	array('heading' => 'System'),

	array(
		'key' => 'notifications', 'label' => 'Notifications', 'icon' => 'fa-bell',
		'permission' => 'notifications.view',
		'children' => array(
			array('key' => 'notifications.all',   'label' => 'All Notifications', 'uri' => 'admin/notifications',           'permission' => 'notifications.view'),
			array('key' => 'notifications.email', 'label' => 'Email Templates',   'uri' => 'admin/templates/email',         'permission' => 'templates.view'),
			array('key' => 'notifications.sms',   'label' => 'SMS Templates',     'uri' => 'admin/templates/sms',           'permission' => 'templates.view'),
		),
	),

	array(
		'key' => 'settings', 'label' => 'Settings', 'icon' => 'fa-gear',
		'permission' => 'settings.view',
		'children' => array(
			array('key' => 'settings.general',  'label' => 'General',   'uri' => 'admin/settings',          'permission' => 'settings.view'),
			array('key' => 'settings.tax',      'label' => 'Tax & GST', 'uri' => 'admin/settings/tax',      'permission' => 'settings.manage'),
			array('key' => 'settings.shipping', 'label' => 'Shipping',  'uri' => 'admin/settings/shipping', 'permission' => 'settings.manage'),
			array('key' => 'settings.payment',  'label' => 'Payment',   'uri' => 'admin/settings/payment',  'permission' => 'settings.manage'),
			array('key' => 'settings.backup',   'label' => 'Backup',    'uri' => 'admin/backups',           'permission' => 'backups.manage'),
			array('key' => 'settings.audit',    'label' => 'Audit Logs','uri' => 'admin/audit-logs',        'permission' => 'audit.view'),
		),
	),
);
