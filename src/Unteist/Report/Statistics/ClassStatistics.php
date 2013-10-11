<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Report\Statistics;

use Unteist\Event\MethodEvent;
use Unteist\Event\TestCaseEvent;

/**
 * Class ClassStatistics
 *
 * @package Unteist\Report\Statistics
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class ClassStatistics implements \Countable, \Iterator
{
    /**
     * @var \ArrayIterator
     */
    private $storage;
    /**
     * @var int
     */
    private $asserts = 0;
    /**
     * @var int
     */
    private $time = 0;
    /**
     * @var int
     */
    private $passed = 0;
    /**
     * @var int
     */
    private $skipped = 0;
    /**
     * @var int
     */
    private $failed = 0;
    /**
     * @var int
     */
    private $incomplete = 0;
    /**
     * @var int
     */
    private $count = 0;

    public function __construct()
    {
        $this->storage = new \ArrayIterator();
    }

    /**
     * Return the current element.
     *
     * @return self
     */
    public function current()
    {
        return $this->storage->current();
    }

    /**
     * Move forward to next element.
     */
    public function next()
    {
        $this->storage->next();
    }

    /**
     * Return the TestCase name.
     *
     * @return string
     */
    public function key()
    {
        return $this->storage->key();
    }

    /**
     * Checks if current position is valid.
     *
     * @return bool The return value will be casted to boolean and then evaluated.
     */
    public function valid()
    {
        return $this->storage->valid();
    }

    /**
     * Rewind the Iterator to the first element.
     */
    public function rewind()
    {
        $this->storage->rewind();
    }

    /**
     * Count events.
     *
     * @return int
     */
    public function count()
    {
        return $this->count;
    }

    /**
     * @param \ArrayObject|MethodEvent[] $data
     */
    public function addEvents(\ArrayObject $data)
    {
        foreach ($data as $event) {
            $this->count++;
            $this->asserts += $event->getAsserts();
            $this->time += $event->getTime();
            switch ($event->getStatus()) {
                case MethodEvent::METHOD_OK:
                    $this->passed++;
                    break;
                case MethodEvent::METHOD_SKIPPED:
                    $this->skipped++;
                    break;
                case MethodEvent::METHOD_FAILED:
                    $this->failed++;
                    break;
                case MethodEvent::METHOD_INCOMPLETE:
                    $this->incomplete++;
                    break;
            }
        }
    }

    public function addStatistics(TestCaseEvent $event, self $statistics)
    {
        $this->storage[$event->getClass()] = $statistics;
    }

    /**
     * Get total number of asserts.
     *
     * @return int
     */
    public function getAsserts()
    {
        return $this->asserts;
    }

    /**
     * Get number of test cases.
     *
     * @return int
     */
    public function getClasses()
    {
        return count($this->storage);
    }

    /**
     * Get number of failed methods.
     *
     * @return int
     */
    public function getFailed()
    {
        return $this->failed;
    }

    /**
     * Get number of incomplete methods.
     *
     * @return int
     */
    public function getIncomplete()
    {
        return $this->incomplete;
    }

    /**
     * Get number of successful methods.
     *
     * @return int
     */
    public function getPassed()
    {
        return $this->passed;
    }

    /**
     * Get number of skipped methods.
     *
     * @return int
     */
    public function getSkipped()
    {
        return $this->skipped;
    }

    /**
     * Get total execution time.
     *
     * @return int
     */
    public function getTime()
    {
        return $this->time;
    }
}
