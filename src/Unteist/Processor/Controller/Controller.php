<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Processor\Controller;

use Unteist\Event\TestCaseEvent;
use Unteist\Meta\TestMeta;
use Unteist\Event\MethodEvent;
use Unteist\Processor\Runner;

/**
 * Class Controller
 *
 * @package Unteist\Processor\Controller
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class Controller implements ControllerParentInterface
{
    /**
     * @var ControllerInterface[]
     */
    protected $controllers;
    /**
     * @var string
     */
    protected $current;
    /**
     * @var Runner
     */
    protected $runner;

    /**
     * @return Runner
     */
    public function getRunner()
    {
        return $this->runner;
    }

    /**
     * @param Runner $runner
     */
    public function setRunner(Runner $runner)
    {
        $this->runner = $runner;
    }

    /**
     * Use specified controller.
     *
     * @param string $id
     *
     * @throws \InvalidArgumentException
     */
    public function switchTo($id)
    {
        if (isset($this->controllers[$id])) {
            $this->current = $id;
        } else {
            $message = sprintf('Unknown controller id "%s". ', $id);
            if (empty($this->controllers)) {
                $message .= 'Controllers list is empty.';
            } else {
                $message .= 'Allowed ' . join(', ', array_keys($this->controllers)) . '.';
            }
            throw new \InvalidArgumentException($message);
        }
    }

    /**
     * @param ControllerChildInterface $controller
     * @param string $id
     */
    public function add(ControllerChildInterface $controller, $id)
    {
        $controller->setParent($this);
        $this->controllers[$id] = $controller;
    }

    /**
     * Actions before each test.
     *
     * @param TestCaseEvent $event
     */
    public function beforeCase(TestCaseEvent $event)
    {
        $this->controllers[$this->current]->beforeCase($event);
    }

    /**
     * Resolve test dependencies.
     *
     * @param TestMeta $test
     */
    public function resolveDependencies(TestMeta $test)
    {
        $this->controllers[$this->current]->resolveDependencies($test);
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
        return $this->controllers[$this->current]->getDataSet($test);
    }

    /**
     * Actions before each test.
     *
     * @param MethodEvent $event
     */
    public function beforeTest(MethodEvent $event)
    {
        $this->controllers[$this->current]->beforeTest($event);
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
        return $this->controllers[$this->current]->test($test, $event, $dataSet);
    }

    /**
     * Actions after each test.
     *
     * @param MethodEvent $event
     */
    public function afterTest(MethodEvent $event)
    {
        $this->controllers[$this->current]->afterTest($event);
    }

    /**
     * Actions after each test.
     *
     * @param TestCaseEvent $event
     */
    public function afterCase(TestCaseEvent $event)
    {
        $this->controllers[$this->current]->afterCase($event);
    }
}
