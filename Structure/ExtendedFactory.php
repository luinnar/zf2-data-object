<?php

namespace DataObject\Structure;

use Zend\Db\Sql\Expression;

trait ExtendedFactory
{
	use BaseFactory {
		delete as private _delete;
		insert as private _insert;
		update as private _update;
	}

// single object manipulation

	/**
	 * Perform SQL insert query and returns last inserted ID
	 *
	 * @param	array	$aData	data to save
	 * @return	mixed
	 */
	protected function insert(array &$aData)
	{
		parent::insert($aData);

		return $this->_insert($aData);
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
		parent::delete($mId);

		return $this->_delete($mId);
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
		parent::update($mId, $aData);

		return $this->_update($mId, $aData);
	}

// structure methods

	/**
	 * Prepares fields list for select
	 *
	 * @param	array	$aData	array with tables structure
	 * @return	array
	 */
	protected function _doPrepareFields(array &$aData)
	{
		$aResult = [];

		foreach($aData as $sTable => &$mFields)
		{
			// Zend\Db\Sql\Expression
			if($mFields instanceof Expression)
			{
				$aResult[$sTable] = $mFields;

				continue;
			}

			foreach($mFields as $sField)
			{
				$aResult[] = $sTable .'.'. $sField .' AS '. $sTable .'-'. $sField;
			}
		}

		return $aResult;
	}
}
