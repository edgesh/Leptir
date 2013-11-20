<?php

namespace Leptir\Broker;

use Leptir\Exception\BrokerFactoryException;

class BrokerFactory
{
    const BROKER_SQS = 'sqs';
    const BROKER_MONGO = 'mongodb';
    const BROKER_REDIS = 'redis';
    const BROKER_DOCTRINE = 'doctrine';

    /**
     * Factory for creating all supported brokers.
     *
     * @param array $config
     * @return MongoBroker|RedisBroker|SQSBroker
     * @throws \Leptir\Exception\BrokerFactoryException
     */
    public static function factory(array $config)
    {
        if (!isset($config['type'])) {
              throw new BrokerFactoryException(BrokerFactoryException::BROKER_TYPE_NOT_DEFINED);
        }
        $brokerType = $config['type'];
        $options = isset($config['options']) ? $config['options'] : array();

        switch($brokerType)
        {
            case self::BROKER_SQS:
                return new SQSBroker($options);
            case self::BROKER_MONGO:
                return new MongoBroker($options);
            case self::BROKER_REDIS:
                return new RedisBroker($options);
            default:
                throw new BrokerFactoryException(BrokerFactoryException::BROKER_NOT_SUPPORTED);
        }
    }
}
