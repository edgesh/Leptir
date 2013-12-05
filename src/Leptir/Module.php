<?php

namespace Leptir;


use Zend\Console\Adapter\AdapterInterface;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\Config\Factory;

class Module
{
    public function onBootstrap(MvcEvent $e)
    {
        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
    }

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
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/' . __NAMESPACE__,
                ),
            ),
        );
    }

    public function getConsoleUsage(AdapterInterface $console)
    {
        return array(
            'Leptir - background task processor',
            'Usages',
            'index.php leptir daemon start|stop|restart [--configuration config_file.ext]',
            'index.php leptir tester <action> <taskName>'
        );
    }

    public function getServiceConfig()
    {
        return array();
    }

}
