<?php

/**
 * ProcessWire Notices
 *
 * Contains notices/messages used by the application to the user. 
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2013 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://processwire.com
 *
 */

/**
 * Base class that holds a message, source class, and timestamp
 * 
 * @property string $text Text of notice
 * @property string $class Class of notice
 * @property int $timestamp When the notice was generated
 * @property int $flags Optional flags bitmask of Notice::debug and/or Notice::warning
 *
 */
abstract class Notice extends WireData {

	/**
	 * Flag indicates the notice is for when debug mode is on only
	 *
	 */
	const debug = 2;

	/**
	 * Flag indicates the notice is a warning
	 *
	 */
	const warning = 4; 

	/**
	 * Flag indicates the notice will also be sent to the messages or errors log
	 *
	 */
	const log = 8; 

	/**
	 * Flag indicates the notice will be logged, but not shown
	 *
	 */
	const logOnly = 16;

	/**
	 * Flag indicates the notice is allowed to contain markup and won't be automatically entity encoded
	 *
	 * Note: entity encoding is done by the admin theme at output time. 
	 *
	 */
	const allowMarkup = 32;

	/**
	 * Create the Notice
	 *
	 * @param string $text
	 * @param int $flags
	 *
	 */
	public function __construct($text, $flags = 0) {
		$this->set('text', $text); 
		$this->set('class', ''); 
		$this->set('timestamp', time()); 
		$this->set('flags', $flags); 
	}

	/**
	 * @return string Name of log (basename)
	 * 
	 */
	abstract public function getLogName();
}

/**
 * A notice that's indicated to be informational
 *
 */
class NoticeMessage extends Notice { 
	public function getLogName() {
		return 'messages';
	}
}

/**
 * A notice that's indicated to be an error
 *
 */
class NoticeError extends Notice { 
	public function getLogName() {
		return 'errors';
	}
}

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
			if(!$this->wire('config')->debug) return $this;
		}
	
		if(($item->flags & Notice::log) || ($item->flags & Notice::logOnly)) {
			$this->addLog($item);
			if($item->flags & Notice::logOnly) return $this;
		}
		
		return parent::add($item); 
	}
	
	protected function addLog($item) {
		$text = $item->text;
		if($this->wire('config')->debug && $item->class) $text .= " ($item->class)"; 
		$this->wire('log')->save($item->getLogName(), $text); 
	}

	public function hasErrors() {
		$numErrors = 0;
		foreach($this as $notice) {
			if($notice instanceof NoticeError) $numErrors++;
		}
		return $numErrors > 0;
	}
}
