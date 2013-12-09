<?php

namespace Leptir\Daemon;

/**
 * process descriptor using pcntl functions
 *
 * Class Process
 * @package Leptir\Daemon
 */
class Process
{
    private $pid;
    private $processFinished = false;

    public function __construct($pid = -1)
    {
        if ($pid > 0) {
            $this->pid = $pid;
        } else {
            $this->pid = getmypid();
        }
        $this->processFinished = false;
    }

    final public function getPID()
    {
        return $this->pid;
    }

    public function isActive()
    {
        if ($this->processFinished === true) {
            return false;
        }
        return self::processIsRunning($this->pid);
    }

    public function waitToFinish()
    {
        while ($this->isActive()) {
            pcntl_waitpid($this->pid, $status);
        }
        $this->processFinished = true;
    }

    public function updateState()
    {
        $check = pcntl_waitpid($this->pid, $status, WNOHANG | WUNTRACED);
        if ($check === $this->pid) {
            $this->processFinished = true;
        }
    }

    public static function processIsRunning($pid)
    {
        if (is_null($pid) || !$pid) {
            return false;
        }
        exec('ps ' . $pid, $output, $result);
        if (count($output) >= 2) {
            return true;
        }
        return false;
    }

    protected function setPriority($priority)
    {
        if ($this->getPID()) {
            return pcntl_setpriority($priority, $this->getPID());
        }
        return false;
    }

    protected function getPriority()
    {
        return pcntl_getpriority($this->getPID());
    }
}
