<?php

namespace LeptirTest\Mocks;

use Leptir\Broker\Broker;

class MockBroker extends Broker
{
    public function testGetBrokerForPriority($priority)
    {
        return $this->getBrokerForPriority($priority);
    }

    public function testGetProbabilities()
    {
        return $this->brokersProbability;
    }

    public function testGetBrokerForNextTask()
    {
        return $this->getBrokerForNextTask();
    }
}