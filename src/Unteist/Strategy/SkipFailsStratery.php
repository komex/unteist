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
     * @param AssertException $exception
     *
     * @throws SkipException
     */
    public function assertFail(AssertException $exception)
    {
        throw new SkipException('The test has failed test in depends.', 0, $exception);
    }
}