<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <komexx@gmail.com>
 */

namespace Unteist\Report\Statistics;

use Unteist\Event\TestCaseEvent;
use Unteist\Event\TestEvent;
use Unteist\Meta\TestMeta;

/**
 * Class Processor
 *
 * @package Unteist\Meta
 * @author Andrey Kolchenko <komexx@gmail.com>
 */
class StatisticsProcessor implements \Iterator, \Countable
{
    /**
     * @var TestEvent[]
     */
    protected $events = [];
    /**
     * @var array
     */
    private $cache;
    /**
     * @var int
     */
    private $position = 0;

    /**
     * @param TestCaseEvent $events
     */
    public function __construct(TestCaseEvent $events = null)
    {
        if ($events !== null) {
            $this->addTestCaseEvents($events);
        }
    }

    /**
     * Add test events from case.
     *
     * @param TestCaseEvent $event
     */
    public function addTestCaseEvents(TestCaseEvent $event)
    {
        foreach ($event->getTestEvents() as $event) {
            array_push($this->events, $event);
        }
    }

    /**
     * Get total count of asserts in TestCase.
     *
     * @return int
     */
    public function getAsserts()
    {
        if (empty($this->cache)) {
            $this->rebuildCache();
        }

        return $this->cache['asserts'];
    }

    /**
     * Get execution time of TestCase.
     *
     * @return float
     */
    public function getTime()
    {
        if (empty($this->cache)) {
            $this->rebuildCache();
        }

        return $this->cache['time'];
    }

    /**
     * Get count of tests by its type name.
     *
     * @param string|null $type Test type
     *
     * @return int
     */
    public function getTestsCount($type = null)
    {
        if (empty($this->cache)) {
            $this->rebuildCache();
        }

        $type = strtolower($type);
        if (in_array($type, ['success', 'skipped', 'fail', 'error', 'incomplete'])) {
            return $this->cache[$type];
        }
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the current element
     *
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     */
    public function current()
    {
        return $this->events[$this->position];
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Move forward to next element
     *
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    public function next()
    {
        ++$this->position;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the key of the current element
     *
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Checks if current position is valid
     *
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    public function valid()
    {
        return isset($this->events[$this->position]);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Rewind the Iterator to the first element
     *
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Count elements of an object
     *
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     */
    public function count()
    {
        return count($this->events);
    }

    /**
     * Evaluate statistics.
     */
    private function rebuildCache()
    {
        $this->cache = [
            'asserts' => 0,
            'time' => 0,
            'success' => 0,
            'skipped' => 0,
            'fail' => 0,
            'error' => 0,
            'incomplete' => 0,
        ];
        foreach ($this->events as $event) {
            $this->cache['asserts'] += $event->getAsserts();
            $this->cache['time'] += $event->getTime();
            switch ($event->getStatus()) {
                case TestMeta::TEST_DONE:
                    $this->cache['success']++;
                    break;
                case TestMeta::TEST_SKIPPED:
                    $this->cache['skipped']++;
                    break;
                case TestMeta::TEST_FAILED:
                    $this->cache['fail']++;
                    break;
                case TestMeta::TEST_ERROR:
                    $this->cache['error']++;
                    break;
                case TestMeta::TEST_INCOMPLETE:
                    $this->cache['incomplete']++;
            }
        }
    }
}
