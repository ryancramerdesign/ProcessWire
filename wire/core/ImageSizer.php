<?php

/**
 * ProcessWire ImageSizer
 *
 * ImageSizer handles resizing of a single JPG, GIF, or PNG image using GD2.
 *
 * ImageSizer class includes ideas adapted from comments found at PHP.net 
 * in the GD functions documentation.
 *
 * Code for IPTC, auto rotation and sharpening by Horst Nogajski.
 * http://nogajski.de/
 *
 * Other user contributions as noted. 
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2013 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://processwire.com
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
	protected $imageType = null; 

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
	 * enable auto_rotation according to EXIF-Orientation-Flag
	 *
	 */
	protected $autoRotation = true;

	/**
	 * default sharpening mode: [ none | soft | medium | strong ]
	 *
	 */
	protected $sharpening = 'soft';


	/**
	 * Options allowed for sharpening
	 *
	 */
	protected $sharpeningValues = array(
		0 => 'none', // none
		1 => 'soft',
		2 => 'medium',
		3 => 'strong'
		);

	/**
	 * List of valid option Names from config.php (@horst)
	 *
	 */
	protected $optionNames = array(
		'autoRotation',
		'upscaling',
		'cropping',
		'quality',
		'sharpening'
		);

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
	 * Result of iptcparse(), if available
	 *
	 */
	protected $iptcRaw = null;


	/**
	 * List of valid IPTC tags (@horst)
	 *
	 */
	protected $validIptcTags = array('005','007','010','012','015','020','022','025','030','035','037','038','040','045','047','050','055','060','062','063','065','070','075','080','085','090','092','095','100','101','103','105','110','115','116','118','120','121','122','130','131','135','150','199','209','210','211','212','213','214','215','216','217');


	/**
	 * Construct the ImageSizer for a single image
	 *
	 */
	public function __construct($filename, $options = array()) {

		// filling all options with global custom values from config.php
		$options = array_merge($this->wire('config')->imageSizerOptions, $options); 
		$this->setOptions($options);

		if(!$this->loadImageInfo($filename)) {
			throw new WireException(basename($filename) . " is not a recogized image"); 
		}
	}

	/**
	 * Load the image information (width/height) using PHP's getimagesize function 
	 *
	 */
	protected function loadImageInfo($filename) {

		$this->filename = $filename; 
		$pathinfo = pathinfo($filename); 
		$this->extension = strtolower($pathinfo['extension']); 

		$additionalInfo = array();
		$info = @getimagesize($this->filename, $additionalInfo);
		if($info === false) return false;

		if(function_exists("exif_imagetype")) {
			$this->imageType = exif_imagetype($filename); 

		} else if(isset($info[2])) {
			// imagetype (IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG)
			$this->imageType = $info[2];

		} else if(isset($this->supportedImageTypes[$this->extension])) {
			$this->imageType = $this->supportedImageTypes[$this->extension]; 
		}

		if(!in_array($this->imageType, $this->supportedImageTypes)) return false; 

		// width, height
		$this->setImageInfo($info[0], $info[1]);

		// read metadata if present and if its the first call of the method
		if(isset($additionalInfo['APP13']) && empty($this->iptcRaw)) {
			$iptc = iptcparse($additionalInfo["APP13"]);
			if(is_array($iptc)) $this->iptcRaw = $iptc;
		}

		return true; 
	}

	/**
	 * Resize the image proportionally to the given width/height
	 *
	 * Note: Some code used in this method is adapted from code found in comments at php.net for the GD functions
	 *
	 * @param int $targetWidth Target width in pixels, or 0 for proportional to height
	 * @param int $targetHeight Target height in pixels, or 0 for proportional to width. Optional-if not specified, 0 is assumed.
	 * @return bool True if the resize was successful
 	 *
	 * @todo this method has become too long and needs to be split into smaller dedicated parts
	 *
	 */
	public function ___resize($targetWidth, $targetHeight = 0) {

		$orientations = null; // @horst
		$needRotation = $this->autoRotation !== true ? false : ($this->checkOrientation($orientations) && (!empty($orientations[0]) || !empty($orientations[1])) ? true : false);

		$source = $this->filename;
		$dest = str_replace("." . $this->extension, "_tmp." . $this->extension, $source);
		$image = null;

		switch($this->imageType) { // @teppo
			case IMAGETYPE_GIF: $image = @imagecreatefromgif($source); break;
			case IMAGETYPE_PNG: $image = @imagecreatefrompng($source); break;
			case IMAGETYPE_JPEG: $image = @imagecreatefromjpeg($source); break;
		}

		if(!$image) return false;

		if($needRotation) { // @horst (Rotation is executed first because it can change width and height dimensions!)
			$image = $this->imRotate($image, $orientations[0]);
			if($orientations[0] == 90 || $orientations[0] == 270) {
				// we have to swap width & height now!
				$tmp = array($targetWidth, $targetHeight);
				$targetWidth = $tmp[1];
				$targetHeight = $tmp[0];
				$tmp = array($this->getWidth(), $this->getHeight());
				$this->setImageInfo($tmp[1], $tmp[0]);
			}
			if($orientations[1] > 0) {
				$image = $this->imFlip($image, ($orientations[1] == 2 ? true : false));
			}
		}

		// if we need to _scale_ an image we can get better results with first linearize the gamma curve
		// and convert back later, - after manipulations are finished -
		// but we cannot use it together with (transparent) PNG
		$linearizedGamma = false;

		// get all dimensions at first, before any image operation
		list($gdWidth, $gdHeight, $targetWidth, $targetHeight) = $this->getResizeDimensions($targetWidth, $targetHeight);
		$w1 = ($gdWidth / 2) - ($targetWidth / 2);
		$h1 = ($gdHeight / 2) - ($targetHeight / 2);
		$this->getCropDimensions($w1, $h1, $gdWidth, $targetWidth, $gdHeight, $targetHeight);

		// now lets check what operations are necessary:
		if($gdWidth==$targetWidth && $gdWidth==$this->image['width'] && $gdHeight==$this->image['height'] && $gdHeight==$targetHeight) {

			// this is the case if the original size is requested or a greater size but upscaling is set to false.
			// we only need one operation and without resampling, just copy!
			$thumb = imagecreatetruecolor($gdWidth, $gdHeight);
			$this->prepareImageLayer($thumb, $image);
			imagecopy($thumb, $image, 0, 0, 0, 0, $targetWidth, $targetHeight);
			$this->sharpening = 'none';  // no need for sharpening because we use original copy without scaling
			
			
// @Ryan: here we don't need to create a copy but only return true. - right?
			

		}
		elseif($gdWidth==$targetWidth && $gdHeight==$targetHeight) {

			// this is the case if we scale up or down without cropping
			// we need one operation with resampling.
			if($this->imageType != IMAGETYPE_PNG) {
				imagegammacorrect($image, 2.0, 1.0);  // linearize gamma to 1.0
				$linearizedGamma = true;
			}
			$thumb = imagecreatetruecolor($gdWidth, $gdHeight);
			$this->prepareImageLayer($thumb, $image);
			imagecopyresampled($thumb, $image, 0, 0, 0, 0, $gdWidth, $gdHeight, $this->image['width'], $this->image['height']);

		}
		else {

			// we use two steps if we scale up or down and also need to crop!
			if($this->imageType != IMAGETYPE_PNG) {
				imagegammacorrect($image, 2.0, 1.0);  // linearize gamma to 1.0
				$linearizedGamma = true;
			}
			$thumb2 = imagecreatetruecolor($gdWidth, $gdHeight);
			$this->prepareImageLayer($thumb2, $image);
			imagecopyresampled($thumb2, $image, 0, 0, 0, 0, $gdWidth, $gdHeight, $this->image['width'], $this->image['height']);

			$thumb = imagecreatetruecolor($targetWidth, $targetHeight);
			$this->prepareImageLayer($thumb, $image);
			imagecopyresampled($thumb, $thumb2, 0, 0, $w1, $h1, $targetWidth, $targetHeight, $targetWidth, $targetHeight);
// @Ryan: I think here we can use imagecopy without resampling here, but haven't tested enough now. Will do so.
			imagedestroy($thumb2);

		}

		if($this->sharpening && $this->sharpening != 'none') $thumb = $this->imSharpen($thumb, $this->sharpening); // @horst


		// write to file
		$result = false;
		switch($this->imageType) {
			case IMAGETYPE_GIF:
				if($linearizedGamma) {
					imagegammacorrect($thumb, 1.0, 2.0);  // correct gamma from linearized 1.0 back to 2.0
				}
				$result = imagegif($thumb, $dest);
				break;
			case IMAGETYPE_PNG:
				// convert 1-100 (worst-best) scale to 0-9 (best-worst) scale for PNG
				$quality = round(abs(($this->quality - 100) / 11.111111));
				$result = imagepng($thumb, $dest, $quality);
				break;
			case IMAGETYPE_JPEG:
				if($linearizedGamma) {
					imagegammacorrect($thumb, 1.0, 2.0);  // correct gamma from linearized 1.0 back to 2.0
				}
				$result = imagejpeg($thumb, $dest, $this->quality);
				break;
		}

		@imagedestroy($image);
		@imagedestroy($thumb);

		if($result === false) {
			if(is_file($dest)) @unlink($dest);
			return false;
		}

		unlink($source);
		rename($dest, $source);

		$this->writeIPTC();  // @horst: if we've retrieved IPTC-Metadata from sourcefile, we write it back now

		$this->loadImageInfo($this->filename);
		$this->modified = true;

		return true;
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
	 * Return the image width
	 * 
	 * @return int
	 *
	 */
	public function getWidth() { return $this->image['width']; }

	/**
	 * Return the image height
	 * 
	 * @return int
	 *
	 */
	public function getHeight() { return $this->image['height']; }

	/**
	 * Return true if it's necessary to perform a resize with the given width/height, or false if not.
	 * 
	 * @param int $targetWidth
	 * @param int $targetHeight
	 * @return bool
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
	 * @param int $targetWidth
	 * @param int $targetHeight
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
	 * Was the image modified?
	 * 
	 * @return bool
	 *	
	 */
	public function isModified() {
		return $this->modified; 
	}

	/**
	 * Given an unknown cropping value, return the validated internal representation of it
	 *
	 * @param string|bool|array $cropping
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
	 * @param string|bool|array $cropping
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

	/**
	 * Turn on/off cropping and/or set cropping direction
	 *
	 * @param bool|string|array $cropping Specify one of: northwest, north, northeast, west, center, east, southwest, south, southeast.
	 *	Or a string of: 50%,50% (x and y percentages to crop from)
	 * 	Or an array('50%', '50%')
	 *	Or to disable cropping, specify boolean false. To enable cropping with default (center), you may also specify boolean true.
	 * @return $this
	 *
	 */
	public function setCropping($cropping = true) {
		$this->cropping = self::croppingValue($cropping);
		return $this;
	}

	/**
 	 * Set the image quality 1-100, where 100 is highest quality
	 *
	 * @param int $n
	 * @return $this
	 *
	 */
	public function setQuality($n) {
		$n = (int) $n; 	
		if($n < 1) $n = 1; 
		if($n > 100) $n = 100;
		$this->quality = (int) $n; 
		return $this;
	}

	/**
	 * Set sharpening value: blank (for none), soft, medium, or strong
	 * 
	 * @param mixed $value
	 * @return $this
	 * @throws WireException
	 *
	 */
	public function setSharpening($value) {

		/*
		if(is_array($value) && count($value) === 3) {
			// Horst: what is this for? 
			$ret = $value;
		*/

		if(is_string($value) && in_array(strtolower($value), $this->sharpeningValues)) {
			$ret = strtolower($value);

		} else if(is_int($value) && isset($this->sharpeningValues[$value])) {
			$ret = $this->sharpeningValues[$value]; 

		} else if(is_bool($value)) {
			$ret = $value ? "medium" : "none";
			
		} else {
			throw new WireException("Unknown value for sharpening"); 
		}

		$this->sharpening = $ret; 

		return $this; 
	}

	/**
	 * Turn on/off auto rotation
	 * 
	 * @param bool value Whether to auto-rotate or not (default = true)
	 * @return $this
	 *
	 */
	public function setAutoRotation($value = true) {
		$this->autoRotation = $this->getBooleanValue($value); 
		return $this; 
	}

	/**
	 * Turn on/off upscaling
	 * 
	 * @param bool $value Whether to upscale or not (default = true)
	 * @return $this
	 *
	 */
	public function setUpscaling($value = true) {
		$this->upscaling = $this->getBooleanValue($value); 
		return $this; 
	}


	/**
	 * Alternative to the above set* functions where you specify all in an array
	 *
	 * @param array $options May contain the following (show with default values):
	 *	'quality' => 90,
	 *	'cropping' => true, 
	 *	'upscaling' => true,
	 *	'autoRotation' => true, 
	 * 	'sharpening' => 'soft' (none|soft|medium|string)
	 * @return $this
	 *
	 */
	public function setOptions(array $options) {
		
		foreach($options as $key => $value) {
			switch($key) {

				case 'autoRotation': $this->setAutoRotation($value); break;
				case 'upscaling': $this->setUpscaling($value); break;
				case 'sharpening': $this->setSharpening($value); break;
				case 'quality': $this->setQuality($value); break;
				case 'cropping': $this->setCropping($value); break;
				
				default: // unknown option
			}
		}
		return $this; 
	}

	/**
	 * Given a value, convert it to a boolean. 
	 *
	 * Value can be string representations like: 0, 1 off, on, yes, no, y, n, false, true.
	 *
	 * @param bool|int|string $value
	 * @return bool
	 *
	 */
	protected function getBooleanValue($value) {
		if(in_array(strtolower($value), array('0', 'off', 'false', 'no', 'n', 'none'))) return false; 
		return ((int) $value) > 0;
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
			'upscaling' => $this->upscaling,
			'autoRotation' => $this->autoRotation,
			'sharpening' => $this->sharpening
			);
	}

	public function __get($key) {
		$keys = array(
			'filename',
			'extension',
			'imageType',
			'image',
			'modified',
			'supportedImageTypes', 
			);
		if(in_array($key, $keys)) return $this->$key; 
		if(in_array($key, $this->optionNames)) return $this->$key; 
		return null;
	}

	/**
	 * Return the filename
	 *
	 * @return string
	 *
	 */
	public function getFilename() {
		return $this->filename; 
	}

	/**
	 * Return the file extension
	 *
	 * @return string
	 *
	 */
	public function getExtension() {
		return $this->extension; 
	}

	/**
	 * Return the image type constant
	 *
	 * @return string
	 *
	 */
	public function getImageType() {
		return $this->imageType; 
	}

	/**
	 * Prepare IPTC data (@horst)
	 *
	 * @return string $iptcNew
	 *
	 */
	protected function iptcPrepareData() {
		$iptcNew = '';
		foreach(array_keys($this->iptcRaw) as $s) {
			$tag = substr($s,2);
			if(substr($s,0,1)=='2' && in_array($tag, $this->validIptcTags) && is_array($this->iptcRaw[$s])) {
				foreach($this->iptcRaw[$s] as $row) {
					$iptcNew .= $this->iptcMakeTag(2, $tag, $row);
				}
			}
		}
		return $iptcNew;
	}

	/**
	 * Make IPTC tag (@horst)
	 *
	 * @param string $rec
	 * @param string $dat
	 * @param string $val
	 * @return string
	 *
	 */
	protected function iptcMakeTag($rec, $dat, $val) {
		$len = strlen($val);
		if($len < 0x8000) {
			return  @chr(0x1c) . @chr($rec) . @chr($dat) .
				chr($len >> 8) .
				chr($len & 0xff) .
				$val;
		} else {
			return  chr(0x1c) . chr($rec) . chr($dat) .
				chr(0x80) . chr(0x04) .
				chr(($len >> 24) & 0xff) .
				chr(($len >> 16) & 0xff) .
				chr(($len >> 8 ) & 0xff) .
				chr(($len ) & 0xff) .
				$val;
		}
	}

	/**
	 * Rotate image (@horst)
	 * 
	 * @param resource $im
	 * @param int $degree
	 * @return resource 
	 *
	 */
	protected function imRotate($im, $degree) {
		$degree = (is_float($degree) || is_int($degree)) && $degree > -361 && $degree < 361 ? $degree : false;
		if($degree === false) return $im; // TODO 5 -c errorhandling: Throw WireExeption
		if(in_array($degree, array(-360, 0, 360))) return $im;
		return @imagerotate($im, $degree, imagecolorallocate($im,0,0,0));
	}

	/**
	 * Flip image (@horst)
	 * 
	 * @param resource $im
	 * @param bool $vertical (default = false)
	 * @return resource
	 *
	 */
	protected function imFlip($im, $vertical = false) {
		$sx  = imagesx($im);
		$sy  = imagesy($im);
		$im2 = @imagecreatetruecolor($sx, $sy);
		if($vertical === true) {
			@imagecopyresampled($im2, $im, 0, 0, 0, ($sy-1), $sx, $sy, $sx, 0-$sy);
		} else {
			@imagecopyresampled($im2, $im, 0, 0, ($sx-1), 0, $sx, $sy, 0-$sx, $sy);
		}
		return $im2;
	}

	/**
	 * Sharpen image (@horst)
	 *
	 * @param resource $im
	 * @param string $mode May be: none | soft | medium | strong
	 * @return resource
	 *
	 */
	protected function imSharpen($im, $mode) {

		switch($mode) {

			case 'none':
				return $im;

			case 'medium':
				$sharpenMatrix = array(
					array( -1, -1, -1 ),
					array( -1, 16, -1 ),
					array( -1, -1, -1 )
					);
				break;

			case 'strong':
				$sharpenMatrix = array(
					array( -1.2, -1, -1.2 ),
					array( -1,   12, -1   ),
					array( -1.2, -1, -1.2 )
					);
				break;

			case 'soft':

			default:
				$sharpenMatrix = array(
					array( -1, -1, -1 ),
					array( -1, 20, -1 ),
					array( -1, -1, -1 )
					);
				break;
		}
		// calculate the sharpen divisor
		$divisor = array_sum(array_map('array_sum', $sharpenMatrix));
		$offset = 0;
		if(!imageconvolution($im, $sharpenMatrix, $divisor, $offset)) return false; // TODO 5 -c errorhandling: Throw WireExeption
		return $im;
	}

	/**
	 * Check orientation (@horst)
	 *
	 * @param array
	 * @return bool
	 *
	 */
	protected function checkOrientation(&$correctionArray) {
		// first value is rotation-degree and second value is flip-mode: 0=NONE | 1=HORIZONTAL | 2=VERTICAL
		$corrections = array(
			'1' => array(  0, 0),
			'2' => array(  0, 1),
			'3' => array(180, 0),
			'4' => array(  0, 2),
			'5' => array(270, 1),
			'6' => array(270, 0),
			'7' => array( 90, 1),
			'8' => array( 90, 0)
			);
		if(!function_exists('exif_read_data')) return false;
		$exif = @exif_read_data($this->filename, 'IFD0');
		if(!is_array($exif) || !isset($exif['Orientation']) || !in_array(strval($exif['Orientation']), array_keys($corrections))) return false;
		$correctionArray = $corrections[strval($exif['Orientation'])];
		return true;
	}



