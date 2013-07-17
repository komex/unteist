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
     * @param \Exception $exception
     *
     * @throws \RuntimeException
     */
    public function fail(\Exception $exception)
    {
        throw new \RuntimeException($exception);
    }
}