<?php

namespace DataObject;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Delete;
use Zend\Db\Sql\Insert;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Update;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Where;
use Zend\Db\ResultSet\ResultSet;

/**
 * Abstract class using to create factory for models
 *
 * @copyright	Copyright (c) 2011, Autentika Sp. z o.o.
 * @license		New BSD License
 * @author		Mateusz Juściński, Mateusz Kohut, Daniel Kózka
 */
abstract class Factory
{
	/**
	 * Instance of db adapter
	 *
	 * @var Adapter
	 */
	static protected $oDb = null;

	/**
	 * Sets DB connection
	 *
	 * @param	Adapter		$oDb	DB connection
	 * @throws	Exception
	 * @return	void
	 */
	static public function setConnection(Adapter $oDb)
	{
		if(self::$oDb !== null)
		{
			throw new Exception('DataObject was initialised!');
		}

		self::$oDb = $oDb;
	}

	/**
	 * Gets DB connection
	 *
	 * @throws	Exception
	 * @return	Adapter
	 */
	static public function getConnection()
	{
		if(self::$oDb === null)
		{
			throw new Exception('DataObject is not initialised!');
		}

		return self::$oDb;
	}

	/**
	 * Locked = ready
	 *
	 * @var bool
	 */
	private $bStructureLock = false;

	/**
	 * Data structure information
	 *
	 * @var array
	 */
	private $aStructure = [];

// factory method

	/**
	 * Returns an array of objects with specified ID
	 *
	 * @param	array	$aIds		array with ID/IDs
	 * @param	array	$aOrder		array with order definition
	 * @param	mixed	$mOption	additional options for getSelect
	 * @return	array
	 */
	public function getFromIds(array $aIds, array $aOrder = array(), $mOption = null)
	{
		if(empty($aIds))
		{
			return array();
		}

		$oSelect = $this->getSelect(array('*'), $mOption)
						->where($this->getPrimaryWhere($aIds));

		if(!empty($aOrder))
		{
			$oSelect->order($aOrder);
		}

		return $this->createList(
					$oSelect->getSqlString(self::$oDb->getPlatform()),
					$mOption
				);
	}

	/**
	 * Returns an array of object that matches the given condition
	 *
	 * @param	Zend/Db/Sql/Where	$mWhere		where object
	 * @param	array				$aOrder		array with ordering options
	 * @param	mixed				$mOption	additional options
	 */
	public function getFromWhere(Where $oWhere, array $aOrder = array(), $mOption = null)
	{
		$oSelect = $this->getSelect(array('*'), $mOption)->where($oWhere);

		if(!empty($aOrder))
		{
			$oSelect->order($aOrder);
		}

		return $this->createList($oSelect, $mOption);
	}

	/**
	 * Returns a single object with the specified ID
	 *
	 * @param	mixed	$mId		specific key value or an array (<field> => <value>)
	 * @param	mixed	$mOption	optional parameters
	 * @return	Core_DataObject
	 */
	public function getOne($mId, $mOption = null)
	{
		$oSelect = $this->getSelect(array('*'), $mOption)
						->where($this->getPrimaryWhere($mId));

		$aResult = $this->createList($oSelect, $mOption);

		if(!isset($aResult[0]))
		{
			throw new Exception('The object with the specified ID does not exist');
		}

		return $aResult[0];
	}

	/**
	 * Returns one page for paginator
	 *
	 * @param	int								$iPage		page number
	 * @param	int								$iCount		number of results per page
	 * @param	array							$aOrder		array with order definition
	 * @param	string|Core_DataObject_Wheret	$oWhere		where string or Where object
	 * @param	mixed							$mOption	optional parameters
	 * @return	array
	 */
	public function getPage($iPage, $iCount, array $aOrder = array(), Where $oWhere = null,
							$mOption = null)
	{
		$oSelect = $this->getSelect(array('*'), $mOption)
						->limit((int) $iCount)
						->offset((int) ($iPage - 1) * $iCount);

		// adds order
		if(!empty($aOrder))
		{
			$oSelect->order($aOrder);
		}

		// adds where
		if($oWhere !== null)
		{
			$oSelect->where($oWhere);
		}

		return $this->createList($oSelect, $mOption);
	}

