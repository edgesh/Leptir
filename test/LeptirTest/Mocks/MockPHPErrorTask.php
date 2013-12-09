<?php

namespace LeptirTest\Mocks;

use Leptir\Task\AbstractLeptirTask;

class MockPHPErrorTask extends AbstractLeptirTask
{
    /**
     * Main logic of the task. This method has to be implemented for every task.
     *
     * @return mixed
     */
    protected function doJob()
    {
        trigger_error('Very nice! How much?');
    }

}
 