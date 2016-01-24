<?php

namespace MongoObject\Factory;

use MongoObject\Document\Document;
use MongoObject\Exception;

/**
 * Fabryka dokumentów zawierających moduły
 *
 * @author luinnar
 */
abstract class ModulableFactory extends AbstractFactory
{
	/**
	 * Aktualnie załadowane moduły w postaci: nazwaModulu => sicezka.modulu
	 *
	 * @var	array
	 */
	private $aModulesCurrent = [];

	/**
	 * Tablica z polami wysyłanymi do mongo w postaci sicezka.modulu => false
	 *
	 * @var array
	 */
	private $aModulesFields = [];

	/**
	 * Koństruktor
	 *
	 * @param	string	$sCollName	nazwa aktualnej kolekcji
	 */
	public function __construct($sCollName)
	{
		parent::__construct($sCollName);

		$this->moduleReset();
	}

// obsługa modułów

	/**
	 * Inicjalizuje moduł/moduły
	 *
	 * @param	string|array	$mModule	nazwa lub tablica nazw modułów do usunięcia
	 * @return	self
	 */
	public function moduleLoad($mModule)
	{
		$aModules = $this->moduleGetAll();

		if(!is_array($mModule))
		{
			$mModule = [$mModule];
		}

		foreach($mModule as $sName)
		{
			// brak modułu
			if(!isset($aModules[$sName]))
			{
				throw new Exception('Podany moduł nie został zarejestrowany');
			}
			// mam już ten moduł
			elseif(isset($this->aModulesCurrent[$sName]))
			{
				continue;
			}

			$aInfo = $aModules[$sName];
			// zapisuję aktualne moduły
			$this->aModulesCurrent[$sName] = $aInfo;
			// usuwam wyłączenie modułu
			unset($this->aModulesFields[$aInfo[1]]);
		}

		return $this;
	}

	/**
	 * Usuwa moduł/moduły
	 *
	 * @param	string|array	$mModule	nazwa lub tablica nazw modułów do usunięcia
	 * @return	self
	 */
	public function moduleRemove($mModule)
	{
		$aModules = $this->moduleGetAll();

		if(!is_array($mModule))
		{
			$mModule = [$mModule];
		}

		foreach($mModule as $sName)
		{
			// brak modułu
			if(!isset($aModules[$sName]))
			{
				throw new Exception('Podany moduł nie został zarejestrowany');
			}
			// moduł nie był załadowany, pomijam
			elseif(!isset($this->aModulesCurrent[$sName]))
			{
				continue;
			}

			$sPath = $aModules[$sName][1];
			// usuwam moduł z aktualnie załadowanych
			unset($this->aModulesCurrent[$sName]);
			// wyłączam pobieranie modułu
			$this->aModulesFields[$sPath] = false;
		}

		return $this;
	}

	/**
	 * Wyłącza wszystkie moduły
	 *
	 * @return	self
	 */
	public function moduleReset()
	{
		$this->aModulesCurrent = [];

		// wyłączam wszystkie moduły
		foreach($this->moduleGetAll() as $sName => $aInfo)
		{
			$this->aModulesFields[$aInfo[1]] = false;
		}

		return $this;
	}

// metody protected

	/**
	 * (non-PHPdoc)
	 * @see \MongoObject\Factory\AbstractFactory::createObject()
	 */
	protected function createObject(array &$aData, array $aOptions = [])
	{
		return new Document($aData, $this->getCollection(), $this->aModulesCurrent);
	}

	/**
	 * (non-PHPdoc)
	 * @see \MongoObject\Factory\AbstractFactory::getDocumentFields()
	 */
	protected function getDocumentFields($aFields)
	{
		if(is_array($aFields))
		{
			return $aFields;
		}

		return $this->aModulesFields;
	}

	/**
	 * Zwraca tablicę z wszystkimi dostępnymi modułami w postaci nazwaModulu => sicezka.modulu
	 *
	 * @return	array
	 */
	abstract protected function moduleGetAll();

	/**
	 * Zwraca aktualnie załadowane moduły
	 *
	 * @return	array
	 */
	protected function moduleGetCurrent()
	{
		return $this->aModulesCurrent;
	}
}
