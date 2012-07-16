<?php

namespace Supra\FileStorage\ImageProcessor;

use Supra\FileStorage\Exception\ImageProcessorException;

/**
 * Image cropper
 *
 */
class ImageCropper extends ImageProcessor
{

	protected $left = null;
	protected $top = null;
	protected $right = null;
	protected $bottom = null;
	protected $width = null;
	protected $height = null;

	/**
	 * Set left offset
	 *
	 * @param int $value
	 * @return ImageCropper 
	 */
	public function setLeft($value)
	{
		$this->left = intval($value);
		return $this;
	}

	/**
	 * Set top offset
	 *
	 * @param int $value
	 * @return ImageCropper 
	 */
	public function setTop($value)
	{
		$this->top = intval($value);
		return $this;
	}

	/**
	 * Set right offset (positive from left, negative from right). Will override 
	 * crop width
	 *
	 * @param int $value
	 * @return ImageCropper 
	 */
	public function setRight($value)
	{
		$this->right = intval($value);
		$this->width = null;
		return $this;
	}

	/**
	 * Set bottom offset (positive from top, negative from bottom). Will 
	 * override crop height
	 *
	 * @param int $value
	 * @return ImageCropper 
	 */
	public function setBottom($value)
	{
		$this->bottom = intval($value);
		$this->height = null;
		return $this;
	}

	/**
	 * Set crop width. Will override right offset
	 *
	 * @param int $value
	 * @return ImageCropper 
	 */
	public function setWidth($value)
	{
		$this->width = intval($value);
		$this->right = null;
		return $this;
	}

	/**
	 * Set crop height. Will override bottom offset
	 *
	 * @param int $value
	 * @return ImageCropper 
	 */
	public function setHeight($value)
	{
		$this->height = intval($value);
		$this->bottom = null;
		return $this;
	}
	
	/**
	 * Process
	 * 
	 */
	public function process()
	{

		// parameter check
		if (empty($this->sourceFilename)) {
			throw new ImageProcessorException('Source image is not set');
		}
		if (empty($this->targetFilename)) {
			throw new ImageProcessorException('Target (output) file is not set');
		}

		// check if there are enough dimensions
		if (($this->left === null) 
			|| ($this->top === null) 
			|| (($this->right === null) && empty($this->width))
			|| (($this->bottom === null) && empty($this->height))
		) {
			throw new ImageProcessorException('Crop dimensions are incomplete to process image');
		}

		$image = $this->createImageFromFile($this->sourceFilename);
		$imageInfo = $this->getImageInfo($this->sourceFilename);

		// check if left and top are in range
		if (($this->left < 0) || ($this->left >= $imageInfo->getWidth())) {
			throw new ImageProcessorException('Left offset is out of borders');
		}
		if (($this->top < 0 ) || ($this->top >= $imageInfo->getHeight())) {
			throw new ImageProcessorException('Top offset is out of borders');
		}

		// invert right and bottom if required
		if ($this->right < 0) {
			$this->right = $imageInfo->getWidth() + $this->right - 1;
		}
		if ($this->bottom < 0) {
			$this->bottom = $imageInfo->getHeight() + $this->bottom - 1;
		}

		// check if right and bottom are in range
		if (($this->right < 0) || ($this->right >= $imageInfo->getWidth())) {
			throw new ImageProcessorException('Right offset is out of borders');
		}
		if (($this->bottom < 0) || ($this->bottom >= $imageInfo->getHeight())) {
			throw new ImageProcessorException('Bottom offset is out borders');
		}

		//convert right/bottom to width/height or check width/height
		if ($this->right !== null) {
			$this->width = $this->right - $this->left + 1;
		} else if ($this->width > ($imageInfo->getWidth() - $this->left)) {
			throw new ImageProcessorException('Crop width exceeds maximum (out of borders)');
		}
		if ($this->bottom !== null) {
			$this->height = $this->bottom - $this->top + 1;
		} else if ($this->height > ($imageInfo->getHeight() - $this->top)) {
			throw new ImageProcessorException('Crop height exceeds maximum (out of borders)');
		}

		// create output image
		$croppedImage = imagecreatetruecolor($this->width, $this->height);
		
		// check if transparecy requires special treatment
		if ($imageInfo->getType() == IMAGETYPE_PNG) {
			$this->preserveTransparency($image, $croppedImage, $imageInfo->getType());
		}

		//copy cropped
		imagecopy($croppedImage, $image, 
				0, 0, 
				$this->left, $this->top, 
				$this->width, $this->height);

		// save to file
		$this->saveImageToFile($croppedImage, $this->targetFilename, 
				$imageInfo->getType(), $this->targetQuality, $imageInfo->getMime());

		chmod($this->targetFilename, SITESUPRA_FILE_PERMISSION_MODE);
	}

	/**
	 * Process
	 * 
	 */
	public function crop()
	{
		$this->process();
	}

	/**
	 * Reset this instance
	 * 
	 */
	public function reset()
	{
		parent::reset();
		$this->left = null;
		$this->top = null;
		$this->right = null;
		$this->bottom = null;
		$this->width = null;
		$this->height = null;
	}
}
