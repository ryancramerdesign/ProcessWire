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
 * Copyright (C) 2015 by Ryan Cramer 
 * This file licensed under Mozilla Public License v2.0 http://mozilla.org/MPL/2.0/
 * 
 * https://processwire.com
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
	 * This can be populated on a per image basis. It provides cropping first and then resizing, the opposite of the default behave
	 *
	 * It needs an array with 4 params: x y w h for the cropping rectangle
	 *
	 * Default is: null
	 *
	 */
	protected $cropExtra = null;

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
	 * Degrees to rotate: -270, -180, -90, 90, 180, 270
	 * 
	 * @var int
	 * 
	 */
	protected $rotate = 0;

	/**
	 * Flip image: Specify 'v' for vertical or 'h' for horizontal
	 * 
	 * @var string
	 * 
	 */
	protected $flip = '';

	/**
	 * default gamma correction: 0.5 - 4.0 | -1 to disable gammacorrection, default = 2.0
	 * 
	 * can be overridden by setting it to $config->imageSizerOptions['defaultGamma']
	 * or passing it along with image options array
	 * 
	 */
	protected $defaultGamma = 2.0;

	/**
	 * Factor to use when determining if enough memory available for resize. 
	 *
	 */
	protected $memoryCheckFactor = 2.2; 

	/**
	 * Other options for 3rd party use
	 *
	 */
	protected $options = array();

	/**
	 * Options allowed for sharpening
	 *
	 */
	static protected $sharpeningValues = array(
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
		'sharpening',
		'defaultGamma',
		'scale', 
		'rotate',
		'flip', 
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
	 * Indicates how much an image should be sharpened
	 * 
	 */
	protected $usmValue = 100; 

	/**
	 * Result of iptcparse(), if available
	 *
	 */
	protected $iptcRaw = null;

	/**
	 * List of valid IPTC tags (@horst)
	 *
	 */
	protected $validIptcTags = array(
		'005','007','010','012','015','020','022','025','030','035','037','038','040','045','047','050','055','060',
		'062','063','065','070','075','080','085','090','092','095','100','101','103','105','110','115','116','118',
		'120','121','122','130','131','135','150','199','209','210','211','212','213','214','215','216','217');

	/**
	 * Information about the image from getimagesize (width, height, imagetype, channels, etc.)
	 * 
	 */
	protected $info = null;

	/**
	 * HiDPI scale value (2.0 = hidpi, 1.0 = normal)
	 * 
	 * @var float
	 * 
	 */
	protected $scale = 1.0;

	/**
	 * Construct the ImageSizer for a single image
	 *
	 */
	public function __construct($filename, $options = array()) {
	
		// ensures the resize doesn't timeout the request (with at least 30 seconds)
		$this->setTimeLimit(); 

		// set the use of UnSharpMask as default, can be overwritten per pageimage options
		// or per $config->imageSizerOptions in site/config.php
		$this->options['useUSM'] = true;

		// filling all options with global custom values from config.php
		$options = array_merge($this->wire('config')->imageSizerOptions, $options); 
		$this->setOptions($options);
		$this->loadImageInfo($filename, true);
	}

	/**
	 * Load the image information (width/height) using PHP's getimagesize function 
	 * 
	 */
	protected function loadImageInfo($filename, $reloadAll = false) {

		$this->filename = $filename;
		$this->extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

		$additionalInfo = array();
	
		$this->info = @getimagesize($this->filename, $additionalInfo);
		if($this->info === false) throw new WireException(basename($filename) . " - not a recognized image");
		$this->info['channels'] = isset($this->info['channels']) && $this->info['channels'] > 0 && $this->info['channels'] <= 4 ? $this->info['channels'] : 3;

		if(function_exists("exif_imagetype")) {
			$this->imageType = exif_imagetype($filename); 

		} else if(isset($info[2])) {
			// imagetype (IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG)
			$this->imageType = $info[2];

		} else if(isset($this->supportedImageTypes[$this->extension])) {
			$this->imageType = $this->supportedImageTypes[$this->extension]; 
		}

		if(!in_array($this->imageType, $this->supportedImageTypes)) {
			throw new WireException(basename($filename) . " - not a supported image type"); 
		}

		// width, height
		$this->setImageInfo($this->info[0], $this->info[1]);
		

		// read metadata if present and if its the first call of the method
		if(is_array($additionalInfo) && $reloadAll) {
			$appmarker = array();
			foreach($additionalInfo as $k => $v) {
				$appmarker[$k] = substr($v, 0, strpos($v, null));
			}
			$this->info['appmarker'] = $appmarker;
			if(isset($additionalInfo['APP13'])) {
				$iptc = iptcparse($additionalInfo["APP13"]);
				if(is_array($iptc)) $this->iptcRaw = $iptc;
			}
		}
		
	}

	/**
	 * Resize the image proportionally to the given width/height
	 *
	 * Note: Some code used in this method is adapted from code found in comments at php.net for the GD functions
	 *
	 * @param int $targetWidth Target width in pixels, or 0 for proportional to height
	 * @param int $targetHeight Target height in pixels, or 0 for proportional to width. Optional-if not specified, 0 is assumed.
	 * @return bool True if the resize was successful
	 * @throws WireException when not enough memory to load image
 	 *
	 */
	public function ___resize($targetWidth, $targetHeight = 0) {
		
		if($this->scale !== 1.0) {
			// adjust for hidpi
			if($targetWidth) $targetWidth = ceil($targetWidth * $this->scale);
			if($targetHeight) $targetHeight = ceil($targetHeight * $this->scale);
		}

		$orientations = null; // @horst
		$needRotation = $this->autoRotation !== true ? false : ($this->checkOrientation($orientations) && (!empty($orientations[0]) || !empty($orientations[1])) ? true : false);
		$source = $this->filename;
		$dest = str_replace("." . $this->extension, "_tmp." . $this->extension, $source); 
		$image = null;

		// check if we can load the sourceimage into ram		
		if(self::checkMemoryForImage(array($this->info[0], $this->info[1], $this->info['channels'])) === false) {
			throw new WireException(basename($source) . " - not enough memory to load");
		}

		switch($this->imageType) { // @teppo
			case IMAGETYPE_GIF: $image = @imagecreatefromgif($source); break;
			case IMAGETYPE_PNG: $image = @imagecreatefrompng($source); break;
			case IMAGETYPE_JPEG: $image = @imagecreatefromjpeg($source); break;
		}

		if(!$image) return false;

		if($this->imageType != IMAGETYPE_PNG || !$this->hasAlphaChannel()) { 
			// @horst: linearize gamma to 1.0 - we do not use gamma correction with pngs containing alphachannel, because GD-lib  doesn't respect transparency here (is buggy) 
			$this->gammaCorrection($image, true);
		}

		if($this->rotate || $needRotation) { // @horst
			$degrees = $this->rotate ? $this->rotate : $orientations[0];
			$image = $this->imRotate($image, $degrees);
			if(abs($degrees) == 90 || abs($degrees) == 270) {
				// we have to swap width & height now!
				$tmp = array($this->getWidth(), $this->getHeight());
				$this->setImageInfo($tmp[1], $tmp[0]);
			}
		}
		if($this->flip || $needRotation) {
			$vertical = null;
			if($this->flip) {
				$vertical = $this->flip == 'v';
			} else if($orientations[1] > 0) {
				$vertical = $orientations[1] == 2;
			}
			if(!is_null($vertical)) $image = $this->imFlip($image, $vertical); 
		}

		// if there is requested to crop _before_ resize, we do it here @horst
		if(is_array($this->cropExtra)) {
			// check if we can load a second copy from sourceimage into ram
			if(self::checkMemoryForImage(array($this->info[0], $this->info[1], 3)) === false) {
				throw new WireException(basename($source) . " - not enough memory to load a copy for cropExtra");
			}
			$imageTemp = imagecreatetruecolor(imagesx($image), imagesy($image));  // create an intermediate memory image
			$this->prepareImageLayer($imageTemp, $image);
			imagecopy($imageTemp, $image, 0, 0, 0, 0, imagesx($image), imagesy($image)); // copy our initial image into the intermediate one
			imagedestroy($image); // release the initial image
			// get crop values and create a new initial image
			list($x, $y, $w, $h) = $this->cropExtra;
			// check if we can load a cropped version into ram
			if(self::checkMemoryForImage(array($w, $h, 3)) === false) {
				throw new WireException(basename($source) . " - not enough memory to load a cropped version for cropExtra");
			}
			$image = imagecreatetruecolor($w, $h);
			$this->prepareImageLayer($image, $imageTemp);
			imagecopy($image, $imageTemp, 0, 0, $x, $y, $w, $h);
			unset($x, $y, $w, $h);
			// now release the intermediate image and update settings
			imagedestroy($imageTemp);
			$this->setImageInfo(imagesx($image), imagesy($image));
			// $this->cropping = false; // ?? set this to prevent overhead with the following manipulation ??
		}

		// here we check for cropping, upscaling, sharpening
		// we get all dimensions at first, before any image operation !
		list($gdWidth, $gdHeight, $targetWidth, $targetHeight) = $this->getResizeDimensions($targetWidth, $targetHeight);
		$x1 = ($gdWidth / 2) - ($targetWidth / 2);
		$y1 = ($gdHeight / 2) - ($targetHeight / 2);
		$this->getCropDimensions($x1, $y1, $gdWidth, $targetWidth, $gdHeight, $targetHeight);

		// now lets check what operations are necessary:
		if($gdWidth == $targetWidth && $gdWidth == $this->image['width'] &&  $gdHeight == $this->image['height'] && $gdHeight == $targetHeight) {
			
			// this is the case if the original size is requested or a greater size but upscaling is set to false

			/*
			// since we have added support for crop-before-resize, we have to check for this
			if(!is_array($this->cropExtra)) {
				// the sourceimage is allready the targetimage, we can leave here
				@imagedestroy($image);
				return true;
			}
			// we have a cropped_before_resized image and need to save this version,
			// so we let pass it through without further manipulation, we just need to copy it into the final memimage called "$thumb"
			*/
			
			// the current version is allready the desired result, we only may have to apply compression where possible
			$this->sharpening = 'none'; // we set sharpening to none
			
			
			if(self::checkMemoryForImage(array(imagesx($image), imagesy($image), 3)) === false) {
				throw new WireException(basename($source) . " - not enough memory to copy the final cropExtra");
			}
			$thumb = imagecreatetruecolor(imagesx($image), imagesy($image));          // create the final memory image
			$this->prepareImageLayer($thumb, $image);
			imagecopy($thumb, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));  // copy our intermediate image into the final one

		} else if($gdWidth == $targetWidth && $gdHeight == $targetHeight) {
			
			// this is the case if we scale up or down _without_ cropping

			if(self::checkMemoryForImage(array($gdWidth, $gdHeight, 3)) === false) {
				throw new WireException(basename($source) . " - not enough memory to resize to the final image");
			}

			$thumb = imagecreatetruecolor($gdWidth, $gdHeight);
			$this->prepareImageLayer($thumb, $image);
			imagecopyresampled($thumb, $image, 0, 0, 0, 0, $gdWidth, $gdHeight, $this->image['width'], $this->image['height']);
			
		} else {
			
			// we have to scale up or down and to _crop_

			if(self::checkMemoryForImage(array($gdWidth, $gdHeight, 3)) === false) {
				throw new WireException(basename($source) . " - not enough memory to resize to the intermediate image");
			}

			$thumb2 = imagecreatetruecolor($gdWidth, $gdHeight);
			$this->prepareImageLayer($thumb2, $image);
			imagecopyresampled($thumb2, $image, 0, 0, 0, 0, $gdWidth, $gdHeight, $this->image['width'], $this->image['height']);

			if(self::checkMemoryForImage(array($targetWidth, $targetHeight, 3)) === false) {
				throw new WireException(basename($source) . " - not enough memory to crop to the final image");
			}

			$thumb = imagecreatetruecolor($targetWidth, $targetHeight);
			$this->prepareImageLayer($thumb, $image);
			imagecopyresampled($thumb, $thumb2, 0, 0, $x1, $y1, $targetWidth, $targetHeight, $targetWidth, $targetHeight);
			imagedestroy($thumb2);
		}

		// optionally apply sharpening to the final thumb
		if($this->sharpening && $this->sharpening != 'none') { // @horst
			if(IMAGETYPE_PNG != $this->imageType || ! $this->hasAlphaChannel()) {
				// is needed for the USM sharpening function to calculate the best sharpening params
				$this->usmValue = $this->calculateUSMfactor($targetWidth, $targetHeight);
				$thumb = $this->imSharpen($thumb, $this->sharpening);
			}
		}

		// write to file
		$result = false;
		switch($this->imageType) {
			case IMAGETYPE_GIF:
				// correct gamma from linearized 1.0 back to 2.0
				$this->gammaCorrection($thumb, false);
				$result = imagegif($thumb, $dest); 
				break;
			case IMAGETYPE_PNG: 
				if(!$this->hasAlphaChannel()) $this->gammaCorrection($thumb, false);
				// always use highest compression level for PNG (9) per @horst
				$result = imagepng($thumb, $dest, 9);
				break;
			case IMAGETYPE_JPEG:
				// correct gamma from linearized 1.0 back to 2.0
				$this->gammaCorrection($thumb, false);
				$result = imagejpeg($thumb, $dest, $this->quality); 
				break;
		}

		if(isset($image) && is_resource($image)) @imagedestroy($image); // @horst
		if(isset($thumb) && is_resource($thumb)) @imagedestroy($thumb);
		if(isset($thumb2) && is_resource($thumb2)) @imagedestroy($thumb2);
		
		if(isset($image)) $image = null;
		if(isset($thumb)) $thumb = null;
		if(isset($thumb2)) $thumb2 = null;

		if($result === false) {
			if(is_file($dest)) @unlink($dest); 
			return false;
		}

		unlink($source); // $source is equal to $this->filename 
		rename($dest, $source); // $dest is the intermediate filename ({basename}_tmp{.ext})

		// @horst: if we've retrieved IPTC-Metadata from sourcefile, we write it back now
		$this->writeBackIptc();

		$this->loadImageInfo($this->filename, true);
		$this->modified = true;

		return true;
	}

	/**
	 * Default IPTC Handling: if we've retrieved IPTC-Metadata from sourcefile, we write it into the variation here but we omit custom tags for internal use (@horst)
	 *
	 * @param bool $includeCustomTags, default is FALSE
	 * @return bool
	 *
	 */
	public function writeBackIptc($includeCustomTags = false) {
		if(!$this->iptcRaw) return;
		$content = iptcembed($this->iptcPrepareData($includeCustomTags), $this->filename);
		if($content === false) return;
		$dest = preg_replace('/\.' . $this->extension . '$/', '_tmp.' . $this->extension, $this->filename);
		if(strlen($content) == @file_put_contents($dest, $content, LOCK_EX)) {
			// on success we replace the file
			unlink($this->filename);
			rename($dest, $this->filename);
			return true;
		} else {
			// it was created a temp diskfile but not with all data in it
			if(file_exists($dest)) @unlink($dest);
			return false;
		}
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
	 * @deprecated no longer in use, left as comment for reference, TBD later
	 *
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
	 */

	/**
	 * Given a target height, return the proportional width for this image
	 *
	 */
	protected function getProportionalWidth($targetHeight) {
		$img =& $this->image;
		return ceil(($targetHeight / $img['height']) * $img['width']); // @horst
	}

	/**
	 * Given a target width, return the proportional height for this image
	 *
	 */
	protected function getProportionalHeight($targetWidth) {
		$img =& $this->image;
		return ceil(($targetWidth / $img['width']) * $img['height']); // @horst
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

		if(!$targetHeight) $targetHeight = round(($targetWidth / $img['width']) * $img['height']); 
		if(!$targetWidth) $targetWidth = round(($targetHeight / $img['height']) * $img['width']); 

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
	 * Set values for cropExtra rectangle, which enables cropping before resizing
	 *
	 * Added by @horst
	 *
	 * @param array $value containing 4 params (x y w h) indexed or associative
	 * @return $this
	 * @throws WireException when given invalid value
	 *
	 */
	public function setCropExtra($value) {

		$this->cropExtra = null;

		if(!is_array($value) || 4 != count($value)) {
			throw new WireException('Missing or wrong param Array for ImageSizer-cropExtra!');
		}

		if(array_keys($value) === range(0, count($value) - 1)) {
			// we have a zerobased sequential array, we assume this order: x y w h
			list($x, $y, $w, $h) = $value;
		} else {
			// check for associative array
			foreach(array('x','y','w','h') as $v) {
				if(isset($value[$v])) $$v = $value[$v];
			}
		}

		foreach(array('x', 'y', 'w', 'h') as $k) {
			$v = isset($$k) ? $$k : -1;
			if(!is_int($v) || $v < 0) throw new WireException("Missing or wrong param $k for ImageSizer-cropExtra!");
			if(('w' == $k || 'h' == $k) && 0 == $v) throw new WireException("Wrong param $k for ImageSizer-cropExtra!");
		}

		$this->cropExtra = array($x, $y, $w, $h);

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
	 * Given an unknown sharpening value, return the string representation of it
	 *
	 * Okay for use in filenames. Method added by @horst
	 *
	 * @param string|bool $value
	 * @param bool $short
	 * @return string
	 *
	 */
	static public function sharpeningValueStr($value, $short = false) {

		$sharpeningValues = self::$sharpeningValues;

		if(is_string($value) && in_array(strtolower($value), $sharpeningValues)) {
			$ret = strtolower($value);

		} else if(is_int($value) && isset($sharpeningValues[$value])) {
			$ret = $sharpeningValues[$value];

		} else if(is_bool($value)) {
			$ret = $value ? "soft" : "none";

		} else {
			// sharpening is unknown, return empty string
			return '';
		}

		if(!$short) return $ret;    // return name
		$flip = array_flip($sharpeningValues);
		return 's' . $flip[$ret];   // return char s appended with the numbered index
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

		if(is_string($value) && in_array(strtolower($value), self::$sharpeningValues)) {
			$ret = strtolower($value);

		} else if(is_int($value) && isset(self::$sharpeningValues[$value])) {
			$ret = self::$sharpeningValues[$value]; 

		} else if(is_bool($value)) {
			$ret = $value ? "soft" : "none";
			
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
	 * Set default gamma value: 0.5 - 4.0 | -1
	 *
	 * @param float|int $value 0.5 to 4.0 or -1 to disable
	 * @return $this
	 * @throws WireException when given invalid value
	 *
	 */
	public function setDefaultGamma($value = 2.0) {
		if($value === -1 || ($value >= 0.5 && $value <= 4.0)) {
			$this->defaultGamma = $value;
		} else {
			throw new WireException('Invalid defaultGamma value - must be 0.5 - 4.0 or -1 to disable gammacorrection');
		}
		return $this; 
	}

	/**
	 * Set a time limit for manipulating one image (default is 30)
	 * 
	 * If specified time limit is less than PHP's max_execution_time, then PHP's setting will be used instead.
	 *
	 * @param int $value 10 to 60 recommended, default is 30
	 * @return this
	 *
	 */
	public function setTimeLimit($value = 30) {
		// imagesizer can get invoked from different locations, including those that are inside of loops
		// like the wire/modules/Inputfield/InputfieldFile/InputfieldFile.module :: ___renderList() method
		
		$prevLimit = ini_get('max_execution_time');
		
		// if unlimited execution time, no need to introduce one
		if(!$prevLimit) return; 
		
		// don't override a previously set high time limit, just start over with it
		$timeLimit = (int) ($prevLimit > $value ? $prevLimit : $value); 
		
		// restart time limit
		set_time_limit($timeLimit);
		
		return $this;
	}

	/**
	 * Set scale for hidpi (2.0=hidpi, 1.0=normal, or other value if preferred)
	 * 
	 * @param float $scale
	 * @return $this
	 * 
	 */
	public function setScale($scale) {
		$this->scale = (float) $scale;
		return $this;
	}

	/**
	 * Enable hidpi mode?
	 * 
	 * Just a shortcut for calling $this->scale()
	 * 
	 * @param bool $hidpi True or false (default=true)
	 * @return this
	 * 
	 */
	public function setHidpi($hidpi = true) {
		return $this->setScale($hidpi ? 2.0 : 1.0);	
	}

	/**
	 * Set rotation degrees
	 * 
	 * Specify one of: -270, -180, -90, 90, 180, 270
	 * 
	 * @param $degrees
	 * @return this
	 * 
	 */
	public function setRotate($degrees) {
		$valid = array(-270, -180, -90, 90, 180, 270);
		$degrees = (int) $degrees; 	
		if(in_array($degrees, $valid)) $this->rotate = $degrees; 
		return $this; 
	}
	
	/**
	 * Set flip
	 *
	 * Specify one of: 'vertical' or 'horizontal', also accepts
	 * shorter versions like, 'vert', 'horiz', 'v', 'h', etc. 
	 *
	 * @param $flip
	 * @return this
	 *
	 */
	public function setFlip($flip) {
		$flip = strtolower(substr($flip, 0, 1)); 
		if($flip == 'v' || $flip == 'h') $this->flip = $flip; 
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
	 * 	'scale' => 1.0 (use 2.0 for hidpi or 1.0 for normal-default)
	 * 	'hidpi' => false, (alternative to scale, specify true to enable hidpi)
	 * 	'rotate' => 0 (90, 180, 270 or negative versions of those)
	 * 	'flip' => '', (vertical|horizontal)
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
				case 'defaultGamma': $this->setDefaultGamma($value); break;
				case 'cropExtra': $this->setCropExtra($value); break;
				case 'scale': $this->setScale($value); break;
				case 'hidpi': $this->setHidpi($value); break;
				case 'rotate': $this->setRotate($value); break;
				case 'flip': $this->setFlip($value); break;
							  
				default: 
					// unknown or 3rd party option
					$this->options[$key] = $value; 
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
		$options = array(
			'quality' => $this->quality, 
			'cropping' => $this->cropping, 
			'upscaling' => $this->upscaling,
			'autoRotation' => $this->autoRotation,
			'sharpening' => $this->sharpening,
			'defaultGamma' => $this->defaultGamma,
			'cropExtra' => $this->cropExtra, 
			'scale' => $this->scale, 
			);
		$options = array_merge($this->options, $options); 
		return $options; 
	}

	public function __get($key) {
		$keys = array(
			'filename',
			'extension',
			'imageType',
			'image',
			'modified',
			'supportedImageTypes', 
			'info',
			'iptcRaw',
			'validIptcTags',
			'cropExtra',
			'options'
			);
		if(in_array($key, $keys)) return $this->$key; 
		if(in_array($key, $this->optionNames)) return $this->$key; 
		if(isset($this->options[$key])) return $this->options[$key]; 
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
	 * @param bool $includeCustomTags (default=false)
	 * @return string $iptcNew
	 *
	 */
	protected function iptcPrepareData($includeCustomTags = false) {
		$customTags = array('213','214','215','216','217');
		$iptcNew = '';
		foreach(array_keys($this->iptcRaw) as $s) {
			$tag = substr($s, 2);
			if(!$includeCustomTags && in_array($tag, $customTags)) continue;
			if(substr($s, 0, 1) == '2' && in_array($tag, $this->validIptcTags) && is_array($this->iptcRaw[$s])) {
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
		if($degree === false) return $im; 
		if(in_array($degree, array(-360, 0, 360))) return $im;
		return @imagerotate($im, $degree, imagecolorallocate($im, 0, 0, 0));
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

		// check if we have to use an individual value for "useUSM"
		if(isset($this->options['useUSM'])) {
			$this->useUSM = $this->getBooleanValue($this->options['useUSM']);
		}

		// due to a bug in PHP's bundled GD-Lib with the function imageconvolution in some PHP versions
		// we have to bypass this for those who have to run on this PHP versions
		// see: https://bugs.php.net/bug.php?id=66714
		// and here under GD: http://php.net/ChangeLog-5.php#5.5.11
		$buggyPHP = (version_compare(phpversion(), '5.5.8', '>') && version_compare(phpversion(), '5.5.11', '<')) ? true : false;

		// USM method is used for buggy PHP versions
		// for regular versions it can be omitted per: useUSM = false passes as pageimage option
		// or set in the site/config.php under $config->imageSizerOptions: 'useUSM' => false | true
		if($buggyPHP || $this->useUSM) {

			switch($mode) {

				case 'none':
					return $im;
					break;

				case 'strong':
					$amount=160;
					$radius=1.0;
					$threshold=7;
					break;

				case 'medium':
					$amount=130;
					$radius=0.75;
					$threshold=7;
					break;

				case 'soft':
				default:
					$amount=100;
					$radius=0.5;
					$threshold=7;
					break;
			}

			// calculate the final amount according to the usmValue
			$this->usmValue = $this->usmValue < 0 ? 0 : ($this->usmValue > 100 ? 100 : $this->usmValue);
			if(0 == $this->usmValue) return $im;
			$amount = intval($amount / 100 * $this->usmValue);

			// apply unsharp mask filter
			return $this->UnsharpMask($im, $amount, $radius, $threshold);
		}

		// if we do not use USM, we use our default sharpening method,
		// entirely based on GDs imageconvolution
		switch($mode) {

			case 'none':
				return $im;
				break;

			case 'strong':
				$sharpenMatrix = array(
					array( -1.2, -1, -1.2 ),
					array( -1,   16, -1   ),
					array( -1.2, -1, -1.2 )
				);
				break;

			case 'medium':
				$sharpenMatrix = array(
					array( -1.1, -1, -1.1 ),
					array( -1,   20, -1 ),
					array( -1.1, -1, -1.1 )
				);
				break;

			case 'soft':
			default:
				$sharpenMatrix = array(
					array( -1, -1, -1 ),
					array( -1, 24, -1 ),
					array( -1, -1, -1 )
				);
				break;
		}

		// calculate the sharpen divisor
		$divisor = array_sum(array_map('array_sum', $sharpenMatrix));
		$offset = 0;
		if(!imageconvolution($im, $sharpenMatrix, $divisor, $offset)) return false; // TODO 4 -c errorhandling: Throw WireException?

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

	/**
	 * Check orientation (@horst)
	 *
	 * @param mixed $image, pageimage or filename
	 * @param mixed $correctionArray, null or array by reference
	 * @return bool
	 *
	 */
	static public function imageIsRotated($image, &$correctionArray = null) {
		if($image instanceof Pageimage) {
			$fn = $image->filename;
		} elseif(is_readable($image)) {
			$fn = $image;
		} else {
			return null;
		}
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
		if(!function_exists('exif_read_data')) return null;
		$exif = @exif_read_data($fn, 'IFD0');
		if(!is_array($exif) || !isset($exif['Orientation']) || !in_array(strval($exif['Orientation']), array_keys($corrections))) return null;
		$correctionArray = $corrections[strval($exif['Orientation'])];
		return $correctionArray[0] > 0;
	}

	/**
	 * Check if GIF-image is animated or not (@horst)
	 *
	 * @param mixed $image, pageimage or filename
	 * @return bool
	 *
	 */
	static public function imageIsAnimatedGif($image) {
		if($image instanceof Pageimage) {
			$fn = $image->filename;
		} elseif(is_readable($image)) {
			$fn = $image;
		} else {
			return null;
		}
		$info = getimagesize($fn);
		if(IMAGETYPE_GIF != $info[2]) return false;
		if(self::checkMemoryForImage(array($info[0], $info[1]))) {
			return (bool) preg_match('/\x00\x21\xF9\x04.{4}\x00(\x2C|\x21)/s', file_get_contents($fn));
		}
		// we have not enough free memory to load the complete image at once, so we do it in chunks
		if(!($fh = @fopen($fn, 'rb'))) {
			return null;
		}
		$count = 0;
		while(!feof($fh) && $count < 2) {
			$chunk = fread($fh, 1024 * 100); //read 100kb at a time
			$count += preg_match_all('#\x00\x21\xF9\x04.{4}\x00[\x2C\x21]#s', $chunk, $matches);
		}
		fclose($fh);
		return $count > 1;
	}

	/**
	 * Possibility to clean IPTC data, also for original images (@horst)
	 *
	 * @param mixed $image, pageimage or filename
	 * @return mixed, null or bool
	 *
	 */
	static public function imageResetIPTC($image) {
		if($image instanceof Pageimage) {
			$fn = $image->filename;
		} elseif(is_readable($image)) {
			$fn = $image;
		} else {
			return null;
		}
		$is = new ImageSizer($fn);
		$result = false !== $is->writeBackIptc() ? true : false;
		unset($is);
		return $result;
	}

	/**
	 * Check for alphachannel in PNGs
	 *
	 * This method by Horst, who also credits initial code as coming from the FPDF project: 
	 * http://www.fpdf.org/
	 *
	 * @return bool
	 *
	 */
	protected function hasAlphaChannel() {
		$errors = array();
		$a = array();
		$f = @fopen($this->filename,'rb');
		if($f === false) return false;

		// Check signature
		if(@fread($f,8) != chr(137) .'PNG' . chr(13) . chr(10) . chr(26) . chr(10)) {
			@fclose($f);
			return false;
		}
		// Read header chunk
		@fread($f, 4);
		if(@fread($f, 4) != 'IHDR') {
			@fclose($f);
			return false;
		}
		$a['width']  = $this->freadint($f);
		$a['height'] = $this->freadint($f);
		$a['bits']   = ord(@fread($f, 1));
		$a['alpha']  = false;

		$ct = ord(@fread($f, 1));
		if($ct == 0) {
			$a['channels'] = 1;
			$a['colspace'] = 'DeviceGray';
		} else if($ct == 2) {
			$a['channels'] = 3;
			$a['colspace'] = 'DeviceRGB';
		} else if($ct == 3) {
			$a['channels'] = 1;
			$a['colspace'] = 'Indexed';
		} else {
			$a['channels'] = $ct;
			$a['colspace'] = 'DeviceRGB';
			$a['alpha']	= true; // alphatransparency in 24bit images !
		}

		if($a['alpha']) return true;   // early return

		if(ord(@fread($f, 1)) != 0) $errors[] = 'Unknown compression method!';
		if(ord(@fread($f, 1)) != 0) $errors[] = 'Unknown filter method!';
		if(ord(@fread($f, 1)) != 0) $errors[] = 'Interlacing not supported!';

		// Scan chunks looking for palette, transparency and image data
		// http://www.w3.org/TR/2003/REC-PNG-20031110/#table53
		// http://www.libpng.org/pub/png/book/chapter11.html#png.ch11.div.6
		@fread($f, 4);
		$pal = '';
		$trns = '';
		$counter = 0;
		
		do {
			$n = $this->freadint($f);
			$counter += $n;
			$type = @fread($f, 4);
			
			if($type == 'PLTE') {
				// Read palette
				$pal = @fread($f, $n);
				@fread($f, 4);
				
			} else if($type == 'tRNS') {
				// Read transparency info
				$t = @fread($f, $n);
				if($ct == 0) {
					$trns = array(ord(substr($t, 1, 1)));
				} else if($ct == 2) {
					$trns = array(ord(substr($t, 1, 1)), ord(substr($t, 3, 1)), ord(substr($t, 5, 1)));
				} else {
					$pos = strpos($t, chr(0));
					if(is_int($pos)) {
						$trns = array($pos);
					}
				}
				@fread($f, 4);
				break;
				
			} else if($type == 'IEND' || $type == 'IDAT' || $counter >= 2048) {
				break;
				
			} else {
				fread($f, $n + 4);
			}
			
		} while($n);

		@fclose($f);
		if($a['colspace'] == 'Indexed' and empty($pal)) $errors[] = 'Missing palette!';
		if(count($errors) > 0) $a['errors'] = $errors;
		if(!empty($trns)) $a['alpha'] = true;  // alphatransparency in 8bit images !
		
		return $a['alpha'];	
	}

	/**
	 * apply GammaCorrection to an image (@horst)
	 * 
	 * with mode = true it linearizes an image to 1
	 * with mode = false it set it back to the originating gamma value
	 *
	 * @param GD-image-resource $image
	 * @param boolean $mode
	 *
	 */
	protected function gammaCorrection(&$image, $mode) {
		if(-1 == $this->defaultGamma || !is_bool($mode)) return;
		if($mode) {
			// linearizes to 1.0
			if(imagegammacorrect($image, $this->defaultGamma, 1.0)) $this->gammaLinearized = true;
		} else {
			if(!isset($this->gammaLinearized) || !$this->gammaLinearized) return;
			// switch back to original Gamma
			if(imagegammacorrect($image, 1.0, $this->defaultGamma)) unset($this->gammaLinearized);
		}
	}


	/**
	 * reads a 4-byte integer from file (@horst)
	 *
	 * @param filepointer
	 * @return mixed
	 *
	 */
	protected function freadint(&$f) {
		$i = ord(@fread($f, 1)) << 24;
		$i += ord(@fread($f, 1)) << 16;
		$i += ord(@fread($f, 1)) << 8;
		$i += ord(@fread($f, 1));
		return $i;
	}

	/**
	 * Set whether the image was modified
	 *
	 * Public so that other modules/hooks can adjust this property if needed.
	 * Not for general API use
	 *
	 * @param bool $modified
	 * @return this
	 *
	 */
	public function setModified($modified) {
		$this->modified = $modified ? true : false;
		return $this;
	}


	/**
	 * Unsharp Mask for PHP - version 2.1.1
 	 *	
	 * Unsharp mask algorithm by Torstein Hønsi 2003-07.
	 * thoensi_at_netcom_dot_no.
	 * Please leave this notice.
	 *
	 * http://vikjavev.no/computing/ump.php
	 *
	 */
	protected function unsharpMask($img, $amount, $radius, $threshold) {


		// Attempt to calibrate the parameters to Photoshop:
		if($amount > 500) $amount = 500;
		$amount = $amount * 0.016;
		if($radius > 50) $radius = 50;
		$radius = $radius * 2;
		if($threshold > 255) $threshold = 255;

		$radius = abs(round($radius));     // Only integers make sense.
		if($radius == 0) {
			return $img;
		}
		$w = imagesx($img);
		$h = imagesy($img);
		$imgCanvas = imagecreatetruecolor($w, $h);
		$imgBlur = imagecreatetruecolor($w, $h);

		// due to a bug in PHP's bundled GD-Lib with the function imageconvolution in some PHP versions
		// we have to bypass this for those who have to run on this PHP versions
		// see: https://bugs.php.net/bug.php?id=66714
		// and here under GD: http://php.net/ChangeLog-5.php#5.5.11
		$buggyPHP = (version_compare(phpversion(), '5.5.8', '>') && version_compare(phpversion(), '5.5.11', '<')) ? true : false;

		// Gaussian blur matrix:
		//
		//    1    2    1
		//    2    4    2
		//    1    2    1
		//
		//////////////////////////////////////////////////
		if(function_exists('imageconvolution') && !$buggyPHP) {
			$matrix = array(
				array( 1, 2, 1 ),
				array( 2, 4, 2 ),
				array( 1, 2, 1 )
			);
			imagecopy ($imgBlur, $img, 0, 0, 0, 0, $w, $h);
			imageconvolution($imgBlur, $matrix, 16, 0);
		} else {
			// Move copies of the image around one pixel at the time and merge them with weight
			// according to the matrix. The same matrix is simply repeated for higher radii.
			for ($i = 0; $i < $radius; $i++)    {
				imagecopy ($imgBlur, $img, 0, 0, 1, 0, $w - 1, $h); // left
				imagecopymerge ($imgBlur, $img, 1, 0, 0, 0, $w, $h, 50); // right
				imagecopymerge ($imgBlur, $img, 0, 0, 0, 0, $w, $h, 50); // center
				imagecopy ($imgCanvas, $imgBlur, 0, 0, 0, 0, $w, $h);

				imagecopymerge ($imgBlur, $imgCanvas, 0, 0, 0, 1, $w, $h - 1, 33.33333 ); // up
				imagecopymerge ($imgBlur, $imgCanvas, 0, 1, 0, 0, $w, $h, 25); // down
			}
		}

		if($threshold>0) {
			// Calculate the difference between the blurred pixels and the original
			// and set the pixels
			for($x = 0; $x < $w-1; $x++) { // each row
				for($y = 0; $y < $h; $y++) { // each pixel

					$rgbOrig = ImageColorAt($img, $x, $y);
					$rOrig = (($rgbOrig >> 16) & 0xFF);
					$gOrig = (($rgbOrig >> 8) & 0xFF);
					$bOrig = ($rgbOrig & 0xFF);

					$rgbBlur = ImageColorAt($imgBlur, $x, $y);

					$rBlur = (($rgbBlur >> 16) & 0xFF);
					$gBlur = (($rgbBlur >> 8) & 0xFF);
					$bBlur = ($rgbBlur & 0xFF);

					// When the masked pixels differ less from the original
					// than the threshold specifies, they are set to their original value.
					$rNew = (abs($rOrig - $rBlur) >= $threshold)
						? max(0, min(255, ($amount * ($rOrig - $rBlur)) + $rOrig))
						: $rOrig;
					$gNew = (abs($gOrig - $gBlur) >= $threshold)
						? max(0, min(255, ($amount * ($gOrig - $gBlur)) + $gOrig))
						: $gOrig;
					$bNew = (abs($bOrig - $bBlur) >= $threshold)
						? max(0, min(255, ($amount * ($bOrig - $bBlur)) + $bOrig))
						: $bOrig;

					if(($rOrig != $rNew) || ($gOrig != $gNew) || ($bOrig != $bNew)) {
						$pixCol = ImageColorAllocate($img, $rNew, $gNew, $bNew);
						ImageSetPixel($img, $x, $y, $pixCol);
					}
				}
			}
		} else {
			for($x = 0; $x < $w; $x++) { // each row
				for($y = 0; $y < $h; $y++) { // each pixel
					$rgbOrig = ImageColorAt($img, $x, $y);
					$rOrig = (($rgbOrig >> 16) & 0xFF);
					$gOrig = (($rgbOrig >> 8) & 0xFF);
					$bOrig = ($rgbOrig & 0xFF);

					$rgbBlur = ImageColorAt($imgBlur, $x, $y);

					$rBlur = (($rgbBlur >> 16) & 0xFF);
					$gBlur = (($rgbBlur >> 8) & 0xFF);
					$bBlur = ($rgbBlur & 0xFF);

					$rNew = ($amount * ($rOrig - $rBlur)) + $rOrig;
					if($rNew>255) {
						$rNew=255;
					} else if($rNew<0) {
						$rNew=0;
					}
					$gNew = ($amount * ($gOrig - $gBlur)) + $gOrig;
					if($gNew>255) {
						$gNew=255;
					}
					else if($gNew<0) {
						$gNew=0;
					}
					$bNew = ($amount * ($bOrig - $bBlur)) + $bOrig;
					if($bNew>255) {
						$bNew=255;
					}
					else if($bNew<0) {
						$bNew=0;
					}
					$rgbNew = ($rNew << 16) + ($gNew <<8) + $bNew;
					ImageSetPixel($img, $x, $y, $rgbNew);
				}
			}
		}
		imagedestroy($imgCanvas);
		imagedestroy($imgBlur);

		return $img;
	}

	/**
	 * return an integer value indicating how much an image should be sharpened
	 * according to resizing scalevalue and absolute target dimensions
	 *
	 * @param mixed $targetWidth width of the targetimage
	 * @param mixed $targetHeight height of the targetimage
	 * @param mixed $origWidth
	 * @param mixed $origHeight
	 * @return int
	 *
	 */
	protected function calculateUSMfactor($targetWidth, $targetHeight, $origWidth = null, $origHeight = null) {

		if(null === $origWidth) $origWidth = $this->getWidth();
		if(null === $origHeight) $origHeight = $this->getHeight();
		$w = ceil($targetWidth / $origWidth * 100);
		$h = ceil($targetHeight / $origHeight * 100);

		// select the resizing scalevalue with check for crop images
		if($w==$h || ($w-1)==$h || ($w+1)==$h) {  // equal, no crop
			$resizingScalevalue = $w;
			$target = $targetWidth;
		}
		else { // crop
			if(($w<$h && $w<100) || ($w>$h && $h>=100)) {
				$resizingScalevalue = $w;
				$target = $targetWidth;
			}
			elseif(($w<$h && $w>=100) || ($w>$h && $h<100)) {
				$resizingScalevalue = $h;
				$target = $targetHeight;
			}
		}

		// adjusting with respect to the scalefactor
		$resizingScalevalue = ($resizingScalevalue * -1) + 100;
		$resizingScalevalue = $resizingScalevalue < 0 ? $resizingScalevalue * -1 : $resizingScalevalue;
		if($resizingScalevalue>0 && $resizingScalevalue<10) $resizingScalevalue += 15;
		elseif($resizingScalevalue>9 && $resizingScalevalue<25) $resizingScalevalue += 20;
		elseif($resizingScalevalue>24 && $resizingScalevalue<40) $resizingScalevalue += 35;
		elseif($resizingScalevalue>39 && $resizingScalevalue<55) $resizingScalevalue += 20;
		elseif($resizingScalevalue>54 && $resizingScalevalue<70) $resizingScalevalue +=  5;
		elseif($resizingScalevalue>69 && $resizingScalevalue<80) $resizingScalevalue -= 10;

		// adjusting with respect to absolute dimensions
		if($target < 50) $res = intval($resizingScalevalue / 18 * 3);
		elseif($target < 100) $res = intval($resizingScalevalue / 18 * 4);
		elseif($target < 200) $res = intval($resizingScalevalue / 18 * 6);
		elseif($target < 300) $res = intval($resizingScalevalue / 18 * 8);
		elseif($target < 400) $res = intval($resizingScalevalue / 18 * 10);
		elseif($target < 500) $res = intval($resizingScalevalue / 18 * 12);
		elseif($target < 600) $res = intval($resizingScalevalue / 18 * 15);
		elseif($target > 599) $res = $resizingScalevalue;
		$res = $res < 0 ? $res * -1 : $res; // avoid negative numbers

		return $res;
	}

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
			$transparentColor = $transparentIndex != -1 ? @imagecolorsforindex($image, $transparentIndex) : 0;
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

		if(is_string($this->cropping)) {
			
			switch($this->cropping) {
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
			}
			
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

	/**
	 * calculation if there is enough memory available at runtime for loading and resizing an given imagefile
	 *
	 * @param $sourceDimensions - array with three values: width, height, number of channels
	 * @param $targetDimensions - optional - mixed: bool true | false or array with three values: width, height, number of channels
	 * @return bool if a calculation was possible (true|false), or null if the calculation could not be done
	 *
	 */
	static public function checkMemoryForImage($sourceDimensions, $targetDimensions = false) {

		static $phpMaxMem = null; // with this static we only once need to read from php.ini and calculate phpMaxMem, regardless how often this function is called in a request

		if(null === $phpMaxMem) {
			$sMem = trim(strtoupper(ini_get('memory_limit')), ' B'); // trim B just in case it has Mb rather than M
			switch(substr($sMem, -1)) {
				case 'M': $phpMaxMem = ((int) $sMem) * 1048576; break;
				case 'K': $phpMaxMem = ((int) $sMem) * 1024; break;
				case 'G': $phpMaxMem = ((int) $sMem) * 1073741824; break;
				default: $phpMaxMem = (int) $sMem;
			}
		}

		if($phpMaxMem <= 0) return null; // we couldn't read the MaxMemorySetting or there isn't one set, so in both cases we do not know if there is enough or not

		// calculate $sourceDimensions
		if(!isset($sourceDimensions[0]) || !isset($sourceDimensions[1]) || !isset($sourceDimensions[2]) || !is_int($sourceDimensions[0]) || !is_int($sourceDimensions[1]) || !is_int($sourceDimensions[2])) return null;
		//            width             *        height        *       channels
		$imgMem = ($sourceDimensions[0] * $sourceDimensions[1] * $sourceDimensions[2]);

		if(true === $targetDimensions) {
			// we have to add ram for a copy of the sourceimage
			$imgMem += $imgMem;
		} else if(is_array($targetDimensions)) {
			// we have to add ram for a targetimage
			if(!isset($targetDimensions[0]) || !isset($targetDimensions[1]) || !isset($targetDimensions[2]) || !is_int($targetDimensions[0]) || !is_int($targetDimensions[1]) || !is_int($targetDimensions[2])) return null;
			$imgMem += ($targetDimensions[0] * $targetDimensions[1] * $targetDimensions[2]);
		}

		// read current allocated memory
		$curMem = memory_get_usage(true);  // memory_get_usage() is always available with PHP since 5.2.1

		// check if there is enough RAM loading the image(s), plus 3 MB for GD to use for calculations/transforms
		return ($phpMaxMem - $curMem >= $imgMem + (3 * 1048576)) ? true : false;
	}



}
