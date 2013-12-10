<?php

namespace LeptirTest\Mocks;

use Leptir\MetaStorage\AbstractMetaStorage;

class MockMetaBackend extends AbstractMetaStorage
{

    public $info = null;

    public function __construct(array $config = array())
    {
        $this->info = null;
    }

    public function saveMetaInfo(\ArrayObject $object)
    {
        $this->info = $object;
    }

    public function testGetSavedInfo()
    {
        return $this->info;
    }

    public function loadMetaInfoById($id)
    {
        return $this->info;
    }


}