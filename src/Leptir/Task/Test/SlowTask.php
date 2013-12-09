<?php

namespace Leptir\Task\Test;

use Leptir\Task\AbstractLeptirTask;

/**
 * Simple task used for testing purpose. This task will not anything smart, it will just sleep for
 * random amount of seconds (between 6 and 19)
 *
 * Class SlowTask
 * @package Leptir\Task\Test
 */
class SlowTask extends AbstractLeptirTask
{
    protected function doJob()
    {
        $sleepTime = rand(30, 50);
        $this->logInfo('Sleeping for '. $sleepTime);
        sleep($sleepTime);

        $this->addResponseLine('Task had a great nap');
        return self::EXIT_SUCCESS;
    }
}
