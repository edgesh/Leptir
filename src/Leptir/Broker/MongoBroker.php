<?php

namespace Leptir\Broker;

use Leptir\Exception\MongoBrokerException;
use Leptir\Task\BaseTask;
use Zend\Stdlib\ArrayUtils;

/**
 * Class MongoBroker
 * @package Leptir\Broker
 */

class MongoBroker extends AbstractBroker
{
    const DEFAULT_HOST = 'localhost';
    const DEFAULT_PORT = 27017;
    const DEFAULT_DATABASE = 'leptir';
    const DEFAULT_COLLECTION = 'tasks';


    /**
     * @var \MongoCollection|null $mongoConnection
     */
    private $mongoConnection = null;

    public function __construct(array $config = array())
    {
        $host = isset($config['host']) ? $config['host'] : self::DEFAULT_HOST;
        $port = isset($config['port']) ? $config['port'] : self::DEFAULT_PORT;
        $database = isset($config['database']) ? $config['database'] : self::DEFAULT_DATABASE;
        $collection = isset($config['collection']) ? $config['collection'] : self::DEFAULT_COLLECTION;

        $options = array(
            'connect' => true
        );

        if (isset($config['options'])) {
            $options = ArrayUtils::merge($options, $config['options']);
        }

        $hostConnection = new \MongoClient('mongodb://' . $host . ':' . (string)$port, $options);
        $dbConnection = $hostConnection->$database;
        $this->mongoConnection = $dbConnection->$collection;
    }

    protected function pushBrokerTask(BrokerTask $brokerTask)
    {
        $arrayCopy = $brokerTask->getArrayCopy();
        $this->saveObject($arrayCopy);
    }

    /**
     * Receive one task from broker.
     *
     * @return BrokerTask
     */
    public function popBrokerTask()
    {
        $arrayCopy = $this->fetchNextObject();
        if (!is_null($arrayCopy) && !empty($arrayCopy)) {
            return BrokerTask::createFromArrayCopy($arrayCopy);
        }
        return null;
    }

    /**
     * Method that returns number of un-processed tasks.
     *
     * @return int
     */
    public function getTasksCount()
    {
        return $this->mongoConnection->count();
    }


    /**
     * Saves Array object to database
     *
     * @param \ArrayObject $object
     */
    private function saveObject(\ArrayObject $object)
    {
        $this->mongoConnection->save($object);
    }

    /**
     * TODO priority support
     *
     * @returns \ArrayObject
     */
    private function fetchNextObject()
    {
        /**
         * Mongo operation which finds the oldest document and removes it in one atomic operation
         */
        $object = $this->mongoConnection->findAndModify(
            array(),
            null,
            null,
            array(
                'sort' => array(
                    '_id' => 1
                ),
                'remove' => true
            )
        );

        if (is_null($object) || !$object) {
            return null;
        }
        $object = new \ArrayObject($object);

        return $object;
    }
}
