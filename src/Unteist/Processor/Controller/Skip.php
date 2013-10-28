<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <komexx@gmail.com>
 */

namespace Unteist\Processor\Controller;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Unteist\Event\EventStorage;
use Unteist\Event\MethodEvent;
use Unteist\Event\TestCaseEvent;
use Unteist\Meta\TestMeta;

/**
 * Class Skip
 *
 * @package Unteist\Processor\Controller
 * @author Andrey Kolchenko <komexx@gmail.com>
 */
class Skip implements ControllerChildInterface
{
    /**
     * @var ControllerParentInterface
     */
    protected $parent;
    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;
    /**
     * @var string
     */
    private $depends;

    /**
     * Set parent controller for behavior controller.
     *
     * @param ControllerParentInterface $parent
     */
    public function setParent(ControllerParentInterface $parent)
    {
        $this->parent = $parent;
    }

    /**
     * Set additional depends which will be added to all test events.
     *
     * @param string $depends
     */
    public function setDepends($depends)
    {
        $this->depends = $depends;
    }

    /**
     * @param EventDispatcherInterface $dispatcher
     */
    public function setDispatcher(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Actions before each test.
     *
     * @param TestCaseEvent $event
     */
    public function beforeCase(TestCaseEvent $event)
    {

    }

    /**
     * Resolve test dependencies.
     *
     * @param TestMeta $test
     */
    public function resolveDependencies(TestMeta $test)
    {

    }

    /**
     * Get test data set.
     *
     * @param TestMeta $test
     *
     * @return array[]
     */
    public function getDataSet(TestMeta $test)
    {
        return [[]];
    }

    /**
     * Actions before each test.
     *
     * @param MethodEvent $event
     */
    public function beforeTest(MethodEvent $event)
    {
        $this->dispatcher->dispatch(EventStorage::EV_BEFORE_TEST, $event);
    }

    /**
     * Run test method.
     *
     * @param TestMeta $test Meta information about test method
     * @param MethodEvent $event Configured method event
     * @param array $dataSet Arguments for test
     *
     * @return int Status code
     */
    public function test(TestMeta $test, MethodEvent $event, array $dataSet)
    {
        $test->setStatus(TestMeta::TEST_SKIPPED);
        $event->setStatus(MethodEvent::METHOD_SKIPPED);
        if ($this->depends !== null) {
            $event->addDepend($this->depends);
        }
        $this->dispatcher->dispatch(EventStorage::EV_METHOD_SKIPPED, $event);

        return 1;
    }

    /**
     * Actions after each test.
     *
     * @param MethodEvent $event
     */
    public function afterTest(MethodEvent $event)
    {
        $this->dispatcher->dispatch(EventStorage::EV_AFTER_TEST, $event);
    }

    /**
     * Actions after each case.
     *
     * @param TestCaseEvent $event
     */
    public function afterCase(TestCaseEvent $event)
    {
        $this->dispatcher->dispatch(EventStorage::EV_AFTER_CASE, $event);
    }
}
