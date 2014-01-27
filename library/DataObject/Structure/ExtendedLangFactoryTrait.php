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
		$this->_oBaseJoin = (new Where)
								->equalTo(
									$sBasePrimary, 	$sTable .'.'. $sPrimary,
									Where::TYPE_IDENTIFIER, Where::TYPE_IDENTIFIER
								)
								->and->equalTo(
									$sTable .'.locale', $this->getLocale()
								);
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
