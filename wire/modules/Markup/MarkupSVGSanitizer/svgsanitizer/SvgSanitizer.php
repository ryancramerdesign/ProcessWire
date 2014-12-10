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
 *  and also changed to save sanitized version back to original file
 */

class SvgSanitizer {

	//private $remoteHref = false;		// Check if hrefs in XML can goto remote locations
	private $xmlDoc;					// PHP XML DOMDocument
	private $filename;

	// defines the whitelist of elements and attributes allowed.
	//public static $whitelist = json_decode(FieldtypeFile::svg_sanitizer_whitelist);
	/*public static $whitelist = array(
		"a"=>array("class", "clip-path", "clip-rule", "fill", "fill-opacity", "fill-rule", "filter", "id", "mask", "opacity", "stroke", "stroke-dasharray", "stroke-dashoffset", "stroke-linecap", "stroke-linejoin", "stroke-miterlimit", "stroke-opacity", "stroke-width", "style", "systemLanguage", "transform", "href", "xlink:href", "xlink:title"),
		"circle"=>array("class", "clip-path", "clip-rule", "cx", "cy", "fill", "fill-opacity", "fill-rule", "filter", "id", "mask", "opacity", "r", "requiredFeatures", "stroke", "stroke-dasharray", "stroke-dashoffset", "stroke-linecap", "stroke-linejoin", "stroke-miterlimit", "stroke-opacity", "stroke-width", "style", "systemLanguage", "transform"),
		"clipPath"=>array("class", "clipPathUnits", "id"),
		"defs"=>array(),
	    "style" =>array("type"),
		"desc"=>array(),
		"ellipse"=>array("class", "clip-path", "clip-rule", "cx", "cy", "fill", "fill-opacity", "fill-rule", "filter", "id", "mask", "opacity", "requiredFeatures", "rx", "ry", "stroke", "stroke-dasharray", "stroke-dashoffset", "stroke-linecap", "stroke-linejoin", "stroke-miterlimit", "stroke-opacity", "stroke-width", "style", "systemLanguage", "transform"),
		"feGaussianBlur"=>array("class", "color-interpolation-filters", "id", "requiredFeatures", "stdDeviation"),
		"filter"=>array("class", "color-interpolation-filters", "filterRes", "filterUnits", "height", "id", "primitiveUnits", "requiredFeatures", "width", "x", "xlink:href", "y"),
		"foreignObject"=>array("class", "font-size", "height", "id", "opacity", "requiredFeatures", "style", "transform", "width", "x", "y"),
		"g"=>array("class", "clip-path", "clip-rule", "id", "display", "fill", "fill-opacity", "fill-rule", "filter", "mask", "opacity", "requiredFeatures", "stroke", "stroke-dasharray", "stroke-dashoffset", "stroke-linecap", "stroke-linejoin", "stroke-miterlimit", "stroke-opacity", "stroke-width", "style", "systemLanguage", "transform", "font-family", "font-size", "font-style", "font-weight", "text-anchor"),
		"image"=>array("class", "clip-path", "clip-rule", "filter", "height", "id", "mask", "opacity", "requiredFeatures", "style", "systemLanguage", "transform", "width", "x", "xlink:href", "xlink:title", "y"),
		"line"=>array("class", "clip-path", "clip-rule", "fill", "fill-opacity", "fill-rule", "filter", "id", "marker-end", "marker-mid", "marker-start", "mask", "opacity", "requiredFeatures", "stroke", "stroke-dasharray", "stroke-dashoffset", "stroke-linecap", "stroke-linejoin", "stroke-miterlimit", "stroke-opacity", "stroke-width", "style", "systemLanguage", "transform", "x1", "x2", "y1", "y2"),
		"linearGradient"=>array("class", "id", "gradientTransform", "gradientUnits", "requiredFeatures", "spreadMethod", "systemLanguage", "x1", "x2", "xlink:href", "y1", "y2"),
		"marker"=>array("id", "class", "markerHeight", "markerUnits", "markerWidth", "orient", "preserveAspectRatio", "refX", "refY", "systemLanguage", "viewBox"),
		"mask"=>array("class", "height", "id", "maskContentUnits", "maskUnits", "width", "x", "y"),
		"metadata"=>array("class", "id"),
		"path"=>array("class", "clip-path", "clip-rule", "d", "fill", "fill-opacity", "fill-rule", "filter", "id", "marker-end", "marker-mid", "marker-start", "mask", "opacity", "requiredFeatures", "stroke", "stroke-dasharray", "stroke-dashoffset", "stroke-linecap", "stroke-linejoin", "stroke-miterlimit", "stroke-opacity", "stroke-width", "style", "systemLanguage", "transform"),
		"pattern"=>array("class", "height", "id", "patternContentUnits", "patternTransform", "patternUnits", "requiredFeatures", "style", "systemLanguage", "viewBox", "width", "x", "xlink:href", "y"),
		"polygon"=>array("class", "clip-path", "clip-rule", "id", "fill", "fill-opacity", "fill-rule", "filter", "id", "class", "marker-end", "marker-mid", "marker-start", "mask", "opacity", "points", "requiredFeatures", "stroke", "stroke-dasharray", "stroke-dashoffset", "stroke-linecap", "stroke-linejoin", "stroke-miterlimit", "stroke-opacity", "stroke-width", "style", "systemLanguage", "transform"),
		"polyline"=>array("class", "clip-path", "clip-rule", "id", "fill", "fill-opacity", "fill-rule", "filter", "marker-end", "marker-mid", "marker-start", "mask", "opacity", "points", "requiredFeatures", "stroke", "stroke-dasharray", "stroke-dashoffset", "stroke-linecap", "stroke-linejoin", "stroke-miterlimit", "stroke-opacity", "stroke-width", "style", "systemLanguage", "transform"),
		"radialGradient"=>array("class", "cx", "cy", "fx", "fy", "gradientTransform", "gradientUnits", "id", "r", "requiredFeatures", "spreadMethod", "systemLanguage", "xlink:href"),
		"rect"=>array("class", "clip-path", "clip-rule", "fill", "fill-opacity", "fill-rule", "filter", "height", "id", "mask", "opacity", "requiredFeatures", "rx", "ry", "stroke", "stroke-dasharray", "stroke-dashoffset", "stroke-linecap", "stroke-linejoin", "stroke-miterlimit", "stroke-opacity", "stroke-width", "style", "systemLanguage", "transform", "width", "x", "y"),
		"stop"=>array("class", "id", "offset", "requiredFeatures", "stop-color", "stop-opacity", "style", "systemLanguage"),
		"svg"=>array("class", "clip-path", "clip-rule", "filter", "id", "height", "mask", "preserveAspectRatio", "requiredFeatures", "style", "systemLanguage", "viewBox", "width", "x", "xmlns", "xmlns:se", "xmlns:xlink", "y"),
		"switch"=>array("class", "id", "requiredFeatures", "systemLanguage"),
		"symbol"=>array("class", "fill", "fill-opacity", "fill-rule", "filter", "font-family", "font-size", "font-style", "font-weight", "id", "opacity", "preserveAspectRatio", "requiredFeatures", "stroke", "stroke-dasharray", "stroke-dashoffset", "stroke-linecap", "stroke-linejoin", "stroke-miterlimit", "stroke-opacity", "stroke-width", "style", "systemLanguage", "transform", "viewBox"),
		"text"=>array("class", "clip-path", "clip-rule", "fill", "fill-opacity", "fill-rule", "filter", "font-family", "font-size", "font-style", "font-weight", "id", "mask", "opacity", "requiredFeatures", "stroke", "stroke-dasharray", "stroke-dashoffset", "stroke-linecap", "stroke-linejoin", "stroke-miterlimit", "stroke-opacity", "stroke-width", "style", "systemLanguage", "text-anchor", "transform", "x", "xml:space", "y"),
		"textPath"=>array("class", "id", "method", "requiredFeatures", "spacing", "startOffset", "style", "systemLanguage", "transform", "xlink:href"),
		"title"=>array(),
		"tspan"=>array("class", "clip-path", "clip-rule", "dx", "dy", "fill", "fill-opacity", "fill-rule", "filter", "font-family", "font-size", "font-style", "font-weight", "id", "mask", "opacity", "requiredFeatures", "rotate", "stroke", "stroke-dasharray", "stroke-dashoffset", "stroke-linecap", "stroke-linejoin", "stroke-miterlimit", "stroke-opacity", "stroke-width", "style", "systemLanguage", "text-anchor", "textLength", "transform", "x", "xml:space", "y"),
		"use"=>array("class", "clip-path", "clip-rule", "fill", "fill-opacity", "fill-rule", "filter", "height", "id", "mask", "stroke", "stroke-dasharray", "stroke-dashoffset", "stroke-linecap", "stroke-linejoin", "stroke-miterlimit", "stroke-opacity", "stroke-width", "style", "transform", "width", "x", "xlink:href", "y"),
	);*/

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