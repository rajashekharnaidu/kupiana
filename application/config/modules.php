<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * HMVC module locations.
 *
 * The installed HMVC package (Jens Segers — application/third_party/HMVC)
 * expects a plain LIST of absolute paths; its Router constructor runs each
 * entry through realpath().
 *
 * Do NOT use the wiredesignz "Modular Extensions" format
 * (array(APPPATH.'modules/' => '../modules/')). The router would take the
 * relative VALUE, realpath('../modules/') returns FALSE, and every module path
 * collapses to '/' — which makes every module route return 404.
 *
 * @package Kupiana\Config
 */
$config['modules_locations'] = array(
	APPPATH.'modules/',
);
