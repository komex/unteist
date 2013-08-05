<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Strategy;

use Unteist\Exception\IncompleteTestException;

/**
 * Class IncompleteTestStrategy
 *
 * @package Unteist\Strategy
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class IncompleteTestStrategy implements StrategyInterface
{
    /**
     * Generate new specified exception or do nothing.
     *
     * @param \Exception $exception
     *
     * @throws IncompleteTestException
     */
    public function generateException(\Exception $exception)
    {
        if ($exception instanceof IncompleteTestException) {
            throw $exception;
        } else {
            throw new IncompleteTestException('Test was marked as incomplete by chosen strategy.', 0, $exception);
        }
    }
}
