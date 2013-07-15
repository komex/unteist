<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Event;

use Symfony\Component\EventDispatcher\Event;
use Unteist\Processor\TestRunner;

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
    protected $name;
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
     * @param string $name Test name
     * @param TestCaseEvent $test_case_event
     */
    public function __construct($name, TestCaseEvent $test_case_event)
    {
        $this->name = $name;
        $this->test_case_event = $test_case_event;
        $this->status = TestRunner::TEST_NEW;
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
    public function getName()
    {
        return $this->name;
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
}