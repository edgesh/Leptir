<?php

namespace Leptir\ErrorReport;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Collection of error reporting objects
 *
 * Class ErrorReport
 * @package Leptir\ErrorReport
 */
class ErrorReport implements ErrorReportInterface, ServiceLocatorAwareInterface
{
    private $errorReportingObjects = array();
    private $serviceLocator;

    public function __construct($config)
    {
        foreach ($config as $simpleConfig) {
            if (!isset($simpleConfig['class'])) {
                continue;
            }
            $instance = new $simpleConfig['class']();
            if (!($instance instanceof ErrorReportInterface)) {
                continue;
            }
            $this->errorReportingObjects[] = $instance;
        }
    }

    public function reportException(\Exception $ex)
    {
        /** @var $errorReporting ErrorReportInterface */
        foreach ($this->errorReportingObjects as $errorReporting) {
            $errorReporting->reportException($ex);
        }
    }

    public function reportErrorMessage($message)
    {
        /** @var $errorReporting ErrorReportInterface */
        foreach ($this->errorReportingObjects as $errorReporting) {
            $errorReporting->reportErrorMessage($message);
        }
    }

    public function addErrorData(array $data)
    {
        /** @var $errorReporting ErrorReportInterface */
        foreach ($this->errorReportingObjects as $errorReporting) {
            $errorReporting->addErrorData($data);
        }
    }

    /**
     * Set service locator
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        foreach ($this->errorReportingObjects as $object) {
            if ($object instanceof ServiceLocatorAwareInterface) {
                $object->setServiceLocator($serviceLocator);
            }
        }
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
}
