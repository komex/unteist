<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Event;

use Symfony\Component\EventDispatcher\Event;
use Unteist\Assert\Assert;
use Unteist\Meta\TestMeta;

/**
 * Class TestEvent
 *
 * @package Unteist\Event
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class TestEvent extends Event
{
    /**
     * @var string
     */
    protected $method;
    /**
     * @var TestCaseEvent
     */
    protected $test_case_event;
    /**
     * @var array
     */
    protected $depends = [];
    /**
     * @var int
     */
    protected $data_set = 0;
    /**
     * @var int
     */
    protected $status;
    /**
     * @var int
     */
    protected $asserts;
    /**
     * @var double
     */
    protected $time;
    /**
     * @var string
     */
    protected $exception_message;
    /**
     * @var string
     */
    protected $stacktrace;

    /**
     * @param string $method Test name
     * @param TestCaseEvent $test_case_event
     */
    public function __construct($method, TestCaseEvent $test_case_event)
    {
        $this->method = $method;
        $this->test_case_event = $test_case_event;
        $this->status = TestMeta::TEST_NEW;
        $test_case_event->addTestEvent($this);
    }

    /**
     * Get test status.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set test status.
     *
     * @param int $status
     */
    public function setStatus($status)
    {
        $this->status = intval($status, 10);
    }

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
     * Get test depends.
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
    public function setDepends($depends)
    {
        $this->depends = $depends;
    }

    /**
     * Get test name.
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Get parent TestCase event.
     *
     * @return TestCaseEvent
     */
    public function getTestCaseEvent()
    {
        return $this->test_case_event;
    }

    /**
     * Get count of asserts in this test.
     *
     * @return int
     */
    public function getAsserts()
    {
        return $this->asserts;
    }

    /**
     * Increment number of asserts in this test.
     *
     * @param int $asserts
     */
    public function setAsserts($asserts)
    {
        $this->asserts = $asserts;
    }

    /**
     * Get execution time.
     *
     * @return float
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * Set execution time.
     *
     * @param float $time
     */
    public function setTime($time)
    {
        $this->time = floatval($time);
    }

    /**
     * Get exception message.
     *
     * @return string
     */
    public function getExceptionMessage()
    {
        return $this->exception_message;
    }

    /**
     * Set exception to event.
     *
     * @param \Exception $exception
     */
    public function setException(\Exception $exception)
    {
        $this->exception_message = $exception->getMessage();
        $previous = $exception;
        do {
            $previous = $previous->getPrevious();
            if (empty($previous)) {
                break;
            }
            $this->exception_message .= PHP_EOL . $previous->getMessage();
        } while (true);
        foreach ($exception->getTrace() as $trace) {
            if (empty($trace['file'])) {
                break;
            }
            $this->stacktrace = $trace['file'] . ':' . $trace['line'];
        }
    }

    /**
     * Get exception stack trace.
     *
     * @return string
     */
    public function getStacktrace()
    {
        return $this->stacktrace;
    }

    /**
     * Is test success?
     *
     * @return bool
     */
    public function isSuccess()
    {
        return $this->status == TestMeta::TEST_DONE;
    }

    /**
     * Is test skipped.
     *
     * @return bool
     */
    public function isSkipped()
    {
        return $this->status == TestMeta::TEST_SKIPPED;
    }

    /**
     * Is test failed?
     *
     * @return bool
     */
    public function isFailed()
    {
        return $this->status == TestMeta::TEST_FAILED;
    }

    /**
     * Is test incomplete?
     *
     * @return bool
     */
    public function isIncomplete()
    {
        return $this->status == TestMeta::TEST_INCOMPLETE;
    }
}
