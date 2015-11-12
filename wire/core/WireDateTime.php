<?php

/**
 * ProcessWire WireDateTime 
 * 
 * Provides helpers for working with dates/times and conversion between formats.
 * 
 * This class can be used outside of ProcessWire (minus relative date support).
 *
 * ProcessWire 2.x
 * Copyright (C) 2015 by Ryan Cramer
 * This file licensed under Mozilla Public License v2.0 http://mozilla.org/MPL/2.0/
 *
 * https://processwire.com
 * 
 * @todo in PW 3.0 move wireDate() and wireRelativeTimeStr() function implementation into this class. 
 *
 */

class WireDateTime {
	
	/**
	 * Date formats in date() format
	 *
	 */
	static protected $dateFormats = array(

		// Gregorian little-endian, starting with day (used by most of world)
		'l, j F Y', // Monday, 1 April 2012
		'j F Y',	// 1 April 2012
		'd-M-Y',	// 01-Apr-2012
		'dMy',		// 01Apr12
		'd/m/Y',	// 01/04/2012
		'd.m.Y',	// 01.04.2012
		'd/m/y',	// 01/04/12
		'd.m.y',	// 01.04.12
		'j/n/Y',	// 1/4/2012
		'j.n.Y',	// 1.4.2012
		'j/n/y',	// 1/4/12
		'j.n.y',	// 1.4.12

		// Gregorian big-endian, starting with year (used in Asian countries, Hungary, Sweden, and US armed forces)
		'Y-m-d',	// 2012-04-01 (ISO-8601)
		'Y/m/d',	// 2012/04/01 (ISO-8601)
		'Y.n.j',	// 2012.4.1 (common in China)
		'Y/n/j',	// 2012/4/1 
		'Y F j',	// 2012 April 1
		'Y-M-j, l',	// 2012-Apr-1, Monday
		'Y-M-j',	// 2012-Apr-1
		'YMj',		// 2012Apr1

		// Middle-endian, starting with month (US and some Canada)
		'l, F j, Y',	// Monday, April 1, 2012
		'F j, Y',	// April 1, 2012
		'M j, Y',	// Apr 1, 2012
		'm/d/Y',	// 04/01/2012 
		'm.d.Y',	// 04.01.2012 
		'm/d/y',	// 04/01/12 
		'm.d.y',	// 04.01.12 
		'n/j/Y',	// 4/1/2012 
		'n.j.Y',	// 4.1.2012 
		'n/j/y',	// 4/1/12 
		'n.j.y',	// 4.1.12 

		// Other
		//'%x', 	// 04/01/12 (locale based date)
		//'%c', 	// Tue Feb 5 00:45:10 2012 (locale based date/time)
		// 'U',		// Unix Timestamp

	);

	static protected $timeFormats = array(
		'g:i a',	// 5:01 pm
		'h:i a',	// 05:01 pm
		'g:i A',	// 5:01 PM
		'h:i A',	// 05:01 PM
		'H:i',		// 17:01 (with leading zeros)
		'H:i:s',	// 17:01:01 (with leading zeros)

		// Relative (see wireDate in /wire/core/Functions.php)
		'!relative',
		'!relative-',
		'!rel',
		'!rel-',
		'!r',
		'!r-',
	);

