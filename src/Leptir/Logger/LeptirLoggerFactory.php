<?php

namespace Leptir\Logger;

use Leptir\Exception\LeptirLoggerFactoryException;
use Zend\Log\Logger;
use Zend\Log\Writer\Stream;

class LeptirLoggerFactory
{
    public static function factory($name, $config)
    {
        if (!isset($config['type'])) {
            throw new LeptirLoggerFactoryException(LeptirLoggerFactoryException::LOGGER_TYPE_NOT_DEFINED);
        }

        $type = $config['type'];
        $options = isset($config['options']) ? $config['options'] : array();

        switch($type)
        {
            case "file":
                return self::createFileLogger($options);
            case "stdout":
                return self::createStdoutLogger($options);
                break;
            case "stderr":
                return self::createStderrLogger($options);
                break;
            default:
                throw new LeptirLoggerFactoryException(LeptirLoggerFactoryException::LOGGER_TYPE_NOT_SUPPORTED);
        }
    }

    private static function createFileLogger(array $config)
    {
        $logger = new Logger();
        if (!isset($config['path'])) {
            throw new LeptirLoggerFactoryException(LeptirLoggerFactoryException::FILE_PATH_NOT_DEFINED);
        }
        $writer = new Stream($config['path']);
        $logger->addWriter($writer);
        return $logger;
    }

    private static function createStdoutLogger(array $config)
    {
        $logger = new Logger();
        $writer = new Stream('php://stdout');
        $logger->addWriter($writer);
        return $logger;
    }

    private static function createStderrLogger(array $config)
    {
        $logger = new Logger();
        $writer = new Stream('php://stderr');
        $logger->addWriter($writer);
        return $logger;
    }
}
