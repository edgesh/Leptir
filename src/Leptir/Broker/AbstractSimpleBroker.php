<?php

namespace Leptir\Broker;

abstract class AbstractSimpleBroker
{
    protected $priority = 0;
    protected $TASK_COUNT_CACHING_TIME = 0.2;

    protected $countCacheRefreshed = 0;
    protected $cachedCount = 0;

    public function __construct(array $config = array())
    {
        if (isset($config['configuration']['priority'])) {
            $this->priority = $config['configuration']['priority'];
        }
        if (isset($config['configuration']['task_count_caching_time'])) {
            $this->TASK_COUNT_CACHING_TIME = $config['configuration']['task_count_caching_time'];
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
    final public function getTasksCount()
    {
        if (microtime(true) - $this->countCacheRefreshed > $this->TASK_COUNT_CACHING_TIME) {
            $this->cachedCount = $this->tasksCount();
            $this->countCacheRefreshed = microtime();
        }

        return $this->cachedCount;
    }

    abstract protected function tasksCount();


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
