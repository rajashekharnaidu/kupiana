<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Common helper.
 *
 * Formatting, math and small utilities used across controllers and views.
 * Every function is guarded so the helper can be loaded more than once.
 *
 * @package Kupiana\Helpers
 */

if ( ! function_exists('app_config'))
{
	/**
	 * Read a value from application/config/app.php.
	 *
	 * @param  string $key
	 * @param  mixed  $default
	 * @return mixed
	 */
	function app_config($key, $default = NULL)
	{
		$CI =& get_instance();
		$CI->config->load('app', TRUE);

		$value = $CI->config->item($key, 'app');

		return ($value === NULL) ? $default : $value;
	}
}

if ( ! function_exists('money'))
{
	/**
	 * Format an amount as currency.
	 *
	 * @param  float $amount
	 * @param  bool  $with_symbol
	 * @return string
	 */
	function money($amount, $with_symbol = TRUE)
	{
		$currency = app_config('currency', array());

		$decimals  = isset($currency['decimals']) ? (int) $currency['decimals'] : 2;
		$thousands = isset($currency['thousand_separator']) ? $currency['thousand_separator'] : ',';
		$point     = isset($currency['decimal_separator']) ? $currency['decimal_separator'] : '.';
		$symbol    = isset($currency['symbol']) ? $currency['symbol'] : '₹';
		$position  = isset($currency['position']) ? $currency['position'] : 'before';

		$formatted = number_format((float) $amount, $decimals, $point, $thousands);

		if ( ! $with_symbol)
		{
			return $formatted;
		}

		return ($position === 'after') ? $formatted.$symbol : $symbol.$formatted;
	}
}

if ( ! function_exists('money_compact'))
{
	/**
	 * Shorten large amounts for dashboard tiles: 1.2L, 3.4Cr, 12.5K.
	 *
	 * @param  float $amount
	 * @return string
	 */
	function money_compact($amount)
	{
		$currency = app_config('currency', array());
		$symbol   = isset($currency['symbol']) ? $currency['symbol'] : '₹';
		$amount   = (float) $amount;
		$sign     = $amount < 0 ? '-' : '';
		$amount   = abs($amount);

		if ($amount >= 10000000)
		{
			return $sign.$symbol.round($amount / 10000000, 2).'Cr';
		}

		if ($amount >= 100000)
		{
			return $sign.$symbol.round($amount / 100000, 2).'L';
		}

		if ($amount >= 1000)
		{
			return $sign.$symbol.round($amount / 1000, 1).'K';
		}

		return $sign.$symbol.number_format($amount, 0);
	}
}

if ( ! function_exists('format_date'))
{
	/**
	 * Format a date string using the configured display format.
	 *
	 * @param  string|null $value
	 * @param  string|null $format
	 * @param  string      $empty
	 * @return string
	 */
	function format_date($value, $format = NULL, $empty = '—')
	{
		if (empty($value) || $value === '0000-00-00' || $value === '0000-00-00 00:00:00')
		{
			return $empty;
		}

		$timestamp = strtotime($value);

		if ($timestamp === FALSE)
		{
			return $empty;
		}

		return date($format ?: app_config('date_format', 'd M Y'), $timestamp);
	}
}

if ( ! function_exists('format_datetime'))
{
	/**
	 * Format a datetime string using the configured display format.
	 *
	 * @param  string|null $value
	 * @param  string      $empty
	 * @return string
	 */
	function format_datetime($value, $empty = '—')
	{
		return format_date($value, app_config('datetime_format', 'd M Y, h:i A'), $empty);
	}
}

