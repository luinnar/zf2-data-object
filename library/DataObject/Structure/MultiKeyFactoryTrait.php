<?php

namespace DataObject\Structure;

use Zend\Db\Sql\Where;

trait MultiKeyFactoryTrait
{
	/**
	 * Returns SQL WHERE string created for the specified key fields
	 *
	 * @param	mixed	$mId	primary key value
	 * @return	string
	 */
	protected function getPrimaryWhere($mId)
	{
		$oWhere = new Where();

		// single primary key
		if(!isset($mId[0]))
		{
			$mId = [$mId];
		}

		// many fields in key
		foreach($mId as $aPrimary)
		{
			$oWhere = $oWhere->nest();

			foreach($this->getTableKey() as $sField)
			{
				if(!isset($aPrimary[$sField]))
				{
					throw new Exception('No value for key part: ' . $sField);
				}

				$sFieldName = $this->sTableName .'.'. $sField;

				if(is_array($aPrimary[$sField]))
				{
					$oWhere->in($sFieldName, $aPrimary[$sField]);
				}
				else
				{
					$oWhere->equalTo($sFieldName, $aPrimary[$sField]);
				}
			}

			$oWhere = $oWhere->unnest();
		}

		return $oWhere;
	}
}
