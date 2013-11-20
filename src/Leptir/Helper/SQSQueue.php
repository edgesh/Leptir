<?php

namespace Leptir\Helper;

class SQSQueue
{
    private $queue;
    private $queueName;

    public function __construct($key, $secret, $queueName)
    {
        $this->queue = new \AmazonSQS(
            array(
                'key' => $key,
                'secret' => $secret
            )
        );
        $this->queueName = $queueName;
    }

    public function approximateCount()
    {
        $resp = $this->queue->get_queue_attributes(
            $this->queueName,
            array('AttributeName' => 'ApproximateNumberOfMessages')
        );
        $stdResponse = $resp->body->to_stdClass();

        $value = 0;
        if (isset($stdResponse->GetQueueAttributesResult->Attribute->Value)) {
            $value = $stdResponse->GetQueueAttributesResult->Attribute->Value;
        }

        return $value;
    }

    public function sendMessage($message, $delay = -1)
    {
        $args = array();
        if ($delay > 0) {
            $args['DelaySeconds'] = $delay;
        }

        $this->queue->send_message($this->queueName, $message, $args);
    }

    public function receiveMessage()
    {
        $message = $this->queue->receive_message($this->queueName);

        return new SQSMessage($message);
    }

    public function deleteMessage(SQSMessage $message)
    {
        if (!$message->getReceiptHandle()) {
            return false;
        }

        $resp = $this->queue->delete_message($this->queueName, $message->getReceiptHandle());

        if ($resp) {
            return true;
        }

        return false;
    }

    public function popMessage()
    {
        $message = $this->receiveMessage();
        $this->deleteMessage($message);
        return $message;
    }
}
