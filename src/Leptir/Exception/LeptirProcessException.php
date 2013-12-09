<?php

namespace Leptir\Exception;

class LeptirProcessException extends AbstractLeptirException
{
    const PROCESS_FORK_ERROR = 1;

    protected function getMessageForCode($code)
    {
        switch($code)
        {
            case self::PROCESS_FORK_ERROR:
                return "Error while forking a process.";
        }
    }
}
