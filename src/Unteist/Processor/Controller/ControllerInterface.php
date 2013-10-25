<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Processor\Controller;

use Unteist\Event\MethodEvent;
use Unteist\Event\TestCaseEvent;
use Unteist\Meta\TestMeta;

/**
 * Interface ControllerInterface
 *
 * @package Unteist\Processor\Controller
 */
interface ControllerInterface
{
    /**
     * Actions before each test.
     *
     * @param TestCaseEvent $event
     */
    public function beforeCase(TestCaseEvent $event);

    /**
     * Resolve test dependencies.
     *
     * @param TestMeta $test
     */
    public function resolveDependencies(TestMeta $test);

    /**
     * Get test data set.
     *
     * @param TestMeta $test
     *
     * @return array[]
     */
    public function getDataSet(TestMeta $test);

    /**
     * Actions before each test.
     *
     * @param MethodEvent $event
     */
    public function beforeTest(MethodEvent $event);

    /**
     * Run test method.
     *
     * @param TestMeta $test Meta information about test method
     * @param MethodEvent $event Configured method event
     * @param array $dataSet Arguments for test
     *
     * @return int Status code
     */
    public function test(TestMeta $test, MethodEvent $event, array $dataSet);

    /**
     * Actions after each test.
     *
     * @param MethodEvent $event
     */
    public function afterTest(MethodEvent $event);

    /**
     * Actions after each case.
     *
     * @param TestCaseEvent $event
     */
    public function afterCase(TestCaseEvent $event);
}
