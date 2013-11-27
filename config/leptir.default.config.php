<?php
return array(
    'leptir' => array(
        'broker' => array(
            'type' => 'mongodb',
            'options' => array(

            )
        ),
        'loggers' => array(
            'logfile' => array(
                'type' => 'file',
                'options' => array(
                    'path' => '/var/log/leptir.log'
                )
            ),
            'stdoutlog' => array(
                'type' => 'stdout'
            )
        ),
        'daemon' => array(
            'configuration' => array(
                'task_execution_time' => 600,
                'number_of_workers' => 4,
                'empty_queue_sleep_time' => 0.2,
                'workers_active_sleep_time' => 0.2
            )
        ),
        'meta_storage' => array(
            'type' => 'mongodb',
            'options' => array(
                'host' => 'localhost',
                'port' => 27017,
                'database' => 'leptir',
                'collection' => 'info',
                'options' => array(
                    'readPreference' => 'secondaryPreferred'
                )
            )
        )
    )
);
