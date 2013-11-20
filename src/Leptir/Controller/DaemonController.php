<?php

namespace Leptir\Controller;

use Leptir\Broker\BrokerFactory;
use Leptir\Broker\SQSBroker;
use Leptir\Daemon\Daemon;
use Leptir\Daemon\DaemonProcess;
use Leptir\Exception\LeptirRuntimeException;
use Leptir\Logger\LeptirLoggerFactory;
use Leptir\MetaBackend\MetaBackendFactory;
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
                throw new LeptirRuntimeException(LeptirRuntimeException::CONFIGURATION_FILE_INVALID);
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
            $logger = LeptirLoggerFactory::factory($name, $options);
            $loggers[] = $logger;
        }

        if (isset($leptirConfig['meta_storage'])) {
            $metaBackend = MetaBackendFactory::factory($leptirConfig['meta_storage']);
        } else {
            $metaBackend = null;
        }

        $broker = BrokerFactory::factory($leptirConfig['broker']);

        $daemon = new Daemon($broker, $leptirConfig['daemon'], $loggers, $metaBackend);

        $daemon->start();
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
}
