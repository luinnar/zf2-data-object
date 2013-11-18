<?php

namespace DataObject\Structure;

use DataObject\Exception;
use DataObject\Factory;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Delete;
use Zend\Db\Sql\Insert;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Update;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Where;

trait BaseFactory
{
	/**
	 * Table name
	 *
	 * @var string
	 */
	private static $_sTableName;

	/**
	 * Table fields names
	 *
	 * @var	array
	 */
	private static $_aFields = [];

	/**
	 * Primary key definition
	 *
	 * @var	array
	 */
	private static $_aPrimaryKey = [];

// single object manipulation

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
						->into(self::$_sTableName)
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
			$oDelete = (new Delete(self::$_sTableName))
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
			$oUpdate = (new Update(self::$_sTableName))
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

// select definitions

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
		$aPrimaryKey = self::$_aPrimaryKey;
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

					$sFieldName = self::$_sTableName .'.'. $sField;

					if(is_array($aPrimary[$sField]))
					{
						$oWhere2->in($sFieldName, $aPrimary[$sField]);
					}
					else
					{
						$oWhere2->equalTo($sFieldName, $aPrimary[$sField]);
					}
				}

				$oWhere->orPredicate($oWhere2);
			}
		}
		else
		{
			$sFieldName = self::$_sTableName .'.'. $aPrimaryKey[0];

			if(is_array($mId))
			{
				$oWhere->in($sFieldName, $mId);
			}
			else
			{
				$oWhere->equalTo($sFieldName, $mId);
			}
		}

		return $oWhere;
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
		if($aFields == ['*'])
		{
			$aFields = self::$_aFields;
		}

		return (new Select())
						->from(self::$_sTableName)
						->columns($aFields);
	}

	/**
	 * Data object structure initialisation
	 *
	 * @param	string	$sTable		table name
	 * @param	array	$aPrimary	primary key definition
	 * @param	array	$aFields	fields definition
	 * @return	void
	 */
	private static function initStructure($sTable, array $aPrimary, array $aFields)
	{
		if(!empty(self::$_sTableName))
		{
			return;
		}

		self::$_sTableName	= $sTable;
		self::$_aPrimaryKey	= $aPrimary;
		self::$_aFields		= $aFields;
	}
}
