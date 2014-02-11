<?php

namespace Leptir\Broker;

use Leptir\MetaStorage\MetaStorage;
use Leptir\Task\AbstractLeptirTask;
use Zend\Log\Logger;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Decorator for Leptir\Task\BaseTask
 *      Contains additional task meta-data:
 *          - priority
 *          - timeOfExecution
 *
 * Class BrokerTask
 * @package Leptir\Broker\BrokerTask
 */
class BrokerTask
{
    const CLASS_NAME_KEY = 'cn';
    const PRIORITY_KEY = 'pr';
    const TIME_OF_EXECUTION_KEY = 'toex';
    const TASK_DATA_KEY = 'td';
    const TASK_TIME_LIMIT_KEY = 'tl';

    /**
     * Task being decorated.
     *
     * @var AbstractLeptirTask|null $task
     */
    private $task = null;

    /**
     * Priority of $task.
     *
     * @var int $priority
     */
    private $priority = -1;

    /**
     * Time of execution of the $task.
     *
     * @var \DateTime|null
     */
    private $timeOfExecution = null;

    /**
     * Task time limit in seconds. If time limit is less or equal to zero, default leptir
     * time limit configuration will be used.
     *
     * @var int
     */
    private $timeLimit = 0;

    public function __construct(
        AbstractLeptirTask $task,
        $priority = -1,
        \DateTime $timeOfExecution = null,
        $timeLimit = 0
    ) {
        $this->task = $task;
        $this->priority = $priority;
        $this->timeOfExecution = $timeOfExecution;
        $this->timeLimit = $timeLimit;
    }

    /**
     * Recreate broker task from array representation of it
     *
     * @param \ArrayObject $arrayCopy
     * @return BrokerTask
     */
    public static function createFromArrayCopy(\ArrayObject $arrayCopy)
    {
        $taskData = $arrayCopy[self::TASK_DATA_KEY];
        $className = $arrayCopy[self::CLASS_NAME_KEY];
        $priority = intval($arrayCopy[self::PRIORITY_KEY]);
        $timeOfExecution = $arrayCopy[self::TIME_OF_EXECUTION_KEY];
        $timeLimit = $arrayCopy[self::TASK_TIME_LIMIT_KEY];

        if (!intval($timeOfExecution)) {
            $timeOfExecution = null;
        } else {
            $timeOfExecution = \DateTime::createFromFormat('U', intval($timeOfExecution));
        }

        if (class_exists($className)) {
            /** @var AbstractLeptirTask $task */
            $task = new $className();
            $task->exchangeArray($taskData);
        } else {
            return null;
        }

        return new self($task, $priority, $timeOfExecution, $timeLimit);

    }

    /**
     * Method taht checks if task is ready to be executed (if current time is past the $timeOfExecution).
     * @return bool
     */
    final public function isReady()
    {
        if (!($this->timeOfExecution instanceof \DateTime)) {
            return true;
        }
        $now = new \DateTime();
        return $this->timeOfExecution <= $now;
    }


    /**
     * Execute inner task.
     */
    final public function execute($timeLimit = 0, MetaStorage $metaBackend = null, $graceful = false)
    {
        if ($this->getTimeLimit() <= 0) {
            $this->task->execute($timeLimit, $metaBackend, $graceful);
        } else {
            $this->task->execute($this->getTimeLimit(), $metaBackend, $graceful);
        }
    }

    /**
     * Method returns array representation of BrokerTask
     *
     * @return \ArrayObject
     */
    final public function getArrayCopy()
    {
        $arrayObject = new \ArrayObject();
        $arrayObject[self::CLASS_NAME_KEY] = get_class($this->task);
        $arrayObject[self::PRIORITY_KEY] = $this->priority;
        $arrayObject[self::TASK_TIME_LIMIT_KEY] = $this->getTimeLimit();
        if ($this->timeOfExecution instanceof \DateTime) {
            $arrayObject[self::TIME_OF_EXECUTION_KEY] = $this->timeOfExecution->getTimestamp();
        } else {
            $arrayObject[self::TIME_OF_EXECUTION_KEY] = '';
        }
        $arrayObject[self::TASK_DATA_KEY] = $this->task->getArrayCopy();
        return $arrayObject;
    }

    final public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Get time of execution. Time of execution can be null ( time not defined ).
     *
     * @return \DateTime|null
     */
    final public function getTimeOfExecution()
    {
        return $this->timeOfExecution;
    }

    /**
     * Task time limit getter.
     * @return int
     */
    final public function getTimeLimit()
    {
        return $this->timeLimit;
    }

    /**
     * Get task to execute (instanceof Leptir\Task\BaseTask)
     *
     * @return AbstractLeptirTask|null
     */
    final public function getTask()
    {
        return $this->task;
    }

    /**
     * Inject logger into the task
     *
     * @param Logger $logger
     */
    final public function subscribeLogger(Logger $logger)
    {
        $this->getTask()->subscribeLogger($logger);
    }

    /**
     * Injecting list of loggers into the task
     *
     * @param array $loggers
     */
    final public function subscribeLoggers(array $loggers)
    {
        $this->getTask()->subscribeLoggers($loggers);
    }

    /**
     * Fetch return code from task.
     *
     * @return int
     */
    final public function getTaskReturnCode()
    {
        return $this->task->getReturnCode();
    }

    /**
     * Injecting service manager into the task
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    final public function injectServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        if ($this->getTask() instanceof ServiceLocatorAwareInterface) {
            $this->getTask()->setServiceLocator($serviceLocator);
        }
    }
}
