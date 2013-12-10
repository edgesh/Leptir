<?php

namespace LeptirTest\Task;

use Leptir\Exception\LeptirTaskException;
use LeptirTest\Mocks\MockMetaBackend;
use LeptirTest\Mocks\MockPHPErrorTask;
use LeptirTest\Mocks\MockSlowTask;
use LeptirTest\Mocks\MockMetaStorage;
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
            $this->task->execute(1, $backend);
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
        $task->execute(0, $backend);

        $this->assertNotNull($backend->testGetSavedInfo());

        $info = $backend->testGetSavedInfo();

        $this->assertTrue(isset($info['respM']));
        $this->assertEquals($info['respM'], 'Task exited with exception: Very nice! How much?');
    }
}
