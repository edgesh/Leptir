<?php

namespace Leptir\Exception;

class LeptirLoggerFactoryException extends AbstractLeptirException
{
    const FILE_PATH_NOT_DEFINED = 1;
    const LOGGER_TYPE_NOT_DEFINED = 2;
    const LOGGER_TYPE_NOT_SUPPORTED = 3;

    protected function getMessageForCode($code)
    {
        switch($code)
        {
            case self::FILE_PATH_NOT_DEFINED:
                return "Logger file path not defined.";
            case self::LOGGER_TYPE_NOT_DEFINED:
                return "Logger configuration malformed. Logger type not defined.";
            case self::LOGGER_TYPE_NOT_SUPPORTED:
                return "Requested logger type not supported.";
            default:
                return "Unknown error happened.";
        }
    }
}
