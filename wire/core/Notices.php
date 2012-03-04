<?php

/**
 * ProcessWire Notices
 *
 * Contains notices/messages used by the application to the user. 
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

/**
 * Base class that holds a message, source class, and timestamp
 *
 */
abstract class Notice extends WireData {

	/**
	 * Flag indicates the notice is for when debug mode is on only
	 *
	 */
	const debug = 2;
	const warning = 4; 

	public function __construct($text, $flags = 0) {
		$this->set('text', $text); 
		$this->set('class', ''); 
		$this->set('timestamp', time()); 
		$this->set('flags', $flags); 
	}
}

/**
 * A notice that's indicated to be informational
 *
 */
class NoticeMessage extends Notice { }

/**
 * A notice that's indicated to be an error
 *
 */
class NoticeError extends Notice { }

/**
 * A class to contain multiple Notice instances, whether messages or errors
 *
 */
class Notices extends WireArray {
	
	public function isValidItem($item) {
		return $item instanceof Notice; 
	}	

	public function makeBlankItem() {
		return new NoticeMessage(''); 
	}

	public function add($item) {
		if($item->flags & Notice::debug) {
			if(!$this->fuel('config')->debug) return $this;
		}
		return parent::add($item); 
	}

	public function hasErrors() {
		$numErrors = 0;
		foreach($this as $notice) {
			if($notice instanceof NoticeError) $numErrors++;
		}
		return $numErrors > 0;
	}
}
