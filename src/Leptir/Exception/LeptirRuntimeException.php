<?php

namespace Leptir\Exception;

class LeptirRuntimeException extends LeptirBaseException
{
    const CONFIGURATION_FILE_INVALID = 1;

    protected function getMessageForCode($code)
    {
        switch($code)
        {
            case self::CONFIGURATION_FILE_INVALID:
                return "Leptir configuration is invalid.";
            default:
                return "Unknown exception happen.";
        }
    }
}