<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Strategy;


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
     * @param \Exception $exception
     */
    public function fail($exception)
    {

    }
}