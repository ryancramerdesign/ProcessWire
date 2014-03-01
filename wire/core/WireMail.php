<?php

/**
 * ProcessWire WireMail 
 *
 * ProcessWire 2.x 
 * Copyright (C) 2014 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://processwire.com
 *
 * USAGE:
 *
 * $mail = new WireMail(); 
 * 
 * // chained method call usage
 * $mail->to('user@domain.com')->from('you@company.com')->subject('Message Subject')->body('Message Body')->send();
 *
 * // separate method call usage
 * $mail->to('user@domain.com'); // specify CSV string or array for multiple addresses
 * $mail->from('you@company.com'); 
 * $mail->subject('Message Subject'); 
 * $mail->body('Message Body'); 
 * $mail->send();
 *
 * // you can also set values without function calls:
 * $mail->to = 'user@domain.com';
 * $mail->from = 'you@company.com';
 * ...and so on.
 *
 * // other methods or properties you might set (or get)
 * $mail->bodyHTML('<html><body><h1>Message Body</h1></body></html>'); 
 * $mail->header('X-Mailer', 'ProcessWire'); 
 * $mail->param('-f you@company.com'); // PHP mail() param (envelope from example)
 *
 * // note that the send() function always returns the quantity of messages sent
 * $numSent = $mail->send();
 *
 */

class WireMail extends WireData implements WireMailInterface {

	/**
	 * Mail properties
	 *
	 */
	protected $mail = array(
		'to' => array(),
		'from' => '', 
		'fromName' => '', 
		'subject' => '', 
		'body' => '',
		'bodyHTML' => '',
		'header' => array(),
		'param' => array(), 
		);

	public function __construct() {
		$this->mail['header']['X-Mailer'] = "ProcessWire/" . $this->className();
	}


	public function __get($key) {
		if(array_key_exists($key, $this->mail)) return $this->mail[$key]; 
		return parent::__get($key); 
	}

	public function __set($key, $value) {
		if(array_key_exists($key, $this->mail)) $this->$key($value); 
			else parent::__set($key, $value); 
	}

	protected function sanitizeEmail($email) {
		$email = strtolower(trim($email)); 
		$clean = $this->wire('sanitizer')->email($email); 
		if($email != $clean) throw new WireException("Invalid email address"); 
		return $clean;
	}

	protected function sanitizeHeader($header) {
		return $this->wire('sanitizer')->emailHeader($header); 
	}

	/**
	 * Set the email to address
	 *
	 * @param string|array $email Specify a single email address or an array of multiple email addresses.
	 * @return this 
	 * @throws WireException if any provided emails were invalid
	 *
	 */
	public function to($email) {
		if(!is_array($email)) $email = explode(',', $email); 
		$this->mail['to'] = array(); // clear
		foreach($email as $e) {
			$this->mail['to'][] = $this->sanitizeEmail($e); 
		}
		return $this; 
	}

	/**
	 * Set the email from address
	 *
	 * @param string Must be a single email address
	 * @return this 
	 * @throws WireException if provided email was invalid
	 *
	 */
	public function from($email) {

		if(strpos($email, '<') !== false && strpos($email, '>') !== false) {
			// email has separate from name and email
			if(preg_match('/^(.*?)<([^>]+)>.*$/', $email, $matches)) {
				$this->mail['fromName'] = $this->sanitizeHeader($matches[1]);
				$email = $matches[2];
			}
		}

		$this->mail['from'] = $this->sanitizeEmail($email); 
		return $this; 
	}

	/**
	 * Set the email subject
	 *
	 * @param string $subject 
	 * @return this 
	 *
	 */
	public function subject($subject) {
		$this->mail['subject'] = $this->wire('sanitizer')->emailHeader($subject); 	
		return $this; 
	}

	/**
	 * Set the email message body (text only)
	 *
	 * @param string $body in text only
	 * @return this 
	 *
	 */
	public function body($body) {
		$this->mail['body'] = $body; 
		return $this; 
	}

