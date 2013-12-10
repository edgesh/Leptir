<?php

namespace Leptir\MetaStorage;

class MetaStorage
{
    protected $storageList = array();

    public function __construct(array $conf = array())
    {
        foreach($conf as $storageConfig)
        {
            $storage = MetaStorageFactory::factory($storageConfig);
            $this->storageList[] = $storage;
        }
    }

    public function saveMetaInfo(\ArrayObject $metaInfo)
    {
        /** @var $storage AbstractMetaStorage */
        foreach($this->storageList as $storage) {
            $storage->saveMetaInfo($metaInfo);
        }
    }

}