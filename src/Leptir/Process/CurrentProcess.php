<?php

namespace Leptir\Process;

use Leptir\Exception\LeptirProcessException;

class CurrentProcess extends Process
{
    private $childProcesses = array();

    public function __construct(array $options = array())
    {
        if (function_exists('posix_getpid')) {
            parent::__construct(posix_getpid(), $options);
        } else {
            parent::__construct(getmypid(), $options);
        }
    }

    public function writeToPidFile()
    {
        if ($this->getPidFilePath()) {
            @file_put_contents($this->getPidFilePath(), $this->getPid());
            if (error_get_last()) {
                throw new LeptirProcessException(LeptirProcessException::PID_FILE_NOT_WRITABLE);
            }
        }
    }

    public function removePidFile()
    {
        if (file_exists($this->getPidFilePath())) {
            @unlink($this->getPidFilePath());
            if (error_get_last()) {
                throw new LeptirProcessException(LeptirProcessException::PID_FILE_NOT_WRITABLE);
            }
        }
    }

    protected function forkProcess(
        $parentCallable,
        $parentCallableArguments,
        $childCallable,
        $childCallableArguments
    ) {
        $pid = pcntl_fork();

        if (-1 === $pid) {
            throw new LeptirProcessException(LeptirProcessException::PROCESS_FORK_ERROR);
        }

        if (0 === $pid) {
            if (is_callable($childCallable)) {
                call_user_func_array($childCallable, $childCallableArguments);
            }
            exit(1);
        } else {
            $this->childProcesses[] = new Process($pid);
            if (is_callable($parentCallable)) {
                call_user_func_array($parentCallable, $parentCallableArguments);
            }
        }
    }

    protected function cleanupZombieChildren()
    {
        $stillActive = array();

        /** @var $process Process */
        foreach ($this->childProcesses as $process) {
            $process->cleanupIfZombie();
            if ($process->isActive()) {
                $stillActive[] = $process;
            }
        }
        $this->childProcesses = $stillActive;
    }

    protected function waitForChildrenToFinish()
    {
        while (!empty($this->childProcesses)) {
            $this->cleanupZombieChildren();
            usleep(200000);
        }
    }

    protected function numberOfActiveChildren()
    {
        return count($this->childProcesses);
    }
}
