<?php

/**
 * ProcessWire WireCache
 *
 * Simple cache or storing strings (encoded or otherwise) and serves as $cache API var
 *
 * ProcessWire 2.x
 * Copyright (C) 2015 by Ryan Cramer
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 *
 * http://processwire.com
 * 
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
	 * Optionally specify expiration time and/or a cache generation function to use when cache needs to be created.
	 * 
	 * @param string|array $name Provide a single cache name, an array of cache names, or an asterisk cache name.
	 * 	If given a single cache name (string) just the contents of that cache will be returned.
	 * 	If given an array of names, multiple caches will be returned, indexed by cache name. 
	 * 	If given a cache name with an asterisk in it, it will return an array of all matching caches. 
	 * @param int|string|null Optionally specify max age (in seconds) OR oldest date string.
	 * 	If cache exists and is older, then blank returned. You may omit this to divert to whatever expiration
	 * 	was specified at save() time. Note: The $expire and $func arguments may optionally be reversed. 
	 * @param function|callable $func Optionally provide a function/closure that generates the cache value and it 
	 * 	will be used when needed.This option requires that only one cache is being retrieved (not an array of caches). 
	 * 	Note: The $expire and $func arguments may optionally be reversed. 
	 * @return string|array|null Returns null if cache doesn't exist and no generation function provided. 
	 * @throws WireException if given invalid arguments
	 * 
	 * 
	 */
	public function get($name, $expire = null, $func = null) {
		
		if(!is_null($expire)) {
			if(!is_int($expire) && is_callable($expire)) {
				$_func = $func;
				$func = $expire; 
				$expire = is_null($_func) ? null : $this->getExpires($_func);
				unset($_func);
			} else {
				$expire = $this->getExpires($expire);
			}
		}

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
		$wildcards = array();
		$n = 0;
		
		foreach($names as $name) {
			$n++;
			if(strpos($name, '*') !== false || strpos($name, '%') !== false) {
				// retrieve all caches matching wildcard
				$wildcards[$name] = $name; 
				$name = str_replace('*', '%', $name); 
				$multi = true; 
				$where[$n] = "name LIKE :name$n";
			} else {
				$where[$n] = "name=:name$n";
			}
			$binds[":name$n"] = $name; 
		}
		
		if($multi && !is_null($func)) {
			throw new WireException("Function (\$func) may not be specified to \$cache->get() when requesting multiple caches.");
		}
		
		$sql = "SELECT name, data FROM caches WHERE (" . implode(' OR ', $where) . ") ";
		
		if(is_null($expire)) {
			$sql .= "AND (expires>=:now OR expires<=:never) ";
			$binds[':now'] = date(self::dateFormat, time());
			$binds[':never'] = self::expireNever;
		} else {
			$sql .= "AND expires<=:expire ";
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
		
		if($multi) {
			foreach($names as $name) {
				// ensure there is at least a placeholder for all requested caches
				if(!isset($values[$name]) && !isset($wildcards[$name])) $values[$name] = '';
			}
			foreach($values as $k => $v) {
				if(is_array($v) && isset($v['PageArray'])) {
					$values[$k] = $this->arrayToPageArray($v);
				}
			}
		} else if(is_array($value) && isset($value['PageArray'])) {
			$value = $this->arrayToPageArray($value);
			
		} else if(empty($value) && !is_null($func) && is_callable($func)) {
			// generate the cache now from the given callable function
			$value = $this->renderCacheValue($name, $expire, $func); 
		}
		
		return $multi ? $values : $value; 
	}

	/**
	 * Render and save a cache value, when given a function to do so
	 * 
	 * Provided $func may specify any arguments that correspond with the names of API vars
	 * and it will be sent those arguments. 
	 * 
	 * Provided $func may either echo or return it's output. If any value is returned by
	 * the function it will be used as the cache value. If no value is returned, then 
	 * the output buffer will be used as the cache value. 
	 * 
	 * @param string $name
	 * @param int|string|null $expire
	 * @param callable $func
	 * @return bool|string
	 * @since Version 2.5.28
	 * 
	 */
	protected function renderCacheValue($name, $expire, $func) {
		
		$ref = new ReflectionFunction($func);
		$params = $ref->getParameters(); // requested arguments
		$args = array(); // arguments we provide
		
		foreach($params as $param) {
			$arg = null;
			// if requested param is an API variable we will provide it
			if(preg_match('/\$([_a-zA-Z0-9]+)\b/', $param, $matches)) $arg = $this->wire($matches[1]);
			$args[] = $arg;
		}

		ob_start();
		
		if(count($args)) {
			$value = call_user_func_array($func, $args);
		} else {
			$value = $func();
		}
		
		$out = ob_get_contents();
		ob_end_clean();
		
		if(empty($value) && !empty($out)) $value = $out; 

		if($value !== false) {
			$this->save($name, $value, $expire);
		}
		
		return $value; 
	}

	/**
	 * Same as get() but with namespace
	 * 
	 * @param string|object $ns Namespace
	 * @param string $name
	 * @param null|int|string $expire
	 * @param callable|null $func
	 * @return string|array
	 * 
	 */
	public function getFor($ns, $name, $expire = null, $func = null) {
		if(is_object($ns)) $ns = get_class($ns); 
		return $this->get($ns . "__$name", $expire, $func); 
	}

	/**
	 * Save data to cache with given name
	 * 
	 * @param string $name Name of cache, can be any string up to 255 chars
	 * @param string|array|PageArray $data Data that you want to cache 
	 * @param int|Page $expire Lifetime of this cache, in seconds
	 * 		...or specify: WireCache::expireHourly, WireCache::expireDaily, WireCache::expireWeekly, WireCache::expireMonthly
	 * 		...or specify the future date you want it to expire (as unix timestamp or any strtotime compatible date format)
	 * 		...or provide a Page object to expire when any page using that template is saved.
	 * 		...or specify: WireCache::expireNever to prevent expiration.
	 * 		...or specify: WireCache::expireSave to expire when any page or template is saved.
	 * @return bool Returns true if cache was successful, false if not
	 * @throws WireException if given uncachable data
	 * 
	 */
	public function save($name, $data, $expire = self::expireDaily) {

		if(is_object($data)) {
			if($data instanceof PageArray) {
				$data = $this->pageArrayToArray($data); 
			} else if(method_exists($data, '__toString')) {
				$data = (string) $data; 
			} else {
				throw new WireException("WireCache::save does not know how to cache values of type " . get_class($data));
			}
		}
	
		if(is_array($data)) $data = json_encode($data);

		$sql = 
			'INSERT INTO caches (name, data, expires) VALUES(:name, :data, :expires) ' . 
			'ON DUPLICATE KEY UPDATE data=VALUES(data), expires=VALUES(expires)';
					
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
		
		$this->maintenance();
	
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
	 * General maintenance will only run once per request, and won't run at all during ajax requests. 
	 * If you want to force it to run regardless, specify boolean true for the $obj argument. 
	 * 
	 * @param Template|Page|null|bool Item to queue for expiration or omit to execute maintenance now
	 * 	Specify boolean true to force maintenance to run (see note above)
	 * @return bool
	 * 
	 */
	public function maintenance($obj = null) {
		
		static $done = false;
		$forceRun = false;
		
		if(is_object($obj)) {
			// queue items for later cache clearing
			if($obj instanceof Page) {
				$this->templateIDs[$obj->template->id] = (int) $obj->template->id;
			} else if($obj instanceof Template) {
				$this->templateIDs[$obj->id] = (int) $obj->id;
			} else {
				return false;
			}
			return true; 
		} else if($obj === true) {
			// force run, even if run earlier
			$forceRun = true; 
			$done = true; 
		} else {
			// only perform maintenance once per request
			if($done) return true; 
			$done = true; 
		}
		
		$database = $this->wire('database');
		
		// perform maintenance now	
		$sql = 'DELETE FROM caches WHERE (expires<=:now AND expires>:never) ';
		
		if(count($this->templateIDs)) {
			$dates = array();
			foreach($this->templateIDs as $id) {
				$dates[] = "'" . date(self::dateFormat, $id) . "'";
			}
			$sql .= 'OR expires=:expireSave '; 
			$sql .= 'OR expires IN(' . implode(',', $dates) . ')';
		} else {
			// don't perform general maintenance during ajax requests
			if($this->wire('config')->ajax && !$forceRun) return;
		}
		
		$query = $database->prepare($sql, "cache.maintenance()");
		$query->bindValue(':now', date(self::dateFormat, time()));
		$query->bindValue(':never', self::expireNever);
		
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
	
	/**
	 * Convert a cacheable array to a PageArray
	 *
	 * @param array $data
	 * @return PageArray
	 * @since Version 2.5.28
	 *
	 */
	protected function arrayToPageArray(array $data) {

		$pageArrayClass = isset($data['pageArrayClass']) ? $data['pageArrayClass'] : 'PageArray';

		if(!isset($data['PageArray']) || !is_array($data['PageArray'])) {
			return new $pageArrayClass();
		}

		$options = array();
		$template = empty($data['template']) ? null : $this->wire('templates')->get((int) $data['template']);
		if($template) $options['template'] = $template;
		if($pageArrayClass != 'PageArray') $options['pageArrayClass'] = $pageArrayClass;
		if(!empty($data['pageClass']) && $data['pageClass'] != 'Page') $options['pageClass'] = $data['pageClass'];

		return $this->wire('pages')->getById($data['PageArray'], $options);
	}

	/**
	 * Given a PageArray, convert it to a cachable array
	 *
	 * @param PageArray $items
	 * @return array
	 * @throws WireException
	 * @since Version 2.5.28
	 *
	 */
	protected function pageArrayToArray(PageArray $items) {

		$templates = array();
		$ids = array();
		$pageClasses = array();

		foreach($items as $item) {
			$templates[$item->template->id] = $item->template->id;
			$ids[] = $item->id;
			$pageClass = $item->className();
			$pageClasses[$pageClass] = $pageClass;
		}

		if(count($pageClasses) > 1) {
			throw new WireException("Can't cache multiple page types together: " . implode(', ', $pageClasses));
		}

		$data = array(
			'PageArray' => $ids,
			'template'  => count($templates) == 1 ? reset($templates) : 0,
		);

		$pageClass = reset($pageClasses);
		if($pageClass && $pageClass != 'Page') $data['pageClass'] = $pageClass;

		$pageArrayClass = $items->className();
		if($pageArrayClass != 'PageArray') $data['pageArrayClass'] = $pageArrayClass;

		return $data;
	}

}

