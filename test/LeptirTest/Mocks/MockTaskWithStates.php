<?php

namespace LeptirTest\Mocks;

use Leptir\Task\AbstractLeptirTask;

class MockTaskWithStates extends AbstractLeptirTask
{
    protected function doJob()
    {
        $this->changeState(1);
        // do something
        $this->changeState(2);
        // do something else
        $this->changeState(3);
        return self::EXIT_SUCCESS;
    }
}
