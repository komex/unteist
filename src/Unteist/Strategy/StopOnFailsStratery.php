<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Strategy;


/**
 * Class StopOnFailsStratery
 *
 * @package Unteist\Strategy
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class StopOnFailsStratery extends IgnoreFailsStrategy
{
    /**
     * Prevent executing next tests.
     *
     * @param \RuntimeException $exception
     *
     * @throws \RuntimeException
     */
    public function fail(\RuntimeException $exception)
    {
        throw $exception;
    }
}