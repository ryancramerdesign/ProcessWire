<?php

/**
 * ProcessWire WireCache
 *
 * Simple cache or storing strings (encoded or otherwise) and serves as $cache API var
 *
 * ProcessWire 2.x
 * Copyright (C) 2014 by Ryan Cramer
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 *
 * http://processwire.com
 *
 */

class WireCache extends Wire {

	/**
	 * Date indicates item does not expire
	 * 
	 */
	const noExpire = '2010-04-08 03:10:10';
	
	/**
	 * Get data from cache with given name
	 * 
	 * @param $name
	 * @return string
	 * 
	 */
	public function get($name) {
		$sql = "SELECT data FROM caches WHERE name=:name";
		$query = $this->wire('database')->prepare($sql); 
		$query->bindValue(':name', $name); 
		$value = '';
		
		try {
			$query->execute(); 
			if($query->rowCount()) $value = $query->fetchColumn(0); 
			$query->closeCursor();
			
		} catch(Exception $e) {
			$value = '';
		}
		
		return $value; 
	}

	/**
	 * Save data to cache with given name
	 * 
	 * @param string $name Name of cache, can be any string up to 255 chars
	 * @param string $data Data that you want to cache (currently must be a string)
	 * @param int $seconds Lifetime of this cache, in seconds, or WireCache::noExpire to prevent expiration
	 * @return bool Returns true if cache was successful, false if not
	 * 
	 */
	public function save($name, $data, $seconds = 3600) {
	
		$query = $this->wire('database')->prepare('DELETE FROM caches WHERE name=:name'); 
		$query->bindValue(':name', $name); 
		
		try {
			$query->execute();
			$query->closeCursor();
		} catch(Exception $e) {
			return false;
		}

		$sql = "INSERT INTO caches SET name=:name, data=:data, expires=:expires";
		$query = $this->wire('database')->prepare($sql); 
		$query->bindValue(':name', $name); 
		$query->bindValue(':data', $data); 
		$query->bindValue(':expires', $seconds == self::noExpire ? self::noExpire : date('Y-m-d H:i:s', time() + $seconds));
		
		try {
			$result = $query->execute();
			$this->message($this->_('Saved cache ') . ' - ' . $name, Notice::debug | Notice::log); 
		} catch(Exception $e) {
			$result = false; 
		}
	
		return $result;
	}

	/**
	 * Delete the cache identified by $name
	 * 
	 * @param string $name
	 * @return bool True on success, false on failure
	 * 
	 */
	public function delete($name) {
		try {
			$query = $this->wire('database')->prepare("DELETE FROM caches WHERE name=:name"); 
			$query->bindValue(':name', $name); 
			$query->execute();
			$success = true; 
			$this->message($this->_('Cleared cache') . ' - ' . $name, Notice::debug | Notice::log); 
		} catch(Exception $e) {
			$this->error($e->getMessage()); 
			$success = false;
		}
		return $success;
	}

	/**
	 * Delete expired caches
	 * 
	 * Should be called as part of a regular maintenance routine.
	 * 
	 */
	public function maintenance() {
		try {
			$query = $this->wire('database')->prepare('DELETE FROM caches WHERE expires < NOW() AND expires!=:noExpire'); 
			$query->bindValue(':noExpire', self::noExpire); 
			$query->execute();
		} catch(Exception $e) {
			$this->error($e->getMessage(), Notice::debug | Notice::log); 
		}
	}

}
