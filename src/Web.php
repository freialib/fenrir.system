<?php namespace fenrir\system;

/**
 * @copyright (c) 2014, freia Team
 * @license BSD-2 <http://freialib.github.io/license.txt>
 * @package freia Library
 */
class Web implements \hlin\archetype\Web {

	use \hlin\WebTrait;

	/**
	 * @return static
	 */
	static function instance() {
		$i = new static;
		return $i;
	}

	/**
	 * @return string lowercase http name
	 */
	function requestMethod() {
		return strtolower($_SERVER['REQUEST_METHOD']);
	}

	/**
	 * @return string
	 */
	function requestUri() {
		return $_SERVER['REQUEST_URI'];
	}

	/**
	 * @return string
	 */
	function requestBody() {
		return file_get_contents('php://input');
	}

	/**
	 * @return array
	 */
	function postData() {
		return $_POST;
	}

	/**
	 * @see http://www.php.net//manual/en/function.header.php
	 */
	function header($header, $replace = true, $http_response_code = null) {
		if ($http_response_code !== null) {
			header($header, $replace, $http_response_code);
		}
		else { // $http_response_code === null
			header($header, $replace);
		}
	}

	/**
	 * Sent content to the client.
	 */
	function send($contents, $status = 200, array $headers = null) {

		$this->http_response_code($status);

		if ($headers !== null) {
			foreach ($headers as $header) {
				$this->header($header[0], $header[1], $header[2]);
			}
		}

		if ( ! empty($contents)) {
			echo $contents;
		}
	}

	/**
	 * @see http://www.php.net/manual/en/function.http-response-code.php
	 * @return int
	 */
	function http_response_code($code = null) {
		if ($code === null) {
			return http_response_code();
		}
		else { // act as setter
			return http_response_code($code);
		}
	}

} # class
