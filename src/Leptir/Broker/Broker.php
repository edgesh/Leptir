<?php

namespace Leptir\Broker;

use Leptir\Logger\LeptirLoggerTrait;
use Leptir\Task\BaseTask;

class Broker
{
    use LeptirLoggerTrait;

    private $simpleBrokers = array();
    private $brokersProbability = array();

    private $queueCountCache = array();

    public function __construct(array $config = array(), $loggers = array())
    {
        $this->subscribeLoggers($loggers);

        foreach ($config as $brokerSetting) {
            try {
                $broker = BrokerFactory::factory($brokerSetting);
            } catch (\Exception $e) {
                $this->logError("Broker can't be initialized. " . $e->getMessage());
                continue;
            }
            $this->simpleBrokers[] = $broker;
        }
        $this->calculateBrokerProbabilities();
    }

    /**
     * Fetch number of tasks from all the brokers
     */
    final public function getTotalNumberOfTasks()
    {
        $total = 0;
        $numberOfBrokers = count($this->simpleBrokers);
        for ($i=0; $i<$numberOfBrokers; $i++) {
            $total += $this->getTasksCountForQueue($i);
        }
        return $total;
    }

    final public function getBoundedNumberOfTasks($bound)
    {
        $total = 0;
        $numberOfBrokers = count($this->simpleBrokers);
        for ($i=0; $i<$numberOfBrokers; $i++) {
            $total += $this->getTasksCountForQueue($i);
            if ($total > $bound) {
                return $total;
            }
        }
        return $total;
    }

    final public function addSimpleBroker(AbstractSimpleBroker $broker)
    {
        $this->simpleBrokers[] = $broker;
        $this->calculateBrokerProbabilities();
    }

    /**
     * Get one task from brokers following priority rules
     */
    final public function getOneTask()
    {
        $r = 1.0 * mt_rand() / mt_getrandmax();
        $numberOfBrokers = count($this->simpleBrokers);

        $totalProbability = 1.0;
        for ($i=0; $i<$numberOfBrokers; $i++) {

            if ($this->getTasksCountForQueue($i) === 0) {
                $totalProbability -= $this->brokersProbability[$i];
            }

        }

        $r *= $totalProbability;
        for ($i=0; $i<$numberOfBrokers; $i++) {
            /** @var AbstractSimpleBroker $broker */
            $broker = $this->simpleBrokers[$i];

            if ($this->getTasksCountForQueue($i) === 0) {
                continue;
            }

            if ($r <= $this->brokersProbability[$i]) {
                $task = $broker->popBrokerTask();
                if (isset($this->queueCountCache[$i])) {
                    $this->queueCountCache[$i]['count'] = max($this->queueCountCache[$i]['count']-1, 0);
                }
                return $task;
            }
            $r -= $this->brokersProbability[$i];
        }
        return null;
    }

    private function sortBrokersByPriority()
    {
        usort(
            $this->simpleBrokers,
            function ($broker1, $broker2) {
                /**
                 * @var $broker1 AbstractSimpleBroker
                 * @var $broker2 AbstractSimpleBroker
                 */
                return ($broker1->getPriority() < $broker2->getPriority()) ? -1 : 1;
            }
        );
    }

    private function calculateBrokerProbabilities()
    {
        $this->sortBrokersByPriority();
        $this->brokersProbability = array();

        $rank = 1;
        $numBrokers = count($this->simpleBrokers);
        $totalSum = 0;
        $ranks = array();
        for ($i=0; $i<$numBrokers; $i++) {
            if ($i > 0) {
                /** @var AbstractSimpleBroker $currentBroker */
                $currentBroker = $this->simpleBrokers[$i];
                /** @var AbstractSimpleBroker $previousBroker */
                $previousBroker = $this->simpleBrokers[$i-1];
                if ($currentBroker->getPriority() != $previousBroker->getPriority()) {
                    $rank += 1;
                }
            }
            $ranks[$i] = $rank;
            $totalSum += 1.0 / $rank;
        }

        for ($i=0; $i<$numBrokers; $i++) {
            $rank = $ranks[$i];
            $fitness = 1.0 / $rank;
            $this->brokersProbability[$i] = $fitness / $totalSum;

            $this->logInfo(
                get_class($this->simpleBrokers[$i]) .
                ' Broker probability: ' .
                (string)($fitness / $totalSum)
            );

        }
    }

    final public function pushTask(BaseTask $task, \DateTime $timeOfExecution = null, $priority = 0)
    {
        $taskId = $this->generateUniqueId();
        $task->setTaskId($taskId);
        $brokerTask = new BrokerTask($task, $priority, $timeOfExecution);

        /** @var AbstractSimpleBroker $simpleBroker */
        $simpleBroker = $this->getBrokerForPriority($priority);
        $simpleBroker->pushBrokerTask($brokerTask);

        return array(
            'id' => $taskId,
        );
    }

    /**
     * Helper method to generate unique string id
     *
     * @return string
     */
    final protected function generateUniqueId()
    {
        if (function_exists('posix_getpid')) {
            $pid = (string)posix_getpid();
        } else {
            $pid = (string)getmypid();
        }
        return uniqid($pid, true);
    }

    /**
     * @param $priority
     * @return AbstractSimpleBroker|null
     */
    final protected function getBrokerForPriority($priority)
    {
        $numberOfBrokers = count($this->simpleBrokers);
        for ($i=0; $i<$numberOfBrokers; $i++) {
            /** @var AbstractSimpleBroker $broker */
            $broker = $this->simpleBrokers[$i];
            if ($broker->getPriority() >= $priority) {
                return $broker;
            }
        }
        return count($this->simpleBrokers) ? $this->simpleBrokers[0] : null;
    }

    final private function getTasksCountForQueue($index)
    {
        if (!isset($this->queueCountCache[$index]) ||
            (time() - $this->queueCountCache[$index]['refreshed'] > 1)) {

            $this->refreshQueueCount($index);
        }
        return isset($this->queueCountCache[$index]) ? $this->queueCountCache[$index]['count'] : 0;
    }

    final private function refreshQueueCount($index)
    {
        $broker = isset($this->simpleBrokers[$index]) ? $this->simpleBrokers[$index] : null;
        if (!($broker instanceof AbstractSimpleBroker)) {
            return ;
        }
        $this->queueCountCache[$index] = array(
            'count' => $broker->getTasksCount(),
            'refreshed' => time()
        );
    }
}
