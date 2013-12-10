<?php

namespace Leptir\Controller;

use Leptir\Broker\Broker;
use Leptir\Core\Master;
use Leptir\Logger\LeptirLoggerFactory;
use Leptir\MetaStorage\MetaStorage;
use Leptir\MetaStorage\MetaStorageFactory;
use Zend\Config\Factory;
use Zend\Console\Adapter\AdapterInterface;
use Zend\Console\ColorInterface;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Stdlib\ArrayUtils;
use Zend\Console\Request;

class BaseLeptirController extends AbstractActionController
{
    protected $config = array();
    protected $loggers = array();
    protected $metaStorage = null;
    protected $broker = null;
    protected $master = null;

    protected function getDefaultLeptirConfig()
    {
        $serviceConfig = $this->serviceLocator->get('config');
        return isset($serviceConfig['leptir']) ? $serviceConfig['leptir'] : array();
    }

    protected function getConfigFromParameter()
    {
        /** @var Request $request */
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

    protected function getMasterConfig()
    {
        $config = $this->getConfigByKey('leptir');
        $config['configuration']['pid_path'] = $this->getPidFilePath();
        return $config;
    }

    protected function getMaster()
    {
        if (is_null($this->master)) {
            $this->master = new Master(
                $this->getBroker(),
                $this->getMasterConfig(),
                $this->getLoggers(),
                $this->getMetaStorage()
            );
        }

        return $this->master;
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
     * @return \Leptir\MetaStorage\MongoMetaStorage|null
     */
    protected function getMetaStorage()
    {
        if (is_null($this->metaStorage)) {
            $this->metaStorage = new MetaStorage($this->getMetaStorageConfig());
        }
        return $this->metaStorage;
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

    protected function getPidFilePath()
    {
        /** @var Request $request */
        $request = $this->getRequest();
        $pidFilePath = $request->getParam('pid', '');
        if (!$pidFilePath) {
            $pidFilePath = '/var/run/leptir.pid';
        }
        return $pidFilePath;
    }
}
