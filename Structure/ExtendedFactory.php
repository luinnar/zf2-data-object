<?php

namespace DataObject\Structure;

use DataObject\Exception;
use Zend\Db\Sql\Select;

trait MultiFactory
{
	// definiuję niezbędne metody
	use BaseFactory;

	/**
	 * Returns a Select object
	 *
	 * @param	mixed	$mFields	fields to select
	 * @param	mixed	$mOption	additional options
	 * @return	\Zend\Db\Sql\Select
	 */
	protected function getSelect(array $aFields = ['*'], $mOption = null)
	{
		if(!$this->structureIsLocked())
		{
			throw new Exception('DataObject structure wasn\'t locked');
		}

		// get all fields from all tables
		if($aFields == ['*'])
		{
			$aFields = $this->structureGet('tables');
		}

		// creates select statement
		$oSelect = (new Select())->from($aFields);

		foreach($this->structureGet('connections', []) as $sTable => $sConnect)
		{
			$oSelect->join($sTable, $aStruct['tables'][$sTable], null);
		}

		return $oSelect;
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
	 * Perform SQL insert query and returns last inserted ID
	 *
	 * @param	array	$aData	data to save
	 * @return	mixed
	 */
	final protected function insert(array &$aData)
	{
	}

// init methods

	/**
	 * Adds join definition
	 *
	 * @param	string	$sConnection	join definition
	 * @return	/DataObject/Factory
	 */
	protected function initConnection($sTable, $sConnection)
	{
		$aTables = $this->structureGet('tables', []);

		// no table structure information
		if(!isset($aTables[$sTable]))
		{
			throw new Exception('No information about table "'. $sTable .'" schema');
		}

		$aConn = $this->structureGet('connection', []);
		$aConn[$sTable] = $sConnection;

		$this->structureSet('connection', $aConn);

		return $this;
	}

	/**
	 * Adds table definition
	 *
	 * @param	string	$sName		table name
	 * @param	array	$aFields	fields names
	 * @param	array	$aPrimary	(optional) array with primary key
	 * @return	/DataObject/Factory
	 */
	protected function initTable($sName, array $aFields, array $aPrimary = [])
	{
		$aTables = $this->structureGet('tables', []);
		$aTables[$sName] = $aFields;

		$this->structureSet('tables', $aTables);

		if(!empty($aPrimary))
		{
			$this->structureSet('primary', $aPrimary);
		}

		return $this;
	}
}
