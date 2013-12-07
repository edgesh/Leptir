<?php

namespace Leptir\Controller;

use Leptir\Broker\Broker;
use Leptir\Daemon\Daemon;
use Leptir\Logger\LeptirLoggerFactory;
use Leptir\MetaBackend\MetaBackendFactory;
use Zend\Config\Factory;
use Zend\Console\Adapter\AdapterInterface;
use Zend\Console\ColorInterface;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Stdlib\ArrayUtils;

class BaseLeptirController extends AbstractActionController
{
    protected $config = array();
    protected $loggers = array();
    protected $metaBackend = null;
    protected $broker = null;
    protected $daemon = null;

    protected function getDefaultLeptirConfig()
    {
        $serviceConfig = $this->serviceLocator->get('config');
        return isset($serviceConfig['leptir']) ? $serviceConfig['leptir'] : array();
    }

    protected function getConfigFromParameter()
    {
        $request = $this->getRequest();
        $configFilename = $request->getParam('config');
        $configFromFile = array();
        if (!is_null($configFilename)) {
            try {
                $configFromFile = Factory::fromFile($configFilename);
            } catch (\Exception $e) {

            }
        }
        return $configFromFile;
    }

    protected function getCompleteLeptirConfig()
    {
        if (empty($this->config)) {
            $this->config = ArrayUtils::merge(
                $this->getDefaultLeptirConfig(),
                $this->getConfigFromParameter()
            );
        }
        return $this->config;
    }

    private function getConfigByKey($key)
    {
        $config = $this->getCompleteLeptirConfig();
        return isset($config[$key]) ? $config[$key] : array();
    }

    protected function getLoggersConfig()
    {
        return $this->getConfigByKey('loggers');
    }

    /**
     * @return array
     */
    protected function getLoggers()
    {
        if (empty($this->loggers)) {
            $loggersConfig = $this->getLoggersConfig();
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
            $this->loggers = $loggers;
        }
        return $this->loggers;
    }

    protected function getDaemonConfig()
    {
        return $this->getConfigByKey('daemon');
    }

    protected function getDaemon()
    {
        if (is_null($this->daemon)) {
            $this->daemon = new Daemon(
                $this->getBroker(),
                $this->getDaemonConfig(),
                $this->getLoggers(),
                $this->getMetaBackend()
            );
        }

        return $this->daemon;
    }

    protected function getBrokersConfig()
    {
        return $this->getConfigByKey('brokers');
    }

    protected function getBroker()
    {
        if (is_null($this->broker)) {
            $this->broker = new Broker($this->getBrokersConfig(), $this->getLoggers());
            $this->broker->setServiceLocator($this->getServiceLocator());
        }
        return $this->broker;
    }

    protected function getMetaStorageConfig()
    {
        return $this->getConfigByKey('meta_storage');
    }

    /**
     * @return \Leptir\MetaBackend\MongoMetaBackend|null
     */
    protected function getMetaBackend()
    {
        if (is_null($this->metaBackend)) {
            $this->metaBackend = MetaBackendFactory::factory($this->getMetaStorageConfig());
        }
        return $this->metaBackend;
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
