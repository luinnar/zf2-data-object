<?php

namespace DataObject\Helper;

use \Zend\Db\Sql\Sql,
	\Zend\Db\Sql\Select,
	\DataObject\Factory;

trait RawFactoryTrait
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

		return $oDbRes->toArray();
	}

	/**
	 * Fetch pairs from raw database data
	 *
	 * @param	Select	$oSelect		select object
	 * @return	array
	 */
	protected function fetchRawPairs(Select $oSelect)
	{
		$aResult = [];
		foreach($this->fetchRawAll($oSelect) as $aRow)
		{
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
		$aResult = [];
		foreach($this->fetchRawAll($oSelect) as $aRow)
		{
			$aResult[] = array_shift($aRow);
		}

		return $aResult;
	}
}
