<?php

namespace LeptirTest\Mocks;

use Leptir\Broker\AbstractSimpleBroker;
use Leptir\Broker\BrokerTask;

class MockSimpleBroker extends AbstractSimpleBroker
{
    private $tasks = array();

    public function __construct(array $config = array())
    {
        parent::__construct($config);
    }

    /**
     * Method that handles inserting a new task into broker queue.
     *
     * @param BrokerTask $task
     * @return mixed
     */
    public function pushBrokerTask(BrokerTask $task)
    {
        $this->tasks[] = $task;
    }

    /**
     * Receive one task from broker.
     *
     * @return BrokerTask
     */
    public function popBrokerTask()
    {
        if (count($this->tasks)) {
            return array_shift($this->tasks);
        }
        return null;
    }

    /**
     * Retrieve number of tasks from broker. This method doesn't need to do caching.
     * Caching is done in Broker class.
     *
     * @return int
     */
    protected function tasksCount()
    {
        return count($this->tasks);
    }
}

 