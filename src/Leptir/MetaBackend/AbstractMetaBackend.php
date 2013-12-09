<?php

namespace Leptir\MetaBackend;


abstract class AbstractMetaBackend
{
    private $configNumberOfRecordsToKeep = -1;

    public function __construct(array $config = array())
    {
        if (isset($config['capped_size'])) {
            $this->configNumberOfRecordsToKeep = intval($config['capped_size']);
        }
    }
    abstract public function saveMetaInfo(\ArrayObject $object);

    protected function numberOfRecordsToKeep()
    {
        return $this->configNumberOfRecordsToKeep;
    }
}