if ( ! function_exists('time_ago'))
{
	/**
	 * Relative time such as "3 hours ago".
	 *
	 * @param  string|null $value
	 * @return string
	 */
	function time_ago($value)
	{
		if (empty($value))
		{
			return '—';
		}

		$timestamp = strtotime($value);

		if ($timestamp === FALSE)
		{
			return '—';
		}

		$diff = time() - $timestamp;

		if ($diff < 0)
		{
			return format_datetime($value);
		}

		$units = array(
			31536000 => 'year',
			2592000  => 'month',
			604800   => 'week',
			86400    => 'day',
			3600     => 'hour',
			60       => 'minute',
		);

		foreach ($units as $seconds => $label)
		{
			if ($diff >= $seconds)
			{
				$count = (int) floor($diff / $seconds);
				return $count.' '.$label.($count > 1 ? 's' : '').' ago';
			}
		}

		return 'just now';
	}
}

if ( ! function_exists('make_slug'))
{
	/**
	 * URL-safe slug from arbitrary text.
	 *
	 * @param  string $text
	 * @return string
	 */
	function make_slug($text)
	{
		$CI =& get_instance();
		$CI->load->helper('text');

		return url_title(convert_accented_characters(trim($text)), '-', TRUE);
	}
}

if ( ! function_exists('unique_slug'))
{
	/**
	 * Slug guaranteed unique within a table, appending -2, -3, ... as needed.
	 *
	 * @param  string   $text
	 * @param  string   $table
	 * @param  string   $column
	 * @param  int|null $ignore_id
	 * @return string
	 */
	function unique_slug($text, $table, $column = 'slug', $ignore_id = NULL)
	{
		$CI =& get_instance();

		$base = make_slug($text);
		$slug = $base;
		$i    = 2;

		while (TRUE)
		{
			$CI->db->where($column, $slug);

			if ($ignore_id !== NULL)
			{
				$CI->db->where('id !=', (int) $ignore_id);
			}

			if ($CI->db->count_all_results($table) === 0)
			{
				return $slug;
			}

			$slug = $base.'-'.$i;
			$i++;
		}
	}
}

if ( ! function_exists('generate_code'))
{
	/**
	 * Sequential-looking reference code, e.g. ORD-20260802-4F9A21.
	 *
	 * @param  string $prefix
	 * @return string
	 */
	function generate_code($prefix = 'ORD')
	{
		return strtoupper($prefix).'-'.date('Ymd').'-'.strtoupper(bin2hex(random_bytes(3)));
	}
}

if ( ! function_exists('generate_token'))
{
	/**
	 * Cryptographically secure random hex token.
	 *
	 * @param  int $bytes
	 * @return string
	 */
	function generate_token($bytes = 32)
	{
		return bin2hex(random_bytes($bytes));
	}
}

if ( ! function_exists('generate_otp'))
{
	/**
	 * Numeric one-time password of the configured length.
	 *
	 * @param  int|null $length
	 * @return string
	 */
	function generate_otp($length = NULL)
	{
		$security = app_config('security', array());
		$length   = $length ?: (isset($security['otp_length']) ? (int) $security['otp_length'] : 6);

		$otp = '';

		for ($i = 0; $i < $length; $i++)
		{
			$otp .= random_int(0, 9);
		}

		return $otp;
	}
}

if ( ! function_exists('array_get'))
{
	/**
	 * Null-safe read from an array or object.
	 *
	 * @param  mixed  $subject
	 * @param  string $key
	 * @param  mixed  $default
	 * @return mixed
	 */
	function array_get($subject, $key, $default = NULL)
	{
		if (is_array($subject))
		{
			return array_key_exists($key, $subject) ? $subject[$key] : $default;
		}

		if (is_object($subject))
		{
			return property_exists($subject, $key) ? $subject->{$key} : $default;
		}

		return $default;
	}
}

if ( ! function_exists('discount_percent'))
{
	/**
	 * Percentage saved between an MRP and a selling price.
	 *
	 * @param  float $mrp
	 * @param  float $price
	 * @return int
	 */
	function discount_percent($mrp, $price)
	{
		$mrp   = (float) $mrp;
		$price = (float) $price;

		if ($mrp <= 0 || $price >= $mrp)
		{
			return 0;
		}

		return (int) round((($mrp - $price) / $mrp) * 100);
	}
}

