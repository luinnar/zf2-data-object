<?php

namespace DataObject\Helper;

/**
 * Extended JSON serialization with dynamic fields filtering
 *
 * @author Mateusz Juściński
 */
trait JsonSerializeTrait
{
	/**
	 * Serialization filter
	 *
	 * @var array
	 */
	private static $aJsonSerializeFilter = [];

	/**
	 * Clears JSON serialization filter
	 *
	 * @return	void
	 */
	public static function jsonSerializeClearFilter()
	{
		self::$aJsonSerializeFilter = [];
	}

	/**
	 * Sets JSON serialization filter
	 *
	 * @return	void
	 */
	public static function jsonSerializeSetFilter(array $aFields)
	{
		self::$aJsonSerializeFilter = array_combine($aFields, array_fill(0, count($aFields), true));
	}

	/**
	 * (non-PHPdoc)
	 * @see JsonSerializable::jsonSerialize()
	 */
	public function jsonSerialize()
	{
		$aData = $this->_jsonSerialize();

		if(!empty(self::$aJsonSerializeFilter))
		{
			$aData = array_intersect_key($aData, self::$aJsonSerializeFilter);
		}

		return $aData;
	}

	/**
	 * Internal serialization method
	 *
	 * @return	array
	 */
	abstract protected function _jsonSerialize();
}
