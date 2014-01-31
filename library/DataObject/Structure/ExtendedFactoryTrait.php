<?php

namespace DataObject\Structure;

use DataObject\Exception,
	DataObject\Factory,
	DataObject\Helper\MultitableTrait,
	Zend\Db\Sql\Expression,
	Zend\Db\Sql\Insert,
	Zend\Db\Sql\Sql,
	Zend\Db\Sql\Update;

trait ExtendedFactoryTrait
{
	use MultitableTrait;

	/**
	 * Table fields names
	 *
	 * @var	array
	 */
	private $_aFields = [];

	/**
	 * Where statement for join
	 *
	 * @var Zend\Db\Sql\Where
	 */
	private $_oBaseJoin;

	/**
	 * Table name
	 *
	 * @var string
	 */
	private $_sTableName;

	/**
	 * Primary key name
	 *
	 * @var	array
	 */
	private $_sPrimaryKey;

	/**
	 * Data object extended structure initialisation
	 *
	 * @param	string	$sTable			table name
	 * @param	array	$aPrimary		primary key definition
	 * @param	array	$aFields		fields definition
	 * @return	void
	 */
	protected function initExtended($sTable, $sPrimary, array $aFields)
	{
		$this->_sTableName	= $sTable;
		$this->_sPrimaryKey	= $sPrimary;
		$this->_aFields		= $aFields;

		// create where statment for join
		$this->_oBaseJoin = $this->getPrimaryWhere(
								new Expression(
										Factory::getConnection()
											->getPlatform()
											->quoteIdentifierChain([$sTable, $sPrimary])
									)
							);
	}

	/**
	 * (non-PHPdoc)
	 * @see DataObject\Factory::getSelect()
	 */
	protected function getSelect(array $aFields = ['*'], $mOption = null)
	{
		$aCurrFields = [];

		if($aFields == ['*'])
		{
			$aCurrFields = $this->multitablePrefixAdd($this->_sTableName, $this->_aFields);
		}

		return parent::getSelect($aFields, $mOption)
							->join($this->_sTableName, $this->getBaseJoin(), $aCurrFields);
	}

// single object manipulation

	/**
	 * (non-PHPdoc)
	 * @see DataObject\Factory::insert()
	 */
	protected function insert(array $aData)
	{
		$aCurrent	= null;
		$sFieldName	= '_'. $this->_sTableName;

		if(isset($aData[$sFieldName]))
		{
			$aCurrent = $aData[$sFieldName];
			unset($aData[$sFieldName]);
		}

		$mPrimaryKey = parent::insert($aData);

		if(!empty($aCurrent))
		{
			$oDb = self::getConnection();

			// seting extended primary key
			$aCurrent[$this->_sPrimaryKey] = $mPrimaryKey;
			// preparing query
			$oInsert = (new Insert())
							->into($this->_sTableName)
							->values($aCurrent);

			// execute query
			$oDb->query(
				(new Sql($oDb))->getSqlStringForSqlObject($oInsert),
				$oDb::QUERY_MODE_EXECUTE
			);
		}

		return $mPrimaryKey;
	}

	/**
	 * Protected update method
	 *
	 * @param	mixed	$mId	primary value
	 * @param	array	$aData	data to update
	 * @throws	Exception
	 * @return	void
	 */
	protected function _update($mId, array $aData)
	{
		$sFieldName = '_'. $this->_sTableName;

		if(!empty($aData[$sFieldName]))
		{
			try
			{
				$oUpdate = (new Update($this->_sTableName))
									->set($aData[$sFieldName])
									->where([$this->_sPrimaryKey => $mId]);

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

			unset($aData[$sFieldName]);
		}

		parent::_update($mId, $aData);
	}

// structure info methods

	/**
	 * Returns DataObject structure
	 *
	 * @return	array
	 */
	protected function getTableFields()
	{
		return $this->_aFields;
	}

	/**
	 * Returns primary key name
	 *
	 * @return	string
	 */
	protected function getTableKey()
	{
		return $this->_sPrimaryKey;
	}

	/**
	 * Returns table name
	 *
	 * @return	string
	 */
	protected function getTableName()
	{
		return $this->_sTableName;
	}

// private

	/**
	 * Return where for join
	 *
	 * @return	Zend\Db\Sql\Where
	 */
	private function getBaseJoin()
	{
		return $this->_oBaseJoin;
	}
}
