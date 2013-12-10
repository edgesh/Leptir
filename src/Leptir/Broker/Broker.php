<?php

namespace Leptir\Broker;

use Leptir\Logger\LeptirLoggerTrait;
use Leptir\Task\AbstractLeptirTask;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Leptir\Broker\SimpleBroker\SimpleBrokerFactory;
use Leptir\Broker\SimpleBroker\AbstractSimpleBroker;

class Broker implements ServiceLocatorAwareInterface
{
    /**
     * Logging is enabled from this class.
     * Available methods:
     *  * logInfo
     *  * logError
     *  * logWarning
     *  * logDebug
     */
    use LeptirLoggerTrait;

    /**
     * List of simple brokers.
     *
     * @var AbstractSimpleBroker[]
     */
    protected $simpleBrokers = array();

    /**
     * Probability for each broker to be chosen for next task. Broker priority affects the
     * probability.
     *
     * @var float[]
     */
    protected $brokersProbability = array();

    /**
     * Service manager to be injected into tasks if they implements ServiceLocatorAwareInterface
     *
     * @var null
     */
    protected $serviceLocator = null;

    public function __construct(array $config = array(), $loggers = array())
    {
        $this->subscribeLoggers($loggers);

        foreach ($config as $brokerSetting) {
            try {
                $broker = SimpleBrokerFactory::factory($brokerSetting);
            } catch (\Exception $e) {
                $this->logError("Broker can't be initialized. " . $e->getMessage());
                continue;
            }
            $this->simpleBrokers[] = $broker;
        }
        $this->calculateBrokerProbabilities();
    }

    /**
     * Total number of tasks from all the brokers.
     *
     * @returns int
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

    /**
     * Method returns bounded value of available tasks. It doesn't ask all the brokers if
     * it's not necessary.
     *
     * @param int $bound
     * @return int
     */
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

    /**
     * Set service locator
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    /**
     * Get service locator
     *
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    final public function addSimpleBroker(AbstractSimpleBroker $broker)
    {
        $this->simpleBrokers[] = $broker;
        $this->calculateBrokerProbabilities();
    }

    /**
     * Get one task from brokers following priority rules
     */
    public function getOneTask()
    {
        $broker = $this->getBrokerForNextTask();
        $task = $broker->popBrokerTask();
        if ($task) {
            $broker->decreaseCachedCount(1);
        }

        // Service locator injection
        if ($task && $task->getTask() instanceof ServiceLocatorAwareInterface) {
            $task->injectServiceLocator($this->getServiceLocator());
        }
        return $task;
    }

    /**
     * Determine the broker for next task respecting the probabilities
     *
     * @return AbstractSimpleBroker|null
     */
    protected function getBrokerForNextTask()
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
                return $broker;
            }
            $r -= $this->brokersProbability[$i];
        }
        return null;
    }

    /**
     * Sort brokers by priority
     */
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

    /**
     * Selection similar to rank selection in genetic algorithm.
     *
     */
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

    final public function pushTask(AbstractLeptirTask $task, \DateTime $timeOfExecution = null, $priority = 0)
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

    final public function getNumberOfSimpleBrokers()
    {
        return count($this->simpleBrokers);
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
        return count($this->simpleBrokers) ?
            $this->simpleBrokers[count($this->simpleBrokers)-1] :
            null;
    }

    /**
     * Fetch number of tasks in broker with index $index.
     *
     * @param $index
     * @return int
     */
    final private function getTasksCountForQueue($index)
    {
        $broker = isset($this->simpleBrokers[$index]) ? $this->simpleBrokers[$index] : null;

        if ($broker instanceof AbstractSimpleBroker) {
            return $broker->getTasksCount();
        }
        return 0;
    }
}
