<?php

/**
 * Class CommentStars
 * 
 * Handles rendering of star ratings for comments.
 * Additional code in comments.js and comments.css accompanies this. 
 * 
 * Copyright 2015 by Ryan Cramer for ProcessWire 
 * 
 */

class CommentStars extends WireData {
	
	protected static $defaults = array(
		'numStars' => 5, // max number of stars
		'star' => '★', // this may be overridden with HTML (icons for instance)
		'starOn' => '', // optionally use separate star for ON...
		'starOff' => '', // ...and OFF
		'starOnClass' => 'CommentStarOn', // class assigned to active/on stars
		'wrapClass' => 'CommentStars', // required by JS and CSS
		'wrapClassInput' => 'CommentStarsInput', // required by JS and CSS
	);
	
	/**
	 * Construct comment stars
	 * 
	 * @param int $numStars Number of stars max (default=5)
	 * 
	 */
	public function __construct() {
		foreach(self::$defaults as $key => $value) {
			$this->set($key, $value);
		}
	}

	/**
	 * Change one of the defaults (see $defaults)
	 * 
	 * Example: CommentStars::setDefault('star', '<i class="fa fa-star"></i>'); 
	 * 
	 * @param $key
	 * @param $value
	 * 
	 */
	public static function setDefault($key, $value) {
		self::$defaults[$key] = $value;
	}

	/**
	 * Render stars 
	 * 
	 * @param int|null $stars Number of stars that are in active state
	 * @param bool $allowInput Whether to allow input of stars
	 * @return string
	 * 
	 */
	public function render($stars = 0, $allowInput = false) {
		
		$class = $allowInput ? "$this->wrapClass $this->wrapClassInput" : $this->wrapClass;
		if(!$this->starOn) $this->starOn = $this->star;
		if(!$this->starOff) $this->starOff = $this->star;
		$star = $this->starOff;
		
		if($allowInput) {
			$attr = " data-onclass='$this->starOnClass'";
			if($this->starOn !== $this->starOff) $attr .= " " . 
				"data-on='" . htmlspecialchars($starOn, ENT_QUOTES, 'UTF-8') . "' " . 
				"data-off='" . htmlspecialchars($starOff, ENT_QUOTES, 'UTF-8') . "'";
		} else {
			$attr = '';
		}
		
		$out = "<span class='$class'$attr>";
		
		for($n = 1; $n <= $this->numStars; $n++) {
			if($n <= $stars) {
				$star = $this->starOn;
				$attr = " class='$this->starOnClass'";
			} else {
				$star = $this->starOff;
				$attr = "";
			}
			$out .= "<span$attr data-value='$n'>$star</span>";
		}
		
		$out .= "</span>";
		
		return $out;
	}
}