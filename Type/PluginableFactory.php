<?php

namespace DataObject\Type;

use DataObject\Exception;
use DataObject\Factory;
use Zend\Db\Sql\Select;

/**
 * DataObject factory with plugins
 *
 * @license		New BSD License
 * @author		Mateusz Juściński
 */
abstract class PluginableFactory extends Factory
{
	/**
	 * Plugins configuration
	 *
	 * @var array
	 */
	private static $aPlugins = [];

	/**
	 * Array with loaded plugins
	 *
	 * @var array
	 */
	private $aCurrentPlugins = [];

	/**
	 * (non-PHPdoc)
	 * @see DataObject\Factory::getSelect()
	 */
	protected function getSelect(array $aFields = ['*'], $mOption = null)
	{
		$oSelect = $this->_getSelect($aFields, $mOption);

		foreach($this->aCurrentPlugins as $oFactory)
		{
			$oSelect = $oFactory->addToSelect($oSelect, $mOption);
		}

		return $oSelect;
	}

	/**
	 * Loads plugin
	 *
	 * @param	string|array	$mName	plugin name or array with plugin names
	 * @throws	Exception
	 * @return	void
	 */
	public function pluginLoad($mName)
	{
		if(!is_array($mName))
		{
			$mName = [$mName];
		}

		foreach($mName as $sPluginName)
		{
			if(!isset(self::$aPlugins[$sPluginName]))
			{
				throw new Exception('Plugin "'. $sPluginName .'" doesnt exists in configuration');
			}

			$this->aCurrentPlugins[$sPluginName] = new self::$aPlugins[$sPluginName];
		}
	}

	/**
	 * Unloads plugin
	 *
	 * @param	string|array	$mName	plugin name or array with names
	 * @throws	Exception
	 * @return	void
	 */
	public function pluginUnload($mName)
	{
		if(!is_array($mName))
		{
			$mName = [$mName];
		}

		foreach($mName as $sPluginName)
		{
			unset($this->aCurrentPlugins[$sPluginName]);
		}
	}

	/**
	 * Loads plugin configuraction
	 *
	 * @param	array	$aPlugins	array with plugin configuration
	 * @return	void
	 */
	protected static function pluginsLoadConfig(array $aPlugins)
	{
		if(!empty(self::$aPlugins))
		{
			return;
		}

		self::$aPlugins = $aPlugins;
	}

	/**
	 * Structure select method
	 */
	abstract private function _getSelect(array $aFields = ['*'], $mOption = null);
}
