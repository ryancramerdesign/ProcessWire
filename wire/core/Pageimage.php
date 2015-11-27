<?php

/**
 * ProcessWire Pageimage
 *
 * Represents a single image item attached to a page, typically via a FieldtypeImage field.
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2015 by Ryan Cramer 
 * This file licensed under Mozilla Public License v2.0 http://mozilla.org/MPL/2.0/
 * 
 * https://processwire.com
 *
 * 
 * @property int $width Width of image, in pixels
 * @property int $height Height of image, in pixels
 * @property Pageimage $original Reference to original $image, if this is a resized version.
 *
 */

class Pageimage extends Pagefile {

	/**
	 * Reference to the collection of Pageimages that this Pageimage belongs to
	 *
	 */
	protected $pageimages; 

	/**
	 * Reference to the original image this variation was created from
	 *
	 * Applicable only if this image is a variation (resized version). It will be null in all other instances. 
	 *
	 */
	protected $original = null;

	/**
	 * Cached result of the getVariations() method
	 *
	 * Don't reference this directly, because it won't be loaded unless requested, instead use the getVariations() method
	 *
	 */
	private $variations = null; 

	/**
	 * Cached result of the getImageInfo() method
	 *
	 * Don't reference this directly, because it won't be loaded unless requested, instead use the getImageInfo() method
	 *
	 */
	private $imageInfo = array(
		'width' => 0, 
		'height' => 0, 
		); 

	/**
	 * Last size error, if one occurred. 
	 *
	 */
	protected $error = '';

	/**
	 * Construct a new Pagefile
	 *
	 * @param Pagefiles $pagefiles 
	 * @param string $filename Full path and filename to this pagefile
	 * @throws WireException
	 *
	 */
	public function __construct(Pagefiles $pagefiles, $filename) {

		if(!$pagefiles instanceof Pageimages) throw new WireException("Pageimage::__construct requires instance of Pageimages"); 
		$this->pageimages = $pagefiles; 
		parent::__construct($pagefiles, $filename); 
	}

	/**
	 * When a Pageimage is cloned, we reset it's width and height to force them to reload in the clone
	 *
	 */
	public function __clone() {
		$this->imageInfo['width'] = 0; 
		$this->imageInfo['height'] = 0; 
	}

	/**
	 * Return the web accessible URL to this Pagefile
	 *
	 */
	public function url() {
		if(self::isHooked('Pagefile::url()') || self::isHooked('Pageimage::url()')) { 
			return $this->__call('url', array()); 
		} else { 
			return $this->___url();
		}
	}

	/**
	 * Returns the disk path to the Pagefile
	 *
	 */
	public function filename() {
		if(self::isHooked('Pagefile::filename()') || self::isHooked('Pageimage::filename()')) { 
			return $this->__call('filename', array()); 
		} else { 
			return $this->___filename();
		}
	}

	/**
	 * Returns array of suffixes for this file or true/false if this file has the given suffix
	 * 
	 * When providing a suffix, this method can be thought of: hasSuffix(suffix)
	 * 
	 * @param string $s Optionally provide suffix to return true/false if file has the suffix
	 * @return array|bool 
	 * 
	 */
	public function suffix($s = '') {
		$info = $this->isVariation(parent::get('basename')); 
		if(strlen($s)) {
			return $info ? in_array($s, $info['suffix']) : false;
		} else {
			return $info ? $info['suffix'] : array();
		}
	}

	/**
	 * Returns the basename of this Pagefile
	 *
	 */
	public function basename() {
		return parent::get('basename'); 
	}

	/**
	 * Get a property from this Pageimage
	 * 
	 * @param string $key
	 * @return mixed
	 *
	 */
	public function get($key) {
		switch($key) {
			case 'width':
			case 'height':
				$value = $this->$key();
				break;
			case 'hidpiWidth':
			case 'retinaWidth':
				$value = $this->hidpiWidth();
				break;
			case 'hidpiHeight':
			case 'retinaHeight':
				$value = $this->hidpiHeight();
				break;
			case 'original':
				$value = $this->getOriginal();
				break;
			case 'error':
				$value = $this->error;
				break;
			default: 
				$value = parent::get($key); 
		}
		return $value; 
	}

