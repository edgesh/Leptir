<?php

namespace LeptirTest\Broker;

use Leptir\Broker\BrokerTask;
use LeptirTest\Mocks\MockBroker;
use LeptirTest\Mocks\MockServiceManager;
use LeptirTest\Mocks\MockSimpleBroker;
use LeptirTest\Mocks\MockTask;
use Zend\ServiceManager\Exception;
use Zend\Test\PHPUnit\Controller\AbstractControllerTestCase;

class BrokerTest extends AbstractControllerTestCase
{
    /**
     * @var MockBroker
     */
    private $broker = null;

    public function setUp()
    {
        $this->broker = new MockBroker();

        $sb1 = new MockSimpleBroker(array(
                'configuration' => array(
                    'priority' => 1
                )
            )
        );
        $sb1->pushBrokerTask(new BrokerTask(new MockTask()));
        $sb1->pushBrokerTask(new BrokerTask(new MockTask()));
        $sb1->pushBrokerTask(new BrokerTask(new MockTask()));
        $this->broker->addSimpleBroker($sb1);
    }

    public function testBroker()
    {
        $sb2 = new MockSimpleBroker(array(
                'configuration' => array(
                    'priority' => 2
                )
            )
        );

        $sb2->pushBrokerTask(new BrokerTask(new MockTask()));
        $sb2->pushBrokerTask(new BrokerTask(new MockTask()));

        $sb5 = new MockSimpleBroker(array(
                'configuration' => array(
                    'priority' => 5
                )
            )
        );
        $sb5->pushBrokerTask(new BrokerTask(new MockTask()));
        $sb5->pushBrokerTask(new BrokerTask(new MockTask()));
        $sb5->pushBrokerTask(new BrokerTask(new MockTask()));

        $sb11 = new MockSimpleBroker(array(
                'configuration' => array(
                    'priority' => 11
                )
            )
        );
        $sb11->pushBrokerTask(new BrokerTask(new MockTask()));
        $sb11->pushBrokerTask(new BrokerTask(new MockTask()));
        $sb11->pushBrokerTask(new BrokerTask(new MockTask()));

        $this->broker->addSimpleBroker($sb2);
        $this->broker->addSimpleBroker($sb11);
        $this->broker->addSimpleBroker($sb5);

        $this->assertEquals(
            $this->broker->testGetBrokerForPriority(-1)->getPriority(),
            1
        );
        $this->assertEquals(
            $this->broker->testGetBrokerForPriority(1)->getPriority(),
            1
        );
        $this->assertEquals(
            $this->broker->testGetBrokerForPriority(2)->getPriority(),
            2
        );
        $this->assertEquals(
            $this->broker->testGetBrokerForPriority(3)->getPriority(),
            5
        );
        $this->assertEquals(
            $this->broker->testGetBrokerForPriority(4)->getPriority(),
            5
        );
        $this->assertEquals(
            $this->broker->testGetBrokerForPriority(5)->getPriority(),
            5
        );
        $this->assertEquals(
            $this->broker->testGetBrokerForPriority(6)->getPriority(),
            11
        );
        $this->assertEquals(
            $this->broker->testGetBrokerForPriority(12)->getPriority(),
            11
        );

        $probability = $this->broker->testGetProbabilities();

        $this->assertTrue(
            $probability[0] > $probability[1] &&
            $probability[1] > $probability[2] &&
            $probability[2] > $probability[3]
        );

        $count = array();

        for($i=0; $i<10000; $i++) {
            $broker = $this->broker->testGetBrokerForNextTask();
            $count[$broker->getPriority()] = isset($count[$broker->getPriority()]) ?
                $count[$broker->getPriority()] + 1 : 1;
        }

        $this->assertTrue(
            $count[1] > $count[2] &&
            $count[2] > $count[5] &&
            $count[5] > $count[11]
        );

        $sb2->popBrokerTask();
        $sb2->popBrokerTask();
        $count = array();

        for($i=0; $i<100; $i++) {
            $broker = $this->broker->testGetBrokerForNextTask();
            $count[$broker->getPriority()] = isset($count[$broker->getPriority()]) ?
                $count[$broker->getPriority()] + 1 : 1;
        }

        $this->assertFalse(
            isset($count[2])
        );
    }

    public function testServiceInjection()
    {

        $this->broker->setServiceLocator(new MockServiceManager());

        $this->broker->pushTask(new MockTask());

        $brokerTask = $this->broker->getOneTask();

        $this->assertNotNull($brokerTask);

        $this->assertInstanceof('Leptir\Task\BaseTask', $brokerTask->getTask());
        $this->assertInstanceof('Zend\ServiceManager\ServiceLocatorAwareInterface', $brokerTask->getTask());

        /** @var MockTask $task */
        $task = $brokerTask->getTask();

        $this->assertInstanceof('Zend\ServiceManager\ServiceLocatorAwareInterface', $task);

        $serviceLocator = $task->getServiceLocator();
        $this->assertNotNull($serviceLocator);
        $this->assertInstanceof('LeptirTest\Mocks\MockServiceManager', $serviceLocator);
    }
}
