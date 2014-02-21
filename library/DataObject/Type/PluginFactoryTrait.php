<?php

namespace DataObject\Type;

use DataObject\DataObject,
	DataObject\Exception,
	DataObject\Helper\MultitableTrait,
	Zend\Db\Sql\Select;

/**
 * DataObject plugin factory
 *
 * @license		New BSD License
 * @author		Mateusz Juściński
 */
trait PluginFactoryTrait
{
	use MultitableTrait;

	/**
	 * Adds join to select query
	 *
	 * @param	Select	$oSelect	base query
	 * @param	mixed	$mOption	optional parameters
	 * @return	Select
	 */
	abstract public function addToSelect(Select $oSelect, $mOption = null);

	/**
	 * Create plugin from raw data
	 *
	 * @param	array	$aData	single row from DB
	 * @return	DataObject
	 */
	abstract public function getPluginObject(array $aData, DataObject $oOwner);

	/**
	 * Create single plugin for owner object
	 *
	 * @param	DataObject	$oOwner		owner instance
	 * @throws	Exception
	 * @return	DataObject
	 */
	public function getPluginByOwner(DataObject $oOwner)
	{
		throw new Exception('Unsupported');
	}

	/**
	 * (non-PHPdoc)
	 * @see DataObject\Factory::delete()
	 */
	public function delete(DataObject $oModel)
	{
		if($oModel->isSaved())
		{
			parent::delete($oModel);
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see DataObject\Factory::update()
	 */
	public function update(DataObject $oModel)
	{
		if($oModel->isSaved())
		{
			parent::update($oModel);
		}
		else
		{
			$this->create($oModel);
		}
	}

	/**
	 * Saves new plugin instance into database
	 *
	 * @param	DataObcjet	$oModel		instance to save
	 * @return	void
	 */
	abstract protected function create(DataObject $oModel);

	/**
	 * (non-PHPdoc)
	 * @see DataObject\Factory::createObject()
	 */
	protected function createObject(array $aRow, $mOption = null)
	{
		throw new Exception('Cannot create plugin with no parent object');
	}
}
