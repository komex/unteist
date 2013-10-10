<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class MethodEvent
 *
 * @package Unteist\Event
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class MethodEvent extends Event
{
    /**
     * Method was finished with success status.
     */
    const METHOD_OK = 1;
    /**
     * Method was failed.
     */
    const METHOD_FAILED = 2;
    /**
     * Method was skipped.
     */
    const METHOD_SKIPPED = 3;
    /**
     * Method was incomplete.
     */
    const METHOD_INCOMPLETE = 4;
    /**
     * @var string
     */
    private $class;
    /**
     * @var string
     */
    private $method;
    /**
     * @var int
     */
    private $status = self::METHOD_OK;
    /**
     * @var int
     */
    private $asserts = 0;
    /**
     * @var double
     */
    private $time = 0;
    /**
     * @var array
     */
    private $depends = [];
    /**
     * @var int
     */
    private $data_set = 0;
    /**
     * @var string
     */
    private $exception;
    /**
     * @var string
     */
    private $exceptionMessage;
    /**
     * @var string
     */
    private $file;
    /**
     * @var int
     */
    private $line;
    /**
     * @var array
     */
    private $trace = [];

    /**
     * Get test's data set number.
     *
     * @return int
     */
    public function getDataSet()
    {
        return $this->data_set;
    }

    /**
     * Set test's data set number.
     *
     * @param int $data_set
     */
    public function setDataSet($data_set)
    {
        $this->data_set = intval($data_set, 10);
    }

    /**
     * Get test's depends.
     *
     * @return array
     */
    public function getDepends()
    {
        return $this->depends;
    }

    /**
     * Set test's depends.
     *
     * @param array $depends
     */
    public function setDepends(array $depends)
    {
        $this->depends = $depends;
    }

    /**
     * @return int
     */
    public function getAsserts()
    {
        return $this->asserts;
    }

    /**
     * @param int $asserts
     */
    public function setAsserts($asserts)
    {
        $this->asserts = $asserts;
    }

    /**
     * @return float
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @param float $time
     */
    public function setTime($time)
    {
        $this->time = $time;
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @param string $class
     */
    public function setClass($class)
    {
        $this->class = $class;
    }

    /**
     * @return string
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * Parse exception and set class, method and exception information.
     *
     * @param \Exception $exception
     */
    public function parseException(\Exception $exception)
    {
        while ($exception->getPrevious() !== null) {
            $exception = $exception->getPrevious();
        }
        $this->exception = get_class($exception);
        $this->exceptionMessage = $exception->getMessage();
        $trace = $exception->getTrace();
        $expected_call_user_func = false;
        $this->trace = [];
        foreach ($trace as $record) {
            unset($record['args']);
            array_push($this->trace, $record);
            if (empty($record['file']) && is_subclass_of($record['class'], '\\Unteist\\TestCase')) {
                $this->class = $record['class'];
                $this->method = $record['function'];
                $expected_call_user_func = true;
                continue;
            }
            if ($expected_call_user_func && substr($record['function'], 0, 14) === 'call_user_func') {
                array_pop($this->trace);

                return;
            } else {
                $expected_call_user_func = false;
            }
        }
    }

    /**
     * @return string
     */
    public function getExceptionMessage()
    {
        return $this->exceptionMessage;
    }

    /**
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param string $file
     */
    public function setFile($file)
    {
        $this->file = $file;
    }

    /**
     * @return int
     */
    public function getLine()
    {
        return $this->line;
    }

    /**
     * @param int $line
     */
    public function setLine($line)
    {
        $this->line = $line;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param string $method
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $status
     */
    public function setStatus($status)
    {
        $this->status = intval($status, 10);
    }
}
