<?php

namespace DataObject;

/**
 * Abstract class using to create models
 *
 * @copyright	Copyright (c) 2011, Autentika Sp. z o.o.
 * @license		New BSD License
 * @author		Mateusz Juściński, Mateusz Kohut, Daniel Kózka
 */
abstract class DataObject
{
	/**
	 * DataObject fields
	 *
	 * @var array
	 */
	protected $aData;

	/**
	 * Parent factory
	 *
	 * @var Factory
	 */
	protected $oFactory;

	/**
	 * Primary Key value
	 *
	 * @var mixed
	 */
	private $mPrimaryValue;

	/**
	 * The list of modified fields
	 *
	 * @var array
	 */
	private $aModifiedFields = array();

	/**
	 * Is object removed
	 *
	 * @var bool
	 */
	private $bDeleted = false;

	/**
	 * Whether the object is modified
	 *
	 * @var bool
	 */
	private $bModified = false;

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
		$this->aData		 = $aData;
		$this->mPrimaryValue = $mPrimary;
		$this->oFactory		 = $oFactory;
	}

	/**
	 * Do not allow serialization of a database object
	 */
	public function __sleep()
	{
		$aResult = array();

		// analizuję pola klasy i odrzucam pole bazy danych
		foreach((new \ReflectionClass($this))->getProperties() as $oProperty)
		{
			if($oProperty->getName() != 'oFactory')
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
		$this->oFactory = new $sFactory;
	}

	/**
	 * Delete object from DB
	 *
	 * @throws	Exception
	 * @return	void
	 */
	public function delete()
	{
		$this->oFactory->delete($this->mPrimaryValue);
		$this->bDeleted = true;
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
		if($this->bDeleted)
		{
			throw new Exception('Object is already deleted, you cannot save it.');
		}
		// check whether any data has been modified
		elseif(!$this->bModified)
		{
			// WARNING RETURN
			return;
		}

		$this->oFactory->update($this->mPrimaryValue, $this->aModifiedFields);
		$this->clearModified();
	}

	/**
	 * Clears information about data modifications
	 *
	 * @return	void
	 */
	final protected function clearModified()
	{
		$this->aModifiedFields = array();
		$this->bModified = false;
	}

	/**
	 * Returns true, if object was modified
	 *
	 * @param	string	$sField		optional field name
	 * @return 	bool
	 */
	final protected function isModified($sField = null)
	{
		return isset($sFieldName) ? isset($ths->aModifiedFields[$sField]) : $this->bModified;
	}

	/**
	 * Set new DB field value
	 *
	 * @param	string	$sField		DB field name
	 * @param	string	$mValue		new field value
	 * @return	void
	 */
	final protected function setDataValue($sField, $mValue)
	{
		$this->aModifiedFields[$sField] = $mValue;
		$this->bModified = true;
	}
}
