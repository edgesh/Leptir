<?php

namespace Leptir\Broker;

use Leptir\Exception\SQSBrokerException;
use Leptir\Helper\SQSQueue;
use Leptir\Task\BaseTask;

class SQSBroker extends AbstractSimpleBroker
{
    const DELAYED_TASK_INSERT_TIME = 6;

    /**
     * @var \Leptir\Helper\SQSQueue|null
     */
    private $SQSQueue = null;

    public function __construct(array $config = array())
    {
        parent::__construct($config);

        $connection = isset($config['connection']) ? $config['connection'] : array();

        if (!isset($connection['sqs_key'])) {
            throw new SQSBrokerException(SQSBrokerException::SQS_KEY_MISSNING);
        }
        if (!isset($connection['sqs_secret'])) {
            throw new SQSBrokerException(SQSBrokerException::SQS_SECRET_MISSING);
        }
        if (!isset($connection['sqs_queue'])) {
            throw new SQSBrokerException(SQSBrokerException::SQS_URL_MISSING);
        }

        $this->SQSQueue = new SQSQueue(
            $connection['sqs_key'],
            $connection['sqs_secret'],
            $connection['sqs_queue']
        );
    }

    /**
     * Give a new task to the consumer
     *
     * @param string $message
     * @param int $delay
     */
    private function sendMessage($message, $delay = -1)
    {
        $this->SQSQueue->sendMessage($message, $delay);
    }

    /**
     * Receive one task from the consumer
     *
     * @return BaseTask
     */
    private function receiveMessage()
    {
        $message = $this->SQSQueue->popMessage();

        if (!$message || !$message->getMessage()) {
            return null;
        }
        return $message->getMessage();
    }

    /**
     * Number of tasks that consumer currently contains
     *
     * @return int
     */
    protected function tasksCount()
    {
        return $this->SQSQueue->approximateCount();
    }

    /**
     * @param BrokerTask $task
     * @return mixed|void
     */
    public function pushBrokerTask(BrokerTask $task)
    {
        $arrayCopy = $task->getArrayCopy();
        $encoded = json_encode($arrayCopy);
        $delay = $this->convertTimeToRelativeDelay($task->getTimeOfExecution());

        if ($delay > self::DELAYED_TASK_INSERT_TIME) {
            $delay = self::DELAYED_TASK_INSERT_TIME;
        }
        $this->sendMessage($encoded, $delay);
    }

    /**
     * Receive one task from broker.
     *
     * @return BrokerTask
     */
    public function popBrokerTask()
    {
        $message = $this->receiveMessage();
        $decoded = json_decode($message, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }
        if (!is_array($decoded)) {
            return null;
        }
        $arrayCopy = new \ArrayObject($decoded);
        $brokerTask = BrokerTask::createFromArrayCopy($arrayCopy);

        $timeOfExecution = $brokerTask->getTimeOfExecution();
        $now = new \DateTime();

        if (!$timeOfExecution || $timeOfExecution <= $now) {
            // task is ready for execution
            return $brokerTask;
        } else {
            /**
             * Task is not ready for execution yet. We have to re-insert it back to queue with
             * some delay.
             */
            $this->pushBrokerTask($brokerTask);
            return null;
        }
    }
}
