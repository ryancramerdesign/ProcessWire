<?php

/**
 * ProcessWire Language Functions
 *
 * Provide GetText like language translation functions to ProcessWire
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2011 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

/**
 * Perform a language translation
 * 
 * @param string $text Text for translation. 
 * @param string $textdomain Textdomain for the text, may be class name, filename, or something made up by you. If ommitted, a debug backtrace will attempt to determine it automatically.
 * @param string $context Name of context - DO NOT USE with this function for translation as it won't be parsed for translation. Use only with the _x() function, which will be parsed. 
 * @return string Translated text or original text if translation not available.
 *
 *
 */
function __($text, $textdomain = null, $context = '') {
	if(!Wire::getFuel('languages')) return $text; 
	if(!$language = Wire::getFuel('user')->language) return $text; 
	if(!$language->id) return $text; 
	if(is_null($textdomain)) {
		if(defined('DEBUG_BACKTRACE_IGNORE_ARGS')) $traces = @debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
          		else $traces = @debug_backtrace();
		// $traces = @debug_backtrace(defined('DEBUG_BACKTRACE_IGNORE_ARGS') ? DEBUG_BACKTRACE_IGNORE_ARGS : false);
		if(isset($traces[0]) && $traces[0]['file'] != __FILE__) $textdomain = $traces[0]['file'];
			else if(isset($traces[1]) && $traces[1]['file'] != __FILE__) $textdomain = $traces[1]['file'];
		if(is_null($textdomain)) $textdomain = 'site';
	}
	return htmlspecialchars($language->translator()->getTranslation($textdomain, $text, $context), ENT_QUOTES, 'UTF-8'); 
}

/**
 * Perform a language translation in a specific context
 * 
 * Used when to text strings might be the same in English, but different in other languages. 
 * 
 * @param string $text Text for translation. 
 * @param string $context Name of context
 * @param string $textdomain Textdomain for the text, may be class name, filename, or something made up by you. If ommitted, a debug backtrace will attempt to determine automatically.
 * @return string Translated text or original text if translation not available.
 *
 */
function _x($text, $context, $textdomain = null) {
	return __($text, $textdomain, $context); 	
}

/**
 * Perform a language translation with singular and plural versions
 * 
 * @param string $textSingular Singular version of text (when there is 1 item)
 * @param string $textPlural Plural version of text (when there are multiple items or 0 items)
 * @param string $textdomain Textdomain for the text, may be class name, filename, or something made up by you. If ommitted, a debug backtrace will attempt to determine automatically.
 * @return string Translated text or original text if translation not available.
 *
 */
function _n($textSingular, $textPlural, $count, $textdomain = null) {
	return $count == 1 ? __($textSingular, $textdomain) : __($textPlural, $textdomain); 	
}


