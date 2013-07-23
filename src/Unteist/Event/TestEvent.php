<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Event;

use Symfony\Component\EventDispatcher\Event;
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
     * @var array
     */
    protected $data_set = [];
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
     * Get test's data set.
     *
     * @return array
     */
    public function getDataSet()
    {
        return $this->data_set;
    }

    /**
     * Set test's data set.
     *
     * @param array $data_set
     */
    public function setDataSet($data_set)
    {
        $this->data_set = $data_set;
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
     */
    public function incAsserts()
    {
        $this->asserts++;
        $this->test_case_event->resetCache();
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
        $this->test_case_event->resetCache();
    }
}