<?php

namespace DataObject\Type;

use DataObject\Exception;
use DataObject\Factory;
use DataObject\Helper\Multitable;
use DataObject\Type\Pluginable;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;

/**
 * DataObject factory with plugins
 *
 * @license		New BSD License
 * @author		Mateusz Juściński
 */
abstract class PluginableFactory extends Factory
{
	use Multitable;

	/**
	 * Plugins configuration
	 *
	 * @var array
	 */
	private static $aPlugins = [];

	/**
	 * Array with loaded plugins
	 *
	 * @var array
	 */
	private $aCurrentPlugins = [];

	/**
	 * Loads plugin
	 *
	 * @param	string|array	$mName	plugin name or array with plugin names
	 * @throws	Exception
	 * @return	void
	 */
	public function pluginLoad($mName)
	{
		if(!is_array($mName))
		{
			$mName = [$mName];
		}

		foreach($mName as $sPluginName)
		{
			if(!isset(self::$aPlugins[$sPluginName]))
			{
				throw new Exception('Plugin "'. $sPluginName .'" doesnt exists in configuration');
			}

			$this->aCurrentPlugins[$sPluginName] = new self::$aPlugins[$sPluginName];
		}
	}

	/**
	 * Unloads plugin
	 *
	 * @param	string|array	$mName	plugin name or array with names
	 * @throws	Exception
	 * @return	void
	 */
	public function pluginUnload($mName)
	{
		if(!is_array($mName))
		{
			$mName = [$mName];
		}

		foreach($mName as $sPluginName)
		{
			unset($this->aCurrentPlugins[$sPluginName]);
		}
	}

// DataObject factory methods

	/**
	 * Creates an array of objects from the results returned by the database
	 *
	 * @param	Select	$aDbResult	Zend/Db/Sql/Select object
	 * @param	mixed	$mOption	aditional options
	 * @return array
	 */
	protected function createList(Select $oSelect, $mOption = null)
	{
		$oDb	= self::getConnection();
		$oDbRes = $oDb->query(
					(new Sql($oDb))->getSqlStringForSqlObject($oSelect),
					$oDb::QUERY_MODE_EXECUTE
				);

		$aResult = [];

		foreach($oDbRes as $aRow)
		{
			$aRow = $this->multitablePrefixRemove($aRow->getArrayCopy());

			// creating main instance from primary data
			$oModel = $this->createObject(
							$aRow[$this->multitableDefaultPrefix()],
							$mOption
						);
			// deleting used data
			unset($aRow[$this->multitableDefaultPrefix()]);
			// loading current plugins into model instance
			$this->loadInto($oModel, $aRow);

			$aResult[] = $oModel;
		}

		return $aResult;
	}

	/**
	 * (non-PHPdoc)
	 * @see DataObject\Factory::getSelect()
	 */
	protected function getSelect(array $aFields = ['*'], $mOption = null)
	{
		$oSelect = $this->_getSelect($aFields, $mOption);

		foreach($this->aCurrentPlugins as $oFactory)
		{
			$oSelect = $oFactory->addToSelect($oSelect, $mOption);
		}

		return $oSelect;
	}

// plugin factory methods

	/**
	 * Loads plugins into given model
	 *
	 * @param	Pluginable	$oModel		pluginable model instance
	 * @param	array		$aData		plugins data
	 * @return	Pluginable
	 */
	protected function loadInto(Pluginable $oModel, array $aData)
	{
		foreach($aData as $sPlugin => $aFields)
		{
			if(empty(self::$aPlugins[$sPlugin]))
			{
				throw new Exception('Plugin "'. $sPluginName .'" is not loaded');
			}

			$oFactory = self::$aPlugins[$sPlugin];
			$oModel->loadPlugin($oFactory->getPluginObject($aFields), $sPlugin);
		}

		return $oModel;
	}

	/**
	 * Loads plugin configuraction
	 *
	 * @param	array	$aPlugins	array with plugin configuration
	 * @return	void
	 */
	protected static function initPlugins(array $aPlugins)
	{
		if(!empty(self::$aPlugins))
		{
			return;
		}

		self::$aPlugins = $aPlugins;
	}

	/**
	 * Structure select method
	 */
	abstract protected function _getSelect(array $aFields = ['*'], $mOption = null);
}
