<?php

namespace Leptir\Broker;

use Leptir\Task\BaseTask;
use Predis\Client;

/**
 * Class MongoBroker
 * @package Leptir\Broker
 */

class RedisBroker extends AbstractBroker
{
    private $redisClient = null;
    private $key = 'leptir:tasks';

    public function __construct(array $config = array())
    {
        $scheme = isset($config['scheme']) ? $config['scheme'] : 'tcp';
        $host = isset($config['host']) ? $config['host'] : '127.0.0.1';
        $port = intval(isset($config['port']) ? $config['port'] : 6379);
        $database = intval(isset($config['database']) ? $config['database'] : 0);
        $this->key = isset($config['key']) ? $config['key'] : 'leptir:tasks';

        $this->redisClient = new Client(
            array(
                'scheme' => $scheme,
                'host' => $host,
                'port' => $port,
                'database' => $database
            )
        );
    }

    /**
     * Send a new task to the broker
     *
     * @param BrokerTask $task
     */
    public function pushBrokerTask(BrokerTask $task)
    {
        $array = $task->getArrayCopy();
        $encoded = json_encode($array);
        $this->redisClient->lpush($this->key, $encoded);
    }

    /**
     * Receive one task from broker.
     *
     * @return BrokerTask
     */
    public function popBrokerTask()
    {
        $message = $this->redisClient->rpop($this->key);
        if (is_null($message) || empty($message)) {
            return null;
        }
        $decoded = json_decode($message, true);
        return BrokerTask::createFromArrayCopy(new \ArrayObject($decoded));
    }

    /**
     * Method that returns number of un-processed tasks.
     *
     * @return int
     */
    public function getTasksCount()
    {
        return $this->redisClient->llen($this->key);
    }
}
