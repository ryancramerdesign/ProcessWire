<?php

/**
 * ProcessWire Log
 *
 * WireLog represents the ProcessWire $log API variable.
 * It is an API-friendly interface to the FileLog class.
 *
 * ProcessWire 2.x
 * Copyright (C) 2013 by Ryan Cramer
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 *
 * http://processwire.com
 *
 */

class WireLog extends Wire {

	protected $logExtension = 'txt';

	/**
	 * Record an informational or 'success' message in the message log (messages.txt)
	 *
	 * @param string $text Message to log
	 * @param bool|int $flags Specify boolean true to also have the message displayed interactively (admin only).
	 * @return $this
	 *
	 */
	public function message($text, $flags = 0) {
		$flags = $flags === true ? Notice::log : $flags | Notice::logOnly;
		return parent::message($text, $flags);
	}

	/**
	 * Record an error message in the error log (errors.txt)
	 *
	 * Note: Fatal errors should instead throw a WireException.
	 *
	 * @param string $text
	 * @param int|bool $flags Specify boolean true to also display the error interactively (admin only).
	 * @return $this
	 *
	 */
	public function error($text, $flags = 0) {
		$flags = $flags === true ? Notice::log : $flags | Notice::logOnly;
		return parent::error($text, $flags);
	}

	/**
	 * Save text to a caller-defined log
	 * 
	 * If the log doesn't currently exist, it will be created. 
	 * 
	 * The log filename is /site/assets/logs/[name].txt
	 * 
	 * @param string $name Name of log to save to (word consisting of [-._a-z0-9] and no extension)
	 * @param string $text Text to save to the log
	 * @return bool Whether it was written (usually assumed to be true)
	 * @throws WireException
	 * 
	 */
	public function ___save($name, $text) {
		$log = new FileLog($this->getFilename($name));
		$log->setDelimeter("\t");
		$user = $this->wire('user');
		$page = $this->wire('page');
		$text = str_replace(array("\r", "\n", "\t"), ' ', strip_tags($text));
		$line = ($user ? $user->name : "?") . "\t" . ($page ? $page->httpUrl : "?") . "\t" . $text;
		return $log->save($line);
	}

	/**
	 * Get the full filename (including path) for the given log name
	 * 
	 * @param string $name
	 * @return string
	 * @throws WireException
	 * 
	 */
	public function getFilename($name) {
		if($name !== $this->wire('sanitizer')->pageName($name)) throw new WireException("Log name must contain only [-_.a-z0-9] with no extension");
		return $this->wire('config')->paths->logs . $name . '.' . $this->logExtension;
	}

	/**
	 * Return the given number of entries from the end of log file
	 * 
	 * @param string $name Name of log 
	 * @param int $limit Number of entries to retrieve (default = 10)
	 * @return array
	 * @todo add pagination capability
	 * 
	 */
	public function get($name, $limit = 10) {
		$log = new FileLog($this->getFilename($name));
		$chunkSize = $limit * 255; // 255 = guesstimate of average line length
		$lines = array();
		$cnt = 0;
		$lastCnt = 0;
		while(1) {
			$lines = $log->get($chunkSize);
			$cnt = count($lines);
			if(!$cnt || $cnt >= $limit || $lastCnt == $cnt) break;
			$lastCnt = $cnt; 
			$chunkSize += ($limit - $cnt) * $log->getMaxLineLength();
		} 
		if($cnt > $limit) $lines = array_slice($lines, 0, $limit);
		return $lines;	
	}

	/**
	 * Return an array of entries that exist in the given range of dates
	 * 
	 * @param string $name Name of log 
	 * @param int|string $dateFrom Unix timestamp or string date/time to start from 
	 * @param int|string $dateFrom Unix timestamp or string date/time to end at (default = now)
	 * @return array
	 * 
	 */
	public function getDate($name, $dateFrom, $dateTo = 0) {
		$log = new FileLog($this->getFilename($name));
		$log->setDelimeter("\t");
		return $log->getDate($dateFrom, $dateTo); 
	}

}
