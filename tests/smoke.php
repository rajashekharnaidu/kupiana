<?php
/**
 * Kupiana smoke tests.
 *
 * These checks are intentionally framework-light so they can run on a local
 * XAMPP install without Composer/vendor bootstrap. The suite is primarily
 * read-only: it exercises routes, authentication, ACL, exports, webhook rejection
 * paths and database integrity assumptions without creating business records.
 */

error_reporting(E_ALL);
date_default_timezone_set('Asia/Kolkata');

$root = dirname(__DIR__);
$base = rtrim(getenv('TEST_BASE_URL') ?: 'http://127.0.0.1:8891', '/');
$results = array('passed' => 0, 'failed' => 0, 'failures' => array());

function env_value($key, $default = NULL)
{
	static $env = NULL;
	if ($env === NULL)
	{
		$env = array();
		$file = dirname(__DIR__).'/.env';
		if (is_readable($file))
		{
			foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line)
			{
				$line = trim($line);
				if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === FALSE) { continue; }
				list($name, $value) = explode('=', $line, 2);
				$value = trim($value);
				if (strlen($value) >= 2 && (($value[0] === '"' && substr($value, -1) === '"') || ($value[0] === "'" && substr($value, -1) === "'"))) { $value = substr($value, 1, -1); }
				$env[trim($name)] = $value;
			}
		}
	}
	$value = getenv($key);
	return $value !== FALSE ? $value : (array_key_exists($key, $env) ? $env[$key] : $default);
}

function ok($condition, $message)
{
	global $results;
	if ($condition)
	{
		$results['passed']++;
		echo "PASS  ".$message.PHP_EOL;
		return;
	}
	$results['failed']++;
	$results['failures'][] = $message;
	echo "FAIL  ".$message.PHP_EOL;
}

function request($method, $path, $cookie = NULL, $data = NULL, array $headers = array())
{
	global $base;
	$ch = curl_init($base.$path);
	curl_setopt_array($ch, array(
		CURLOPT_RETURNTRANSFER => TRUE,
		CURLOPT_HEADER => TRUE,
		CURLOPT_FOLLOWLOCATION => FALSE,
		CURLOPT_TIMEOUT => 20,
	));
	if ($cookie)
	{
		curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
	}
	if ($method === 'POST')
	{
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, is_string($data) ? $data : http_build_query((array) $data));
	}
	if ($headers) { curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); }
	$raw = curl_exec($ch);
	$error = curl_error($ch);
	$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	$response = array(
		'code' => curl_getinfo($ch, CURLINFO_RESPONSE_CODE),
		'content_type' => curl_getinfo($ch, CURLINFO_CONTENT_TYPE),
		'redirect' => curl_getinfo($ch, CURLINFO_REDIRECT_URL),
		'headers' => substr((string) $raw, 0, $header_size),
		'body' => substr((string) $raw, $header_size),
		'error' => $error,
	);
	curl_close($ch);
	return $response;
}

function csrf_from($html)
{
	if (preg_match('/name="([^"]*csrf[^"]*)" value="([^"]*)"/i', $html, $m)) { return array($m[1], $m[2]); }
	return array('', '');
}

function login_as($email, $password)
{
	$cookie = tempnam(sys_get_temp_dir(), 'kupiana_test_cookie_');
	$form = request('GET', '/login', $cookie);
	list($csrf_name, $csrf_hash) = csrf_from($form['body']);
	ok($form['code'] === 200 && $csrf_name !== '' && $csrf_hash !== '', 'login form exposes CSRF token for '.$email);
	$response = request('POST', '/login', $cookie, array('email' => $email, 'password' => $password, $csrf_name => $csrf_hash));
	ok(in_array($response['code'], array(302, 303, 307), TRUE), 'login redirects for '.$email);
	return $cookie;
}

function body_has(array $response, $needle)
{
	return strpos($response['body'], $needle) !== FALSE;
}

function json_ld_has(array $response, $needle)
{
	if (!preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $response['body'], $matches)) {
		return FALSE;
	}

	foreach ($matches[1] as $json) {
		$decoded = json_decode($json, TRUE);
		if (is_array($decoded) && strpos(json_encode($decoded), $needle) !== FALSE) {
			return TRUE;
		}
	}

	return FALSE;
}

function db()
{
	$mysqli = @new mysqli(env_value('DB_HOST', 'localhost'), env_value('DB_USERNAME', 'root'), env_value('DB_PASSWORD', ''), env_value('DB_DATABASE', 'kupiana'));
	if ($mysqli->connect_errno) { return NULL; }
	$mysqli->set_charset('utf8mb4');
	return $mysqli;
}

echo "Kupiana smoke suite against {$base}".PHP_EOL;

$public = array('/', '/shop', '/search', '/deals', '/offers', '/brands', '/cart', '/wishlist', '/contact', '/track-order', '/blog', '/robots.txt', '/sitemap.xml');
foreach ($public as $path)
{
	$response = request('GET', $path);
	ok($response['error'] === '' && $response['code'] === 200, 'public route '.$path.' returns 200');
}

