<?php

namespace Leptir\MetaBackend;

use Zend\Stdlib\ArrayUtils;

class MongoMetaBackend extends AbstractMetaBackend
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
}
