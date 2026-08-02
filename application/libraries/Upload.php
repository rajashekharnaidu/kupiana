<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Safe image upload helper for admin forms.
 */
class Upload
{
	/** @var CI_Controller */
	protected $CI;

	public function __construct()
	{
		$this->CI =& get_instance();
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
		if (empty($_FILES[$field]['name']))
		{
			return FALSE;
		}

		$config = $this->CI->config->item('upload', 'app');
		$relative = isset($config['paths'][$directory]) ? $config['paths'][$directory] : 'imports/';
		$path = rtrim($config['base_path'].$relative, '/').'/';
		if ( ! is_dir($path)) { mkdir($path, 0755, TRUE); }

		$extension = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
		$allowed = array('jpg', 'jpeg', 'png', 'webp', 'gif', 'svg');
		if ( ! in_array($extension, $allowed, TRUE) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK)
		{
			return FALSE;
		}

		$name = random_token(16).'.'.$extension;
		$target = $path.$name;
		if ( ! move_uploaded_file($_FILES[$field]['tmp_name'], $target)) { return FALSE; }

		return array('name' => $name, 'path' => $target, 'url' => base_url($config['base_url'].$relative.$name));
	}
}
