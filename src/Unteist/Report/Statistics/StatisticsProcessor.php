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
class StatisticsProcessor implements \Iterator, \Countable, \ArrayAccess
{
    /**
     * @var TestEvent[]
     */
    protected $events = [];
    /**
     * @var array
     */
    private $cache = [
        'asserts' => 0,
        'time' => 0,
        'success' => 0,
        'skipped' => 0,
        'fail' => 0,
        'error' => 0,
        'incomplete' => 0,
    ];
    /**
     * @var int
     */
    private $position = 0;
    /**
     * @var bool
     */
    private $rebuild_cache = true;

    /**
     * @param TestCaseEvent $events
     */
    public function __construct(TestCaseEvent $events = null)
    {
        if ($events !== null) {
            $this->addTestCaseEvent($events);
        }
    }

    /**
     * Add test events from case.
     *
     * @param TestCaseEvent $event
     */
    public function addTestCaseEvent(TestCaseEvent $event)
    {
        foreach ($event->getTestEvents() as $event) {
            array_push($this->events, $event);
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
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Whether a offset exists
     *
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     *
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     *
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset)
    {
        return isset($this->cache[$offset]);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to retrieve
     *
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     *
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     *
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
        if ($this->rebuild_cache) {
            $this->rebuildCache();
        }
        $type = strtolower($offset);

        return ($this->offsetExists($type) ? $this->cache[$offset] : null);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to set
     *
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     *
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     *
     * @throws \UnderflowException
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        throw new \UnderflowException('You can not set value to ' . __CLASS__);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to unset
     *
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     *
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     *
     * @throws \UnderflowException
     * @return void
     */
    public function offsetUnset($offset)
    {
        throw new \UnderflowException('You can not unset value from ' . __CLASS__);
    }

    /**
     * Evaluate statistics.
     */
    private function rebuildCache()
    {
        $this->rebuild_cache = false;
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
