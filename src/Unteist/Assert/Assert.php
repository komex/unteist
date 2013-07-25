<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert;

use Unteist\Assert\Constraint\IsFalse;
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
     * Increase asserts counter.
     */
    public function incAssertsCount()
    {
        self::$count++;
    }

    /**
     * Assert that element is FALSE.
     *
     * @param mixed $element
     *
     * @throws \Unteist\Exception\AssertFailException
     */
    public static function isFalse($element)
    {
        $constraint = new IsFalse($element);
        if (!$constraint->matches()) {
            throw new AssertFailException($constraint->toString());
        }
        self::incAssertsCount();
    }
}