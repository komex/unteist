<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Processor\Controller;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Unteist\Event\EventStorage;
use Unteist\Event\TestCaseEvent;
use Unteist\Event\TestEvent;
use Unteist\Meta\TestMeta;
use Unteist\Processor\Runner;

/**
 * Class AbstractController
 *
 * @package Unteist\Processor\Controller
 */
abstract class AbstractController
{
    /**
     * @var Runner
     */
    protected $runner;
    /**
     * @var ContainerBuilder
     */
    protected $container;
    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;
    /**
     * @var EventDispatcherInterface
     */
    protected $precondition;
    /**
     * @var TestCaseEvent
     */
    protected $test_case_event;
    /**
     * @var array
     */
    protected $listeners = [];

    /**
     * @param ContainerBuilder $container
     */
    public function __construct(ContainerBuilder $container)
    {
        $this->container = $container;
        $this->dispatcher = $container->get('dispatcher');
    }

    /**
     * @param array $listeners
     */
    public function setListeners(array $listeners)
    {
        $this->listeners = $listeners;
    }

    /**
     * @param EventDispatcherInterface $precondition
     */
    public function setPrecondition(EventDispatcherInterface $precondition)
    {
        $this->precondition = $precondition;
    }

    /**
     * @param Runner $runner
     */
    public function setRunner(Runner $runner)
    {
        $this->runner = $runner;
    }

    /**
     * @param TestCaseEvent $test_case_event
     */
    public function setTestCaseEvent(TestCaseEvent $test_case_event)
    {
        $this->test_case_event = $test_case_event;
    }

    /**
     * Before all tests.
     */
    public function beforeCase()
    {
        try {
            $this->dispatcher->dispatch(EventStorage::EV_BEFORE_CASE, $this->test_case_event);
            $this->precondition->dispatch(EventStorage::EV_BEFORE_CASE);
        } catch (\Exception $e) {
            $controller = new SkipTestsController($this->container);
            $controller->setException($e);
            $this->runner->setController($controller);
        }
    }

    /**
     * Run the test.
     *
     * @param TestMeta $test
     *
     * @return int
     */
    abstract public function test(TestMeta $test);

    /**
     * All tests done.
     */
    public function afterCase()
    {
        $this->dispatcher->dispatch(EventStorage::EV_AFTER_CASE, $this->test_case_event);
        foreach ($this->listeners as $event => $listener) {
            $this->dispatcher->removeListener($event, $listener);
        }
    }

    /**
     * Before test.
     */
    protected function beforeTest(TestEvent $event)
    {
        $this->dispatcher->dispatch(EventStorage::EV_BEFORE_TEST, $event);
    }

    /**
     * Test done.
     */
    protected function afterTest(TestEvent $event)
    {
        $this->dispatcher->dispatch(EventStorage::EV_AFTER_TEST, $event);
    }
}
