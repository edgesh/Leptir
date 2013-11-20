<?php

namespace Leptir\Task\Test;

use Leptir\Task\BaseTask;

class TestTask extends BaseTask
{
    /**
     * Main logic of the task. This method has to be implemented for every task.
     *
     * @return mixed
     */
    protected function doJob()
    {
        $message = $this->getString('message', '');
        $this->logInfo(
            'Got message: "' . $message . '"'
        );
        return self::EXIT_SUCCESS;
    }
}
