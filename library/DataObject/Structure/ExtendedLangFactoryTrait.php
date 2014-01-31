<?php

namespace DataObject\Structure;

use DataObject\Exception,
	DataObject\Factory,
	Zend\Db\Sql\Sql,
	Zend\Db\Sql\Update,
	Zend\Db\Sql\Where;

/**
 * Extended structure for language factories
 *
 * @author	Mateusz Juściński
 */
trait ExtendedLangFactoryTrait
{
	use ExtendedFactoryTrait {
		initExtended as private _initExtended;
	}

	/**
	 * Base join predicate - to this join we will add locale predicate
	 *
	 * @var	\Zend\Db\Sql\Where
	 */
	private $_oBaseJoinPrepare;

	/**
	 * (non-PHPdoc)
	 * @see DataObject\Type\LanguageFactory::getLocale()
	 */
	abstract public function getLocale();

	/**
	 * (non-PHPdoc)
	 * @see DataObject\Type\LanguageFactory::isLocaleSet()
	 */
	abstract public function isLocaleSet();

	/**
	 * (non-PHPdoc)
	 * @see ExtendedLangFactoryTrait::initExtended()
	 */
	protected function initExtended($sTable, $sPrimary, array $aFields, $sBasePrimary)
	{
		$this->_initExtended($sTable, $sPrimary, $aFields, $sBasePrimary);

		// create where statment for join
		$this->_oBaseJoinPrepare = (new Where)
								->equalTo(
									$sBasePrimary, 	$sTable .'.'. $sPrimary,
									Where::TYPE_IDENTIFIER, Where::TYPE_IDENTIFIER
								);

		$this->afterLocaleChange();
	}

	/**
	 * (non-PHPdoc)
	 * @see ELanguageFactory::afterLocaleChange()
	 */
	protected function afterLocaleChange()
	{
		$oWhere = clone $this->_oBaseJoinPrepare;
		$oWhere->and->equalTo(
					$this->_sTableName .'.locale', $this->getLocale()
				);

		$this->_oBaseJoin = $oWhere;
	}

	/**
	 * (non-PHPdoc)
	 * @see DataObject\Factory::_update()
	 */
	protected function _update($mId, array $aData)
	{
		if(!$this->isLocaleSet())
		{
			throw new Exception('Language is not set');
		}

		$sFieldName = '_'. $this->_sTableName;

		if(!empty($aData[$sFieldName]))
		{
			try
			{
				$oUpdate = (new Update($this->_sTableName))
									->set($aData[$sFieldName])
									->where([
										$this->_sPrimaryKey => $mId,
										'locale'			=> $this->getLocale()
									]);

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
