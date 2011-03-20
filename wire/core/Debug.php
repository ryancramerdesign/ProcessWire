<?php

/**
 * ProcessWire Debug 
 *
 * Provides methods useful for debugging or development. 
 *
 * Currently only provides timer capability. 
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

class Debug {
	
	static protected $timers = array();

	/**
	 * Measure time between two events
	 *
	 * First call should be to $key = Debug::timer() with no params, or provide your own key that's not already been used
	 * Second call should pass the key given by the first call to get the time elapsed, i.e. $time = Debug::timer($key)
	 *
	 */
	static public function timer($key = '') {
		// returns number of seconds elapsed since first call

		if(!$key || !isset(self::$timers[$key])) {
			preg_match('/(\.[0-9]+) ([0-9]+)/', microtime(), $time);
			$startTime = doubleval($time[1]) + doubleval($time[2]);
			if(!$key) $key = $startTime; 
			self::$timers[$key] = $startTime; 
			return $key; 
		} else {
			preg_match('/(\.[0-9]*) ([0-9]*)/', microtime(), $time);
			$endTime = doubleval($time[1]) + doubleval($time[2]);
			$startTime = self::$timers[$key]; 
			$runTime = number_format($endTime - $startTime, 4);
			return $runTime;
		}
	}

}
