<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <komexx@gmail.com>
 */

namespace Unteist\Report\Statistics;

use Unteist\Event\TestCaseEvent;
use Unteist\Meta\TestMeta;

/**
 * Class Processor
 *
 * @package Unteist\Meta
 * @author Andrey Kolchenko <komexx@gmail.com>
 */
class StatisticsProcessor
{
    /**
     * @var TestCaseEvent[]
     */
    protected $suite = [];
    /**
     * @var array
     */
    protected $cache;

    /**
     * Add test events from case.
     *
     * @param TestCaseEvent $event
     */
    public function addTestCaseEvents(TestCaseEvent $event)
    {
        array_push($this->suite, $event);
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
            $this->count();
        }

        $type = strtolower($type);
        if (in_array($type, ['success', 'skipped', 'fail', 'error', 'incomplete'])) {
            return $this->cache[$type];
        } else {
            return $this->cache['count'];
        }
    }

    /**
     * Evaluate statistics.
     */
    private function count()
    {
        $this->cache = [
            'count' => 0,
            'asserts' => 0,
            'time' => 0,
            'success' => 0,
            'skipped' => 0,
            'fail' => 0,
            'error' => 0,
            'incomplete' => 0,
        ];
        foreach ($this->suite as $case) {
            foreach ($case->getTestEvents() as $event) {
                $this->cache['count']++;
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
}
