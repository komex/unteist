<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert;

use Unteist\Exception\AssertException;


/**
 * Class AssertArray
 *
 * @package Unteist\Assert
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 * @method incAsserts
 */
trait AssertArray
{
    /**
     * @param $key
     * @param array $array
     * @param string $message
     *
     * @throws \Unteist\Exception\AssertException
     */
    public function assertArrayHasKey($key, array $array, $message = '')
    {
        if (!array_key_exists($key, $array)) {
            $message = sprintf('Failed assert that an array has a key "%s".%s%s', $key, PHP_EOL, $message);
            throw new AssertException($message);
        }
        $this->incAsserts();
    }

    /**
     * @param $key
     * @param array $array
     * @param string $message
     *
     * @throws \Unteist\Exception\AssertException
     */
    public function assertArrayNotHasKey($key, array $array, $message = '')
    {
        if (array_key_exists($key, $array)) {
            $message = sprintf('Failed assert that an array has not a key "%s".%s%s', $key, PHP_EOL, $message);
            throw new AssertException($message);
        }
        $this->incAsserts();
    }
}