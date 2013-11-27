<?php

namespace Leptir\Controller;

use Leptir\Broker\BrokerFactory;
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
                array(
                    'leptir' => $configFromFile
                )
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
        try {
            $broker = BrokerFactory::factory($leptirConfig['broker']);
        } catch (\Exception $e) {
            $this->writeErrorLine('(Creating broker) ' . $e->getMessage());
            exit(1);
        }

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
        $daemonProcess->waitForProcessesToFinish();
    }

    public function restartAction()
    {
        $this->stopAction();
        $this->startAction();
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
