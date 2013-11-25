<?php

namespace DataObject\Generator;

use DataObject\Generator\AbstractGenerator;

require_once 'AbstractGenerator.php';

/**
 * Factory class generator
 *
 * @author Mateusz Juściński
 */
class Factory extends AbstractGenerator
{
	/**
	 * Primary key name
	 *
	 * @var array
	 */
	protected $aPrimary = [];

	/**
	 * (non-PHPdoc)
	 * @see AbstractGenerator::generate()
	 */
	public function generate($sTableName, $sFullClassName)
	{
		$this->getTableInformation($sTableName);
		$this->getTemplates($this->sTemplatePath .'/factory');

		$iPos = strrpos($sFullClassName, '\\');

		$sNamespace = substr($sFullClassName, 0, $iPos);
		$sClassName = substr($sFullClassName, $iPos + 1);

		$sCode = $this->fillFields(
					'class',
					array(
						'class'			=> $sClassName,
						'constructor'	=> $this->generateConstructor($sTableName),
						'create'		=> $this->generateCreate($sTableName, $sClassName),
						'createobject'	=> $this->generateCreateObject($sClassName),
						'namespace'		=> $sNamespace
					)
				);

		$this->save($sFullClassName, $sCode);
	}

	/**
	 * Generates constructor method
	 *
	 * @param	string	$sTableName	table name
	 * @return	string
	 */
	protected function generateConstructor($sTableName)
	{
		$aFields = [];

		// searches for primary keys
		foreach($this->aFields as $sName => $aOptions)
		{
			if(isset($aOptions['primary']))
			{
				$this->aPrimary[] = "'". $sName ."'";
			}

			$aFields[] = $sName;
		}

		$sKey = (count($this->aPrimary) > 1 ? '['. implode(', ', $this->aPrimary) .']' : $this->aPrimary[0]);

		return $this->fillFields(
					'construct',
					array(
						'table'	 => "'". $sTableName ."'",
						'key'	 => $sKey,
						'fields' => "['". implode("', '", $aFields) ."']"
					)
				);
	}

	/**
	 * Generates create method
	 *
	 * @param	string	$sTableName	table name
	 * @param	string	$sClassName	new class name
	 * @return	string
	 */
	protected function generateCreate($sTableName, $sClassName)
	{
		$sDocFields = $sParams = $sFields = $sKeys = '';

		foreach($this->aFields as $sName => $aOptions)
		{
			// only nonkeys fields can be function attributes
			if(isset($aOptions['primary']))
			{
				continue;
			}

			$sFieldName = $aOptions['prefix'] . $aOptions['name'];

			// docblock
			$sDocFields .= $this->fillFields(
								'create_field_doc',
								array(
									'type' => $aOptions['docType'],
									'param' => '$'. $sFieldName
								)
							);

			// method arguments
			$sParams .= '$'.$sFieldName;
			$sParams .= (isset($aOptions['default']) ? " = '". $aOptions['default'] ."'": '') .', ';

			// fields
			$sFields .= $this->fillFields(
							'create_field',
							array(
								'dbfield' => "'". $sName ."'",
								'param' => '$'. $sFieldName
							)
						);
		}

		return $this->fillFields(
					'create_base',
					[
						'doc'		=> $sDocFields,
						'class'		=> str_replace('Factory', '', $sClassName),
						'params'	=> substr($sParams, 0, -2),
						'primary'	=> reset($this->aPrimary),
						'dbfields'	=> $sFields
					]
				);
	}

	/**
	 * Generates createObject method
	 *
	 * @param	string	$sClassName	new class name
	 * @return	string
	 */
	protected function generateCreateObject($sClassName)
	{
		return $this->fillFields(
					'createobject',
					[
						'class'		=> str_replace('Factory', '', $sClassName),
						'primary'	=> reset($this->aPrimary)
					]
				);
	}
}
