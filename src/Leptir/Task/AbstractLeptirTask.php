<?php

namespace Leptir\Task;

use Leptir\ErrorReport\ErrorReport;
use Leptir\ErrorReport\ErrorReportInterface;
use Leptir\Exception\LeptirInputException;
use Leptir\Exception\LeptirTaskException;
use Leptir\Logger\LeptirLoggerTrait;
use Leptir\MetaStorage\MetaStorage;
use Zend\Stdlib\ArrayUtils;

abstract class AbstractLeptirTask
{
    use LeptirLoggerTrait;

    /** CONSTANTS */
    const EXIT_SUCCESS = 1;
    const EXIT_WARNING = 2;
    const EXIT_ERROR = 3;
    const EXIT_UNKNOWN = 4;
    const EXIT_TIME_LIMIT_EXCEEDED = 5;

    const STATUS_PENDING = 1;
    const STATUS_IN_PROGRESS = 2;
    const STATUS_COMPLETED = 3;

    /** DATA */
    private $responseLines = array();
    private $returnCode;
    private $parameters = array();
    private $taskId = '';
    private $taskStatus = self::STATUS_PENDING;
    private $taskExecutionStartTime = null;
    private $executionFlow = array();

    /**
     * @var MetaStorage|null
     */
    private $metaBackend = null;

    /**
     * @var ErrorReport
     */
    private $errorReporting = null;

    private $taskTimeStart;
    private $taskTimeEnd;

    public function __construct(array $parameters = array())
    {
        $this->returnCode = self::EXIT_UNKNOWN;
        $this->parameters = $parameters;
    }

    /**
     * Method which triggers task execution and all the dependencies that come with that.
     */
    final public function execute(
        $timeLimit = 0,
        MetaStorage $metaBackend = null,
        $graceful = false,
        ErrorReport $errorReport = null
    ) {
        $this->taskExecutionStartTime = new \DateTime();
        $this->saveTaskMetaInfo();
        $this->metaBackend = $metaBackend;
        $this->errorReporting = $errorReport;
        $this->taskTimeStart = microtime(true);
        $this->taskStatus = self::STATUS_IN_PROGRESS;

        if ($timeLimit > 0 && $graceful) {
            pcntl_signal(
                SIGALRM,
                array(
                    $this,
                    'alarmHandler'
                )
            );
            pcntl_alarm($timeLimit);
        }

        if ($graceful) {
            set_error_handler(
                array(
                    $this,
                    'errorHandler'
                )
            );
        }

        $this->printTaskStartLog();

        @$this->beforeStart();
        try {
            $resp = $this->doJob();
        } catch (LeptirTaskException $ex) {
            if (!$graceful) {
                throw $ex;
            } else {
                if ($ex->getCode() == LeptirTaskException::TIME_LIMIT_EXCEEDED) {
                    $resp = self::EXIT_TIME_LIMIT_EXCEEDED;
                    $this->addResponseLine('Time limit exceeded.');
                    $this->errorReporting->addErrorData(
                        array(
                            'ErrType' => 'TimeLimit',
                            'TaskType' => $this->getTaskType(),
                            'TaskId' => $this->taskId
                        )
                    );
                    $this->errorReporting->reportErrorMessage('Task time limit exceeded.');
                } else {
                    $resp = self::EXIT_ERROR;
                    $this->addResponseLine('LeptirTaskException occurred. ' . $ex->getMessage());
                    if ($this->errorReporting instanceof ErrorReportInterface) {
                        $this->errorReporting->reportException($ex);
                    }
                }
            }
        } catch (\Exception $ex) {
            if (!$graceful) {
                throw $ex;
            } else {
                $this->addResponseLine('Task exited with exception: ' . $ex->getMessage());
                $this->logError('Task exited with exception: ' . $ex->getMessage());
                if ($this->errorReporting instanceof ErrorReportInterface) {
                    $this->errorReporting->reportException($ex);
                }
                $resp = self::EXIT_ERROR;
            }
        }

        if ($resp !== self::EXIT_SUCCESS &&
            $resp !== self::EXIT_WARNING &&
            $resp !== self::EXIT_ERROR &&
            $resp !== self::EXIT_TIME_LIMIT_EXCEEDED &&
            $resp !== self::EXIT_UNKNOWN) {
                $resp = self::EXIT_UNKNOWN;
        }
        $this->returnCode = $resp;
        @$this->afterFinish();

        $this->taskTimeEnd = microtime(true);
        $this->taskStatus = self::STATUS_COMPLETED;

        $this->printTaskEndLog();

        $this->saveTaskMetaInfo();
    }

