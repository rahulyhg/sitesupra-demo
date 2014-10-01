<?php

namespace Supra\Package\Cms\Entity\Theme;

use Supra\Package\Cms\Entity\Abstraction\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Supra\Controller\Pages\Entity\Theme\Parameter\ThemeParameterAbstraction;

/**
 * @Entity 
 * @ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 * @Table(uniqueConstraints={@UniqueConstraint(name="unique_name_in_theme_idx", columns={"name", "theme_id"})}))
 */
class ThemeParameterSet extends Entity
{

	const TYPE_PRESET = 'preset';
	const TYPE_USER = 'user';

	/**
	 * @ManyToOne(targetEntity="Theme", inversedBy="parameterSets", fetch="EAGER")
	 * @JoinColumn(name="theme_id", referencedColumnName="id")
	 * @var Theme
	 */
	protected $theme;

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $name;

	/**
	 * @Column(type="string", nullable=true)
	 * @var string
	 */
	protected $title;

	/**
	 * @Column(type="boolean")
	 * @var boolean
	 */
	protected $locked = false;

	/**
	 * @Column(type="string")
	 * @var boolean
	 */
	protected $type = self::TYPE_PRESET;
	
	/**
	 * @Column(type="string", nullable=true)
	 * @var string
	 */
	protected $lessParameterValuesHash;
	
	/**
	 * Hash map of known web fonts
	 * @FIXME: duplicate code
	 * @FIXME: JS contains hardcoded values in google-fonts.js
	 *  
	 * @var array
	 */
	private $webSafeFonts = array(
			'Arial, Helvetica, sans-serif' => true,
			'"Times New Roman", Times, serif' => true,
			'Georgia, serif' => true,
			'"Palatino Linotype", "Book Antiqua", Palatino, serif' => true,
			'Impact, Charcoal, sans-serif' => true,
			'"Lucida Sans Unicode", "Lucida Grande", sans-serif' => true,
			'Tahoma, Geneva, sans-serif' => true,
			'"Trebuchet MS", Helvetica, sans-serif' => true,
			'Verdana, Geneva, sans-serif' => true,
	);

	/**
	 * @OneToMany(targetEntity="ThemeParameterValue", mappedBy="set", cascade={"all"}, orphanRemoval=true, indexBy="parameterName")
	 * @var ArrayCollection
	 */
	protected $values;

	public function __construct()
	{
		parent::__construct();

		$this->values = new ArrayCollection();
	}

	/**
	 * @return Theme
	 */
	public function getTheme()
	{
		return $this->theme;
	}

