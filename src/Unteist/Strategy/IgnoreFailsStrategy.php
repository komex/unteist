<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Strategy;

use Unteist\Exception\AssertException;
use Unteist\Exception\SkipException;


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
     * @param AssertException $exception
     */
    public function assertFail(AssertException $exception)
    {

    }

    /**
     * Skip test.
     *
     * @param SkipException $exception
     *
     * @throws SkipException
     */
    public function skipTest(SkipException $exception)
    {
        throw $exception;
    }
}