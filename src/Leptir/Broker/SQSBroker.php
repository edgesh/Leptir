<?php

namespace Leptir\Broker;

use Leptir\Exception\SQSBrokerException;
use Leptir\Helper\SQSQueue;
use Leptir\Task\BaseTask;

class SQSBroker extends AbstractBroker
{
    /**
     * @var \Leptir\Helper\SQSQueue|null
     */
    private $SQSQueue = null;

    public function __construct(array $config = array())
    {
        if (!isset($config['sqs_key'])) {
            throw new SQSBrokerException(SQSBrokerException::SQS_KEY_MISSNING);
        }
        if (!isset($config['sqs_secret'])) {
            throw new SQSBrokerException(SQSBrokerException::SQS_SECRET_MISSING);
        }
        if (!isset($config['sqs_queue'])) {
            throw new SQSBrokerException(SQSBrokerException::SQS_URL_MISSING);
        }

        $this->SQSQueue = new SQSQueue(
            $config['sqs_key'],
            $config['sqs_secret'],
            $config['sqs_queue']
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
    public function getTasksCount()
    {
        return $this->SQSQueue->approximateCount();
    }

    /**
     * @param BrokerTask $task
     */
    public function pushBrokerTask(BrokerTask $task)
    {
        $arrayCopy = $task->getArrayCopy();
        $encoded = json_encode($arrayCopy);
        $delay = $this->convertTimeToRelativeDelay($task->getTimeOfExecution());
        $this->sendMessage($encoded, $delay);
    }

    /**
     * Receive one task from broker.
     *
     * TODO priority support possible ?
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
        return BrokerTask::createFromArrayCopy($arrayCopy);
    }
}
