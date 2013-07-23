<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Strategy;

use Unteist\Exception\AssertFailException;
use Unteist\Exception\SkipTestException;


/**
 * Class IgnoreFailsStrategy
 *
 * @package Unteist\Strategy
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class IgnoreFailsStrategy
{
    /**
     * Doing nothing on test's fail.
     *
     * @param AssertFailException $exception
     */
    public function assertFail(AssertFailException $exception)
    {

    }

    /**
     * Skip test.
     *
     * @param SkipTestException $exception
     *
     * @throws SkipTestException
     */
    public function skipTest(SkipTestException $exception)
    {
        throw $exception;
    }
}