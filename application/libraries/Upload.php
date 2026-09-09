<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Safe image upload helper for admin forms.
 */
class Upload
{
	/** @var CI_Controller */
	protected $CI;

	/** @var string Human-readable reason the last image() call failed. */
	protected $last_error = 'Choose a valid image file.';

	public function __construct()
	{
		$this->CI =& get_instance();
	}

	/**
	 * Reason the last image() call returned FALSE.
	 *
	 * @return string
	 */
	public function error()
	{
		return $this->last_error;
	}

	/**
	 * Store an image and create configured thumbnails when GD is available.
	 *
	 * @param string $field
	 * @param string $directory
	 * @return array|false
	 */
	public function image($field, $directory = 'imports')
	{
		$this->last_error = 'Choose a valid image file.';

		if (empty($_FILES[$field]['name']))
		{
			return FALSE;
		}

		$config = $this->CI->config->item('upload', 'app');
		$max_kb = isset($config['max_size_kb']) ? (int) $config['max_size_kb'] : 0;

		// A file exceeding upload_max_filesize/post_max_size never reaches
		// $_FILES[$field]['size'] - PHP flags it via the error code instead.
		if (in_array($_FILES[$field]['error'], array(UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE), TRUE))
		{
			$this->last_error = $max_kb ? 'Image must be smaller than '.round($max_kb / 1024, 1).' MB.' : 'Image is too large.';
			return FALSE;
		}

		if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK)
		{
			return FALSE;
		}

		if ($max_kb && $_FILES[$field]['size'] > $max_kb * 1024)
		{
			$this->last_error = 'Image must be smaller than '.round($max_kb / 1024, 1).' MB.';
			return FALSE;
		}

		$extension = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
		$allowed = array('jpg', 'jpeg', 'png', 'webp', 'gif', 'svg');
		if ( ! in_array($extension, $allowed, TRUE))
		{
			return FALSE;
		}

		$relative = isset($config['paths'][$directory]) ? $config['paths'][$directory] : 'imports/';
		$path = rtrim($config['base_path'].$relative, '/').'/';
		if ( ! is_dir($path)) { mkdir($path, 0755, TRUE); }

		$name = generate_token(16).'.'.$extension;
		$target = $path.$name;
		if ( ! move_uploaded_file($_FILES[$field]['tmp_name'], $target)) { return FALSE; }

		return array('name' => $name, 'path' => $target, 'url' => base_url($config['base_url'].$relative.$name));
	}
}
