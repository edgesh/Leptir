<?php

namespace Leptir\Exception;

class DaemonProcessException extends LeptirBaseException
{
    const UNABLE_TO_ACCESS_PID_FILE = 1;

    protected function getMessageForCode($code)
    {
        switch($code) {
            case self::UNABLE_TO_ACCESS_PID_FILE:
                return "Unable to access leptir.pid file. Make sure you have enough permission.";
            default:
                return "Unknown error happened";
        }
    }
}
