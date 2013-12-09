<?php

namespace Leptir\Task\Test;

use Leptir\Task\AbstractLeptirTask;

/**
 * Awesome testing task which will log message parameter.
 *
 * Class TestTask
 * @package Leptir\Task\Test
 */
class TestTask extends AbstractLeptirTask
{
    protected function doJob()
    {
        $message = $this->getString('message', '');
        $this->logInfo($message);

        $this->addResponseLine('Test task done.');
        return self::EXIT_SUCCESS;
    }
}
