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
 * @todo make it able to cache PageArrays (by caching IDs to array in save, and getById in get)
 *
 */

class WireCache extends Wire {

	/**
	 * Expiration constants that may be supplied to WireCache::save $seconds argument. 
	 * 
	 */
	const expireNever = '2010-04-08 03:10:10';
	const expireSave = '2010-01-01 01:01:01';
	const expireNow = 0;
	const expireHourly = 3600; 
	const expireDaily = 86400;
	const expireWeekly = 604800;
	const expireMonthly = 2419200;

	/**
	 * Date format used by our DB queries
	 * 
	 */
	const dateFormat = 'Y-m-d H:i:s';
	
	protected $instanceID = 0; 

	/**
	 * Array of template IDs of saved pages to be handled by maintenance()
	 * 
	 * @var array
	 * 
	 */
	protected $templateIDs = array();

	/**
	 * Preloaded cache values, indexed by cache name
	 * 
	 * @var array
	 * 
	 */
	protected $preloads = array();
	
	public function __construct() {
		$this->instanceID = mt_rand();
	}

	/**
	 * Preload the given caches, so that they will be returned without query on the next get() call
	 * 
	 * After a preloaded cache is returned from a get() call, it is removed from local storage. 
	 * 
	 * @param string|array $names
	 * @param null $expire
	 * 
	 */
	public function preload(array $names, $expire = null) {
		if(!is_array($names)) $names = array($names);
		$this->preloads = array_merge($this->preloads, $this->get($names, $expire));
	}
	
	/**
	 * Get data from cache with given name
	 * 
	 * @param string|array $name Provide a single cache name, or an array of cache names. 
	 * 	If given a single cache name (string) just the contents of that cache will be returned.
	 * 	If given an array of names, multiple caches will be returned, indexed by cache name. 
	 * @param int|string|null Optionally specify max age (in seconds) OR oldest date string.
	 * 	If cache exists and is older, then blank returned. 
	 * @return string|array|null Returns null if cache doesn't exist
	 * 
	 */
	public function get($name, $expire = null) {
		
		if(!is_null($expire)) $expire = $this->getExpires($expire); 

		$multi = is_array($name); // retrieving multiple caches at once?
		if($multi) {
			$names = $name;
		} else {
			if(isset($this->preloads[$name])) {
				$value = $this->preloads[$name];
				unset($this->preloads[$name]); 
				return $value; 
			}
			$names = array($name); 	
		}
		
		$where = array();
		$binds = array();
		$n = 0;
		
		foreach($names as $name) {
			$n++;
			$where[$n] = "name=:name$n";
			$binds[":name$n"] = $name; 
		}
		
		// $sql = "SELECT name, data FROM caches WHERE name=:name";
		$sql = "SELECT name, data FROM caches WHERE (" . implode(' OR ', $where) . ")";
		
		if(!is_null($expire)) {
			$sql .= " AND expires<=:expire";
			$binds[':expire'] = $expire; 
		}
		
		$query = $this->wire('database')->prepare($sql, "cache.get(" . implode('|', $names) . ", " . ($expire ? $expire : "null") . ")"); 
		
		foreach($binds as $key => $value) $query->bindValue($key, $value);
		
		$value = ''; // return value for non-multi mode
		$values = array(); // return value for multi-mode
		
		try {
			$query->execute(); 
			if($query->rowCount() == 0) {
				$value = null; // cache does not exist
			} else while($row = $query->fetch(PDO::FETCH_NUM)) {
				list($name, $value) = $row;
				$c = substr($value, 0, 1);
				if($c == '{' || $c == '[') {
					$_value = json_decode($value, true);
					if(is_array($_value)) $value = $_value;
				}
				if($multi) $values[$name] = $value; 
			}
			$query->closeCursor();
				
		} catch(Exception $e) {
			$value = null;
		}
		
		if($multi) foreach($names as $name) {
			// ensure there is at least a placeholder for all requested caches
			if(!isset($values[$name])) $values[$name] = '';
		}
		
		return $multi ? $values : $value; 
	}

	/**
	 * Same as get() but with namespace
	 * 
	 * @param string|object $ns Namespace
	 * @param string $name
	 * @param null|int|string $expire
	 * @return string|array
	 * 
	 */
	public function getFor($ns, $name, $expire = null) {
		if(is_object($ns)) $ns = get_class($ns); 
		return $this->get($ns . "__$name", $expire); 
	}

