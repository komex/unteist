<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Strategy;

use Unteist\Exception\TestFailException;

/**
 * Class TestFailStrategy
 *
 * @package Unteist\Strategy
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class TestFailStrategy implements StrategyInterface
{
    /**
     * Generate new specified exception or do nothing.
     *
     * @param \Exception $exception
     *
     * @throws TestFailException
     */
    public function generateException(\Exception $exception)
    {
        if ($exception instanceof TestFailException) {
            throw $exception;
        } else {
            throw new TestFailException('Test was marked as failure by chosen strategy', 0, $exception);
        }
    }
}
