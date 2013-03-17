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
 * Copyright (C) 2013 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://processwire.com
 *
 * @todo use ImageSizerInterface
 *
 */
class ImageSizer extends Wire {

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
	 * Type of image
	 *
	 */
	protected $imageType; 

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
	 * Directions that cropping may gravitate towards
	 *
	 * Beyond those included below, TRUE represents center and FALSE represents no cropping.
	 *
	 */
	static protected $croppingValues = array(
		'nw' => 'northwest',
		'n'  => 'north',
		'ne' => 'northeast',
		'w'  => 'west',
		'e'  => 'east',
		'sw' => 'southwest',
		's'  => 'south',
		'se' => 'southeast',
		);

	/**
	 * Allow images to be cropped to achieve necessary dimension? If so, what direction?
	 *
	 * Possible values: northwest, north, northeast, west, center, east, southwest, south, southeast 
	 * 	or TRUE to crop to center, or FALSE to disable cropping.
	 * Default is: TRUE
	 *
	 */
	protected $cropping = true;


	/**
	 * Was the given image modified?
	 *
	 */
	protected $modified = false; 

	/**
	 * Supported image types (@teppo)
	 *
	 */
	protected $supportedImageTypes = array(
		'gif' => IMAGETYPE_GIF,
		'jpg' => IMAGETYPE_JPEG,
		'jpeg' => IMAGETYPE_JPEG,
		'png' => IMAGETYPE_PNG,
		);

