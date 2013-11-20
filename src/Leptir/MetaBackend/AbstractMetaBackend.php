<?php

namespace Leptir\MetaBackend;


abstract class AbstractMetaBackend
{
    abstract public function __construct(array $config = array());
    abstract public function saveMetaInfo(\ArrayObject $object);
}