// >>> outsourced parts from ___resize method starts here


	/**
	 * prepares a new created GD image resource according to the IMAGETYPE
	 *
	 * Intended for use by the resize() method
	 *
	 * @param GD-resource $im, destination resource needs to be prepared
	 * @param GD-resource $image, with GIF we need to read from source resource
	 * @return void
	 *
	 */
	protected function prepareImageLayer(&$im, &$image) {

		if($this->imageType == IMAGETYPE_PNG) {
			// @adamkiss PNG transparency
			imagealphablending($im, false);
			imagesavealpha($im, true);

		} else if($this->imageType == IMAGETYPE_GIF) {
			// @mrx GIF transparency
			$transparentIndex = imagecolortransparent($image);
			$transparentColor = $transparentIndex != -1 ? imagecolorsforindex($image, $transparentIndex) : 0;
			if(!empty($transparentColor)) {
				$transparentNew = imagecolorallocate($im, $transparentColor['red'], $transparentColor['green'], $transparentColor['blue']);
				$transparentNewIndex = imagecolortransparent($im, $transparentNew);
				imagefill($im, 0, 0, $transparentNewIndex);
			}

		} else {
			$bgcolor = imagecolorallocate($im, 0, 0, 0);
			imagefilledrectangle($im, 0, 0, imagesx($im), imagesy($im), $bgcolor);
			imagealphablending($im, false);
		}

	}


	/**
	 * if the source image is of type JPEG and we have successful read IPTC-Metadata from it,
	 * we write this data into the destination image
	 *
	 * Intended for use by the resize() method
	 *
	 * @return void
	 *
	 */
	protected function writeIPTC() {

		// @horst: if we've retrieved IPTC-Metadata from sourcefile, we write it back now
		if($this->iptcRaw) {
			$content = iptcembed($this->iptcPrepareData(), $this->filename);
			if($content !== false) {
				$dest = preg_replace('/\.' . $this->extension . '$/', '_tmp.' . $this->extension, $this->filename);
				if(strlen($content) == @file_put_contents($dest, $content, LOCK_EX)) {
					// on success we replace the file
					unlink($this->filename);
					rename($dest, $this->filename);
				} else {
					// it was created a temp diskfile but not with all data in it
					if(file_exists($dest)) @unlink($dest);
				}
			}
		}
	}


	/**
	 * check if cropping is needed, if yes, populate x- and y-position to params $w1 and $h1
	 *
	 * Intended for use by the resize() method
	 *
	 * @param int $w1 - byReference
	 * @param int $h1 - byReference
	 * @param int $gdWidth
	 * @param int $targetWidth
	 * @param int $gdHeight
	 * @param int $targetHeight
	 * @return void
	 *
	 */
	protected function getCropDimensions(&$w1, &$h1, $gdWidth, $targetWidth, $gdHeight, $targetHeight) {

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

	}


// <<< outsourced parts from ___resize method ENDS here



}

