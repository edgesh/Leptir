<?php

namespace Leptir\Broker\SimpleBroker;

use Leptir\Broker\BrokerTask;
use Predis\Client;

/**
 * Class MongoBroker
 * @package Leptir\Broker
 */

class RedisSimpleBroker extends AbstractSimpleBroker
{
    private $redisClient = null;
    private $key = 'leptir:ztasks';

    public function __construct(array $config = array())
    {
        parent::__construct($config);

        $connection = isset($config['connection']) ? $config['connection'] : array();

        $scheme = isset($connection['scheme']) ? $connection['scheme'] : 'tcp';
        $host = isset($connection['host']) ? $connection['host'] : '127.0.0.1';
        $port = intval(isset($connection['port']) ? $connection['port'] : 6379);
        $database = intval(isset($connection['database']) ? $connection['database'] : 0);
        $this->key = isset($connection['key']) ? $connection['key'] : 'leptir:ztasks';

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
     * @param BrokerTask $task
     * @return mixed|void
     */
    public function pushBrokerTask(BrokerTask $task)
    {
        $score = $this->getTimeStampForDate($task->getTimeOfExecution());
        $array = $task->getArrayCopy();
        $encoded = json_encode($array);
        $this->redisClient->zadd($this->key, $score, $encoded);
    }

    /**
     * Receive one task from broker.
     *
     * @return BrokerTask
     */
    public function popBrokerTask()
    {
        $responses = $this->redisClient->pipeline(
            function ($pipe) {
                $pipe->zrange($this->key, 0, 1);
                $pipe->zremrangebyrank($this->key, 0, 0);
            }
        );
        if (is_null($responses) || empty($responses) || count($responses) !== 2) {
            return null;
        }
        $message = $responses[0];
        if (is_null($message) || empty($message)) {
            return null;
        }

        $decoded = json_decode($message[0], true);
        return BrokerTask::createFromArrayCopy(new \ArrayObject($decoded));
    }

    /**
     * Method that returns number of un-processed tasks.
     *
     * @return int
     */
    protected function tasksCount()
    {
        $currentScore = $this->getTimeStampForDate(new \DateTime()) + 1;
        return $this->redisClient->zcount($this->key, '-inf', $currentScore);
    }
}
