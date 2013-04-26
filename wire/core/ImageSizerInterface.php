<?php

/**
 * Image Sizer Interface
 *
 */ 

interface ImageSizerInterface {

	/**
	 * Set the file to be manipulated
	 *
	 * Note: Some code used in this method is adapted from code found in comments at php.net for the GD functions
	 *
	 * @param int $width
	 * @param int $height
	 * @return bool True if the resize was successful
	 *
	 */
	public function __construct($filename);

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
	public function resize($targetWidth, $targetHeight = 0);

	/**
	 * Return the image width in pixels.
	 *
	 */
	public function getWidth();

	/**
	 * Return the image height in pixels.
	 *
	 */
	public function getHeight();

	/**
	 * Turn on/off upscaling
	 *
	 */
	public function setUpscaling($upscaling = true);

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
	public function setCropping($cropping = true);

	/**
 	 * Set the image quality 1-100, where 100 is highest quality
	 *
	 * @param int $n
	 * @return this
	 *
	 */
	public function setQuality($n);

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
	public function setOptions(array $options);

	/**
	 * Return an array of the current options
	 *
	 * @return array
	 *
	 */
	public function getOptions();

	/**
	 * Was the image modified?
	 *
	 * @return booe 
	 *	
	 */
	public function isModified();

}
