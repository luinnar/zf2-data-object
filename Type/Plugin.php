<?php

namespace DataObject\Type;

use DataObject\Factory;
use DataObject\Pluginable;

/**
 * DataObject plugin
 *
 * @license		New BSD License
 * @author		Mateusz Juściński
 */
abstract class Plugin extends DataObject
{
	/**
	 * Plugin-owner object
	 *
	 * @var DataObject
	 */
	protected $oOwner;

	/**
	 * Constructor, sets necessary data for the data object
	 * Warning: In child class use this constructor!
	 *
	 * @param	array		$aData		model data
	 * @param	mixed		$mPrimary	primary key value
	 * @param	Pluginable	$oOwner		plugin-owner object
	 * @param	Factory		$oFactory	DataObject factory
	 */
	public function __construct(array $aData, $mPrimary, Pluginable $oOwner, Factory $oFactory)
	{
		$this->initStructure($aData, $mPrimary, $oFactory);
		$this->oOwner = $oOwner;
	}

	/**
	 * Returns owner object
	 *
	 * @return	Pluginable
	 */
	public function getOwner()
	{
		return $this->oOwner;
	}
}
