<?php

namespace Leptir\Daemon;

use Leptir\Broker\AbstractSimpleBroker;
use Leptir\Broker\Broker;
use Leptir\Broker\BrokerTask;
use Leptir\Exception\DaemonException;
use Leptir\Exception\DaemonProcessException;
use Leptir\Logger\LeptirLoggerTrait;
use Leptir\MetaBackend\AbstractMetaBackend;

/**
 * Class Daemon
 * @package Leptir\Daemon
 */
class Daemon
{
    use LeptirLoggerTrait;

    private $EMPTY_QUEUE_SLEEP_TIME;
    private $WORKERS_ACTIVE_SLEEP_TIME;
    private $NUMBER_OF_WORKERS;
    private $TASK_EXECUTION_TIME;

    private $daemonProcess;
    private $isRunning = true;
    private $metaBackend = null;

    public function __construct (
        Broker $broker,
        array $daemonConfig,
        array $loggers = array(),
        AbstractMetaBackend $metaBackend = null
    ) {
        $this->loggers = $loggers;

        try {
            $this->daemonProcess = new DaemonProcess();
        } catch (DaemonProcessException $e) {
            $this->logError($e->getMessage());
            exit(1);
        }
        $this->broker = $broker;

        $this->metaBackend = $metaBackend;
        $this->isRunning = true;

        $configuration = $daemonConfig['configuration'];

        $this->EMPTY_QUEUE_SLEEP_TIME = $this->secondsToMicroseconds($configuration['empty_queue_sleep_time']);
        $this->WORKERS_ACTIVE_SLEEP_TIME = $this->secondsToMicroseconds($configuration['workers_active_sleep_time']);
        $this->NUMBER_OF_WORKERS = $configuration['number_of_workers'];
        $this->TASK_EXECUTION_TIME = $configuration['task_execution_time'];

        // ticks
        declare(
            ticks = 1
        );

        // signal handlers
        $this->setupSignalHandler(SIGTERM);
    }

    final public function start()
    {
        if ($this->daemonProcess->isActive()) {
            throw new DaemonException(DaemonException::DAEMON_ALREADY_RUNNING);
        }
        $this->daemonProcess->startProcess();
        $this->run();
    }

    private function run()
    {
        while ($this->isRunning) {
            if ($this->daemonProcess->activeChildrenCount() >= $this->NUMBER_OF_WORKERS) {
                if ($this->WORKERS_ACTIVE_SLEEP_TIME) {
                    usleep($this->WORKERS_ACTIVE_SLEEP_TIME);
                }
            } else {
                $queueSize = $this->broker->getBoundedNumberOfTasks($this->NUMBER_OF_WORKERS);

                if ($queueSize) {
                    $numberToSpawn = min(
                        $queueSize,
                        $this->NUMBER_OF_WORKERS -
                        $this->daemonProcess->activeChildrenCount()
                    );

                    for ($i=0; $i<$numberToSpawn; $i++) {
                        /** @var BrokerTask $task */
                        $task = $this->broker->getOneTask();
                        if ($task) {
                            $task->subscribeLoggers($this->loggers);
                            $this->daemonProcess->createProcessForTask(
                                $task,
                                $this->TASK_EXECUTION_TIME,
                                $this->metaBackend
                            );
                        }
                    }
                } else {
                    if ($this->EMPTY_QUEUE_SLEEP_TIME > 0) {
                        usleep($this->EMPTY_QUEUE_SLEEP_TIME);
                    }
                }
            }
            $this->daemonProcess->updateState();
        }

        $this->logInfo(
            "Somebody stopped a little butterfly. He's gonna wait for all the children to finish."
        );

        $this->daemonProcess->waitForProcessesToFinish();
    }

    public function stopDaemon()
    {
        $this->daemonProcess->waitForProcessesToFinish();
    }

    public function __signalHandler($signo)
    {
        $this->isRunning = false;
        $this->logInfo(
            'Signal ' . (string)$signo . ' received. Shutdown procedure triggered.'
        );
        return 1;
    }

    private function setupSignalHandler($signal)
    {
        pcntl_signal(
            $signal,
            array(
                $this,
                '__signalHandler'
            )
        );
    }

    private function secondsToMicroseconds($seconds)
    {
        return (int)(1.0 * $seconds * 1000000);
    }

    protected function formatLogMessage($message)
    {
        return sprintf('[%d](:MASTER:) %s', getmypid(), $message);
    }
}
