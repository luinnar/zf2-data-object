<?php

namespace DataObject\Type;

use	DataObject\Exception,
	DataObject\Factory,
	DataObject\Helper\MultitableTrait,
	Zend\Db\Sql\Expression,
	Zend\Db\Sql\Select;

trait AggregateFactoryTrait
{
	use MultitableTrait;

	/**
	 * Returns object form raw data
	 *
	 * @param	array	$aData	row from database
	 * @throws	Exception
	 * @return	DataObject
	 */
	public function aggregateGetObject(array &$aData)
	{
		if(empty($aData[$this->getTableName()]))
		{
			throw new Exception('No data from "'. $this->getTableName() .'" table');
		}

		$aRow = $aData[$this->getTableName()];

		if(!$this->aggregateCheckData($aRow))
		{
			throw new Exception('Incorrect data');
		}

		return $this->createObject($aRow);
	}

	/**
	 * Returns (by reference) structure configuration
	 *
	 * @param	string	$sTable		table name
	 * @param	array	$aFields	table fields
	 * @return	void
	 */
	public function aggregateGetConfig(&$sTable, &$aFields)
	{
		$sTable	 = $this->getTableName();
		$aFields = $this->getTableFields();
	}

	/**
	 * Extends select for aggregation
	 *
	 * @param	Select	$oSelect	actual select
	 * @param	string	$sPrimary	primary field name (with table name!)
	 * @param	string	$sType		JOIN type
	 * @return	Select
	 */
	public function aggregateGetSelect(Select $oSelect, $sPrimary, $sType = Select::JOIN_INNER)
	{
		$oPrimary = new Expression(
							Factory::getConnection()
								->getPlatform()
								->quoteIdentifierChain(explode('.', $sPrimary))
						);

		return $oSelect->join(
					$this->getTableName(),
					$this->getPrimaryWhere($oPrimary),
					$this->multitablePrefixAdd($this->getTableName(), $this->getTableFields()),
					$sType
				);
	}

	/**
	 * Check data given data structure
	 *
	 * @param	array	$aData	table row
	 * @return	bool
	 */
	protected function aggregateCheckData(array &$aData)
	{
		$aDiff = array_diff($this->getTableFields(), array_keys($aData));

		return empty($aDiff);
	}

	abstract protected function createObject(array $aRow, $mOptions = null);
	abstract protected function getPrimaryWhere($mId);
	abstract protected function getTableFields();
	abstract protected function getTableName();
}
