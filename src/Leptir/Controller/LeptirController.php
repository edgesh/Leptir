<?php

namespace Leptir\Controller;


use Leptir\Process\Process;
use Zend\Console\Request;

class LeptirController extends BaseLeptirController
{
    public function startAction()
    {
        $request = $this->getRequest();
        /**
         * Request has to be instanceof Zend\Console\Request
         */
        if (!$request instanceof Request) {
            throw new \RuntimeException('You can only use this action from a console.');
        }

        $pidFilePath = $this->getPidFilePath();

        if (file_exists($pidFilePath)) {
            $fileContent = @file_get_contents($pidFilePath);

            if ($error = error_get_last()) {
                $this->writeErrorLine($error['message']);
                exit(1);
            }
            $pid = intval($fileContent);
            $process = new Process($pid);
            if ($process->isActive()) {
                $this->writeErrorLine("Leptir process is already running.");
                exit(1);
            } else {
                $this->writeWarningLine("PID file exists but leptir process is not running.");
                $this->writeWarningLine("Cleaning up PID file...");
            }
        }

        $isDaemon = $request->getParam('daemon');

        if (!$isDaemon) {

            try{
                $master = $this->getMaster();
            } catch (\Exception $ex) {
                $this->writeErrorLine($ex->getMessage());
                exit(1);
            }

            try {
                $master->start();
            } catch (\Exception $e) {
                $this->writeErrorLine($e->getMessage());
            }
        } else {
            $pid = pcntl_fork();

            if ($pid == -1) {
                $this->writeErrorLine('Foking daemon process failed. Exiting ...');
                exit(1);
            } elseif ($pid == 0) {
                // child process which will be a daemon
                posix_setsid();
                try{
                    $master = $this->getMaster();
                } catch (\Exception $ex) {
                    $this->writeErrorLine($ex->getMessage());
                    exit(1);
                }
                $master->start(true);
            }
        }
    }

    public function stopAction()
    {
        $pidFilePath = $this->getPidFilePath();

        if (file_exists($pidFilePath)) {
            $fileContent = @file_get_contents($pidFilePath);

            if ($error = error_get_last()) {
                $this->writeErrorLine($error['message']);
                exit(1);
            }
            $pid = intval($fileContent);
            $process = new Process($pid);

            if (!$process->isActive()) {
                $this->writeWarningLine('Leptir process is not active.');
                $this->writeWarningLine('Removing PID file.');
                @unlink($pidFilePath);
                if ($error = error_get_last()) {
                    $this->writeErrorLine($error['message']);
                }
            } else {
                $process->sendSignal(SIGTERM);
            }
        } else {
            $this->writeWarningLine("Leptir process is not running. Nothing to stop here.");
        }
    }

    /**
     * Install action detects the OS and installs daemon support
     */
    public function installAction()
    {
        $request = $this->getRequest();
        if (!$request instanceof Request) {
            throw new \RuntimeException('You can only use this action from a console.');
        }

        if (!defined('ROOT_PATH')) {
            $this->writeWarningLine('ROOT_PATH not defined.');
            $this->writeWarningLine(
'Add line to index.php:
    define("ROOT_PATH", dirname(__DIR__));');
            exit(1);
        }

        $configPath = $request->getParam('config');

        $pidFilePath = $request->getParam('pid', '');
        if (!$pidFilePath) {
            $pidFilePath = '/var/run/leptir.pid';
        }

        $phpPath = $request->getParam('php_path', '');

        $scriptPath = realpath(__DIR__ . '/../../../scripts/daemon.sh');
        $scriptContent = file_get_contents($scriptPath);

        $scriptContent = str_replace("{{PID_PATH}}", $pidFilePath, $scriptContent);
        $scriptContent = str_replace("{{ROOT_PATH}}", ROOT_PATH, $scriptContent);
        $scriptContent = str_replace("{{CONFIG_PATH}}", $configPath, $scriptContent);
        $scriptContent = str_replace("{{PHP_PATH}}", $phpPath, $scriptContent);

        file_put_contents('/etc/init.d/leptir', $scriptContent);

    }
}
