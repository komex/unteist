<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Strategy;

use Unteist\Exception\AssertException;
use Unteist\Exception\SkipException;


/**
 * Class Context
 *
 * @package Unteist\Strategy
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class Context
{
    /**
     * Ignore test fails and go to next one.
     */
    const STRATEGY_IGNORE_FAILS = 0;
    /**
     * Throw Exception on test fail.
     */
    const STRATEGY_STOP_ON_FAILS = 1;
    /**
     * Mark test as skipped on fail.
     */
    const STRATEGY_SKIP_FAILS = 2;
    /**
     * @var IgnoreFailsStrategy
     */
    protected $strategy;

    /**
     * Default context.
     */
    public function __construct()
    {
        $this->setStrategy(self::STRATEGY_STOP_ON_FAILS);
    }

    /**
     * Set current strategy.
     *
     * @param int $strategy
     */
    public function setStrategy($strategy)
    {
        switch ($strategy) {
            case self::STRATEGY_STOP_ON_FAILS:
                $this->strategy = new StopOnFailsStratery();
                break;
            case self::STRATEGY_SKIP_FAILS:
                $this->strategy = new SkipFailsStratery();
                break;
            default:
                $this->strategy = new IgnoreFailsStrategy();
        }
    }

    /**
     * Call this method on test fail.
     *
     * @param AssertException $exception
     *
     * @throws AssertException
     * @throws SkipException
     */
    public function assertFail(AssertException $exception)
    {
        $this->strategy->assertFail($exception);
    }

    /**
     * Skip current test.
     *
     * @param SkipException $exception
     */
    public function skipTest(SkipException $exception)
    {
        $this->strategy->skipTest($exception);
    }
}