<?php

namespace MongoObject\Paginator;

/**
 * Adapter do paginatora
 *
 * @author Mateusz Juściński
 */
class Adapter implements \Zend\Paginator\Adapter\AdapterInterface
{
	/**
	 * Łączna ilość elementów w wynikach
	 *
	 * @var int
	 */
	protected $iCount = null;

	/**
	 * Pola do wyciągnięcia
	 *
	 * @var array
	 */
	protected $aFields;

	/**
	 * Zapytanie mongowe
	 *
	 * @var array
	 */
	protected $aQuery;

	/**
	 * Sortowanie wyników
	 *
	 * @var array
	 */
	protected $aSort;

	/**
	 * Opcje dodatkowe
	 *
	 * @var array
	 */
	protected $aOptions;

	/**
	 * Fabryka pobierajace dane
	 *
	 * @var \MongoCollection
	 */
	protected $oColl;

	/**
	 * Fabryka pobierajace dane
	 *
	 * @var \MongoObject\Factory\AbstractFactory
	 */
	protected $oFactory;

	/**
	 * Koństruktor
	 *
	 * @param	CUS_Mongo_Factory	$oFactory	fabryka pobierająca dane
	 * @param	\MongoCollection	$oColl		kolekcja mongo
	 * @param	array				$aQuery		zapytamnie mongowe
	 * @param	array				$aFields	lista pól
	 * @param	array				$aSort		sortowanie wyników
	 * @param	array				$aOptions	opcje dodatkowe
	 */
	public function __construct($oFactory, \MongoCollection $oColl, array $aQuery, array $aFields, array $aSort, array $aOptions = [])
	{
		$this->oFactory = $oFactory;
		$this->oColl	= $oColl;
		$this->aQuery	= $aQuery;
		$this->aFields	= $aFields;
		$this->aSort	= $aSort;
		$this->aOptions	= $aOptions;
	}

	/**
	 * Zwraca liczbę elementów
	 *
	 * @return	int
	 */
	public function count()
	{
		if($this->iCount === null)
		{
			$this->iCount = $this->oColl->find($this->aQuery, $this->aFields)->count(true);
		}

		return $this->iCount;
	}

	/**
	 * (non-PHPdoc)
	 * @see Zend_Paginator_Adapter_Interface::getItems()
	 */
	public function getItems($iOffset, $iItemPerPage)
	{
		$iPage = floor($iOffset / $iItemPerPage) + 1;

		return $this->oFactory->getPage(
					$iPage, $iItemPerPage, $this->aQuery, $this->aFields, $this->aSort, $this->aOptions
				);
	}
}
