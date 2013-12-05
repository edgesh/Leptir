<?php

namespace Leptir\Controller;

use Leptir\Broker\Broker;
use Leptir\Daemon\Daemon;
use Leptir\Daemon\DaemonProcess;
use Leptir\Exception\DaemonProcessException;
use Leptir\Logger\LeptirLoggerFactory;
use Leptir\MetaBackend\MetaBackendFactory;
use Zend\Console\Adapter\AdapterInterface;
use Zend\Console\ColorInterface;
use Zend\Console\Request;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Stdlib\ArrayUtils;
use Zend\Config\Factory;

class DaemonController extends AbstractActionController
{
    public function startAction()
    {
        $daemonProcess = new DaemonProcess();
        if ($daemonProcess->getPID()) {
            $this->writeErrorLine("Leptir is already running. Only one leptir can fly inside the box at the time.");
            exit(1);
        }

        $request = $this->getRequest();

        if (!$request instanceof Request) {
            throw new \RuntimeException('You can only use this action from a console.');
        }
        $serviceConfig = $this->serviceLocator->get('config');
        $leptirConfig = isset($serviceConfig['leptir']) ? $serviceConfig['leptir'] : array();

        $configFilename = $request->getParam('config');

        if (!is_null($configFilename)) {
            try {
                $configFromFile = Factory::fromFile($configFilename);
            } catch (\Exception $e) {
                $this->writeErrorLine($e->getMessage());
                exit(1);
            }

            $leptirConfig = ArrayUtils::merge(
                $leptirConfig,
                $configFromFile
            );
        }

        // create loggers
        $loggersConfig = array();
        if (isset($leptirConfig['loggers'])) {
            $loggersConfig = $leptirConfig['loggers'];
        }

        $loggers = array();
        foreach ($loggersConfig as $name => $options) {
            try {
                $logger = LeptirLoggerFactory::factory($name, $options);
            } catch (\Exception $e) {
                $this->writeWarningLine('(Creating logger - logger will be ignored) ' . $e->getMessage());
                continue;
            }

            if ($logger) {
                $loggers[] = $logger;
            }
        }

        if (isset($leptirConfig['meta_storage'])) {
            try {
                $metaBackend = MetaBackendFactory::factory($leptirConfig['meta_storage']);
            } catch (\Exception $e) {
                $this->writeErrorLine('(creating meta storage) ' . $e->getMessage());
                exit(1);
            }
        } else {
            $metaBackend = null;
        }

        $brokersSettings = isset($leptirConfig['brokers']) ? $leptirConfig['brokers'] : array();
        $broker = new Broker($brokersSettings, $loggers);

        $daemon = new Daemon($broker, $leptirConfig['daemon'], $loggers, $metaBackend);
        try {
            $daemon->start();
        } catch (DaemonProcessException $e) {
            $this->writeErrorLine($e->getMessage());
        } catch (\Exception $e) {
            $this->writeErrorLine($e->getMessage());
        }
    }

    public function stopAction()
    {
        $daemonProcess = new DaemonProcess();
        if (!$daemonProcess->getPID()) {
            $this->writeErrorLine("Leptir is already dead. That's not very nice of you.");
            exit(1);
        }

        $daemonProcess->sendSignal(SIGTERM);
    }

    public function restartAction()
    {
        $this->stopAction();
        $this->startAction();
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

        $configPath = $request->getParam('config');

        if ($configPath) {
            $configString = ' --config=' . $configPath;
        } else {
            $configString = '';
        }
        $indexPath = realpath(__DIR__ . '/../../../../../public/index.php');

        file_put_contents(
            '/etc/init.d/leptir',
            sprintf(
'#!/bin/bash
case "$1" in
start)
    if [ -f "/var/run/leptir.pid" ] ; then
        echo "Leptir is already running."
        exit 1
    fi
    echo "Starting a little butterfly."
    php %s leptir daemon start %s >& /dev/null &
;;
stop)
    if [ ! -f "/var/run/leptir.pid" ] ; then
        echo "Leptir is already dead. :("
        exit 1
    fi
    echo "Stopping a little butterfly. You\'ll have to wait for all the tasks to finish though."
    php %s leptir daemon stop >& /dev/null &
;;
restart)
    echo "Restarting a little butterfly. You\'ll have to wait for all the tasks to finish before that action."
    php %s leptir daemon restart %s >& /dev/null &
;;
*)
    echo "Usage: $0 (start|stop|restart)"
    exit 1
esac

exit 0
',
$indexPath, $configString,
$indexPath,
$indexPath, $configString)
        );
    }

    protected function writeErrorLine($line)
    {
        /** @var AdapterInterface $console */
        $console = $this->getServiceLocator()->get('console');
        $console->writeLine('[ERROR] ' . $line, ColorInterface::RED);
    }

    protected function writeWarningLine($line)
    {
        /** @var AdapterInterface $console */
        $console = $this->getServiceLocator()->get('console');
        $console->writeLine('[WARNING] ' . $line, ColorInterface::YELLOW);
    }
}
