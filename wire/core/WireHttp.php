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

class WireHttp extends Wire { 

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
	 * Raw data, when data is not an array
	 *
	 */
	protected $rawData = null;

	/**
	 * Last response header
	 *
	 */
	protected $responseHeader = array();

	/**
	 * Last error message
	 *
	 */
	protected $error = '';

	/**
	 * Send to a URL using POST
	 *
	 * @param string $url URL to post to (including http:// or https://)
	 * @param mixed $data Array of data to send (if not already set before) or raw data
	 * @return bool|string False on failure or string of contents received on success.
	 *
	 */
	public function post($url, $data = array()) {
		if(!isset($this->headers['content-type'])) $this->setHeader('content-type', 'application/x-www-form-urlencoded; charset=utf-8');
		return $this->send($url, $data, 'POST');
	}

	/**
	 * Send to a URL using GET
	 *
	 * @param string $url URL to post to (including http:// or https://)
	 * @param mixed $data Array of data to send (if not already set before) or raw data to send
	 * @return bool|string False on failure or string of contents received on success.
	 *
	 */
	public function get($url, $data = array()) {
		return $this->send($url, $data, 'GET');
	}

	/**
	 * Send to a URL that responds with JSON using GET and return the resulting array or object
	 *
	 * @param string $url URL to post to (including http:// or https://)
	 * @param bool $assoc Default is to return an array (specified by TRUE). If you want an object instead, specify FALSE. 
	 * @param mixed $data Array of data to send (if not already set before) or raw data to send
	 * @return bool|array|object False on failure or an array or object on success. 
	 *
	 */
	public function getJSON($url, $assoc = true, $data = array()) {
		return json_decode($this->get($url, $data), $assoc); 
	}

	/**
	 * Send to a URL using HEAD (@horst)
	 *
	 * @param string $url URL to request (including http:// or https://)
	 * @param mixed $data Array of data to send (if not already set before) or raw data to send
	 * @return bool|array False on failure or Arrray with ResponseHeaders on success.
	 *
	 */
	public function head($url, $data = array()) {
		$responseHeader = $this->send($url, $data, 'HEAD');
		return is_array($responseHeader) ? $responseHeader : false;
	}

	/**
	 * Send to a URL using HEAD and return the status code (@horst)
	 *
	 * @param string $url URL to request (including http:// or https://)
	 * @param mixed $data Array of data to send (if not already set before) or raw data
	 * @param bool $textMode When true function will return a string rather than integer, see the statusText() method.
	 * @return bool|integer|string False on failure or integer of status code (200|404|etc) on success.
	 *
	 */
	 public function status($url, $data = array(), $textMode = false) {
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
	 * @param mixed $data Array of data to send (if not already set before) or raw data
	 * @return bool|string False on failure or string of status code + text on success.
	 *	Example: "200 OK', "302 Found", "404 Not Found"
	 *
	 */
	public function statusText($url, $data = array()) {
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
	 *  
	 *
	 */
	public function setData($data) {
		if(is_array($data)) $this->data = $data; 
			else $this->rawData = $data; 
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
	protected function send($url, $data = array(), $method = 'POST') { 

		$this->error = '';
		$this->responseHeader = array();
		$unmodifiedURL = $url;

		if(!empty($data)) $this->setData($data);
		if($method !== 'GET') $method = 'POST';

		$useSocket = false; 
		if(strpos($url, 'https://') === 0 && !extension_loaded('openssl')) $useSocket = true; 
		if(!ini_get('allow_url_fopen')) $useSocket = true; 
		if($useSocket) return $this->sendSocket($url, $method); 

		if(!empty($this->data)) {
			$content = http_build_query($this->data); 
			if($method === 'GET' && strlen($content)) { 
				$url .= (strpos($url, '?') === false ? '?' : '&') . $content; 
				$content = '';
			}
		} else if(!empty($this->rawData)) {
			$content = $this->rawData; 
		} else {
			$content = '';
		}

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
		if(!$fp) {
			//$this->error = "fopen() failed, see result of getResponseHeader()";
			//if(isset($http_response_header)) $this->responseHeader = $http_response_header; 
			return $this->sendSocket($unmodifiedURL, $method); 
		}

		$result = @stream_get_contents($fp); 
		if(isset($http_response_header)) $this->responseHeader = $http_response_header; 
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
		$query = empty($info['query']) ? '' : '?' . $info['query'];

		if($info['scheme'] == 'https') {
			$port = 443; 
			$scheme = 'ssl://';
		} else {
			$port = empty($info['port']) ? 80 : $info['port'];
			$scheme = '';
		}

		if(!empty($this->data)) {
			$content = http_build_query($this->data); 
			if($method === 'GET' && strlen($content)) { 
				$query .= (strpos($query, '?') === false ? '?' : '&') . $content; 
				$content = '';
			}
		} else if(!empty($this->rawData)) {
			$content = $this->rawData; 
		} else {
			$content = '';
		}

		$this->setHeader('content-length', strlen($content));

		$request = "$method $path$query HTTP/1.0\r\nHost: $host\r\n";

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
		if(strlen($errstr)) $this->error = $errno . ': ' . $errstr; 

		// skip past the headers in the response, so that it is consistent with 
		// the results returned by the regular send() method
		$pos = strpos($response, "\r\n\r\n"); 
		$this->responseHeader = explode("\r\n", substr($response, 0, $pos)); 
		$response = substr($response, $pos+4); 

		return $response;

	}

	/**
	 * Download a file from a URL and save it locally
	 * 
	 * Code originated from @soma's Modules Manager
	 * 
	 * @param $fromURL URL of file you want to download.
	 * @param $toFile Filename you want to save it to (including full path).
	 * @param array $options Optional aptions array for PHP's stream_context_create().
	 * @return string Filename that was downloaded (including full path).
	 * @throws WireException All error conditions throw exceptions. 
	 * 
	 */
	public function download($fromURL, $toFile, array $options = array()) {

		if((substr($fromURL, 0, 8) == 'https://') && !extension_loaded('openssl')) {
			throw new WireException($this->_('WireHttp::download-OpenSSL extension required but not available.'));
		}

		// Define the options
		$defaultOptions = array(
				'max_redirects' => 3
				); 
		$options = array_merge($defaultOptions, $options);
		$context = stream_context_create(array('http' => $options));

		// download the file
		$content = file_get_contents($fromURL, false, $context);
		if($content === false) {
			throw new WireException($this->_('File could not be downloaded:') . ' ' . htmlentities($fromURL));
		}

		if(($fp = fopen($toFile, 'wb')) === false) {
			throw new WireException($this->_('fopen error for filename:') . ' ' . htmlentities($toFile));
		}

		fwrite($fp, $content);
		fclose($fp);
		
		$chmodFile = $this->wire('config')->chmodFile; 
		if($chmodFile) chmod($toFile, octdec($chmodFile));
		
		return $toFile;
	}

	/**
	 * Get the last HTTP response header
	 * 
	 * Useful to examine for errors if your request returned false
	 *
	 * @return array
	 *
	 */
	public function getResponseHeader() {
		return $this->responseHeader; 
	}

	/**
	 * Get a string of the last error message
	 *
	 * @return string
	 *
	 */
	public function getError() {
		return $this->error; 
	}	

}
