<?php

namespace DataObject\Type;

use DataObject\DataObject;
use DataObject\Exception;
use DataObject\Plugin;

/**
 * DataObject with plugins
 *
 * @license		New BSD License
 * @author		Mateusz Juściński
 */
abstract class Pluginable extends DataObject
{
	/**
	 * Loaded plugins
	 *
	 * @var array
	 */
	protected $aPlugins = [];

	/**
	 * Returns instance ID
	 *
	 * @return	mixed
	 */
	abstract public function getId();

	/**
	 * Return loaded plugin
	 *
	 * @param	string	$sName	plugin name
	 * @throws	Exception
	 * @return	Plugin
	 */
	public function getPlugin($sName)
	{
		if(!isset($this->aPlugins[$sName]))
		{
			throw new Exception('Plugin "'. $sName .'" is not loaded');
		}

		return $this->aPlugins[$sName];
	}

	/**
	 * Loads plugin
	 *
	 * @param	Plugin	$oPlugin	plugin instance
	 * @param	string	$sName		plugin name
	 * @throws	Exception
	 * @return	void
	 */
	public function loadPlugin(Plugin $oPlugin, $sName)
	{
		if($oPlugin->getOwner() != $this)
		{
			throw new Exception('I\'m not the plugin owner');
		}

		$this->aPlugins[$sName] = $oPlugin;
	}
}
