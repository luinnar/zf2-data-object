<?php

namespace MongoObject\Factory;

use MongoObject\ConnectionManager;
use MongoObject\Exception;
use MongoObject\Document\Document;

/**
 * Abstrakcyjna klasa fabryki modeli
 *
 * @author Mateusz Juściński
 */
abstract class AbstractFactory
{
	/**
	 * Collection name
	 *
	 * @var string
	 */
	private $sCollectionName;

	/**
	 * Instance of Mongo's collection
	 *
	 * @var \MongoCollection
	 */
	private $oCollection;

	/**
	 * Constructor
	 *
	 * @param	string	$sCollName	collection name
	 */
	public function __construct($sCollName)
	{
		$this->sCollectionName = $sCollName;
	}

// metody fabryczne

	/**
	 * Returns mongo's cursor with query result
	 *
	 * @param	array		$aQuery		array with query conditions
	 * @param	array|null	$aFields	required fields
	 * @return	\MongoCursor
	 */
	public function find(array $aQuery = [], array $aFields = null)
	{
		// wybieram zestaw pól
		$aFields = $this->getDocumentFields($aFields);

		// czy przekazano listę pól do pobrania
		if(is_array($aFields))
		{
			return $this->getCollection()->find($aQuery, $aFields);
		}

		return $this->getCollection()->find($aQuery);
	}

	/**
	 * Returns single document matching to query conditions
	 *
	 * @param	array		$aFind		array with query conditions
	 * @param	array|null	$aFields	required fields
	 * @throws	CUS_Mongo_Factory_Exception
	 * @return	CUS_Mongo_Document
	 */
	public function findOne(array $aFind, array $aFields = null)
	{
		// wybieram zestaw pól
		$aFields = $this->getDocumentFields($aFields);

		// czy przekazano listę pól do pobrania
		if(is_array($aFields))
		{
			$aRes = $this->getCollection()->findOne($aFind, $aFields);
		}
		else
		{
			$aRes = $this->getCollection()->findOne($aFind);
		}

		if(empty($aRes))
		{
			throw new Exception('Nie znaleziono poszukiwanego dokumentu');
		}

		return $this->createObject($aRes);
	}

	/**
	 * Parses API request and returns results as array
	 *
	 * @param	array	$aParams	required firlds
	 * @param	array	$aFilters	query conditions as array
	 * @param	array	$aSort		sorting
	 * @param	int		$iPage		results page number
	 * @param	int		$iPageCount	results per page
	 * @return	array
	 */
	public function getByApiRequest(array $aParams, array $aFilters = [], array $aSort = [],
									$iPage = 1, $iPageCount = 100)
	{
		// converting ID string to MongoID instance
		if(isset($aFilters['_id']) && is_string($aFilters['_id']))
		{
			$aFilters['_id'] = new \MongoId($aFilters['_id']);
		}
		// multiple ID filtering
		elseif(	isset($aFilters['_id']) &&
				is_array($aFilters['_id']) &&
				!empty($aFilters['_id']['$in']))
		{
			array_walk(
				$aFilters['_id']['$in'],
				function(&$mVal)
				{
					$mVal = new \MongoId($mVal);
				}
			);
		}

		// query execution
		$oRequest = $this->find($aFilters, $aParams);
		$oRequest->skip(($iPage - 1) * $iPageCount)->limit($iPageCount);

		// sorting
		if(!empty($aSort))
		{
			$oRequest->sort($aSort);
		}

		$aDbRes = iterator_to_array($oRequest, false);

		// converts MongoID objects to strings
		array_walk(
			$aDbRes,
			function(&$aDocument, $iKey)
			{
				$aDocument['_id'] = (string) $aDocument['_id'];
			}
		);

		return [
			'pages' => ceil($oRequest->count() / $iPageCount),
			'count'	=> $oRequest->count(),
			'items' => $aDbRes
		];
	}

