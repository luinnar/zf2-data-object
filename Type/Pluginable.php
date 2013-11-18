<?php

namespace DataObject\Type;

use DataObject\DataObject;
use DataObject\Exception;

/**
 * DataObject with plugins
 *
 * @license		New BSD License
 * @author		Mateusz Juściński
 */
trait Pluginable
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
	 * @param	string	$sName	plugin name
	 * @throws	Exception
	 * @return	Plugin
	 */
	public function getPlugin($sName)
	{
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
}
