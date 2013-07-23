<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Strategy;

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
     * @param \RuntimeException $exception
     *
     * @throws SkipException
     */
    public function fail(\RuntimeException $exception)
    {
        throw new SkipException($exception);
    }
}