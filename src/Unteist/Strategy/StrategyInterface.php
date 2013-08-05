<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Strategy;

/**
 * Class StrategyInterface
 *
 * @package Unteist\Strategy
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
interface StrategyInterface
{
    /**
     * Generate new specified exception or do nothing.
     *
     * @param \Exception $exception
     *
     * @throws \Exception
     */
    public function generateException(\Exception $exception);
}
