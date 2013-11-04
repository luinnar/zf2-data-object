<?php

namespace DataObject\Paginator;

use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;
use Zend\Paginator\Adapter\AdapterInterface;

/**
 * Paginator suitable for use in DataObject
 *
 * @copyright	Copyright (c) 2011, Autentika Sp. z o.o.
 * @license		New BSD License
 * @author		Mateusz Juściński, Mateusz Kohut, Daniel Kózka
 */
class Adapter implements AdapterInterface
{
	/**
	 * Array defines order
	 *
	 * @var array
	 */
	private $aOrder = array();

	/**
	 * All records count
	 *
	 * @var int
	 */
	private $iCount = null;

	/**
	 * DB abstraction instance
	 *
	 * @var unknown
	 */
	private $oDb = null;

	/**
	 * Select for rows counting
	 *
	 * @var Select
	 */
	private $oCountSelect = null;

	/**
	 * Input data Factory name
	 *
	 * @var Factory
	 */
	private $oFactory = null;

	/**
	 * Where definition
	 *
	 * @var Where
	 */
	private $oWhere = null;

	/**
	 * Optional parameters
	 *
	 * @var	mixed
	 */
	private $mOption = null;

	/**
	 * Constructor
	 *
	 * @param	Factory		$oFactory		factory that creates this object
	 * @param	Select		$oCountSelect	Select for rows counting
	 * @param	mixed		$mOption		additional options sended to getPage()
	 * @return	Paginator
	 */
	public function __construct(Factory $oFactory, Select $oCountSelect, $mOption)
	{
		$this->oFactory		= $oFactory;
		$this->oCountSelect	= $oCountSelect;
		$this->mOption 		= $mOption;
	}

	/**
	 * Returns the total number of records in the query
	 *
	 * @return	int
	 */
	public function count()
	{
		if($this->iCount === null)
		{
			$aDbRes = (new Sql(Factory::getConnection()))
							->prepareStatementForSqlObject($this->oCountSelect)
							->execute()
							->current();

			$this->iCount = array_shift($aDbRes);
		}

		return $this->iCount;
	}

	/**
	 * Return an array of elements on a selected page
	 *
	 * @param	int		$iOffset		query offset
	 * @param	int		$iItemsPerPage	items limit per page
	 * @return	array
	 */
	public function getItems($iOffset, $iItemsPerPage)
	{
		$iPage = floor($iOffset / $iItemsPerPage) + 1;

		return $this->oFactory->getPage(
					$iPage, $iItemsPerPage, $this->aOrder, $this->oWhere, $this->mOption
				);
	}

	/**
	 * Change query order
	 *
	 * @param	array	$aOrder
	 * @return	void
	 */
	public function setOrder(array $aOrder)
	{
		if($this->iCount !== null)
		{
			throw new Exception('You cannot set ORDER after query execution');
		}

		$this->aOrder = $aOrder;
	}

	/**
	 * Change query where
	 *
	 * @param	Where	$mWhere		where string or Where object
	 * @return	void
	 */
	public function setWhere(Where $oWhere)
	{
		if($this->iCount !== null)
		{
			throw new Exception('You cannot set WHERE after query execution');
		}

		$this->oWhere = $oWhere;
	}
}