	/**
	 * @param Theme $theme 
	 */
	public function setTheme(Theme $theme = null)
	{
		$this->theme = $theme;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param string $name 
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * @param string $title 
	 */
	public function setTitle($title)
	{
		$this->title = $title;
	}

	/**
	 * @return boolean
	 */
	public function isLocked()
	{
		return $this->locked;
	}

	/**
	 * @param boolean $locked 
	 */
	public function setLocked($locked)
	{
		$this->locked = $locked;
	}

	/**
	 * @return array
	 */
	public function getValues()
	{
		return $this->values;
	}

	/**
	 * @param array $values 
	 */
	public function setValues($values)
	{
		$this->values = $values;
	}

	/**
	 * @param ThemeParameterValue $value 
	 */
	public function addValue(ThemeParameterValue $value)
	{
		if ($this->values->containsKey($value->getParameterName())) {
			$this->removeValue($this->values->get($value->getParameterName()));
		}

		$value->setSet($this);
		$this->values[$value->getParameterName()] = $value;
	}

	/**
	 * @param ThemeParameterValue $value 
	 */
	public function removeValue(ThemeParameterValue $value)
	{
		$value->setSet(null);

		$this->values->removeElement($value);
	}

	/**
	 * @param \Supra\Controller\Pages\Entity\Theme\Parameter\ThemeParameterAbstraction $parameter
	 * @return ThemeParameterValue
	 */
	public function addNewValueForParameter(Parameter\ThemeParameterAbstraction $parameter)
	{
		$value = $parameter->makeNewParameterValue();

		$this->addValue($value);

		return $value;
	}

	/**
	 * @return array
	 */
	public function getOutputValues()
	{
		$theme = $this->getTheme();
		$parameters = $theme->getParameters();

		$outputValues = array();

		foreach ($parameters as $parameter) {
			/* @var $parameter ThemeParameterAbstraction */

			$parameterValue = $this->getParameterValueForParameter($parameter);

			if ($parameterValue === null) {
				continue;
			}
			
			$outputValues[$parameter->getName()] = $parameter->getOuptutValueFromParameterValue($parameterValue);
		}

		return $outputValues;
	}
	
	/**
	 * Returns used family names of "FontParameter" parameters
	 * @return array
	 */
	public function collectGoogleFontFamilies()
	{		
		$parameters = $this->getTheme()
				->getParameters();
		
		$fontFamilies = array();
		foreach ($parameters as $parameter) {
			if ($parameter instanceof Parameter\FontParameter) {
				
				$parameterValue = $this->getParameterValueForParameter($parameter);
				
				if ($parameterValue === null) {
					continue;
				}
			
				$value = $parameter->getOuptutValueFromParameterValue($parameterValue);
				
				if (isset($value['family']) && ! empty($value['family'])) {
					
					// @FIXME
					// this step helps to skip standart (and hardcoded in JS) fonts
					// which are not recognized by Google
					// must be implemented in another way?
					$fontFamily = $value['family'];
					if ( ! isset($this->webSafeFonts[$fontFamily])) {
						$fontFamilies[] = $fontFamily;
					}
				}
			}
		}
		
		return $fontFamilies;
	}

	/**
	 * @param ThemeParameterValue $parameter
	 * @return mixed
	 */
	protected function getParameterValueForParameter(ThemeParameterAbstraction $parameter)
	{
		$parameterName = $parameter->getName();

		$parameterValue = $this->values->get($parameterName);

		return $parameterValue;
	}

	/**
	 * @return array
	 */
	public function getOutputValuesForLess()
	{
		$theme = $this->getTheme();
		$parameters = $theme->getParameters();
		
		foreach ($parameters as $parameter) {
			/* @var $parameter ThemeParameterAbstraction */

			$configuration = $parameter->getConfiguration();
			
			$parameterName = $parameter->getName();

			if ($configuration === null) {
				\Log::info("Missing configuration for stored parameter {$parameterName}");
				continue;
			}
			
			if ($parameter->hasValueForLess()) {
				
				$parameterValue = $this->getParameterValueForParameter($parameter);
				$outputValueForLess = $parameter->getLessOuptutValueFromParameterValue($parameterValue);
				
				if ($outputValueForLess === null) {
					continue;
				}
				
				if (is_array($outputValueForLess)) {

					foreach ($outputValueForLess as $key => $value) {
						$flatOutputValuesForLess[$parameterName . '_' . $key] = $value;
					}
				} else {

					$flatOutputValuesForLess[$parameterName] = $outputValueForLess;
				}
			}
		}

		return $flatOutputValuesForLess;
	}

	/**
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @param string $type
	 */
	public function setType($type)
	{
		$this->type = $type;
	}
	
	/**
	 */
	public function recalculateParameterValuesHash()
	{
		$outputValues = $this->getOutputValuesForLess();
		$valueString = serialize($outputValues);
		
		$this->lessParameterValuesHash = crc32($valueString);
	}
	
	/**
	 * Returns pre-generated parameter collection values hash
	 * When parameter values changes this parameter must be re-calculated
	 * 
	 * @return string
	 */
	public function getLessParameterValuesHash()
	{
		return $this->lessParameterValuesHash;
	}

}