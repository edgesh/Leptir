<?php

namespace Leptir\Controller;

use Leptir\Daemon\DaemonProcess;
use Leptir\Exception\DaemonProcessException;
use Zend\Console\Request;

class DaemonController extends BaseLeptirController
{
    public function startAction()
    {
        $daemonProcess = new DaemonProcess();

        if ($daemonProcess->isActive()) {
            $this->writeErrorLine("Leptir is already running. Only one leptir can fly inside the box at the time.");
            exit(1);
        }
        $daemonProcess->setUp();

        $request = $this->getRequest();
        /**
         * Request has to be instanceof Zend\Console\Request
         */
        if (!$request instanceof Request) {
            throw new \RuntimeException('You can only use this action from a console.');
        }

        $daemon = $this->getDaemon();

        try {
            $daemon->start();
        } catch (\Exception $e) {
            $this->writeErrorLine($e->getMessage());
        }
    }

    public function stopAction()
    {
        $daemonProcess = new DaemonProcess();

        if (!$daemonProcess->isActive()) {
            $this->writeErrorLine('Leptir is already not flying.');
            $daemonProcess->cleanUp();
            exit(1);
        }

        $daemonProcess->sendSignal(SIGTERM);
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

        if ($configPath) {
            $configString = ' --config=' . $configPath;
        } else {
            $configString = '';
        }
        $indexPath = realpath(ROOT_PATH . '/public/index.php');

    }
}
