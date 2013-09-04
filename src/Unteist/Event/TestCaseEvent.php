<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Event;

use Symfony\Component\EventDispatcher\Event;
use Unteist\Meta\TestMeta;

/**
 * Class TestCaseEvent
 *
 * @package Unteist\Event
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class TestCaseEvent extends Event
{
    /**
     * @var string
     */
    protected $class;
    /**
     * @var TestEvent[]
     */
    protected $test_events = [];

    /**
     * @param string $class Test case class
     */
    public function __construct($class)
    {
        $this->class = $class;
    }

    /**
     * Get test's namespace.
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Get test's events.
     *
     * @return TestEvent[]
     */
    public function getTestEvents()
    {
        return $this->test_events;
    }

    /**
     * Add a new event to TestCase.
     *
     * @param TestEvent $event
     */
    public function addTestEvent(TestEvent $event)
    {
        array_push($this->test_events, $event);
    }
}
