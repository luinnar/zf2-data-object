<?php

namespace DataObject\Type;

use DataObject\DataObject,
	DataObject\Exception;

/**
 * DataObject with plugins
 *
 * @license		New BSD License
 * @author		Mateusz Juściński
 */
trait PluginableTrait
{
	/**
	 * Loaded plugins
	 *
	 * @var array
	 */
	protected $_aPlugins = [];

	/**
	 * Return loaded plugin
	 *
	 * @param	string	$sName			plugin name
	 * @param	bool	$bForceLoad		forces plugin load
	 * @throws	Exception
	 * @return	Plugin
	 */
	public function getPlugin($sName, $bForceLoad = false)
	{
		if($bForceLoad)
		{
			$this->loadPlugin(
				$this->getFactory()->pluginGet($sName, $this),
				$sName
			);
		}

		if(!isset($this->_aPlugins[$sName]))
		{
			throw new Exception('Plugin "'. $sName .'" is not loaded');
		}

		return $this->_aPlugins[$sName];
	}

	/**
	 * Loads plugin
	 *
	 * @param	Plugin	$oPlugin	plugin instance
	 * @param	string	$sName		plugin name
	 * @throws	Exception
	 * @return	void
	 */
	public function loadPlugin(DataObject $oPlugin, $sName)
	{
		if($oPlugin->getOwner() != $this)
		{
			throw new Exception('I\'m not the plugin owner');
		}

		$this->_aPlugins[$sName] = $oPlugin;
	}

	/**
	 * (non-PHPdoc)
	 * @see DataObject\DataObject::delete()
	 */
	public function delete()
	{
		foreach($this->_aPlugins as $oPlugin)
		{
			$oPlugin->delete();
		}

		parent::delete();
	}

	/**
	 * (non-PHPdoc)
	 * @see DataObject\DataObject::save()
	 */
	public function save()
	{
		foreach($this->_aPlugins as $oPlugin)
		{
			$oPlugin->save();
		}

		parent::save();
	}
}
