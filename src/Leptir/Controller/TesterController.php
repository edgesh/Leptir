<?php

namespace Leptir\Controller;

use Leptir\Broker\BrokerFactory;
use Leptir\Task\Test\TestTask;
use Zend\Console\Request;
use Zend\Mvc\Controller\AbstractActionController;

class TesterController extends AbstractActionController
{
    public function pushAction()
    {
        $request = $this->getRequest();

        if (!$request instanceof Request) {
            throw new \RuntimeException('You can only use this action from a console.');
        }

        $serviceConfig = $this->serviceLocator->get('config');
        $leptirConfig = isset($serviceConfig['leptir']) ? $serviceConfig['leptir'] : array();

        $taskName = $request->getParam('taskName');

        $broker = BrokerFactory::factory($leptirConfig['broker']);

        switch($taskName)
        {
            case 'test':
                for ($i=0; $i<1; $i++) {
                    $task = new TestTask(
                        array(
                            'message' => 'Task num: ' . (string)$i
                        )
                    );
                    $broker->pushTask($task);
                }
                break;
        }
    }
}
