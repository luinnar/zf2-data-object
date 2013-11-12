<?php

namespace DataObject\Structure;

trait BaseFactory
{
	abstract protected function structureGet($sField = null, $mDefault = null);

	abstract protected function structureIsLocked();

	abstract protected function structureLock();

	abstract protected function structureSet($mField, $mData = null);
}
