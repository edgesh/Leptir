<?php

namespace Leptir\MetaBackend;

class MetaBackendFactory
{
    public static function factory($config)
    {
        $type = $config['type'];

        switch($type) {
            case 'mongodb':
                return new MongoMetaBackend($config);
            default:
                return null;
        }
    }
}
