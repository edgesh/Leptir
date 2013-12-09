<?php

return array(
    'controllers' => array(
        'invokables' => array(
            'Leptir\Controller\Daemon' => 'Leptir\Controller\DaemonController',
            'Leptir\Controller\Tester' => 'Leptir\Controller\TesterController'
        ),
    ),
    'console' => array(
        'router' => array(
            'routes' => array(
                'leptir-start' => array(
                    'options' => array(
                        'route' => 'leptir start [--config=] [--daemon]',
                        'defaults' => array(
                            'controller' => 'Leptir\Controller\Daemon',
                            'action' => 'start'
                        )
                    )
                ),
                'leptir-stop' => array(
                    'options' => array(
                        'route' => 'leptir stop',
                        'defaults' => array(
                            'controller' => 'Leptir\Controller\Daemon',
                            'action' => 'stop'
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
