<?php

namespace DataObject\Type;

use DataObject\Factory;
use DataObject\DataObject;

/**
 * DataObject plugin
 *
 * @license		New BSD License
 * @author		Mateusz Juściński
 */
trait Plugin
{
	/**
	 * Plugin-owner object
	 *
	 * @var DataObject
	 */
	protected $oOwner;

	/**
	 * Id plugin saved
	 *
	 * @var bool
	 */
	private $_bSaved;

	/**
	 * Constructor, sets necessary data for the data object
	 * Warning: In child class use this constructor!
	 *
	 * @param	array		$aData		model data
	 * @param	mixed		$mPrimary	primary key value
	 * @param	Pluginable	$oOwner		plugin-owner object
	 * @param	Factory		$oFactory	DataObject factory
	 */
	public function __construct(array $aData, $mPrimary, DataObject $oOwner, Factory $oFactory)
	{
		$this->initStructure($aData, $mPrimary, $oFactory);
		$this->oOwner	= $oOwner;
		$this->_bSaved	= !empty($mPrimary);
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

	/**
	 * Is plugin saved
	 *
	 * @return	bool
	 */
	public function isSaved()
	{
		return $this->_bSaved;
	}

	/**
	 * (non-PHPdoc)
	 * @see DataObject\DataObject::save()
	 */
	public function save()
	{
		$this->_save();
		$this->_bSaved = true;
		$this->_mPrimaryValue = $this->getOwner()->getPrimaryField();
	}

	/**
	 * (non-PHPdoc)
	 * @see DataObject\DataObject::save()
	 */
	abstract protected function _save();
}
