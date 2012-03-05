<?php

namespace Supra\Response;

use Supra\Response\TwigResponse;

/**
 * Keeps local context changes and proxies them to the global context
 */
class ResponseContextLocalProxy extends ResponseContext
{
	/**
	 * @var ResponseContext
	 */
	private $localContext;
	
	/**
	 * Bind global context data to be used, creates separate local context for
	 * keeping local changes
	 * @param ResponseContext $mainContext
	 */
	public function __construct(ResponseContext $mainContext)
	{
		$this->contextData = &$mainContext->contextData;
		$this->layoutSnippetResponses = &$mainContext->layoutSnippetResponses;
		
		$this->localContext = new ResponseContext();
	}
	
	/**
	 * Serializing local context only
	 * @return array
	 */
	public function __sleep()
	{
		$fieldNames = array('localContext');
		
		return $fieldNames;
	}
	
	/**
	 * Makes sure local context is created
	 */
	public function __wakeup()
	{
		if ( ! $this->localContext instanceof ResponseContext) {
			$this->localContext = new ResponseContext();
		}
	}
	
	/**
	 * @param string $key
	 * @param mixed $value
	 */
	public function setValue($key, $value)
	{
		$this->localContext->setValue($key, $value);
		parent::setValue($key, $value);
	}
	
	/**
	 * @param string $key
	 * @param TwigResponse | string $value 
	 */
	public function addToLayoutSnippet($key, $snippet)
	{
		$this->localContext->addToLayoutSnippet($key, $snippet);
		parent::addToLayoutSnippet($key, $snippet);
	}
	
	/**
	 * Flushes all local data to common context after wakeup
	 * @param ResponseContext $mainContext
	 */
	public function flushToContext(ResponseContext $mainContext)
	{
		$this->localContext->flushToContext($mainContext);
	}
	
}