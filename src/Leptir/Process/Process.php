<?php

namespace Leptir\Process;

/**
 * process descriptor using pcntl functions
 *
 * Class Process
 * @package Leptir\Daemon
 */
class Process
{
    private $pid;
    private $pidFilePath = '';

    public function __construct($pid, $options = array())
    {
        $this->pid = $pid;
        if (isset($options['pid_path'])) {
            $this->pidFilePath = $options['pid_path'];
        }
    }

    public function getPidFilePath()
    {
        return $this->pidFilePath;
    }

    public function getPid()
    {
        return $this->pid;
    }

    public function isActive()
    {
        if (file_exists('/proc/' . $this->getPid())) {
            return true;
        }
        exec('ps ' . $this->getPid(), $output, $result);
        return count($output) >= 2;
    }

    public function waitToFinish()
    {
        if (!$this->isActive()) {
            return;
        }

        while($this->isActive()) {
            usleep(200000);
            $this->cleanupIfZombie();
        }
        return;
    }

    public function cleanupIfZombie()
    {
        pcntl_waitpid($this->getPid(), $status, WNOHANG | WUNTRACED);
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

    public function sendSignal($signo)
    {
        posix_kill($this->getPid(), $signo);
    }

}
