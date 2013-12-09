<?php

namespace Leptir\Exception;

class BrokerFactoryException extends AbstractLeptirException
{
    const BROKER_CONFIG_INVALID = 1;
    const BROKER_NOT_SUPPORTED = 2;
    const BROKER_TYPE_NOT_DEFINED = 3;

    protected function getMessageForCode($code)
    {
        switch($code)
        {
            case self::BROKER_CONFIG_INVALID:
                return 'Broker configuration invalid.';
            case self::BROKER_NOT_SUPPORTED:
                return 'Broker configuration invalid. Requested broker is not supported';
            case self::BROKER_TYPE_NOT_DEFINED:
                return 'Broker type is not defined in configuration.';
            default:
                return 'Unknown error happened.';
        }
    }
}