	/**
	 * Gets the image information with PHP's getimagesize function and caches the result
	 * 
	 */
	public function getImageInfo($reset = false) {

		if($reset) $checkImage = true; 
			else if($this->imageInfo['width']) $checkImage = false; 
			else $checkImage = true; 

		if($checkImage) { 
			if($this->ext == 'svg') {
				if($xml = @file_get_contents($this->filename)) {
					$a = @simplexml_load_string($xml)->attributes();
					$this->imageInfo['width'] = (int) $a->width > 0 ? (int) $a->width : '100%';
					$this->imageInfo['height'] = (int) $a->height > 0 ? (int) $a->height : '100%';
				}
			} else if($info = @getimagesize($this->filename)) {
				$this->imageInfo['width'] = $info[0]; 
				$this->imageInfo['height'] = $info[1]; 
			}
		}

		return $this->imageInfo; 
	}
	
	/**
	 * Return a Pageimage object sized/cropped to the specified dimensions. 
	 *
	 * The 3rd argument $options may be an array, string, integer or boolean. When an array, you may specify multiple options
	 * to override. These include: 'quality', 'upscaling', and 'cropping'. When a string, it is assumed you are specifying
	 * a cropping value. When an integer, it is assumed you are specifying a quality value. When a boolean, it is assumed you
	 * are specifying an 'upscaling' toggle on/off. 
	 *
	 * Cropping may be specified either in the options array with the 'cropping' index, or via a 3rd string param to the function.
	 * Possible values for 'cropping' include: northwest, north, northeast, west, center, east, southwest, south, southeast.
	 * If you prefer, you can specify shorter versions like 'nw' for 'northwest', or 's' for 'south', etc. 
	 * If cropping is not specified, then 'center' is assumed. 
	 * To completely disable cropping, specify a blank string.
	 *
	 * Quality may be specified either in the options array with the 'quality' index, or via a 3rd integer param to the function.
	 * Possible values for 'quality' are 1 to 100. Default is 90. Important: See the PLEASE NOTE section below.
	 *
	 * Upscaling may be specified either in the options array with the 'upscaling' index, or via a 3rd boolean param to the function.
	 * Possible values for 'upscaling' are TRUE and FALSE. Default is TRUE. Important: See the PLEASE NOTE section below.
	 *
	 * PLEASE NOTE: ProcessWire doesn't keep separate copies of images with different 'quality' or 'upscaling' values. If you change
	 * these and a variation image at the existing dimensions already exists, then you'll still get the old version. 
	 * To clear out an old version of an image, use the removeVariations() method in this class before calling size() with new 
	 * quality or upscaling settings.
	 *
	 * @param int $width
	 * @param int $height
	 * @param array|string|int $options Array of options (or selector string) to override default behavior: 
	 * 	- quality=90 (quality setting 1-100)
	 * 	- upscaling=true (allow image to be upscaled?)
	 * 	- cropping=center (cropping mode, see ImageSizer class for options)
	 * 	- suffix='word' (your suffix word in fieldName format, or use array of words for multiple)
	 * 	- forceNew=true (force re-creation of the image?)
	 * 	- sharpening=soft (specify: none, soft, medium, strong)
	 * 	- autoRotation=true (automatically correct rotation of images that provide the info)
	 * 	- rotate=0 (Specify degrees, one of: -270, -180, -90, 90, 180, 270)
	 * 	- flip='' (To flip, specify either 'vertical' or 'horizontal')
	 * 	- hidpi=false (specify true to enable hidpi/retuna/pixel doubling)
	 * 	- cleanFilename=false (clean filename of historical resize information for shorter filenames)
	 *	Or you may specify a string|bool with with 'cropping' value if you don't need to combine with other options.
	 *	Or you may specify an integer with 'quality' value if you don't need to combine with other options.
	 * 	Or you may specify a boolean with 'upscaling' value if you don't need to combine with other options.
	 * @return Pageimage
	 *
	 */
	public function size($width, $height, $options = array()) {

		if(self::isHooked('Pageimage::size()')) {
			return $this->__call('size', array($width, $height, $options)); 
		} else { 
			return $this->___size($width, $height, $options);
		}
	}