	/**
	 * Returns a paginator set on a particular page
	 *
	 * @param	int								$iPage		page number
	 * @param	int								$iCount		number of results per page
	 * @param	array							$aOrder		array with order definition
	 * @param	string|Core_DataObject_Wheret	$oWhere		where string or Where object
	 * @param	mixed							$mOption	optional parameters sended to getPage()
	 * @return	\Zend\Paginator\Paginator
	 */
	public function getPaginator($iPage, $iCount, array $aOrder = array(), Where $oWhere = null,
									$mOption = null)
	{
		$oSelect = $this->getCountSelect($mOption);

		if(!empty($oWhere))
		{
			$oSelect->where($oWhere);
		}

		$oInterface = new Paginator\Adapter($this, $oSelect, $mOption);

		if(!empty($aOrder))
		{
			$oInterface->setOrder($aOrder);
		}

		if(!empty($oWhere))
		{
			$oInterface->setWhere($oWhere);
		}

		return (new \Zend\Paginator\Paginator($oInterface))
						->setCurrentPageNumber($iPage)
						->setItemCountPerPage($iCount);
	}

// object manipulation methods

	/**
	 * Perform SQL insert query and returns last inserted ID
	 *
	 * @param	array	$aData	data to save
	 * @return	mixed
	 */
	protected function insert(array &$aData)
	{
		$oDb = self::getConnection();

		// przygotowuję zapytanie
		$oInsert = (new Insert())
					->into($this->getTableName())
					->values($aData);

		// uruchamiam zapytanie
		$oDb->query(
			(new Sql($oDb))->getSqlStringForSqlObject($oInsert),
			$oDb::QUERY_MODE_EXECUTE
		);

		return $oDb->getDriver()->getLastGeneratedValue();
	}

	/**
	 * Delete object with given ID
	 *
	 * @param	mixed	$mId	primary key value
	 * @throws	\RuntimeException
	 * @return	void
	 */
	public function delete($mId)
	{
		try
		{
			$oDelete = (new Delete($this->getTableName()))
								->where($this->getPrimaryWhere($mId));

			// wykonuje zapytanie
			$oDb = Factory::getConnection();
			$oDb->query(
				(new Sql($oDb))->getSqlStringForSqlObject($oDelete),
				$oDb::QUERY_MODE_EXECUTE
			);
		}
		catch(\Exception $e)
		{
			throw new Exception('Error while deleting data', null, $e);
		}
	}

	/**
	 * Updates
	 *
	 * @param	mixed	$mId	primary key value
	 * @param	array	$aData
	 * @return	void
	 */
	public function update($mId, array $aData)
	{
		try
		{
			$oUpdate = (new Update($this->getTableName()))
								->set($aData)
								->where($this->getPrimaryWhere($mId));

			// wykonuje zapytanie
			$oDb = Factory::getConnection();
			$oDb->query(
				(new Sql($oDb))->getSqlStringForSqlObject($oUpdate),
				$oDb::QUERY_MODE_EXECUTE
			);
		}
		catch(\Exception $e)
		{
			throw new Exception('Error while updating data', null, $e);
		}
	}

// additional methods

	/**
	 * Creates an array of objects from the results returned by the database
	 *
	 * @param	Select	$aDbResult	Zend/Db/Sql/Select object
	 * @param	mixed	$mOption	aditional options
	 * @return array
	 */
	protected function createList(Select $oSelect, $mOption = null)
	{
		$oDb	= self::getConnection();
		$oDbRes = $oDb->query(
					(new Sql($oDb))->getSqlStringForSqlObject($oSelect),
					$oDb::QUERY_MODE_EXECUTE
				);

		$aResult = array();

		foreach($oDbRes as $aRow)
		{
			$aResult[] = $this->createObject($aRow->getArrayCopy(), $mOption);
		}

		return $aResult;
	}

	/**
	 * Create object from DB row
	 *
	 * @param	array	$aRow	one row from database
	 * @param	mixed	$mOption	optional parameters
	 * @return	DataObject
	 */
	abstract protected function createObject(array $aRow, $mOption = null);

