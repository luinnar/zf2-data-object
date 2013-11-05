<?php

namespace DataObject;

use Zend\Db\Sql\Sql;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Delete;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Update;
use Zend\Db\Sql\Where;

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
	 * Instance of db adapter
	 *
	 * @var Zend\Db\Adapter\Adapter
	 */
	protected $oDb;

	/**
	 * Primary Key definition
	 *
	 * @var array
	 */
	private $aPrimaryValue = array();

	/**
	 * The list of modified fields
	 *
	 * @var array
	 */
	private $aModifiedFields = array();

	/**
	 * Name of DB table
	 *
	 * @var string
	 */
	private $sTableName;

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
	 * @param	string	$sTableName		name of DB table connected with model
	 * @param	array	$aPrimaryKey	array with prmiary key description (<field name> => <field value>)
	 * @return	DataObject
	 */
	public function __construct($sTableName, array $aPrimaryKey)
	{
		$this->doInit($sTableName, $aPrimaryKey);
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
			if($oProperty->getName() != 'oDb')
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
		$this->oDb = Factory::getConnection();
	}

	/**
	 * Delete object from DB
	 *
	 * @return void
	 */
	public function delete()
	{
		$oDelete = (new Delete($this->getTableName()))
							->where($this->getPrimaryWhere());

		// wykonuje zapytanie
		$oDb = Factory::getConnection();
		$oDb->query(
			(new Sql($oDb))->getSqlStringForSqlObject($oDelete),
			$oDb::QUERY_MODE_EXECUTE
		);

		$this->bDeleted = true;
	}

	/**
	 * Save object to DB
	 *
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
		if($this->bModified)
		{
			$oUpdate = (new Update($this->getTableName()))
								->set($this->aModifiedFields)
								->where($this->getPrimaryWhere());

			// wykonuje zapytanie
			$oDb = Factory::getConnection();
			$oDb->query(
				(new Sql($oDb))->getSqlStringForSqlObject($oUpdate),
				$oDb::QUERY_MODE_EXECUTE
			);

			$this->clearModified();
		}
	}

	/**
	 * Initialise data object
	 *
	 * @param	string	$sTableName		name of DB table connected with model
	 * @param	array	$aPrimaryKey	array with prmiary key description (<field name> => <field value>)
	 */
	final protected function doInit($sTableName, array $aPrimaryKey)
	{
		if(!empty($this->oDb))
		{
			return;
		}

		$this->oDb				= Factory::getConnection();
		$this->sTableName		= $sTableName;
		$this->aPrimaryValue	= $aPrimaryKey;
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
	 * Returns DB table name
	 *
	 * @return	string
	 */
	protected function getTableName()
	{
		return $this->sTableName;
	}

	/**
	 * Returns where statement made from primary key
	 *
	 * @return \Zend\Db\Sql\Where
	 */
	protected function getPrimaryWhere()
	{
		$oWhere = new Where();

		foreach($this->aPrimaryValue as $sField => $mValue)
		{
			$oWhere->equalTo($sField, $mValue);
		}

		return $oWhere;
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
