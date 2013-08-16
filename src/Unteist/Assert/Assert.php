<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert;

use Unteist\Assert\Matcher\AbstractMatcher;
use Unteist\Assert\Matcher\ArrayHasKeys;
use Unteist\Assert\Matcher\EqualTo;
use Unteist\Assert\Matcher\IdenticalTo;
use Unteist\Assert\Matcher\Not;
use Unteist\Assert\Matcher\SameInstance;
use Unteist\Assert\Matcher\StringContains;
use Unteist\Assert\Matcher\StringEndsWith;
use Unteist\Assert\Matcher\StringStartsWith;
use Unteist\Assert\Matcher\TypeOf;

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
        self::that($actual, new IdenticalTo(false), $message);
    }

    /**
     * Make custom assert.
     *
     * @param mixed $actual
     * @param AbstractMatcher $matcher
     * @param string $message
     */
    public static function that($actual, AbstractMatcher $matcher, $message = '')
    {
        $matcher->match($actual, $message);
        self::$count++;
    }

    /**
     * Assert that element is TRUE.
     *
     * @param mixed $actual
     * @param string $message
     */
    public static function isTrue($actual, $message = '')
    {
        self::that($actual, new IdenticalTo(true), $message);
    }

    /**
     * Assert that element is NULL.
     *
     * @param mixed $actual
     * @param string $message
     */
    public static function isNull($actual, $message = '')
    {
        self::that($actual, new IdenticalTo(null), $message);
    }

    /**
     * Assert that element is not NULL.
     *
     * @param mixed $actual
     * @param string $message
     */
    public static function isNotNull($actual, $message = '')
    {
        self::that($actual, new Not(new IdenticalTo(null)), $message);
    }

    /**
     * Assert that array has specified key.
     *
     * @param array $array
     * @param string|int $key
     * @param string $message
     */
    public static function arrayHasKey(array $array, $key, $message = '')
    {
        self::that($array, new ArrayHasKeys((array)$key), $message);
    }

    /**
     * Assert that array not has specified key.
     *
     * @param array $array
     * @param string|int $key
     * @param string $message
     */
    public static function arrayNotHasKey(array $array, $key, $message = '')
    {
        self::that($array, new Not(new ArrayHasKeys((array)$key)), $message);
    }

    /**
     * Assert that actual variable equal to expected variable.
     *
     * @param mixed $expected
     * @param mixed $actual
     * @param string $message
     */
    public static function equals($expected, $actual, $message = '')
    {
        self::that($actual, new EqualTo($expected), $message);
    }

    /**
     * Assert that actual variable identical to expected variable.
     *
     * @param mixed $expected
     * @param mixed $actual
     * @param string $message
     */
    public static function identical($expected, $actual, $message = '')
    {
        self::that($actual, new IdenticalTo($expected), $message);
    }

    /**
     * Assert that actual variable is instance of specified class.
     *
     * @param mixed $expected
     * @param mixed $actual
     * @param string $message
     */
    public static function sameInstance($expected, $actual, $message = '')
    {
        self::that($actual, new SameInstance($expected), $message);
    }

    /**
     * Assert that string variable contains specified string.
     *
     * @param string $haystack
     * @param string $needle
     * @param string $message
     */
    public static function stringContains($haystack, $needle, $message = '')
    {
        self::that($haystack, new StringContains($needle), $message);
    }

    /**
     * Assert that string variable starts with specified string.
     *
     * @param string $haystack
     * @param string $needle
     * @param string $message
     */
    public static function stringStartsWith($haystack, $needle, $message = '')
    {
        self::that($haystack, new StringStartsWith($needle), $message);
    }

    /**
     * Assert that string variable ends with specified string.
     *
     * @param string $haystack
     * @param string $needle
     * @param string $message
     */
    public static function stringEndsWith($haystack, $needle, $message = '')
    {
        self::that($haystack, new StringEndsWith($needle), $message);
    }

    /**
     * Assert that actual variable has specified type.
     *
     * @param mixed $expected
     * @param mixed $actual
     * @param string $message
     */
    public static function typeOf($expected, $actual, $message = '')
    {
        self::that($actual, new TypeOf($expected), $message);
    }
}
