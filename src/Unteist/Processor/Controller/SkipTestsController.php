<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Processor\Controller;

use Unteist\Event\EventStorage;
use Unteist\Event\MethodEvent;
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
     * @var string
     */
    private $depends;

    /**
     * @param string $depends
     */
    public function setDepends($depends)
    {
        $this->depends = $depends;
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
        $event = new MethodEvent();
        $event->setClass($test->getClass());
        $event->setMethod($test->getMethod());
        $this->beforeTest($event);
        $event->setStatus(MethodEvent::METHOD_SKIPPED);
        $depends = $test->getDependencies();
        if ($this->depends !== null) {
            array_unshift($depends, $this->depends);
        }
        $event->setDepends($depends);
        $this->dispatcher->dispatch(EventStorage::EV_METHOD_SKIPPED, $event);
        $this->afterTest($event);

        return 1;
    }
}
