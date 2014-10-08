<?php

namespace Supra\Package\Cms\Pages\Layout\Processor;

use Supra\Response\ResponseInterface;
use Supra\Request\RequestInterface;

/**
 * Layout processor interface
 */
interface ProcessorInterface
{
	/**
	 * Process the layout
	 * @param ResponseInterface $response
	 * @param array $placeResponses
	 * @param string $layoutSrc
	 */
	public function process(ResponseInterface $response, array $placeResponses, $layoutSrc);

	/**
	 * Return list of place names inside the layout
	 * @param string $layoutSrc
	 * @return array
	 */
	public function getPlaces($layoutSrc);
	
	/**
	 * Returns the list of place holder groups
	 * 
	 * @param string $layoutSrc
	 * @return array
	 */
	public function getPlaceGroups($layoutSrc);
	
	/**
	 * Set request object to use
	 * @param RequestInterface $request
	 */
	public function setRequest(RequestInterface $request);
}