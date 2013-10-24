<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Processor\Controller;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Unteist\Event\EventStorage;
use Unteist\Event\MethodEvent;
use Unteist\Event\TestCaseEvent;
use Unteist\Meta\TestMeta;

/**
 * Class AbstractController
 *
 * @package Unteist\Processor\Controller
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
abstract class AbstractController extends ContainerAware
{
    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @param EventDispatcherInterface $dispatcher
     */
    public function setDispatcher(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
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
    public function afterCase(TestCaseEvent $testCaseEvent)
    {
        $this->dispatcher->dispatch(EventStorage::EV_AFTER_CASE, $testCaseEvent);
    }

    /**
     * Before test.
     */
    protected function beforeTest(MethodEvent $event)
    {
        $this->dispatcher->dispatch(EventStorage::EV_BEFORE_TEST, $event);
    }

    /**
     * Test done.
     */
    protected function afterTest(MethodEvent $event)
    {
        $this->dispatcher->dispatch(EventStorage::EV_AFTER_TEST, $event);
    }
}
