<?php

namespace Leptir\Exception;

class LeptirTaskException extends LeptirBaseException
{
    const TIME_LIMIT_EXCEEDED = 1;
    const RUNTIME_ERROR_OCCURRED = 2;

    protected function getMessageForCode($code)
    {
        switch($code)
        {
            case self::TIME_LIMIT_EXCEEDED:
                return "Task time limit exceeded.";
            case self::RUNTIME_ERROR_OCCURRED:
                return "Runtime error occurred while executing task.";
            default:
                return "Unknown exception.";
        }
    }

}