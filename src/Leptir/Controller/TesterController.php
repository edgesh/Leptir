<?php

namespace Leptir\Controller;

use Leptir\Task\Test\SlowTask;
use Leptir\Task\Test\TestTask;
use Zend\Console\Request;

class TesterController extends BaseLeptirController
{
    public function pushAction()
    {
        $request = $this->getRequest();

        if (!$request instanceof Request) {
            throw new \RuntimeException('You can only use this action from a console.');
        }


        $priority = intval($request->getParam('priority', 0));
        $quantity = intval($request->getParam('number', 1));
        $delaySeconds = intval($request->getParam('delaySeconds', 0));

        $broker = $this->getBroker();
        $now = new \DateTime();
        if ($delaySeconds > 0) {
            $now->add(new \DateInterval('PT' . $delaySeconds . 'S'));
        }

        for($i=0; $i<$quantity; $i++) {
            $task = $this->getTaskFromName($request->getParam('taskName'));
            $broker->pushTask($task, $now, $priority);
        }
    }

    private function getTaskFromName($name)
    {
        switch($name) {
            case 'test':
                return new TestTask(
                    array(
                        'message' => 'Test message'
                    )
                );
            case 'slow':
                return new SlowTask();
            default:
                $this->writeErrorLine('Task not recognized: ' . $name);
                break;
        }
    }

}