    public function exchangeArray(array $data = array())
    {
        $this->parameters = isset($data['parameters']) ? $data['parameters'] : array();
        $this->taskId = isset($data['id']) ? $data['id'] : '';
    }

    public function getArrayCopy()
    {
        return new \ArrayObject(
            array(
                'id' => $this->taskId,
                'parameters' => $this->parameters
            )
        );
    }

    final protected function addResponseLine($line)
    {
        $this->responseLines[] = $line;
    }

    /**
     * Method that will be executed before the execution of the task. Method can be overridden if
     * necessary.
     */
    protected function beforeStart()
    {

    }

    /**
     * Main logic of the task. This method has to be implemented for every task.
     *
     * @return mixed
     */
    abstract protected function doJob();

    /**
     * Method tat will be executed after the execution of the task. Method can be overridden if
     * necessary.
     */
    protected function afterFinish()
    {

    }

    /**
     * Define additional information for task meta info (stored in database)
     * This will override existing values in case of collision
     *
     * @return array
     */
    public function getAdditionalMetaInfo()
    {
        return array();
    }

    /**
     * Errors are handled manually and shown in logs.
     *
     */

    public function errorHandler(
        $errorNumber,
        $errorString,
        $errorFile,
        $errorLine
    ) {
        $this->returnCode = self::EXIT_ERROR;
        $this->taskStatus = self::STATUS_COMPLETED;
        switch($errorNumber) {
            case E_USER_ERROR:
                $responseLine = '[E_USER_ERROR] ';
                break;
            case E_USER_WARNING:
                $responseLine = '[E_USER_WARNING] ';
                break;
            case E_USER_NOTICE:
                $responseLine = '[E_USER_NOTICE] ';
                break;
            default:
                $responseLine = '[Error] ';
                break;
        }
        if ($this->errorReporting instanceof ErrorReportInterface) {
            $this->errorReporting->addErrorData(
                array(
                    'Line' => $errorLine,
                    'File' => $errorFile,
                    'Number' => $errorNumber,
                    'ErrType' => $responseLine,
                    'TaskType' => $this->getTaskType(),
                    'TaskId' => $this->taskId
                )
            );
            $this->errorReporting->reportErrorMessage($errorString);
        }
        $responseLine .= $errorString . ' (File: ' . $errorFile . ', Line: ' . (string)$errorLine . ')';
        $this->responseLines = array(
            $responseLine
        );
        $this->logError('Error in task: ' . $responseLine);

        $this->saveTaskMetaInfo();
        return true;
    }


    /**
     * @param string $paramName
     * @param int|null $defaultValue
     * @return int
     * @throws \Leptir\Exception\LeptirInputException
     */
    final protected function getInt($paramName, $defaultValue = null)
    {
        if (isset($this->parameters[$paramName])) {
            $value = $this->parameters[$paramName];
            if (is_int($value)) {
                return $value;
            } else {
                throw new LeptirInputException($paramName, LeptirInputException::NOT_AN_INT);
            }
        } else {
            if (is_int($defaultValue)) {
                return $defaultValue;
            }
        }
        throw new LeptirInputException($paramName, LeptirInputException::VALUE_NOT_DEFINED);
    }

    /**
     * @param string $paramName
     * @param float|null $defaultValue
     * @return float
     * @throws \Leptir\Exception\LeptirInputException
     */
    final protected function getFloat($paramName, $defaultValue = null)
    {
        if (isset($this->parameters[$paramName])) {
            $value = $this->parameters[$paramName];
            if (is_float($value) || is_double($value)) {
                return $value;
            } else {
                throw new LeptirInputException($paramName, LeptirInputException::NOT_A_FLOAT);
            }
        } else {
            if (is_float($defaultValue) || is_double($defaultValue)) {
                return $defaultValue;
            }
        }
        throw new LeptirInputException($paramName, LeptirInputException::VALUE_NOT_DEFINED);
    }

