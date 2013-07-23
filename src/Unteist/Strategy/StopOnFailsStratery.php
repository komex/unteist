<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Strategy;

use Unteist\Exception\AssertException;


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
     * @param AssertException $exception
     *
     * @throws AssertException
     */
    public function assertFail(AssertException $exception)
    {
        throw $exception;
    }
}