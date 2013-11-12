<?php

namespace DataObject;

use Zend\Mvc\MvcEvent;

class Module
{
	public function onBootstrap(MvcEvent $oEvent)
	{
		Factory::setConnection(
			$oEvent->getApplication()->getServiceManager()->get('Application\Db')
		);
	}
}
