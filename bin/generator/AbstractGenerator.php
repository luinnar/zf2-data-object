<?php

namespace DataObject\Generator;

/**
 * Abstract class for DataObject code generators
 *
 * @author Mateusz Juściński
 */
abstract class AbstractGenerator
{
	/**
	 * Constants for data type names
	 *
	 * @var string
	 */
	const TYPE_ENUM		= 'enum';
	const TYPE_INT		= 'int';
	const TYPE_STRING	= 'string';

	/**
	 * Array with information about table fields
	 *
	 * @var array
	 */
	protected $aFields = [];

	/**
	 * Array with templates
	 *
	 * @var array
	 */
	protected $aTemplates = [];

	/**
	 * Path to models directory
	 *
	 * @var string
	 */
	protected $sModelsPath;

	/**
	 * Path to templates directory
	 *
	 * @var string
	 */
	protected $sTemplatePath;

	/**
	 * PDO instance
	 *
	 * @var PDO
	 */
	protected $oDb;

	/**
	 * Construct
	 *
	 * @param	PDO		$oDb			PDO instance
	 * @param	string	$sModelsPath	path to models directory
	 * @param	string	$sTemplatePath	path to template directory
	 */
	public function __construct($oDb, $sModelsPath, $sTemplatePath)
	{
		$this->oDb = $oDb;
		$this->sModelsPath = $sModelsPath;
		$this->sTemplatePath = $sTemplatePath;
	}

	/**
	 * Generates source code
	 *
	 * @para	string	$sTableName		table with new class definition
	 * @param	string	$sClassName		new class name
	 * @return	void
	 */
	abstract public function generate($sTableName, $sClassName);

	/**
	 * Replaces variables in template with it's values
	 *
	 * @param	string	$sTemplate	template name
	 * @param	array	$aFields	array with variables name and value
	 * @return	string
	 */
	protected function fillFields($sTemplate, array $aFields)
	{
		if(!isset($this->aTemplates[$sTemplate]))
		{
			throw new Exception('Template "'. $sTemplate .'" does\'nt exist');
		}

		$sResult = $this->aTemplates[$sTemplate];

		foreach($aFields as $sName => $sValue)
		{
			$sResult = str_replace('${'. $sName .'}', $sValue, $sResult);
		}

		return $sResult;
	}

// methods gathers information about class

	/**
	 * Examines field from database table
	 *
	 * @param	string	$sDbType
	 * @return	array
	 */
	protected function getClassType($sTableName, $sDbName, $sDbType)
	{
		$aResult = array(
			'docType'	=> null,
			'type'		=> null,
			'prefix'	=> null,
			'const'		=> null
		);

		$sType = str_replace(' unsigned', '', strtolower($sDbType));
		$sType = strpos($sDbType, '(') === false ? $sType : strstr($sType, '(', true);

		// check field data type
		if(strpos($sType, 'int') !== false)
		{
			$aResult['docType'] = 'int';
			$aResult['type']	= self::TYPE_INT;
			$aResult['prefix']	= 'i';
		}
		elseif(in_array($sType, array('real', 'double', 'float', 'decimal', 'numeric')))
		{
			$aResult['docType'] = 'float';
			$aResult['type']	= self::TYPE_INT;
			$aResult['prefix']	= 'i';
		}
		elseif(strpos($sType, 'char') !== false || strpos($sType, 'text') !== false)
		{
			$aResult['docType'] = 'string';
			$aResult['type']	= self::TYPE_STRING;
			$aResult['prefix']	= 's';
		}
		elseif(strpos($sType, 'date') !== false || strpos($sType, 'time') !== false)
		{
			$aResult['docType'] = 'string';
			$aResult['type']	= self::TYPE_STRING;
			$aResult['prefix']	= 's';
		}
		elseif($sType == 'enum')
		{
			$aResult['docType'] = 'string';
			$aResult['type']	= self::TYPE_ENUM;
			$aResult['prefix']	= 's';
			$aResult['const']	= [];

			$sConstPrefix = strtoupper($sTableName .'_'. $sDbName);

			// creates constants definition
			foreach(explode("','", substr($sDbType, 6, -2)) as $sOption)
			{
				$sConstName = $sConstPrefix .'_'. strtoupper($sOption);

				$aResult['const'][$sConstName] = $sOption;
			}
		}
		else
		{
			throw new \BuildException('Unsupported field type ('. $sType .')');
		}

		$aResult['name'] = str_replace(' ', '', ucwords(str_replace('_', ' ', $sDbName)));

		return $aResult;
	}

	/**
	 * Collects information about fields in table
	 *
	 * @throws Exception
	 */
	protected function getTableInformation($sTableName)
	{
		if(!empty($this->aFields))
		{
			return;
		}

		$aDbRes = $this->oDb->query('DESCRIBE '. $sTableName, \PDO::FETCH_NUM);

		if(empty($aDbRes))
		{
			throw new Exception('Can\'t describe table '. $sTableName);
		}

		// gathers information about fields
		foreach($aDbRes as $aRow)
		{
			list($sName, $sType, $sNull, $sKey, $sDefault, $sExtra) = $aRow;

			$this->aFields[$sName] = $this->getClassType($sTableName, $sName, $sType);

			if($sKey == 'PRI')		// primary key
			{
				$this->aFields[$sName]['primary'] = true;
			}

			if(!empty($sDefault))	// default value
			{
				$this->aFields[$sName]['default'] = $sDefault;
			}
		}
	}

	/**
	 * Returns array with class elements templates
	 *
	 * @param	string	$sPath	path to template file
	 * @return	array
	 */
	protected function getTemplates($sPath)
	{
		if(empty($this->aTemplates))
		{
			$oFile = new \SplFileObject($sPath, 'r');
			$sCurrent = null;

			foreach($oFile as $sLine)
			{
				if(substr($sLine, 0, 3) == '== ')
				{
					// new template starts here
					$sCurrent = substr($sLine, 3, -1);
					$this->aTemplates[$sCurrent] = '';
				}
				else
				{
					if(preg_match('/^(\$\{[a-z0-9]+\})\s+$/i', $sLine, $aResults))
					{
						$this->aTemplates[$sCurrent] .= $aResults[1];
					}
					else
					{
						$this->aTemplates[$sCurrent] .= $sLine;
					}
				}
			}
		}

		return $this->aTemplates;
	}

// other methods

	/**
	 * Saves source code to file
	 *
	 * @param	string	$sClassName	new class name
	 * @param	string	$sCode		source code
	 * @throws	BuildException
	 * @return	void
	 */
	protected function save($sClassName, $sCode)
	{
		$sModule = substr($sClassName, 0, strpos($sClassName, '\\'));
		$sPath	 = $this->sModelsPath .'/'. $sModule .'/src/'. str_replace('\\', '/', $sClassName) .'.php';

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
