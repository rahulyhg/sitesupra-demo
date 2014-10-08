<?php

namespace Supra\Package\Cms\Pages\Layout\Processor;

use Supra\Template\Parser\Twig\Twig;
use Twig_Loader_Filesystem;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Request\PageRequest;
use Supra\Controller\Pages\Twig\TwigSupraPageGlobal;
use Supra\Response\HttpResponse;
use Supra\Response\ResponseContext;
use Supra\Package\Cms\Pages\Layout\Theme\ThemeInterface;

/**
 * Twig layout processor
 */
class TwigProcessor extends HtmlProcessor
{

	protected $loader;
	
	/**
	 *
	 * @var ThemeInterface
	 * 
	 */
	protected $theme;

	/**
	 * @param ThemeInterface $theme 
	 */
	public function setTheme(ThemeInterface $theme)
	{
		$this->theme = $theme;
	}

	/**
	 * @return ThemeInterface
	 */
	public function getTheme()
	{
		return $this->theme;
	}

	/**
	 * @param string $layoutSrc
	 * @return string
	 */
	protected function getContent($layoutSrc)
	{
		$theme = $this->getTheme();

		if ( ! empty($theme)) {
			$this->setLayoutDir($theme->getRootDir());
		}

		$twig = ObjectRepository::getTemplateParser($this);
		/* @var $twig Twig */

		if ( ! $twig instanceof Twig) {
			throw new \RuntimeException("Twig layout processor expects twig template parser");
		}

		$helper = new TwigSupraPageGlobal();
		ObjectRepository::setCallerParent($helper, $this);
		$helper->setRequest($this->request);

		if ( ! empty($theme)) {
			$helper->setTheme($theme);
		}
		
		if ($this->response instanceof HttpResponse) {
			$helper->setResponseContext($this->response->getContext());
		} else {
			$helper->setResponseContext(new ResponseContext());
		}

		$twig->addGlobal('supra', $helper);

		if ($this->loader === null) {
			
			$paths = array();
			
			$currentLoader = $twig->getLoader();
			if ($currentLoader instanceof Twig_Loader_Filesystem) {
				$paths = $currentLoader->getPaths();
			}
			
			$paths[] = $this->layoutDir;
			
			$this->loader = new Twig_Loader_Filesystem($paths);	
		}
		
		$contents = $twig->parseTemplate($layoutSrc, array(), $this->loader);

		return $contents;
	}
}