<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Processor;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Unteist\Event\EventStorage;
use Unteist\Event\TestEvent;


/**
 * Class StatusSwitcher
 *
 * @package Unteist\Processor
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class StatusSwitcher
{
    /**
     * @var \SplObjectStorage
     */
    protected $tests;
    /**
     * @var TestEvent
     */
    protected $test_event;
    /**
     * @var EventDispatcher
     */
    protected $precondition;
    /**
     * @var EventDispatcher
     */
    protected $dispatcher;

    /**
     * @param \ArrayObject $tests
     * @param EventDispatcher $precondition
     * @param EventDispatcher $dispatcher
     */
    public function __construct(\ArrayObject $tests, EventDispatcher $precondition, EventDispatcher $dispatcher)
    {
        $this->tests = $tests;
        $this->precondition = $precondition;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Set base event for test.
     *
     * @param TestEvent $test_event
     */
    public function setTestEvent(TestEvent $test_event)
    {
        $this->test_event = $test_event;
    }

    /**
     * Mark test as currently in work.
     *
     * @param string $method
     *
     * @throws \InvalidArgumentException If test not found.
     */
    public function marked($method)
    {
        if (empty($this->tests[$method])) {
            throw new \InvalidArgumentException(sprintf('Test with name "%s" does not found.', $method));
        }
        $this->tests[$method]['status'] = TestRunner::TEST_MARKED;
    }

    /**
     * Mark test as well done.
     *
     * @param string $method
     *
     * @throws \InvalidArgumentException If test not found.
     */
    public function done($method)
    {
        if (empty($this->tests[$method])) {
            throw new \InvalidArgumentException(sprintf('Test with name "%s" does not found.', $method));
        }
        $this->tests[$method]['status'] = TestRunner::TEST_DONE;
        $this->test_event->setStatus(TestRunner::TEST_DONE);
        $this->eventAfterTest();
    }

    /**
     * Mark test as skipped.
     *
     * @param string $method
     *
     * @throws \InvalidArgumentException If test not found.
     */
    public function skipped($method)
    {
        if (empty($this->tests[$method])) {
            throw new \InvalidArgumentException(sprintf('Test with name "%s" does not found.', $method));
        }
        $this->tests[$method]['status'] = TestRunner::TEST_SKIPPED;
        $this->test_event->setStatus(TestRunner::TEST_SKIPPED);
        $this->dispatcher->dispatch(EventStorage::EV_TEST_SKIPPED, $this->test_event);
    }

    /**
     * Mark test as failed.
     *
     * @param string $method
     *
     * @throws \InvalidArgumentException If test not found.
     */
    public function failed($method)
    {
        if (empty($this->tests[$method])) {
            throw new \InvalidArgumentException(sprintf('Test with name "%s" does not found.', $method));
        }
        $this->tests[$method]['status'] = TestRunner::TEST_FAILED;
        $this->test_event->setStatus(TestRunner::TEST_FAILED);
        $this->dispatcher->dispatch(EventStorage::EV_TEST_FAIL, $this->test_event);
        $this->eventAfterTest();
    }

    /**
     * Generate EventStorage::EV_AFTER_TEST event.
     */
    public function eventAfterTest()
    {
        $this->precondition->dispatch(EventStorage::EV_AFTER_TEST, $this->test_event);
        $this->dispatcher->dispatch(EventStorage::EV_AFTER_TEST, $this->test_event);
    }
}