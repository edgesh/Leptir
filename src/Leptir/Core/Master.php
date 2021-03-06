<?php

namespace Leptir\Core;

use Leptir\Broker\Broker;
use Leptir\Broker\BrokerTask;
use Leptir\ErrorReport\ErrorReport;
use Leptir\Exception\LeptirTaskException;
use Leptir\Logger\LeptirLoggerTrait;
use Leptir\MetaStorage\MetaStorage;
use Leptir\Process\CurrentProcess;

/**
 * Master process
 *
 * Class Master
 * @package Leptir\Core
 */
class Master extends CurrentProcess
{
    use LeptirLoggerTrait;

    private $configEmptyQueueSleepTime;
    private $configAllWorkersActiveSleepTime;
    private $configNumberOfWorkers;
    private $configTaskExecutionTime;

    private $isRunning = true;
    private $metaBackend = null;
    private $errorReporting = null;

    private $isDaemon = false;

    public function __construct (
        Broker $broker,
        array $masterConfig,
        array $loggers = array(),
        MetaStorage $metaBackend = null,
        ErrorReport $errorReport = null
    ) {
        $configuration = $masterConfig['configuration'];

        $this->configEmptyQueueSleepTime = $this->secondsToMicroseconds(
            $configuration['empty_queue_sleep_time']
        );
        $this->configAllWorkersActiveSleepTime = $this->secondsToMicroseconds(
            $configuration['workers_active_sleep_time']
        );
        $this->configNumberOfWorkers = $configuration['number_of_workers'];
        $this->configTaskExecutionTime = $configuration['task_execution_time'];
        $this->configPidFilePath = isset($configuration['pid_path']) ?
            $configuration['pid_path'] : '/var/run/leptir.pid';

        parent::__construct(
            array(
                'pid_path' => $this->configPidFilePath
            )
        );

        $this->loggers = $loggers;
        $this->broker = $broker;
        $this->metaBackend = $metaBackend;
        $this->errorReporting = $errorReport;
        $this->isRunning = true;

        // ticks
        declare(
            ticks = 1
        );

        // signal handlers
        $this->setupSignalHandler(SIGTERM);
    }

    final public function start($isDaemon = false)
    {
        $this->isDaemon = $isDaemon;
        $this->writeToPidFile();
        $this->run();
    }

    private function run()
    {
        while ($this->isRunning) {
            if ($this->numberOfActiveChildren() >= $this->configNumberOfWorkers) {
                if ($this->configAllWorkersActiveSleepTime) {
                    usleep($this->configAllWorkersActiveSleepTime);
                }
            } else {
                $queueSize = $this->broker->getBoundedNumberOfTasks($this->configNumberOfWorkers);

                if ($queueSize) {
                    $numberToSpawn = min(
                        $queueSize,
                        $this->configNumberOfWorkers -
                        $this->numberOfActiveChildren()
                    );

                    for ($i=0; $i<$numberToSpawn; $i++) {
                        /** @var BrokerTask $task */
                        $task = $this->broker->getOneTask();
                        if ($task) {
                            $this->forkProcess(
                                null,
                                null,
                                array(
                                    $this,
                                    'childProcessJob'
                                ),
                                array(
                                    'task' => $task
                                )
                            );
                        }
                    }
                } else {
                    if ($this->configEmptyQueueSleepTime > 0) {
                        usleep($this->configEmptyQueueSleepTime);
                    }
                }
            }
            $this->cleanupZombieChildren();
        }

        $this->logInfo(
            "Somebody stopped a little butterfly. He's gonna wait for all the children to finish."
        );
        if ($this->numberOfActiveChildren() > 0) {
            $this->waitForChildrenToFinish();
        } else {
            $this->logInfo(
                "There's no active children. Let's just land on the hard surface"
            );
        }
        $this->removePidFile();
        $this->logInfo("All good. I'm out for now.");
    }

    public function signalHandler($signo)
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
                'signalHandler'
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

    protected function childProcessJob(BrokerTask $task)
    {
        $task->subscribeLoggers($this->loggers);
        try {
            $task->execute(
                $this->configTaskExecutionTime, // execution time
                $this->metaBackend, // backend storage
                true, // graceful
                $this->errorReporting
            );
        } catch (LeptirTaskException $e) { // time limit exceeded exception
        } catch (\Exception $e) {
        }
    }
}
