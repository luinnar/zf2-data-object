<?php

namespace DataObject\Generator;

use DataObject\Generator\AbstractGenerator;

require_once 'AbstractGenerator.php';

/**
 * Generator kodu dla modeli
 *
 * @author Mateusz Juściński
 */
class Model extends AbstractGenerator
{
	/**
	 * (non-PHPdoc)
	 * @see AbstractGenerator::generate()
	 */
	public function generate($sTableName, $sFullClassName)
	{
		$this->getTableInformation($sTableName);
		$this->getTemplates($this->sTemplatePath .'/model');

		$iPos = strrpos($sFullClassName, '\\');

		$sNamespace = substr($sFullClassName, 0, $iPos);
		$sClassName = substr($sFullClassName, $iPos + 1);

		$sCode = $this->fillFields(
					'class',
					[
						'namespace'		=> $sNamespace,
						'name'			=> $sClassName,
						'consts'		=> $this->generateConsts(),
						'getters'		=> $this->generateGetters(),
						'setters'		=> $this->generateSetters($sClassName)
					]
				);

		$this->save($sFullClassName, $sCode);
	}

	/**
	 * Generates constants
	 *
	 * @return	string
	 */
	protected function generateConsts()
	{
		$sConsts = '';

		foreach($this->aFields as $sName => $aOptions)
		{
			if(isset($aOptions['const']))
			{
				$sConsts .= $this->aTemplates['constDoc'];

				// generates consts for current variable
				foreach($aOptions['const'] as $sName => $sValue)
				{
					$sConsts .= $this->fillFields('const', ['name' => $sName, 'value' => $sValue]);
				}
			}
		}

		return $sConsts;
	}

	/**
	 * Generates getter methods
	 *
	 * @return	string
	 */
	protected function generateGetters()
	{
		$sGetters = '';

		foreach($this->aFields as $sName => $aOptions)
		{
			$sGetters .= $this->fillFields(
							'getter',
							[
								'type'		=> $aOptions['docType'],
								'funcName'	=> 'get'. $aOptions['name'],
								'field'		=> "'". $sName ."'"
							]
						);
		}

		return $sGetters;
	}

	/**
	 * Generates setter methods
	 *
	 * @param	string	$sClassName		class name
	 * @return	string
	 */
	protected function generateSetters($sClassName)
	{
		$sSetters = '';

		foreach($this->aFields as $sName => $aOptions)
		{
			// don't create setters for key fields
			if(isset($aOptions['primary']))
			{
				continue;
			}

			$sSetters .= $this->fillFields(
							'setter',
							[
								'className'	=> $sClassName,
								'type'		=> $aOptions['docType'],
								'param'		=> '$'. $aOptions['prefix'] . $aOptions['name'],
								'funcName'	=> 'set'. $aOptions['name'],
								'dbfield'	=> "'". $sName ."'"
							]
						);
		}

		return $sSetters;
	}
}
