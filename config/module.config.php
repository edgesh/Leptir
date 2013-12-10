<?php

return array(
    'controllers' => array(
        'invokables' => array(
            'Leptir\Controller\Leptir' => 'Leptir\Controller\LeptirController',
            'Leptir\Controller\Tester' => 'Leptir\Controller\TesterController'
        ),
    ),
    'console' => array(
        'router' => array(
            'routes' => array(
                'leptir-start' => array(
                    'options' => array(
                        'route' => 'leptir start [--config=] [--daemon] [--pid=]',
                        'defaults' => array(
                            'controller' => 'Leptir\Controller\Leptir',
                            'action' => 'start'
                        )
                    )
                ),
                'leptir-stop' => array(
                    'options' => array(
                        'route' => 'leptir stop [--config=] [--pid=]',
                        'defaults' => array(
                            'controller' => 'Leptir\Controller\Leptir',
                            'action' => 'stop'
                        )
                    )
                ),
                'leptir-install' => array(
                    'options' => array(
                        'route' => 'leptir install [--config=] [--pid=] [--php_path=]',
                        'defaults' => array(
                            'controller' => 'Leptir\Controller\Leptir',
                            'action' => 'install'
                        )
                    )
                ),
                'add-test-task' => array(
                    'options' => array(
                        'route' => 'leptir tester <action> <taskName> [--config=]',
                        'defaults' => array(
                            'controller' => 'Leptir\Controller\Tester'
                        )
                    )
                )
            ),

        )
    )
);
