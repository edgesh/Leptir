<?php

namespace Leptir\Broker\SimpleBroker;

use Leptir\Exception\BrokerFactoryException;

class SimpleBrokerFactory
{
    const BROKER_SQS = 'sqs';
    const BROKER_MONGO = 'mongodb';
    const BROKER_REDIS = 'redis';
    const BROKER_DOCTRINE = 'doctrine';

    /**
     * Factory for creating all supported brokers.
     *
     * @param array $config
     * @return MongoSimpleBroker|RedisSimpleBroker|SQSSimpleBroker
     * @throws \Leptir\Exception\BrokerFactoryException
     */
    public static function factory(array $config)
    {
        if (!isset($config['type'])) {
              throw new BrokerFactoryException(BrokerFactoryException::BROKER_TYPE_NOT_DEFINED);
        }
        $brokerType = $config['type'];

        switch($brokerType)
        {
            case self::BROKER_SQS:
                return new SQSSimpleBroker($config);
            case self::BROKER_MONGO:
                return new MongoSimpleBroker($config);
            case self::BROKER_REDIS:
                return new RedisSimpleBroker($config);
            default:
                throw new BrokerFactoryException(BrokerFactoryException::BROKER_NOT_SUPPORTED);
        }
    }
}