	/**
	 * Construct the ImageSizer for a single image
	 *
	 */
	public function __construct($filename) {

		$this->filename = $filename; 
		$p = pathinfo($filename); 
		$basename = $p['basename']; 
		$this->extension = strtolower($p['extension']); 

		if(function_exists("exif_imagetype")) {

			$this->imageType = exif_imagetype($filename); 

			if(!in_array($this->imageType, $this->supportedImageTypes)) // @teppo
				throw new WireException("$basename is an unsupported image type"); 	

		} else {
			// if exif_imagetype function is not present, we fallback to extension detection

			if(!isset($this->supportedImageTypes[$this->extension])) 
				throw new WireException("$basename contains an unsupported image extension"); 

			$this->imageType = $this->supportedImageTypes[$this->extension]; 
		}


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
	public function ___resize($targetWidth, $targetHeight = 0) {


		if(!$this->isResizeNecessary($targetWidth, $targetHeight)) return true; 

		$source = $this->filename;
		$dest = str_replace("." . $this->extension, "_tmp." . $this->extension, $source); 
		$image = null;

		switch($this->imageType) { // @teppo
			case IMAGETYPE_GIF: $image = @imagecreatefromgif($source); break;
			case IMAGETYPE_PNG: $image = @imagecreatefrompng($source); break;
			case IMAGETYPE_JPEG: $image = @imagecreatefromjpeg($source); break;
		}

		if(!$image) return false; 

		list($gdWidth, $gdHeight, $targetWidth, $targetHeight) = $this->getResizeDimensions($targetWidth, $targetHeight); 

		$thumb = imagecreatetruecolor($gdWidth, $gdHeight);  

		if($this->imageType == IMAGETYPE_PNG) { 
			// @adamkiss PNG transparency
			imagealphablending($thumb, false); 
			imagesavealpha($thumb, true); 

		} else if($this->imageType == IMAGETYPE_GIF) {
			// @mrx GIF transparency
	        	$transparentIndex = ImageColorTransparent($image);
			$transparentColor = $transparentIndex != -1 ? ImageColorsForIndex($image, $transparentIndex) : 0;
	        	if(!empty($transparentColor)) {
	            		$transparentNew = ImageColorAllocate($thumb, $transparentColor['red'], $transparentColor['green'], $transparentColor['blue']);
	            		$transparentNewIndex = ImageColorTransparent($thumb, $transparentNew);
	            		ImageFill($thumb, 0, 0, $transparentNewIndex);
	        	}

		} else {
			$bgcolor = imagecolorallocate($thumb, 0, 0, 0);  
			imagefilledrectangle($thumb, 0, 0, $gdWidth, $gdHeight, $bgcolor);
			imagealphablending($thumb, true);
		}

		imagecopyresampled($thumb, $image, 0, 0, 0, 0, $gdWidth, $gdHeight, $this->image['width'], $this->image['height']);
		$thumb2 = imagecreatetruecolor($targetWidth, $targetHeight);

		if($this->imageType == IMAGETYPE_PNG) { 
			// @adamkiss PNG transparency
			imagealphablending($thumb2, false); 
			imagesavealpha($thumb2, true); 

		} else if($this->imageType == IMAGETYPE_GIF) {
			// @mrx GIF transparency
			if(!empty($transparentColor)) {
				$transparentNew = ImageColorAllocate($thumb2, $transparentColor['red'], $transparentColor['green'], $transparentColor['blue']);
				$transparentNewIndex = ImageColorTransparent($thumb2, $transparentNew);
				ImageFill($thumb2, 0, 0, $transparentNewIndex);
			}

		} else {
			$bgcolor = imagecolorallocate($thumb2, 0, 0, 0);  
			imagefilledrectangle($thumb2, 0, 0, $targetWidth, $targetHeight, 0);
			imagealphablending($thumb2, true);
		}

		$w1 = ($gdWidth / 2) - ($targetWidth / 2);
		$h1 = ($gdHeight / 2) - ($targetHeight / 2);

		if(is_string($this->cropping)) switch($this->cropping) { 
			// @interrobang crop directions
			case 'nw':
				$w1 = 0;
				$h1 = 0;
				break;
			case 'n':
				$h1 = 0;
				break;
			case 'ne':
				$w1 = $gdWidth - $targetWidth;
				$h1 = 0;
				break;
			case 'w':
				$w1 = 0;
				break;
			case 'e':
				$w1 = $gdWidth - $targetWidth;
				break;
			case 'sw':
				$w1 = 0;
				$h1 = $gdHeight - $targetHeight;
				break;
			case 's':
				$h1 = $gdHeight - $targetHeight;
				break;
			case 'se':
				$w1 = $gdWidth - $targetWidth;
				$h1 = $gdHeight - $targetHeight;
				break;
			default: // center or false, we do nothing

		} else if(is_array($this->cropping)) {
			// @interrobang + @u-nikos
			if(strpos($this->cropping[0], '%') === false) $pointX = (int) $this->cropping[0];
				else $pointX = $gdWidth * ((int) $this->cropping[0] / 100);

			if(strpos($this->cropping[1], '%') === false) $pointY = (int) $this->cropping[1];
				else $pointY = $gdHeight * ((int) $this->cropping[1] / 100);

			if($pointX < $targetWidth / 2) $w1 = 0;
				else if($pointX > ($gdWidth - $targetWidth / 2)) $w1 = $gdWidth - $targetWidth;
				else $w1 = $pointX - $targetWidth / 2;

			if($pointY < $targetHeight / 2) $h1 = 0;
				else if($pointY > ($gdHeight - $targetHeight / 2)) $h1 = $gdHeight - $targetHeight;
				else $h1 = $pointY - $targetHeight / 2;
		}

		imagecopyresampled($thumb2, $thumb, 0, 0, $w1, $h1, $targetWidth, $targetHeight, $targetWidth, $targetHeight);

		// write to file
		$result = false;
		switch($this->imageType) {
			case IMAGETYPE_GIF: 
				$result = imagegif($thumb2, $dest); 
				break;
			case IMAGETYPE_PNG: 
				// convert 1-100 (worst-best) scale to 0-9 (best-worst) scale for PNG 
				$quality = round(abs(($this->quality - 100) / 11.111111)); 
				$result = imagepng($thumb2, $dest, $quality); 
				break;
			case IMAGETYPE_JPEG:
				$result = imagejpeg($thumb2, $dest, $this->quality); 
				break;
		}

		if($result === false) {
			if(is_file($dest)) unlink($dest); 
			return false;
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

		$r = array(	0 => (int) $pWidth, 	
				1 => (int) $pHeight,
				2 => (int) $targetWidth,
				3 => (int) $targetHeight
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
	 * Turn on/off cropping and/or set cropping direction
	 *
	 * @param bool|string|array $cropping Specify one of: northwest, north, northeast, west, center, east, southwest, south, southeast.
	 *	Or a string of: 50%,50% (x and y percentages to crop from)
	 * 	Or an array('50%', '50%')
	 *	Or to disable cropping, specify boolean false. To enable cropping with default (center), you may also specify boolean true.
	 * @return this
	 *
	 */
	public function setCropping($cropping = true) {
		$this->cropping = self::croppingValue($cropping);
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

	/**
	 * Alternative to the above set* functions where you specify all in an array
	 *
	 * @param array $options May contain the following (show with default values):
	 *	'quality' => 90,
	 *	'cropping' => true, 
	 *	'upscaling' => true
	 * @return this
	 *
	 */
	public function setOptions(array $options) {
		foreach($options as $key => $value) {
			switch($key) {
				case 'quality': $this->setQuality($value); break;
				case 'cropping': $this->setCropping($value); break;
				case 'upscaling': $this->setUpscaling($value); break;
			}
		}
		return $this; 
	}

	/**
	 * Return an array of the current options
	 *
	 * @return array
	 *
	 */
	public function getOptions() {
		return array(
			'quality' => $this->quality, 
			'cropping' => $this->cropping, 
			'upscaling' => $this->upscaling
			);
	}

	/**
	 * Given an unknown cropping value, return the validated internal representation of it
	 *
	 * @return string|bool
	 *
	 */
	static public function croppingValue($cropping) {

		if(is_string($cropping)) {
			$cropping = strtolower($cropping); 
			if(strpos($cropping, ',')) {
				$cropping = explode(',', $cropping);
				if(strpos($cropping[0], '%') !== false) $cropping[0] = round(min(100, max(0, $cropping[0]))) . '%';
					else $cropping[0] = (int) $cropping[0];
				if(strpos($cropping[1], '%') !== false) $cropping[1] = round(min(100, max(0, $cropping[1]))) . '%';
					else $cropping[1] = (int) $cropping[1];
			}
		}
		
		if($cropping === true) $cropping = true; // default, crop to center
			else if(!$cropping) $cropping = false;
			else if(is_array($cropping)) $cropping = $cropping; // already took care of it above
			else if(in_array($cropping, self::$croppingValues)) $cropping = array_search($cropping, self::$croppingValues); 
			else if(array_key_exists($cropping, self::$croppingValues)) $cropping = $cropping; 
			else $cropping = true; // unknown value or 'center', default to TRUE/center

		return $cropping; 
	}

	/**
	 * Given an unknown cropping value, return the string representation of it 
	 *
	 * Okay for use in filenames
	 *
	 * @return string
	 *
	 */
	static public function croppingValueStr($cropping) {

		$cropping = self::croppingValue($cropping); 

		// crop name if custom center point is specified
		if(is_array($cropping)) {
			// p = percent, d = pixel dimension
			$cropping = (strpos($cropping[0], '%') !== false ? 'p' : 'd') . ((int) $cropping[0]) . 'x' . ((int) $cropping[1]);
		}

		// if crop is TRUE or FALSE, we don't reflect that in the filename, so make it blank
		if(is_bool($cropping)) $cropping = '';

		return $cropping;
	}

}

