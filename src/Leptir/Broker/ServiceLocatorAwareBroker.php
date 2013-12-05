<?php

namespace Leptir\Broker;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ServiceLocatorAwareBroker extends Broker implements ServiceLocatorAwareInterface
{
    private $serviceLocator;

    /**
     * Set service locator
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    /**
     * Get service locator
     *
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    public function getOneTask()
    {
        $task = parent::getOneTask();
        if ($task instanceof ServiceLocatorAwareInterface) {
            $task->setServiceLocator($this->serviceLocator);
        }
    }

}