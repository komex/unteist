<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert;

use Unteist\Event\TestEvent;
use Unteist\Exception\AssertFailException;


/**
 * Class AssertArray
 *
 * @package Unteist\Assert
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 * @method TestEvent getTestEvent()
 */
trait AssertArray
{
    /**
     * @param $key
     * @param array $array
     * @param string $message
     *
     * @throws \Unteist\Exception\AssertFailException
     */
    public function assertArrayHasKey($key, array $array, $message = '')
    {
        if (!array_key_exists($key, $array)) {
            $message = sprintf('Failed assert that an array has a key "%s".%s%s', $key, PHP_EOL, $message);
            throw new AssertFailException($message);
        }
        $event = $this->getTestEvent();
        $event->incAsserts();
    }

    /**
     * @param $key
     * @param array $array
     * @param string $message
     *
     * @throws \Unteist\Exception\AssertFailException
     */
    public function assertArrayNotHasKey($key, array $array, $message = '')
    {
        if (array_key_exists($key, $array)) {
            $message = sprintf('Failed assert that an array has not a key "%s".%s%s', $key, PHP_EOL, $message);
            throw new AssertFailException($message);
        }
        $event = $this->getTestEvent();
        $event->incAsserts();
    }
}