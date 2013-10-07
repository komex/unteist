<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Processor\Controller;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Unteist\Event\EventStorage;
use Unteist\Event\TestCaseEvent;
use Unteist\Event\TestEvent;
use Unteist\Meta\TestMeta;

/**
 * Class DummyController
 *
 * @package Unteist\Processor\Controller
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class SkipTestsController
{
    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;
    /**
     * @var TestCaseEvent
     */
    protected $test_case_event;
    /**
     * @var array
     */
    protected $listeners = [];
    /**
     * @var \Exception
     */
    private $exception;

    /**
     * @param EventDispatcherInterface $dispatcher
     * @param TestCaseEvent $test_case_event
     * @param array $listeners
     */
    public function __construct(EventDispatcherInterface $dispatcher, TestCaseEvent $test_case_event, array $listeners)
    {
        $this->dispatcher = $dispatcher;
        $this->listeners = $listeners;
        $this->test_case_event = $test_case_event;
    }

    /**
     * @param EventDispatcherInterface $dispatcher
     */
    public function setDispatcher(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param array $listeners
     */
    public function setListeners(array $listeners)
    {
        $this->listeners = $listeners;
    }

    /**
     * @param TestCaseEvent $test_case_event
     */
    public function setTestCaseEvent(TestCaseEvent $test_case_event)
    {
        $this->test_case_event = $test_case_event;
    }

    /**
     * @param \Exception $exception
     */
    public function setException(\Exception $exception)
    {
        $this->exception = $exception;
    }

    /**
     * Run test.
     *
     * @param TestMeta $test
     *
     * @return int
     */
    public function run(TestMeta $test)
    {
        $test->setStatus(TestMeta::TEST_SKIPPED);
        $event = new TestEvent($test->getMethod(), $this->test_case_event);
        $event->setException($this->exception);
        $event->setStatus(TestMeta::TEST_SKIPPED);
        $event->setDepends($test->getDependencies());
        $this->dispatcher->dispatch(EventStorage::EV_TEST_SKIPPED, $event);

        return 1;
    }

    /**
     * All tests done. Generate EV_AFTER_CASE event.
     */
    public function afterCase()
    {
        $this->dispatcher->dispatch(EventStorage::EV_AFTER_CASE, $this->test_case_event);
        foreach ($this->listeners as $event => $listener) {
            $this->dispatcher->removeListener($event, $listener);
        }
    }
}