	/**
	 * Set the email message body (HTML only)
	 *
	 * @param string $body in HTML
	 * @return this 
	 *
	 */
	public function bodyHTML($body) {
		$this->mail['bodyHTML'] = $body; 
		return $this; 
	}

	/**
	 * Set any email header
	 *
	 * Note: multiple calls will append existing headers. 
	 * To remove an existing header, specify NULL as the value. 
	 *
	 * @param string $key
	 * @param string $value
	 * @return this 
	 *
	 */
	public function header($key, $value) {
		if(is_null($value)) {
			unset($this->mail['header'][$key]); 
		} else { 
			$k = $this->wire('sanitizer')->name($this->wire('sanitizer')->emailHeader($key)); 
			// ensure consistent capitalization for all header keys
			$k = ucwords(str_replace('-', ' ', $k)); 
			$k = str_replace(' ', '-', $k); 
			$v = $this->wire('sanitizer')->emailHeader($value); 
			$this->mail['header'][$k] = $v; 
		}
		return $this; 
	}

	/**
	 * Set any email param 
	 *
	 * See $additional_parameters at: http://www.php.net/manual/en/function.mail.php
	 * Note: multiple calls will append existing params. 
	 * To remove an existing param, specify NULL as the value. 
	 * This function may only be applicable to PHP mail().
	 *
	 * @param string $value
	 * @return this 
	 *
	 */
	public function param($value) {
		if(is_null($value)) {
			$this->mail['param'] = array();
		} else { 
			$this->mail['param'][] = $value; 
		}
		return $this; 
	}

	/**
	 * Send the email
	 *
	 * This is the primary method that modules extending this class would want to replace.
	 *
	 * @return int Returns a positive number (indicating number of addresses emailed) or 0 on failure. 
	 *
	 */
	public function ___send() {

		$header = '';
		$from = $this->from;
		if(!strlen($from)) $from = $this->wire('config')->adminEmail;
		if(!strlen($from)) $from = 'processwire@' . $this->wire('config')->httpHost; 

		if($this->fromName) {
			$fromName = $this->fromName; 
			if(strpos($fromName, ',') !== false) $fromName = '"' . str_replace('"', ' ', $fromName) . '"';
			$header = "From: $fromName <$from>"; 
		} else {
			$header = "From: $from";
		}

		foreach($this->header as $key => $value) $header .= "\r\n$key: $value";

		$param = $this->wire('config')->phpMailAdditionalParameters;
		if(is_null($param)) $param = '';
		foreach($this->param as $value) $param .= " $value";		

		$header = trim($header); 
		$param = trim($param); 
		$body = '';
		$text = $this->body; 
		$html = $this->bodyHTML;

		if($this->bodyHTML) {
			if(!strlen($text)) $text = strip_tags($html); 
			$boundary = "==Multipart_Boundary_x" . md5(time()) . "x";
			$header .= "\r\nMIME-Version: 1.0";
			$header .= "\r\nContent-Type: multipart/alternative;\r\n  boundary=\"$boundary\"";
			$body = "This is a multi-part message in MIME format.\r\n\r\n" . 
				"--$boundary\r\n" . 
				"Content-Type: text/plain; charset=\"utf-8\"\r\n" . 
				"Content-Transfer-Encoding: 7bit\r\n\r\n" . 
				"$text\r\n\r\n" . 
				"--$boundary\r\n" . 
				"Content-Type: text/html; charset=\"utf-8\"\r\n" . 
				"Content-Transfer-Encoding: 7bit\r\n\r\n" . 
				"$html\r\n\r\n" . 
				"--$boundary--\r\n";
		} else {
			$body = $text; 
		}

		$numSent = 0;
		foreach($this->to as $to) {
			if(mail($to, $this->subject, $body, $header, $param)) $numSent++;
		}

		return $numSent; 
	}
}
