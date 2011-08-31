<?php

/**
 * ProcessWire ImageSizer
 *
 * ImageSizer handles resizing of a single JPG, GIF, or PNG image using GD2.
 *
 * ImageSizer class includes ideas adapted from comments found at PHP.net 
 * in the GD functions documentation.
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */
class ImageSizer {

 	/**
	 * Filename to be resized 
	 *
	 */
	protected $filename;

	/**
	 * Extension of filename
	 *
	 */
	protected $extension; 

	/**
	 * Image quality setting, 1..100
	 *
	 */
	protected $quality = 90;

	/**
	 * Information about the image (width/height)
	 *
	 */
	protected $image = array(
		'width' => 0,
		'height' => 0
	);

	/**
	 * Allow images to be upscaled / enlarged?
	 *
	 */
	protected $upscaling = true;

	/**
	 * Allow images to be cropped to achieve necessary dimension?
	 *
	 */
	protected $cropping = true;

	/**
	 * Was the given image modified?
	 *
	 */
	protected $modified = false; 

	/**
	 * File extensions that are supported for resizing
	 *
	 */
	protected $supportedExtensions = array(
		'gif', 
		'jpg', 
		'jpeg', 
		'png',
		); 

	/**
	 * Construct the ImageSizer for a single image
	 *
	 */
	public function __construct($filename) {

		$this->filename = $filename; 
		$p = pathinfo($filename); 
		$this->extension = strtolower($p['extension']); 
		$basename = $p['basename']; 

		if(!in_array($this->extension, $this->supportedExtensions)) 
			throw new WireException("$basename is an unsupported image type"); 	

		if(!$this->loadImageInfo()) 
			throw new WireException("$basename is not a recogized image"); 

	}

	/**
	 * Save the width and height of the image
	 *
	 */
	protected function setImageInfo($width, $height) {
		$this->image['width'] = $width;
		$this->image['height'] = $height; 
	}

	/**
	 * Load the image information (width/height) using PHP's getimagesize function 
	 *
	 */
	protected function loadImageInfo() {
		if(($info = @getimagesize($this->filename)) === false) return false; 
		$this->setImageInfo($info[0], $info[1]); 
		return true; 
	}

	/**
	 * Resize the image proportionally to the given width/height
	 *
	 * Note: Some code used in this method is adapted from code found in comments at php.net for the GD functions
	 *
	 * @param int $width
	 * @param int $height
	 * @return bool True if the resize was successful
	 *
	 */
	public function resize($targetWidth, $targetHeight = 0) {


		if(!$this->isResizeNecessary($targetWidth, $targetHeight)) return true; 

		$source = $this->filename;
		$dest = str_replace("." . $this->extension, "_tmp." . $this->extension, $source); 

		switch($this->extension) {
			case 'gif': $image = @imagecreatefromgif($source); break;
			case 'png': $image = @imagecreatefrompng($source); break;
			case 'jpeg':
			case 'jpg': $image = @imagecreatefromjpeg($source); break;
		}

		if(!$image) return false; 

		list($gdWidth, $gdHeight, $targetWidth, $targetHeight) = $this->getResizeDimensions($targetWidth, $targetHeight); 

		$thumb = imagecreatetruecolor($gdWidth, $gdHeight);  

		if($this->extension == 'png') { // Adam's PNG transparency fix
			imagealphablending($thumb, false); 
			imagesavealpha($thumb, true); 
		} else {
			$bgcolor = imagecolorallocate($thumb, 0, 0, 0);  
			imagefilledrectangle($thumb, 0, 0, $gdWidth, $gdHeight, $bgcolor);
			imagealphablending($thumb, true);
		}

		imagecopyresampled($thumb, $image, 0, 0, 0, 0, $gdWidth, $gdHeight, $this->image['width'], $this->image['height']);
		$thumb2 = imagecreatetruecolor($targetWidth, $targetHeight);

		if($this->extension == 'png') { 
			imagealphablending($thumb2, false); 
			imagesavealpha($thumb2, true); 
		} else {
			$bgcolor = imagecolorallocate($thumb2, 0, 0, 0);  
			imagefilledrectangle($thumb2, 0, 0, $targetWidth, $targetHeight, 0);
			imagealphablending($thumb2, true);
		}

		$w1 = ($gdWidth / 2) - ($targetWidth / 2);
		$h1 = ($gdHeight / 2) - ($targetHeight / 2);

		imagecopyresampled($thumb2, $thumb, 0, 0, $w1, $h1, $targetWidth, $targetHeight, $targetWidth, $targetHeight);

		// write to file
		switch($this->extension) {
			case 'gif': 
				imagegif($thumb2, $dest); 
				break;
			case 'png': 
				// convert 1-100 (worst-best) scale to 0-9 (best-worst) scale for PNG 
				$quality = round(abs(($this->quality - 100) / 11.111111)); 
				imagepng($thumb2, $dest, $quality); 
				break;
			case 'jpeg':
			case 'jpg': 
				imagejpeg($thumb2, $dest, $this->quality); 
				break;
		}

		unlink($source); 
		rename($dest, $source); 

		$this->loadImageInfo(); 
		$this->modified = true; 
		
		return true;
	}

