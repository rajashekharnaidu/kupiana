#!/usr/bin/env php
<?php

$root = dirname(__DIR__);
$errors = array();
$warnings = array();

function line($message)
{
	echo $message.PHP_EOL;
}

function env_file($root)
{
	$path = $root.DIRECTORY_SEPARATOR.'.env';
	$env = array();

	if (!is_file($path) || !is_readable($path)) {
		return $env;
	}

	foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
		$line = trim($line);

		if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === FALSE) {
			continue;
		}

		list($key, $value) = explode('=', $line, 2);
		$key = trim($key);
		$value = trim($value);

		if (
			(strlen($value) >= 2)
			&& (($value[0] === '"' && substr($value, -1) === '"') || ($value[0] === "'" && substr($value, -1) === "'"))
		) {
			$value = substr($value, 1, -1);
		}

		$env[$key] = $value;
	}

	return $env;
}

function env_value(array $env, $key, $default = '')
{
	$value = getenv($key);

	if ($value !== FALSE) {
		return $value;
	}

	return array_key_exists($key, $env) ? $env[$key] : $default;
}

function env_bool(array $env, $key, $default = FALSE)
{
	$value = env_value($env, $key, NULL);

	if ($value === NULL || $value === '') {
		return (bool) $default;
	}

	$parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

	return $parsed === NULL ? (bool) $default : (bool) $parsed;
}

function check($condition, $message, array &$errors)
{
	if ($condition) {
		line('[OK] '.$message);
		return;
	}

	line('[FAIL] '.$message);
	$errors[] = $message;
}

function warn_if($condition, $message, array &$warnings)
{
	if (!$condition) {
		return;
	}

	line('[WARN] '.$message);
	$warnings[] = $message;
}

$env = env_file($root);
$ci_env = env_value($env, 'CI_ENV', 'development');
$base_url = env_value($env, 'APP_BASE_URL', '');
$key = env_value($env, 'APP_ENCRYPTION_KEY', '');
$session_path = env_value($env, 'SESSION_SAVE_PATH', 'application/cache/sessions');
$session_path = $session_path !== '' && $session_path[0] !== '/'
	? $root.DIRECTORY_SEPARATOR.$session_path
	: $session_path;

line('Kupiana deployment preflight');
line('Project root: '.$root);
line('');

check(version_compare(PHP_VERSION, '8.0.0', '>='), 'PHP version is 8.0 or newer: '.PHP_VERSION, $errors);

foreach (array('mysqli', 'curl', 'json', 'mbstring', 'openssl', 'fileinfo') as $extension) {
	check(extension_loaded($extension), 'PHP extension loaded: '.$extension, $errors);
}

check(is_file($root.DIRECTORY_SEPARATOR.'.env'), '.env exists', $errors);
check(is_file($root.DIRECTORY_SEPARATOR.'.htaccess'), 'root .htaccess exists', $errors);
check(is_file($root.DIRECTORY_SEPARATOR.'application'.DIRECTORY_SEPARATOR.'.htaccess'), 'application is protected by .htaccess', $errors);
check(is_file($root.DIRECTORY_SEPARATOR.'system'.DIRECTORY_SEPARATOR.'.htaccess'), 'system is protected by .htaccess', $errors);
check(is_file($root.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'.htaccess'), 'uploads execution is blocked by .htaccess', $errors);
check(is_file($root.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'schema.sql'), 'database schema file is present', $errors);
check(is_file($root.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'seed.sql'), 'database seed file is present', $errors);

foreach (array(
	$root.DIRECTORY_SEPARATOR.'application'.DIRECTORY_SEPARATOR.'cache',
	$session_path,
	$root.DIRECTORY_SEPARATOR.'application'.DIRECTORY_SEPARATOR.'logs',
	$root.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'uploads',
	$root.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'backups',
	$root.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'invoices',
) as $path) {
	check(is_dir($path) && is_writable($path), 'writable directory: '.$path, $errors);
}

if (class_exists('mysqli')) {
	$mysqli = @new mysqli(
		env_value($env, 'DB_HOST', 'localhost'),
		env_value($env, 'DB_USERNAME', 'root'),
		env_value($env, 'DB_PASSWORD', ''),
		env_value($env, 'DB_DATABASE', 'kupiana')
	);

	check(!$mysqli->connect_errno, 'database connection opens', $errors);

	if (!$mysqli->connect_errno) {
		$mysqli->close();
	}
}

warn_if($ci_env !== 'production', 'CI_ENV is not production; current value: '.$ci_env, $warnings);
warn_if($base_url === '' || strpos($base_url, 'https://') !== 0, 'APP_BASE_URL should be an HTTPS URL in production.', $warnings);
warn_if(strlen($key) < 32 || $key === 'kupiana-change-this-key-before-production', 'APP_ENCRYPTION_KEY should be replaced with a long random value.', $warnings);
warn_if(!env_bool($env, 'COOKIE_SECURE', $ci_env === 'production'), 'COOKIE_SECURE should be true behind HTTPS.', $warnings);
warn_if(env_bool($env, 'MAIL_ENABLED', FALSE) && env_value($env, 'ZEPTOMAIL_API_KEY', '') === '', 'MAIL_ENABLED is true but ZEPTOMAIL_API_KEY is empty.', $warnings);
warn_if(env_bool($env, 'RAZORPAY_ENABLED', FALSE) && (env_value($env, 'RAZORPAY_KEY_ID', '') === '' || env_value($env, 'RAZORPAY_KEY_SECRET', '') === ''), 'RAZORPAY_ENABLED is true but Razorpay keys are incomplete.', $warnings);

line('');
line('Preflight complete: '.count($errors).' failure(s), '.count($warnings).' warning(s).');

exit(count($errors) > 0 ? 1 : 0);
