<?php

namespace LeptirTest\Mocks;

use Leptir\MetaStorage\AbstractMetaStorage;
use Leptir\MetaStorage\MetaStorage;

class MockMetaStorage extends MetaStorage
{
    public function __construct(AbstractMetaStorage $storage)
    {
        $this->storageList[] = $storage;
    }

    public function testGetSavedInfo()
    {
        return $this->storageList[0]->info;
    }

}
