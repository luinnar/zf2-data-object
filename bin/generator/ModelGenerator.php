<?php

require_once 'phing/Task.php';
require_once 'Model.php';
require_once 'Factory.php';

use DataObject\Generator\Model;
use DataObject\Generator\Factory;

/**
 * Task generates source code of DataObject models
 *
 * @author Mateusz Juściński
 */
class ModelGenerator extends Task
{
	/**
	 * PDO Instance
	 *
	 * @var PDO
	 */
	protected $oDb;

	/**
	 * Generated class name
	 *
	 * @var string
	 */
	protected $sClassName;

	/**
	 * Table name
	 *
	 * @var string
	 */
	protected $sTableName;

	/**
	 * Path to models directory
	 *
	 * @var string
	 */
	protected $sModelPath;

	/**
	 * Path to templates directory
	 *
	 * @var string
	 */
	protected $sTemplatePath;

	/**
	 * (non-PHPdoc)
	 * @see Task::main()
	 */
	public function main()
	{
		$this->checkParams();

		try
		{
			// generates model class
			$oGenerator = new Model($this->oDb, $this->sModelPath, $this->sTemplatePath);
			$oGenerator->generate($this->sTableName, $this->sClassName);

			// generates factory class
			$oGenerator = new Factory($this->oDb, $this->sModelPath, $this->sTemplatePath);
			$oGenerator->generate($this->sTableName, $this->sClassName .'Factory');
		}
		catch(Exception $e)
		{
			throw new BuildException($e->getMessage());
		}
	}

	/**
	 * Sets connection string
	 * String format: host:{host};dbname:{name};user:{user};pass:{pass}
	 *
	 * @param	string	$sString	connection string
	 * @return	void
	 */
	public function setDbString($sParams)
	{
		$aOptions = array();

		// breaks connect string into variables
		foreach(explode(';', $sParams) as $sParam)
		{
			list($sName, $sValue) = explode(':', $sParam);
			$aOptions[$sName] = $sValue;
		}

		$sDsn = 'mysql:dbname='. $aOptions['dbname'] .';host='. $aOptions['host'];

		$this->oDb = new PDO($sDsn, $aOptions['user'], $aOptions['pass']);
	}

	/**
	 * Sets name of generated class
	 *
	 * @param	string	$sName	class name
	 * @return	void
	 */
	public function setClassName($sName)
	{
		$this->sClassName = $sName;
	}

	/**
	 * Sets table name
	 *
	 * @param	string	$sName	table name
	 * @return	void
	 */
	public function setTableName($sName)
	{
		$this->sTableName = $sName;
	}

	/**
	 * Sets path to models directory
	 *
	 * @param	string	$sPath	path to models directory
	 */
	public function setModelPath($sPath)
	{
		$this->sModelPath = $sPath;
	}

	/**
	 * Sets path to templates directory
	 *
	 * @param	string	$sPath	path to templates directory
	 * @return	void
	 */
	public function setTemplatePath($sPath)
	{
		$this->sTemplatePath = $sPath;
	}

	/**
	 * Check all configuration parameters
	 *
	 * @throws	BuildException
	 * @return	void
	 */
	protected function checkParams()
	{
		if(empty($this->oDb) || !($this->oDb instanceof PDO))
		{
			throw new BuildException('Database connection failed', $this->getLocation());
		}

		if(empty($this->sClassName))
		{
			throw new BuildException('Invalid class name', $this->getLocation());
		}

		if(empty($this->sTableName))
		{
			throw new BuildException('Invalid table name', $this->getLocation());
		}

		if(empty($this->sModelPath) || !file_exists($this->sModelPath))
		{
			throw new BuildException('Incorrect new class path', $this->getLocation());
		}

		if(empty($this->sTemplatePath) || !file_exists($this->sTemplatePath))
		{
			throw new BuildException('Incorrect templates path', $this->getLocation());
		}
	}
}
