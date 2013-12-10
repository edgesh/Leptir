<?php

namespace Leptir\MetaStorage;

use Predis\Client;

/**
 * Redis meta storage saves information in two data structures
 *      * hash map - task id as map (saved under $key)
 *      * set - sorted by time of execution (execution time stamp is score and taskId is value)
 *            - saved under $key:{taskId}
 *
 * Class RedisMetaStorage
 * @package Leptir\MetaStorage
 */
class RedisMetaStorage extends AbstractMetaStorage
{
    /**
     * @var \Predis\Client
     */
    private $redisClient;

    /**
     * Set key
     *
     * @var string
     */
    private $key;

    function __construct()
    {
        $connection = isset($config['connection']) ? $config['connection'] : array();

        $scheme = isset($connection['scheme']) ? $connection['scheme'] : 'tcp';
        $host = isset($connection['host']) ? $connection['host'] : '127.0.0.1';
        $port = intval(isset($connection['port']) ? $connection['port'] : 6379);
        $database = intval(isset($connection['database']) ? $connection['database'] : 0);

        $this->key = isset($connection['key']) ? $connection['key'] : 'leptir:taskinfo';

        $this->redisClient = new Client(
            array(
                'scheme' => $scheme,
                'host' => $host,
                'port' => $port,
                'database' => $database
            )
        );
    }

    public function saveMetaInfo(\ArrayObject $object)
    {
        $taskId = $object['id'];
        $key = $this->key . ':' . $taskId;

        $this->redisClient->set($key, json_encode($object));
    }

    public function loadMetaInfoById($id)
    {
        $key = $this->key . ':' . $id;
        $object = $this->redisClient->get($key);

        if (!$object) {
            return null;
        }
        return new \ArrayObject(json_decode($object, true));
    }


}
 