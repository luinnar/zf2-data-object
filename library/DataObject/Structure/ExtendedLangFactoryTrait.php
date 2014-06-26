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
		getBaseJoin as private _getBaseJoin;
	}

	/**
	 * Base join where with lang
	 *
	 * @var	Zend\Db\Sql\Where
	 */
	private $_oJoinWhere;

	/**
	 * Language used in BaseJoin
	 *
	 * @var	string
	 */
	private $_sJoinLang = null;

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
	 * @see DataObject\Structure\ExtendedFactoryTrait::getBaseJoin()
	 */
	private function getBaseJoin()
	{
		if($this->_sJoinLang != $this->getLocale())
		{
			$this->_sJoinLang = $this->getLocale();

			$this->_oJoinWhere = clone $this->_getBaseJoin();
			$this->_oJoinWhere->and->equalTo(
				$this->_sTableName .'.locale', $this->_sJoinLang
			);
		}

		return $this->_oJoinWhere;
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
