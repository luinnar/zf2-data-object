<?php

namespace DataObject\Type;

use DataObject\DataObject,
	DataObject\Exception,
	DataObject\Helper\MultitableTrait;

/**
 * DataObject factory with plugins
 *
 * @license		New BSD License
 * @author		Mateusz Juściński
 */
trait PluginableFactoryTrait
{
	use MultitableTrait;

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
	 * Returns plugin for given owner instance
	 *
	 * @param	string		$sPlugin	plugin name
	 * @param	DataObject	$oModel		owner model instance
	 * @return	DataObject
	 */
	public function pluginGet($sPlugin, DataObject $oModel)
	{
		$sPlugin = ltrim($sPlugin, '_');
		$bLoaded = $this->pluginIsLoaded($sPlugin);

		// loads missing plugin
		if(!$bLoaded)
		{
			$this->pluginLoad($sPlugin);
		}

		$oFactory	= $this->aCurrentPlugins[$sPlugin];
		$oPlugin	= $oFactory->getPluginByOwner($oModel);

		// uloads plugin to preserve plugin state
		if(!$bLoaded)
		{
			$this->pluginUnload($sPlugin);
		}

		return $oPlugin;
	}

	/**
	 * Is plugin loaded?
	 *
	 * @param	string	$sName	plugin name
	 * @return	bool
	 */
	public function pluginIsLoaded($sName)
	{
		return !empty($this->aCurrentPlugins[$sName]);
	}

	/**
	 * Loads plugin
	 *
	 * @param	string|array	$mName	plugin name or array with plugin names
	 * @throws	Exception
	 * @return	PluginableFactoryTrait
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

		return $this;
	}

	/**
	 * Unloads plugin. If $mName is null then unload all plugins.
	 *
	 * @param	string|array|null	$mName	null, plugin name or array with names
	 * @throws	Exception
	 * @return	PluginableFactoryTrait
	 */
	public function pluginUnload($mName = null)
	{
		if(null === $mName)
		{
			$this->aCurrentPlugins = [];
			return $this;
		}

		if(!is_array($mName))
		{
			$mName = [$mName];
		}

		foreach($mName as $sPluginName)
		{
			unset($this->aCurrentPlugins[$sPluginName]);
		}

		return $this;
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
			$sPlugin = ltrim($sPlugin, '_');

			if(!isset(self::$aPlugins[$sPlugin]))
			{
				continue;
			}

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
