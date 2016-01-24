<?php

namespace MongoObject\Document;

use MongoObject\Exception;

/**
 * Baza dla dokumentów mongo
 *
 * @author Mateusz Juściński
 */
abstract class AbstractDocument
{
	/**
	 * Typy operacji aktualizacyjnych
	 *
	 * @var string
	 */
	const UPDATE_INC		= '$inc';
	const UPDATE_SET		= '$set';
	const UPDATE_UNSET		= '$unset';
	// operacje tablicowe
	const UPDATE_ADDTOSET	= '$addToSet';
	const UPDATE_POP		= '$pop';
	const UPDATE_PULL		= '$pull';
	const UPDATE_PULLALL	= '$pullAll';
	const UPDATE_PUSH		= '$push';
	const UPDATE_PUSHALL	= '$pushAll';

	/**
	 * Tablica dokumentu
	 *
	 * @var array
	 */
	protected $aData;

	/**
	 * Zmodyfikowane dane
	 *
	 * @var array
	 */
	private $aMods = [];

	/**
	 * Koństruktor
	 *
	 * @param	array	$aData	dane dokumentu
	 */
	public function __construct(array &$aData)
	{
		$this->aData = $aData;
	}

// pobieranie danych

	/**
	 * Zwraca cały dokument
	 *
	 * @return	array
	 */
	public function getAll()
	{
		return $this->aData;
	}

	/**
	 * Zwraca wartość pola o podanej nazwie (nie obsługuje zagłębień)
	 *
	 * @param	string	$sField		nazwa pola-dziecka
	 * @param	mixed	$sDefault	wartość domyślna
	 * @return	mixed
	 */
	protected function getChild($sField, $sDefault = null)
	{
		if(array_key_exists($sField, $this->aData))
		{
			return $this->aData[$sField];
		}

		return $sDefault;
	}

	/**
	 * Zwraca referencję do elementu znajdującego się pod podaną ściezką
	 *
	 * @param	string	$sPath	ścieżka do elementu w postaci: cos.cosinnego.element
	 * @throws	\MongoObject\Exception
	 * @return	reference
	 */
	protected function &getFromPath($sPath)
	{
		// ścieżka bez zagłębień
		if(strpos($sPath, '.') === false)
		{
			// element nie istnieje
			if(!array_key_exists($sPath, $this->aData))
			{
				throw new Exception('Podana ścieżka nie istnieje');
			}

			return $this->aData[$sPath];
		}

		$aCurrent	= &$this->aData;
		$aPath		= explode('.', $sPath);
		$iCount		= count($aPath);

		for($i = 0; $i < $iCount; $i++)
		{
			$sField = $aPath[$i];

			// brak poszukiwanego pola
			if(!array_key_exists($sField, $aCurrent))
			{
				throw new Exception('Podana ścieżka nie istnieje');
			}

			$aCurrent = &$aCurrent[$sField];

			// jeśli jeszcze nie kończymy, a aktualny element nie jest tablicą
			if($i < $iCount && !is_array($aCurrent))
			{
				throw new Exception('Podana ścieżka nie istnieje');
			}
		}

		return $aCurrent;
	}

	/**
	 * Ustawia wartość konkretnego pola
	 *
	 * @param	string	$sPath	ścieżka pod którą mają zostać zapisane dane
	 * @param	mixed	$mData	dane do zapisania
	 * @throws	\MongoObject\Exception
	 * @return	reference
	 */
	protected function &setToPath($sPath, $mData)
	{
		$iPos = strrpos($sPath, '.');

		if($iPos === false)
		{
			$aData = &$this->aData[$sPath];
			$sField = $sPath;
		}
		else
		{
			$aData = $this->getFromPath(substr($sPath, 0, $iPos));
			$sField = substr($sPath, $iPos + 1);
		}

		$aData[$sField] = $mData;

		return $aData[$sField];
	}

// obsługa modyfikacji dokumentu

	/**
	 * Czyści tablicę zmodyfikowanych elementów
	 *
	 * @return	void
	 */
	final protected function clearChanges()
	{
		$this->aMods = [];
	}

	/**
	 * Zwraca modyfikacje wykonane na obiekcie
	 *
	 * @return	array
	 */
	final protected function getChanges()
	{
		return $this->aMods;
	}

	/**
	 * Łączy aktualne zmiany ze zmianami przekazanymi w tablicy
	 *
	 * @param	array	$aChanges	zmiany do połączenia
	 * @return	void
	 */
	final protected function mergeChanges(array &$aChanges)
	{
		$this->aMods = array_merge_recursive($this->aMods, $aChanges);
	}

	/**
	 * Zapisuje wykonaną operację
	 *
	 * @param	string	$sPath		ścieżka do zmienianej danej
	 * @param	mixed	$mValue		nowe dane
	 * @param	string	$sOperation	wykonywana operacja	(stałe self::UPDATE_*)
	 * @return	void
	 */
	final protected function saveOperation($sPath, $mValue, $sOperation = self::UPDATE_SET)
	{
		if(empty($this->aMods[$sOperation]))
		{
			$this->aMods[$sOperation] = [];
		}

		// podwójne dodawanie do zbioru
		if($sOperation == self::UPDATE_ADDTOSET && isset($this->aMods[$sOperation][$sPath]))
		{
			$mTmp = &$this->aMods[$sOperation][$sPath];

			// czy mam już więcej wartości
			if(is_array($mTmp) && isset($mTmp['$each']))
			{
				$mTmp['$each'][] = $mValue;
			}
			else
			{
				$mTmp = ['$each' => [$mTmp, $mValue]];
			}
		}
		// usuwanie wartości z tablicy
		elseif($sOperation == self::UPDATE_PULL)
		{
			// usuwałem wcześniej więcej elementów z tablicy
			if(isset($this->aMods[self::UPDATE_PULLALL][$sPath]))
			{
				$this->aMods[self::UPDATE_PULLALL][$sPath][] = $mValue;
			}
			// usuwałem już jeden element z tablicy
			elseif(isset($this->aMods[$sOperation][$sPath]))
			{
				$this->aMods[self::UPDATE_PULLALL][$sPath] = [$this->aMods[$sOperation][$sPath], $mValue];

				// anuluję operację pojedynczego usuwania
				unset($this->aMods[$sOperation][$sPath]);

				// brak innych pojedynczych usuwań - czyszczę
				if(empty($this->aMods[$sOperation]))
				{
					unset($this->aMods[$sOperation]);
				}
			}
			// normalnie dodaję wpis
			else
			{
				$this->aMods[$sOperation][$sPath] = $mValue;
			}
		}
		// operacja, które należy nadpisywać
		else
		{
			$this->aMods[$sOperation][$sPath] = $mValue;
		}
	}
}
