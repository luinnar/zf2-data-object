<?php

namespace MongoObject\Document;

use MongoObject\Exception;

/**
 * Klasa podstawowa dla dokumentów mongo
 *
 * @author Mateusz Juściński
 */
class Document extends AbstractDocument
{
	/**
	 * Kolekcja na której operujemy
	 *
	 * @var \MongoCollection
	 */
	private $oCollection;

	/**
	 * Tablica z modułami rozszerzającymi działanie dokumentu
	 *
	 * @var array
	 */
	private $aModules = null;

	/**
	 * Koństruktor
	 *
	 * @param	array				$aData			tablica prezentująca dokument
	 * @param	\MongoCollection	$oCollection	kolekcja, na której operujemy
	 * @param	array				$aModules		tablica z załadowanymi modułami
	 */
	public function __construct(array &$aData, \MongoCollection $oCollection, array $aModules = [])
	{
		parent::__construct($aData);

		$this->oCollection	= $oCollection;
		$this->aModules		= $aModules;
	}

	/**
	 * Zwraca ID dokumentu
	 *
	 * @return	string
	 */
	public function getId($bGetObject = false)
	{
		if($bGetObject)
		{
			return $this->aData['_id'];
		}

		return (string) $this->aData['_id'];
	}

// modyfikacja danych

	/**
	 * Usuwa cały dokument
	 *
	 * @return	void
	 */
	public function delete()
	{
		$this->oCollection->remove(['_id' => $this->aData['_id']]);
		$this->aData = [];
	}

	/**
	 * Zapisuję dokonane zmiany w bazie
	 *
	 * @return	void
	 */
	public function save()
	{
		$aMods = [];

		// dla modelu z modułami
		if($this->aModules !== null)
		{
			// obsługa modułów
			foreach($this->aModules as $mModule)
			{
				if(!$mModule instanceof Module)	// moduł nie został zainicjalizowany
				{
					continue;
				}

				$aChanges	= $mModule->beforeSave();
				$aMods[]	= $mModule;

				if(!empty($aChanges))	// łączę zmiany wykonane w module
				{
					$this->mergeChanges($aChanges);
				}
			}
		}

		$aChanges = $this->getChanges();

		// zapisuję jeśli się coś zmieniło
		if(!empty($aChanges))
		{
			$this->oCollection->update(['_id' => $this->getId(true)], $this->getChanges());
			$this->clearChanges();

			// post save
			foreach($aMods as $oModule)
			{
				$oModule->afterSave();
			}
		}
	}

// dodatkowe metody

	/**
	 * Zwraca moduł o poadnej nazwie
	 *
	 * @param	string	$sName	nazwa modułu
	 * @return	\MongoObject\Document\Module
	 */
	public function getModule($sName)
	{
		// niezainicjalizowany moduł
		if(!isset($this->aModules[$sName]))
		{
			throw new Exception('Moduł "'. $sName .'" nie został zainicjalizowany');
		}
		// skonfigurowany ale niezaładowany moduł
		elseif(is_array($this->aModules[$sName]))
		{
			$sClass	 = $this->aModules[$sName][0];
			$sPath	 = $this->aModules[$sName][1];
			$bCreate = false; // czy tworzymy nowy moduł

			try
			{
				$aData = $this->getFromPath($sPath); // pobieram dane modułu

				if($aData === null)
				{
					$aData = [];
				}
			}
			catch(Exception $e)
			{
				$aData	 = [];
				$bCreate = true;
			}

			if(!is_array($aData)) // dane modułu muszą być tablicą
			{
				throw new Exception('Dane modułu muszą być tablicą');
			}

			$this->aModules[$sName] = new $sClass($this, $sPath, $aData, $bCreate);
		}

		return $this->aModules[$sName];
	}

	/**
	 * Zwraca obiekt kolekcji
	 *
	 * @return \MongoCollection
	 */
	protected function getCollection()
	{
		return $this->oCollection;
	}
}
