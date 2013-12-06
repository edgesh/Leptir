<?php

namespace LeptirTest\Mocks;

use Zend\ServiceManager\ServiceLocatorInterface;

class MockServiceManager implements ServiceLocatorInterface
{
    /**
     * Retrieve a registered instance
     *
     * @param  string $name
     * @throws \Exception\ServiceNotFoundException
     * @return object|array
     */
    public function get($name)
    {
        return null;
    }

    /**
     * Check for a registered instance
     *
     * @param  string|array $name
     * @return bool
     */
    public function has($name)
    {
        return true;
    }

}