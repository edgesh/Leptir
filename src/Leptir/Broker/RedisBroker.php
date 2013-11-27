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
    private $key = 'leptir:ztasks';

    public function __construct(array $config = array())
    {
        $scheme = isset($config['scheme']) ? $config['scheme'] : 'tcp';
        $host = isset($config['host']) ? $config['host'] : '127.0.0.1';
        $port = intval(isset($config['port']) ? $config['port'] : 6379);
        $database = intval(isset($config['database']) ? $config['database'] : 0);
        $this->key = isset($config['key']) ? $config['key'] : 'leptir:ztasks';

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
    public function getTasksCount()
    {
        $currentScore = $this->getTimeStampForDate(new \DateTime()) + 1;
        return $this->redisClient->zcount($this->key, '-inf', $currentScore);
    }
}
