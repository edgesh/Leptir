<?php

namespace Leptir\Broker;

use Leptir\MetaBackend\AbstractMetaBackend;
use Leptir\Task\BaseTask;
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

    /**
     * Task being decorated.
     *
     * @var BaseTask|null $task
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

    public function __construct(BaseTask $task, $priority = -1, \DateTime $timeOfExecution = null)
    {
        $this->task = $task;
        $this->priority = $priority;
        $this->timeOfExecution = $timeOfExecution;
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

        if (!intval($timeOfExecution)) {
            $timeOfExecution = null;
        } else {
            $timeOfExecution = \DateTime::createFromFormat('U', intval($timeOfExecution));
        }

        if (class_exists($className)) {
            /** @var BaseTask $task */
            $task = new $className();
            $task->exchangeArray($taskData);
        } else {
            return null;
        }

        return new self($task, $priority, $timeOfExecution);

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
    final public function execute($executionTime = 0, AbstractMetaBackend $metaBackend = null)
    {
        $this->task->execute($executionTime, $metaBackend);
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
     * Get task to execute (instanceof Leptir\Task\BaseTask)
     *
     * @return BaseTask|null
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

    final public function subscribeLoggers(array $loggers)
    {
        $this->getTask()->subscribeLoggers($loggers);
    }

    final public function getTaskReturnCode()
    {
        return $this->task->getReturnCode();
    }

    final public function injectServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        if ($this->task instanceof ServiceLocatorAwareInterface) {
            $this->task->setServiceLocator($serviceLocator);
        }
    }
}
