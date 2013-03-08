<?php

/**
 * ProcessWire Pageimage
 *
 * Represents a single image item attached to a page, typically via a FieldtypeImage field.
 * 
 * ProcessWire 2.x 
 * Copyright (C) 2010 by Ryan Cramer 
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://www.processwire.com
 * http://www.ryancramer.com
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
	 * Construct a new Pagefile
	 *
	 * @param Pagefiles $pagefiles 
	 * @param string $filename Full path and filename to this pagefile
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
		return $this->pagefiles->url . $this->basename; 	
	}

	/**
	 * Returns the disk path to the Pagefile
	 *
	 */
	public function filename() {
		return $this->pagefiles->path . $this->basename;
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
	 */
	public function get($key) {
		if($key == 'width') return $this->width();
		if($key == 'height') return $this->height();
		if($key == 'original') return $this->getOriginal();
		return parent::get($key); 
	}

	/**
	 * Gets the image information with PHP's getimagesize function and caches the result
	 *
	 */
	public function getImageInfo($reset = false) {

		if($reset) $checkImage = true; 
			else if($this->imageInfo['width']) $checkImage = false; 
			else $checkImage = true; 

		if($checkImage && ($info = @getimagesize($this->filename))) {
			$this->imageInfo['width'] = $info[0]; 
			$this->imageInfo['height'] = $info[1]; 
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
	 * @param array|string|int $options Array of options to override default behavior (quality=90, upscaling=true, cropping=center).
	 *	Or you may specify a string|bool with with 'cropping' value if you don't need to combine with other options.
	 *	Or you may specify an integer with 'quality' value if you don't need to combine with other options.
	 * 	Or you may specify a boolean with 'upscaling' value if you don't need to combine with other options.
	 * @return Pageimage
	 *
	 */
	public function size($width, $height, $options = array()) {

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
			} 
		}

		$defaultOptions = array(
			'upscaling' => true,
			'cropping' => true,
			'quality' => 90
			);

		$configOptions = wire('config')->imageSizerOptions; 
		if(!is_array($configOptions)) $configOptions = array();
		$options = array_merge($defaultOptions, $configOptions, $options); 

		$width = (int) $width;
		$height = (int) $height; 
		$crop = ImageSizer::croppingValueStr($options['cropping']); 	

		$basename = basename($this->basename(), "." . $this->ext()); 		// i.e. myfile
		$basename .= '.' . $width . 'x' . $height . $crop . "." . $this->ext();	// i.e. myfile.100x100.jpg or myfile.100x100nw.jpg
		$filename = $this->pagefiles->path() . $basename; 

		if(!is_file($filename)) {
			if(@copy($this->filename(), $filename)) {
				try { 
					$sizer = new ImageSizer($filename); 
					$sizer->setOptions($options);
					$sizer->resize($width, $height); 
				} catch(Exception $e) {
					$this->error($e->getMessage()); 
				}
				if($this->config->chmodFile) chmod($filename, octdec($this->config->chmodFile));
			}
		}

		$pageimage = clone $this; 
		$pageimage->setFilename($filename); 	
		$pageimage->setOriginal($this); 

		return $pageimage; 
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
	 * Get all size variations of this Pageimage as a Pageimages array of Pageimage objects.
	 *
	 * This is useful after a delete of an image (for example). This method can be used to track down all the child files that also need to be deleted. 
	 *
	 * @return Pageimages
	 *
	 */
	public function getVariations() {

		if(!is_null($this->variations)) return $this->variations; 

		$variations = new Pageimages($this->pagefiles->page); 
		$dir = new DirectoryIterator($this->pagefiles->path); 
		$basename = basename($this->basename, "." . $this->ext()); 

		foreach($dir as $file) {
			if($file->isDir() || $file->isDot()) continue; 			
			if(!$this->isVariation($file->getFilename())) continue; 
			$pageimage = clone $this; 
			$pathname = $file->getPathname();
			if(DIRECTORY_SEPARATOR != '/') $pathname = str_replace(DIRECTORY_SEPARATOR, '/', $pathname);
			$pageimage->setFilename($pathname); 
			$pageimage->setOriginal($this); 
			$variations->add($pageimage); 
		}

		$this->variations = $variations; 
		return $variations; 
	}

	/**
	 * Given a filename, return array of info if this is a variation for this instance's file, false if not
	 *
	 * Returned array includes the following indexes: 
	 * - original: Original basename
	 * - width: Specified width
	 * - height: Specified height
	 * - crop: Cropping info string or blank if none
	 * 
	 * @param string $basename Filename to check
	 * @return bool|array Returns false if not a variation or array of it is
	 *
	 */
	public function isVariation($basename) {

		$variationName = basename($basename);
		$originalName = basename($this->basename, "." . $this->ext());  // excludes extension

		// if originalName is already a variation filename, remove the variation info from it.
		// reduce to original name, i.e. all info after (and including) a period
		if(strpos($originalName, '.') && preg_match('/^([^.]+)\.\d+x\d+/', $originalName, $matches)) {
			$originalName = $matches[1];
		}

		$re = 	'/^'  . 
			$originalName . '\.' .		// myfile. 
			'(\d+)x(\d+)' .			// 50x50	
			'([pd]\d+x\d+|[a-z]{1,2})?' . 	// nw or p30x40 or d30x40
			'\.' . $this->ext() . 		// .jpg
			'$/';

		// if regex does not match, return false
		if(!preg_match($re, $variationName, $matches)) return false;

		// this is a variation, return array of info
		$info = array(
			'original' => $originalName . '.' . $this->ext(), 
			'width' => $matches[1],
			'height' => $matches[2], 
			'crop' => (isset($matches[3]) ? $matches[3] : '')
			);

		return $info;
	}

	/**
	 * Delete all the alternate sizes associated with this Pageimage
	 *
	 * @return this
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
	 * @return this
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
		$info = $this->isVariation($this->basename()); 
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
}

