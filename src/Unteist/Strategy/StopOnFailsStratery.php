<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Strategy;

use Unteist\Exception\AssertFailException;

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
     * @param AssertFailException $exception
     *
     * @throws AssertFailException
     */
    public function assertFail(AssertFailException $exception)
    {
        throw $exception;
    }
}
