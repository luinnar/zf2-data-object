<?php

namespace DataObject\Type;

use DataObject\Factory;

/**
 * Base factory for models with translations
 *
 * @author Mateusz Juściński
 */
abstract class LanguageFactory extends Factory
{
	/**
	 * Global locale
	 *
	 * @var string|null
	 */
	private static $sDefaultLocale = null;

	/**
	 * Local locale setting
	 *
	 * @var string|null
	 */
	private $sLocale = null;

	/**
	 * Returns default locale setting
	 *
	 * @return	string|null
	 */
	public static function getDefaultLocale()
	{
		return self::$sDefaultLocale;
	}

	/**
	 * Sets default locale for all models
	 *
	 * @param	string	$sLocale
	 * @return	void
	 */
	public static function setDefaultLocale($sLocale)
	{
		self::$sDefaultLocale = $sLocale;
	}

	/**
	 * Returns current locale
	 *
	 * @return string|null
	 */
	public function getLocale()
	{
		if(empty($this->sLocale))
		{
			return self::getDefaultLocale();
		}

		return $this->sLocale;
	}

	/**
	 * Returns locale configuration status
	 *
	 * @return boolean
	 */
	public function isLocaleSet()
	{
		return ($this->getLocale() !== null);
	}

	/**
	 * Sets local language
	 *
	 * @param	string	$sLocale	locale code
	 * @return	void
	 */
	public function setLocale($sLocale)
	{
		$this->sLocale = $sLocale;
	}
}
