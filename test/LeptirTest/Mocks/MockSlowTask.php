<?php

namespace LeptirTest\Mocks;

use Leptir\Task\BaseTask;

class MockSlowTask extends BaseTask
{
    /**
     * Main logic of the task. This method has to be implemented for every task.
     *
     * @return mixed
     */
    protected function doJob()
    {
        sleep(10);
        return self::EXIT_SUCCESS;
    }

}