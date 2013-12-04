<?php

namespace Leptir\Exception;

class MongoBrokerException extends LeptirBaseException
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
