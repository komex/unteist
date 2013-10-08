<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Processor\Controller;

use Unteist\Event\EventStorage;
use Unteist\Event\TestEvent;
use Unteist\Meta\TestMeta;

/**
 * Class DummyController
 *
 * @package Unteist\Processor\Controller
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class SkipTestsController extends AbstractController
{
    /**
     * @var \Exception
     */
    private $exception;

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
    public function test(TestMeta $test)
    {
        $test->setStatus(TestMeta::TEST_SKIPPED);
        $event = new TestEvent($test->getMethod(), $this->test_case_event);
        $this->beforeTest($event);
        $event->setException($this->exception);
        $event->setStatus(TestMeta::TEST_SKIPPED);
        $event->setDepends($test->getDependencies());
        $this->dispatcher->dispatch(EventStorage::EV_TEST_SKIPPED, $event);
        $this->afterTest($event);

        return 1;
    }
}
