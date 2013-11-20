<?php

namespace DataObject\Type;

use DataObject\DataObject;
use DataObject\Exception;
use DataObject\Helper\Multitable;

/**
 * DataObject factory with plugins
 *
 * @license		New BSD License
 * @author		Mateusz Juściński
 */
trait PluginableFactory
{
	use Multitable;

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

			$this->aCurrentPlugins[$sPluginName] = new self::$aPlugins[$sPluginName]($this);
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

// DataObject factory methods

	/**
	 * (non-PHPdoc)
	 * @see DataObject\Factory::getSelect()
	 */
	protected function getSelect(array $aFields = ['*'], $mOption = null)
	{
		$oSelect = parent::getSelect($aFields, $mOption);

		foreach($this->aCurrentPlugins as $oFactory)
		{
			$oSelect = $oFactory->addToSelect($oSelect, $mOption);
		}

		return $oSelect;
	}

// plugin factory methods

	/**
	 * Loads plugins into given model
	 *
	 * @param	Pluginable	$oModel		pluginable model instance
	 * @param	array		$aData		plugins data
	 * @return	Pluginable
	 */
	protected function loadInto(DataObject $oModel, array $aData)
	{
		foreach($aData as $sPlugin => $aFields)
		{
			if(empty($this->aCurrentPlugins[$sPlugin]))
			{
				throw new Exception('Plugin "'. $sPlugin .'" is not loaded');
			}

			$oFactory = $this->aCurrentPlugins[$sPlugin];

			$oModel->loadPlugin(
				$oFactory->getPluginObject($aFields, $oModel),
				$sPlugin
			);
		}

		return $oModel;
	}

	/**
	 * Loads plugin configuraction
	 *
	 * @param	array	$aPlugins	array with plugin configuration
	 * @return	void
	 */
	protected static function initPlugins(array $aPlugins)
	{
		if(!empty(self::$aPlugins))
		{
			return;
		}

		self::$aPlugins = $aPlugins;
	}
}
