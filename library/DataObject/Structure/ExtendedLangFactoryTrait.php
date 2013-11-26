<?php

namespace DataObject\Structure;

use DataObject\Exception,
	DataObject\Factory,
	Zend\Db\Sql\Update,
	Zend\Db\Sql\Where;

/**
 * Extended structure for language factories
 *
 * @author	Mateusz Juściński
 */
trait ExtendedLangFactoryTrait
{
	use ExtendedFactoryTrait;

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
	 * @see DataObject\Factory::getSelect()
	 */
	protected function getSelect(array $aFields = ['*'], $mOption = null)
	{
		if(!$this->isLocaleSet())
		{
			throw new Exception('Language is not set');
		}

		$aCurrFields = null;

		if($aFields == ['*'])
		{
			$aCurrFields = $this->multitablePrefixAdd($this->_sTableName, $this->_aFields);
		}

		$oJoinWhere = (new Where)
			->equalTo(
				$this->_sBasePrimary, 	$this->_sTableName .'.'. $this->_sPrimaryKey,
				Where::TYPE_IDENTIFIER, Where::TYPE_IDENTIFIER
			)
			->and->equalTo(
				$this->_sTableName .'.locale', $this->getLocale()
			);

		$oSelect = parent::getSelect($aFields, $mOption);
		$oSelect->join($this->_sTableName, $oJoinWhere, $aCurrFields);

		return $oSelect;
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
