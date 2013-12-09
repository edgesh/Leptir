<?php

namespace Leptir\Exception;

class MongoBrokerException extends AbstractLeptirException
{
    protected function getMessageForCode($code)
    {
        switch($code)
        {
            default:
                return "Unknown exception occurred.";
        }
    }
}
