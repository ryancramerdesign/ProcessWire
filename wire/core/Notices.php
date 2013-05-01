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
	const warning = 4; 
	const log = 8; 
	const logOnly = 16;

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
		return 'messages.txt';
	}
}

/**
 * A notice that's indicated to be an error
 *
 */
class NoticeError extends Notice { 
	public function getLogName() {
		return 'errors.txt';
	}
}

/**
 * A class to contain multiple Notice instances, whether messages or errors
 *
 */
class Notices extends WireArray {
	
	protected $logPath = '';
	
	public function __construct($logPath = '') {
		if(empty($logPath)) $logPath = $this->wire('config')->paths->logs; 
		$this->logPath = rtrim($logPath, '/') . '/';
	}
	
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
		$log = new FileLog($this->logPath . $item->getLogName());
		$log->setDelimeter("\t");
		$user = $this->wire('user');
		$page = $this->wire('page');
		$text = str_replace(array("\r", "\n", "\t"), ' ', $item->text);
		if($this->wire('config')->debug && $item->class) $text .= "\t($item->class)"; 
		$line = ($user ? $user->name : "?") . "\t" . 
				($page ? $page->httpUrl : "?") . "\t" . 
				$text;
		$log->save($line); 
	}

	public function hasErrors() {
		$numErrors = 0;
		foreach($this as $notice) {
			if($notice instanceof NoticeError) $numErrors++;
		}
		return $numErrors > 0;
	}
}