	/**
	 * Hookable version of size() with implementation
	 *	
	 * See comments for size() method above.
	 * 
	 * @param int $width
	 * @param int $height
	 * @param array|string|int $options
	 * @return Pageimage
	 *
	 */
	protected function ___size($width, $height, $options) {

		// I was getting unnecessarily resized images without this code below,
		// but this may be better solved in ImageSizer?
		/*
		$w = $this->width();
		$h = $this->height();
		if($w == $width && $h == $height) return $this; 
		if(!$height && $w == $width) return $this; 
		if(!$width && $h == $height) return $this; 
		*/
		
		if($this->ext == 'svg') return $this; 

		if(!is_array($options)) { 
			if(is_string($options)) {
				// optionally allow a string to be specified with crop direction, for shorter syntax
				if(strpos($options, ',') !== false) $options = explode(',', $options); // 30,40
				$options = array('cropping' => $options); 
			} else if(is_int($options)) {
				// optionally allow an integer to be specified with quality, for shorter syntax
				$options = array('quality' => $options);
			} else if(is_bool($options)) {
				// optionally allow a boolean to be specified with upscaling toggle on/off
				$options = array('upscaling' => $options); 
			} else { 
				// unknown options type
				$options = array();
			}
		}

		$defaultOptions = array(
			'upscaling' => true,
			'cropping' => true,
			'quality' => 90,
			'hidpiQuality' => 40, 
			'suffix' => array(), // can be array of suffixes or string of 1 suffix
			'forceNew' => false,  // force it to create new image even if already exists
			'hidpi' => false, 
			'cleanFilename' => false, // clean filename of historial resize information
			'rotate' => 0,
			'flip' => '', 
			);

		$this->error = '';
		$configOptions = wire('config')->imageSizerOptions; 
		if(!is_array($configOptions)) $configOptions = array();
		$options = array_merge($defaultOptions, $configOptions, $options); 

		$width = (int) $width;
		$height = (int) $height;
		
		if(is_string($options['cropping'])
			&& strpos($options['cropping'], 'x') === 0
			&& preg_match('/^x(\d+)[yx](\d+)/', $options['cropping'], $matches)) {
			$options['cropping'] = true; 
			$options['cropExtra'] = array((int) $matches[1], (int) $matches[2], $width, $height); 
			$crop = '';
		} else {
			$crop = ImageSizer::croppingValueStr($options['cropping']);
		}
		
	
		if(!is_array($options['suffix'])) {
			// convert to array
			$options['suffix'] = empty($options['suffix']) ? array() : explode(' ', $options['suffix']); 
		}

		if($options['rotate'] && !in_array(abs((int) $options['rotate']), array(90, 180, 270))) $options['rotate'] = 0;
		if($options['rotate']) $options['suffix'][] = ($options['rotate'] > 0 ? "rot" : "tor") . abs($options['rotate']); 
		if($options['flip']) $options['suffix'][] = strtolower(substr($options['flip'], 0, 1)) == 'v' ? 'flipv' : 'fliph';
		
		$suffixStr = '';
		if(!empty($options['suffix'])) {
			$suffix = $options['suffix'];
			sort($suffix); 
			foreach($suffix as $key => $s) {
				$s = strtolower($this->wire('sanitizer')->fieldName($s)); 
				if(empty($s)) unset($suffix[$key]); 
					else $suffix[$key] = $s; 
			}
			if(count($suffix)) $suffixStr = '-' . implode('-', $suffix); 
		}
		
		if($options['hidpi']) {
			$suffixStr .= '-hidpi';
			if($options['hidpiQuality']) $options['quality'] = $options['hidpiQuality'];
		}

		//$basename = $this->pagefiles->cleanBasename($this->basename(), false, false, false);
		// cleanBasename($basename, $originalize = false, $allowDots = true, $translate = false) 
		$basename = basename($this->basename(), "." . $this->ext());        // i.e. myfile
		if($options['cleanFilename'] && strpos($basename, '.') !== false) {
			$basename = substr($basename, 0, strpos($basename, '.')); 
		}
		$basename .= '.' . $width . 'x' . $height . $crop . $suffixStr . "." . $this->ext();	// i.e. myfile.100x100.jpg or myfile.100x100nw-suffix1-suffix2.jpg
		$filenameFinal = $this->pagefiles->path() . $basename;
		$filenameUnvalidated = '';
		$exists = file_exists($filenameFinal);

		if(!$exists || $options['forceNew']) {
			$filenameUnvalidated = $this->pagefiles->page->filesManager()->getTempPath() . $basename;
			if($exists && $options['forceNew']) @unlink($filenameFinal);
			if(file_exists($filenameUnvalidated)) @unlink($filenameUnvalidated);
			if(@copy($this->filename(), $filenameUnvalidated)) {
				try { 
					$sizer = new ImageSizer($filenameUnvalidated);
					$sizer->setOptions($options);
					if($sizer->resize($width, $height) && @rename($filenameUnvalidated, $filenameFinal)) {
						wireChmod($filenameFinal); 
					} else {
						$this->error = "ImageSizer::resize($width, $height) failed for $filenameUnvalidated";
					}
				} catch(Exception $e) {
					$this->trackException($e, false); 
					$this->error = $e->getMessage(); 
				}
			} else {
				$this->error("Unable to copy $this->filename => $filenameUnvalidated"); 
			}
		}

		$pageimage = clone $this; 

		// if desired, user can check for property of $pageimage->error to see if an error occurred. 
		// if an error occurred, that error property will be populated with details
		if($this->error) { 
			// error condition: unlink copied file 
			if(is_file($filenameFinal)) @unlink($filenameFinal);
			if($filenameUnvalidated && is_file($filenameUnvalidated)) @unlink($filenameUnvalidated);

			// write an invalid image so it's clear something failed
			// todo: maybe return a 1-pixel blank image instead?
			$data = "This is intentionally invalid image data.\n$this->error";
			if(file_put_contents($filenameFinal, $data) !== false) wireChmod($filenameFinal);

			// we also tell PW about it for logging and/or admin purposes
			$this->error($this->error); 
		}

		$pageimage->setFilename($filenameFinal); 	
		$pageimage->setOriginal($this); 

		return $pageimage; 
	}
	
