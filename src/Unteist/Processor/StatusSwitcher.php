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
     * @param \SplObjectStorage $tests
     * @param EventDispatcher $precondition
     * @param EventDispatcher $dispatcher
     */
    public function __construct(\SplObjectStorage $tests, EventDispatcher $precondition, EventDispatcher $dispatcher)
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
     * @param \ReflectionMethod $method
     */
    public function marked(\ReflectionMethod $method)
    {
        $data = $this->tests->offsetGet($method);
        $data['status'] = TestRunner::TEST_MARKED;
        $this->tests->offsetSet($method, $data);
    }

    /**
     * @param \ReflectionMethod $method
     */
    public function done(\ReflectionMethod $method)
    {
        $data = $this->tests->offsetGet($method);
        $data['status'] = TestRunner::TEST_DONE;
        $this->tests->offsetSet($method, $data);
        $this->test_event->setStatus(TestRunner::TEST_DONE);
        $this->eventAfterTest();
    }

    /**
     * @param \ReflectionMethod $method
     */
    public function skipped(\ReflectionMethod $method)
    {
        $data = $this->tests->offsetGet($method);
        $data['status'] = TestRunner::TEST_SKIPPED;
        $this->tests->offsetSet($method, $data);
        $this->test_event->setStatus(TestRunner::TEST_SKIPPED);
        $this->dispatcher->dispatch(EventStorage::EV_TEST_SKIPPED, $this->test_event);
    }

    /**
     * @param \ReflectionMethod $method
     */
    public function failed(\ReflectionMethod $method)
    {
        $data = $this->tests->offsetGet($method);
        $data['status'] = TestRunner::TEST_FAILED;
        $this->tests->offsetSet($method, $data);
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