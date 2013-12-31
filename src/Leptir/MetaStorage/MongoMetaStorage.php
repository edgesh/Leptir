<?php

namespace Leptir\MetaStorage;

use Zend\Stdlib\ArrayUtils;

class MongoMetaStorage extends AbstractMetaStorage
{
    const DEFAULT_HOST = 'localhost';
    const DEFAULT_PORT = 27017;
    const DEFAULT_DATABASE = 'leptir';
    const DEFAULT_COLLECTION = 'info';

    /**
     * @var \MongoCollection|null $mongoConnection
     */
    private $mongoConnection = null;

    public function __construct(array $config = array())
    {
        $connection = isset($config['connection']) ? $config['connection'] : array();

        $host = isset($connection['host']) ? $connection['host'] : self::DEFAULT_HOST;
        $port = isset($connection['port']) ? $connection['port'] : self::DEFAULT_PORT;
        $database = isset($connection['database']) ? $connection['database'] : self::DEFAULT_DATABASE;
        $collection = isset($connection['collection']) ? $connection['collection'] : self::DEFAULT_COLLECTION;

        $options = array(
            'connect' => true
        );
        if (isset($connection['options'])) {
            $options = ArrayUtils::merge($options, $connection['options']);
        }
        $hostConnection = new \MongoClient('mongodb://' . $host . ':' . (string)$port, $options);
        $dbConnection = $hostConnection->$database;
        $this->mongoConnection = $dbConnection->$collection;
    }

    public function saveMetaInfo(\ArrayObject $object)
    {
        if (isset($object['id'])) {
            $object['_id'] = $object['id'];
            unset($object['id']);
        }
        foreach ($object as $key => $value) {
            if ($value instanceof \DateTime) {
                $mongoDate = new \MongoDate($value->format('U'));
                $object[$key] = $mongoDate;
            }
        }
        $this->mongoConnection->save($object);
    }

    public function loadMetaInfoById($id)
    {
        $object = $this->mongoConnection->findOne(
            array(
                '_id' => $id
            )
        );
        if (!$object) {
            return null;
        }
        $object['id'] = $object['_id'];
        unset($object['_id']);
        return new \ArrayObject($object);
    }
}
