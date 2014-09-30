<?php

namespace Supra\Package\Framework\Twig;

use Supra\Controller\FrontController;
use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;

class FrameworkExtension extends \Twig_Extension implements ContainerAware
{
	/**
	 * @var ContainerInterface
	 */
	protected $container;

	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}

	public function getFunctions()
	{
		return array(
			new \Twig_SimpleFunction('supra_path', array($this, 'getSupraPath')),
			new \Twig_SimpleFunction('controller', array($this, 'renderController'), array('is_safe' => array('html')))
		);
	}

	public function getFilters()
	{
		return array(
			new \Twig_SimpleFilter('dateintl', array($this, 'filterLocalizedDatePattern'))
		);
	}

	public function filterLocalizedDatePattern($date, $pattern, $locale = null)
	{
		if (!class_exists('IntlDateFormatter')) {
			throw new \RuntimeException('The intl extension is needed to use intl-based filters.');
		}

		$formatter = \IntlDateFormatter::create(
			$locale !== null ? $locale : \Locale::getDefault(),
			\IntlDateFormatter::NONE,
			\IntlDateFormatter::NONE,
			date_default_timezone_get()
		);

		$formatter->setPattern($pattern);

		if (!$date instanceof \DateTime) {
			if (\ctype_digit((string) $date)) {
				$date = new \DateTime('@'.$date);
				$date->setTimezone(new \DateTimeZone(date_default_timezone_get()));
			} else {
				$date = new \DateTime($date);
			}
		}

		return $formatter->format($date->getTimestamp());
	}

	public function getSupraPath($name, $params = array())
	{
		return $this->container->getRouter()->generate($name, $params);
	}

	public function renderController($name)
	{
		//@todo: parameters support
		//@todo: less ugly controller resolver, route this through kernel
		//@todo: whatever, rewrite this completely
		$front = FrontController::getInstance();
		$controller = $front->parseControllerName($name);
		$action = $controller['action'].'Action';
		$controller = $controller['controller'];

		$controllerObject = new $controller();
		$controllerObject->setContainer($this->container);

		return call_user_func(array($controllerObject, $action))->getContent();
	}

	/**
	 * Returns the name of the extension.
	 *
	 * @return string The extension name
	 */
	public function getName()
	{
		return 'framework';
	}

}