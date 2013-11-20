<?php

namespace Leptir\Task;

/**
 * Interface to define a way to create revert process for some task. Revert task will be created
 * if original tasks execution fails at some point (error, timeout, ...)
 *
 * Interface RevertibleTaskInterface
 * @package Leptir\Task
 */

interface RevertibleTaskInterface
{
    /**
     * @return BaseTask
     */
    public function createRevertTask();
}
