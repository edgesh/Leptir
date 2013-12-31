<?php

namespace Leptir\Task\Test;

use Leptir\Task\AbstractLeptirTask;

class ExceptionTask extends AbstractLeptirTask
{
    /**
     * Main logic of the task. This method has to be implemented for every task.
     *
     * @return mixed
     * @throws \Exception
     */
    protected function doJob()
    {
        throw new \Exception('Very nice! How much?');
    }
}