	/**
	 * Returns DB table name
	 *
	 * @return	string
	 */
	protected function getTableName()
	{
		return $this->structureGet('table');
	}

	/**
	 * Returns an array with describe Primary Key
	 *
	 * @return	array
	 */
	protected function getPrimaryKey()
	{
		return $this->structureGet('primary');
	}

	/**
	 * Returns a Select object
	 *
	 * @param	mixed	$mFields	fields to select
	 * @param	mixed	$mOption	additional options
	 * @return	\Zend\Db\Sql\Select
	 */
	protected function getSelect(array $aFields = ['*'], $mOption = null)
	{
		return (new Select())
					->from($this->getTableName())
					->columns($aFields);
	}

	/**
	 * Returns a Select object for Paginator Count
	 *
	 * @param	mixed	$mOption	additional options
	 * @return	Zend_Db_Select
	 */
	protected function getCountSelect($mOption = null)
	{
		return $this->getSelect(['count' => new Expression('COUNT(*)')], $mOption);
	}

	/**
	 * Returns SQL WHERE string created for the specified key fields
	 *
	 * @param	mixed	$mId	primary key value
	 * @return	string
	 */
	protected function getPrimaryWhere($mId)
	{
		$aPrimaryKey = $this->structureGet('primary');
		$oWhere		 = new Where();

		if(count($aPrimaryKey) > 1)
		{
			// single primary key
			if(!isset($mId[0]))
			{
				$mId = [$mId];
			}

			// many fields in key
			foreach($mId as $aPrimary)
			{
				$oWhere2 = new Where();

				foreach($aPrimaryKey as $sField)
				{
					if(!isset($aPrimary[$sField]))
					{
						throw new Exception('No value for key part: ' . $sField);
					}

					if(is_array($aPrimary[$sField]))
					{
						$oWhere2->in($sField, $aPrimary[$sField]);
					}
					else
					{
						$oWhere2->equalTo($sField, $aPrimary[$sField]);
					}
				}

				$oWhere->orPredicate($oWhere2);
			}
		}
		else
		{
			if(is_array($mId))
			{
				$oWhere->in($aPrimaryKey[0], $mId);
			}
			else
			{
				$oWhere->equalTo($aPrimaryKey[0], $mId);
			}
		}

		return $oWhere;
	}

// init methods

	/**
	 * Add table to structure and lock it
	 *
	 * @param	string	$sName		table name
	 * @param	array	$aFields	fields
	 * @param	array	$aPrimary	primary key definition
	 * @return	void
	 */
	protected function initTable($sName, array $aFields, $aPrimary)
	{
		$this->structureSet([
			'table'		=> $sName,
			'fields'	=> $aFields,
			'primary'	=> $aPrimary
		]);
		$this->structureLock();

		return $this;
	}

// structure methods

	/**
	 * Retrives info about structure
	 *
	 * @param	string	$sField		(optional) field name
	 * @param	mixed	$mDefault	(optional) default value for unset fields
	 * @return	mixed
	 */
	final protected function structureGet($sField = null, $mDefault = null)
	{
		if(empty($sField))
		{
			return $this->aStructure;
		}
		elseif(!array_key_exists($sField, $this->aStructure))
		{
			return $mDefault;
		}

		return $this->aStructure[$sField];
	}

	/**
	 * Is structure locked
	 *
	 * @return	bool
	 */
	final protected function structureIsLocked()
	{
		return $this->bStructureLock;
	}

	/**
	 * Locks structure data
	 *
	 * @return	void
	 */
	final protected function structureLock()
	{
		$this->bStructureLock = true;
	}

	/**
	 * Sets information about DataObject structure
	 *
	 * @param	string|array	$mField		field name or array with structure data
	 * @param	mixed			$mData		(optional) field value
	 * @throws	/DataObject/Exception
	 * @return	void
	 */
	final protected function structureSet($mField, $mData = null)
	{
		if($this->structureIsLocked())
		{
			throw new Exception('DataObject structure is locked');
		}

		if(is_array($mField))
		{
			$this->aStructure = $mField;

			return;
		}

		$this->aStructure[$mField] = $mData;
	}
}
