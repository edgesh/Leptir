<?php

namespace Leptir\Task;

/**
 * Interface to define a way to create revert process for some task. Revert task will be created
 * if original tasks execution fails at some point (error, timeout, ...)
 *
 * Not supported yet
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