	/**
	 * Date/time translation table from PHP date() to strftime() and JS/jQuery
	 *
	 */
	static protected $dateConversion = array(
		// date  	strftime js    	regex
		'l' => array(	'%A', 	'DD', 	'\w+'), 			// Name of day, long			Monday
		'D' => array(	'%a', 	'D', 	'\w+'),				// Name of day, short			Mon
		'F' => array(	'%B', 	'MM', 	'\w+'),				// Name of month, long			April
		'M' => array(	'%b', 	'M', 	'\w+'),				// Name of month, short			Apr
		'j' => array(	'$-d', 	'd', 	'\d{1,2}'),			// Day without leading zeros		1
		'd' => array(	'$d', 	'dd', 	'\d{2}'),			// Day with leading zeros		01
		'n' => array(	'$-m', 	'm', 	'\d{1,2}'),			// Month without leading zeros		4
		'm' => array(	'$m', 	'mm', 	'\d{2}'),			// Month with leading zeros		04
		'y' => array(	'%y', 	'y', 	'\d{2}'),			// Year 2 character			12
		'Y' => array(	'%Y', 	'yy', 	'\d{4}'),			// Year 4 character			2012
		'N' => array(	'%u', 	'', 	'[1-7]'),			// Day of the week (1-7)		1
		'w' => array(	'%w', 	'', 	'[0-6]'),			// Zero-based day of week (0-6)		0
		'S' => array(	'', 	'', 	'\w{2}'),			// Day ordinal suffix			st, nd, rd, th
		'z' => array(	'%-j', 	'o', 	'\d{1,3}'),			// Day of the year (0-365)		123
		'W' => array(	'%W', 	'', 	'\d{1,2}'),			// Week # of the year			42
		't' => array(	'', 	'', 	'(28|29|30|31)'),		// Number of days in month		28
		'o' => array(	'%V', 	'', 	'\d{1,2}'),			// ISO-8601 week number			42
		'a' => array(	'%P',	'tt',	'[aApP][mM]'),			// am or pm				am
		'A' => array(	'%p',	'TT',	'[aApP][mM]'),			// AM or PM				AM
		'g' => array(	'%-I',	'h', 	'\d{1,2}'),			// 12-hour format, no leading zeros	5
		'h' => array(	'%I', 	'hh', 	'\d{2}'),			// 12-hour format, leading zeros	05
		'G' => array(	'%-H', 	'h24', 	'\d{1,2}'),			// 24-hour format, no leading zeros	5
		'H' => array(	'%H', 	'hh24', '\d{2}'),			// 24-hour format, leading zeros	05
		'i' => array(	'%M', 	'mm', 	'[0-5][0-9]'),			// Minutes				09
		's' => array(	'%S', 	'ss', 	'[0-5][0-9]'), 			// Seconds				59
		'e' => array(	'', 	'', 	'\w+'),				// Timezone Identifier			UTC, GMT, Atlantic/Azores
		'I' => array(	'', 	'', 	'[01]'),			// Daylight savings time?		1=yes, 0=no
		'O' => array(	'', 	'', 	'[-+]\d{4}'),			// Difference to Greenwich time/hrs	+0200
		'P' => array(	'', 	'', 	'[-+]\d{2}:\d{2}'),		// Same as above, but with colon	+02:00
		'T' => array(	'', 	'', 	'\w+'),				// Timezone abbreviation		MST, EST
		'Z' => array(	'', 	'', 	'-?\d+'),			// Timezone offset in seconds		-43200 through 50400
		'c' => array(	'', 	'', 	'[-+:T\d]{19,25}'),		// ISO-8601 date			2004-02-12T15:19:21+00:00
		'r' => array(	'', 	'',		'\w+, \d+ \w+ \d{4}'),		// RFC-2822 date			Thu, 21 Dec 2000 16:01:07 +0200
		'U' => array(	'%s', 	'@', 	'\d+'),				// Unix timestamp			123344556	
	);

	/**
	 * Return all predefined PHP date() formats for use as dates
	 *
	 */
	public function getDateFormats() { return self::$dateFormats; }
	public static function _getDateFormats() { return self::$dateFormats; }

	/**
	 * Return all predefined PHP date() formats for use as times
	 *
	 */
	public function getTimeFormats() { return self::$timeFormats; }
	public static function _getTimeFormats() { return self::$timeFormats; }

	/**
	 * Given a date/time string and expected format, convert it to a unix timestamp
	 *
	 * @param string $str Date/time string
	 * @param string $format Format of the date/time string in PHP date syntax
	 * @return int
	 *
	 */
	public function stringToTimestamp($str, $format) {

		if(empty($str)) return '';

		// already a timestamp
		if(ctype_digit(ltrim($str, '-'))) return (int) $str;

		$format = trim($format);
		if(!strlen($format)) return strtotime($str);

		// use PHP 5.3's date parser if its available
		if(function_exists('date_parse_from_format')) {
			// PHP 5.3+
			$a = date_parse_from_format($format, $str);
			if(isset($a['warnings']) && count($a['warnings'])) {
				foreach($a['warnings'] as $warning) {
					if(function_exists('wire')) {
						wire()->warning($warning . " (value='$str', format='$format')");
					} else {
						// some other warning system, outside of ProcessWire usage
					}
				}
			}
			if($a['year'] && $a['month'] && $a['day']) {
				return mktime($a['hour'], $a['minute'], $a['second'], $a['month'], $a['day'], $a['year']);
			}
		}

		$regex = '!^' . $this->convertDateFormat($format, 'regex') . '$!';
		if(!preg_match($regex, $str, $m)) {
			// wire('pages')->message("'$format' - '$regex' - '$str'"); 
			// if we can't match it, then just send it to strtotime
			return strtotime($str);
		}

		$s = '';

		// month
		if(isset($m['n'])) $s .= $m['n'] . '/';
			else if(isset($m['m'])) $s .= $m['m'] . '/';
			else if(isset($m['F'])) $s .= $m['F'] . ' ';
			else if(isset($m['M'])) $s .= $m['M'] . ' ';
			else return strtotime($str);

		// separator character
		$c = substr($s, -1);

		// day
		if(isset($m['j'])) $s .= $m['j'] . $c;
			else if(isset($m['d'])) $s .= $m['d'] . $c;
			else $s .= '1' . $c;

		// year
		if(isset($m['y'])) $s .= $m['y'];
			else if(isset($m['Y'])) $s .= $m['Y'];
			else $s .= date('Y');

		$s .= ' ';
		$useTime = true;

		// hour 
		if(isset($m['g'])) $s .= $m['g'] . ':';
			else if(isset($m['h'])) $s .= $m['h'] . ':';
			else if(isset($m['G'])) $s .= $m['G'] . ':';
			else if(isset($m['H'])) $s .= $m['H'] . ':';
			else $useTime = false;

		// time
		if($useTime) {
			// minute
			if(isset($m['i'])) {
				$s .= $m['i'];
				// second
				if(isset($m['s'])) $s .= ':' . $m['s'];
			}
			// am/pm
			if(isset($m['a'])) $s .= ' ' . $m['a'];
				else if(isset($m['A'])) $s .= ' ' . $m['A'];
		}

		return strtotime($s);
	}

