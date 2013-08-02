<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Strategy;

use Unteist\Exception\SkipTestException;

/**
 * Class SkipTestStrategy
 *
 * @package Unteist\Strategy
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class SkipTestStrategy implements StrategyInterface
{
    /**
     * Generate new specified exception or do nothing.
     *
     * @param \Exception $exception
     *
     * @throws SkipTestException
     */
    public function generateException(\Exception $exception)
    {
        if ($exception instanceof SkipTestException) {
            throw $exception;
        } else {
            throw new SkipTestException('Test skipped by chosen strategy', 0, $exception);
        }
    }
}
