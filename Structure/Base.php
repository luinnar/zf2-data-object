<?php

namespace DataObject\Structure;

use DataObject\Factory;

/**
 * DataObject structure
 *
 * @author Mateusz Juściński
 */
trait Base
{
	/**
	 * DataObject fields
	 *
	 * @var array
	 */
	private $aData;

	/**
	 * Parent factory
	 *
	 * @var Factory
	 */
	private $_oFactory;

	/**
	 * Primary Key value
	 *
	 * @var mixed
	 */
	private $_mPrimaryValue;

	/**
	 * The list of modified fields
	 *
	 * @var array
	 */
	private $_aModifiedFields = [];

	/**
	 * Is object removed
	 *
	 * @var bool
	 */
	private $_bDeleted = false;

	/**
	 * Whether the object is modified
	 *
	 * @var bool
	 */
	private $_bModified = false;

	/**
	 * Constructor, sets necessary data for the data object
	 * Warning: In child class use this constructor!
	 *
	 * @param	array	$aData		model data
	 * @param	mixed	$mPrimary	primary key value
	 * @param	Factory	$oFactory	DataObject factory
	 */
	public function __construct(array $aData, $mPrimary, Factory $oFactory)
	{
		$this->aData			= $aData;
		$this->_mPrimaryValue	= $mPrimary;
		$this->_oFactory		= $oFactory;
	}

	/**
	 * Do not allow serialization of a database object
	 */
	public function __sleep()
	{
		$aResult = [];

		// analizuję pola klasy i odrzucam pole bazy danych
		foreach((new \ReflectionClass($this))->getProperties() as $oProperty)
		{
			if($oProperty->getName() != '_oFactory')
			{
				$aResult[] = $oProperty->getName();
			}
		}

		return $aResult;
	}

	/**
	 * Loads database object after usnserialize
	 */
	public function __wakeup()
	{
		$sFactory =	get_class() .'Factory';
		$this->_oFactory = new $sFactory;
	}

	/**
	 * Delete object from DB
	 *
	 * @throws	Exception
	 * @return	void
	 */
	public function delete()
	{
		$this->_oFactory->delete($this->_mPrimaryValue);
		$this->_bDeleted = true;
	}

	/**
	 * Save object to DB
	 *
	 * @throws	Exception
	 * @return	void
	 */
	public function save()
	{
		// is deleted
		if($this->_bDeleted)
		{
			throw new Exception('Object is already deleted, you cannot save it.');
		}
		// check whether any data has been modified
		elseif(!$this->isModified())
		{
			// WARNING RETURN
			return;
		}

		$this->_oFactory->update($this->_mPrimaryValue, $this->_aModifiedFields);
		$this->clearModified();
	}

	/**
	 * Clears information about data modifications
	 *
	 * @return	void
	 */
	protected function clearModified()
	{
		$this->_aModifiedFields = [];
		$this->_bModified = false;
	}

	/**
	 * Returns true, if object was modified
	 *
	 * @param	string	$sField		optional field name
	 * @return 	bool
	 */
	protected function isModified($sField = null)
	{
		return isset($sFieldName) ? isset($ths->_aModifiedFields[$sField]) : $this->_bModified;
	}

	/**
	 * Set new DB field value
	 *
	 * @param	string	$sField		DB field name
	 * @param	string	$mValue		new field value
	 * @return	void
	 */
	protected function setDataValue($sField, $mValue)
	{
		$this->aData[$sField] = $mValue;
		$this->_aModifiedFields[$sField] = $mValue;
		$this->_bModified = true;
	}
}
