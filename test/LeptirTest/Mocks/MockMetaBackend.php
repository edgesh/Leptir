<?php

namespace LeptirTest\Mocks;

use Leptir\MetaBackend\AbstractMetaBackend;

class MockMetaBackend extends AbstractMetaBackend
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

}