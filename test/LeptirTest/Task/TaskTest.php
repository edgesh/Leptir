<?php

namespace LeptirTest\Task;

use Leptir\Exception\LeptirTaskException;
use LeptirTest\Mocks\MockMetaBackend;
use LeptirTest\Mocks\MockPHPErrorTask;
use LeptirTest\Mocks\MockSlowTask;
use LeptirTest\Mocks\MockMetaStorage;
use LeptirTest\Mocks\MockTaskWithStates;
use Zend\Test\PHPUnit\Controller\AbstractControllerTestCase;

class TaskTest extends AbstractControllerTestCase
{
    /**
     * @var MockSlowTask
     */
    private $task = null;

    public function setUp()
    {
        $this->task = new MockSlowTask();
    }

    public function testTimeLimit()
    {
        $backend = new MockMetaStorage(new MockMetaBackend());
        $start = microtime(true);
        try{
            /**
             * Task would usually run for 10 seconds
             * We are testing execution time limiting here
             */
            $this->task->execute(1, $backend, true);
        } catch (LeptirTaskException $e) {
            $this->assertEquals($e->getCode(), LeptirTaskException::TIME_LIMIT_EXCEEDED);
        }
        $end = microtime(true);
        $this->assertLessThan(2, $end - $start);
        $this->assertNotNull($backend->testGetSavedInfo());
    }

    public function testPHPError()
    {
        $backend = new MockMetaStorage(new MockMetaBackend());
        $task = new MockPHPErrorTask();
        $task->execute(0, $backend, true);

        $this->assertNotNull($backend->testGetSavedInfo());

        $info = $backend->testGetSavedInfo();

        $this->assertArrayHasKey('respM', $info);
    }

    public function setTaskStateChange()
    {
        $backend = new MockMetaStorage(new MockMetaBackend());
        $task = new MockTaskWithStates();
        $task->execute(0, $backend, true);

        $lastState = $task->getLastState();
        $flow = $task->getExecutionFlow();
        $this->assertEquals(3, $lastState);
        $this->assertEquals($flow, array(1, 2, 3));
    }
}