    /**
     * @param string $paramName
     * @param string|null $defaultValue
     * @return string
     * @throws \Leptir\Exception\LeptirInputException
     */
    final protected function getString($paramName, $defaultValue = null)
    {
        if (isset($this->parameters[$paramName])) {
            $value = $this->parameters[$paramName];
            if (is_string($value)) {
                return $value;
            } else {
                throw new LeptirInputException($paramName, LeptirInputException::NOT_A_STRING);
            }
        } else {
            if (is_string($defaultValue)) {
                return $defaultValue;
            }
        }
        throw new LeptirInputException($paramName, LeptirInputException::VALUE_NOT_DEFINED);
    }

    /**
     * @param string $paramName
     * @param array|null $defaultValue
     * @return array
     * @throws \Leptir\Exception\LeptirInputException
     */
    final protected function getArray($paramName, $defaultValue = null)
    {
        if (isset($this->parameters[$paramName])) {
            $value = $this->parameters[$paramName];
            if (is_array($value)) {
                return $value;
            } else {
                throw new LeptirInputException($paramName, LeptirInputException::NOT_AN_ARRAY);
            }
        } else {
            if (is_array($defaultValue)) {
                return $defaultValue;
            }
        }
        throw new LeptirInputException($paramName, LeptirInputException::VALUE_NOT_DEFINED);
    }

    /**
     * @param string $paramName
     * @param mixed $defaultValue
     * @return mixed
     */
    final protected function getData($paramName, $defaultValue = null)
    {
        if (isset($this->parameters[$paramName])) {
            return $this->parameters[$paramName];
        }
        return $defaultValue;
    }

    protected function setData($paramName, $paramValue, $override = true)
    {
        if ($override) {
            $this->parameters[$paramName] = $paramValue;
        } else {
            if (!isset($this->parameters[$paramName])) {
                $this->parameters[$paramName] = $paramValue;
            }
        }
    }

    final public function alarmHandler()
    {
        $this->logInfo('Task execution time exceeded.');
        $this->taskTimeEnd = microtime(true);

        throw new LeptirTaskException(LeptirTaskException::TIME_LIMIT_EXCEEDED);
    }

    private function printTaskStartLog()
    {
        $this->logInfo(
            'Task started.'
        );
    }

    private function printTaskEndLog()
    {
        $this->logInfo(
            'Task is done. Execution time: ' .
            (string)(round($this->taskTimeEnd - $this->taskTimeStart, 4)) . ' seconds.'
        );
    }

    final public function getReturnCode()
    {
        return $this->returnCode;
    }

    /**
     * @param string $message
     * @return string
     */
    final protected function formatLogMessage($message)
    {
        return sprintf('[%s](%s) %s', (string)$this->taskId, get_class($this), $message);
    }

    protected function getExecutionTime()
    {
        return $this->taskTimeEnd - $this->taskTimeStart;
    }

    protected function getTaskType()
    {
        return get_class($this);
    }

    protected function getResponseMessage()
    {
        return implode(" ", $this->responseLines);
    }

    protected function getStatus()
    {
        return $this->taskStatus;
    }

    final public function getMetaInfo()
    {
        return new \ArrayObject(
            ArrayUtils::merge(
                array(
                    'id' => $this->taskId,
                    'status' => $this->getStatus(),
                    'retC' => $this->getReturnCode(),
                    'exTime' => $this->getExecutionTime(),
                    'respM' => $this->getResponseMessage(),
                    'type' => $this->getTaskType(),
                    'exStart' => $this->taskExecutionStartTime
                ),
                $this->getAdditionalMetaInfo()
            )
        );
    }

    final public function setTaskId($taskId)
    {
        $this->taskId = $taskId;
    }

    final public function saveTaskMetaInfo()
    {
        if (!is_null($this->metaBackend) && ($this->metaBackend instanceof MetaStorage)) {
            $info = $this->getMetaInfo();
            $this->metaBackend->saveMetaInfo($info);
        }
    }

    /**
     * Change current state of task - to keep track of execution flow
     *
     * @param $state
     */
    protected function changeState($state)
    {
        $this->executionFlow[] = $state;
    }

    /**
     * Get last state task was in so far
     *
     * @return mixed|null
     */
    public function getLastState()
    {
        if (empty($this->executionFlow)) {
            return null;
        }
        return end($this->executionFlow);
    }

    /**
     * Get the whole execution flow
     *
     * @return array
     */
    public function getExecutionFlow()
    {
        return $this->executionFlow;
    }
}
