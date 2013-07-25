<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert\Comparator;

use Unteist\Exception\ComparisonFailure;


/**
 * Class Type
 *
 * @package Unteist\Assert\Comparator
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class Type implements ComparatorInterface
{
    /**
     * Returns whether the comparator can compare two values.
     *
     * @param mixed $expected The first value to compare
     * @param mixed $actual The second value to compare
     *
     * @return bool
     */
    public function accepts($expected, $actual)
    {
        return true;
    }

    /**
     * Asserts that two values are equal.
     *
     * @param mixed $expected
     * @param mixed $actual
     * @param int $delta
     * @param bool $canonicalize
     * @param bool $ignore_case
     *
     * @throws \Unteist\Exception\ComparisonFailure If expected and actual elements has different types.
     */
    public function assertEquals($expected, $actual, $delta = 0, $canonicalize = false, $ignore_case = false)
    {
        $expected_type = gettype($expected);
        $actual_type = gettype($actual);
        if ($expected_type != $actual_type) {
            throw new ComparisonFailure('', '', sprintf(
                '%s does not match expected type "%s".',
                $actual_type,
                $expected_type
            ));
        }
    }
} 