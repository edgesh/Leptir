Leptir
=========

Leptir is a asynchronous task processor. Leptir is used for both inserting tasks into brokers and as a daemon process which does the processing.

Leptir configuration
----
```php
    return array(
        'leptir' => array(
            'brokers' => array(
                array(
                'type' => 'mongodb',
                'connection' => array(
                    'collection' => 'tasksp1'
                    // default values will be used
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
                // brokers configuration explained leter
            ),
            'loggers' => array(
                // loggers configuration explained later
            ),
            'daemon' => array(
                'configuration' => array(
                    'task_execution_time' => 600,
                    'number_of_workers' => 4,
                    'empty_queue_sleep_time' => 0.5,
                    'workers_active_sleep_time' => 0.2,
                    'task_count_caching_time' => 0.6
                )
            ),
            'meta_storage' => array(
                // meta storage configuration explained later
            )
        )
    )
```

Explaination of daemon configuration parameters:

- **task_execution_time** - Maximum task execution time. If task doesn't finish in that time it will be terminated.
- **number_of_workers** - Number of active workers
- **empty_queue_sleep_time** - amount of second to sleep if currenly there are no tasks to process (0 - no sleep)
- **workers_active_sleep_time** - amount of seconds to sleep if all workers are active (0 - no sleep)

Parameters *empty_queue_sleep_time* and *workers_active_sleep_time* are used to reduce number of queries for broker. 

Configuration can contain definition for multiple brokers which will be used at the same time. Brokers also can have different priorities. Brokers with lower priority index will have higher chance that their task will be choosen. 


Brokers
----
Brokers are esentially queues which are holding unprocessed tasks. Every broker has scheduling support. Currently supported brokers:
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
    public function pushTask(BaseTask $task, \DateTime $timeOfExecution = null, $priority)
```
Method is used to push a new task into the queue.


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


Task Info Backend
----
Information about executed tasks are stored in database. 
Currently supported backends:
- MongoDB

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
- **status** - Current status of the task (1 - pending, 2 - in progress, 3 - completed)
- **retC** - Status code returned by task (1 - success, 2 - warning, 3 - error, 4 - unknown)
- **exTime** - execution time
- **respM** - Task's response message
- **type** - Task class name
- **exStart** - Task execution start time
- **_id** - unique task id (generated by broker)

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
*logfile* and *stdoutlog* are names (used only for readibility). STDOUT and STDERR loggers don't require additional options. File path is mandatory for file logger.

Daemon
----
Leptir daemon runs on the box and processes the tasks from brokers. Daemon configuration is described above. 

Starting leptir daemon:
```sh
    php {PROJECT_PATH}/public/index.php leptir daemon start
```

Stopping leptir daemon:
```sh
    php {PROJECT_PATH}/public/index.php leptir daemon stop
```

Restarting leptir daemon:
```sh
    php {PROJECT_PATH}/public/index.php leptir daemon restart
```

Tasks
---
Every task has to extends abstract class *Leptir\Task\BaseTask*. Mandatory method that needs to be implemented is protected *doJob* method.

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
- **protected function beforeStart()** - this method will be executed before *doJob* method
- **protected function afterFinish()** - this method will be executed after *doJob* method
- **public function getAdditionalMetaInfo()** - method should return associative array of additional fields to save as task information (task info backend section described before)

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
License
----

[MIT](http://en.wikipedia.org/wiki/MIT_License)

Copyright (C) 2013 Backplane, INC
  
    
