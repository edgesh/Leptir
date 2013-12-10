<?php

namespace Leptir\MetaStorage;

class MetaStorageFactory
{
    public static function factory($config)
    {
        $type = $config['type'];

        switch($type) {
            case 'mongodb':
                return new MongoMetaStorage($config);
            case 'redis':
                return new RedisMetaStorage($config);
            default:
                return null;
        }
    }
}
