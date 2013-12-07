<?php

namespace Leptir\Daemon;

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

    private $configEmptyQueueSleepTime;
    private $configAllWorkersActiveSleepTime;
    private $configNumberOfWorkers;
    private $configTaskExecutionTime;

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

        $this->configEmptyQueueSleepTime = $this->secondsToMicroseconds($configuration['empty_queue_sleep_time']);
        $this->configAllWorkersActiveSleepTime = $this->secondsToMicroseconds($configuration['workers_active_sleep_time']);
        $this->configNumberOfWorkers = $configuration['number_of_workers'];
        $this->configTaskExecutionTime = $configuration['task_execution_time'];

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
            if ($this->daemonProcess->activeChildrenCount() >= $this->configNumberOfWorkers) {
                if ($this->configAllWorkersActiveSleepTime) {
                    usleep($this->configAllWorkersActiveSleepTime);
                }
            } else {
                $queueSize = $this->broker->getBoundedNumberOfTasks($this->configNumberOfWorkers);

                if ($queueSize) {
                    $numberToSpawn = min(
                        $queueSize,
                        $this->configNumberOfWorkers -
                        $this->daemonProcess->activeChildrenCount()
                    );

                    for ($i=0; $i<$numberToSpawn; $i++) {
                        /** @var BrokerTask $task */
                        $task = $this->broker->getOneTask();
                        if ($task) {
                            $task->subscribeLoggers($this->loggers);
                            $this->daemonProcess->createProcessForTask(
                                $task,
                                $this->configTaskExecutionTime,
                                $this->metaBackend
                            );
                        }
                    }
                } else {
                    if ($this->configEmptyQueueSleepTime > 0) {
                        usleep($this->configEmptyQueueSleepTime);
                    }
                }
            }
            $this->daemonProcess->updateState();
        }

        $this->logInfo(
            "Somebody stopped a little butterfly. He's gonna wait for all the children to finish."
        );
        if ($this->daemonProcess->activeChildrenCount()) {
            $this->daemonProcess->waitForProcessesToFinish();
        } else {
            $this->logInfo(
                "There's no active children. Let's just land on the hard surface"
            );
        }
        $this->logInfo("All good. I'm out for now.");
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