	/**
	 * Same as size() but with width/height assumed to be hidpi width/height
	 * 
	 * @param $width
	 * @param $height
	 * @param array $options See options in size() method. 
	 * @return Pageimage
	 *
	 */
	public function hidpiSize($width, $height, $options = array()) {
		$options['hidpi'] = true; 
		return $this->size($width, $height, $options); 
	}

	/**
	 * Create a crop and return new Pageimage
	 * 
	 * @param int $x Starting X position (left) in pixels
	 * @param int $y Starting Y position (top) in pixels
	 * @param int $width Width of crop in pixels
	 * @param int $height Height of crop in pixels
	 * @param array $options See options array for size() method. Avoid setting crop properties here since we are overriding them.
	 * @return Pageimage
	 *
	 */
	public function ___crop($x, $y, $width, $height, $options = array()) {
		
		$x = (int) $x;
		$y = (int) $y;
		$width = (int) $width;
		$height = (int) $height;
		
		if(empty($options['suffix'])) {
			$options['suffix'] = array();
		} else if(!is_array($options['suffix'])) {
			$options['suffix'] = array($options['suffix']); 
		}
		
		$options['suffix'][] = "cropx{$x}y{$y}"; 
		$options['cropExtra'] = array($x, $y, $width, $height);
		$options['cleanFilename'] = true; 
		
		return $this->size($width, $height, $options);
	}

	/**
	 * Multipurpose: return the width of the Pageimage OR return an image sized with a given width (and proportional height)
	 *
	 * If given a width, it'll return a new Pageimage object sized to that width. 
	 * If not given a width, it'll return the width of this Pageimage
	 *
	 * @param int $n Optional width
	 * @param array|string|int|bool $options Optional options (see size function)
	 * @return int|Pageimage
	 *
	 */
	public function width($n = 0, $options = array()) {
		if($n) return $this->size($n, 0, $options); 	
		$info = $this->getImageInfo();
		return $info['width']; 
	}

	/**
	 * Multipurpose: return the height of the Pageimage OR return an image sized with a given height (and proportional width)
	 *
	 * If given a height, it'll return a new Pageimage object sized to that height. 
	 * If not given a height, it'll return the height of this Pageimage
	 *
	 * @param int $n Optional height
	 * @param array|string|int|bool $options Optional options (see size function)
	 * @return int|Pageimage
	 *
	 */
	public function height($n = 0, $options = array()) {
		if($n) return $this->size(0, $n, $options); 	
		$info = $this->getImageInfo();
		return $info['height']; 
	}

	/**
	 * Return width for hidpi/retina use, or resize an image for desired hidpi width
	 * 
	 * If the $width argument is ommitted or provided as a float, hidpi width (int) is returned (default scale=0.5)
	 * If $width is provided (int) then a new Pageimage is returned at that width x 2 (for hidpi use).
	 * 
	 * @param int|float $width Specify int to return resized image for hidpi, or float (or omit) to return current width at hidpi.
	 * @param array $options Optional options for use when resizing, see size() method for details.
	 * 	Or you may specify an int as if you want to return a hidpi width and want to calculate with that width rather than current image width.
	 * @return int|Pageimage
	 * 
	 */	
	public function hidpiWidth($width = 0, $options = array()) {
		if(is_float($width) || $width < 1) {
			// return hidpi width intended: scale omitted or provided in $width argument
			$scale = $width;
			if(!$scale || $scale < 0 || $scale > 1) $scale = 0.5;
			$width = is_array($options) ? 0 : (int) $options;
			if($width < 1) $width = $this->width();
			return ceil($width * $scale); 
		} else if($width) {
			// resize intended
			if(!is_array($options)) $options = array();
			return $this->hidpiSize((int) $width, 0, $options);
		}
	}

