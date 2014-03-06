<?php

namespace Leptir\ErrorReport;

/**
 * Collection of error reporting objects
 *
 * Class ErrorReport
 * @package Leptir\ErrorReport
 */
class ErrorReport implements ErrorReportInterface
{
    private $errorReportingObjects = array();

    public function __construct($config)
    {
        foreach($config as $simpleConfig)
        {
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
        foreach($this->errorReportingObjects as $errorReporting) {
            $errorReporting->reportException($ex);
        }
    }

    public function reportErrorMessage($message)
    {
        /** @var $errorReporting ErrorReportInterface */
        foreach($this->errorReportingObjects as $errorReporting) {
            $errorReporting->reportErrorMessage($message);
        }
    }

    public function addErrorData(array $data)
    {
        /** @var $errorReporting ErrorReportInterface */
        foreach($this->errorReportingObjects as $errorReporting) {
            $errorReporting->addErrorData($data);
        }
    }
}
