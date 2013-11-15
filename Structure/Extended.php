<?php

namespace DataObject\Structure;

use DataObject\Factory;

/**
 * DataObject structure
 *
 * @author Mateusz Juściński
 */
trait Extended
{
	use Base {
		delete as private _delete;
		save as private _save;
	}

	/**
	 * Constructor, sets necessary data for the data object
	 * Warning: In child class use this constructor!
	 *
	 * @param	array	$aData		model data
	 * @param	mixed	$mPrimary	primary key value
	 * @param	Factory	$oFactory	DataObject factory
	 */
	public function __construct(array $aData, $mPrimary, Factory $oFactory)
	{
	}

	/**
	 * Delete object from DB
	 *
	 * @throws	Exception
	 * @return	void
	 */
	public function delete()
	{

	}

	/**
	 * Save object to DB
	 *
	 * @throws	Exception
	 * @return	void
	 */
	public function save()
	{
		parent::save();

		$this->_save();
	}
}