	/**
	 * Format a date with the given PHP date() or PHP strftime() format
	 *
	 * @param int $value Unix timestamp of date
	 * @param string $format date() or strftime() format string to use for formatting
	 * @return string Formatted date string
	 *
	 */
	public function formatDate($value, $format) {

		if(!$value) return '';
		if(!strlen($format) || $format == 'U' || $format == '%s') return (int) $value; // unix timestamp
		$relativeStr = '';

		if(strpos($format, '!') !== false) {
			if(function_exists('wireDate')) {
				if(preg_match('/([!][relativ]+-?)/', $format, $matches)) {
					$relativeStr = wireDate(ltrim($matches[1], '!'), $value);
					$format = str_replace($matches[1], '///', $format);
				}
			} else {
				// usage outside of ProcessWire, relative dates not supported
			}
		}

		if(strpos($format, '%') !== false) {
			// use strftime() if format string contains a %
			if(strpos($format, '%-') !== false) {
				// not all systems support the '%-' option in strftime to trim leading zeros
				// so we are doing our own implementation here
				$TRIM0 = true;
				$format = str_replace('%-', 'TRIM0%', $format);
			} else {
				$TRIM0 = false;
			}
			$value = strftime($format, $value);
			if($TRIM0) $value = str_replace(array('TRIM00', 'TRIM0'), '', $value);

		} else if(function_exists('wireDate')) {
			// use ProcessWire's wireDate()
			$value = wireDate($format, $value);
		} else {
			// usage outside of ProcessWire
			$value = date($format, $value);
		}

		if(strlen($relativeStr)) $value = str_replace('///', $relativeStr, $value);

		return $value;
	}

	/**
	 * Given a date() format, convert it to either 'js', 'strftime' or 'regex' format
	 *
	 * @param string $format PHP date() format
	 * @param string $type New format to convert to: either 'js', 'strftime', or 'regex'
	 * @return string
	 *
	 */
	public function convertDateFormat($format, $type) {

		$newFormat = '';
		$lastc = '';

		for($n = 0; $n < strlen($format); $n++) {

			$c = $format[$n];

			if($c == '\\') {
				// begin escaped character
				$lastc = $c;
				continue;
			}

			if($lastc == '\\') {
				// literal character, not translated
				$lastc = $c;
				$newFormat .= $c;
				continue;
			}

			if(!isset(self::$dateConversion[$c])) {
				// unknown character
				if($type == 'regex' && in_array($c, array('.', '[', ']', '(', ')', '*', '+', '?'))) {
					$c = '\\' . $c; // escape regex chars
				}
				$newFormat .= $c;
				continue;
			}

			list($strftime, $js, $regex) = self::$dateConversion[$c];
			if($type == 'js') {
				$newFormat .= $js;
			} else if($type == 'strftime') {
				$newFormat .= $strftime;
			} else if($type == 'regex') {
				$newFormat .= '\b(?<' . $c . '>' . $regex . ')\b'; // regex captured with name of date() format char
			} else {
				$newFormat .= $c;
			}

			$lastc = $c;
		}

		return $newFormat;
	}
	
	
}