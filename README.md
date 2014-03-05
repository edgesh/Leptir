Leptir
=========

Leptir is a asynchronous, highly scalable task processor which can use multiple brokers(queues) in parallel. Different priorities can be defined for different brokers.

[![Build Status](https://travis-ci.org/Backplane/Leptir.png?branch=master)](https://travis-ci.org/Backplane/Leptir)

Leptir configuration
----
```php
    return array(
        'leptir' => array(
            'brokers' => array(
                   array(
                	'type' => 'mongodb',
                	'connection' => array(
                    		'collection' => 'tasksp1',
                    		'host' => 'localhost',
                    		'port' => 27017,
                    		'database' => 'leptir',
                    		'options' => array(
                    			'secondaryPreferred' => true
                    		)
                    		
                	),
                	'configuration' => array(
                    		'priority' => 1,
                    		'task_count_caching_time' => 0.6
                	)
            	),
            	array(
                	'type' => 'mongodb',
                	'connection' => array(
                    		'collection' => 'tasksp2'
                    		// default values will be used
                	),
                	'configuration' => array(
                    		'priority' => 2
                	)
            	)
            ),
            'loggers' => array(
                // loggers configuration explained later
            ),
            'daemon' => array(
                'configuration' => array(
                    'task_execution_time' => 600,
                    'number_of_workers' => 4,
                    'empty_queue_sleep_time' => 0.5,
                    'workers_active_sleep_time' => 0.2
                )
            ),
            'meta_storage' => array(
                array(
                    //storage configuration
                ),
                array(
                    // storage configuraion
                )
            )
        )
    )
```

Explaination of leptir configuration parameters:

- `task_execution_time` - Maximum task execution time. If task doesn't finish in that time it will be terminated. Maximum execution time can be overrided for every task individually when pushing to the broker queue.
- `number_of_workers` - Maximum number of active workers per leptir instance.
- `empty_queue_sleep_time` - Time to sleep when queue is empty (0 - no sleep) --- reduces number of queries for broker. (seconds, floating point supported)
- `workers_active_sleep_time` - Time to sleep when all the workers are busy. (0 - no sleep) --- reduces number of queries for broker. (seconds, floating point supported)

Parameters `empty_queue_sleep_time` and `workers_active_sleep_time` are used to reduce number of queries for broker (more usefull for SQS broker where number of queries affect the price). 

Configuration can contain definition for multiple brokers which will be used at the same time. Brokers also can have different priorities. Brokers with lower priority index will have higher chance that their task will be choosen. 

Configuration can also contain definition for multiple task meta information storage.


Brokers
----
Brokers are esentially queues which are holding unprocessed tasks. Every broker has scheduling support. Priority is assigned to every broker as well (default priority is 0). Currently supported brokers:
  - MongoDB
  - Amazon SQS
  - Redis

Tasks can be prioritized by using multiple brokers with different priority levels.

Example of broker usage:
```php
    $broker = new Broker($brokersConfiguration);
    $task = new TestTask(
        array(
            'param1' => 'First parameter'
            'paramFloat' => 3.89,
            'randomIntParam' => 10
        )
    );
    $broker->pushTask($task, null, 1);
```
Broker methods:
```php
    public function pushTask(BaseTask $task, \DateTime $timeOfExecution = null, $priority = 0, $timeLimit = 0)
```
Method is used to push a new task into the queue.

Every broker has couple of configuration options:
- `priority`: Broker priority level (lower number means higher priority)
- `task_count_caching_time` - Caching time for information about current number of tasks in the queue. Used to reduce number of request to database/SQS.


#### MongoDB broker
Configuration example:
```php
    array(
        'type' => 'mongodb',
        'connection' => array(
            'host' => 'localhost',
            'port' => 27017,
            'database' => 'leptir' 
            'collection' => 'tasks',
            'options' => array(
                'connect' => true,
                'secondaryPreferred' => true
            )
        ),
        'configuration' => array(
            'priority' => 0
        )
    )    
```
This configuration is also a default one. Default configuration is merged with user defined one before broker is created.

#### Amazon SQS broker
Configuration example:
```php
    array(
        'type' => 'sqs',
        'connection' => array(
            'sqs_key' => 'SQS KEY',
            'sqs_secret' => 'SQS SECRET',
            'sqs_queue' => 'QUEUE URI'
        ),
        'configuration' => array(
            'priority' => 0
        )
     ) 
```
#### Redis broker
Configuration example:
```php
    array(
        'type' => 'redis',
        'connection' => array(
            'scheme' => 'tcp',
            'host' => 'localhost',
            'port' => 6379,
            'database' => 0,
            'key' => 'leptir:ztasks'
        ),
        'configuration' => array(
            'priority' => 0
        )
     ) 
```
This configuration is also a default one.


Task Meta Storage
---- 
Used to store information about executed tasks. Multiple meta info storages can be defined.

Currently supported storages:
- MongoDB
- Redis

#### Mongo meta storage
Task information format:
```json
{
    "status" : 3,
	"retC" : 1,
	"exTime" : 0.0005939006805419922,
	"respM" : "Test task done.",
	"type" : "Leptir\\Task\\Test\\TestTask",
	"exStart" : ISODate("2013-12-04T02:43:35Z"),
	"_id" : "10577529e96d5eb0225.50827478"
}

```
- `status` - Current status of the task (1 - pending, 2 - in progress, 3 - completed)
- `retC` - Status code returned by task (1 - success, 2 - warning, 3 - error, 4 - unknown, 5 - time limit exceeded)
- `exTime` - execution time
- `respM` - Task's response message
- `type` - Task class name
- `exStart` - Task execution start time
- `_id` - unique task id (generated by broker)

Configuration:
```php
    array(
        'connection' => array(
            'host' => 'localhost',
            'port' => 27017,
            'database' => 'leptir'
            'collection' => 'info'
            'options' => array(
                // mongo connection options
            )
        ),
        'configuration' => array(
            
        )
    )
```

#### Redis meta storage
Redis meta storage is storing key-value pair information about every task where key is task id and value is JSON encoded task information array.

Configuration:
```php
    array(
        'connection' => array(
            'scheme' => 'tcp',
            'host' => 'localhost',
            'port' => 6379,
            'database' => 0
        ),
        'configuration' => array(
            'expire_after_seconds' => 86400
        )
    )
```

Loggers
----
Loggers for Leptir daemon can be defined in configuration file. 
Available loggers:
- File logger
- STDOUT logger
- STDERR logger

Leptir is using Zend\Logging library to generate logs. 
Configuration examples:
```php
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
        )
```
`logfile` and `stdoutlog` are names (used only for readibility). STDOUT and STDERR loggers don't require additional options. Logfile `path` is mandatory for file logger.

Tasks
---
Every task has to extends abstract class `Leptir\Task\BaseTask`. Mandatory method that needs to be implemented is protected `doJob` method.

Simple task example:
```php
<?php

namespace Leptir\Task\Test;

use Leptir\Task\BaseTask;

/**
 * Simple task used for testing purpose. This task will not anything smart, it will just sleep for
 * random amount of seconds (between 6 and 19)
 *
 * Class SlowTask
 * @package Leptir\Task\Test
 */
class SlowTask extends BaseTask
{
    protected function doJob()
    {
        $sleepTime = rand(6, 19);
        $this->logInfo('Sleeping for '. $sleepTime);
        sleep($sleepTime);

        $this->addResponseLine('Task had a great nap');
        return self::EXIT_SUCCESS;
    }
}
```
Additional methods that can be overrided for extended control over task execution.
- `protected function beforeStart()` - this method will be executed before *doJob* method
- `protected function afterFinish()` - this method will be executed after *doJob* method
- `public function getAdditionalMetaInfo()` - method should return associative array of additional fields to save as task information (task info backend section described before)

There are also several methotds implemented in BaseTask class that can be used while implementing custom tasks:
* **Methods for fetching parameters passed to task**:
```php
    protected function getInt($paramName, $defaultValue = null)
```
```php
    protected function getString($paramName, $defaultValue = null)
```
```php
    protected function getFloat($paramName, $defaultValue = null)
```
```php
    protected function getArray($paramName, $defaultValue = null)
```
Methods will throw *Leptir\Exception\LeptirInputException* when parameter type doesn't match requested one. 

* **Current execution time** - somethinmes task implementation requires task execution time (to make an action if running time is to long or something similar)
```php
    protected function getExecutionTime()
```
Method will return float number represending the execution time.

* **Logging methods**
```php
    protected function logInfo($message)
```
```php
    protected function logError($message)
```
```php
    protected function logWarning($message)
```
```php
    protected function logDebug($message)
```
* **Execution flow methods** - task execution flow can be tracked - helps writing unittest for user written tasks
- `changeState($state)` - change current state of the execution
- `getLastState()` - get last state task was in
- `getExecutionFlow()` - returns an array with list of states task was in while running

Command line actions
----

#### Starting leptir
```sh
    php index.php leptir start [--daemon] [--config=] [--pid=]
```
Leptir can be started as daemon. Only file loggers will be active when leptir is running as a daemon.

#### Stopping leptir
```sh
    php index.php leptir stop [--pid=]
```
Command used to stop leptir process.

#### Install leptir as a service
```sh
    php index.php leptir install  [--config=] [--pid=] [--php_path=]
```
Command used to install leptir as a service (creates `/etc/ini.d/leptir` file). File content:
```sh
#!/bin/bash
PID_PATH='{{PID_PATH}}'

case "$1" in
start)
        # check if PID file exists
        if [ -f $PID_PATH ]; then
                pid="`cat $PID_PATH`"
                if ps $pid > /dev/null
                then
                        echo -e "\e[31mLeptir is already flying on this box.\e[0m"
                        exit 1
                else
                        echo -e "\e[33m.pid file is there, but process is not running. Cleaning .pid file and starting the process.\e[0m"
                        rm -f "$PID_PATH"
                fi
        fi
        echo -e "\e[32mStarting a little butterfly. Fly buddy, fly!\e[0m"
    {{PHP_PATH}}php {{ROOT_PATH}}/public/index.php leptir start  --config={{CONFIG_PATH}} --daemon --pid $PID_PATH
;;
stop)
    echo -ne "Stopping a little butterfly. You'll have to wait for all the tasks to finish though.\n"
        {{PHP_PATH}}php {{ROOT_PATH}}/public/index.php leptir stop --pid $PID_PATH
        while [ -f $PID_PATH ];
        do
                sleep 1
                echo -ne "."
        done
        echo
;;
*)
    echo "Usage: $0 (start|stop)"
    exit 1
esac

exit 0
```
