<?php

namespace DataObject\Helper;

use \Zend\Db\Sql\Sql,
	\Zend\Db\Sql\Select,
	\DataObject\Factory;

trait RawFactory
{
	/**
	 * Fetch raw database data
	 *
	 * @param	Select	$oSelect		select object
	 * @return	array
	 */
	protected function fetchRawAll(Select $oSelect)
	{
		$oDb	= self::getConnection();
		$oDbRes = $oDb->query(
					(new Sql($oDb))->getSqlStringForSqlObject($oSelect),
					$oDb::QUERY_MODE_EXECUTE
				);

		$aResult = [];
		foreach($oDbRes as $oRow)
		{
			$aResult[] = (array) $oRow; // ArrayObject => array
		}

		return $aResult;
	}

	/**
	 * Fetch pairs from raw database data
	 *
	 * @param	Select	$oSelect		select object
	 * @return	array
	 */
	protected function fetchRawPairs(Select $oSelect)
	{
		$oDb	= self::getConnection();
		$oDbRes = $oDb->query(
					(new Sql($oDb))->getSqlStringForSqlObject($oSelect),
					$oDb::QUERY_MODE_EXECUTE
				);

		$aResult = [];
		foreach($oDbRes as $oRow)
		{
			$aRow = (array) $oRow;
			$sKey = array_shift($aRow);
			$aResult[$sKey] = array_shift($aRow);
		}

		return $aResult;
	}

	/**
	 * Fetch first column from raw database data
	 *
	 * @param	Select	$oSelect		select object
	 * @return	array
	 */
	protected function fetchRawColumn(Select $oSelect)
	{
		$oDb	= self::getConnection();
		$oDbRes = $oDb->query(
					(new Sql($oDb))->getSqlStringForSqlObject($oSelect),
					$oDb::QUERY_MODE_EXECUTE
				);

		$aResult = [];
		foreach($oDbRes as $oRow)
		{
			$aRow = (array) $oRow;
			$aResult[] = array_shift($aRow);
		}

		return $aResult;
	}
}