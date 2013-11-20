<?php

namespace DataObject\Structure;

use DataObject\Exception;
use DataObject\Factory;
use DataObject\Helper\Multitable;
use Zend\Db\Sql\Insert;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Update;

trait ExtendedFactory
{
	use Multitable;

	/**
	 * Table fields names
	 *
	 * @var	array
	 */
	private $_aFields = [];

	/**
	 * Base primary key with table name
	 *
	 * @var string
	 */
	private $_sBasePrimary;

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
	 * @param	string	$sBasePrimary	base primary key (with table name)
	 * @return	void
	 */
	protected function initExtended($sTable, $sPrimary, array $aFields, $sBasePrimary)
	{
		$this->_sTableName		= $sTable;
		$this->_sPrimaryKey		= $sPrimary;
		$this->_aFields			= $aFields;
		$this->_sBasePrimary	= $sBasePrimary;
	}

	/**
	 * (non-PHPdoc)
	 * @see DataObject\Factory::getSelect()
	 */
	protected function getSelect(array $aFields = ['*'], $mOption = null)
	{
		$aCurrFields = null;

		if($aFields == ['*'])
		{
			$aCurrFields = $this->multitablePrefixAdd($this->_sTableName, $this->_aFields);
		}

		$oSelect = parent::getSelect($aFields, $mOption);
		$oSelect->join(
			$this->_sTableName,
			$this->_sBasePrimary .'='. $this->_sTableName .'.'. $this->_sPrimaryKey,
			$aCurrFields
		);

		return $oSelect;
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
}
