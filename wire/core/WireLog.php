<?php

/**
 * ProcessWire Log
 *
 * WireLog represents the ProcessWire $log API variable.
 * It is an API-friendly interface to the FileLog class.
 *
 * ProcessWire 2.x
 * Copyright (C) 2013 by Ryan Cramer
 * This file licensed under Mozilla Public License v2.0 http://mozilla.org/MPL/2.0/
 *
 * https://processwire.com
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
	 * Record a warning message in the warnings log (warnings.txt)
	 *
	 * @param string $text
	 * @param int|bool $flags Specify boolean true to also display the warning interactively (admin only).
	 * @return $this
	 *
	 */
	public function warning($text, $flags = 0) {
		$flags = $flags === true ? Notice::log : $flags | Notice::logOnly;
		return parent::warning($text, $flags);
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
	 * @param array $options Options to modify behavior (defaults: showUser=true, showURL=true, url='url', delimiter=\t)
	 * @return bool Whether it was written (usually assumed to be true)
	 * @throws WireException
	 * 
	 */
	public function ___save($name, $text, $options = array()) {
		
		$defaults = array(
			'showUser' => true,
			'showURL' => true,
			'url' => '', // URL to show (default=blank, auto-detect)
			'delimiter' => "\t",
			);
		
		$options = array_merge($defaults, $options);
		// showURL option was previously named showPage
		if(isset($options['showPage'])) $options['showURL'] = $options['showPage'];
		$log = $this->getFileLog($name, $options); 
		$text = str_replace(array("\r", "\n", "\t"), ' ', $text);
		
		if($options['showURL']) {
			if($options['url']) {
				$url = $options['url'];
			} else {
				$input = $this->wire('input');
				$url = $input ? $input->httpUrl() : '';
				if(strlen($url) && $input) {
					if(count($input->get)) {
						$url .= "?";
						foreach($input->get as $k => $v) {
							$k = $this->wire('sanitizer')->name($k);
							$v = $this->wire('sanitizer')->name($v);
							$url .= "$k=$v&";
						}
						$url = rtrim($url, "&");
					}
					if(strlen($url) > 500) $url = substr($url, 0, 500) . " ...";
				} else {
					$url = '?';
				}
			}
			$text = "$url$options[delimiter]$text";
		}
		
		if($options['showUser']) {
			$user = $this->wire('user');
			$text = ($user && $user->id ? $user->name : "?") . "$options[delimiter]$text";
		}
		
		return $log->save($text);
	}

	/**
	 * Return array of all logs
	 * 
	 * Each log entry is an array that includes the following:
	 * 	- name (string): Name of log file, excluding extension.
	 * 	- file (string): Full path and filename of log file. 
	 * 	- size (int): Size in bytes
	 * 	- modified (int): Last modified date (unix timestamp)
	 * 
	 * @return array 
	 * 
	 */
	public function getLogs() {
		
		$logs = array();
		$dir = new DirectoryIterator($this->wire('config')->paths->logs); 
		
		foreach($dir as $file) {
			if($file->isDot() || $file->isDir()) continue; 
			if($file->getExtension() != 'txt') continue; 
			$name = basename($file, '.txt'); 
			if($name != $this->wire('sanitizer')->pageName($name)) continue; 
			$logs[$name] = array(
				'name' => $name,
				'file' => $file->getPathname(),
				'size' => $file->getSize(), 
				'modified' => $file->getMTime(), 
			);
		}
		
		ksort($logs); 
		return $logs;	
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
		if($name !== $this->wire('sanitizer')->pageName($name)) {
			throw new WireException("Log name must contain only [-_.a-z0-9] with no extension");
		}
		return $this->wire('config')->paths->logs . $name . '.' . $this->logExtension;
	}

	/**
	 * Return the given number of entries from the end of log file
	 * 
	 * This method is pagination aware. 
	 * 
	 * @param string $name Name of log 
	 * @param array $options Specify any of the following: 
	 * 	- limit (integer): Specify number of lines.
	 * 	- text (string): Text to find.
	 * 	- dateFrom (int|string): Oldest date to match entries.
	 * 	- dateTo (int|string): Newest date to match entries.
	 * 	- reverse (bool): Reverse order (default=true)
	 * 	- pageNum (int): Pagination number 1 or above (default=0 which means auto-detect)
	 * @return array 
	 * 
	 */
	public function getLines($name, array $options = array()) {
		$pageNum = !empty($options['pageNum']) ? $options['pageNum'] : $this->wire('input')->pageNum;
		unset($options['pageNum']); 
		$log = $this->getFileLog($name); 
		$limit = isset($options['limit']) ? (int) $options['limit'] : 100; 
		return $log->find($limit, $pageNum); 
	}

	/**
	 * Same as getLines() but returns each log line as an associative array of each part of the line split up
	 * 
	 * @param $name Name of log file (excluding extension)
	 * @param array $options
	 * 	- limit (integer): Specify number of lines. 
	 * 	- text (string): Text to find. 
	 * 	- dateFrom (int|string): Oldest date to match entries. 
	 * 	- dateTo (int|string): Newest date to match entries. 
	 * 	- reverse (bool): Reverse order (default=true)
	 * 	- pageNum (int): Pagination number 1 or above (default=0 which means auto-detect)
	 * @return array(array(
	 * 	'date' => "ISO-8601 date string",
	 * 	'user' => "user name or boolean false if unknown", 
	 * 	'url' => "full URL or boolean false if unknown", 
	 * 	'text' => "text of the log entry"
	 * ));
	 * 
	 */
	public function getEntries($name, array $options = array()) {
		
		$log = $this->getFileLog($name);
		$limit = isset($options['limit']) ? $options['limit'] : 100; 
		$pageNum = !empty($options['pageNum']) ? $options['pageNum'] : $this->wire('input')->pageNum; 
		unset($options['pageNum']); 
		$lines = $log->find($limit, $pageNum, $options); 
		
		foreach($lines as $key => $line) {
			$entry = $this->lineToEntry($line); 
			$lines[$key] = $entry; 
		}
		
		return $lines; 
	}

	/**
	 * Convert a log line to an entry array
	 * 
	 * @param $line
	 * @return array
	 * 
	 */
	public function lineToEntry($line) {
		
		$parts = explode("\t", $line, 4);
		
		if(count($parts) == 2) {
			$entry = array(
				'date' => $parts[0], 
				'user' => '',
				'url'  => '',
				'text' => $parts[1]
			);
		} else if(count($parts) == 3) {
			$entry = array(
				'date' => $parts[0], 
				'user' => strpos($parts[1], '/') === false ? $parts[1] : '',
				'url'  => strpos($parts[1], '/') !== false ? $parts[1] : '',
				'text' => $parts[2]
			);
		} else {
			$entry = array(
				'date' => isset($parts[0]) ? $parts[0] : '',
				'user' => isset($parts[1]) ? $parts[1] : '',
				'url'  => isset($parts[2]) ? $parts[2] : '',
				'text' => isset($parts[3]) ? $parts[3] : '',
			);
		}
		
		$entry['date'] = wireDate(wire('config')->dateFormat, strtotime($entry['date']));
		$entry['user'] = wire('sanitizer')->pageName($entry['user']); 
		
		if($entry['url'] == 'page?') $entry['url'] = false;
		if($entry['user'] == 'user?') $entry['user'] = false;
		
		return $entry; 
	}

	/**
	 * Get the total number of entries present in the given log
	 * 
	 * @param $name
	 * @return int
	 * 
	 */
	public function getTotalEntries($name) {
		$log = $this->getFileLog($name); 
		return $log->getTotalLines(); 
	}
	
	/**
	 * Get lines from log file (deprecated)
	 *
	 * @param $name
	 * @param int $limit
	 * @param array $options
	 * @deprecated Use getLines() or getEntries() intead.
	 * @return array
	 *
	 */
	public function get($name, $limit = 100, array $options = array()) {
		return $this->getLines($name, $limit, $options);
	}

	/**
	 * Return an array of log lines that exist in the given range of dates
	 * 
	 * Pagination aware. 
	 * 
	 * @param string $name Name of log 
	 * @param int|string $dateFrom Unix timestamp or string date/time to start from 
	 * @param int|string $dateTo Unix timestamp or string date/time to end at (default = now)
	 * @param int $limit Max items per pagination
	 * @return array
	 * @deprecated Use getLines() or getEntries() with dateFrom/dateTo $options instead. 
	 * 
	 */
	public function getDate($name, $dateFrom, $dateTo = 0, $limit = 100) {
		$log = $this->getFileLog($name); 
		$pageNum = $this->wire('input')->pageNum();
		return $log->getDate($dateFrom, $dateTo, $pageNum, $limit); 
	}
	
	/**
	 * Delete the log file identified by $name
	 *
	 * @param $name
	 * @return bool
	 *
	 */
	public function delete($name) {
		$log = $this->getFileLog($name);
		return $log->delete();
	}

	/**
	 * Prune log file to contain only entries from last n days
	 * 
	 * @param string $name
	 * @param int $days
	 * @return int Number of items in new log file or booean false on failure
	 * @throws WireException
	 * 
	 */
	public function prune($name, $days) {
		$log = $this->getFileLog($name);
		if($days < 1) throw new WireException("Prune days must be 1 or more"); 
		$oldestDate = strtotime("-$days DAYS"); 
		return $log->pruneDate($oldestDate); 
	}

	/**
	 * Returns instance of FileLog for given log name
	 * 
	 * @param $name
	 * @param array $options
	 * @return FileLog
	 * 
	 */
	public function getFileLog($name, array $options = array()) {
		$log = new FileLog($this->getFilename($name));
		if(isset($options['delimiter'])) $log->setDelimeter($options['delimiter']);
			else $log->setDelimeter("\t");
		return $log;
	}

}
