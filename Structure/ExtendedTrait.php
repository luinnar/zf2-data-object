<?php

namespace DataObject\Structure;

/**
 * DataObject structure
 *
 * @author Mateusz Juściński
 */
trait ExtendedTrait
{
	/**
	 * Set new DB field value
	 *
	 * @param	string	$sField		DB field name
	 * @param	string	$mValue		new field value
	 * @param	string	$sTable		optional table name
	 * @return	void
	 */
	protected function setDataValue($sField, $mValue, $sTable = null)
	{
		if(empty($sTable))
		{
			parent::setDataValue($sField, $mValue);
		}
		else
		{
			$this->aData['_'. $sTable][$sField] = $mValue;
			$this->aModifiedFields['_'. $sTable][$sField] = $mValue;
		}
	}
}
