<?php

namespace DataObject;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Insert;
use Zend\Db\Sql\Select;
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
	 * Sets DB connection
	 * @param	Adapter		$oDb	DB connection
	 * @throws	Exception
	 * @return	void
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
	 * Primary Key definition
	 *
	 * @var array
	 */
	private $aPrimaryKey = null;

	/**
	 * DB table name
	 *
	 * @var string
	 */
	private $sTableName = null;

	/**
	 * Constructor, sets necessary data for the data object
	 * Warning: In child class use this constructor!
	 *
	 * @param	string	$sTableName		name of DB table connected with model
	 * @param	array	$aPrimaryKey	array with primay key fields
	 * @return	Factory
	 */
	public function __construct($sTableName, array $aPrimaryKey)
	{
		$this->sTableName	= $sTableName;
		$this->aPrimaryKey	= $aPrimaryKey;
	}

// Factory method

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

// additional methods

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
	final protected function getTableName()
	{
		return $this->sTableName;
	}

	/**
	 * Returns an array with describe Primary Key
	 *
	 * @return	array
	 */
	final protected function getPrimaryKey()
	{
		return $this->aPrimaryKey;
	}

	/**
	 * Returns a Select object
	 *
	 * @param	mixed	$mFields	fields to select
	 * @param	mixed	$mOption	additional options
	 * @return	\Zend\Db\Sql\Select
	 */
	protected function getSelect(array $aFields = array('*'), $mOption = null)
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
	 * @return string
	 */
	protected function getPrimaryWhere($mId)
	{
		$oWhere = new Where();

		if(count($this->aPrimaryKey) > 1)
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

				foreach($this->aPrimaryKey as $sField)
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
				$oWhere->in($this->aPrimaryKey[0], $mId);
			}
			else
			{
				$oWhere->equalTo($this->aPrimaryKey[0], $mId);
			}
		}

		return $oWhere;
	}

	/**
	 * Perform SQL insert query and returns last inserted ID
	 *
	 * @param	array	$aData	data to save
	 * @return	mixed
	 */
	final protected function insert(array &$aData)
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
}
