<?php

namespace DataObject;

/**
 * Abstract class using to create models
 *
 * @license		New BSD License
 * @author		Mateusz Juściński
 */
abstract class DataObject
{
	/**
	 * Delete object from DB
	 *
	 * @throws	Exception
	 * @return	void
	 */
	abstract public function delete();

	/**
	 * Save object to DB
	 *
	 * @throws	Exception
	 * @return	void
	 */
	abstract public function save();

	/**
	 * Clears information about data modifications
	 *
	 * @return	void
	 */
	abstract protected function clearModified();

	/**
	 * Sets structure information
	 *
	 * @param	array	$aData		model data
	 * @param	mixed	$mPrimary	primary key value
	 * @param	Factory	$oFactory	DataObject factory
	 * @return	void
	 */
	abstract protected function initStructure(array $aData, $mPrimary, Factory $oFactory);

	/**
	 * Returns true, if object was modified
	 *
	 * @param	string	$sField		optional field name
	 * @return 	bool
	 */
	abstract protected function isModified($sField = null);

	/**
	 * Set new DB field value
	 *
	 * @param	string	$sField		DB field name
	 * @param	string	$mValue		new field value
	 * @return	void
	 */
	abstract protected function setDataValue($sField, $mValue);
}
