<?php

namespace Leptir\Task\Test;

use Leptir\Task\AbstractLeptirTask;

class ErrorTask extends AbstractLeptirTask
{
    /**
     * Main logic of the task. This method has to be implemented for every task.
     *
     * @return mixed
     */
    protected function doJob()
    {
        $this->logInfo('Triggering an error.');
        trigger_error('Very nice! How much?');
    }
}
