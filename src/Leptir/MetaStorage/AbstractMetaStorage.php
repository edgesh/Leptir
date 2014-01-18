<?php

namespace Leptir\MetaStorage;


abstract class AbstractMetaStorage
{
    abstract public function saveMetaInfo(\ArrayObject $object);
    abstract public function loadMetaInfoById($id);
}
