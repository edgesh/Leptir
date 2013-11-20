<?php

namespace Leptir\Broker;

use Leptir\Task\BaseTask;

abstract class AbstractBroker
{
    abstract public function __construct(array $config = array());

    /**
     * Send a new task to broker.
     *
     * @param BaseTask $task\
     * @param int $priority
     * @param \DateTime $timeOfExecution
     * @return array
     */
    final public function pushTask(BaseTask $task, $priority = -1, \DateTime $timeOfExecution = null)
    {
        $taskId = $this->generateUniqueId();
        $task->setTaskId($taskId);
        $brokerTask = new BrokerTask($task, $priority, $timeOfExecution);
        $this->pushBrokerTask($brokerTask);

        return array(
            'id' => $taskId,
        );
    }

    abstract protected function pushBrokerTask(BrokerTask $task);

    /**
     * Receive one task from broker.
     *
     * @return BrokerTask
     */
    abstract public function popBrokerTask();

    /**
     * Method that returns number of un-processed tasks.
     *
     * @return int
     */
    abstract public function getTasksCount();

    /**
     * Converts DateTime into relative delay (seconds remaining until the execution)
     * Returns -1 if time is not defined or if time is in the past
     *
     * @param \DateTime|null $time
     * @return int|mixed
     */
    final protected function convertTimeToRelativeDelay(\DateTime $time = null)
    {
        if (!$time) {
            return -1;
        }
        $timeStamp = $time->getTimestamp();
        $now = new \DateTime();
        $timeStampNow = $now->getTimestamp();
        return max(-1, $timeStamp - $timeStampNow);
    }

    final protected function generateUniqueId()
    {
        if (function_exists('posix_getpid')) {
            $pid = (string)posix_getpid();
        } else {
            $pid = (string)getmypid();
        }
        return uniqid($pid, true);
    }
}