	/**
	 * Return height for hidpi/retina use, or resize an image for desired hidpi height
	 *
	 * If the $height argument is omitted or provided as a float, hidpi height (int) is returned (default scale=0.5)
	 * If $height is provided (int) then a new Pageimage is returned at that height x 2 (for hidpi use).
	 *
	 * @param int|float $height Specify int to return resized image for hidpi, or float (or omit) to return current width at hidpi.
	 * @param array|int $options Optional options for use when resizing, see size() method for details.
	 * 	Or you may specify an int as if you want to return a hidpi height and want to calculate with that height rather than current image height.
	 * @return int|Pageimage
	 *
	 */	
	public function hidpiHeight($height = 0, $options = array()) {
		if(is_float($height) || $height < 1) {
			// return hidpi height intended: scale omitted or provided in $height argument
			$scale = $height;
			if(!$scale || $scale < 0 || $scale > 1) $scale = 0.5;
			$height = is_array($options) ? 0 : (int) $options;
			if($height < 1) $height = $this->height();
			return ceil($height * $scale);
		} else if($height) {
			// resize intended
			if(!is_array($options)) $options = array();
			return $this->hidpiSize(0, (int) $height, $options);
		}
	}

	/**
	 * Return an image no larger than the given width
	 *
	 * If source image is equal to or smaller than the requested dimension, 
	 * then it will remain that way and the source image is returned (not a copy).
	 * 
	 * If the source image is larger than the requested dimension, then a new copy
	 * will be returned at the requested dimension.
	 *
 	 * @param int $n Maximum width
	 * @param array $options Optional options array
	 * @return Pageimage
	 *
	 */
	public function maxWidth($n, array $options = array()) {
		$options['upscaling'] = false;
		if($this->width() > $n) return $this->width($n); 
		return $this;
	}

	/**
	 * Return an image no larger than the given height
	 *
	 * If source image is equal to or smaller than the requested dimension, 
	 * then it will remain that way and the source image is returned (not a copy).
	 * 
	 * If the source image is larger than the requested dimension, then a new copy
	 * will be returned at the requested dimension.
	 *
 	 * @param int $n Maximum width
	 * @param array $options Optional options array
	 * @return Pageimage
	 *
	 */
	public function maxHeight($n, array $options = array()) {
		$options['upscaling'] = false;
		if($this->height() > $n) return $this->height($n); 
		return $this;
	}

	/**
	 * Return an image no larger than the given width and height
	 * 
	 * @param int $width Max allowed width
	 * @param int $height Max allowed height
	 * @param array $options Optional options array
	 * @return Pageimage
	 * 
	 */
	public function maxSize($width, $height, $options = array()) {
		$w = $this->width();
		$h = $this->height();
		if($w >= $h) {
			if($w > $width && $h > $height) {
				return $this->size($width, $height, $options);
			} else {
				return $this->maxWidth($width, $options);
			}
		} else {
			if($w > $width && $h > $height) {
				return $this->size($width, $height, $options);
			} else {
				return $this->maxHeight($height, $options);
			}
		}
	}

	/**
	 * Get all size variations of this Pageimage 
	 *
	 * This is useful after a delete of an image (for example). This method can be used to track down all the child files that also need to be deleted. 
	 *
	 * @param array $options One or more options of: 
	 * 	- info (bool): when true, method returns variation info arrays rather than Pageimage objects
	 * 	- width (int): only variations with given width will be returned
	 * 	- height (int): only variations with given height will be returned
	 * 	- width>= (int): only variations with width greater than or equal to given will be returned
	 * 	- height>= (int): only variations with height greater than or equal to given will be returned
	 * 	- width<= (int): only variations with width less than or equal to given will be returned
	 * 	- height<= (int): only variations with height less than or equal to given will be returned
	 * 	- suffix (string): only variations having the given suffix will be returned
	 * @return Pageimages|array Returns Pageimages array of Pageimage instances. Only returns regular array if $options[info] is true.
	 *
	 */
	public function getVariations(array $options = array()) {

		if(!is_null($this->variations)) return $this->variations; 

		$variations = new Pageimages($this->pagefiles->page); 
		$dir = new DirectoryIterator($this->pagefiles->path); 
		$infos = array();

		foreach($dir as $file) {
			if($file->isDir() || $file->isDot()) continue; 			
			$info = $this->isVariation($file->getFilename());
			if(!$info) continue; 
			$allow = true;
			if(count($options)) foreach($options as $option => $value) {
				switch($option) {
					case 'width': $allow = $info['width'] == $value; break;
					case 'width>=': $allow = $info['width'] >= $value; break;
					case 'width<=': $allow = $info['width'] <= $value; break;
					case 'height': $allow = $info['height'] == $value; break;
					case 'height>=': $allow = $info['height'] >= $value; break;
					case 'height<=': $allow = $info['height'] <= $value; break;
					case 'suffix': $allow = in_array($value, $info['suffix']); break;
				}
			}
			if(!$allow) continue; 
			if(!empty($options['info'])) {
				$infos[$file->getBasename()] = $info;
			} else {
				$pageimage = clone $this; 
				$pathname = $file->getPathname();
				if(DIRECTORY_SEPARATOR != '/') $pathname = str_replace(DIRECTORY_SEPARATOR, '/', $pathname);
				$pageimage->setFilename($pathname); 
				$pageimage->setOriginal($this); 
				$variations->add($pageimage); 
			}
		}

		if(!empty($options['info'])) {
			return $infos;
		} else {
			$this->variations = $variations;
			return $variations; 
		}
	}

