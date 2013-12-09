<?php

namespace Leptir\Controller;

use Leptir\Broker\Broker;
use Leptir\Task\Test\SlowTask;
use Leptir\Task\Test\TestTask;
use Zend\Console\Request;
use Zend\Mvc\Controller\AbstractActionController;

class TesterController extends BaseLeptirController
{
    public function pushAction()
    {
        $request = $this->getRequest();

        if (!$request instanceof Request) {
            throw new \RuntimeException('You can only use this action from a console.');
        }

        $taskName = $request->getParam('taskName');
        $broker = $this->getBroker();

        switch($taskName)
        {
            case 'test':
                for ($i=0; $i<2; $i++) {
                    $task = new TestTask(
                        array(
                            'message' => 'Task num: ' . (string)$i
                        )
                    );

                    $now = new \DateTime();
                    $now->add(new \DateInterval('PT30S'));
                    if ($i == 0) {
                        $broker->pushTask($task, $now, 1);
                    } else {
                        $broker->pushTask($task, null, 1);
                    }
                }
                break;
            case 'slow':
                $task = new SlowTask();
                $broker->pushTask($task);
                $task = new SlowTask();
                $broker->pushTask($task);
                break;
        }
    }
}
