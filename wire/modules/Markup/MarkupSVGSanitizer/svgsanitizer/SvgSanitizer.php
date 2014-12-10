<?php
/**
 *  SVGSanitizer
 *
 *  Whitelist-based PHP SVG sanitizer.
 *
 *  @link https://github.com/alister-/SVG-Sanitizer}
 *  @author Alister Norris
 *  @copyright Copyright (c) 2013 Alister Norris
 *  @license http://opensource.org/licenses/mit-license.php The MIT License
 *  @package svgsanitizer
 *
 *  Modified by Adrian Jones to fix some bugs (mostly iteration mistakes)
 *  - save sanitized version back to original file
 *  - move whitelist from here to a field setting in PW
 */

class SvgSanitizer {

	//private $remoteHref = false;		// Check if hrefs in XML can goto remote locations
	private $xmlDoc;					// PHP XML DOMDocument
	private $filename;


	function __construct() {
		$this->xmlDoc = new DOMDocument();
		$this->xmlDoc->preserveWhiteSpace = false;
	}

	// load XML SVG
	function load($filename) {
		$this->filename = $filename;
		$this->xmlDoc->load($this->filename);
	}

	function sanitize($whitelist) {

		// all elements in xml doc
		$allElements = $this->xmlDoc->getElementsByTagName("*");

		// loop through all elements
		$numElements = $allElements->length;
		for($i = 0; $i < $numElements; $i++) {

			$whitelist_attr_arr = array(); //reset array for each new element

			$currentNode = $allElements->item($i);

			// array of allowed attributes in specific element
			if(isset($whitelist[$currentNode->tagName])) $whitelist_attr_arr = $whitelist[$currentNode->tagName];

			// does element exist in whitelist?
		    if(!empty($whitelist_attr_arr)) {
		    		$numAttributes = $currentNode->attributes->length;

		    		for($x = 0; $x < $numAttributes; $x++) {

		    			// get attributes name
		    			$attrName = $currentNode->attributes->item($x)->name;

		    			// check if attribute isn't in whitelist and if not, remove it
		    			if(!in_array($attrName,$whitelist_attr_arr)) {
		    				$currentNode->removeAttribute($attrName);
		    				$numAttributes--;
		    				$x--;
		    			}
		    		}
		    }

		    // else remove element
		    else {
		        $currentNode->parentNode->removeChild($currentNode);
		        $numElements--;
		        $i--;
		    }
		}
	}

	function saveSVG() {
		$this->xmlDoc->formatOutput = true;
		//return($this->xmlDoc->saveXML());
		file_put_contents($this->filename, $this->xmlDoc->saveXML());
	}
}

?>