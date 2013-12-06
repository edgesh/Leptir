<?php

namespace Leptir\Broker;

abstract class AbstractSimpleBroker
{
    /**
     * Variable used to cache number of tasks in current broker.
     *
     * @var int
     */
    protected $cachedCount = 0;

    /**
     * Variable that holds the last time tasks count was refreshed.
     * Task count is refreshed every $TASK_COUNT_CACHING_TIME seconds.
     *
     * @var int
     */
    protected $countCacheRefreshed = 0;

    /**
     * Priority of current broker
     *
     * @var int
     */
    protected $priority = 0;

    /**
     * Settings variable which represents the number of seconds tasks count will be cached.
     * Increasing this time can reduce number of broker requests.
     *
     * @var float
     */
    private $TASK_COUNT_CACHING_TIME = 0.2;

    /**
     * Default constructor.
     * Available parameters:
     *      * priority - priority of current broker
     *      * task_count_caching_time - explained for $TASK_COUNT_CACHING_TIME
     *
     * @param array $config
     */
    public function __construct(array $config = array())
    {
        if (isset($config['configuration']['priority'])) {
            $this->priority = $config['configuration']['priority'];
        }
        if (isset($config['configuration']['task_count_caching_time'])) {
            $this->TASK_COUNT_CACHING_TIME = $config['configuration']['task_count_caching_time'];
        }
    }

    /**
     * Method that handles inserting a new task into broker queue.
     *
     * @param BrokerTask $task
     * @return mixed
     */
    abstract public function pushBrokerTask(BrokerTask $task);

    /**
     * Receive one task from broker.
     *
     * @return BrokerTask
     */
    abstract public function popBrokerTask();

    /**
     * Retrieve number of tasks from broker. This method doesn't need to do caching.
     * Caching is done in Broker class.
     *
     * @return int
     */
    abstract protected function tasksCount();

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

    /**
     * Broker priority getter.
     *
     * @return int
     */
    final public function getPriority()
    {
        return $this->priority;
    }

    final public function increaseCachedCount($value) {
        $this->cachedCount += $value;
    }

    final public function decreaseCachedCount($value) {
        $this->cachedCount = max($this->cachedCount - $value, 0);
    }

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

    /**
     * Retrieve unix timestamp from DateTime. Also handles null values.
     *
     * @param \DateTime $time
     * @return int
     */
    protected function getTimeStampForDate(\DateTime $time = null)
    {
        if (!($time instanceof \DateTime)) {
            $time = new \DateTime();
        }
        $score = intval($time->format('U'));
        return $score;
    }
}
