<?php

/**
 * ProcessWire HTTP tools
 *
 * Provides capability for sending POST/GET requests to URLs
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2013 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://processwire.com
 *
 */

class WireHttp { 

	/**
	 * Headers to include in the request
	 *
	 */
	protected $headers = array(
		'charset' => 'utf-8',
		);

	/**
	 * Data to send in the request
	 *
	 */
	protected $data = array();

	/**
	 * Send to a URL using POST
	 *
	 * @param string $url URL to post to (including http:// or https://)
	 * @param array $data Array of data to send (if not already set before)
	 * @return bool|string False on failure or string of contents received on success.
	 *
	 */
	public function post($url, array $data = array()) {
		if(!isset($this->headers['content-type'])) $this->setHeader('content-type', 'application/x-www-form-urlencoded; charset=utf-8');
		return $this->send($url, $data, 'POST');
	}

	/**
	 * Send to a URL using GET
	 *
	 * @param string $url URL to post to (including http:// or https://)
	 * @param array $data Array of data to send (if not already set before)
	 * @return bool|string False on failure or string of contents received on success.
	 *
	 */
	public function get($url, array $data = array()) {
		return $this->send($url, $data, 'GET');
	}


	/**
	 * Send to a URL using HEAD (@horst)
	 *
	 * @param string $url URL to request (including http:// or https://)
	 * @param array $data Array of data to send (if not already set before)
	 * @return bool|array False on failure or Arrray with ResponseHeaders on success.
	 *
	 */
	public function head($url, array $data = array()) {
		$responseHeader = $this->send($url, $data, 'HEAD');
		return is_array($responseHeader) ? $responseHeader : false;
	}

	/**
	 * Send to a URL using HEAD and return the status code (@horst)
	 *
	 * @param string $url URL to request (including http:// or https://)
	 * @param array $data Array of data to send (if not already set before)
	 * @param bool $textMode When true function will return a string rather than integer, see the statusText() method.
	 * @return bool|integer|string False on failure or integer of status code (200|404|etc) on success.
	 *
	 */
	 public function status($url, array $data = array(), $textMode = false) {
		$responseHeader = $this->send($url, $data, 'HEAD');
		if(!is_array($responseHeader)) return false;
		$statusCode = (preg_match("=^(HTTP/\d+\.\d+) (\d{3}) (.*)=", $responseHeader[0], $matches) === 1) ? intval($matches[2]) : false;
		if($textMode) $statusCode = isset($matches[3]) ? "$statusCode $matches[3]" : "$statusCode";
		return $statusCode;
	}

	/**
	 * Send to a URL using HEAD and return the status code and text like "200 OK"
	 *
	 * @param string $url URL to request (including http:// or https://)
	 * @param array $data Array of data to send (if not already set before)
	 * @return bool|string False on failure or string of status code + text on success.
	 *	Example: "200 OK', "302 Found", "404 Not Found"
	 *
	 */
	public function statusText($url, array $data = array()) {
		return $this->status($url, $data, true); 
	}

	/**
	 * Set an array of headers, removes any existing headers
	 *
	 */
	public function setHeaders(array $headers) {
		foreach($headers as $key => $value) {
			$this->setHeader($key, $value);
		}
		return $this;
	}

	/**
	 * Send an individual header to send
	 *
	 */
	public function setHeader($key, $value) {
		$key = strtolower($key);
		$this->headers[$key] = $value; 
		return $this;
	}

	/**
	 * Set an array of data, removes any existing data
	 *
	 */
	public function setData(array $data) {
		$this->data = $data; 
		return $this;
	}

	/**
	 * Set a variable to be included in the POST/GET request
	 *
	 * @param string $key
	 * @param string|int $value
	 * @return $this
	 *
	 */
	public function set($key, $value) {
		$this->$data[$key] = $value; 
		return $this;
	}

	/**
	 * Allows setting to $data via $http->key = $value
	 *
	 */
	public function __set($key, $value) {
		$this->set($key, $value);
	}

	/**
	 * Enables getting from $data via $http->key 
	 *
	 */
	public function __get($key) {
		return array_key_exists($key, $this->data) ? $this->data[$key] : null;
	}	

	/**
	 * Send the given $data array to a URL using either POST or GET
	 *
	 * @param string $url URL to post to (including http:// or https://)
	 * @param array $data Array of data to send (if not already set before)
	 * @param string $method Method to use (either POST or GET)
	 * @return bool|string False on failure or string of contents received on success.
	 *
	 */
	protected function send($url, array $data = array(), $method = 'POST') { 

		if(count($data)) $this->setData($data);
		if($method !== 'GET') $method = 'POST';

		if(!ini_get('allow_url_fopen')) return $this->sendSocket($url, $method); 

		$content = http_build_query($this->data); 
		$this->setHeader('content-length', strlen($content)); 

		$header = '';
		foreach($this->headers as $key => $value) $header .= "$key: $value\r\n";

		$options = array(
			'http' => array( 
				'method' => $method,
				'content' => $content,
				'header' => $header
				)
			);      

		$context = @stream_context_create($options); 
		$fp = @fopen($url, 'rb', false, $context); 
		if(!$fp) return false;

		$result = @stream_get_contents($fp); 
		return $result;
	}

	/**
	 * Alternate method of sending when allow_url_fopen isn't allowed
	 *
	 */
	protected function sendSocket($url, $method = 'POST') {

		$timeoutSeconds = 3; 
		if($method != 'GET') $method = 'POST';

		$info = parse_url($url);
		$host = $info['host'];
		$path = empty($info['path']) ? '/' : $info['path'];

		if($info['scheme'] == 'https') {
			$port = 443; 
			$scheme = 'ssl://';
		} else {
			$port = empty($info['port']) ? 80 : $info['port'];
			$scheme = '';
		}

		$content = http_build_query($this->data); 

		$this->setHeader('content-length', strlen($content));

		$request = "$method $path HTTP/1.0\r\nHost: $host\r\n";

		foreach($this->headers as $key => $value) {
			$request .= "$key: $value\r\n";
		}

		$response = '';
		$errno = '';
		$errstr = '';

		if(false !== ($fs = fsockopen($scheme . $host, $port, $errno, $errstr, $timeoutSeconds))) {
			fwrite($fs, "$request\r\n$content");
			while(!feof($fs)) {
				// get 1 tcp-ip packet per iteration
				$response .= fgets($fs, 1160); 
			}
			fclose($fs);
		}

		// skip past the headers in the response, so that it is consistent with 
		// the results returned by the regular send() method
		$pos = strpos($response, "\r\n\r\n"); 
		$response = substr($response, $pos+4); 

		return $response;

	}


}