	/**
	 * Rebuilds variations of this image
	 * 
	 * By default, this excludes crops and images with suffixes, but can be 
	 * overridden with the $mode and $suffix arguments. 
	 * 
	 * Mode 0 is the only truly safe mode, as in any other mode there are possibilities that the resulting
	 * rebuild of the variation may not be exactly what was intended. The issues with other modules primarily
	 * arise when the suffix means something about the technical details of the produced image, or when 
	 * rebuilding variations that include crops from an original image that has changed dimensions or crops. 
	 * 
	 * @param int $mode Can be any one of the following (default is 0): 
	 * 	- 0: rebuild only non-suffix, non-crop variations, and those w/suffix specified in $suffix argument. ($suffix is INCLUSION list)
	 * 	- 1: rebuild all non-suffix variations, and those w/suffix specifed in $suffix argument. ($suffix is INCLUSION list)
	 * 	- 2: rebuild all variations, except those with suffix specified in $suffix argument. ($suffix is EXCLUSION list)
	 * 	- 3: rebuild only variations specified in the $suffix argument. ($suffix is ONLY-INCLUSION list)
	 * @param array $suffix Optional argument to specify suffixes to include or exclude (according to $mode). 
	 * @param array $options Options for ImageSizer (see $options argument for size() method). 
	 * @return array of format array(
	 * 	'rebuilt' => array(file1, file2, file3, etc.), 
	 * 	'skipped' => array(file1, file2, file3, etc.), 
	 * 	'errors' => array(file1, file2, file3, etc.); 
	 * 	);
	 * 
	 */
	public function ___rebuildVariations($mode = 0, array $suffix = array(), array $options = array()) {
		
		$skipped = array();
		$rebuilt = array();
		$errors = array();
		$reasons = array();
		$options['forceNew'] = true; 
		
		foreach($this->getVariations(array('info' => true)) as $info) {
			
			$o = $options;
			unset($o['cropping']); 
			$skip = false; 
			$name = $info['name'];
			
			if($info['crop'] && !$mode) {
				// skip crops when mode is 0
				$reasons[$name] = "$name: Crop is $info[crop] and mode is 0";
				$skip = true; 
				
			} else if(count($info['suffix'])) {
				// check suffixes 
				foreach($info['suffix'] as $k => $s) {
					if($s === 'hidpi') {
						// allow hidpi to passthru
						$o['hidpi'] = true;
					} else if($s == 'is') {
						// this is a known core suffix that we allow
					} else if(strpos($s, 'cropx') === 0) {
						// skip cropx suffix (already known from $info[crop])
						unset($info['suffix'][$k]);
						continue; 
					} else if(strpos($s, 'pid') === 0 && preg_match('/^pid\d+$/', $s)) {
						// allow pid123 to pass through 
					} else if(in_array($s, $suffix)) {
						// suffix is one provided in $suffix argument
						if($mode == 2) {
							// mode 2 where $suffix is an exclusion list
							$skip = true;
							$reasons[$name] = "$name: Suffix '$s' is one provided in exclusion list (mode==true)";
						} else {
							// allowed suffix
						}
					} else {
						// image has suffix not specified in $suffix argument
						if($mode == 0 || $mode == 1 || $mode == 3) {
							$skip = true;
							$reasons[$name] = "$name: Image has suffix '$s' not provided in allowed list: " . implode(', ', $suffix);
						}
					}
				}
			}
			
			if($skip) {
				$skipped[] = $name; 
				continue; 
			}
		
			// rebuild the variation
			$o['forceNew'] = true; 
			$o['suffix'] = $info['suffix'];
			if(is_file($info['path'])) unlink($info['path']); 
			
			if($info['crop'] && preg_match('/^x(\d+)y(\d+)$/', $info['crop'], $matches)) {
				$cropX = (int) $matches[1];
				$cropY = (int) $matches[2];
				$variation = $this->crop($cropX, $cropY, $info['width'], $info['height'], $options); 
			} else {
				if($info['crop']) $options['cropping'] = $info['crop'];
				$variation = $this->size($info['width'], $info['height'], $options);
			}
			
			if($variation) {
				if($variation->name != $name) rename($variation->filename(), $info['path']); 
				$rebuilt[] = $name;
			} else {
				$errors[] = $name;
			}
		}
		
		return array(
			'rebuilt' => $rebuilt, 
			'skipped' => $skipped, 
			'reasons' => $reasons, 
			'errors' => $errors
		); 
	}

