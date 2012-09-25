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
	 * @param int $width
	 * @param int $height
	 * @param array $options Array of options to override default behavior (quality=90, upscaling=true, cropping=true)
	 * @return Pageimage
	 *
	 */
	public function size($width, $height, array $options = array()) {

		$defaultOptions = array(
			'upscaling' => true,
			'cropping' => true,
			'quality' => 90
			);

		$width = (int) $width;
		$height = (int) $height; 

		$basename = basename($this->basename(), "." . $this->ext()); 		// i.e. myfile
		$basename .= '.' . $width . 'x' . $height . "." . $this->ext();	// i.e. myfile.100x100.jpg
		$filename = $this->pagefiles->path() . $basename; 

		if(!is_file($filename)) {
			if(@copy($this->filename(), $filename)) {
				$configOptions = wire('config')->imageSizerOptions; 
				if(!is_array($configOptions)) $configOptions = array();
				$options = array_merge($defaultOptions, $configOptions, $options); 
				$sizer = new ImageSizer($filename); 
				$sizer->setOptions($options);
				$sizer->resize($width, $height); 
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
	 * @param array $options Optional options (see size function)
	 * @return int|Pageimage
	 *
	 */
	public function width($n = 0, array $options = array()) {
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
	 * @param array $options Optional options (see size function)
	 * @return int|Pageimage
	 *
	 */
	public function height($n = 0, array $options = array()) {
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
			if(!preg_match('/^'  . $basename . '\.\d+x\d+\.' . $this->ext() . '$/', $file->getFilename())) continue; 
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
		if(!preg_match('/^(.+\.)\d+x\d+\.' . $this->ext() . '$/', $this->basename(), $matches)) return null;
		$basename = $matches[1] . $this->ext();
		$this->original = $this->pagefiles->get($basename); 
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

