<?php
$root = $_SERVER['DOCUMENT_ROOT'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path !== '/' && file_exists($root.$path) && ! is_dir($root.$path)) {
	return FALSE;
}
$_SERVER['SCRIPT_NAME'] = '/index.php';
require $root.'/index.php';