	/**
	 * Given a filename, return array of info if this is a variation for this instance's file, false if not
	 *
	 * Returned array includes the following indexes: 
	 * - original: Original basename
	 * - url: URL to image
	 * - path: Full path + filename to image
	 * - width: Specified width
	 * - height: Specified height
	 * - crop: Cropping info string or blank if none
	 * - suffix: array of suffixes
	 * - suffixAll: (!) contains all suffixes including among parent variations
	 * - parent: (!) variation info array of direct parent variation file
	 * 
	 * Items above identified with (!) are only present if variation is based on another variation, and thus
	 * has a parent variation image between it and the original. 
	 * 
	 * @param string $basename Filename to check
	 * @param bool $allowSelf When true, it will return variation info even if same as current Pageimage.
	 * @return bool|array Returns false if not a variation or array of it is
	 *
	 */
	public function ___isVariation($basename, $allowSelf = false) {

		static $level = 0;
		$variationName = basename($basename);
		$originalName = $this->basename; 
	
		// that that everything from the beginning up to the first period is exactly the same
		// otherwise, they are different source files
		$test1 = substr($variationName, 0, strpos($variationName, '.'));
		$test2 = substr($originalName, 0, strpos($originalName, '.')); 
		if($test1 !== $test2) return false;

		// remove extension from originalName
		$originalName = basename($originalName, "." . $this->ext());  
		
		// if originalName is already a variation filename, remove the variation info from it.
		// reduce to original name, i.e. all info after (and including) a period
		if(strpos($originalName, '.') && preg_match('/^([^.]+)\.(?:\d+x\d+|-[_a-z0-9]+)/', $originalName, $matches)) {
			$originalName = $matches[1];
		}
	
		// if file is the same as the original, then it's not a variation
		if(!$allowSelf && $variationName == $this->basename) return false;
		
		// if file doesn't start with the original name then it's not a variation
		if(strpos($variationName, $originalName) !== 0) return false; 
	
		// get down to the meat and the base
		// meat is the part of the filename containing variation info like dimensions, crop, suffix, etc.
		// base is the part before that, which may include parent meat
		$pos = strrpos($variationName, '.'); // get extension
		$ext = substr($variationName, $pos); 
		$base = substr($variationName, 0, $pos); // get without extension
		$rpos = strrpos($base, '.'); // get last data chunk after dot
		if($rpos !== false) {
			$meat = substr($base, $rpos+1) . $ext; // the part of the filename we're interested in
			$base = substr($base, 0, $rpos); // the rest of the filename
			$parent = "$base." . $this->ext();
		} else {
			$meat = $variationName;
			$parent = null;
		}

		// identify parent and any parent suffixes
		$suffixAll = array();
		while(($pos = strrpos($base, '.')) !== false) {
			$part = substr($base, $pos+1); 
			// if(is_null($parent)) {
				// $parent = substr($base, 0, $pos) . $ext;
				//$parent = $originalName . "." . $part . $ext;
			// }
			$base = substr($base, 0, $pos); 
			while(($rpos = strrpos($part, '-')) !== false) {
				$suffixAll[] = substr($part, $rpos+1); 
				$part = substr($part, 0, $rpos); 
			}
		}

		// variation name with size dimensions and optionally suffix
		$re1 = '/^'  . 
			'(\d+)x(\d+)' .					// 50x50	
			'([pd]\d+x\d+|[a-z]{1,2})?' . 	// nw or p30x40 or d30x40
			'(?:-([-_a-z0-9]+))?' . 		// -suffix1 or -suffix1-suffix2, etc.
			'\.' . $this->ext() . 			// .jpg
			'$/';
	
		// variation name with suffix only
		$re2 = '/^' . 						
			'-([-_a-z0-9]+)' . 				// suffix1 or suffix1-suffix2, etc. 
			'(?:\.' . 						// optional extras for dimensions/crop, starts with period
				'(\d+)x(\d+)' .				// optional 50x50	
				'([pd]\d+x\d+|[a-z]{1,2})?' . // nw or p30x40 or d30x40
			')?' .
			'\.' . $this->ext() . 			// .jpg
			'$/'; 

		// if regex does not match, return false
		if(preg_match($re1, $meat, $matches)) {
			// this is a variation with dimensions, return array of info
			$info = array(
				'name' => $basename, 
				'url' => $this->pagefiles->url . $basename, 
				'path' => $this->pagefiles->path . $basename, 
				'original' => $originalName . '.' . $this->ext(),
				'width' => (int) $matches[1],
				'height' => (int) $matches[2],
				'crop' => (isset($matches[3]) ? $matches[3] : ''),
				'suffix' => (isset($matches[4]) ? explode('-', $matches[4]) : array()),
				);

		} else if(preg_match($re2, $meat, $matches)) {
		
			// this is a variation only with suffix
			$info = array(
				'name' => $basename, 
				'url' => $this->pagefiles->url . $basename,
				'path' => $this->pagefiles->path . $basename, 
				'original' => $originalName . '.' . $this->ext(),
				'width' => (isset($matches[2]) ? (int) $matches[2] : 0),
				'height' => (isset($matches[3]) ? (int) $matches[3] : 0),
				'crop' => (isset($matches[4]) ? $matches[4] : ''),
				'suffix' => explode('-', $matches[1]),
				);
			
		} else {
			return false; 
		}
		
		$info['hidpiWidth'] = $this->hidpiWidth(null, $info['width']);
		$info['hidpiHeight'] = $this->hidpiWidth(null, $info['height']); 
	
		if(empty($info['crop'])) {
			// attempt to extract crop info from suffix
			foreach($info['suffix'] as $key => $suffix) {
				if(strpos($suffix, 'cropx') === 0) {
					$info['crop'] = ltrim($suffix, 'crop'); // i.e. x123y456
				}
			}
		}

		if($parent) {
			// suffixAll includes all parent suffix in addition to current suffix
			if(!$level) $info['suffixAll'] = array_unique(array_merge($info['suffix'], $suffixAll)); 
			// parent property is set with more variation info, when available
			$level++;
			$info['parentName'] = $parent; 
			$info['parent'] = $this->isVariation($parent);
			$level--;
		}

		if(!$this->original) {
			$this->original = $this->pagefiles->get($info['original']);
		}
		
		return $info;
	}

