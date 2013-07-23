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
 * Class SkipFailsStratery
 *
 * @package Unteist\Strategy
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class SkipFailsStratery extends IgnoreFailsStrategy
{
    /**
     * The depends test was fail. Skip base test.
     *
     * @param AssertFailException $exception
     *
     * @throws SkipTestException
     */
    public function assertFail(AssertFailException $exception)
    {
        throw new SkipTestException('The test has failed test in depends.', 0, $exception);
    }
}