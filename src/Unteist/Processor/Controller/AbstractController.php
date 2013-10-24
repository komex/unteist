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
     * @param TestMeta $test
     *
     * @return TestMeta
     */
    public function resolveDependencies(TestMeta $test)
    {

    }

    /**
     * @param TestMeta $test
     *
     * @return array
     */
    public function getDataSet(TestMeta $test)
    {
        return [[]];
    }

    /**
     * Run the test.
     *
     * @param TestMeta $test
     * @param MethodEvent $event
     * @param array $dataSet
     *
     * @return int Status code
     */
    abstract public function test(TestMeta $test, MethodEvent $event, array $dataSet);

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
    public function beforeTest(MethodEvent $event)
    {
        $this->dispatcher->dispatch(EventStorage::EV_BEFORE_TEST, $event);
    }

    /**
     * Test done.
     */
    public function afterTest(MethodEvent $event)
    {
        $this->dispatcher->dispatch(EventStorage::EV_AFTER_TEST, $event);
    }
}
