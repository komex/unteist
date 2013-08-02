<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Strategy;

use Unteist\Exception\TestFailException;
use Unteist\Exception\IncompleteTestException;
use Unteist\Exception\SkipTestException;

/**
 * Class Context
 *
 * @package Unteist\Strategy
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class Context
{
    /**
     * @var StrategyInterface
     */
    protected $error_strategy;
    /**
     * @var StrategyInterface
     */
    protected $failure_strategy;
    /**
     * @var StrategyInterface
     */
    protected $incomplete_strategy;
    /**
     * @var StrategyInterface
     */
    protected $skipped_strategy;
    /**
     * @var StrategyInterface[]
     */
    protected $default_strategies;

    /**
     * Setup default strategy.
     */
    public function __construct(
        StrategyInterface $error,
        StrategyInterface $failure,
        StrategyInterface $incomplete,
        StrategyInterface $skip
    ) {
        $this->default_strategies = [
            'error' => $error,
            'failure' => $failure,
            'incomplete' => $incomplete,
            'skip' => $skip,
        ];
        $this->restore();
    }

    /**
     * Restore default strategy.
     */
    public function restore()
    {
        $this->setErrorStrategy($this->default_strategies['error']);
        $this->setFailureStrategy($this->default_strategies['failure']);
        $this->setIncompleteStrategy($this->default_strategies['incomplete']);
        $this->setSkippedStrategy($this->default_strategies['skip']);
    }

    /**
     * Choose a strategy for the situation in error.
     *
     * @param StrategyInterface $error_strategy
     */
    public function setErrorStrategy(StrategyInterface $error_strategy)
    {
        $this->error_strategy = $error_strategy;
    }

    /**
     * Choose a strategy for the situation in failure test.
     *
     * @param StrategyInterface $failure_strategy
     */
    public function setFailureStrategy(StrategyInterface $failure_strategy)
    {
        $this->failure_strategy = $failure_strategy;
    }

    /**
     * Choose a strategy for the situation in incomplete test.
     *
     * @param StrategyInterface $incomplete_strategy
     */
    public function setIncompleteStrategy(StrategyInterface $incomplete_strategy)
    {
        $this->incomplete_strategy = $incomplete_strategy;
    }

    /**
     * Choose a strategy for the situation in skiped test.
     *
     * @param StrategyInterface $skipped_strategy
     */
    public function setSkippedStrategy(StrategyInterface $skipped_strategy)
    {
        $this->skipped_strategy = $skipped_strategy;
    }

    /**
     * Generate exception on unexpected behaviour.
     *
     * @param \Exception $exception
     *
     * @return int Status code
     */
    public function onError(\Exception $exception)
    {
        $this->error_strategy->generateException($exception);

        return 1;
    }

    /**
     * Generate exception on failure test.
     *
     * @param TestFailException $exception
     *
     * @return int Status code
     */
    public function onFailure(TestFailException $exception)
    {
        $this->failure_strategy->generateException($exception);

        return 1;
    }

    /**
     * Generate exception on incomplete test.
     *
     * @param IncompleteTestException $exception
     *
     * @return int Status code
     */
    public function onIncomplete(IncompleteTestException $exception)
    {
        $this->incomplete_strategy->generateException($exception);

        return 1;
    }

    /**
     * Generate exception on skip test.
     *
     * @param SkipTestException $exception
     *
     * @return int Status code
     */
    public function onSkip(SkipTestException $exception)
    {
        $this->incomplete_strategy->generateException($exception);

        return 1;
    }
}