	/**
	 * Return the image width
	 *
	 */
	public function getWidth() { return $this->image['width']; }

	/**
	 * Return the image height
	 *
	 */
	public function getHeight() { return $this->image['height']; }

	/**
	 * Return true if it's necessary to perform a resize with the given width/height, or false if not.
	 *
	 */
	protected function isResizeNecessary($targetWidth, $targetHeight) {

		$img =& $this->image; 
		$resize = true; 

		if(	(!$targetWidth || $img['width'] == $targetWidth) && 
			(!$targetHeight || $img['height'] == $targetHeight)) {
			
			$resize = false;

		} else if(!$this->upscaling && ($targetHeight >= $img['height'] && $targetWidth >= $img['width'])) {

			$resize = false; 
		}

		return $resize; 
	}

	/**
	 * Given a target height, return the proportional width for this image
	 *
	 */
	protected function getProportionalWidth($targetHeight) {
		$img =& $this->image;
		return ($targetHeight / $img['height']) * $img['width'];
	}

	/**
	 * Given a target width, return the proportional height for this image
	 *
	 */
	protected function getProportionalHeight($targetWidth) {
		$img =& $this->image;
		return ($targetWidth / $img['width']) * $img['height'];
	}

	/**
	 * Get an array of the 4 dimensions necessary to perform the resize
	 * 
	 * Note: Some code used in this method is adapted from code found in comments at php.net for the GD functions
	 *
	 * Intended for use by the resize() method
	 *
	 * @return array
	 *
	 */
	protected function getResizeDimensions($targetWidth, $targetHeight) {

		$pWidth = $targetWidth;
		$pHeight = $targetHeight;

		$img =& $this->image; 

		if(!$targetHeight) $targetHeight = floor(($targetWidth / $img['width']) * $img['height']); 
		if(!$targetWidth) $targetWidth = floor(($targetHeight / $img['height']) * $img['width']); 

		$originalTargetWidth = $targetWidth;
		$originalTargetHeight = $targetHeight; 

		if($img['width'] < $img['height']) {
			$pHeight = $this->getProportionalHeight($targetWidth); 
		} else {
			$pWidth = $this->getProportionalWidth($targetHeight); 
		}

		if($pWidth < $targetWidth) { 
			// if the proportional width is smaller than specified target width 
			$pWidth = $targetWidth;
			$pHeight = $this->getProportionalHeight($targetWidth);
		}

		if($pHeight < $targetHeight) { 
			// if the proportional height is smaller than specified target height 
			$pHeight = $targetHeight;
			$pWidth = $this->getProportionalWidth($targetHeight); 
		}

		if(!$this->upscaling) {
			// we are going to shoot for something smaller than the target

			while($pWidth > $img['width'] || $pHeight > $img['height']) {
				// favor the smallest dimension
				if($pWidth > $img['width']) {
					$pWidth = $img['width']; 
					$pHeight = $this->getProportionalHeight($pWidth); 
				}

				if($pHeight > $img['height']) {
					$pHeight = $img['height']; 
					$pWidth = $this->getProportionalWidth($pHeight); 
				}

				if($targetWidth > $pWidth) $targetWidth = $pWidth;
				if($targetHeight > $pHeight) $targetHeight = $pHeight; 

				if(!$this->cropping) {
					$targetWidth = $pWidth;	
					$targetHeight = $pHeight; 
				}
			}
		}

		if(!$this->cropping) {
			// we will make the image smaller so that none of it gets cropped
			// this means we'll be adjusting either the targetWidth or targetHeight 
			// till we have a suitable dimension 

			if($pHeight > $originalTargetHeight) {
				$pHeight = $originalTargetHeight;	
				$pWidth = $this->getProportionalWidth($pHeight); 
				$targetWidth = $pWidth;
				$targetHeight = $pHeight;
			}
			if($pWidth > $originalTargetWidth) {
				$pWidth = $originalTargetWidth;
				$pHeight = $this->getProportionalHeight($pWidth); 
				$targetWidth = $pWidth;
				$targetHeight = $pHeight;
			}
		}

		$r = array(	0 => $pWidth, 	
				1 => $pHeight,
				2 => $targetWidth,
				3 => $targetHeight
				); 

		return $r;

	}

	/**
	 * Turn on/off upscaling
	 *
	 */
	public function setUpscaling($upscaling = true) {
		$this->upscaling = $upscaling; 
		return $this;
	}

	/**
	 * Turn on/off cropping
	 *
	 */
	public function setCropping($cropping = true) {
		$this->cropping = $cropping; 
		return $this;
	}

	/**
	 * Was the image modified?
	 *	
	 */
	public function isModified() {
		return $this->modified; 
	}

	/**
 	 * Set the image quality 1-100, where 100 is highest quality
	 *
	 * @param int $n
	 * @return this
	 *
	 */
	public function setQuality($n) {
		$this->quality = (int) $n; 
		return $this;
	}

}

