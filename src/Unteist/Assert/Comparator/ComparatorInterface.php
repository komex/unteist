<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert\Comparator;

/**
 * Class ComparatorInterface
 *
 * @package Unteist\Assert\Comparator
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
interface ComparatorInterface
{
    /**
     * Returns whether the comparator can compare two values.
     *
     * @param mixed $expected The first value to compare
     * @param mixed $actual The second value to compare
     *
     * @return bool
     */
    public function accepts($expected, $actual);

    /**
     * Asserts that two values are equal.
     *
     * @param mixed $expected
     * @param mixed $actual
     * @param int $delta
     * @param bool $canonicalize
     * @param bool $ignore_case
     *
     * @return void
     */
    public function assertEquals($expected, $actual, $delta = 0, $canonicalize = false, $ignore_case = false);
}