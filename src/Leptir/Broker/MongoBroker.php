<?php

namespace Leptir\Broker;

use Zend\Stdlib\ArrayUtils;

/**
 * Class MongoBroker
 * @package Leptir\Broker
 */

class MongoBroker extends AbstractSimpleBroker
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
        parent::__construct($config);

        $connection = isset($config['connection']) ? $config['connection'] : array();

        $host = isset($connection['host']) ? $connection['host'] : self::DEFAULT_HOST;
        $port = isset($connection['port']) ? $connection['port'] : self::DEFAULT_PORT;
        $database = isset($connection['database']) ? $connection['database'] : self::DEFAULT_DATABASE;
        $collection = isset($connection['collection']) ? $connection['collection'] : self::DEFAULT_COLLECTION;

        $connectionOptions = array(
            'connect' => true
        );

        if (isset($connection['options'])) {
            $connectionOptions = ArrayUtils::merge($connectionOptions, $connection['options']);
        }

        $hostConnection = new \MongoClient('mongodb://' . $host . ':' . (string)$port, $connectionOptions);
        $dbConnection = $hostConnection->$database;
        $this->mongoConnection = $dbConnection->$collection;
    }

    public function pushBrokerTask(BrokerTask $brokerTask)
    {
        $arrayCopy = $brokerTask->getArrayCopy();
        $id = $this->getIdForDate($brokerTask->getTimeOfExecution());
        $arrayCopy['_id'] = $id;
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
        return $this->mongoConnection->find(
            array(
                '_id' => array(
                    '$lte' => $this->getCurrentId()
                )
            )
        )->count();
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
     * @return \ArrayObject|null
     */
    private function fetchNextObject()
    {
        /**
         * Mongo operation which finds the oldest document and removes it in one atomic operation
         */
        $object = $this->mongoConnection->findAndModify(
            array(
                '_id' => array(
                    '$lte' => $this->getCurrentId()
                )
            ),
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

    private function getIdForDate(\DateTime $time = null)
    {
        $timestamp = $this->getTimeStampForDate($time);
        return sprintf('%08x%.8F', $timestamp, lcg_value());
    }

    private function getCurrentId()
    {
        $now = new \DateTime();
        return sprintf('%08x%.8F', $now->getTimestamp(), 0.0);
    }
}
