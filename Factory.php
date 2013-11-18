<?php

namespace DataObject;

use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Where;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Delete;
use Zend\Db\Sql\Insert;
use Zend\Db\Sql\Update;
use Zend\Db\ResultSet\ResultSet;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

/**
 * Abstract class using to create factory for models
 *
 * @license		New BSD License
 * @author		Mateusz Juściński, Mateusz Kohut, Daniel Kózka
 */
abstract class Factory implements ServiceLocatorAwareInterface
{
	use ServiceLocatorAwareTrait;

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
			return [];
		}

		$oSelect = $this->getSelect(['*'], $mOption)
						->where($this->getPrimaryWhere($aIds));

		if(!empty($aOrder))
		{
			$oSelect->order($aOrder);
		}

		return $this->createList($oSelect, $mOption);
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
		$oSelect = $this->getSelect(['*'], $mOption)->where($oWhere);

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
		$oSelect = $this->getSelect(['*'], $mOption)
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
	public function getPage($iPage, $iCount, array $aOrder = [], Where $oWhere = null,
							$mOption = null)
	{
		$oSelect = $this->getSelect(['*'], $mOption)
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
	 * @param	int		$iPage		page number
	 * @param	int		$iCount		number of results per page
	 * @param	array	$aOrder		array with order definition
	 * @param	Where	$oWhere		where string or Where object
	 * @param	mixed	$mOption	optional parameters sended to getPage()
	 * @return	\Zend\Paginator\Paginator
	 */
	public function getPaginator($iPage, $iCount, array $aOrder = [], Where $oWhere = null, $mOption = null)
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
	abstract protected function insert(array &$aData);

	/**
	 * Delete object with given ID
	 *
	 * @param	mixed	$mId	primary key value
	 * @throws	\RuntimeException
	 * @return	void
	 */
	abstract public function delete(DataObject $oModel);

	/**
	 * Updates
	 *
	 * @param	DataObject $oModel	DataObject to save
	 * @return	void
	 */
	abstract public function update(DataObject $oModel);

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
	 * Returns a Select object
	 *
	 * @param	mixed	$mFields	fields to select
	 * @param	mixed	$mOption	additional options
	 * @return	\Zend\Db\Sql\Select
	 */
	abstract protected function getSelect(array $aFields = ['*'], $mOption = null);

	/**
	 * Returns a Select object for Paginator Count
	 *
	 * @param	mixed	$mOption	additional options
	 * @return	Zend_Db_Select
	 */
	abstract protected function getCountSelect($mOption = null);

	/**
	 * Returns SQL WHERE string created for the specified key fields
	 *
	 * @param	mixed	$mId	primary key value
	 * @return	string
	 */
	abstract protected function getPrimaryWhere($mId);
}
