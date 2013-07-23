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
    protected $class;
    /**
     * @var TestEvent[]
     */
    protected $test_events = [];
    /**
     * @var array
     */
    private $cache;

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

    /**
     * Reset statistics cache.
     */
    public function resetCache()
    {
        $this->cache = null;
    }

    /**
     * Get total count of asserts in TestCase.
     *
     * @return int
     */
    public function getAsserts()
    {
        if (empty($this->cache)) {
            $this->count();
        }

        return $this->cache['asserts'];
    }

    /**
     * Evaluate statistics.
     */
    private function count()
    {
        $this->cache = [
            'asserts' => 0,
            'time' => 0,
        ];
        foreach ($this->test_events as $event) {
            $this->cache['asserts'] += $event->getAsserts();
            $this->cache['time'] += $event->getTime();
        }
    }

    /**
     * Get execution time of TestCase.
     *
     * @return float
     */
    public function getTime()
    {
        if (empty($this->cache)) {
            $this->count();
        }

        return $this->cache['time'];
    }
}