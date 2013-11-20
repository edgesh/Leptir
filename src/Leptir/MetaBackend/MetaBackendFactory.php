<?php

namespace Leptir\MetaBackend;

class MetaBackendFactory
{
    public static function factory($config)
    {
        $type = $config['type'];
        $options = $config['options'];

        switch($type) {
            case 'mongodb':
                return new MongoMetaBackend($options);
            default:
                return null;
        }
    }
}
