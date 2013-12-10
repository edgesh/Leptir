<?php

namespace Leptir\Exception;

class DaemonException extends AbstractLeptirException
{
    const DAEMON_ALREADY_RUNNING = 1;

    protected function getMessageForCode($code)
    {
        switch($code)
        {
            case self::DAEMON_ALREADY_RUNNING:
                return "Leptir daemon is already running.";
            default:
                return "Unknown exception occurred.";
        }
    }
}
