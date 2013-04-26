<?php

/**
 * ProcessWire CSRF Protection
 *
 * ProcessWire 2.x 
 * Copyright (C) 2012 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 *
 */

/**
 * Triggered when CSRF detected
 *
 */
class WireCSRFException extends WireException {}

/**
 * ProcessWire CSRF Protection class
 *
 */
class SessionCSRF extends Wire {

	/**
	 * Get a CSRF Token name, or create one if it doesn't yet exist
	 *
	 * @return string
	 *
	 */
	public function getTokenName() {
		$tokenName = $this->session->get('_token_name'); 
		if(!$tokenName) { 
			$tokenName = 'TOKEN' . mt_rand();
			$this->session->set('_token_name', $tokenName);
		}
		return $tokenName; 
	}

	/**
	 * Get a CSRF Token value as stored in the session, or create one if it doesn't yet exist
	 *
	 * @return string
	 *
	 */
	public function getTokenValue() {
		$tokenName = $this->getTokenName();
		$tokenValue = $this->session->get('_' . $tokenName);
		if(empty($tokenValue)) {
			$tokenValue = md5($this->page->path() . mt_rand() . microtime()) . md5($this->page->name . $this->config->userAuthSalt . mt_rand()); 
			$this->session->set('_' . $tokenName, $tokenValue); 
		}
		return $tokenValue; 
	}

	/**
	 * Returns true if the current POST request contains a valid CSRF token, false if not
	 *
	 * @return bool
	 *
	 */
	public function hasValidToken() {
		$tokenName = $this->getTokenName();
		$tokenValue = $this->getTokenValue();
		if($this->config->ajax && isset($_SERVER["HTTP_X_$tokenName"]) && $_SERVER["HTTP_X_$tokenName"] === $tokenValue) return true; 
		if($this->input->post($tokenName) === $tokenValue) return true; 
		// if this point is reached, token was invalid
		return false; 
	}

	/**
	 * Throws an exception if the token is invalid
	 *
	 */
	public function validate() {
		if(!$this->config->protectCSRF) return true; 
		if($this->hasValidToken()) return true;
		$this->resetToken();
		throw new WireCSRFException($this->_('This request was aborted because it appears to be forged.')); 
	}

	/**
	 * Clear out any token values
	 *
	 */
	public function resetToken() {
		$tokenName = $this->getTokenName();
		$this->session->remove('_token_name'); 
		$this->session->remove('_' . $tokenName); 
	}
}