	/**
	 * Returns single document matching document's ID
	 *
	 * @param	string					$sId	document's ID
	 * @throws	MongoObject\Exception
	 * @return	MongoObject\Document
	 */
	public function getOne($sId)
	{
		return $this->findOne(['_id' => new \MongoId($sId)]);
	}

	/**
	 * Returns single page of query results
	 *
	 * @param	int		$iPage		page number
	 * @param	int		$iCount		items per page count
	 * @param	array	$aQuery		query conditions as array
	 * @param 	array	$aFields	required fields
	 * @param	array	$aSort		sorting condisions
	 * @return	MongoCursor
	 */
	public function getPage($iPage, $iCount, array $aQuery = [], array $aFields = null, array $aSort = [])
	{
		$oCursor = $this->find($aQuery, $this->getDocumentFields($aFields))
						->skip(($iPage - 1) * $iCount)
						->limit($iCount);

		if(!empty($aSort))
		{
			$oCursor->sort($aSort);
		}

		return $this->createList($oCursor);
	}

	/**
	 * Returns paginator
	 *
	 * @param	int		$iPage		page number
	 * @param	int		$iCount		items per page count
	 * @param	array	$aQuery		query conditions as array
	 * @param 	array	$aFields	required fields
	 * @param	array	$aSort		sorting condisions
	 * @return	\Zend\Paginator\Paginator
	 */
	public function getPaginator($iPage, $iCount, array $aQuery = [], array $aFields = null, array $aSort = [])
	{
		$oAdapter = new \MongoObject\Paginator\Adapter(
							$this,
							$this->getCollection(),
							$aQuery,
							$this->getDocumentFields($aFields),
							$aSort
						);

		return (new \Zend\Paginator\Paginator($oAdapter))
						->setCurrentPageNumber($iPage)
						->setItemCountPerPage($iCount);
	}

// metody protected

	/**
	 * Returns array with Document instances
	 *
	 * @param	\MongoCursor	$oDbResult	cursor returned by database
	 * @return	array
	 */
	protected function createList(\MongoCursor $oDbResult)
	{
		$aResult = [];

		foreach($oDbResult as $aDoc)
		{
			$aResult[] = $this->createObject($aDoc);
		}

		return $aResult;
	}

	/**
	 * Creates Document class instances
	 *
	 * @param	array	$aData	document contents
	 * @return	\MongoObject\Document\Document
	 */
	protected function createObject(array &$aData)
	{
		return new Document($aData, $this->getCollection());
	}

	/**
	 * Returns current collection instance
	 *
	 * @return	\MongoCollection
	 */
	protected function getCollection()
	{
		if(empty($this->oCollection))
		{
			// @todo allow to set different DbManger
			// @todo allow to change DB in factory
			$this->oCollection = ConnectionManager::getInstance()->getDb()->selectCollection($this->sCollectionName);
		}

		return $this->oCollection;
	}

	/**
	 * Zwraca listę pól dodawaną do zapytań do bazy lub NULL jeśli pobrać wszystkie
	 *
	 * @param	array|null	$aFields	pola przekazane przy wywołaniu metody
	 * @return	array|null
	 */
	protected function getDocumentFields($aFields)
	{
		if(is_array($aFields))
		{
			return $aFields;
		}

		return null;
	}

// metody statyczne

	/**
	 * Returns mongo IDs in strings to MongoID instances
	 *
	 * @param	string|array	$mTmp	IDs to convert
	 * @return	MongoId|array
	 */
	public static function stringToId($mTmp)
	{
		// got MongoID instance
		if($mTmp instanceof \MongoId)
		{
			return $mTmp;
		}
		// not an array, converting...
		elseif(!is_array($mTmp))
		{
			return new \MongoId($mTmp);
		}
		// array with MongoId instances, nothing to do
		elseif(isset($mTmp[0]) && ($mTmp[0] instanceof \MongoId))
		{
			return $mTmp;
		}

		foreach($mTmp as &$mId)
		{
			$mId = new \MongoId($mId);
		}

		return array_values($mTmp);
	}
}
