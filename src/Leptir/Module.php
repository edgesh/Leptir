<?php

namespace Leptir;


use Zend\Config\Factory;
use Zend\Console\Adapter\AdapterInterface;
use Zend\EventManager\EventInterface;
use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\BootstrapListenerInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\ModuleManager\Feature\ControllerPluginProviderInterface;
use Zend\ModuleManager\Feature\ViewHelperProviderInterface;

/**
 * Circlical ACL Admin Interface for BJYAuthorize
 *
 * @author Alexandre Lemaire <alemaire@circlical.com>
 */
class Module implements
    AutoloaderProviderInterface,
    BootstrapListenerInterface,
    ConfigProviderInterface,
    ControllerPluginProviderInterface,
    ViewHelperProviderInterface
{
    public function onBootstrap(EventInterface $event)
    {
        /* @var $app \Zend\Mvc\ApplicationInterface */
        $app    = $event->getTarget();
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
                    __NAMESPACE__ => __DIR__ . '/../../src/' . __NAMESPACE__,
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

    public function getViewHelperConfig()
    {
        return array(
            'factories' => array(

            ),
        );
    }

    /**
     * Expected to return \Zend\ServiceManager\Config object or array to
     * seed such an object.
     *
     * @return array|\Zend\ServiceManager\Config
     */
    public function getControllerPluginConfig()
    {
        return array(
            'factories' => array(

            ),
        );
    }


}
