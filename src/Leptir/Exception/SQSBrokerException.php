<?php

namespace Leptir\Exception;

class SQSBrokerException extends AbstractLeptirException
{
    const CONFIG_MISSING = 1;
    const SQS_KEY_MISSNING = 2;
    const SQS_SECRET_MISSING = 3;
    const SQS_URL_MISSING = 4;

    protected function getMessageForCode($code)
    {
        switch($code)
        {
            case self::CONFIG_MISSING:
                return "Configuration parameter is missing.";
            case self::SQS_KEY_MISSNING:
                return "AWS SQS key not defined in configuration.";
            case self::SQS_SECRET_MISSING:
                return "AWS SQS secret not defined in configuration.";
            case self::SQS_URL_MISSING:
                return "AWS SQS url not defined in configuration";
            default:
                return "Unknown exception occurred.";
        }
    }
}
