<?php

namespace Leptir\Helper;

class SQSMessage
{
    private $messageBody;

    public function __construct(\CFResponse $message)
    {
        if (isset($message->body)) {
            $this->messageBody = $message->body->to_stdClass();
        } else {
            $this->messageBody = null;
        }
    }

    public function getMessage()
    {
        if (isset($this->messageBody->ReceiveMessageResult->Message->Body)) {
            return $this->messageBody->ReceiveMessageResult->Message->Body;
        } else {
            return null;
        }
    }

    public function getDecodedMessage()
    {
        $message = $this->getMessage();
        if (!$message) {
            return array();
        }

        return json_decode($message, true);
    }

    public function getMessageId()
    {
        if (isset($this->messageBody->ReceiveMessageResult->Message->MessageId)) {
            return $this->messageBody->ReceiveMessageResult->Message->MessageId;
        } else {
            return null;
        }
    }

    public function getReceiptHandle()
    {
        if (isset($this->messageBody->ReceiveMessageResult->Message->ReceiptHandle)) {
            return $this->messageBody->ReceiveMessageResult->Message->ReceiptHandle;
        } else {
            return null;
        }
    }

    public function getMD5OfBody()
    {
        if (isset($this->messageBody->ReceiveMessageResult->Message->MD5OfBody)) {
            return $this->messageBody->ReceiveMessageResult->Message->MD5OfBody;
        } else {
            return null;
        }
    }
}
