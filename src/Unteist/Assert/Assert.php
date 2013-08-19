<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert;

use Unteist\Assert\Matcher\AbstractMatcher;
use Unteist\Assert\Matcher\ArrayHasKey;
use Unteist\Assert\Matcher\Count;
use Unteist\Assert\Matcher\EqualTo;
use Unteist\Assert\Matcher\GreaterThan;
use Unteist\Assert\Matcher\GreaterThanOrEqual;
use Unteist\Assert\Matcher\IdenticalTo;
use Unteist\Assert\Matcher\IsEmpty;
use Unteist\Assert\Matcher\LessThan;
use Unteist\Assert\Matcher\LessThanOrEqual;
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
        self::that($array, new ArrayHasKey($key), $message);
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
        self::that($array, new Not(new ArrayHasKey($key)), $message);
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
     * Assert that actual variable not equal to expected variable.
     *
     * @param mixed $expected
     * @param mixed $actual
     * @param string $message
     */
    public static function notEquals($expected, $actual, $message = '')
    {
        self::that($actual, new Not(new EqualTo($expected)), $message);
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
     * Assert that actual variable not identical to expected variable.
     *
     * @param mixed $expected
     * @param mixed $actual
     * @param string $message
     */
    public static function notIdentical($expected, $actual, $message = '')
    {
        self::that($actual, new Not(new IdenticalTo($expected)), $message);
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
     * Assert that actual variable is not instance of specified class.
     *
     * @param mixed $expected
     * @param mixed $actual
     * @param string $message
     */
    public static function notSameInstance($expected, $actual, $message = '')
    {
        self::that($actual, new Not(new SameInstance($expected)), $message);
    }

    /**
     * Assert that string variable contains specified string.
     *
     * @param string $needle
     * @param string $haystack
     * @param string $message
     */
    public static function stringContains($needle, $haystack, $message = '')
    {
        self::that($haystack, new StringContains($needle), $message);
    }

    /**
     * Assert that string variable not contains specified string.
     *
     * @param string $haystack
     * @param string $needle
     * @param string $message
     */
    public static function stringNotContains($needle, $haystack, $message = '')
    {
        self::that($haystack, new Not(new StringContains($needle)), $message);
    }

    /**
     * Asserts the number of elements of an array, Countable or Traversable.
     *
     * @param int $expected
     * @param array|\Countable|\Traversable $actual
     * @param string $message
     *
     * @throws \InvalidArgumentException If actual variable has an invalid type
     */
    public static function count($expected, $actual, $message = '')
    {
        self::that($actual, new Count($expected), $message);
    }

    /**
     * Asserts the number of elements of an array, Countable or Traversable.
     *
     * @param int $expected
     * @param array|\Countable|\Traversable $actual
     * @param string $message
     *
     * @throws \InvalidArgumentException If actual variable has an invalid type
     */
    public static function notCount($expected, $actual, $message = '')
    {
        self::that($actual, new Not(new Count($expected)), $message);
    }

    /**
     * Assert that string variable starts with specified string.
     *
     * @param string $needle
     * @param string $haystack
     * @param string $message
     */
    public static function stringStartsWith($needle, $haystack, $message = '')
    {
        self::that($haystack, new StringStartsWith($needle), $message);
    }

    /**
     * Assert that string variable not starts with specified string.
     *
     * @param string $needle
     * @param string $haystack
     * @param string $message
     */
    public static function stringNotStartsWith($needle, $haystack, $message = '')
    {
        self::that($haystack, new Not(new StringStartsWith($needle)), $message);
    }

    /**
     * Assert that string variable ends with specified string.
     *
     * @param string $needle
     * @param string $haystack
     * @param string $message
     */
    public static function stringEndsWith($needle, $haystack, $message = '')
    {
        self::that($haystack, new StringEndsWith($needle), $message);
    }

    /**
     * Assert that string variable not ends with specified string.
     *
     * @param string $needle
     * @param string $haystack
     * @param string $message
     */
    public static function stringNotEndsWith($needle, $haystack, $message = '')
    {
        self::that($haystack, new Not(new StringEndsWith($needle)), $message);
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

    /**
     * Assert that actual variable has not specified type.
     *
     * @param mixed $expected
     * @param mixed $actual
     * @param string $message
     */
    public static function notTypeOf($expected, $actual, $message = '')
    {
        self::that($actual, new Not(new TypeOf($expected)), $message);
    }

    /**
     * Assert that actual variable is empty.
     *
     * @param mixed $actual
     * @param string $message
     */
    public static function isEmpty($actual, $message = '')
    {
        self::that($actual, new IsEmpty(), $message);
    }

    /**
     * Assert that actual variable is not empty.
     *
     * @param mixed $actual
     * @param string $message
     */
    public static function isNotEmpty($actual, $message = '')
    {
        self::that($actual, new Not(new IsEmpty()), $message);
    }

    /**
     * Assert that actual variable is greater than expected.
     *
     * @param mixed $expected
     * @param mixed $actual
     * @param string $message
     */
    public static function greaterThan($expected, $actual, $message = '')
    {
        self::that($actual, new GreaterThan($expected), $message);
    }

    /**
     * Assert that actual variable is greater than or equal expected.
     *
     * @param mixed $expected
     * @param mixed $actual
     * @param string $message
     */
    public static function greaterThanOrEqual($expected, $actual, $message = '')
    {
        self::that($actual, new GreaterThanOrEqual($expected), $message);
    }

    /**
     * Assert that actual variable is less than expected.
     *
     * @param mixed $expected
     * @param mixed $actual
     * @param string $message
     */
    public static function lessThan($expected, $actual, $message = '')
    {
        self::that($actual, new LessThan($expected), $message);
    }

    /**
     * Assert that actual variable is less than or equal expected.
     *
     * @param mixed $expected
     * @param mixed $actual
     * @param string $message
     */
    public static function lessThanOrEqual($expected, $actual, $message = '')
    {
        self::that($actual, new LessThanOrEqual($expected), $message);
    }
}
