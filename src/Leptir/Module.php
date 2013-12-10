<?php

namespace Leptir;


use Zend\Config\Factory;
use Zend\Console\Adapter\AdapterInterface;
use Zend\Loader\AutoloaderFactory;
use Zend\Loader\StandardAutoloader;
use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\ModuleManager\Feature\ConsoleUsageProviderInterface;

/**
 *
 */
class Module implements
    AutoloaderProviderInterface,
    ConfigProviderInterface,
    ConsoleUsageProviderInterface
{
    public function getConfig()
    {
        $configs = array(
            __DIR__ . '/../../config/module.config.php',
            __DIR__ . '/../../config/leptir.default.config.php'
        );

        return Factory::fromFiles($configs);
    }

    public function getAutoloaderConfig()
    {
        return array(
            AutoloaderFactory::STANDARD_AUTOLOADER => array(
                StandardAutoloader::LOAD_NS => array(
                    __NAMESPACE__ => __DIR__,
                ),
            ),
        );
    }

    public function getConsoleUsage(AdapterInterface $console)
    {
        return array(
            'Leptir - background task processor',
            'Usage:',
            'index.php leptir start [--config config_file.php] [--daemon] [--pid pid_filepath]',
            'index.php leptir stop',
            'index.php leptir install [--config config.php] [--pid pid_filepath] [--php_path php_dir]',
            'index.php leptir tester <action> <taskName>'
        );
    }
}
