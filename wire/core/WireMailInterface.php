<?php

/**
 * ProcessWire WireMail Interface
 *
 * ProcessWire 2.x 
 * Copyright (C) 2014 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://processwire.com
 *
 */

interface WireMailInterface {

	/**
	 * Set the email to address
	 *
	 * @param string|array $email Specify a single email address or an array of multiple email addresses.
	 * @return this 
	 * @throws WireException if any provided emails were invalid
	 *
	 */
	public function to($email);	

	/**
	 * Set the email from address
	 *
	 * @param string Must be a single email address
	 * @return this 
	 * @throws WireException if provided email was invalid
	 *
	 */
	public function from($email); 

	/**
	 * Set the email subject
	 *
	 * @param string $subject 
	 * @return this 
	 *
	 */
	public function subject($subject); 

	/**
	 * Set the email message body (text only)
	 *
	 * @param string $body in text only
	 * @return this 
	 *
	 */
	public function body($message); 

	/**
	 * Set the email message body (HTML only)
	 *
	 * @param string $body in HTML
	 * @return this 
	 *
	 */
	public function bodyHTML($body); 

	/**
	 * Set any email header
	 *
	 * @param string $key
	 * @param string $value
	 * @return this 
	 *
	 */
	public function header($key, $value); 

	/**
	 * Set any email param 
	 *
	 * See $additional_parameters at: http://www.php.net/manual/en/function.mail.php
	 *
	 * @param string $value
	 * @return this 
	 *
	 */
	public function param($value); 

	/**
	 * Send the email
	 *
	 * @return int Returns number of messages sent or 0 on failure
	 *
	 */
	public function ___send(); 
}
