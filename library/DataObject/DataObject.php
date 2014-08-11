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
	 * DataObject fields
	 *
	 * @var array
	 */
	protected $aData;

	/**
	 * The list of modified fields
	 *
	 * @var array
	 */
	protected $aModifiedFields = [];

	/**
	 * Parent factory
	 *
	 * @var Factory
	 */
	private $oFactory;

	/**
	 * Primary Key value
	 *
	 * @var mixed
	 */
	private $mPrimaryValue;

	/**
	 * Is object removed
	 *
	 * @var bool
	 */
	private $bDeleted = false;

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
		$aResult = [];

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
	 * Loads factory instance after unserialize
	 */
	public function __wakeup()
	{
		$sFactory =	get_class($this) .'Factory';
		$this->oFactory = new $sFactory;

		if(	$this instanceof Type\LanguageInterface &&
			$this->oFactory instanceof Type\LanguageFactory)
		{
			$this->oFactory->setLocale($this->getLocale());
		}
	}

// model manipulation methods

	/**
	 * Delete object from DB
	 *
	 * @throws	Exception
	 * @return	void
	 */
	public function delete()
	{
		$this->oFactory->delete($this);
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
		elseif(!$this->hasModifiedFields())
		{
			// WARNING RETURN
			return;
		}

		$this->oFactory->update($this);
		$this->clearModified();
	}

// model information

	/**
	 * Returns modyfied fields
	 *
	 * @return	array
	 */
	public function getModifiedFields()
	{
		return $this->aModifiedFields;
	}

	/**
	 * Returns primary key value
	 *
	 * @return	mixed
	 */
	public function getPrimaryField()
	{
		return $this->mPrimaryValue;
	}

	/**
	 * Returns true, if object was modified
	 *
	 * @return 	bool
	 */
	public function hasModifiedFields()
	{
		return !empty($this->aModifiedFields);
	}

	/**
	 * Clears information about data modifications
	 *
	 * @return	void
	 */
	protected function clearModified()
	{
		$this->aModifiedFields = [];
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
		$this->aModifiedFields[$sField] = $mValue;

		return $this;
	}

// additional

	/**
	 * Returns factory instance
	 *
	 * @return	Factory
	 */
	protected function getFactory()
	{
		return $this->oFactory;
	}
}