$home = request('GET', '/');
ok(body_has($home, '<link rel="canonical"') && body_has($home, 'property="og:title"') && body_has($home, 'name="twitter:card"'), 'home page renders canonical, Open Graph and Twitter metadata');
ok(json_ld_has($home, 'Organization') && json_ld_has($home, 'WebSite'), 'home page renders Organization and WebSite JSON-LD');
$search_page = request('GET', '/search?q=turmeric');
ok(body_has($search_page, 'name="robots" content="noindex,follow"') && body_has($search_page, '<link rel="canonical" href="'.$base.'/search"'), 'search results are noindex with clean canonical');
$cart_page = request('GET', '/cart');
ok(body_has($cart_page, 'name="robots" content="noindex,follow"'), 'cart page is noindex');
$robots = request('GET', '/robots.txt');
ok(body_has($robots, 'Sitemap: '.$base.'/sitemap.xml') && body_has($robots, 'Disallow: /checkout') && body_has($robots, 'Disallow: /payments/'), 'robots.txt advertises sitemap and blocks private transactional routes');
$sitemap = request('GET', '/sitemap.xml');
ok($sitemap['code'] === 200 && simplexml_load_string($sitemap['body']) !== FALSE && body_has($sitemap, '<loc>'.$base.'/shop</loc>'), 'sitemap.xml is valid XML and includes storefront discovery URLs');

$checkout = request('GET', '/checkout');
ok($checkout['error'] === '' && in_array($checkout['code'], array(200, 302, 303, 307), TRUE), 'checkout renders or redirects safely when cart is empty');

$admin_gate = request('GET', '/admin');
ok(in_array($admin_gate['code'], array(302, 303, 307), TRUE) && strpos($admin_gate['redirect'], '/login') !== FALSE, 'guest admin request redirects to login');

$admin = login_as('admin@kupiana.test', 'admin123');
$admin_routes = array('/admin', '/admin/products', '/admin/orders', '/admin/inventory', '/admin/tracking', '/admin/returns', '/admin/payments', '/admin/reports/sales', '/admin/reports/revenue', '/admin/reports/gst', '/admin/reports/payments', '/admin/reports/shipments', '/admin/reports/returns', '/admin/reports/customers', '/admin/reports/inventory', '/admin/reports/products', '/admin/reports/suppliers', '/admin/reports/coupons', '/admin/reports/profit');
foreach ($admin_routes as $path)
{
	$response = request('GET', $path, $admin);
	ok($response['code'] === 200, 'admin route '.$path.' returns 200');
}
$csv = request('GET', '/admin/reports/export/sales?from=2026-08-01&to=2026-08-03', $admin);
ok($csv['code'] === 200 && strpos((string) $csv['content_type'], 'text/csv') !== FALSE && strpos($csv['body'], 'Order Status') !== FALSE, 'sales report CSV export returns CSV');

$invalid_razorpay = request('POST', '/payments/razorpay/webhook', NULL, '{"event":"payment.captured"}', array('Content-Type: application/json', 'X-Razorpay-Signature: invalid'));
ok($invalid_razorpay['code'] === 400, 'Razorpay webhook rejects invalid signature');
$invalid_tracking = request('POST', '/tracking/webhook', NULL, '{not-json', array('Content-Type: application/json'));
ok($invalid_tracking['code'] === 400, 'tracking webhook rejects invalid JSON');

$customer = login_as('user@kupiana.test', 'user123');
$customer_routes = array('/account', '/account/orders', '/account/returns', '/account/profile', '/account/security', '/account/addresses');
foreach ($customer_routes as $path)
{
	$response = request('GET', $path, $customer);
	ok($response['code'] === 200, 'customer route '.$path.' returns 200');
}
$customer_admin = request('GET', '/admin', $customer);
ok($customer_admin['code'] === 403, 'customer cannot access admin');

$db = db();
ok($db instanceof mysqli, 'database connection opens');
if ($db instanceof mysqli)
{
	$table_count = $db->query('SELECT COUNT(*) AS total FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()')->fetch_object();
	ok($table_count && (int) $table_count->total >= 70, 'database has expected application table count');
	$missing = $db->query("SELECT COUNT(*) AS total FROM information_schema.TABLES t WHERE t.TABLE_SCHEMA = DATABASE() AND t.TABLE_TYPE = 'BASE TABLE' AND NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS c WHERE c.TABLE_SCHEMA = t.TABLE_SCHEMA AND c.TABLE_NAME = t.TABLE_NAME AND c.COLUMN_NAME = 'deleted_at')");
	$missing_row = $missing ? $missing->fetch_object() : NULL;
	ok($missing_row && (int) $missing_row->total === 0, 'business tables include deleted_at soft-delete column');
	$users = $db->query("SELECT COUNT(*) AS total FROM users WHERE status = 'active' AND deleted_at IS NULL")->fetch_object();
	ok($users && (int) $users->total >= 3, 'seeded active users are present');
	$product = $db->query("SELECT slug FROM products WHERE status = 'active' AND deleted_at IS NULL ORDER BY id LIMIT 1");
	$product_row = $product ? $product->fetch_object() : NULL;
	if ($product_row)
	{
		$product_page = request('GET', '/products/'.$product_row->slug);
		ok($product_page['code'] === 200 && body_has($product_page, '<link rel="canonical" href="'.$base.'/products/'.$product_row->slug.'"'), 'product page has a self canonical URL');
		ok(json_ld_has($product_page, 'Product') && json_ld_has($product_page, 'Offer') && json_ld_has($product_page, 'BreadcrumbList'), 'product page renders Product, Offer and BreadcrumbList JSON-LD');
	}
	$db->close();
}

@unlink($admin);
@unlink($customer);

echo PHP_EOL.'Passed: '.$results['passed'].'  Failed: '.$results['failed'].PHP_EOL;
if ($results['failed'] > 0)
{
	echo 'Failures:'.PHP_EOL;
	foreach ($results['failures'] as $failure) { echo '- '.$failure.PHP_EOL; }
	exit(1);
}
