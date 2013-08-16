<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Strategy;

use Unteist\Exception\TestErrorException;
use Unteist\Exception\TestFailException;
use Unteist\Exception\IncompleteTestException;

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
     * Setup default strategy.
     */
    public function __construct(StrategyInterface $error, StrategyInterface $failure, StrategyInterface $incomplete)
    {
        $this->setErrorStrategy($error);
        $this->setFailureStrategy($failure);
        $this->setIncompleteStrategy($incomplete);
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
     * Generate exception on unexpected behaviour.
     *
     * @param TestErrorException $exception
     *
     * @return int Status code
     */
    public function onError(TestErrorException $exception)
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
}
