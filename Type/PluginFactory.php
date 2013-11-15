<?php

namespace DataObject\Type;

use DataObject\Factory;
use Zend\Db\Sql\Select;

/**
 * DataObject plugin factory
 *
 * @license		New BSD License
 * @author		Mateusz Juściński
 */
abstract class PluginFactory extends Factory
{
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
	abstract public function getPluginObject(array $aData);

	/**
	 * Update or save plugin
	 *
	 * @param	mixed	$mId	primary key value
	 * @param	array	$aData	data to save
	 * @return	mixed
	 */
	public function update($mId, array $aData)
	{
		// object is not saved
		if(empty($mId))
		{
			$this->insert($aData);
		}
		else
		{
			$this->_update($mId, $aData);
		}
	}

	abstract private function _update($mId, array $aData);
}
