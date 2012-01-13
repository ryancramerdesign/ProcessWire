<?php

/**
 * ProcessWire FileLog
 *
 * Creates and maintains a text-based log file.
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 * @TODO FileLog needs additioanl documentation.
 *
 */

class FileLog {

	protected $logFilename = false; 
	protected $itemsLogged = array(); 
	protected $delimeter = ':';
	protected $maxLineLength = 2048;
	protected $fileExtension = 'txt';

	/**
	 * Construct the FileLog
 	 *
	 * @param string $path Path where the log will be stored (path should have trailing slash)
	 * 	This may optionally include the filename if you intend to leave the second param blank.
	 * @param string $identifier Basename for the log file, not including the extension. 
	 * 
	 */
	public function __construct($path, $identifier = '') {
		if($identifier) {
			$this->logFilename = "$path$identifier.{$this->fileExtension}";
		} else {
			$this->logFilename = $path; 
		}
	}

	protected function cleanStr($str) {
		$str = strip_tags($str); 
		$str = preg_replace('/[\r\n]/', ' ', $str); 
		$str = trim($str); 
		if(strlen($str) > $this->maxLineLength) $str = substr($str, 0, $this->maxLineLength); 
		return $str; 	
	}

	public function save($str) {

		if(!$this->logFilename) return false; 

		$hash = md5($str); 

		// if we've already logged this during this session, then don't do it again
		if(in_array($hash, $this->itemsLogged)) return true; 

		$ts = date("Y-m-d H:i:s"); 
		$str = $this->cleanStr($str); 

		if($fp = fopen($this->logFilename, "a")) {

			$trys = 0; 
			$stop = false;

			while(!$stop) {
				if(flock($fp, LOCK_EX)) {
					fwrite($fp, "$ts{$this->delimeter}$str\n"); 
					flock($fp, LOCK_UN); 
					$this->itemsLogged[] = $hash; 
					$stop = true; 
				} else {
					usleep(2000);
					if($trys++ > 20) $stop = true; 
				}
			}

			fclose($fp); 
			return true; 
		} else {
			return false;
		}

	}

	public function size() {
		return filesize($this->logFilename); 
	}

	public function filename() {
		return basename($this->logFilename);
	}

	public function pathname() {
		return $this->logFilename; 
	}

	public function mtime() {
		return filemtime($this->logFilename); 
	}

	public function get($chunkSize = 4096) {

		// gets a chunk of the log file from the end
		// same as a unix tail function
		// returns an array of lines

		$chunkNum = 1; 	
		$chunkSize = 4096; 		
		$offset = -1 * ($chunkSize * $chunkNum); 
		$lines = array();

		if(!$fp = fopen($this->logFilename, "r")) return $lines; 
	
		fseek($fp, $offset, SEEK_END);
		$data = fread($fp, $chunkSize); 
		fclose($fp); 

		$lines = explode("\n", $data); 
		return array_reverse($lines); 
	}

	public function prune($bytes) {

		$filename = $this->logFilename; 

		// prune this log file to $bytes size

		if(!$filename || filesize($filename) <= $bytes) return 0; 

		$fpr = fopen($filename, "r"); 	
		$fpw = fopen("$filename.new", "w"); 
		if(!$fpr || !$fpw) return false;

		fseek($fpr, ($bytes * -1), SEEK_END); 
		fgets($fpr, 1024); // first line likely just a partial line, so skip it
		$cnt = 0;

		while(!feof($fpr)) {
			$line = fgets($fpr, 1024); 
			fwrite($fpw, $line); 
			$cnt++;
		}

		fclose($fpw);
		fclose($fpr); 

		if($cnt) {
			unlink($filename); 
			rename("$filename.new", $filename); 
		} else {
			@unlink("$filename.new"); 
		}
	
		return $cnt;	
	}


	public function __toString() {
		return $this->filename(); 
	}	

	public function setDelimeter($c) {
		$this->delimeter = $c; 
	}

	public function setMaxLineLength($c) {
		$this->maxLineLength = (int) $c; 
	}

	public function setFileExtension($ext) {
		$this->fileExtension = $ext; 
	}
}


