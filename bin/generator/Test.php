<?php

namespace DataObject\Generator;

use DataObject\Generator\AbstractGenerator;

require_once 'AbstractGenerator.php';

/**
 * Unit test class generator
 *
 * @author Mateusz Juściński
 */
class Test extends AbstractGenerator
{
	/**
	 * (non-PHPdoc)
	 * @see AbstractGenerator::generate()
	 */
	public function generate($sTableName, $sFullClassName)
	{
		$this->getTableInformation($sTableName);
		$this->getTemplates($this->sTemplatePath .'/test');

		$iPos = strrpos($sFullClassName, '\\');

		$sClassName			= substr($sFullClassName, $iPos + 1);
		$sFactoryFullName	= $sFullClassName .'Factory';

		$sCode = $this->fillFields(
					'file',
					[
						'class_fullname'	=> $sFullClassName,
						'class_name'		=> $sClassName,
						'table_name'		=> $sTableName,
						'factory_fullname'	=> $sFactoryFullName,
						'test_create'		=> $this->generateCreate($sClassName),
						'test_edit'			=> $this->generateEdit($sClassName),
						'test_delete'		=> $this->generateDelete($sClassName)
					]
				);

		$this->save($sFullClassName, $sCode);
	}

	/**
	 * Generates create test
	 *
	 * @return	string
	 */
	protected function generateCreate($sClassName)
	{
		$aFields = [];
		$aValues = $this->generateValues(true);

		foreach($aValues as $sField => $mValue)
		{
			$aFields[] = $this->fillFields(
							'field_check',
							[
								'method' => $this->aFields[$sField]['name'],
								'value'	 => $mValue,
							]
						);
		}

		return $this->fillFields(
					'test_create',
					[
						'class_name'	 => $sClassName,
						'create_list'	 => implode(', ', $aValues),
						'fields_check'	 => implode('', $aFields)
					]
				);
	}

	/**
	 * Generates edit test
	 *
	 * @return	string
	 */
	protected function generateEdit($sClassName)
	{
		$aCheck	 = [];
		$aSet	 = [];
		$aValues = $this->generateValues(true);

		foreach($aValues as $sField => $mValue)
		{
			$aTmp = [
				'method' => $this->aFields[$sField]['name'],
				'value'	 => $mValue,
			];

			$aCheck[]	= $this->fillFields('field_check', $aTmp);
			$aSet[]		= $this->fillFields('field_set', $aTmp);
		}

		return $this->fillFields(
					'test_edit',
					[
						'class_name'	 => $sClassName,
						'fields_check'	 => implode('', $aCheck),
						'fields_set'	 => implode('', $aSet)
					]
				);
	}

	/**
	 * Generates delete
	 *
	 * @return	string
	 */
	protected function generateDelete($sClassName)
	{
		return $this->fillFields('test_delete', ['class_name' => $sClassName]);
	}

	/**
	 * Returns array with random fields values
	 *
	 * @param	bool	$bAddDefault
	 * @return	array
	 */
	protected function generateValues($bAddDefault)
	{
		$aValues = [];

		foreach($this->aFields as $sName => $aOptions)
		{
			if(isset($aOptions['primary']))
			{
				continue;
			}
			elseif($bAddDefault && isset($aOptions['default']))
			{
				$aValues[$sName] = $aOptions['default'];
				continue;
			}

			switch($aOptions['type'])
			{
				case self::TYPE_DATE:
					$mValue = date('Y-m-d', time() - rand(1, 20) * 86400);
					break;
				case self::TYPE_DATETIME:
					$mValue = date('Y-m-d H:i:s', time() - rand(1, 20) * 86400);
					break;
				case self::TYPE_TIME:
					$mValue = date('H:i:s', time() - rand(1, 20) * 60);
					break;
				case self::TYPE_INT:
					$mValue = rand(1, 1000);
					break;
				case self::TYPE_STRING:
					$mValue = '"test'. rand(1, 1000) .'"';
					break;
				case self::TYPE_ENUM:
					$mValue = array_rand($aOptions['const']);
					$mValue = (is_numeric($mValue) ? $mValue : '"'. $mValue .'"');
					break;
			}

			$aValues[$sName] = $mValue;
		}

		return $aValues;
	}


	protected function save($sClassName, $sCode)
	{
		$sModule = substr($sClassName, 0, strpos($sClassName, '\\'));
		$sPath	 = $this->sModelsPath .'/../tests/'. str_replace('\\', '/', $sClassName) .'.php';

		// override check
		if(file_exists($sPath))
		{
			throw new \Exception('Class "'. $sClassName .'" already exists');
		}

		if(!file_exists(dirname($sPath)))
		{
			// prepares directory for class file
			if(mkdir(dirname($sPath), 0750, true))
			{
				throw new \BuildException('Can\'t create directory');
			}
		}

		// saves source code to file
		if(file_put_contents($sPath, $sCode) === false)
		{
			throw new \BuildException('Can\'t save class to file');
		}
	}
}
