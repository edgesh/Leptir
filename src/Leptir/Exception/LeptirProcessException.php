<?php

namespace Leptir\Exception;

class LeptirProcessException extends AbstractLeptirException
{
    const PROCESS_FORK_ERROR = 1;
    const PID_FILE_NOT_WRITABLE = 2;

    protected function getMessageForCode($code)
    {
        switch($code)
        {
            case self::PROCESS_FORK_ERROR:
                return "Error while forking a process.";
            case self::PID_FILE_NOT_WRITABLE:
                return "Can't write to PID file.";
            default:
                return "Unknown exception occurred.";
        }
    }
}
