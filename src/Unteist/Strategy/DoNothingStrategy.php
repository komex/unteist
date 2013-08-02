<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Strategy;

/**
 * Class DoNothingStrategy
 *
 * @package Unteist\Strategy
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class DoNothingStrategy implements StrategyInterface
{
    /**
     * Generate new specified exception or do nothing.
     *
     * @param \Exception $exception
     */
    public function generateException(\Exception $exception)
    {

    }
}
