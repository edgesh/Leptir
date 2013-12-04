<?php

namespace Leptir\Broker;

abstract class AbstractSimpleBroker
{
    protected $priority = 0;

    public function __construct(array $config = array())
    {
        if (isset($config['configuration']['priority'])) {
            $this->priority = $config['configuration']['priority'];
        }
    }

    abstract public function pushBrokerTask(BrokerTask $task);

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



    protected function getTimeStampForDate(\DateTime $time = null)
    {
        if (!($time instanceof \DateTime)) {
            $time = new \DateTime();
        }
        $score = intval($time->format('U'));
        return $score;
    }

    final public function getPriority()
    {
        return $this->priority;
    }
}
