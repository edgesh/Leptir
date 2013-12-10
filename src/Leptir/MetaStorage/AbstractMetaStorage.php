<?php

namespace Leptir\MetaStorage;


abstract class AbstractMetaStorage
{
    private $configNumberOfRecordsToKeep = -1;

    public function __construct(array $config = array())
    {
        if (isset($config['capped_size'])) {
            $this->configNumberOfRecordsToKeep = intval($config['capped_size']);
        }
    }

    abstract public function saveMetaInfo(\ArrayObject $object);

    abstract public function loadMetaInfoById($id);

    protected function numberOfRecordsToKeep()
    {
        return $this->configNumberOfRecordsToKeep;
    }
}