	/**
	 * Delete all the alternate sizes associated with this Pageimage
	 *
	 * @return $this
	 *
	 */
	public function removeVariations() {

		$variations = $this->getVariations();	

		foreach($variations as $variation) {
			if(is_file($variation->filename)) unlink($variation->filename); 			
		}

		$this->variations = null;
		return $this;	
	}

	/**
	 * Identify this Pageimage as a variation, by setting the Pageimage it was resized from
	 *
	 * @param Pageimage $image
	 * @return $this
	 *
	 */
	public function setOriginal(Pageimage $image) {
		$this->original = $image; 
		return $this; 
	}

	/**
	 * If this is a variation, return the original, otherwise return null
	 *
	 * @return Pageimage|null
	 *
	 */
	public function getOriginal() {
		if($this->original) return $this->original; 
		$info = $this->isVariation($this->basename(), true); 
		if($info === false) return null;
		$this->original = $this->pagefiles->get($info['original']); 
		return $this->original;
	}

	/**
	 * Delete the physical file(s) associated with this Pagefile
	 *
	 */
	public function unlink() {
		parent::unlink();
		$this->removeVariations();
		return $this; 
	}

	/**
	 * Copy this Pageimage and any of it's variations to another path
	 *
	 * @param string $path
	 * @return bool True if successful
	 *
	 */
	public function copyToPath($path) {
		if(parent::copyToPath($path)) {
			foreach($this->getVariations() as $variation) {
				if(is_file($variation->filename)) {
					copy($variation->filename, $path . $variation->basename); 
					if($this->config->chmodFile) chmod($path . $variation->basename, octdec($this->config->chmodFile));
				}
			}
			return true; 
		}
		return false; 
	}

	/**
	 * Install this Pagefile
	 *
	 * Implies copying the file to the correct location (if not already there), and populating it's name
	 *
	 * @param string $filename Full path and filename of file to install
	 * @throws WireException
	 *
	 */
	protected function ___install($filename) {
		parent::___install($filename); 
		if(!$this->width()) {
			parent::unlink();
			throw new WireException($this->_('Unable to install invalid image')); 
		}
	}

}

