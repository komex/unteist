<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Processor\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
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
     * @var ContainerInterface
     */
    protected $container;
    /**
     * @var ControllerChildInterface
     */
    protected $current;
    /**
     * @var Runner
     */
    protected $runner;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
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
     * @return ControllerChildInterface
     * @throws \InvalidArgumentException
     */
    public function switchTo($id)
    {
        if ($this->container->has($id)) {
            $this->current = $this->container->get($id);
            $this->current->setParent($this);

            return $this->current;
        } else {
            throw new \InvalidArgumentException(sprintf('Unknown controller id "%s".', $id));
        }
    }

    /**
     * Configure Controller.
     *
     * @param ControllerChildConfigurableInterface $controller
     */
    public function configurator(ControllerChildConfigurableInterface $controller)
    {
        $controller->setRunner($this->runner);
        $controller->setPrecondition($this->runner->getPrecondition());
    }

    /**
     * Actions before each test.
     *
     * @param TestCaseEvent $event
     */
    public function beforeCase(TestCaseEvent $event)
    {
        $this->current->beforeCase($event);
    }

    /**
     * Resolve test dependencies.
     *
     * @param TestMeta $test
     */
    public function resolveDependencies(TestMeta $test)
    {
        $this->current->resolveDependencies($test);
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
        return $this->current->getDataSet($test);
    }

    /**
     * Actions before each test.
     *
     * @param MethodEvent $event
     */
    public function beforeTest(MethodEvent $event)
    {
        $this->current->beforeTest($event);
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
        return $this->current->test($test, $event, $dataSet);
    }

    /**
     * Actions after each test.
     *
     * @param MethodEvent $event
     */
    public function afterTest(MethodEvent $event)
    {
        $this->current->afterTest($event);
    }

    /**
     * Actions after each test.
     *
     * @param TestCaseEvent $event
     */
    public function afterCase(TestCaseEvent $event)
    {
        $this->current->afterCase($event);
    }
}
