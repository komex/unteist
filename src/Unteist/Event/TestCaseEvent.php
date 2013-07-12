<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Event;

use Symfony\Component\EventDispatcher\Event;

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
    protected $name;
    /**
     * @var TestEvent[]
     */
    protected $test_events = [];

    /**
     * @param string $name Test case namespace
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Get test's namespace.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
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