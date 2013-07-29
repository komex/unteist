<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert;

use Unteist\Assert\Matcher\AbstractMatcher;
use Unteist\Assert\Matcher\IdenticalTo;
use Unteist\Exception\AssertFailException;


/**
 * Class Assert
 *
 * @package Unteist\Assert
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class Assert
{
    /**
     * @var int
     */
    private static $count = 0;

    /**
     * Get number of asserts.
     *
     * @return int
     */
    public static function getAssertsCount()
    {
        return self::$count;
    }

    /**
     * Assert that element is FALSE.
     *
     * @param mixed $actual
     * @param string $message
     */
    public static function isFalse($actual, $message = '')
    {
        self::assertThat($actual, new IdenticalTo(false), $message);
    }

    /**
     * @param mixed $actual
     * @param AbstractMatcher $matcher
     * @param string $message
     */
    public static function assertThat($actual, AbstractMatcher $matcher, $message = '')
    {
        $matcher->match($actual, $message);
        self::$count++;
    }

    /**
     * @param string $message
     *
     * @throws AssertFailException
     */
    public static function fail($message = '')
    {
        throw new AssertFailException($message);
    }
}