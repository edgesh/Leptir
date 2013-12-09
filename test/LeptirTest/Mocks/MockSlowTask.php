<?php

namespace LeptirTest\Mocks;

use Leptir\Task\AbstractLeptirTask;

class MockSlowTask extends AbstractLeptirTask
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