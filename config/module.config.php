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
                'leptir' => array(
                    'options' => array(
                        'route' => 'leptir daemon <action> [--config=]',
                        'defaults' => array(
                            'controller' => 'Leptir\Controller\Daemon',
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
