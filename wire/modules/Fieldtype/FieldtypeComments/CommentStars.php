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
		'starOffClass' => 'CommentStarOff', // class assigned to inactive/off stars
		'starPartialClass' => 'CommentStarPartial',
		'wrapClass' => 'CommentStars', // required by JS and CSS
		'wrapClassInput' => 'CommentStarsInput', // required by JS and CSS
		'countClass' => 'CommentStarsCount', // class used for renderCount() method
		'detailsLabel' => '%g/%d', // i.e. 4.5/5
		'countLabelSingular' => '%s via %d rating', // i.e. 4/5 via 1 rating
		'countLabelPlural' => '%s via %d ratings', // i.e. 4.5/5 via 10 ratings
		'unratedLabel' => 'not yet rated',
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
		$this->set('countLabelSingular', $this->_('%s (%d rating)'));
		$this->set('countLabelPlural', $this->_('%s (%d ratings)'));
		$this->set('unratedLabel', $this->_('not yet rated'));
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
	 * If given a float for $stars, it will render partial stars.
	 * If given an int for $stars, it will only render whole stars.
	 * 
	 * @param int|float|null $stars Number of stars that are in active state
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
				// full star on
				$star = $this->starOn;
				$attr = " class='$this->starOnClass'";
			} else if(is_float($stars) && $n > $stars && $n < $stars + 1) {
				// partial star on
				$star = "<span class='$this->starOffClass'>$this->starOff</span>";
				if(preg_match('/^\d+[^\d]+(\d+)$/', round($stars, 2), $matches)) {
					$star .= "<span class='$this->starOnClass' style='width:$matches[1]%;'>$this->starOn</span>";
				}
				$attr = " class='$this->starPartialClass'";
			} else {
				// star off
				$attr = " class='$this->starOffClass'";
				$star = $this->starOff;
				$attr = "";
			}
			$out .= "<span$attr data-value='$n'>$star</span>";
		}
		
		$out .= "</span>";
		
		return $out;
	}

	/**
	 * Render a count of ratings
	 * 
	 * @param int $count
	 * @param float|int $stars
	 * @return string
	 * 
	 */
	public function renderCount($count, $stars = 0.0) {
		$count = (int) $count;
		if($stars > 0) {
			if(is_int($stars)) {
				$stars = round($stars);
			} else {
				if($stars > $this->numStars) $stars = 5.0;
				$stars = round($stars, 1);
			}
			$details = sprintf($this->detailsLabel, $stars, $this->numStars);
			$out = sprintf($this->_n($this->countLabelSingular, $this->countLabelPlural, $count), $details, $count);
		} else {
			$out = $this->unratedLabel;
		}
		return "<span class='$this->countClass'>$out</span>";
	}
	
}