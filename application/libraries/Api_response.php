<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Api_response
 *
 * Builds the single JSON envelope used by every AJAX and REST endpoint so the
 * front-end can handle all responses with one code path.
 *
 *     {
 *       "status":  "success" | "error",
 *       "message": "Human readable message",
 *       "data":    mixed|null,
 *       "errors":  { "field": "message" },
 *       "meta":    { ...pagination or extra context... }
 *     }
 *
 * @package Kupiana\Libraries
 */
class Api_response
{
	/**
	 * Build the base envelope.
	 *
	 * @param  string $status
	 * @param  string $message
	 * @param  mixed  $data
	 * @param  array  $errors
	 * @param  array  $meta
	 * @return array
	 */
	protected function envelope($status, $message, $data = NULL, array $errors = array(), array $meta = array())
	{
		return array(
			'status'  => $status,
			'message' => $message,
			'data'    => $data,
			'errors'  => (object) $errors,
			'meta'    => (object) $meta,
		);
	}

	/**
	 * A successful response.
	 *
	 * @param  mixed  $data
	 * @param  string $message
	 * @param  array  $meta
	 * @return array
	 */
	public function success($data = NULL, $message = 'Request completed successfully.', array $meta = array())
	{
		return $this->envelope('success', $message, $data, array(), $meta);
	}

	/**
	 * A generic failure.
	 *
	 * @param  string $message
	 * @param  array  $errors
	 * @param  mixed  $data
	 * @return array
	 */
	public function error($message = 'Something went wrong. Please try again.', array $errors = array(), $data = NULL)
	{
		return $this->envelope('error', $message, $data, $errors);
	}

	/**
	 * A paginated list response. Accepts the array returned by MY_Model::paginate().
	 *
	 * @param  array  $pagination
	 * @param  string $message
	 * @return array
	 */
	public function paginated(array $pagination, $message = 'Records fetched successfully.')
	{
		$data = isset($pagination['data']) ? $pagination['data'] : array();

		unset($pagination['data']);

		return $this->envelope('success', $message, $data, array(), $pagination);
	}

	/**
	 * Validation failure. Pairs with HTTP 422.
	 *
	 * @param  array  $errors Field => message.
	 * @param  string $message
	 * @return array
	 */
	public function validation_error(array $errors, $message = 'Please correct the highlighted fields.')
	{
		return $this->envelope('error', $message, NULL, $errors);
	}

	/**
	 * Not authenticated. Pairs with HTTP 401.
	 *
	 * @param  string $message
	 * @return array
	 */
	public function unauthorized($message = 'Authentication required.')
	{
		return $this->envelope('error', $message);
	}

	/**
	 * Authenticated but not allowed. Pairs with HTTP 403.
	 *
	 * @param  string $message
	 * @return array
	 */
	public function forbidden($message = 'You do not have permission to perform this action.')
	{
		return $this->envelope('error', $message);
	}

	/**
	 * Resource missing. Pairs with HTTP 404.
	 *
	 * @param  string $message
	 * @return array
	 */
	public function not_found($message = 'The requested record could not be found.')
	{
		return $this->envelope('error', $message);
	}

	/**
	 * Instruct the client to navigate elsewhere after a successful action.
	 *
	 * @param  string $url
	 * @param  string $message
	 * @return array
	 */
	public function redirect($url, $message = 'Redirecting...')
	{
		return $this->envelope('success', $message, NULL, array(), array('redirect' => $url));
	}
}