	/**
	 * Save data to cache with given name
	 * 
	 * @param string $name Name of cache, can be any string up to 255 chars
	 * @param string|array $data Data that you want to cache (currently must be a string or an array)
	 * @param int|Page $expire Lifetime of this cache, in seconds
	 * 		...or specify: WireCache::expireHourly, WireCache::expireDaily, WireCache::expireWeekly, WireCache::expireMonthly
	 * 		...or specify the future date you want it to expire (as unix timestamp or any strtotime compatible date format)
	 * 		...or provide a Page object to expire when any page using that template is saved.
	 * 		...or specify: WireCache::expireNever to prevent expiration.
	 * 		...or specify: WireCache::expireSave to expire when any page or template is saved.
	 * @return bool Returns true if cache was successful, false if not
	 * 
	 */
	public function save($name, $data, $expire = self::expireDaily) {
	
		$query = $this->wire('database')->prepare('DELETE FROM caches WHERE name=:name'); 
		$query->bindValue(':name', $name); 
		
		try {
			$query->execute();
			$query->closeCursor();
		} catch(Exception $e) {
			return false;
		}
	
		if(is_array($data)) $data = json_encode($data); 
	
		$sql = 'INSERT INTO caches SET name=:name, data=:data, expires=:expires';
		$query = $this->wire('database')->prepare($sql, "cache.save($name)"); 
		$query->bindValue(':name', $name); 
		$query->bindValue(':data', $data); 
		$query->bindValue(':expires', $this->getExpires($expire)); 
		
		try {
			$result = $query->execute();
			$this->message($this->_('Saved cache ') . ' - ' . $name, Notice::debug); 
		} catch(Exception $e) {
			$result = false; 
		}
	
		return $result;
	}

	/**
	 * Same as save() except with namespace
	 * 
	 * @param string|object $ns Namespace
	 * @param $name
	 * @param $data
	 * @param int $expire
	 * @return bool 
	 * 
	 */
	public function saveFor($ns, $name, $data, $expire = self::expireDaily) {
		if(is_object($ns)) $ns = get_class($ns); 
		return $this->save($ns . "__$name", $data, $expire); 
	}

	/**
	 * Given a $expire seconds, date, page, or template convert it to an ISO-8601 date
	 * 
	 * @param $expire
	 * @return string
	 * 
	 */
	protected function getExpires($expire) {
		
		if(is_object($expire) && $expire->id) {

			if($expire instanceof Page) {
				// page object
				$expire = $expire->template->id;

			} else if($expire instanceof Template) {
				// template object
				$expire = $expire->id;

			} else {
				// unknown object, substitute default
				$expire = time() + self::expireDaily;
			}

		} else if(in_array($expire, array(self::expireNever, self::expireSave))) {
			// good, we'll take it as-is
			return $expire; 

		} else {

			// account for date format as string
			if(is_string($expire) && !ctype_digit("$expire")) $expire = strtotime($expire);

			if($expire === 0 || $expire === "0") {
				// zero is allowed if that's what was specified
				$expire = (int) $expire; 
			} else {
				// zero is not allowed because it indicates failed type conversion
				$expire = (int) $expire;
				if(!$expire) $expire = self::expireDaily;
			}

			if($expire > time()) {
				// a future date has been specified, so we'll keep it
			} else {
				// a quantity of seconds has been specified, add it to current time
				$expire = time() + $expire;
			}
		}

		$expire = date(self::dateFormat, $expire);
		
		return $expire; 
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
			$query = $this->wire('database')->prepare("DELETE FROM caches WHERE name=:name", "cache.delete($name)"); 
			$query->bindValue(':name', $name); 
			$query->execute();
			$success = true; 
			$this->message($this->_('Cleared cache') . ' - ' . $name, Notice::debug); 
		} catch(Exception $e) {
			$this->error($e->getMessage()); 
			$success = false;
		}
		return $success;
	}

	/**
	 * Delete expired caches or queue an expiration
	 * 
	 * Should be called as part of a regular maintenance routine.
	 * 
	 * @param Template|Page|null Item to queue for expiration or omit to execute maintenance now
	 * @return bool
	 * 
	 */
	public function maintenance($obj = null) {
		
		if(!is_null($obj)) {
			// queue items for later cache clearing
			if($obj instanceof Page) {
				$this->templateIDs[$obj->template->id] = (int) $obj->template->id;
			} else if($obj instanceof Template) {
				$this->templateIDs[$obj->id] = (int) $obj->id;
			} else {
				return false;
			}
			return true; 
		}
		
		$database = $this->wire('database');
		
		// perform maintenance now	
		$sql = 'DELETE FROM caches WHERE (expires<=:now AND expires>:date) ';
		
		if(count($this->templateIDs)) {
			$dates = array();
			foreach($this->templateIDs as $id) $dates[] = "'" . date(self::dateFormat, $id) . "'";
			$sql .= 'OR expires=:expireSave '; 
			$sql .= 'OR expires IN(' . implode(',', $dates) . ')';
		} else {
			// don't perform general maintenance during ajax requests
			if($this->wire('config')->ajax) return;
		}
		
		$query = $database->prepare($sql, "cache.maintenance()");
		$query->bindValue(':now', date(self::dateFormat, time()));
		$query->bindValue(':date', self::expireNever);
		
		if(count($this->templateIDs)) {
			$query->bindValue(':expireSave', self::expireSave); 
			$this->templateIDs = array();
		}
		
		try {
			$query->execute();
			
		} catch(Exception $e) {
			$this->error($e->getMessage(), Notice::debug | Notice::log);
			return false;
		}
		
		return true;
	}
}

