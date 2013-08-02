<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Strategy;

use Unteist\Exception\AssertFailException;

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
     * @throws AssertFailException
     */
    public function generateException(\Exception $exception)
    {
        if ($exception instanceof AssertFailException) {
            throw $exception;
        } else {
            throw new AssertFailException('Test was marked as failure by chosen strategy.', 0, $exception);
        }
    }
}
