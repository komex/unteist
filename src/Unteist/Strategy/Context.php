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
    protected $errorStrategy;
    /**
     * @var StrategyInterface
     */
    protected $failureStrategy;
    /**
     * @var StrategyInterface
     */
    protected $incompleteStrategy;
    /**
     * @var StrategyInterface
     */
    protected $beforeCaseStrategy;
    /**
     * @var StrategyInterface
     */
    protected $beforeTestStrategy;
    /**
     * @var StrategyInterface
     */
    protected $afterTestStrategy;
    /**
     * @var StrategyInterface
     */
    protected $afterCaseStrategy;
    /**
     * @var StrategyInterface[]
     */
    protected $customExceptions = [];

    /**
     * Associate exception with system strategy.
     *
     * @param string $exception Exception class name
     * @param StrategyInterface $strategy
     */
    public function associateException($exception, StrategyInterface $strategy)
    {
        $this->customExceptions[$exception] = $strategy;
    }

    /**
     * Choose a strategy for the situation in error.
     *
     * @param StrategyInterface $strategy
     */
    public function setErrorStrategy(StrategyInterface $strategy)
    {
        $this->errorStrategy = $strategy;
    }

    /**
     * Choose a strategy for the situation in failure test.
     *
     * @param StrategyInterface $strategy
     */
    public function setFailureStrategy(StrategyInterface $strategy)
    {
        $this->failureStrategy = $strategy;
    }

    /**
     * Choose a strategy for the situation in incomplete test.
     *
     * @param StrategyInterface $strategy
     */
    public function setIncompleteStrategy(StrategyInterface $strategy)
    {
        $this->incompleteStrategy = $strategy;
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
        $this->errorStrategy->generateException($exception);

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
        $this->failureStrategy->generateException($exception);

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
        $this->incompleteStrategy->generateException($exception);

        return 1;
    }

    /**
     * Generate exception on beforeCase fail.
     *
     * @param \Exception $exception
     *
     * @return int Status code
     */
    public function onBeforeCase(\Exception $exception)
    {
        $this->beforeCaseStrategy->generateException($exception);

        return 1;
    }

    /**
     * Generate exception on beforeTest fail.
     *
     * @param \Exception $exception
     *
     * @return int Status code
     */
    public function onBeforeTest(\Exception $exception)
    {
        $this->beforeTestStrategy->generateException($exception);

        return 1;
    }

    /**
     * Generate exception on afterTest fail.
     *
     * @param \Exception $exception
     *
     * @return int Status code
     */
    public function onAfterTest(\Exception $exception)
    {
        $this->afterTestStrategy->generateException($exception);

        return 1;
    }

    /**
     * Generate exception on afterCase fail.
     *
     * @param \Exception $exception
     *
     * @return int Status code
     */
    public function onAfterCase(\Exception $exception)
    {
        $this->afterCaseStrategy->generateException($exception);

        return 1;
    }

    /**
     * @param StrategyInterface $afterCaseStrategy
     */
    public function setAfterCaseStrategy(StrategyInterface $afterCaseStrategy)
    {
        $this->afterCaseStrategy = $afterCaseStrategy;
    }

    /**
     * @param StrategyInterface $afterTestStrategy
     */
    public function setAfterTestStrategy(StrategyInterface $afterTestStrategy)
    {
        $this->afterTestStrategy = $afterTestStrategy;
    }

    /**
     * @param StrategyInterface $beforeCaseStrategy
     */
    public function setBeforeCaseStrategy(StrategyInterface $beforeCaseStrategy)
    {
        $this->beforeCaseStrategy = $beforeCaseStrategy;
    }

    /**
     * @param StrategyInterface $beforeTestStrategy
     */
    public function setBeforeTestStrategy(StrategyInterface $beforeTestStrategy)
    {
        $this->beforeTestStrategy = $beforeTestStrategy;
    }

    /**
     * Check associated strategy with specified exception.
     *
     * @param \Exception $exception
     *
     * @throws \Exception
     */
    public function onUnexpectedException(\Exception $exception)
    {
        if (isset($this->customExceptions[get_class($exception)])) {
            $this->customExceptions[get_class($exception)]->generateException($exception);
        } else {
            throw $exception;
        }
    }
}
