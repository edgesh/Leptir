<?php

namespace Leptir\Task\Test;

use Leptir\Task\BaseTask;

/**
 * Simple task used for testing purpose. This task will not anything smart, it will just sleep for
 * random amount of seconds (between 6 and 19)
 *
 * Class SlowTask
 * @package Leptir\Task\Test
 */
class SlowTask extends BaseTask
{
    protected function doJob()
    {
        $sleepTime = rand(6, 19);
        $this->logInfo('Sleeping for '. $sleepTime);
        sleep($sleepTime);
        return self::EXIT_SUCCESS;
    }
}
