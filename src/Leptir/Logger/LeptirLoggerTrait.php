<?php

namespace Leptir\Logger;

use Zend\Log\Logger;

trait LeptirLoggerTrait
{
    protected $loggers = array();


    public function subscribeLogger(Logger $logger)
    {
        $this->loggers[] = $logger;
    }

    public function subscribeLoggers(array $loggers)
    {
        foreach ($loggers as $logger) {
            if ($logger instanceof Logger) {
                $this->loggers[] = $logger;
            }
        }
    }

    final protected function logInfo($message)
    {
        $this->log(Logger::INFO, $message);
    }

    final protected function logWarning($message)
    {
        $this->log(Logger::WARN, $message);
    }

    final protected function logError($message)
    {
        $this->log(Logger::ERR, $message);
    }

    final protected function logDebug($message)
    {
        $this->log(Logger::DEBUG, $message);
    }

    final private function log($level, $message)
    {
        /** @var Logger $logger */
        foreach ($this->loggers as $logger) {
            $logger->log($level, $this->formatMessage($message));
        }
    }

    /**
     * @param string $message
     * @return string
     */
    final private function formatMessage($message)
    {
        if (method_exists($this, 'formatLogMessage')) {
            return $this->formatLogMessage($message);
        }
        return $message;
    }
}
