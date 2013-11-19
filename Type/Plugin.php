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
		parent::save();
		$this->_bSaved = true;
// @todo
//		$this->_mPrimaryValue = $this->getOwner()->getPrimaryField();
	}

	/**
	 * Plugin initialisation
	 *
	 * @param	DataObject	$oOwner
	 * @param	mixed		$mPrimary
	 */
	private function initPlugin(DataObject $oOwner, $mPrimary)
	{
		if(!empty($this->oOwner))
		{
			return;
		}

		$this->oOwner	= $oOwner;
		$this->_bSaved	= !empty($mPrimary);
	}
}
