<?php

namespace Leptir\Daemon;

use Leptir\Broker\BrokerTask;
use Leptir\Exception\DaemonProcessException;
use Leptir\MetaBackend\AbstractMetaBackend;

class DaemonProcess
{
    const PID_FILE = '/var/run/leptir.pid';

    private $pid;
    private $childProcesses = array();

    public function __construct()
    {
        $this->pid = $this->getPID();
    }

    public function isActive()
    {
        $this->pid = $this->getPID();
        return $this->processIsRunning();
    }

    public function waitForProcessesToFinish()
    {
        while (!empty($this->childProcesses)) {
            $this->updateState();
        }
        $this->deletePID();
    }

    public function updateState()
    {
        $this->childProcesses = $this->getStillActiveChildren();
    }

    public function startProcess()
    {
        $pid = getmypid();
        $this->pid = $pid;
        @file_put_contents($this->getPIDFilename(), $pid);
        if (error_get_last()) {
            throw new DaemonProcessException(DaemonProcessException::UNABLE_TO_ACCESS_PID_FILE);
        }
    }

    public function createProcessForTask(BrokerTask $task, $executionTime, AbstractMetaBackend $metaBackend = null)
    {
        $pid = pcntl_fork();

        if ($pid == -1) {
            exit(1);
        } elseif ($pid) {
            $this->childProcesses[] = new Process($pid);
        } else {
            // child process
            $task->execute($executionTime, $metaBackend);
            exit(1);
        }
    }

    public function activeChildrenCount()
    {
        return count($this->childProcesses);
    }

    private function getPID()
    {
        if (file_exists($this->getPIDFilename())) {
            $content = @file_get_contents($this->getPIDFilename());

            if (error_get_last()) {
                throw new DaemonProcessException(DaemonProcessException::UNABLE_TO_ACCESS_PID_FILE);
            }

            return intval($content);
        }
        return null;
    }

    private function processIsRunning()
    {
        return Process::processIsRunning($this->pid);
    }

    private function deletePID()
    {
        unlink($this->getPIDFilename());
    }

    private function getStillActiveChildren()
    {
        $stillActive = array();
        /** @var Process $process */
        foreach ($this->childProcesses as $process) {
            $process->updateState();
            if ($process->isActive()) {
                $stillActive[] = $process;
            }
        }
        return $stillActive;
    }

    /**
     * Method returns PID filename. Functionality extracted to separate method for testing purpose.
     * Replacing constant filename with dynamically generated random filename enables to run multiple
     * workers on a single box.
     */
    private function getPIDFilename()
    {
        return self::PID_FILE;
    }
}
