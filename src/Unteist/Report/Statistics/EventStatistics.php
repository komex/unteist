<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Report\Statistics;

use Unteist\Event\TestEvent;

/**
 * Class EventStatistics
 *
 * @package Unteist\Meta
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class EventStatistics extends StatisticsProcessor
{
    /**
     * Get test's namespace.
     *
     * @return string
     */
    public function getClass()
    {
        return $this->suite[0]->getClass();
    }

    /**
     * Get test's events.
     *
     * @return TestEvent[]
     */
    public function getTestEvents()
    {
        return $this->suite[0]->getTestEvents();
    }
}
