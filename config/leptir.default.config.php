<?php
return array(
    'leptir' => array(
        'brokers' => array(
            array(
                'type' => 'mongodb'
            )
        ),
        'loggers' => array(
            'stdout' => array(
                'type' => 'stdout'
            )
        ),
        'daemon' => array(
            'configuration' => array(
                'task_execution_time' => 600,
                'number_of_workers' => 4,
                'empty_queue_sleep_time' => 0.8,
                'workers_active_sleep_time' => 0.2,
                'task_count_caching_time' => 0.6
            )
        ),
        'meta_storage' => array(
            'type' => 'mongodb',
            'connection' => array(

            ),
            'configuration' => array(

            )
        )
    )
);