if ( ! function_exists('apply_percentage'))
{
	/**
	 * Percentage of an amount, rounded to currency precision.
	 *
	 * @param  float $amount
	 * @param  float $percent
	 * @return float
	 */
	function apply_percentage($amount, $percent)
	{
		$decimals = (int) array_get(app_config('currency', array()), 'decimals', 2);

		return round(((float) $amount * (float) $percent) / 100, $decimals);
	}
}

if ( ! function_exists('upload_url'))
{
	/**
	 * Public URL for an uploaded file, with a placeholder fallback.
	 *
	 * @param  string|null $path Path relative to public/uploads/.
	 * @param  string      $fallback
	 * @return string
	 */
	function upload_url($path, $fallback = 'public/assets/images/placeholder.svg')
	{
		if (empty($path))
		{
			return base_url($fallback);
		}

		if (preg_match('~^https?://~i', $path))
		{
			return $path;
		}

		$base = array_get(app_config('upload', array()), 'base_url', 'public/uploads/');

		return base_url($base.ltrim($path, '/'));
	}
}

if ( ! function_exists('human_filesize'))
{
	/**
	 * Byte count as a readable size.
	 *
	 * @param  int $bytes
	 * @return string
	 */
	function human_filesize($bytes)
	{
		$units = array('B', 'KB', 'MB', 'GB', 'TB');
		$bytes = max((int) $bytes, 0);
		$power = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;
		$power = min($power, count($units) - 1);

		return round($bytes / pow(1024, $power), 2).' '.$units[$power];
	}
}

if ( ! function_exists('mask_value'))
{
	/**
	 * Partially hide an email, phone or card number for display.
	 *
	 * @param  string $value
	 * @param  int    $visible Characters kept at the end.
	 * @return string
	 */
	function mask_value($value, $visible = 4)
	{
		$value  = (string) $value;
		$length = strlen($value);

		if ($length <= $visible)
		{
			return str_repeat('*', $length);
		}

		return str_repeat('*', $length - $visible).substr($value, -$visible);
	}
}

if ( ! function_exists('str_excerpt'))
{
	/**
	 * Trim text to a word-safe length.
	 *
	 * @param  string $text
	 * @param  int    $limit
	 * @param  string $end
	 * @return string
	 */
	function str_excerpt($text, $limit = 140, $end = '…')
	{
		$text = trim(strip_tags((string) $text));

		if (strlen($text) <= $limit)
		{
			return $text;
		}

		return rtrim(substr($text, 0, strrpos(substr($text, 0, $limit), ' ') ?: $limit)).$end;
	}
}

if ( ! function_exists('current_user_id'))
{
	/**
	 * Id of the signed-in user, or NULL.
	 *
	 * @return int|null
	 */
	function current_user_id()
	{
		$CI =& get_instance();

		$id = $CI->session->userdata('user_id');

		return $id ? (int) $id : NULL;
	}
}

if ( ! function_exists('can'))
{
	/**
	 * View-friendly permission check.
	 *
	 * @param  string $permission
	 * @return bool
	 */
	function can($permission)
	{
		$CI =& get_instance();

		return isset($CI->acl) ? $CI->acl->can($permission) : FALSE;
	}
}

if ( ! function_exists('query_url'))
{
	/**
	 * Current URL with query parameters merged in. Used by table sorting,
	 * pagination and filter links so existing state is preserved.
	 *
	 * @param  array $params Pass NULL as a value to drop that key.
	 * @return string
	 */
	function query_url(array $params = array())
	{
		$CI =& get_instance();

		$query = $CI->input->get(NULL, TRUE);
		$query = is_array($query) ? $query : array();

		foreach ($params as $key => $value)
		{
			if ($value === NULL)
			{
				unset($query[$key]);
			}
			else
			{
				$query[$key] = $value;
			}
		}

		$string = http_build_query($query);

		return site_url(uri_string()).($string ? '?'.$string : '');
	}
}
