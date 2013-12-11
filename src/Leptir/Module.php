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
            'leptir start [--config config_file.php] [--daemon] [--pid pid_filepath]' =>
                'Start leptir process',
            array(
                '--config=', 'Config file path'
            ),
            array(
                '--daemon', 'Start process as a daemon (in background)'
            ),
            array(
                '--pid=', 'PID file path (default: /var/run/leptir.pid)'
            ),
            'leptir stop [--pid=]' => 'Stop leptir process',
            array(
                '--pid=' => 'PID file path (default: /var/run/leptir.pid'
            ),
            'leptir install [--config=] [--daemon] [--pid=] [--php_path=]' => 'Install leptir as a service',
            array(
                '--php_path=', 'Path to PHP interpreter'
            ),
            'leptir tester <action> <taskName> [--config=] [--dalaySeconds=] [--priority=] [--number=] [--timeLimit=]' =>
                'Leptir task testers - push testing tasks into the queue',
            array(
                '--delaySeconds=', 'Schedule/delay tasks for some amount of seconds'
            ),
            array(
                '--priority=', 'Task priority. This will make difference if there are multiple brokers with different priorities defined.'
            ),
            array(
                '--number=', 'Number of copies to create. (default: 1)'
            ),
            array(
                '--timeLimit=', 'Task time limit. This will override default task time limit defined in configuration file'
            )
        );
    }
}
