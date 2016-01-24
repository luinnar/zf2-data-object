<?php

namespace MongoObject\Document;

/**
 * Moduł rozszerzający funkcjonalność obiektu
 *
 * @author Mateusz Juściński
 */
class Module extends AbstractDocument
{
	/**
	 * Czy przy zapisie utworzyć nowe dane
	 *
	 * @var bool
	 */
	protected $bCreate;

	/**
	 * Obiekt "rodzica"
	 *
	 * @var \MongoObject\Document\Document
	 */
	protected $oParent;

	/**
	 * Ścieżka do zapisania danych pluginu
	 *
	 * @var string
	 */
	private $sMongoPath;

	/**
	 * Koństruktor
	 *
	 * @param	CUS_Mongo_Document	$oParent	obiekt "rodzica"
	 * @param	array				$aData		dane na których operuje plugin
	 * @param	string				$sPath		ścieżka do danych plugina
	 * @param	bool				$bCreate	czy tworzony jest nowy plugin
	 */
	public function __construct(Document $oParent, $sPath, array &$aData, $bCreate)
	{
		parent::__construct($aData);

		$this->oParent		= $oParent;
		$this->sMongoPath	= $sPath;
		$this->bCreate		= $bCreate;
	}

	/**
	 * Akcja wykonywana zaraz po zapisaniu zmian
	 *
	 * @return	void
	 */
	public function afterSave()
	{
	}

	/**
	 * Zwraca modyfikacje wykonane na obiekcie i usuwa historę modyfikacji
	 *
	 * @return	array
	 */
	public function beforeSave()
	{
		// pobieram zmiany
		$aChanges = $this->getChanges();
		// czyszczę loga zmian
		$this->clearChanges();

		// czy tworzę nowy moduł
		if(!empty($aChanges) && $this->bCreate)
		{
			// ustawiam wszystkie dane
			$aChanges = ['$set' => [$this->sMongoPath => $this->aData]];

			// wyłaczam status tworzenia nowego obiektu
			$this->bCreate = false;
		}

		return $aChanges;
	}

	/**
	 * Czy istnieją jakieś dane modułu
	 *
	 * @return	bool
	 */
	public function isEmpty()
	{
		return empty($this->aData);
	}
}
